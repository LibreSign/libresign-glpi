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

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_libresign() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['libresign'] = true;

   Plugin::registerClass('PluginLibresignConfig', ['addtabon' => 'Config']);
   $PLUGIN_HOOKS['config_page']['libresign'] = 'front/config.form.php';

   include_once(Plugin::getPhpDir('libresign')."/inc/config.class.php");

   $plugin = new Plugin();
   if ($plugin->isActivated("datainjection")) {
      $PLUGIN_HOOKS['menu_entry']['libresign'] = 'front/preference.form.php';
   } elseif ($plugin->isActivated("geststock")) {
      $PLUGIN_HOOKS['menu_entry']['libresign'] = 'front/preference.form.php';
   }
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
      'author'         => '<a href="http://www.lyseonech.com">LyseonTech</a>',
      'license'        => 'GPLv3+',
      'homepage'       => 'http://www.lyseonech.com',
      'requirements'   => [
         'glpi' => [
            'min' => '9.2',
            'max' => '9.6'
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

   //Version check is not done by core in GLPI < 9.2 but has to be delegated to core in GLPI >= 9.2.
   $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
   if (version_compare($version, '9.2', '<')) {
      echo "This plugin requires GLPI >= 9.2";
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
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      echo __('Installed / not configured', 'libresign');
   }
   return false;
}
