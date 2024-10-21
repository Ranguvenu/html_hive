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

function xmldb_local_courses_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2017111300) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('approvalreqd', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111300, 'local', 'courses');
    }
    if ($oldversion < 2017111301) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('selfenrol', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111301, 'local', 'courses');
    }
    if ($oldversion < 2017111302) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_level', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017111302, 'local', 'courses');
    }
    if($oldversion < 2019091300.01){
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_subdepartment', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.01, 'local', 'courses');   
    }
    
    if ($oldversion < 2019091300.07) {
        $table = new xmldb_table('local_moduleconfig');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('costcenters', XMLDB_TYPE_INTEGER, 10, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $dbman->create_table($table);

            upgrade_plugin_savepoint(true, 2019091300.07, 'local', 'courses');
        }
    }

    if($oldversion < 2019091300.08){
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_ilpcourse', XMLDB_TYPE_INTEGER, '5', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('open_grade', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019091300.08, 'local', 'courses');   
    }
  if ($oldversion < 2019091300.10) {
        $table = new xmldb_table('local_course_facilitators'); 
        $table1 = new xmldb_table('course');
        $field = new xmldb_field('open_facilitatorcredits', XMLDB_TYPE_FLOAT, '10,5', null, null, null, null);
        if (!$dbman->field_exists($table1, $field)) {
            $dbman->add_field($table1, $field);
        }
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('credits', XMLDB_TYPE_FLOAT, '10,5', null, null, null, null);
            $table->add_field('contenttype', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('classroomid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $dbman->create_table($table);
            upgrade_plugin_savepoint(true, 2019091300.10, 'local', 'courses');
        }
    }
     if($oldversion < 2019091300.11){
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_careertrack', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.11, 'local', 'courses');   
    }

   if ($oldversion < 2019091300.14) {
        $table = new xmldb_table('local_featured_courses'); 
      
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('featured_course_ids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('open_costcenterid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $dbman->create_table($table);
            upgrade_plugin_savepoint(true, 2019091300.14, 'local', 'courses');
        }
    }

    if ($oldversion < 2019091300.14) {
        $table = new xmldb_table('user_enrolments_log'); 
      
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('enrolstatus', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('enrolid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('unenrol_reason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
            upgrade_plugin_savepoint(true, 2019091300.14, 'local', 'courses');
        }
    }


  /*   if ($oldversion < 2019091300.15) {
        $table = new xmldb_table('local_course_types'); 
      
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course_type', XMLDB_TYPE_CHAR,  '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
             $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);

        }
        upgrade_plugin_savepoint(true, 2019091300.15, 'local', 'courses');
    } */

    if ($oldversion < 2019091300.15) {
        $table = new xmldb_table('local_course_providers'); 
      
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course_provider', XMLDB_TYPE_CHAR,  '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
             $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
            upgrade_plugin_savepoint(true, 2019091300.21, 'local', 'courses');
    
        }
    }

    if ($oldversion < 2019091300.22) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_coursetype', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field1 = new xmldb_field('open_courseprovider', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2019091300.22, 'local', 'courses');
    }
    if ($oldversion < 2019091300.26) {
        $table = new xmldb_table('course');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('open_prerequisites');
            $field->set_attributes(XMLDB_TYPE_CHAR, '50', null, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2019091300.26, 'local', 'courses');
    }

 

    if ($oldversion < 2019091300.27) {
        $time = time();
        $table = new xmldb_table('local_course_types');

        if($dbman->table_exists($table)){
            $dbman->drop_table( $table );
        }
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course_type', XMLDB_TYPE_CHAR,  '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
        }

        if ($dbman->table_exists($table)) {
            $course_type_data = array(

                array('course_type' => 'MOOC', 'active' => '1','usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL),
                array('course_type' => 'ILT', 'active' => '1','usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL),
                array('course_type' => 'E-Learning', 'active' => '1','usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL),
                array('course_type' => 'Learning Path', 'active' => '1','usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL),
               
            );
            foreach ($course_type_data as $course_type) {
                unset($course_type['timecreated']);
                if (!$DB->record_exists('local_course_types',  $course_type)) {
                    $course_type['timecreated'] = $time;
                    $DB->insert_record('local_course_types', $course_type);
                }
            }
        }
        upgrade_plugin_savepoint(true, 2019091300.27, 'local', 'courses');    
    }

    if ($oldversion < 2019091300.29) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_url', XMLDB_TYPE_TEXT, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.29, 'local', 'courses');
    }


    if ($oldversion < 2019091300.31) {
        $table = new xmldb_table('local_course_types');
        $field = new xmldb_field('shortname', XMLDB_TYPE_TEXT, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.31, 'local', 'courses');
    }

    if ($oldversion < 2019091300.32) {
        $course_type_data = array('MOOC','ILT','E-Learning','Learning Path');

        foreach ($course_type_data as $course_type) { 
            if ($DB->record_exists('local_course_types',  array('course_type' => $course_type))) {
                $id = $DB->get_field('local_course_types','id', array('course_type' => $course_type));
                $record = new stdClass;
                $record->id = $id ;
                $record->shortname = trim($course_type);
                $DB->update_record('local_course_types', $record);
            }
        }
        upgrade_plugin_savepoint(true, 2019091300.32, 'local', 'courses');
    }

    if ($oldversion < 2019091300.33) {
        $time = time();
        $initcontent = array('name' => 'Course', 'shortname' => 'course', 'parent_module' => '0', 'usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL, 'pluginname' => 'courses');
        $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'course'));
        if (!$parentid) {
            $parentid = $DB->insert_record('local_notification_type', $initcontent);
        }

        $notification_type_data = array(array('name' => 'Course Completion Reminder','shortname' => 'course_completion_reminder','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'courses'));
        foreach($notification_type_data as $notification_type){
            unset($notification_type['timecreated']);
            if(!$DB->record_exists('local_notification_type',  $notification_type)){
                $notification_type['timecreated'] = $time;
                $DB->insert_record('local_notification_type', $notification_type);
            }
        }
      
        upgrade_plugin_savepoint(true, 2019091300.33, 'local', 'courses');
    }

    if ($oldversion < 2019091300.35) {
        $table = new xmldb_table('local_frequencylogs');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('notification_infoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('from_userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('to_userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            
            $table->add_field('from_emailid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('to_emailid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('moduletype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('moduleid', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('teammemberid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('reminderdays', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('enable_cc', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('emailbody', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, '0');
            $table->add_field('adminbody', XMLDB_TYPE_TEXT, null, null, null, null, '0');
            $table->add_field('attachment_filepath', XMLDB_TYPE_CHAR, null, null, null, null, '0');
            $table->add_field('status', XMLDB_TYPE_INTEGER, 10, null, null, null, '0');

            $table->add_field('nextdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');        
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

            $table->add_field('sent_date', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('sent_by', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $result = $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2019091300.35, 'local', 'courses');
   
    }

    if($oldversion < 2019091300.36){
        $table = new xmldb_table('local_featured_courses');
        $field = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.36, 'local', 'courses');   
    }

    if ($oldversion < 2019091300.37) {
        $table = new xmldb_table('local_course_providers');
        $field = new xmldb_field('shortname', XMLDB_TYPE_TEXT, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.37, 'local', 'courses');
    }

    if ($oldversion < 2019091300.38) {
        $table = new xmldb_table('local_levels');
        $field = new xmldb_field('costcenterid', XMLDB_TYPE_INTEGER, '10', null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.38, 'local', 'courses');
    }


    if ($oldversion < 2019091300.39) {
        $course_type_data = array('MOOC','ILT','E-Learning','Learning Path');

        foreach ($course_type_data as $course_type) { 
            if ($DB->record_exists('local_course_types',  array('course_type' => $course_type))) {
                $id = $DB->get_field('local_course_types','id', array('course_type' => $course_type));
                $record = new stdClass;
                $record->id = $id ;
                $record->shortname = strtolower(str_replace(' ', '', trim($course_type)));
                $DB->update_record('local_course_types', $record);
            }
        }
        upgrade_plugin_savepoint(true, 2019091300.39, 'local', 'courses');
    }

     if ($oldversion < 2019091300.42) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('expirydate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.42, 'local', 'courses');
    }

      if ($oldversion < 2019091300.44) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('expirydateenable', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.44, 'local', 'courses');
    }
    
    if ($oldversion < 2019091300.45) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_ouname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.45, 'local', 'courses');
    }

    if ($oldversion < 2019091300.46) {
        $table = new xmldb_table('local_featured_courses');
        $field = new xmldb_field('featured_lpath_ids',XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.46, 'local', 'courses');
    }

    if ($oldversion < 2019091300.47) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('autoenrol',XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.47, 'local', 'courses');
    }


    if ($oldversion < 2019091300.48) {
        if ($DB->record_exists('local_notification_type',  array('shortname' => 'course_completion_reminder'))) {
            $id = $DB->get_field('local_notification_type','id', array('shortname' => 'course_completion_reminder'));
            $record = new stdClass;
            $record->id = $id ;
            $record->name = 'Course Completion Reminder(After Completion)';
            $DB->update_record('local_notification_type', $record);
        }

        if ($DB->record_exists('local_notification_type',  array('shortname' => 'course_reminder'))) {
            $id = $DB->get_field('local_notification_type','id', array('shortname' => 'course_reminder'));
            $record = new stdClass;
            $record->id = $id ;
            $record->name = 'Course Reminder(Before Completion)';
            $DB->update_record('local_notification_type', $record);
        }

        upgrade_plugin_savepoint(true, 2019091300.48, 'local', 'courses');
    }


    if ($oldversion < 2019091300.49) {
        $table = new xmldb_table('local_course_types');
        $field = new xmldb_field('course_image', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.49, 'local', 'courses');
    }

  /*  if ($oldversion < 2019091300.64) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('open_points', XMLDB_TYPE_FLOAT, '10,1', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019091300.64, 'local', 'courses');
    }
    */
    return true;
}
