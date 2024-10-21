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
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_fmsapi
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    global $CFG;
    $settings = new admin_settingpage('local_fmsapi', new lang_string('fmsapisettings', 'local_fmsapi'));
    $ADMIN->add('localplugins', $settings);   

    $settings->add(new admin_setting_configtext('local_fmsapi/fmsapiurl',
        new lang_string('url', 'local_fmsapi'),
        new lang_string('url_help', 'local_fmsapi'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_fmsapi/fmsauthurl',
        new lang_string('authurl', 'local_fmsapi'),
        new lang_string('authurl_help', 'local_fmsapi'), '', PARAM_TEXT));    
    
    $settings->add(new admin_setting_configtext('local_fmsapi/client_id',
        new lang_string('clientid', 'local_fmsapi'),
        '', '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_fmsapi/client_secret',
        new lang_string('clientsecret', 'local_fmsapi'),
        '', '', PARAM_TEXT));
     
    $settings->add(new admin_setting_configtext('local_fmsapi/code',
        new lang_string('code', 'local_fmsapi'),
        '', '', PARAM_TEXT));   
}
