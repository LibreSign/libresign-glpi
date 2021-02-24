<?php

include ("../../../inc/includes.php");

include_once(Plugin::getPhpDir('libresign')."/inc/apiclient.class.php");

$apiclient = new LibresignAPIClient([
    'upload_url' => 1
]);
$apiclient->initApi();

$apiclient->saveSignedFile($_POST['uuid'], $_FILES['file']['name']);