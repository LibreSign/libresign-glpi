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
        Ticket::getTable() . '.users_id_lastupdater AS user_id',
        TicketValidation::getTable() . '.id AS validation_id'
    ],
    'FROM' => 'glpi_plugin_libresign_files AS file',
    'INNER JOIN'   => [
        Ticket::getTable() => [
            'ON' => [
                'file'   => 'ticket_id',
                Ticket::getTable() => 'id'
            ]
        ],
        TicketValidation::getTable() => [
            'ON' => [
                Ticket::getTable() => 'id',
                TicketValidation::getTable() => 'tickets_id',
                [
                    'AND' => [
                        'FKEY' => [
                            'file' => 'user_id',
                            TicketValidation::getTable() => 'users_id_validate'
                        ]
                    ]
                ]
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

Session::initEntityProfiles($signer['user_id']);
// use first profile
Session::changeProfile(key($_SESSION['glpiprofiles']));

include_once(Plugin::getPhpDir('libresign').'/inc/config.class.php');
$config = new PluginLibresignConfig();
$config->getFromDB(1);

$doc = new Document();
$data = [
    'itemtype' => 'Ticket',
    'items_id' => $signer['ticket_id'],
    'tickets_id' => $signer['ticket_id'],
    // 'users_id' => $signer['user_id'], // Necessary?
    // 'add' => 'Add a New File', // Necessary?
    'filename' => $_FILES['file']['name'],
    'mime' => 'application/pdf',
    'comment' => $config->fields['default_comment']
];
// Force user id
// $_SESSION["glpiID"] = $signer['user_id']; // Necessary?
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
        'status' => CommonITILValidation::ACCEPTED
    ]);
    $aqui = 1;
} while($signer = $iterator->next());