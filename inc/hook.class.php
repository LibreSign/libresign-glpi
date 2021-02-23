<?php

class PluginLibresignHook extends CommonDBTM
{
    public static function preItemAdd(TicketValidation $ticket)
    {
        global $DB;
        try {
            $user = new User();
            $user->getFromDB($ticket->input['users_id_validate']);
            $email = self::getUserEmail($user);

            include_once(Plugin::getPhpDir('libresign').'/inc/config.class.php');
            $config = new PluginLibresignConfig();
            $config->getFromDB(1);
            $displayName = self::getDisplayName();
            $options = [
                'name' => __($config->fields['default_filename']?:'Accept'),
                'users' => [
                    [
                        'display_name' => $displayName,
                        'email' => $email
                    ]
                ],
                'callback' => Plugin::getWebDir('libresign', true, true) . '/front/apirest.php'
            ];
            include_once(Plugin::getPhpDir('libresign').'/inc/httpclient.class.php');
            $iterator = $DB->request([
                'SELECT' => ['file_uuid', 'user_id', 'response_date'],
                'FROM' => 'glpi_plugin_libresign_files',
                'WHERE' => [
                    'ticket_id' => $ticket->input['tickets_id']
                ]
            ]);
            if (count($iterator)) {
                while ($iterator->next()) {
                    $row = $iterator->current();
                    if ($row['response_date']) {
                        throw new Exception(__(
                            'File already signed by %s, impossible to add another subscriber. '.
                            'Delete all signers, the signed file and request new signatures.'
                        ));
                    }
                    if ($row['user_id'] == $ticket->input['users_id_validate']) {
                        throw new Exception(sprintf(
                            __('Signature already requested for %s'),
                            $displayName
                        ));
                    }
                }
                $options['uuid'] = $row['file_uuid'];
                $method = 'PATCH';
            } else {
                $method = 'POST';
                $options['file'] = [
                    'base64' => base64_encode(self::getPdf($ticket))
                ];
            }
            $uuid = self::requestSign($method, $config, $options);
            self::insertRelation($uuid, $ticket);
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

    private static function getUserEmail(User $user)
    {
        $email = $user->getDefaultEmail();
        if (!$email) {
            throw new Exception(sprintf(
                __('The selected user (%s) has no valid email address. The request has not been created, without email confirmation.'),
                $user->getField('name')
            ));
        }
        return $email;
    }

    private static function getDisplayName(PluginLibresignConfig $config)
    {
        $displayName = $user->getField($config->fields['default_display_name']);
        if (!$displayName) {
            throw new Exception(sprintf(
                __('The selected user (%s) has no valid %s. The request has not been created, without %s.'),
                $user->getField('name'), $config->fields['default_display_name'], $config->fields['default_display_name']
            ));
        }
        return $displayName
    }

    private static function requestSign(string $method, PluginLibresignConfig $config, array $options)
    {
        $client = new PluginLibresignHttpclient();
        $response = $client->request($method, $config->fields['nextcloud_url'], [
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
        return $json->data->uuid;
    }

    private static function insertRelation(string $uuid, TicketValidation $ticket)
    {
        global $DB;
        $DB->insert('glpi_plugin_libresign_files', [
            'file_uuid' => $uuid,
            'user_id' => $ticket->input['users_id_validate'],
            'ticket_id' => $ticket->input['tickets_id'],
            'request_date' => date('Y-m-d H:i:s')
        ]);
    }

    private static function getPdf(TicketValidation $ticketValidation)
    {
        global $PLUGIN_HOOKS;
        $ticket = new Ticket();
        $pdf = new $PLUGIN_HOOKS['plugin_pdf']['Ticket']($ticket);
        return $pdf->generatePDF([$ticketValidation->input['tickets_id']], ['Ticket$main'], 0, false);
    }
}