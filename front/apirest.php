<?php

include ("../../../inc/includes.php");

include_once(Plugin::getPhpDir('libresign')."/inc/apiclient.class.php");

$apiclient = new LibresignAPIClient([
    'upload_url' => 1
]);
$apiclient->initApi();

$iterator = $DB->request([
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
        'file.file_uuid' => $_POST['uuid']
    ]
]);
if (!count($iterator)) {
    $apiclient->returnError('Invalid UUID');
    return;
}

$signer = $iterator->next();

$config = new PluginLibresignConfig();
$config->getFromDB(1);

// Initialize entities
Session::initEntityProfiles($config->fields['system_user_id']);
// use first profile
Session::changeProfile(key($_SESSION['glpiprofiles']));
$_SESSION['glpiname'] = 'apirest-libresign';

include_once(Plugin::getPhpDir('libresign').'/inc/config.class.php');

$doc = new Document();
$data = [
    'itemtype' => 'Ticket',
    'items_id' => $signer['ticket_id'],
    'tickets_id' => $signer['ticket_id'],
    'filename' => $_FILES['file']['name'],
    'mime' => 'application/pdf',
    'comment' => $config->fields['default_comment']
];
$doc->check(-1, CREATE, $data);

// Prefix with random value
$_FILES['file']['name'] = uniqid('', true) . $_FILES['file']['name'];

$uploadHandler = new GLPIUploadHandler([
    'param_name' => 'file',
    'accept_file_types' => '/\.(pdf)$/i',
    'upload_dir' => $uploadDir = GLPI_UPLOAD_DIR.'/libresign/files/',
    'print_response' => false
]);
$response = $uploadHandler->get_response();
$data['filepath'] = str_replace(GLPI_DOC_DIR, '', $uploadDir) . $response['file'][0]->name;
$data['sha1sum'] = sha1_file($uploadDir . $response['file'][0]->name);
$newID = $doc->add($data);

$validation = new TicketValidation();
do {
    $validation->update([
        'id' => $signer['validation_id'],
        'users_id_validate' => $signer['user_id'],
        'comment_validation' => $config->fields['default_comment'],
        'validation_date' => date('Y-m-d H:i:s'),
        'status' => CommonITILValidation::ACCEPTED
    ]);
} while($signer = $iterator->next());