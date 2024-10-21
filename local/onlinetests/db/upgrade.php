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
 * @package BizLMS
 * @subpackage local_onlinetest
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_onlinetests_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    


    if ($oldversion < 2017051512.02) {

        $time = time();
        $initcontent = array('name' => 'OnlineTest','shortname' => 'onlinetest','parent_module' => '0','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlinetests');
        $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'onlinetest'));
        if(!$parentid){
            $parentid = $DB->insert_record('local_notification_type', $initcontent);
        }

            $notification_type_data = array(
            array('name' => 'OnlineTest Unenrollment','shortname' => 'onlinetest_unenrollment','parent_module' => $parentid,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL, 'pluginname' => 'onlinetests'),   
        );
        foreach($notification_type_data as $notification_type){
            unset($notification_type['timecreated']);
            if(!$DB->record_exists('local_notification_type',  $notification_type)){
                $notification_type['timecreated'] = $time;
                $DB->insert_record('local_notification_type', $notification_type);
            }
        }
        upgrade_plugin_savepoint(true, 2017051512.02, 'local', 'onlinetests');
    }


    //Adding unenroldate string//
    if ($oldversion < 2019090401) {
         $table = new xmldb_table('local_onlinetests');
         $time = time();
         $strings = array( 
            array('name' => '[test_unenroldate]','module' => 'onlinetest','usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL)
        );
        foreach($strings as $string){
            unset($string['timecreated']);
            if(!$DB->record_exists('local_notification_strings', $string)){
                $string_obj = (object)$string;
                $string_obj->timecreated = $time;
                $DB->insert_record('local_notification_strings', $string_obj);
            }
        }
        $field = new xmldb_field('open_points', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2019090401, 'local', 'onlinetests');
    }

    
    return true;
}
