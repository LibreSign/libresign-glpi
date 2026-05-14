<?php
/*
 -------------------------------------------------------------------------
 LibreSign plugin for GLPI
 Copyright (C) 2021 by the LibreSign Development Team.

 https://github.com/pluginsGLPI/libresign
 -------------------------------------------------------------------------

 LICENSE

 This file is part of LibreSign.

 LibreSign is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 LibreSign is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with LibreSign. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('PLUGIN_LIBRESIGN_VERSION', '0.0.1');

use Glpi\Http\Firewall;
use Glpi\Http\SessionManager;

function plugin_libresign_boot() {
   SessionManager::registerPluginStatelessPath('libresign', '#^/front/apirest\.php$#');
}

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_libresign() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['libresign'] = true;
   Firewall::addPluginStrategyForLegacyScripts('libresign', '#^/front/apirest\.php$#', Firewall::STRATEGY_NO_CHECK);

   Plugin::registerClass('PluginLibresignConfig', ['addtabon' => 'Config']);
   $PLUGIN_HOOKS['config_page']['libresign'] = 'front/config.form.php';

   include_once(Plugin::getPhpDir('libresign')."/inc/config.class.php");

   $PLUGIN_HOOKS['pre_item_add']['libresign'] = [
      'TicketValidation' => [
         'PluginLibresignHook',
         'preItemAdd'
      ]
   ];

   $PLUGIN_HOOKS['item_update']['libresign'] = [
      'TicketValidation' => [
         'PluginLibresignHook',
         'itemUpdate'
      ]
   ];

   $PLUGIN_HOOKS['pre_item_purge']['libresign'] = [
      'TicketValidation' => [
         'PluginLibresignHook',
         'preItemPurge'
      ]
   ];
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_libresign() {
   return [
      'name'           => 'LibreSign',
      'version'        => PLUGIN_LIBRESIGN_VERSION,
      'author'         => '<a href="https://librecode.coop">LibreCode</a>',
      'license'        => 'GPLv3+',
      'homepage'       => 'https://librecode.coop',
      'requirements'   => [
         'glpi' => [
            'min' => '11.0.7',
            'max' => '11.1.0'
         ],
         'php' => [
            'min' => '8.2'
         ]
      ]
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_libresign_check_prerequisites() {
   $plugin = new Plugin();

   //Version check is not done by core in GLPI < 9.2 but has to be delegated to core in GLPI >= 9.2.
   $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
   if (version_compare($version, '11.0.7', '<') || version_compare($version, '11.1.0', '>=')) {
      echo "This plugin requires GLPI >= 11.0.7 and < 11.1.0";
      return false;
   }
   if (version_compare(PHP_VERSION, '8.2', '<')) {
      echo "This plugin requires PHP >= 8.2";
      return false;
   }
   if (!$plugin->isInstalled('pdf') || !$plugin->isActivated('pdf')) {
      echo "This plugin requires the GLPI PDF plugin to be installed and enabled";
      return false;
   }
   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_libresign_check_config($verbose = false) {
   return true;
}

function t_libresign($str) {
   return __($str, 'libresign');
}
