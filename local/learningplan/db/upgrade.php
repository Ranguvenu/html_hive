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
 * @subpackage learningplan
 * @copyright  2017 Anilkumar.cheguri <anil@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_learningplan_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2016120701) {
        $table = new xmldb_table('local_learningplan');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('summaryfile');
            $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2016120701, 'local', 'learningplan');
    }
        // OL-1042 Add Target Audience to Learningplans//
    if ($oldversion < 2016120703) {

        $table = new xmldb_table('local_learningplan');
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
        //$field = new xmldb_field('department', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        //if ($dbman->field_exists($table, $field)) {
        //    $dbman->change_field_type($table, $field);
        //}
        upgrade_plugin_savepoint(true, 2016120703, 'local', 'learningplan');
    }

    if ($oldversion < 2016120704) {
        $table = new xmldb_table('local_learningplan');
        $field = new xmldb_field('open_points', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field1 = new xmldb_field('lpsequence', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2016120704, 'local', 'learningplan');
    }

    //this is added by sharath for adding moduletype and instnce columns in learningplan_courses table
    if ($oldversion < 2016120705) {
        $table = new xmldb_table('local_learningplan_courses');
        $field = new xmldb_field('moduletype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field1 = new xmldb_field('instance', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        upgrade_plugin_savepoint(true, 2016120705, 'local', 'learningplan');
    }
    //ended here by sharath


    //For unenrolment notification//

    if ($oldversion < 2016120706.02) {

          $time = time();
          $initcontent = array('name' => 'Learning Path','shortname' => 'learningplan','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'learningplan');
          $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'learningplan'));
          if(!$parentid){
            $parentid = $DB->insert_record('local_notification_type', $initcontent);
          }


            $notification_type_data = array(
             array('name' => 'Learning Path Unenrollment','shortname' => 'learningplan_unenrol','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'learningplan'), 
            );
            foreach($notification_type_data as $notification_type){
                unset($notification_type['timecreated']);
                if(!$DB->record_exists('local_notification_type',  $notification_type)){
                    $notification_type['timecreated'] = $time;
                    $DB->insert_record('local_notification_type', $notification_type);
                }
            }


        //Adding unenroldate string//
        $strings = array( 
           array('name' => '[lep_unenroldate]','module' => 'learningplan','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL)
        );
        foreach($strings as $string){
            unset($string['timecreated']);
            if(!$DB->record_exists('local_notification_strings', $string)){
                $string_obj = (object)$string;
                $string_obj->timecreated = $time;
                $DB->insert_record('local_notification_strings', $string_obj);
            }
        }

        upgrade_plugin_savepoint(true, 2016120706.02, 'local', 'learningplan');
    }
    if($oldversion < 2018120706){
        $table = new xmldb_table('local_learningplan');
        $field = new xmldb_field('subdepartment', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018120706, 'local', 'learningplan');   
    }
    if ($oldversion < 2019111101) {
    
        $table = new xmldb_table('local_learningplan');
        $field = new xmldb_field('learning_type');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', null, 0, null, null);
       
        $dbman->change_field_precision($table, $field);

      
        upgrade_plugin_savepoint(true, 2019111101, 'local', 'learningplan');   
    }
    if($oldversion < 2019111101.02){
        $table = new xmldb_table('local_learningplan');
        $field = new xmldb_field('credits');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019111101.02, 'local', 'learningplan');   
    }

    if($oldversion < 2019111101.07){
        $table = new xmldb_table('local_learningplan');
        $field = new xmldb_field('selfenrol', XMLDB_TYPE_INTEGER, 2);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019111101.07, 'local', 'learningplan');   
    }

    if ($oldversion < 2019111101.08) {
        $table = new xmldb_table('local_learningplan');
        $field = new xmldb_field('open_grade',XMLDB_TYPE_TEXT, 'big', null, null, null,null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019111101.08, 'local', 'learningplan');
    }
    if($oldversion < 2019111101.09){
        $table = new xmldb_table('local_learningplan');
        $field = new xmldb_field('open_url', XMLDB_TYPE_TEXT, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019111101.09, 'local', 'learningplan');
    }
    if ($oldversion < 2019111101.11) {
        $table = new xmldb_table('local_lp_unenrolments_log'); 
      
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('lpathid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('coursetypeid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('coursetype', XMLDB_TYPE_CHAR, '255', XMLDB_NOTNULL, null, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
            $table->add_field('unenrol_reason', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('time', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
            upgrade_plugin_savepoint(true, 2019111101.11, 'local', 'learningplan');
        }
    }
   
  return true;
}
