<?php

include ("../../../inc/includes.php");

include_once(Plugin::getPhpDir('libresign')."/inc/apiclient.class.php");

$apiclient = new LibresignAPIClient;
$apiclient->initApi();