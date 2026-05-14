<?php

include ("../../../inc/includes.php");

include_once(Plugin::getPhpDir('libresign')."/inc/apiclient.class.php");
include_once(Plugin::getPhpDir('libresign')."/inc/config.class.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new BadMethodCallException('Only POST requests are allowed.');
}
if (!isset($_POST['uuid']) || !isset($_FILES['file']['name'])) {
    throw new InvalidArgumentException('Missing callback payload.');
}

$config = new PluginLibresignConfig();
if (!$config->getFromDB(1)) {
    throw new RuntimeException('Plugin configuration not found.');
}

$providedToken = $_GET['token'] ?? '';
$expectedToken = $config->fields['callback_token'] ?? '';
if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    throw new RuntimeException('Invalid callback token.');
}

$apiclient = new LibresignAPIClient([
    'upload_url' => 1
]);
$apiclient->initApi();

$apiclient->saveSignedFile($_POST['uuid'], $_FILES['file']['name']);
