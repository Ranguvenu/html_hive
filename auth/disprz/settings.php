<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin settings and defaults.
 *
 * @package auth_disprz
 * @copyright  2019 info@eabyas.in
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_disprz/pluginname', '',
        new lang_string('disprz_plugin', 'auth_disprz')));

    $settings->add(new admin_setting_configtext('auth_disprz/host',
        new lang_string('disprz_host', 'auth_disprz'),''
        , 0,PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configtext('auth_disprz/key',
        new lang_string('disprz_key', 'auth_disprz'),'', 0,PARAM_RAW_TRIMMED));

   $settings->add(new admin_setting_configtext('auth_disprz/token',
        new lang_string('disprz_token', 'auth_disprz'),'', 0,PARAM_RAW_TRIMMED));
		
   $settings->add(new admin_setting_configtext('auth_disprz/apiurl',
        new lang_string('apiposturl', 'auth_disprz'),'', 0,PARAM_RAW_TRIMMED));
		
   $settings->add(new admin_setting_configtext('auth_disprz/returnurl',
        new lang_string('returnurl', 'auth_disprz'),'', 0,PARAM_RAW_TRIMMED));

  
}
