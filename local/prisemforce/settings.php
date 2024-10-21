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
 * @package     local_prisemforce
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    global $CFG;
    $settings = new admin_settingpage('local_prisemforce', new lang_string('prisemforcesettings', 'local_prisemforce'));
    $ADMIN->add('localplugins', $settings);
    
    // $settings->add(new admin_setting_configtext('local_prisemforce/client_id',
    //     new lang_string('clientid', 'local_prisemforce'),
    //     '', '', PARAM_TEXT));

    // $settings->add(new admin_setting_configtext('local_prisemforce/client_secret',
    //     new lang_string('clientsecret', 'local_prisemforce'),
    //     '', '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_prisemforce/userxapikey',
        new lang_string('userxapikey', 'local_prisemforce'),
        '', '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_prisemforce/masterxapikey',
        new lang_string('masterxapikey', 'local_prisemforce'),
        '', '', PARAM_TEXT));    
    
    $settings->add(new admin_setting_configtext('local_prisemforce/accesstoken',
        new lang_string('accesstoken', 'local_prisemforce'),
        new lang_string('accesstoken_help', 'local_prisemforce'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_prisemforce/prisemurl',
        new lang_string('url', 'local_prisemforce'),
        new lang_string('url_help', 'local_prisemforce'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_prisemforce/authurl',
        new lang_string('authurl', 'local_prisemforce'), '', '', PARAM_TEXT));     
}
