<?php

use Glpi\Marketplace\Api\Plugins;
use GuzzleHttp\Exception\RequestException;

class PluginLibresignHttpclient extends Plugins
{
    public function request($method, $endpoint, $options)
    {
        return $this->httpClient->request($method, $endpoint, $options);
    }
}