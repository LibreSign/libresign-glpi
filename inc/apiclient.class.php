<?php

use Glpi\Api\APIRest;

class LibresignAPIClient extends APIRest
{
    /** @var DBmysqlIterator */
    private $iterator;
    /** @var PluginLibresignConfig */
    private $config;

    public function initApi()
    {
        global $CFG_GLPI;
        $CFG_GLPI['enable_api'] = true;
        parent::initApi();

        include_once(Plugin::getPhpDir('libresign').'/inc/config.class.php');
        $this->config = new PluginLibresignConfig();
        $this->config->getFromDB(1);

        // Initialize entities
        Session::initEntityProfiles($this->config->fields['system_user_id']);
        // use first profile
        Session::changeProfile(key($_SESSION['glpiprofiles']));
        $_SESSION['glpiname'] = 'apirest-libresign';
    }

    public function saveSignedFile(string $uuid, string &$filename)
    {
        $iterator = $this->getSigners($uuid);
        if (!count($iterator)) {
            return;
        }
        $signer = $iterator->next();

        include_once(Plugin::getPhpDir('libresign').'/inc/config.class.php');

        $doc = new Document();
        $data = [
            'itemtype' => 'Ticket',
            'items_id' => $signer['ticket_id'],
            'tickets_id' => $signer['ticket_id'],
            'filename' => $filename,
            'mime' => 'application/pdf',
            'comment' => $config->fields['default_accept_comment']
        ];
        $doc->check(-1, CREATE, $data);

        // Prefix with random value
        $filename = uniqid('', true) . $filename;

        $uploadHandler = new GLPIUploadHandler([
            'param_name' => 'file',
            'accept_file_types' => '/\.(pdf)$/i',
            'upload_dir' => $uploadDir = GLPI_UPLOAD_DIR.'/libresign/files/',
            'print_response' => false
        ]);
        $response = $uploadHandler->get_response();
        $data['filepath'] = str_replace(GLPI_DOC_DIR, '', $uploadDir) . $response['file'][0]->name;
        $data['sha1sum'] = sha1_file($uploadDir . $response['file'][0]->name);
        $doc->add($data);

        $this->saveValidation();
    }

    private function saveValidation()
    {
        $validation = new TicketValidation();
        $signer = $this->iterator->current();
        do {
            $validation->update([
                'id' => $signer['validation_id'],
                'users_id_validate' => $signer['user_id'],
                'comment_validation' => $this->config->fields['default_accept_comment'],
                'validation_date' => date('Y-m-d H:i:s'),
                'status' => CommonITILValidation::ACCEPTED
            ]);
        } while($signer = $this->iterator->next());
    }

    private function getSigners($uuid)
    {
        global $DB;
        $this->iterator = $DB->request([
            'SELECT' => [
                'file.ticket_id',
                'file.user_id',
                TicketValidation::getTable() . '.id AS validation_id'
            ],
            'FROM' => 'glpi_plugin_libresign_files AS file',
            'INNER JOIN'   => [
                TicketValidation::getTable() => [
                    'ON' => [
                        'file' => 'user_id',
                        TicketValidation::getTable() => 'users_id_validate'
                    ]
                ]
            ],
            'WHERE' => [
                'file.file_uuid' => $uuid
            ]
        ]);
        if (!count($this->iterator)) {
            $this->returnError('Invalid UUID');
            return;
        }
        return $this->iterator;
    }
}