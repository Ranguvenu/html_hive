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
 * This file keeps track of upgrades to the ltiprovider plugin
 *
 * @package    local
 * @subpackage ilp
 * @copyright  2017 Anilkumar.cheguri <anil@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_ilp_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2016120701) {
        $table = new xmldb_table('local_ilp');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('summaryfile');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2016120701, 'local', 'ilp');
    }

    if ($oldversion < 2016120703.01) {
        $table = new xmldb_table('local_ilp');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('userid');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('courseid');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('targetdate');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('careertrack');
            $field->set_attributes(XMLDB_TYPE_CHAR, '225', null, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('sendmail');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('remainder');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            $field = new xmldb_field('department');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            
            
        }

        upgrade_plugin_savepoint(true, 2016120703.01, 'local', 'ilp');
    }

    if ($oldversion < 2016120703.02) {
        $table = new xmldb_table('local_ilp');
        $field = new xmldb_field('completiondate');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2016120703.02, 'local', 'ilp');
    }

    if ($oldversion < 2016120703.03) {
        $table = new xmldb_table('local_ilp');
        $field = new xmldb_field('comment');
        $field->set_attributes(XMLDB_TYPE_CHAR, '225', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2016120703.03, 'local', 'ilp');
    }
    return true;
}
