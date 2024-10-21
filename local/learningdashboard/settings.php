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
 * @package    local_learningdashboard
 * @copyright  2024 Moodle India Information Solutions Pvt Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
global $DB;
require_once($CFG->dirroot.'/local/learningdashboard/lib.php');

$hassiteconfig = has_capability('moodle/site:config', context_system::instance());

try {
    if ($hassiteconfig) {
        $settings = new admin_settingpage('local_learningdashboard', get_string('pluginname', 'local_learningdashboard'));
        $ADMIN->add('localplugins', $settings);

        $categories = course_categories_list();
        $settings->add(new admin_setting_configmultiselect(
            'local_learningdashboard_technical_categories',
            get_string('technical_categories', 'local_learningdashboard'),
            get_string('technical_categories_desc', 'local_learningdashboard'),
            [],
            $categories
        ));

        $settings->add(new admin_setting_configmultiselect(
            'local_learningdashboard_leadership_categories',
            get_string('leadership_categories', 'local_learningdashboard'),
            get_string('leadership_categories_desc', 'local_learningdashboard'),
            [],
            $categories
        ));

        $settings->add(new admin_setting_configtext(
            'local_learningdashboard_label_one',
            get_string('label_one', 'local_learningdashboard'),
            get_string('label_one_desc', 'local_learningdashboard'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'local_learningdashboard_url_one',
            get_string('url_one', 'local_learningdashboard'),
            get_string('url_one_desc', 'local_learningdashboard'),
            '',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
            'local_learningdashboard_label_two',
            get_string('label_two', 'local_learningdashboard'),
            get_string('label_two_desc', 'local_learningdashboard'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'local_learningdashboard_url_two',
            get_string('url_two', 'local_learningdashboard'),
            get_string('url_two_desc', 'local_learningdashboard'),
            '',
            PARAM_URL
        ));

    } else {
        throw new Exception(get_string('config_permissions', 'local_learningdashboard'));
    }

} catch (Exception $e) {
    debugging('Error in local/learningdashboard/settings.php: ' . $e->getMessage(), DEBUG_DEVELOPER);
}
