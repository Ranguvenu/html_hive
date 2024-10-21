<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This courselister is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This courselister is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this courselister.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course list block settings
 * @author eabyas  <info@eabyas.in>
 * @package    Bizlms
 * @subpackage block_courselister
 */

use block_courselister\plugin;

defined('MOODLE_INTERNAL') || die;

global $DB;

if (!isset($hassiteconfig)) {
    $hassiteconfig = has_capability('moodle/site:config', context_system::instance());
}

if ($hassiteconfig) {

    /** @var admin_settingpage $settings */
    $settings->add(
        new admin_setting_heading(
            'block_courselister/title',
            new lang_string('global_setting_title', plugin::COMPONENT),
            new lang_string('global_setting_info', plugin::COMPONENT)
        )
    );

    $choices = [ 0 => new lang_string('none', 'core')];
    if (!during_initial_install()) {
        // @codingStandardsIgnoreStart
        try {
            $choices += $DB->get_records_menu('course_info_field', null, '', 'id, fullname');
        } catch (dml_exception $e) {
            // We dont do anything here on purporse.
        }
        // @codingStandardsIgnoreEnd
    }

    $settings->add(
        new admin_setting_configselect(
            'block_courselister/vendorfield',
            new lang_string('vendorfield', plugin::COMPONENT),
            new lang_string('vendorfield_desc', plugin::COMPONENT),
            0,
            $choices
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'block_courselister/durationfield',
            new lang_string('durationfield', plugin::COMPONENT),
            new lang_string('durationfield_desc', plugin::COMPONENT),
            0,
            $choices
        )
    );

    $settings->add(
        new admin_setting_configselect(
            'block_courselister/modulesfield',
            new lang_string('modulesfield', plugin::COMPONENT),
            new lang_string('modulesfield_desc', plugin::COMPONENT),
            0,
            $choices
        )
    );

}
