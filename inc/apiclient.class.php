<?php

use Glpi\Api\APIRest;

class LibresignAPIClient extends APIRest
{
    public function __construct()
    {
        global $CFG_GLPI;
        $CFG_GLPI['enable_api'] = true;
    }
}