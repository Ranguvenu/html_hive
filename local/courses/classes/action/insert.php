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
 * local courses
 *
 * @package    local_courses
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courses\action;
defined('MOODLE_INTERNAL') or die;
use enrol_get_plugin;

class insert{
    /*
     * @method local_logs Get logs
     * @param $event 
     * @param $module
     * @param $description
     * @param $type
     * @output data will be insert into mdl_local_logs table
     */    
    function local_custom_logs($event, $module, $description, $type=NULL){
    
        global $DB, $USER, $CFG;       
        
        $userid                 = $USER->id; // current userid
        /* creating an object to store parameters*/
        $log_data               = new \stdClass();
        $log_data->event        = $event;
        $log_data->module       = $module;
        $log_data->description  = $description;
        $log_data->type         = $type;
        $log_data->timecreated  = time();
        $log_data->timemodified = time();
        $log_data->usercreated  = $userid;
        $log_data->usermodified = $userid;
        
        $result = $DB->insert_record('local_logs', $log_data);
        return $result;
    }
    
   static function add_enrol_method_tocourse($coursedata,$enrol_status = null,$autoenrol = null){
        global $DB;
       
        $definetypes  = array('learningpath','ilt');
        $types = $coursedata->open_identifiedas;
        $coursetypes = explode(',', $types);

        foreach($coursetypes as $type){
            /*  if($type == 2 || $type == 3 || $type == 5 || $type == 4 || $type ==6 || $type == 1){
            if(constant($type) == 'mooc'){
                $enrolmenttype = 'self';
            }else{
                $enrolmenttype = constant($type);
            } */
            $coursetypename = $DB->get_field('local_course_types','shortname',array('id'=>$type));
            if($coursetypename == 'learningpath'){
                $enrolmenttype = 'learningplan';
            }else if ($coursetypename == 'ilt'){
                $enrolmenttype = 'classroom';
            }else if(isset($autoenrol) && $autoenrol == 'auto'){
                $enrolmenttype = 'auto';
            }else{
                $enrolmenttype = 'self';
                  
            }    
  
            $plugin = \enrol_get_plugin($enrolmenttype);
            if (!$plugin) {
                throw new \moodle_exception('invaliddata', 'error');
            }
            $fields = array();
            $fields['roleid'] = $DB->get_field('role','id',array('shortname' => 'employee'));
           // $fields['type'] = constant($type);
            $fields['type'] = $type;
            $fields['courseid'] = $coursedata->id;
          
            if(!$DB->record_exists('enrol',array('courseid'=> $coursedata->id ,'enrol' => $enrolmenttype))){
                $plugin->add_instance($coursedata, $fields);
            } else {
            
                $existing_method = $DB->get_record('enrol',array('courseid'=> $coursedata->id ,'enrol' => $enrolmenttype));
                if($enrolmenttype == 'self'){
                    if($enrol_status == 1){
                        $existing_method->status = 0;
                    }else{
                            $existing_method->status = 1;
                    }
                }else if($enrolmenttype == 'auto'){
                    if($enrol_status == 1){
                        $existing_method->status = 0;
                        $existing_method->open_ouname = $coursedata->open_ouname ;               
                    }else{
                        $existing_method->status = 1;
                    }
                }else{
                    $existing_method->status = 0;
                }
         
                $DB->update_record('enrol', $existing_method);
            } 
            //}
        }
     }
}
