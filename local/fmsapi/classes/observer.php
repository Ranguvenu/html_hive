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
 * Event observer for local_fmsapi. Dont let other user to view unauthorized courses
 */
use local_fmsapi\api;

require_once($CFG->dirroot . '/local/fmsapi/lib.php');

class local_fmsapi_observer {
     /**
     * Triggered via attemp event.
     *
     * @param \mod_quiz\event\attempt_submitted $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function assignmentsubmitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB;
        
        $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
        $quiz    = $event->get_record_snapshot('quiz', $attempt->quiz);                
        $course = $event->get_record_snapshot('course', $event->courseid);
        $user = $DB->get_record('user',['id' => $event->userid]);
        $object = new stdClass();
        $array = [];
        //$object->hiveCourseId = $course->id;
        $object->hiveCourseName = $course->fullname;
        $object->employeeCode = strtoupper($user->open_employeeid);
        $object->assessmentName = $quiz->name;
        $object->scoreObtained = $attempt->sumgrades;
        $object->attemptNo = $attempt->attempt;
        
        
        $senddata = json_encode($object);
        $api = new api();
        $url = get_config('local_fmsapi', 'fmsapiurl');
        $response = $api->api_data($url,$senddata,'post');       
        $jsonresp = json_decode($response);
        
        //Saving the response in the custom logs
        if ($jsonresp->_message) {
            $status = 0;
            if (strtolower($jsonresp->_message) != 'success') {
                $status = 2;
                send_email_fms($object, $user);
            } else {
                $status = 1;
            }
            custom_fmsapi_log_saving($event->eventname, $course->id, $course->id, $senddata, $response,$status);
        }
    }



}
