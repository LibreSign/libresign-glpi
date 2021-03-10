<?php

use GuzzleHttp\Exception\RequestException;

class PluginLibresignConfig extends CommonDBTM
{
    private static $_instance = null;
    static $rightname         = 'config';

    static function canCreate()
    {
        return Session::haveRight('config', UPDATE);
    }

    static function canView()
    {
        return Session::haveRight('config', READ);
    }

    function update(array $input, $history = 1, $options = []) {
        include_once(Plugin::getPhpDir('libresign') . '/inc/httpclient.class.php');
        $client = new PluginLibresignHttpclient();
        try {
            $url = str_replace('/register', '/me', $input['nextcloud_url']);
            $response = $client->request('GET', $url, [
                'auth' => [$input['username'], $input['password']]
            ]);
            $json = $response->getBody()->getContents();
            if (!$json) {
                throw new \Exception(__('Invalid settings'));
            }
        } catch (RequestException $th) {
            if ($th->getResponse()) {
                $message = $th->getResponse()->getBody()->getContents();
                if (preg_match('/<h2>(?<error>.*)<\/h2>/', $message, $matches)) {
                    $message = $matches['error'];
                }
                if (json_decode($message)) {
                    $message = json_decode($message)->message;
                }
            }
            if (!$message) {
                $message = $th->getMessage();
            }
            Session::addMessageAfterRedirect(
                __($message),
                false,
                ERROR
            );
            return;
        }
        parent::update($input, $history, $options);
    }

   /**
    * Singleton for the unique config record
    */
    static function getInstance()
    {

        if (!isset(self::$_instance)) {
            self::$_instance = new self();
            if (!self::$_instance->getFromDB(1)) {
                self::$_instance->getEmpty();
            }
        }
        return self::$_instance;
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            if ($item->getType() == 'Config') {
                return __('Libresign');
            }
        }
        return '';
    }

    function showConfigForm()
    {
        $config = self::getInstance();

        $config->showFormHeader();

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='nextcloud_url'>" . __('URL of the API') . "</label></td>";
        echo "<td colspan='3'><input type='text' name='nextcloud_url' id='nextcloud_url' size='80' value='" . $config->fields["nextcloud_url"] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='username'>" . __('Username') . "</label></td>";
        echo "<td colspan='3'><input type='text' name='username' id='username' size='80' value='" . $config->fields["username"] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='password'>" . __('Password') . "</label></td>";
        echo "<td colspan='3'><input type='password' name='password' id='password' size='80' value='" . $config->fields["password"] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='default_display_name'>" . __('Default display name field') . "</label></td>";
        echo "<td colspan='3'><input type='text' name='default_display_name' id='default_display_name' size='80' value='" . $config->fields["default_display_name"] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='default_filename'>" . __('Default filename to sign') . "</label></td>";
        echo "<td colspan='3'><input type='text' name='default_filename' id='default_filename' size='80' value='" . $config->fields["default_filename"] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='default_request_comment'>" . __('Comment sent to the signer of a document..') . "</label></td>";
        echo "<td colspan='3'><input type='text' name='default_request_comment' id='default_request_comment' size='80' value='" . $config->fields["default_request_comment"] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='default_accept_comment'>" . __('Comment used when a document is signed.') . "</label></td>";
        echo "<td colspan='3'><input type='text' name='default_accept_comment' id='default_accept_comment' size='80' value='" . $config->fields["default_accept_comment"] . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='system_user_id'>" . __('User ID that will be used to attach the signed file to the ticket.') . "</label></td>";
        echo "<td colspan='3'><input type='text' name='system_user_id' id='system_user_id' size='80' value='" . $config->fields["system_user_id"] . "'></td>";
        echo "</tr>";

        $config->showFormButtons(['candel' => false]);
        return false;
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Config') {
            $config = new self();
            $config->showConfigForm();
        }
    }
}
