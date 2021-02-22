<?php

use Glpi\Marketplace\Api\Plugins;
use GuzzleHttp\Exception\RequestException;

class PluginLibresignHook extends CommonDBTM
{
    public static function preItemAdd(TicketValidation $ticket)
    {
        global $DB;
        $user = new User();
        $user->getFromDB($ticket->input['users_id_validate']);
        $email = $user->getDefaultEmail();
        if (!$email) {
            $ticket->input = null;
            Session::addMessageAfterRedirect(
                sprintf(
                   __('The selected user (%s) has no valid email address. The request has not been created, without email confirmation.'),
                   $user->getField('name')
                ),
                false,
                ERROR
             );
             return;
        }

        include_once(Plugin::getPhpDir('libresign').'/inc/config.class.php');
        $config = new PluginLibresignConfig();
        $config->getFromDB(1);
        $displayName = $user->getField($config->fields['default_display_name']);
        if (!$displayName) {
            $ticket->input = null;
            Session::addMessageAfterRedirect(
                sprintf(
                   __('The selected user (%s) has no valid %s. The request has not been created, without %s.'),
                   $user->getField('name'), $config->fields['default_display_name'], $config->fields['default_display_name']
                ),
                false,
                ERROR
             );
             return;
        }
        $options = [
            'name' => __($config->fields['default_filename']?:'Accept'),
            'users' => [
                [
                    'display_name' => $displayName,
                    'email' => $email
                ]
            ]
        ];
        include_once(Plugin::getPhpDir('libresign').'/inc/httpclient.class.php');
        $client = new PluginLibresignHttpclient();
        $iterator = $DB->request([
            'SELECT' => ['file_uuid'],
            'FROM' => 'glpi_plugin_libresign_files',
            'WHERE' => [
                'ticket_id' => $ticket->input['users_id_validate']
            ]
        ]);
        if (count($iterator)) {
            // PATCH
            $row = $iterator->next();
            $options['uuid'] = $row['file_uuid'];
            $client->request($config->fields['nextcloud_url'], [
                'json' => $options,
                'auth' => [$config->fields['username'], $config->fields['password']]
            ], 'PATCH');
            return;
        }
        // POST
        $options['file'] = [
            'base64' => base64_encode(self::getPdf($ticket))
        ];
        try {
            $response = $client->request('POST',$config->fields['nextcloud_url'], [
                'json' => $options,
                'auth' => [$config->fields['username'], $config->fields['password']]
            ]);
            $json = $response->getBody()->getContents();
            if (!$json) {
                throw new \Exception('Invalid response from LibreSign');
            }
            $json = json_decode($json);
            if (!$json) {
                throw new \Exception('Invalid JSON from LibreSign');
            }
            $DB->insert('glpi_plugin_libresign_files', [
                'file_uuid' => $json->data->uuid,
                'user_id' => $ticket->input['users_id_validate'],
                'ticket_id' => $ticket->input['tickets_id'],
                'request_date' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $ticket->input = null;
            Session::addMessageAfterRedirect(
                sprintf(
                   __('Failure on send file to sign in LibreSign. Error: %s.'),
                   $e->getMessage()
                ),
                false,
                ERROR
             );
             return;
        }
    }

    private static function getPdf(TicketValidation $ticketValidation)
    {
        global $PLUGIN_HOOKS;
        $ticket = new Ticket();
        $pdf = new $PLUGIN_HOOKS['plugin_pdf']['Ticket']($ticket);
        return $pdf->generatePDF([$ticketValidation->input['tickets_id']], ['Ticket$main'], 0, false);
    }
}