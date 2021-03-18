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

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_libresign_install() {
   global $DB;

   if (!$DB->tableExists("glpi_plugin_libresign_files")) {
      $query = "CREATE TABLE glpi.glpi_plugin_libresign_files (
                  ticket_id int(11) NOT NULL,
                  request_date timestamp DEFAULT now() NOT NULL,
                  response_date timestamp DEFAULT NULL NULL,
                  user_id int(11) NOT NULL,
                  file_uuid varchar(36) NOT NULL
               )
               ENGINE=InnoDB
               DEFAULT CHARSET=utf8
               COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_libresign_configs')) {
      $query = "CREATE TABLE `glpi_plugin_libresign_configs`(
                  `id` int(11) NOT NULL,
                  `nextcloud_url`  VARCHAR(255) NULL,
                  `username`  VARCHAR(255) NULL,
                  `password`  VARCHAR(255) NULL,
                  `default_display_name`  VARCHAR(255) NULL,
                  `default_filename`  VARCHAR(255) NULL,
                  `default_request_comment`  TEXT NULL,
                  `default_accept_comment`  TEXT NULL,
                  `system_user_id` int(11) NOT NULL DEFAULT 0,
                  `date_mod` datetime default NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, 'Error in creating glpi_plugin_libresign_configs'.
                              "<br>".$DB->error());

      $DB->insertOrDie(
         'glpi_plugin_libresign_configs', [
            'id' => 1,
            'nextcloud_url' => '$DOMAIN/apps/libresign/api/0.1/webhook/register',
            'username' => null,
            'password' => null,
            'default_display_name' => 'firstname',
            'default_filename' => t_libresign('Accept'),
            'default_request_comment' => t_libresign('Validate GLPI Ticket'),
            'default_accept_comment' => t_libresign('Digitally signed on LibreSign'),
            'system_user_id' => 0,
            'date_mod' => null
         ],
         'Error during update glpi_plugin_pdf_configs<br>' . $DB->error()
      );
   }
   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_libresign_uninstall() {
   global $DB;

   if ($DB->tableExists("glpi_plugin_libresign_files")) {
      $query = "DROP TABLE `glpi_plugin_libresign_files`";
      $DB->query($query) or die("error deleting glpi_plugin_libresign_files");
   }
   if ($DB->tableExists('glpi_plugin_libresign_configs')) {
      $query = "DROP TABLE `glpi_plugin_libresign_configs`";
      $DB->query($query) or die("error deleting glpi_plugin_libresign_configs");
   }

   return true;
}
