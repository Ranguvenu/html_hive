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
 * @package auth_courses
 * @copyright  2021 eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {


$settings = new admin_settingpage('local_courses', get_string('pluginname', 'local_courses'));

  $ADMIN->add('localplugins', $settings);
   
    $settings->add(new admin_setting_configtext('local_courses/disperzhost', get_string('disperzhost', 'local_courses'),get_string('disperzhost_desc', 'local_courses'), '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configtext('local_courses/partnerid', get_string('partnerid', 'local_courses'),get_string('partnerid_desc', 'local_courses'), '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configtext('local_courses/token', get_string('token', 'local_courses'),'', 0, PARAM_RAW_TRIMMED));

  

}
