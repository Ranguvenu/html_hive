<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_fmsapi_install(){
	global $CFG,$DB,$USER;
	$dbman = $DB->get_manager(); 
	
    $table = new xmldb_table('local_fmsapi_course_search');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursename', XMLDB_TYPE_TEXT, null, null, null, null, '0');
        $table->add_field('employee_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('employee_name', XMLDB_TYPE_CHAR, '255', null, null, null, '0');
        $table->add_field('employee_email', XMLDB_TYPE_CHAR, '255', null, null, null, '0');
        $table->add_field('course_description', XMLDB_TYPE_TEXT, null, null, null, null, '0');
        $table->add_field('message', XMLDB_TYPE_CHAR, '100', null, null, null, '0');
        $table->add_field('requested_by', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('requested_date', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $dbman->create_table($table);
    }

    $table = new xmldb_table('local_fmsapi_course_search');
    $field = new xmldb_field('skillkeyword', XMLDB_TYPE_TEXT, null, null, null, null);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    $field = new xmldb_field('skills', XMLDB_TYPE_TEXT, null, null, null, null);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $table1 = new xmldb_table('local_fmsapi_logs');
    if (!$dbman->table_exists($table1)) {
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('eventname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_field('transactionid', XMLDB_TYPE_CHAR, '150', null, XMLDB_NOTNULL, null);
        $table1->add_field('elementid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table1->add_field('params', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('status', XMLDB_TYPE_INTEGER, '1', null, null, null,0);
        $table1->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);           
        $table1->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);
    }
    
}
