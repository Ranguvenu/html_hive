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
 * Auto enrol plugin installation script
 *
 * @package    enrol_auto
 * @author     Eugene Venter <eugene@catalyst.net.nz>
 * @copyright  2013 onwards Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_enrol_auto_install() {
	global $CFG, $DB;
	$dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
	$table = new xmldb_table('enrol');
	if ($dbman->table_exists($table)) {

		$field = new xmldb_field('department',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('open_group',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('open_hrmsrole',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('open_designation',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		$field = new xmldb_field('open_location',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		$field = new xmldb_field('open_ouname',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
	}
}
