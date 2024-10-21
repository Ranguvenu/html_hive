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
 * @copyright   2023 Moodle India Information Solutions Pvt Ltd
 * @author      2023 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_prisemforce_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

     if ($oldversion < 2024032802) {
        $table = new xmldb_table('local_prisemforce_logs');

        if ($dbman->table_exists($table)) {
            $field1 = new xmldb_field('params');
            $field1->set_attributes(XMLDB_TYPE_TEXT, '', null, null, null, null);
        
            if (!$dbman->field_exists($table, $field1)) {
                $dbman->add_field($table, $field1);
            }

            $field2 = new xmldb_field('response');
            $field2->set_attributes(XMLDB_TYPE_TEXT, '', null, null, null, null);
        
            if (!$dbman->field_exists($table, $field2)) {
                $dbman->add_field($table, $field2);
            }
        }

        upgrade_plugin_savepoint(true, 2024032802, 'local', 'prisemforce');
    }
    if ($oldversion < 2024032805) {
        $table = new xmldb_table('local_prisemforce_logs');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, null, null,0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024032805, 'local', 'prisemforce');
    }
    if ($oldversion < 2024032806) {
        $table = new xmldb_table('local_prisemforce_logs');
        $field = new xmldb_field('typeapikey', XMLDB_TYPE_INTEGER, '1', null, null, null,0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024032806, 'local', 'prisemforce');
    }

    return true;
}
