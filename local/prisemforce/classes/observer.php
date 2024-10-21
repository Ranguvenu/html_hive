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
 * @package     local_prisemforce
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Event observer for local_prisemforce.
 */
use local_prisemforce\api;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/prisemforce/lib.php');

class local_prisemforce_observer {

    /**
     * Triggered via attemp event.
     *
     * @param \core\event\course_created $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function coursecreated(\core\event\course_created $event) {
        global $DB;
        $course = $event->get_record_snapshot('course', $event->objectid);
        $object = new stdClass();
        $array = [];
        $coursetype = $DB->get_field('local_course_types','course_type',['id' => $course->open_identifiedas]);
        // $object->courseType = $coursetype;
        $courseprovider = $DB->get_field('local_course_providers','course_provider',['id' => $course->open_courseprovider]);
        // $object->sourceSystem = $courseprovider;
        // $object->image = '';
        $hours = floor($course->duration / 3600);
        $min = (($course->duration / 60) ) % 60;
       
        $skills = explode(',',$course->open_skill);
        $skillarry = [];

        foreach ($skills as $key=>$value) {
            $obj = new stdClass();
            $skillname = $DB->get_field('local_skill','shortname',array('id' => $value));
            if(!empty($skillname)){
                $obj->skillprismSkillId = $skillname;
                $obj->skillRating = '2';
                array_push($skillarry, $obj);
            }
        }        
        $object->learningElementId = $course->id;
        $object->pfRecordId = $course->id;
        $object->leType = 'Course';
        $object->leName = $course->fullname;
        $object->leSourceExtId = 'Hive';
        $object->leRecordStatus = 'Active';
        $object->leOrigin = $courseprovider;
        $object->leDescription = $course->summary;
        $object->leContentUrl = $course->open_url;
        if (count($skillarry) > 0) {
            $object->skills = $skillarry;
        }        
        $object->leDurationHours = $hours.'.'.$min;
        $object->leDaysToComplete = $course->open_coursecompletiondays;
        $object->leCreatedDate = date('c',$course->timecreated);
        array_push($array,$object);
        $record = ['data' => $array];
        
        $senddata = json_encode($record);
        $api = new api();
        $response = $api->api_data(get_config('local_prisemforce', 'masterxapikey'), $senddata, 'post');
        if (is_object($response)) {
            $response = json_encode($response);
        }
        $jsonresp = json_decode($response);
        $status = 2;
        if ($jsonresp->success) {
            $status = 1;
        }
        $typeapikey = 1;// For master api key saving as 1
        //Saving the response in the custom logs       
        custom_log_saving($event->eventname, $course->id, $jsonresp->pfTransactionId, $senddata, $response, $status, $typeapikey);
       
    }
    /**
     * Triggered via attemp event.
     *
     * @param \core\event\course_updated $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function courseupdated(\core\event\course_updated $event) {
        global $DB;
        $course = $event->get_record_snapshot('course', $event->objectid);
        $object = new stdClass();
        $array = [];
        $courseprovider = $DB->get_field('local_course_providers','course_provider',['id' => $course->open_courseprovider]);
        $hours = floor($course->duration / 3600);
        $min = (($course->duration / 60) ) % 60;
        $skills = explode(',',$course->open_skill);
        $skillarry = [];
        foreach ($skills as $key=>$value) {
            $obj = new stdClass();
            $skillname = $DB->get_field('local_skill','shortname',['id' => $value]);
            if(!empty($skillname)){
                $obj->skillprismSkillId = $skillname;
                $obj->skillRating = '2';
                array_push($skillarry, $obj);
            }
        }
        $object->learningElementId = $course->id;
        $object->pfRecordId = $course->id;
        $object->leType = 'Course';
        $object->leName = $course->fullname;
        $object->leSourceExtId = 'Hive';
        $object->leRecordStatus = 'Active';
        $object->leOrigin = $courseprovider;
        $object->leDescription = $course->summary;
        $object->leContentUrl = $course->open_url;
        if (count($skillarry) > 0) {
            $object->skills = $skillarry;
        }  
        $object->leDurationHours = $hours.'.'.$min;
        $object->leDaysToComplete = $course->open_coursecompletiondays;
        $object->leCreatedDate = date('c',$course->timecreated);
        array_push($array,$object);
        $record = ['data' => $array];
        $senddata = json_encode($record);
        $api = new api();
        $response = $api->api_data(get_config('local_prisemforce', 'masterxapikey'), $senddata, 'post');
        if (is_object($response)) {
            $response = json_encode($response);
        }
        $jsonresp = json_decode($response);
        $status = 2;
        //Saving the response in the custom logs
        if ($jsonresp->success) {
            $status = 1;
        }
        $typeapikey = 1;// For master api key saving as 1
        custom_log_saving($event->eventname, $course->id, $jsonresp->pfTransactionId, $senddata, $response, $status, $typeapikey);
        
    }
    /**
     * Triggered via attemp event.
     *
     * @param \core\event\course_updated $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function coursedeleted(\core\event\course_deleted $event) {
        global $DB;
        $course = $event->get_record_snapshot('course', $event->objectid);
        $object = new stdClass();
        $array = [];
        $courseprovider = $DB->get_field('local_course_providers','course_provider',['id' => $course->open_courseprovider]);
        $hours = floor($course->duration / 3600);
        $min = (($course->duration / 60) ) % 60;
        $skills = explode(',',$course->open_skill);
        $skillarry = [];
        foreach ($skills as $key=>$value) {
            $obj = new stdClass();
            $skillname = $DB->get_field('local_skill','shortname',array('id' => $value));
            $obj->skillprismSkillId = $skillname;
            $obj->skillRating = '';
            array_push($skillarry, $obj);
        }
        $object->learningElementId = $course->id;
        $object->leType = 'Course';
        $object->leName = $course->fullname;
        $object->leSourceExtId = 'Hive';
        $object->leRecordStatus = 'Inactive';
        $object->leOrigin = $courseprovider;
        $object->leDescription = $course->summary;
        $object->leContentUrl = $course->open_url;
        $object->skills = $skillarry;
        $object->leDurationHours = $hours.'.'.$min;
        $object->leDaysToComplete = $course->open_coursecompletiondays;
        $object->leCreatedDate = date('c',$course->timecreated);
        array_push($array,$object);
        $record = ['data' => $array];
        $senddata = json_encode($record);
        $api = new api();
        $response = $api->api_data(get_config('local_prisemforce', 'masterxapikey'),$senddata,'post');
        if (is_object($response)) {
            $response = json_encode($response);
        }
        $jsonresp = json_decode($response);
        $status = 2;
        if ($jsonresp->success) {
            $status = 1;
        }
        $typeapikey = 1;// For master api key saving as 1
        //Saving the response in the custom logs       
        custom_log_saving($event->eventname, $course->id, $jsonresp->pfTransactionId, $senddata, $response,$status, $typeapikey);
                
    }
    /**
     * Triggered via attemp event.
     *
     * @param \core\event\course_completed $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function coursecompleted(\core\event\course_completed $event) {
        global $DB;
        $course = $event->get_record_snapshot('course', $event->courseid);
        $user = $DB->get_record('user',['id' => $event->relateduserid]);
        $object = new stdClass();
        $array = [];
        $object->pfRecordId = $course->shortname;
        $object->empId = strtoupper($user->open_employeeid);
        $object->leExtId = $event->courseid;
        $object->leType = 'Course';
        $object->progressPercentage = '100';
        $object->recordStatus = 'Active';
        $object->leDesc = '';
        array_push($array,$object);
        $record = ['data' => $array];
        $senddata = json_encode($record);
        $api = new api();
        $response = $api->api_data(get_config('local_prisemforce', 'userxapikey'),$senddata,'post');
        if (is_object($response)) {
            $response = json_encode($response);
        }
        $jsonresp = json_decode($response);
        $status = 2;
        if ($jsonresp->success) {
            $status = 1;
        }
        $typeapikey = 2;// For user api key saving as 2
        //Saving the response in the custom logs
        custom_log_saving($event->eventname, $event->courseid, $jsonresp->pfTransactionId, $senddata, $response, $status, $typeapikey);
        
    }
    /**
     * Triggered via attemp event.
     *
     * @param \core\event\user_enrolment_created $event The triggered event.
     * @return bool Success/Failure.
     */    
    public static function enrolledcreated(\core\event\user_enrolment_created $event) {
        global $DB;
        $enrolldata = $event->get_record_snapshot('user_enrolments', $event->objectid);        
        $course = $event->get_record_snapshot('course', $event->courseid);
        $coursetype = $DB->get_field('local_course_types','course_type',['id' => $course->open_identifiedas]);
        $courseprovider = $DB->get_field('local_course_providers','course_provider',['id' => $course->open_courseprovider]);
        $user = $event->get_record_snapshot('user', $enrolldata->userid);
        $object = new stdClass();
        $array = [];
        $object->pfRecordId = $course->shortname;
        $object->leExtId = $course->id;
        $object->leType = 'Course';
        $object->recordStatus = 'Active';
        $object->progressPercentage = '100';
        $object->empId = strtoupper($user->open_employeeid);      
        $object->leEnrollmentDate = date('c',$enrolldata->timecreated);
        //$object->leStartDate = date('c',$enrolldata->timecreated);
        //$object->leEndDate = '';       
        array_push($array,$object);
        $record = ['data' => $array];
        $senddata = json_encode($record);
        $api = new api();
        $response = $api->api_data(get_config('local_prisemforce', 'userxapikey'),$senddata,'post');
        if (is_object($response)) {
            $response = json_encode($response);
        }
        $jsonresp = json_decode($response);
        $status = 2;
        if ($jsonresp->success) {
            $status = 1;
        }
        $typeapikey = 2;// For user api key saving as 2.
        //Saving the response in the custom logs.    
        custom_log_saving($event->eventname, $event->courseid, $jsonresp->pfTransactionId, $senddata, $response, $status, $typeapikey);
        
    }
    /**
     * Triggered via attemp event.
     *
     * @param \tool_certificate\event\template_created $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function externalcertificate_approved(\local_externalcertificate\event\approve_externalcertificate $event) {
        global $DB;
        $excertificate = $DB->get_record('local_external_certificates',['id' => $event->objectid]);
        $user = $DB->get_record('user',['id' => $excertificate->userid]);
        if ($excertificate->coursename != 'Other') {
            $excourse = $DB->get_record('local_external_certificates_courses',['id' => $excertificate->coursename]);
        }
        // External course details.
        $hours = floor($excertificate->duration / 3600);
        $min = (($excertificate->duration / 60) ) % 60;
        $skills = explode(',',$excertificate->skill);
        $skillarry = [];
        foreach ($skills as $value) {
            $obj = new stdClass();           
            $obj->skillprismSkillId = $value;
            $obj->skillRating = '2';
            array_push($skillarry, $obj);           
        }
        $coursearray = [];
        $courseobj = new stdClass();
        $courseobj->learningElementId = (string)$event->objectid;//$excourse->id;
        $courseobj->pfRecordId = $excourse->coursecode;
        $courseobj->leType = 'Certification';
        $courseobj->leName = $excourse->coursename;
        $courseobj->leRecordStatus = 'Active';
        $courseobj->leOrigin = $excertificate->certificate_issuing_authority;
        $courseobj->leSourceExtId = 'Hive';
        $courseobj->leDescription = $excertificate->description;
        $courseobj->leContentUrl = $excertificate->url;
        $courseobj->skills = $skillarry;
        $courseobj->leDurationHours = $hours.'.'.$min;
        $courseobj->leCreatedDate = date('c',$excertificate->timecreated);
        array_push($coursearray,$courseobj);
        $courserecord = ['data' => $coursearray];
        
        $senddata = json_encode($courserecord);
        $api = new api();
        $courseresponse = $api->api_data(get_config('local_prisemforce', 'masterxapikey'), $senddata, 'post');
        if (is_object($courseresponse)) {
            $courseresponse = json_encode($courseresponse);
        }
        $coursejsonresp = json_decode($courseresponse);
        $status = 2;
        if ($coursejsonresp->success) {
            $status = 1;
        }
        $typeapikey = 1;// For master api key saving as 1.
        // Saving the response in the custom logs.        
        custom_log_saving($event->eventname, $event->objectid, $coursejsonresp->pfTransactionId, $senddata, $courseresponse, $status, $typeapikey);
        if ($coursejsonresp->success) {
            // External certificate details.
            $object = new stdClass();
            $array = [];
            $object->pfRecordId = $excourse->coursecode;
            $object->empId = strtoupper($user->open_employeeid);
            $object->leExtId = (string)$event->objectid;
            $object->leType = 'Certification';
            $object->progressPercentage = '100';
            $object->recordStatus = 'Active';
            array_push($array,$object);
            $record = ['data' => $array];
            $senddata = json_encode($record);
            $response = $api->api_data(get_config('local_prisemforce', 'userxapikey'), $senddata, 'post');
            if (is_object($response)) {
                $response = json_encode($response);
            }
            $jsonresp = json_decode($response);
            if ($jsonresp->success) {
                $status = 1;
            }
            $typeapikey = 2;// For user api key saving as 2.
            //Saving the response in the custom logs            
            custom_log_saving($event->eventname, $event->objectid, $jsonresp->pfTransactionId, $senddata, $response, $status, $typeapikey);

        }      
        
    }
}
