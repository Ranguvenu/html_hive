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
 * Admin settings and defaults
 *
 * @package local_users
 * @copyright  Eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_users', get_string('pluginname', 'local_users'));
    $ADMIN->add('localplugins', $settings);
    $name = new lang_string('usersyncworkdayapi', 'local_users');
    $description = new lang_string('usersyncworkdayapi_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/usersyncworkdayapi', $name, $description,'',PARAM_RAW));

    $name = new lang_string('usersyncaccesstoken', 'local_users');
    $description = new lang_string('usersyncaccesstoken_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/usersyncaccesstoken', $name, $description,'',PARAM_RAW));
    
    $name = new lang_string('refresh_token', 'local_users');
    $description = new lang_string('refresh_token_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/refresh_token', $name, $description,'',PARAM_RAW));

    $name = new lang_string('grant_type', 'local_users');
    $description = new lang_string('grant_type_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/grant_type', $name, $description,'',PARAM_RAW));

    $name = new lang_string('classroomapi', 'local_users');
    $description = new lang_string('classroomapi_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/classroomapi', $name, $description,'',PARAM_RAW));

    $name = new lang_string('courseapi', 'local_users');
    $description = new lang_string('courseapi_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/courseapi', $name, $description,'',PARAM_RAW));

    $name = new lang_string('learningpathapi', 'local_users');
    $description = new lang_string('learningpathapi_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/learningpathapi', $name, $description,'',PARAM_RAW));

    $name = new lang_string('learningpathcoursesapi', 'local_users');
    $description = new lang_string('learningpathcoursesapi_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/learningpathcoursesapi', $name, $description,'',PARAM_RAW));

    $name = new lang_string('coursestatusapi', 'local_users');
    $description = new lang_string('coursestatusapi_info', 'local_users');
    $settings->add(new admin_setting_configtext('local_users/coursestatusapi', $name, $description,'',PARAM_RAW));

}
