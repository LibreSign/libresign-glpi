<?php

use Glpi\Marketplace\Api\Plugins;

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

        $iterator = $DB->request([
            'SELECT' => ['file_uuid'],
            'FROM' => 'glpi_plugin_libresign_files',
            'WHERE' => [
                'ticket_id' => $ticket->input['users_id_validate']
            ],
            'LIMIT' => 1
        ]);
        include_once(Plugin::getPhpDir('libresign')."/inc/config.class.php");
        $config = new PluginLibresignConfig();
        $config->getFromDB(1);
        $displayName = $user->getField($config->fields["default_display_name"]);
        if (!$displayName) {
            $ticket->input = null;
            Session::addMessageAfterRedirect(
                sprintf(
                   __('The selected user (%s) has no valid %s. The request has not been created, without %s.'),
                   $user->getField('name'), $displayName, $displayName
                ),
                false,
                ERROR
             );
             return;
        }
        $options = [
            'name' => __('Accept'),
            'users' => [
                [
                    'display_name' => $displayName,
                    'email' => $email
                ]
            ]
        ];
        $client = new Plugins();
        if (count($iterator)) {
            // PATCH
            $row = $iterator->next();
            $options['uuid'] = $row['file_uuid'];
            $client->request($config->fields["nextcloud_url"], ['json' => $options], 'PATCH');
            return;
        }
        // POST
        $pdf = self::getPdf($ticket);
        $options['file'] = [
            'base64' => base64_encode('')
        ];
        $client->request($config->fields["nextcloud_url"], ['json' => $options], 'POST');
    }

    private static function getPdf(TicketValidation $ticket)
    {

    }
}