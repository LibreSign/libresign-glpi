<?php

use Glpi\Marketplace\Api\Plugins;

class PluginLibresignHttpclient extends Plugins
{
    public function request($method, $endpoint, $options)
    {
        return $this->httpClient->request($method, $endpoint, $options);
    }
}