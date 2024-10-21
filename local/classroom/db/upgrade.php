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
 * @package Bizlms 
 * @subpackage local_classroom
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_classroom_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2017050404) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', 'shortname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050404, 'local', 'classroom');
    }
    if ($oldversion < 2017050405) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('config', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050405, 'local', 'classroom');
    }
    if ($oldversion < 2019093004.14) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('manage_approval', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field1 = new xmldb_field('allow_multi_session', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2019093004.14, 'local', 'classroom');
    }
    if ($oldversion < 2017050410) {
        $table = new xmldb_table('local_classroom_courses');
        $field = new xmldb_field('course_duration', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050410, 'local', 'classroom');
    }
    if ($oldversion < 2017050411) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('cr_category', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050411, 'local', 'classroom');
    }
    if ($oldversion < 2019093004.11) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('nomination_startdate', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field1 = new xmldb_field('nomination_enddate', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2019093004.11, 'local', 'classroom');
    }
    if ($oldversion < 2017050415) {
        $table = new xmldb_table('local_classroom_sessions');
        $field = new xmldb_field('timestart', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field1 = new xmldb_field('timefinish', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('sessiontimezone', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        $field3 = new xmldb_field('attendance_status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, '0', null);
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }
        upgrade_plugin_savepoint(true, 2017050415, 'local', 'classroom');
    }
    if ($oldversion < 2017050417) {
        $table = new xmldb_table('local_classroom_sessions');
        $field = new xmldb_field('classroomidid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'classroomid');
        }
        upgrade_plugin_savepoint(true, 2017050417, 'local', 'classroom');
    }
    if ($oldversion < 2017050418) {
        $table = new xmldb_table('local_classroom_sessions');
        $field = new xmldb_field('institueid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'instituteid');
        }
        upgrade_plugin_savepoint(true, 2017050418, 'local', 'classroom');
    }
    if ($oldversion < 2019093004.14) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('capacity', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019093004.14, 'local', 'classroom');
    }
    if ($oldversion < 2017050421) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('department', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050421, 'local', 'classroom');
    }
    if ($oldversion < 2017050422) {
        $table = new xmldb_table('local_classroom_signups');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'local_classroom_attendance');
        }
        upgrade_plugin_savepoint(true, 2017050422, 'local', 'classroom');
    }
    if ($oldversion < 2017050424) {
        $table = new xmldb_table('local_classroom_sessions');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050424, 'local', 'classroom');
    }
    if ($oldversion < 2017050425) {
        $table = new xmldb_table('local_classroom_trainerfb');
        $field = new xmldb_field('classroomidid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'classroomid');
        }
        upgrade_plugin_savepoint(true, 2017050425, 'local', 'classroom');
    }
    if ($oldversion < 2017050430) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('classroomlogo', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050430, 'local', 'classroom');
    }
    if ($oldversion < 2017050433) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('actualsessions', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'activesessions');
        }
        $field1 = new xmldb_field('attendees', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field1)) {
            $dbman->rename_field($table, $field1, 'activeusers');
        }
        $field2 = new xmldb_field('enrolled_users', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field2)) {
            $dbman->rename_field($table, $field2, 'totalusers');
        }
        $field3 = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if ($dbman->field_exists($table, $field3)) {
            $dbman->rename_field($table, $field3, 'status');
        }
        upgrade_plugin_savepoint(true, 2017050433, 'local', 'classroom');
    }
    if ($oldversion < 2017050436) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('department', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050436, 'local', 'classroom');
    }
    if ($oldversion < 2017050439) {
        $table = new xmldb_table('local_classroom_sessions');
        $field = new xmldb_field('moduletype', XMLDB_TYPE_CHAR, '250', null, null, null,null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('moduleid', XMLDB_TYPE_INTEGER, '10', null, null, null,'0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050439, 'local', 'classroom');
    }
    if ($oldversion < 2017050441) {
         $table = new xmldb_table('local_classroom_sessions');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '250', null, null, null,null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050441, 'local', 'classroom');
    }
    if ($oldversion < 2017050444) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('completiondate',XMLDB_TYPE_INTEGER, '10', null, null, null,'0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('local_classroom_users');
        $field = new xmldb_field('completiondate',XMLDB_TYPE_INTEGER, '10', null, null, null,'0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050444, 'local', 'classroom');
    }
    if ($oldversion < 2017050448) {
            $table = new xmldb_table('local_classroom_completion');
            if (!$dbman->table_exists($table)) {
               $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               
               $table->add_field('classroomid', XMLDB_TYPE_INTEGER, '10', null, null, null);
               
               $table->add_field('sessiontracking',XMLDB_TYPE_CHAR, '225', null,null,null,"OR");
               
               $table->add_field('sessionids',XMLDB_TYPE_TEXT, 'big', null,null,null,NULL);
               
               $table->add_field('coursetracking',XMLDB_TYPE_CHAR, '225', null,null,null,"OR");
               
               $table->add_field('courseids',XMLDB_TYPE_TEXT, 'big', null,null,null,NULL);
          
               $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,null, null,0);
               $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               
             
                
               $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               
               $dbman->create_table($table);
            }
         upgrade_plugin_savepoint(true, 2017050448, 'local', 'classroom');
    }
    // OL-1042 Add Target Audience to Classrooms//
    if ($oldversion < 2019093004.11) {
        $table = new xmldb_table('local_classroom');
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
        upgrade_plugin_savepoint(true, 2019093004.11, 'local', 'classroom');
    }
    // OL-1042 Add Target Audience to Classrooms//
    if ($oldversion < 2017050454) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('approvalreqd', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050454, 'local', 'classroom');
    }

    if ($oldversion < 2017050455) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('open_points', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2017050455, 'local', 'classroom');
    }
    if ($oldversion < 2017050464) {
            $table = new xmldb_table('local_classroom_waitlist');
            if (!$dbman->table_exists($table)) {
               $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               
               $table->add_field('classroomid', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, null, null,0);
               $table->add_field('enroltype', XMLDB_TYPE_INTEGER, '10', null, null, null,0);
               $table->add_field('enrolstatus', XMLDB_TYPE_INTEGER, '10', null, null, null,0);
               $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null,null, null,0);
               $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               
               $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               
               $dbman->create_table($table);
            }
         upgrade_plugin_savepoint(true, 2017050464, 'local', 'classroom');
    }
    if ($oldversion < 2019093004.14) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('allow_waitinglistusers', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019093004.14, 'local', 'classroom');
    }
    if ($oldversion < 2017050467) {
        $time = time();
        $initcontent = array('name' => 'Classroom','shortname' => 'classroom','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'classroom');
        $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'classroom'));
        if(!$parentid){
            $parentid = $DB->insert_record('local_notification_type', $initcontent);
        }

        $notification_type_data = array(array('name' => 'Classroom Waiting List','shortname' => 'classroom_enrolwaiting','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'classroom'));
            foreach($notification_type_data as $notification_type){
                unset($notification_type['timecreated']);
                if(!$DB->record_exists('local_notification_type',  $notification_type)){
                    $notification_type['timecreated'] = $time;
                    $DB->insert_record('local_notification_type', $notification_type);
                }
            }
        $strings = array(array('name' => '[classroom_waitinglist_order]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[classroom_waitinguserfulname]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[classroom_waitinguseremail]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL));
        foreach($strings as $string){
            unset($string['timecreated']);
            if(!$DB->record_exists('local_notification_strings', $string)){
                $string_obj = (object)$string;
                $string_obj->timecreated = $time;
                $DB->insert_record('local_notification_strings', $string_obj);
            }
        }
        upgrade_plugin_savepoint(true, 2017050467, 'local', 'classroom');
    }
    if($oldversion < 2019093004){
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('subdepartment', XMLDB_TYPE_CHAR, '50', XMLDB_NOTNULL, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019093004, 'local', 'classroom');   
    }

    if ($oldversion < 2019093004.09) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('classroomlogo', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019093004.09, 'local', 'classroom');
    }

    if ($oldversion < 2019093004.15) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('open_grade',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019093004.15, 'local', 'classroom');
    }
     if ($oldversion < 2019093004.16) {
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('open_prerequisites',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
         upgrade_plugin_savepoint(true, 2019093004.16, 'local', 'classroom');
    }
    if ($oldversion < 2019093004.17) {
        $corecomponent = new \core_component();
        $pluginexist = $corecomponent::get_plugin_directory('tool','certificate');
        if($pluginexist){
            $table = new xmldb_table('local_classroom');
            if ($dbman->table_exists($table)) {
                $field = new xmldb_field('certificateid', XMLDB_TYPE_INTEGER, 10, null, null, null);
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                } 
                upgrade_plugin_savepoint(true, 2019093004.17, 'local', 'classroom');  
            }
        }
    }
     if($oldversion < 2019093004.18){
        $table = new xmldb_table('local_classroom');
        $field = new xmldb_field('open_url', XMLDB_TYPE_TEXT, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019093004.18, 'local', 'classroom');
    }
 
    /* Notification types for classroom feedback reminder*/
    if ($oldversion < 2019093004.22) {
        $time = time();
        $initcontent = array('name' => 'Classroom','shortname' => 'classroom','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'classroom');
        $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'classroom'));
        if(!$parentid){
            $parentid = $DB->insert_record('local_notification_type', $initcontent);
        }

        $notification_type_data = array(array('name' => 'Classroom Feedback Reminder','shortname' => 'classroom_feedback_reminder','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'classroom'));
            foreach($notification_type_data as $notification_type){
                unset($notification_type['timecreated']);
                if(!$DB->record_exists('local_notification_type',  $notification_type)){
                    $notification_type['timecreated'] = $time;
                    $DB->insert_record('local_notification_type', $notification_type);
                }
            }
        $strings = array(array('name' => '[classroom_name]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[feedback_name]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[classroom_startdate]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[classroom_enddate]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[classroom_feedbackurl]','module' => 'classroom','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL));
        foreach($strings as $string){
            unset($string['timecreated']);
            if(!$DB->record_exists('local_notification_strings', $string)){
                $string_obj = (object)$string;
                $string_obj->timecreated = $time;
                $DB->insert_record('local_notification_strings', $string_obj);
            }
        }
        upgrade_plugin_savepoint(true, 2019093004.22, 'local', 'classroom');
    }

    if ($oldversion < 2019093004.28) {
        $table = new xmldb_table('local_classroom_sessions');
        $field = new xmldb_field('outlookeventid', XMLDB_TYPE_CHAR, '250', null, null, null, '0', null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019093004.28, 'local', 'classroom');
    }

    if ($oldversion < 2019093004.30) {
            $table = new xmldb_table('local_classroom_calendarlogs');
            if (!$dbman->table_exists($table)) {
               $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
               $table->add_field('classroomid', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('errormessage',XMLDB_TYPE_CHAR, '225', null,null,null,null);
               $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null);
               $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10',null,null, null,0);
               $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
               $dbman->create_table($table);
            }
         upgrade_plugin_savepoint(true,  2019093004.30, 'local', 'classroom');
    }

    if ($oldversion < 2019093004.31) {
        $table = new xmldb_table('local_classroom_unenrol_log'); 
      
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('classroomid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('coursetypeid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('coursetype', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('unenrol_reason', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
            upgrade_plugin_savepoint(true, 2019093004.31, 'local', 'classroom');
        }
    }
    
    return true;
}
