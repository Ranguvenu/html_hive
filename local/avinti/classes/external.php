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
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_avinti
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/local/courses/lib.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/completion/completion_completion.php');

class local_avinti_external extends external_api {
    /**
     * For getting the courses.
     * @return external_function_parameters
     */
    public static function get_courses_parameters(){
        return new external_function_parameters(
            array()
        );
    }
    public static function get_courses(){
        global $DB;
        $selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_skill,cc.name categoryname FROM {course} c
        JOIN {course_categories} cc ON cc.id = c.category
        WHERE c.visible = 1";
        $courses = $DB->get_records_sql($selectsql,array());               
        if(count($courses)>0){
            foreach($courses as $course){
                $courseid = strip_tags(trim($course->id));
                $coursename = strip_tags(trim($course->fullname));
                $courseshortname = strip_tags(trim($course->shortname));
                $categoryname = strip_tags(trim($course->categoryname));
                $sql = "SELECT id,name FROM {local_skill} WHERE FIND_IN_SET(id, '".$course->open_skill."')";
                $skills = $DB->get_records_sql($sql);
                $skillsdata = '';
                foreach ($skills as $skill) {
                    if ($skillsdata == '') {
                        $skillsdata = $skill->name;
                    } else {
                        $skillsdata .= ','.$skill->name;
                    }
                }
                $image = course_thumbimage($course);
                $courseslist[] = ['courseid' =>$courseid, 
                'coursename' => $coursename,
                'courseshortname'=>$courseshortname,
                'categoryname' => $categoryname,
                'skills' => $skillsdata,
                'courseimage' => $image
               ]; 
            }          
        }
        $result = [
            'result' => $courseslist
        ];  
      
        return $result;
    }
    public static function get_courses_returns()
    {
        return new external_single_structure(
            array(
               'result' => new external_multiple_structure(
                 new external_single_structure(
                     array(
                        'courseid' =>new external_value(PARAM_INT, 'Course id',VALUE_REQUIRED),
                        'coursename' => new external_value(PARAM_RAW, 'Course Name'),
                        'courseshortname' => new external_value(PARAM_RAW, 'Course short name',VALUE_REQUIRED),
                        'categoryname' => new external_value(PARAM_RAW, 'Category Name'),
                        'skills' => new external_value(PARAM_RAW, 'Skills Name'),
                        'courseimage' => new external_value(PARAM_URL, 'Course image'),
                    )
                 )
             )
          )
       );
    }
    /**
     * Enroll users to courses.
     * @return external_function_parameters
     */
    public static function course_enrolment_parameters(){
        return new external_function_parameters(
            array(
            'courseidnumber' =>new external_value(PARAM_INT, 'Course idnumber',VALUE_REQUIRED),
            'emailid' => new external_value(PARAM_RAW, 'Email id'),
            )
        );
    }
    public static function course_enrolment($courseidnumber, $emailid){
        global $DB;
        $status = false;
        $message = '';
        $params = self::validate_parameters(
            self::course_enrolment_parameters(),
            [
                'courseidnumber' => $courseidnumber,
                'emailid' => $emailid,
            ]
        );
        $plugin = enrol_get_plugin('manual');
        $userid = $DB->get_field('user', 'id', ['email' => $emailid]);
        $courseid = $DB->get_field('course', 'id', ['idnumber' => $courseidnumber]);
        if ($userid) {
            $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));
            $course = $DB->get_record('course', array('id'=> $courseid));
            if ($course) {
                $sql = "SELECT e.id FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = :courseid and ue.userid = :userid";
                $userenrol = $DB->get_field_sql($sql,['courseid' => $courseid, 'userid' => $userid]);
                if (!$userenrol) {
                    if (empty($instance)) {
                        // Only add an enrol instance to the course if non-existent
                        $enrolid = $plugin->add_instance($course);
                        $instance = $DB->get_record('enrol', array('id' => $enrolid));
                    }
                    $timestart=$DB->get_field('course','startdate',array('id'=>$course->id));
                    $timeend=0;
                   
                    $plugin->enrol_user($instance, $userid,$instance->roleid,$timestart,$timeend);       
                    $notification = new \local_courses\notification();
                    $user = core_user::get_user($userid);
                    $type = 'course_enrol';
                    $notificationdata = $notification->get_existing_notification($course, $type);
                    
                    if ($notificationdata) {
                        $notification->send_course_email($course, $user, $type, $notificationdata);
                    }                   
                    $status = true;
                    $message = 'User enrolled';
                } else {
                    $status = false;
                    $message = 'User already enrolled to the course.';
                }                
            } else {
                $message = 'Invalid Course idnumber -'. $courseid .'- courseidnumber-'. $courseidnumber;
            }            
        } else {
            $message = 'Incorrect Email id';
        }
        return [
            'status' => $status,
            'message' => $message
       ];
    }
    public static function course_enrolment_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'message' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL)
            )
        );
    }
    /**
     * Course completion.
     * @return external_function_parameters
     */
    public static function custom_course_complete_parameters(){
        return new external_function_parameters(
            array('courseidnumber' =>new external_value(PARAM_INT, 'Course idnumber',VALUE_REQUIRED),
            'emailid' => new external_value(PARAM_RAW, 'Email id'),
            )
        );
    }
    public static function custom_course_complete($courseidnumber, $emailid){
        global $DB;
        $status = false;
        $message = '';
        $params = self::validate_parameters(
            self::custom_course_complete_parameters(),
            [
                'courseidnumber' => $courseidnumber,
                'emailid' => $emailid,
            ]
        );
        $plugin = enrol_get_plugin('manual');
        $userid = $DB->get_field('user', 'id', ['email' => $emailid]);
        $courseid = $DB->get_field('course', 'id', ['idnumber' => $courseidnumber]);
        if ($userid) {
            $course = $DB->get_record('course', array('id'=> $courseid));
            if(!empty($course) && !empty($userid) ) {
                $criterion = new \completion_criteria_role();
      
                // $data=new \stdClass();
                // $data->criteria_role = array('3'=> 3);
                // $data->id = $courseid;     
                // $criterion->update_config($data);


                $completionsql="SELECT id from {course_completions} where userid={$userid} AND course={$course->id} and timecompleted is not NULL";
                $completionexist= $DB->get_field_sql($completionsql);
                
                if(empty($completionexist)){
                    $plugin = enrol_get_plugin('manual');
                    $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
                    
                    // echo "Below one is for Instance";                     
                    $roleid = 5;
                    if (empty($instance)) {
                        $enrolid = $plugin->add_instance($course);
                        $instance = $DB->get_record('enrol', array('id' => $enrolid));
                    }

                    if($userid && $instance){
                        if (!$enrol_manual = enrol_get_plugin('self')) {
                            $enrol_manual->enrol_user($instance, $userid, $roleid, 0,0);
                        }                        
                    } 
                $contextid= $DB->get_field('context','id',array('instanceid'=>$course->id,'contextlevel'=>50));

                $DB->set_field("user_enrolments", "timecreated",time(), array("enrolid" => $instance->id,'userid'=>$userid));
                $DB->set_field("role_assignments", "timemodified",time(), array("contextid" => $contextid,'userid'=>$userid));

                $ccompletion = new \completion_completion(array('course' => $course->id, 'userid' => $userid));
                                            // Mark course as complete and get triggered event.
                $ccompletion->mark_complete();
                $DB->set_field("course_completions", "timecompleted", time(), array("course" => $course->id,'userid'=>$userid));
                $status = true;
                $message = 'Course Completed';   

            } else {
               // echo "Completion not exist, about to set the enrolment and update date";
                $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
                $contextid= $DB->get_field('context','id',array('instanceid'=>$course->id,'contextlevel'=>50));
                $DB->set_field("user_enrolments", "timecreated", time(), array("enrolid" => $instance->id,'userid'=>$userid));
                $DB->set_field("role_assignments", "timemodified", time(), array("contextid" => $contextid,'userid'=>$userid));
                $DB->set_field("course_completions", "timecompleted", time(), array("course" => $course->id,'userid'=>$userid));

                $message = 'Course already Completed';  
            }

        } else {
             $message = 'Incorrect Email id or Courseidnumber';
        }           
               
        } else {
            $message = 'Incorrect Email id';
        }
        return [
            'status' => $status,
            'message' => $message
       ];
    }
    public static function custom_course_complete_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'message' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL)
            )
        );
    }


}
