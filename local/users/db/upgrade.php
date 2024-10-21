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
defined('MOODLE_INTERNAL') || die();
function xmldb_local_users_upgrade($oldversion) {
	global $DB, $CFG;
	$dbman = $DB->get_manager();
	if ($oldversion < 2016080911.05) {
		$table = new xmldb_table('user');
		$field1 = new xmldb_field('open_position', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		if (!$dbman->field_exists($table, $field1)) {
			$dbman->add_field($table, $field1);
		}
		$field2 = new xmldb_field('open_domainid', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field2)) {
			$dbman->add_field($table, $field2);
		}
		upgrade_plugin_savepoint(true, 2016080911.05, 'local', 'users');
	}

	if ($oldversion < 20200326001.01) {
		$table = new xmldb_table('user');
		$field1 = new xmldb_field('open_designation', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field1)) {
			$dbman->add_field($table, $field1);
		}
		upgrade_plugin_savepoint(true, 20200326001.01, 'local', 'users');
	}

	if ($oldversion < 20200326001.02) {
		$table = new xmldb_table('user');
		$field1 = new xmldb_field('open_location', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field1)) {
			$dbman->add_field($table, $field1);
		}

		$field = new xmldb_field('open_group', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_ouname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_careertrack', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_dob', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_doj', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_salutation', XMLDB_TYPE_CHAR, '50', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_functionalreportingto', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_gender', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_grade', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_costcenter', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_subdepart', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_calendar', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 20200326001.02, 'local', 'users');
	}

	if ($oldversion < 20200326001.09) {
        $table = new xmldb_table('user');     
        $field = new xmldb_field('open_hrmsrole',XMLDB_TYPE_CHAR, '255', null, null, null,null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 20200326001.09, 'local', 'users');
    }
    if ($oldversion < 20200326001.10) {
		$table = new xmldb_table('user');
		$field1 = new xmldb_field('open_country', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field1)) {
			$dbman->add_field($table, $field1);
		}

		$field = new xmldb_field('open_address', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		upgrade_plugin_savepoint(true, 20200326001.10, 'local', 'users');
	}

	return true;
}