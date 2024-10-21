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
function xmldb_local_courses_install(){
	global $CFG,$DB,$USER;
	$dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
	$table = new xmldb_table('course');
	if ($dbman->table_exists($table)) {
		$field1 = new xmldb_field('open_costcenterid');
        $field1->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        $dbman->add_field($table, $field1);
        
        $field2 = new xmldb_field('open_departmentid');
        $field2->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        $dbman->add_field($table, $field2);

        $field21 = new xmldb_field('open_subdepartment');
        $field21->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, null, null, null);
        $dbman->add_field($table, $field21);

        $field3 = new xmldb_field('open_identifiedas');
        $field3->set_attributes(XMLDB_TYPE_CHAR, '255',null, null, null, null);
        $dbman->add_field($table, $field3);
		
		$field4 = new xmldb_field('open_points');
        $field4->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        $dbman->add_field($table, $field4);
		
		$field5 = new xmldb_field('open_requestcourseid');
        $field5->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field5);
		
		$field6 = new xmldb_field('open_coursecreator');
        $field6->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field6);
		
		$field7 = new xmldb_field('open_coursecompletiondays');
        $field7->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field7);

        $field8 = new xmldb_field('open_cost');
        $field8->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $dbman->add_field($table, $field8);

        $field9 = new xmldb_field('open_skill');
        $field9->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, 0, null);
        $dbman->add_field($table, $field9);
		
		$field10= new xmldb_field('approvalreqd');
        $field10->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        $dbman->add_field($table, $field10);

        $field11= new xmldb_field('open_level');
        $field11->set_attributes(XMLDB_TYPE_INTEGER, '10',null, null, 0, null, null);
        $dbman->add_field($table, $field11);

        $field12= new xmldb_field('selfenrol');
        $field12->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        $dbman->add_field($table, $field12);

        $field13 = new xmldb_field('open_grade');
        $field13->set_attributes(XMLDB_TYPE_CHAR, '50', null, null, null, null, null);
        $dbman->add_field($table, $field13);

        $field14 = new xmldb_field('open_ilpcourse');
        $field14->set_attributes(XMLDB_TYPE_INTEGER, '3', null, null, null, 0);
        $dbman->add_field($table, $field14);

        $field15 = new xmldb_field('open_facilitatorcredits');
        $field15->set_attributes(XMLDB_TYPE_FLOAT, '10,5', null, null, null, null);
        $dbman->add_field($table, $field15);

        $field16 = new xmldb_field('open_careertrack');
        $field16->set_attributes(XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $dbman->add_field($table, $field16);

        $field17 = new xmldb_field('expirydate');
        $field17->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        $dbman->add_field($table, $field17);

        $field18 = new xmldb_field('expirydateenable');
        $field18->set_attributes(XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        $dbman->add_field($table, $field18);

        $field19 = new xmldb_field('open_ouname');
        $field19->set_attributes(XMLDB_TYPE_CHAR, '255',null, null, null, null);
        $dbman->add_field($table, $field19);

        $field20 = new xmldb_field('autoenrol');
        $field20->set_attributes( XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        $dbman->add_field($table, $field20);
        
	}

    $table = new xmldb_table('local_course_facilitators');
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
        // $table->add_key('primary', XMLDB_KEY_FOREIGN, array('id'));
        $result = $dbman->create_table($table);
    }

    /*notifictaions content*/
    // $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
    $table = new xmldb_table('local_notification_type');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('parent_module', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('pluginname', XMLDB_TYPE_CHAR, '255', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // $table->add_key('primary', XMLDB_KEY_FOREIGN, array('id'));
        $result = $dbman->create_table($table);
    }
    $table = new xmldb_table('local_notification_info');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('costcenterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_field('moduletype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        // $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('moduleid', XMLDB_TYPE_TEXT, null, null, null, null, null);
        // courses
        $table->add_field('reminderdays', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('completiondays', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('enable_cc', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, '0');
        $table->add_field('adminbody', XMLDB_TYPE_TEXT, null, null, null, null, '0');
        $table->add_field('attachment_filepath', XMLDB_TYPE_CHAR, null, null, null, null, '0');
        $table->add_field('status', XMLDB_TYPE_INTEGER, 10, null, null, null, '0');
        
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('foreign', XMLDB_KEY_FOREIGN, array('costcenterid'));
        // $table->add_key('foreign', XMLDB_KEY_FOREIGN, array('notificationid'));
        // $table->add_key('foreign', XMLDB_KEY_FOREIGN, array('notificationid'));
        $result = $dbman->create_table($table);
    }
    $table = new xmldb_table('local_emaillogs');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notification_infoid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('from_userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('to_userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_field('from_emailid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('to_emailid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        // $table->add_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('moduletype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('moduleid', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('teammemberid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        // courses
        $table->add_field('reminderdays', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enable_cc', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('emailbody', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, '0');
        $table->add_field('adminbody', XMLDB_TYPE_TEXT, null, null, null, null, '0');
        $table->add_field('attachment_filepath', XMLDB_TYPE_CHAR, null, null, null, null, '0');

        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_field('sent_date', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('sent_by', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // $table->add_key('foreign', XMLDB_KEY_FOREIGN, array('costcenterid'));
        // $table->add_key('foreign', XMLDB_KEY_FOREIGN, array('notificationid'));
        // $table->add_key('foreign', XMLDB_KEY_FOREIGN, array('notificationid'));
        $result = $dbman->create_table($table);
    }
    $table = new xmldb_table('local_notification_strings');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('module', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        // $table->add_field('pluginname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        // $table->add_key('primary', XMLDB_KEY_FOREIGN, array('id'));
        $result = $dbman->create_table($table);
    }
    // data insertion.
    $time = time();
    $initcontent = array('name' => 'Course','shortname' => 'course','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'courses');
    $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'course'));
    if(!$parentid){
        $parentid = $DB->insert_record('local_notification_type', $initcontent);
    }
    $notification_type_data = array(
        array('name' => 'Course Enrollment','shortname' => 'course_enrol','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'courses'),
        array('name' => 'Course Completion','shortname' => 'course_complete','parent_module' => $parentid, 'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'courses'),
        array('name' => 'Course Unenrollment','shortname' => 'course_unenroll','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'courses'),
        array('name' => 'Course Reminder','shortname' => 'course_reminder','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'courses'),
        array('name' => 'New Course Notification','shortname' => 'course_notification','parent_module' => $parentid, 'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'courses')
    );
    foreach($notification_type_data as $notification_type){
        unset($notification_type['timecreated']);
        if(!$DB->record_exists('local_notification_type',  $notification_type)){
            $notification_type['timecreated'] = $time;
            $DB->insert_record('local_notification_type', $notification_type);
        }
    }
    $strings = array(
        array('name' => '[course_title]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[enroluser_fullname]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[enroluser_email]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_code]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_enrolstartdate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_enrolenddate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_completiondays]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_department]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_link]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_duedate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_description]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_url]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_description]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_image]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_completiondate]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_reminderdays]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('name' => '[course_categoryname]','module' => 'course','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL)
    );
    foreach($strings as $string){
        unset($string['timecreated']);
        if(!$DB->record_exists('local_notification_strings', $string)){
            $string_obj = (object)$string;
            $string_obj->timecreated = $time;
            $DB->insert_record('local_notification_strings', $string_obj);
        }
    }
    $corecomponent = new \core_component();
    $pluginexist = $corecomponent::get_plugin_directory('local','certificates');
    if($pluginexist ){
        $table = new xmldb_table('course');
       if ($dbman->table_exists($table)) {
        $field = new xmldb_field('open_certificateid', XMLDB_TYPE_INTEGER, 10, null, null, null);
           if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
           }   
        }
    }

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
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('featured_lpath_ids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);

    }

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

    }

    $table = new xmldb_table('local_course_providers');       
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_provider', XMLDB_TYPE_CHAR,  '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_TEXT, null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);        
         $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

    }

    $table = new xmldb_table('local_course_types');

    if($dbman->table_exists($table)){
        $dbman->drop_table( $table );
    }
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_type', XMLDB_TYPE_CHAR,  '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_TEXT, null, XMLDB_NOTNULL, null, null);
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

    $table = new xmldb_table('local_levels');
    if ($dbman->table_exists($table)) {
        $field = new xmldb_field('costcenterid', XMLDB_TYPE_INTEGER, 10, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
           $dbman->add_field($table, $field);
        }   
     }

    $table = new xmldb_table('local_featured_courses');        
    if ($dbman->table_exists($table)) {
        $field = new xmldb_field('featured_lpath_ids',XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    $table = new xmldb_table('local_course_types');
    if ($dbman->table_exists($table)) {
        $field = new xmldb_field('course_image', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    $table = new xmldb_table('course');
    if ($dbman->table_exists($table)) {
        $field = new xmldb_field('autoenrol',XMLDB_TYPE_INTEGER, '10',XMLDB_NOTNULL, null, 0, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    $table = new xmldb_table('course');
    if ($dbman->table_exists($table)) {
        $field = new xmldb_field('open_ouname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }
    $table = new xmldb_table('course');
    if ($dbman->table_exists($table)) {
        $field = new xmldb_field('open_points', XMLDB_TYPE_FLOAT, '10,1', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
    }
}
