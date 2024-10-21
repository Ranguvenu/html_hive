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

function xmldb_local_evaluation_upgrade($oldversion) {
	global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2019030702.04) {
        $table = new xmldb_table('local_evaluations');
        $field = new xmldb_field('evaluationmode', XMLDB_TYPE_CHAR, '200', null, XMLDB_NOTNULL, null, 'SE');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019030702.04, 'local', 'evaluation');
    }
    if($oldversion < 2019030702.05){
        $table = new xmldb_table('local_evaluation_completed');
        $field = new xmldb_field('evaluatedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019030702.05, 'local', 'evaluation');
    }

    if ($oldversion < 2019030702.07) {

            $time = time();
            $initcontent = array('name' => 'Feedback','shortname' => 'feedback','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'evaluation');
            $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'feedback'));
            if(!$parentid){
                $parentid = $DB->insert_record('local_notification_type', $initcontent);
            }


            $notification_type_data = array(
            array('name' => 'Feedback Unenrollment','shortname' => 'feedback_unenrollment','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'evaluation'),  
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
            array('name' => '[feedback_unenroldate]','module' => 'feedback','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL)
        );
        foreach($strings as $string){
            unset($string['timecreated']);
            if(!$DB->record_exists('local_notification_strings', $string)){
                $string_obj = (object)$string;
                $string_obj->timecreated = $time;
                $DB->insert_record('local_notification_strings', $string_obj);
            }
        }

        upgrade_plugin_savepoint(true, 2019030702.07, 'local', 'evaluation');
    }
    if($oldversion < 2019030702.09){
        $table = new xmldb_table('local_evaluations');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null,'0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019030702.09, 'local', 'evaluation');
    }

    return true;
}