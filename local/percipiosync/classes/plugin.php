<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This percipiosync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This percipiosync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this percipiosync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * percipiosync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_percipiosync
 */
namespace local_percipiosync;

use \local_courses\action\insert as insert;
use coding_exception;
use ddl_exception;
use dml_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die;

global $CFG,$OUTPUT;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_self.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_unenrol.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_duration.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_grade.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
require_once $CFG->libdir.'/gradelib.php';
require_once($CFG->dirroot.'/completion/completion_completion.php');


abstract class plugin extends \core_tag_tag{
    /** @var string */
    const COMPONENT = 'local_percipiosync';
    const SYNCERROR = 0;
    const SYNCCOMPLETE = 1;
    const SYNCFAILED = 2;
    const SYNCSUCCESS= 3;

    const SYNCTYPEMODULE = 'course';

    const percipiosynctype = array('module'=>self::SYNCTYPEMODULE);
    const percipiosyncstatus = array(self::SYNCERROR=>'Error',self::SYNCCOMPLETE=>'Complete',self::SYNCFAILED=>'Failed',self::SYNCSUCCESS=>'Success');

    /**
     * Returns the plugin's saved settings or the defaults
     *
     * @return array $settings
     */
    public static function crud_plugin_data($data,$status,$statusmessage) {
        global $DB,$USER;
        $percipiosyncdata=new \stdClass();
        $percipiosyncdata->status=$status;
        $percipiosyncdata->statusmessage=$statusmessage;
        $percipiosyncdata->moduleid=$data->moduleid;
        $percipiosyncdata->modulecrud=$data->modulecrud;
        $files = (array)$data;

        $exclude = array('moduleid'=>'moduleid','modulecrud'=>'modulecrud');
        $filtered = array_diff_key($files, $exclude);

        $percipiosyncdata->module = json_encode((object)$filtered);
        $percipiosyncdata->moduletype =self::percipiosynctype[$data->type];
        $percipiosyncdata->timecreated = time();
        $percipiosyncdata->usercreated = $USER->id;
        $DB->insert_record('local_percipiosync_modules', $percipiosyncdata);
    }

    /**
     * Returns the plugin's saved settings or the defaults
     *
     * @return array $settings
     */
    public static function get_plugin_settings() {
        // Defaults.
        $settings = self::get_default_settings();

        // If we have some saved config - let's use that.
        $cfg = (array) get_config(self::COMPONENT);
        foreach (array_keys($settings) as $setting) {
            if (!empty($cfg[$setting])) {
                $settings[$setting] = $cfg[$setting];
                unset($cfg[$setting]);      // Dealt with
            }
        }
        // Add in any other settings.
        $settings = array_merge($settings, $cfg);

        return $settings;
    }

    /**
     * Get the default settings.
     *
     * @return array
     */
    public static function get_default_settings() {
        global $CFG;
        return array(
            'enabled' => 0,
            'apiurl' => 'https://fractal.percipio.com/api-2.0/organizations/',
            'accountid' => parse_url($CFG->wwwroot, PHP_URL_HOST),
            'clientid' => parse_url($CFG->wwwroot, PHP_URL_HOST),
            'clientsecret' => parse_url($CFG->wwwroot, PHP_URL_HOST),
            'catlanguage' => 'en-us',
            'syncfromdate' => '',
            'synctdate' => '',
            'ccategories' => 0,      // Caught out too many times with Misc Category - so 0 it is.
            'coursecustomfilesenabled' => 0    
        );
    }

   
    /**
     * Get the DB field mappings.
     *
     * @return array of arrays.
     */
    public static function get_mapping_fields() {
        return array (
            'coursefields' => array(
                'description' => 'summary',
                'contentUuid' => 'idnumber',
                'title' => 'fullname',
                'duration' => 'duration',
                'url' => 'open_url'
            )
        );
    }
    /**
     * Get the  custom fields.
     *
     * @return array
     */
    public static function get_course_customfields() {
        global $DB;
        return $DB->get_records('customfield_field', null, 'name', 'shortname,name as fullname');
    }
    /**
     * Get the course custom fields.
     *
     * @return array
     */
    public static function get_course_custom_fields($courseid) {
        global $DB;
        $sql = "SELECT d.id,d.value as data,c.name as course_title,c.shortname
        FROM {customfield_data} d
        JOIN {customfield_field} c ON c.id = d.fieldid 
        WHERE d.instanceid=:courseid ";
        return $DB->get_records_sql($sql ,array('courseid' =>$courseid));
    }




    public static function crud_percipiosync($testing = null) {

        global $SITE, $PAGE, $OUTPUT,$DB,$CFG,$USER;
        /* statistics variable */
        $stats = array();
        $stats['totalmodules'] = 0;
        $stats['totallpaths'] = 0;
        $stats['coursescreated'] = 0;
        $stats['coursescreatederrors'] = 0;
        $stats['coursesupdated'] = 0;
        $stats['coursesupdatederrors'] = 0;

        $settings = self::get_plugin_settings();
        
        if (empty($settings['enabled']) || empty($settings['ccategories'])) {
        //     // Log this and return.
           return self::log_event('sync_error', array('errormsg' => get_string('configerror',self::COMPONENT),'crud'=>$crud));
       }

        $catlog = null;     // The Catalog object.
        $maxcnt = null;     // Only used by testing - max count of eache set of records.
        $testing='';
      
        if (empty($catlog)) {

            // No local JSON file - so we call the percipio API.
            $c = new \curl(array('cache'=>true));

           // if ($response = $c->get($settings['apiurl'], $requestparams)) {
            $apicompletions= self::get_usercompletions();
            print_object($apicompletions);
            if($apicompletions){

                if (($apicompletionsresult = json_decode($apicompletions)) === null) {
                    $errormsg = get_string('errorjsonparse',self::COMPONENT,  self::get_last_json_errormsg());
                    return self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
                }
            } else {
                $ci = $c->get_info();
                $errormsg =  get_string('errorapicall',self::COMPONENT, self::get_request_status($ci['http_code']));
                self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
                if ( (int) $ci['http_code'] >= 500) {       // Exception so cron will try again.
                    print_error('errorapicall',self::COMPONENT, null, self::get_request_status($ci['http_code']));
                }
                return false;
            }
        }

        if ($apicompletionsresult) {      // We have data to process.
            // Get the mappings for locallib.php.

           $existingcourses = get_courses("all", 'c.shortname ASC','c.id, c.shortname,c.category,c.idnumber,c.fullname');
            // We build a lookup of the courses - we will add to it if we have to create new courses.
           $courselookup = array();
           foreach ($existingcourses as $xcourse) {
            $courselookup[$xcourse->idnumber] = array('courseid' => $xcourse->id, 'count' => 0);
            }
        $courseproviderid= $DB->get_field('local_course_providers','id',array('shortname'=>"percipio"));
        $coursetypeid= $DB->get_field('local_course_types','id',array('shortname'=>"mooc"));

        if($USER->open_costcenterid){
            $parentcostcenterid = $USER->open_costcenterid;
        }else{
           $parentcostcenterid = $DB->get_field('local_costcenter', 'id', array('parentid'=>0));
       }

        // Get the existing courses.
        $cst=0;

        foreach ($apicompletionsresult as $msmodule) {

            $ctr++;
 
            $idnumber = $msmodule->contentUuid;

            /* St-1::check for todays completions */
           //enter into below condition only if course is not there LMS
         
            if (!isset($courselookup[$idnumber]) ) { 

             /* If file is empty get call the curl to get single course information */
                    echo "The course creation page for today".$cst++;


             $apicourseinfo =  self::get_coursedetails($msmodule->contentUuid);

             foreach ($apicourseinfo as $key=>$value ){
                // print_object($value);
                 $courseinfo->title=$value->localizedMetadata[0]->title;
                 $courseinfo->description=$value->localizedMetadata[0]->description;
                 $courseinfo->url=$value->link;
                 $courseinfo->contentUuid=$value->id;
                 $courseinfo->contenttype= $value->contentType->percipioType;
                 $courseinfo->duration= $value->duration;
                 $levelname= $value->expertiseLevels[0];

             }

             /*Get user information from API and Start creating course */
            //  print_object($courseinfo);
             if($courseinfo->contenttype=="COURSE"){

               // Get the mappings for locallib.php.
                $mappingflds = self::get_mapping_fields();
                            // Our configured catagory.
                $coursecategory = $settings['ccategories'];

                if (!empty($settings['coursemappings'])) {
                    $settings['coursemappings'] = unserialize($settings['coursemappings']);
                }

               // Get the hard coded mapped DB fields.
                $fldlist = array();
                $tagfields = array();

                foreach ($mappingflds['coursefields'] as $fld => $val) {
                  
                        $fldlist[] =  $val;
                  
                }

                $ctr++;
                $crserecord = new \stdClass();
                $crserecord->category = $coursecategory;

                foreach ($fldlist as $dbfld) {

                    $mappedfield = array_search($dbfld, $mappingflds['coursefields']);
                    $crserecord->$dbfld = $courseinfo->$mappedfield;
                }

                           // To create a course we need a shortname.
                $crserecord->shortname = $courseinfo->contentUuid;
                        // Force the summary format to HTML
                $crserecord->summaryformat = FORMAT_HTML;
                $crserecord->enablecompletion = 1;

                        //Â Now to the custom fields.
                $crserecord = self::add_new_customfields($crserecord, $apicourseinfo, $settings['coursemappings']);
              //  $crserecord->open_url = $apicourseinfo->url;

                /* Update the DB */

                try {
                    $crserecord->open_costcenterid = $parentcostcenterid;
                    $crserecord->course_type = $parentcostcenterid ? 1 : 0 ;
                    $crserecord->open_departmentid =0;
                    $crserecord->open_subdepartment =0;
                    $crserecord->open_identifiedas=$coursetypeid;
                    $crserecord->format='toggletop';
                    $crserecord->selfenrol=0;
                    $crserecord->newsitems = 0;
                    $crserecord->open_courseprovider= $courseproviderid;
                    $crserecord->open_grade =-1;
                    $crserecord->open_ouname=-1;
                    $crserecord->open_careertrack= "All";
                    $crserecord->open_facilitatorcredits=0;

                $level = $DB->get_field('local_levels','id',array('name'=>$levelname));
                if($level){
                  $crserecord->open_level =   $level;
                 }else {
                  $crserecord->open_level= '';
                }
                
                  $format = $courseinfo->duration;
                  $hours = (int) filter_var(substr($format, 0, strpos($format, 'H')), FILTER_SANITIZE_NUMBER_INT);
                  $minutes = (int) filter_var(substr($format, strpos($format, 'H'), strpos($format, 'M')-strpos($format, 'H')), FILTER_SANITIZE_NUMBER_INT);
                  $seconds = (int) filter_var(substr($format, strpos($format, 'M')), FILTER_SANITIZE_NUMBER_INT);
                  $crserecord->duration =  ($hours * 3600) + ($minutes * 60) +  $seconds;

                    $newcourse = create_course($crserecord);

                    echo "Course is Successfully Created";

                      $existing_method = $DB->get_record('enrol',array('courseid'=> $newcourse->id  ,'enrol' => 'self'));
                    $existing_method->status = 0;
                    $existing_method->customint6 = 1;
           
                   $DB->update_record('enrol', $existing_method);
                    $newshortname= "C_".$newcourse->id;
                   $DB->set_field("course", "shortname", $newshortname, array("id" => $newcourse->id));
                   
                    $criterion = new \completion_criteria_role();
                    $data=new \stdClass();
                    $data->criteria_role=array('3'=>3);
                    $data->id=$newcourse->id;
                    $criterion->update_config($data);
                    $userins= $DB->get_field('user','id',array('email'=>$msmodule->emailAddress));

                    //self::update_usercompletion($userins,$newcourse);
                    
                     if(!empty($userins) && !empty($newcourse)){
                        $completiondate= strtotime($msmodule->completedDate);
                        $enrolleddate= strtotime($msmodule->firstAccess);
                        self::update_usercompletion($userins,$newcourse,$enrolleddate,$completiondate);
                     }

                } catch (\moodle_exception $me) {
                    $stats['coursescreatederrors']++;
                        // Log the error - this could cause lots of records to be created.
                    $errormsg = get_string('errorcoursecreate',self::COMPONENT, $me->getMessage());
                    $msmodule->moduleid=0;
                    $msmodule->modulecrud='create';
                    self::log_event('sync_error', array('errormsg' => $errormsg,'module'=>$extcourseinfo,'crud'=>$crud));
                    continue;
                }
                $stats['coursescreated']++;
                    // Add new course to lookup.
                $courselookup[$newcourse->idnumber] = array('courseid' => $newcourse->id, 'count' => 1);
            }

        }
        else {
                echo "Course is already there,about to mark complete for user";
             $completiondate= strtotime($msmodule->completedDate);
             $enrolleddate= strtotime($msmodule->firstAccess);
              $course=$DB->get_record('course',array('idnumber'=>$msmodule->contentUuid));
              $userid= $DB->get_field('user','id',array('email'=>$msmodule->emailAddress));
          

              if(!empty($course) && !empty($userid) ) {

              $completionsql="SELECT id from {course_completions} where userid={$userid} AND course={$course->id} and timecompleted is not NULL";
              $completionexist= $DB->get_field_sql($completionsql);
 
               if(empty($completionexist)){
              self::update_usercompletion($userid,$course,$enrolleddate,$completiondate);
                }else {
               echo "Completion not exist, about to set the enrolment and update date";
                $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
                $contextid= $DB->get_field('context','id',array('instanceid'=>$course->id,'contextlevel'=>50));
                $DB->set_field("user_enrolments", "timecreated", $enrolleddate, array("enrolid" => $instance->id,'userid'=>$userid));
                $DB->set_field("role_assignments", "timemodified", $enrolleddate, array("contextid" => $contextid,'userid'=>$userid));
                $DB->set_field("course_completions", "timecompleted", $completiondate, array("course" => $course->id,'userid'=>$userid));

            }

             }

        }                
    }

}else {
   

    $ci = $c->get_info();
    $errormsg =  get_string('nocompletionsrecords',self::COMPONENT, self::get_request_status($ci['http_code']));
    self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));

    return false;
}

/* Log Completion Stats */
$crud=1;
self::log_event('process_complete', self::format_stats_report($stats,$crud));

        return true;        // Always - cron expects Exception to recognise errors.

    }

    private static function get_usercompletions(){
        global $DB, $CFG;
        
        $settings = self::get_plugin_settings();
        $todate = date("Y-m-d");
        $fromdate=date("Y-m-d",strtotime("-7 days"));
        
       // $todate = "2022-06-30";
        //$fromdate="2022-04-01";
        
       // $fromdate= $settings['syncfromdate'];
       // $todate= $settings['synctdate'];

         echo "FromDate from Settings".$fdate;
         echo "ToDate from settings".$tdate;
      
        $d = date('Y-m-d H:i:s');
        $fromdate=$fromdate."T13:00:00Z";
        $todate= $todate."T13:00:00Z";
        
        
          echo $fromdate."<br/>";
        echo "Todate".$todate;
  
      //  $authcode= $settings['clientsecret'];
      //  $hosturl= $settings['apiurl'];
        $authcode= "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzZXJ2aWNlX2FjY291bnRfaWQiOiIyYTgzNjVmMS05YzZhLTRiZjEtOGE0Ny01MDQxMzVmZmYzYWUiLCJvcmdhbml6YXRpb25faWQiOiIxODFlMmEyMS03MDNhLTRjZTktYjk5OC04ODI0OWYyODVjOTEiLCJpc3MiOiJhcGkucGVyY2lwaW8uY29tIiwiaWF0IjoxNjM3NTQzNDgyLCJzdWIiOiIyNmEwZTkyYzk0YWJhMTBjYTQ5OGUxZDU2Mjk0NzY4NTQzNzQ0MzdlIiwicG9saWN5LWlkIjoicGVyY2lwaW8tYXBpLXN0YW5kYXJkLXBvbGljeSJ9.B44T-oniRpXCwO7ZoXrWiXNiJ9s51i4fW4WOrwbtaKQj1IGdZZy34JJoVjoqi64Z0TJi_zimE3uUepf0I4IMcvKgCW0_iJocUF6LYQo3ZZWwoaWt51k1zLTYDz1_yrklEvTyBkLo8W4vGQuMm-K1EmP6QFETZIziHVLfx0stZEb9ta5uPDALuRwDqU9vP8DFPrxcaBY-G-N43QdKO3I8K2I0cMw4AOySAuvK8Cvoxd7oy9RVPH8vladKYeHkjsZFjDrXvZbptTO_vZMszBPcRqEaAVT6qoKJuwPkpRWUkYnAkx3mHTKiQ7gIG2cAIgkEgnaqYDA4skNDqNOs0FfiOcCsnvetvGpo21yAKtwYkGfPLyOdiKWu8ldH0rnWkibg7T43DbajTeKdK1cx2v5BhV7BwRvWbFaMlf7dJsCPmdwgIhqx03hNxSuldxnC-7tjQYanj687810CvTSMvHMI6YRaRpFxnUTY8EwHOh6tieZTGWAMXtVxgDjJGk2YC4MLDHLrbM207wK8vvFxycyMxd7YRluJSct50cUHJkC0w0HbF9yfHo2LpcNPKwnsQrQYPBKxYxAV6IJXxZ15QpfZolZ1l8-ObREcM4LBfWSgHxjHmc3oTC_uuQC8lh2gwPKqzjNLxQgNfTPbjD5U-zz9L6eZiXsO_YendtRIN0XXAFM";
        $hosturl= "https://api.percipio.com/reporting/v1/organizations/181e2a21-703a-4ce9-b998-88249f285c91";

        $audience="ALL";
        $contenttype="Course";
        $satus= "COMPLETED";
        $formatType="JSON";
        $flname="false";

        $curl = curl_init();
        
       	$data='{
"start":"'.$fromdate.'",
"end":"'.$todate.'",
"audience":"ALL",
"contentType":"Course",
"isFileRequiredInSftp":false,
"formatType":"JSON",
"status":"COMPLETED",
"includeMillisInFilename":false
}';

        curl_setopt_array($curl, array(
         CURLOPT_URL => "$hosturl/report-requests/learning-activity",
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'POST',
         CURLOPT_POSTFIELDS =>$data,
 CURLOPT_HTTPHEADER => array(
    "Content-Type: application/json",
    "Authorization: Bearer $authcode"
  ),
     ));
     
  
        $response = curl_exec($curl);
        $reportinfo= json_decode($response);
        curl_close($curl);
        if($response){
           // $reportinfo->id= "e2aa6655-bc8a-4cb6-a789-a3459b7b7be2";
             echo "Step-2:::Below is the Report ID from Completion API".$reportinfo->id;
             print_object($reportinfo);
          $reportresponse=  self::get_apireports($reportinfo->id);

      }
    
      return $reportresponse;

  }

  private static function get_apireports($reportid){
 
      global $DB, $CFG;

   $settings = self::get_plugin_settings();
   $authcode= $settings['clientsecret'];
  // $hosturl= $settings['apiurl'];
   echo "Auth Code---:".$authcode;
   $curl = curl_init();
   
   $authcode= "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzZXJ2aWNlX2FjY291bnRfaWQiOiIyYTgzNjVmMS05YzZhLTRiZjEtOGE0Ny01MDQxMzVmZmYzYWUiLCJvcmdhbml6YXRpb25faWQiOiIxODFlMmEyMS03MDNhLTRjZTktYjk5OC04ODI0OWYyODVjOTEiLCJpc3MiOiJhcGkucGVyY2lwaW8uY29tIiwiaWF0IjoxNjM3NTQzNDgyLCJzdWIiOiIyNmEwZTkyYzk0YWJhMTBjYTQ5OGUxZDU2Mjk0NzY4NTQzNzQ0MzdlIiwicG9saWN5LWlkIjoicGVyY2lwaW8tYXBpLXN0YW5kYXJkLXBvbGljeSJ9.B44T-oniRpXCwO7ZoXrWiXNiJ9s51i4fW4WOrwbtaKQj1IGdZZy34JJoVjoqi64Z0TJi_zimE3uUepf0I4IMcvKgCW0_iJocUF6LYQo3ZZWwoaWt51k1zLTYDz1_yrklEvTyBkLo8W4vGQuMm-K1EmP6QFETZIziHVLfx0stZEb9ta5uPDALuRwDqU9vP8DFPrxcaBY-G-N43QdKO3I8K2I0cMw4AOySAuvK8Cvoxd7oy9RVPH8vladKYeHkjsZFjDrXvZbptTO_vZMszBPcRqEaAVT6qoKJuwPkpRWUkYnAkx3mHTKiQ7gIG2cAIgkEgnaqYDA4skNDqNOs0FfiOcCsnvetvGpo21yAKtwYkGfPLyOdiKWu8ldH0rnWkibg7T43DbajTeKdK1cx2v5BhV7BwRvWbFaMlf7dJsCPmdwgIhqx03hNxSuldxnC-7tjQYanj687810CvTSMvHMI6YRaRpFxnUTY8EwHOh6tieZTGWAMXtVxgDjJGk2YC4MLDHLrbM207wK8vvFxycyMxd7YRluJSct50cUHJkC0w0HbF9yfHo2LpcNPKwnsQrQYPBKxYxAV6IJXxZ15QpfZolZ1l8-ObREcM4LBfWSgHxjHmc3oTC_uuQC8lh2gwPKqzjNLxQgNfTPbjD5U-zz9L6eZiXsO_YendtRIN0XXAFM";
   $hosturl= "https://api.percipio.com/reporting/v1/organizations/181e2a21-703a-4ce9-b998-88249f285c91";
   curl_setopt_array($curl, array(
     CURLOPT_URL => "$hosturl/report-requests/$reportid",
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => '',
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => 'GET',
     CURLOPT_HTTPHEADER => array("Authorization: Bearer $authcode"),
 ));

   $response = curl_exec($curl);

   curl_close($curl);
   
   if($response){
    echo "Step-3:::Response from Report Details API";
    print_object($response);
   }

   return $response;

}

private static function get_coursedetails($coursecode){
      global $DB, $CFG;

    $settings = self::get_plugin_settings();
    $authcode= $settings['clientsecret'];
    
    $curl = curl_init();
    $authcode= "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzZXJ2aWNlX2FjY291bnRfaWQiOiIyYTgzNjVmMS05YzZhLTRiZjEtOGE0Ny01MDQxMzVmZmYzYWUiLCJvcmdhbml6YXRpb25faWQiOiIxODFlMmEyMS03MDNhLTRjZTktYjk5OC04ODI0OWYyODVjOTEiLCJpc3MiOiJhcGkucGVyY2lwaW8uY29tIiwiaWF0IjoxNjM3NTQzNDgyLCJzdWIiOiIyNmEwZTkyYzk0YWJhMTBjYTQ5OGUxZDU2Mjk0NzY4NTQzNzQ0MzdlIiwicG9saWN5LWlkIjoicGVyY2lwaW8tYXBpLXN0YW5kYXJkLXBvbGljeSJ9.B44T-oniRpXCwO7ZoXrWiXNiJ9s51i4fW4WOrwbtaKQj1IGdZZy34JJoVjoqi64Z0TJi_zimE3uUepf0I4IMcvKgCW0_iJocUF6LYQo3ZZWwoaWt51k1zLTYDz1_yrklEvTyBkLo8W4vGQuMm-K1EmP6QFETZIziHVLfx0stZEb9ta5uPDALuRwDqU9vP8DFPrxcaBY-G-N43QdKO3I8K2I0cMw4AOySAuvK8Cvoxd7oy9RVPH8vladKYeHkjsZFjDrXvZbptTO_vZMszBPcRqEaAVT6qoKJuwPkpRWUkYnAkx3mHTKiQ7gIG2cAIgkEgnaqYDA4skNDqNOs0FfiOcCsnvetvGpo21yAKtwYkGfPLyOdiKWu8ldH0rnWkibg7T43DbajTeKdK1cx2v5BhV7BwRvWbFaMlf7dJsCPmdwgIhqx03hNxSuldxnC-7tjQYanj687810CvTSMvHMI6YRaRpFxnUTY8EwHOh6tieZTGWAMXtVxgDjJGk2YC4MLDHLrbM207wK8vvFxycyMxd7YRluJSct50cUHJkC0w0HbF9yfHo2LpcNPKwnsQrQYPBKxYxAV6IJXxZ15QpfZolZ1l8-ObREcM4LBfWSgHxjHmc3oTC_uuQC8lh2gwPKqzjNLxQgNfTPbjD5U-zz9L6eZiXsO_YendtRIN0XXAFM";
    $hosturl= "https://api.percipio.com/content-discovery/v1/organizations/181e2a21-703a-4ce9-b998-88249f285c91/search-content/?q=$coursecode";

    curl_setopt_array($curl, array(
     CURLOPT_URL => "$hosturl",
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => '',
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => 'GET',
     CURLOPT_HTTPHEADER => array("Authorization: Bearer $authcode"),
 ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);

}

private static function get_allcourses(){
    
      global $DB, $CFG;

    $settings = self::get_plugin_settings();
    $authcode= $settings['clientsecret'];

    $curl = curl_init();

   $authcode="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzZXJ2aWNlX2FjY291bnRfaWQiOiIyYTgzNjVmMS05YzZhLTRiZjEtOGE0Ny01MDQxMzVmZmYzYWUiLCJvcmdhbml6YXRpb25faWQiOiIxODFlMmEyMS03MDNhLTRjZTktYjk5OC04ODI0OWYyODVjOTEiLCJpc3MiOiJhcGkucGVyY2lwaW8uY29tIiwiaWF0IjoxNjM3NTQzNDgyLCJzdWIiOiIyNmEwZTkyYzk0YWJhMTBjYTQ5OGUxZDU2Mjk0NzY4NTQzNzQ0MzdlIiwicG9saWN5LWlkIjoicGVyY2lwaW8tYXBpLXN0YW5kYXJkLXBvbGljeSJ9.B44T-oniRpXCwO7ZoXrWiXNiJ9s51i4fW4WOrwbtaKQj1IGdZZy34JJoVjoqi64Z0TJi_zimE3uUepf0I4IMcvKgCW0_iJocUF6LYQo3ZZWwoaWt51k1zLTYDz1_yrklEvTyBkLo8W4vGQuMm-K1EmP6QFETZIziHVLfx0stZEb9ta5uPDALuRwDqU9vP8DFPrxcaBY-G-N43QdKO3I8K2I0cMw4AOySAuvK8Cvoxd7oy9RVPH8vladKYeHkjsZFjDrXvZbptTO_vZMszBPcRqEaAVT6qoKJuwPkpRWUkYnAkx3mHTKiQ7gIG2cAIgkEgnaqYDA4skNDqNOs0FfiOcCsnvetvGpo21yAKtwYkGfPLyOdiKWu8ldH0rnWkibg7T43DbajTeKdK1cx2v5BhV7BwRvWbFaMlf7dJsCPmdwgIhqx03hNxSuldxnC-7tjQYanj687810CvTSMvHMI6YRaRpFxnUTY8EwHOh6tieZTGWAMXtVxgDjJGk2YC4MLDHLrbM207wK8vvFxycyMxd7YRluJSct50cUHJkC0w0HbF9yfHo2LpcNPKwnsQrQYPBKxYxAV6IJXxZ15QpfZolZ1l8-ObREcM4LBfWSgHxjHmc3oTC_uuQC8lh2gwPKqzjNLxQgNfTPbjD5U-zz9L6eZiXsO_YendtRIN0XXAFM";
    $hosturl= "https://api.percipio.com/content-discovery/v1/organizations/181e2a21-703a-4ce9-b998-88249f285c91/catalog-content/?offset=0&min=5000&max=7000";

    curl_setopt_array($curl, array(
     CURLOPT_URL => "$hosturl",
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => '',
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => 'GET',
     CURLOPT_HTTPHEADER => array("Authorization: Bearer $authcode"),
 ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;

}


    /**
     * Format the processing stats for the completion event.
     *
     * @param array $stats - the stats array.
     * @return array of strings.
     */
    private static function format_stats_report($stats,$crud) {
        global $SITE, $PAGE, $OUTPUT;

        $newstats=array();
        foreach ($stats as $statname => $statvalue) {

            $notificationtype='success';
            
            if(strpos($statname,"error")){
               $notificationtype='error';
           }
           if(strpos($statname,"failed")){
               $notificationtype='notifyproblem';
           }
           if ($statvalue) {
            if($crud=='r'){
                echo $OUTPUT->notification(get_string('event'.$statname,plugin::COMPONENT) . ':' . $statvalue, $notificationtype);
            }

            $newstats[] = get_string('event'.$statname,plugin::COMPONENT) . ':' . $statvalue;

        }
    }
    return $newstats;
}

    /**
     * Get the changed shortname if neccessary.
     *
     * @param string $url
     * @param string $existingshortname
     * @return boolean|string
     */
    private static function get_changed_shortname($url, $existingshortname) {
        $shortname = basename(parse_url($url,  PHP_URL_PATH));

        if ($shortname == $existingshortname) {
            return false;
        }
        return $shortname;
    }

    private static function get_courses_learningformats() {
        global $DB;
        $learningformatid=null;
        $result = $DB->get_manager()->table_exists('local_courses_learningformat');
        if($result){
            $learningformatid=$DB->get_field('local_courses_learningformat','id',array('shortname'=>'Online Course'));
        }
        return $learningformatid;
    }
    

    /**
     * Compares exisiting and current field mappings and adds new fields to the record.
     *
     * @param stdClass $crserecord - the course or prgram record.
     * @param stdClass $msmodule - the course indo
     * @param array $coursemapping - the current mapping settings
     * @return stdClass - returns the updated course or prgram record.
     */
    private static function add_new_customfields($crserecord, $msmodule, $coursemapping) {
        $thisyear = date('Y');

        foreach ($coursemapping as $msmodfld => $dbfld) {
            if (isset($msmodule->$msmodfld)) {
                // Field rename is so save_course will process the fields.
                $dbfldformname = 'customfield_' . $dbfld;
                /*
                 * I only got to worry about URLS and date fields - HACK - the rest can go to the db as is.
                 *
                 * There may be issues with other field types but time is not on our side - we would ahve to parse a moodle
                 * form to ensure the data ends up exactly like it should.
                 */
                $crserecord->$dbfldformname = $msmodule->$msmodfld;
                
            }
        }

        return $crserecord;
    }

    /**
     * Raises the event events which triggers an entry in the standard log.
     *
     * @param string $eventname
     * @param array $otherdata
     * @param stdClass $context
     * @return boolean - always false.
     */
    private static function log_event($eventname, array $otherdata, $context = null) {
        global $SITE, $PAGE, $OUTPUT;

        if(isset($otherdata['errormsg'])){
            $eventdata = array(
                'other' => $otherdata['errormsg'],
            );
        }else{
            $eventdata = array(
                'other' => $otherdata,
            );
        }
        switch ($eventname) {
            case 'sync_error' :

            if($otherdata['crud']=='r'){
                echo $OUTPUT->notification($otherdata['errormsg'], 'error');
            }
            $event = \local_percipiosync\event\sync_failed::create($eventdata);
            $event->trigger();
            if(isset($otherdata['module'])){
                plugin::crud_plugin_data($otherdata['module'],plugin::SYNCFAILED,$otherdata['errormsg']);
            }


            break;
            case 'process_error' :

            if($otherdata['crud']=='r'){
                echo $OUTPUT->notification($otherdata['errormsg'], 'error');
            }

            $event = \local_percipiosync\event\sync_error::create($eventdata);
            $event->trigger();
            if(isset($otherdata['module'])){
                plugin::crud_plugin_data($otherdata['module'],plugin::SYNCERROR,$otherdata['errormsg']);
            }

            break;
            case 'process_complete':

            if($otherdata){

                $event = \local_percipiosync\event\sync_complete::create($eventdata);
                $event->trigger();

            }

            break;
            case 'sync_success' :

            if($otherdata['crud']=='r'){
                echo $OUTPUT->notification($otherdata['errormsg'], 'success');
            }

            $event = \local_percipiosync\event\sync_failed::create($eventdata);
            $event->trigger();
            if(isset($otherdata['module'])){
                plugin::crud_plugin_data($otherdata['module'],plugin::SYNCSUCCESS,$otherdata['errormsg']);
            }

            break;
            //default : No logging
        }
        return false;       
    }

    /**
     * Returns human readable JSON error since PHP JSON functionality does not :(
     *
     * @return string
     */
    private static function get_last_json_errormsg() {
        $errmsg = '';
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
            $errmsg = ' No errors';
            break;
            case JSON_ERROR_DEPTH:
            $errmsg = 'Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
            $errmsg = 'Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
            $errmsg = 'Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
            $errmsg = 'Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
            $errmsg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
            $errmsg = 'Unknown error';
            break;
        }
        return $errmsg;
    }

    /**
     *  Returns the status message from the HTTP status code.
     *
     * @param string/int $statusno - the HTTPO status code
     * @return string - the HTTP status message.
     */
    private static function get_request_status($statusno) {
        $requeststatus = array(
            '200' => 'Success. The body of the response includes the JSON-encoded data.',
            '400' => 'One of the query parameters is missing or not valid.',
            '401' => 'Unauthorized request. The clientId query parameter is missing from the URL.',
            '404' => 'The URL wasn\'t found on the server.',
            '500' => 'Unexpected server error.',
            '503' => 'The service is temporarily unavailable.'
        );

        if (in_array($statusno, array_keys($requeststatus))) {
            return $requeststatus[$statusno];
        }
        return '';
    }
    
    public static function markcomplete_percipiocourse($testing = null,$crud=null) {

        global $SITE, $PAGE, $OUTPUT,$DB,$CFG;
        /* statistics variable */
        $stats = array();
        $stats['totalmodules'] = 0;
        $stats['totallpaths'] = 0;
        $stats['coursescreated'] = 0;
        $stats['coursescreatederrors'] = 0;
        $stats['coursesupdated'] = 0;
        $stats['coursesupdatederrors'] = 0;

        $settings = self::get_plugin_settings();
        
        if (empty($settings['enabled']) || empty($settings['ccategories'])) {
             // Log this and return.
           return self::log_event('sync_error', array('errormsg' => get_string('configerror',self::COMPONENT),'crud'=>$crud));
       }

        $catlog = null;     // The Catalog object.
        $maxcnt = null;     // Only used by testing - max count of eache set of records.
        $testing='';

        if (empty($catlog)) {

           // HTTP GET Method
            $response= self::get_usercompletions();
            if($response){

                if (($catlog = json_decode($response)) === null) {
                    $errormsg = get_string('errorjsonparse',self::COMPONENT,  self::get_last_json_errormsg());
                    return self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
                }
            } else {
                $ci = $c->get_info();
                $errormsg =  get_string('errorapicall',self::COMPONENT, self::get_request_status($ci['http_code']));
                self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
                if ( (int) $ci['http_code'] >= 500) {       // Exception so cron will try again.
                    print_error('errorapicall',self::COMPONENT, null, self::get_request_status($ci['http_code']));
                }
                return false;
            }
        }

        if ($catlog) {      // We have data to process.
            // Get the mappings for locallib.php.

           foreach ($catlog as $msmodule) {

            $ctr++;
            /* St-1::check for todays completions */

            if (isset($msmodule->completedDate)) {  


                try {

                 $courseid= $DB->get_field('course','id',array('idnumber'=>$msmodule->contentUuid));   
                 $userins= $DB->get_field('user','id',array('email'=>$msmodule->emailAddress));

                 $ccompletion = new \completion_completion(array('course' => $courseid, 'userid' => $userins));
                    // Mark course as complete and get triggered event.
                 $ccompletion->mark_complete();

                 $successmsg = get_string('successcoursecreate',self::COMPONENT, $extcourseinfo->id);
                 self::log_event('markcompletionsync_success', array('errormsg' => $successmsg,'module'=>$extcourseinfo,'crud'=>$crud));
                 $stats['coursecompletionmarkerrors']++;

             } catch (\moodle_exception $me) {
                    // Log the error - this could cause lots of records to be created.
                $errormsg = get_string('errorcoursecompletionmarking',self::COMPONENT, $me->getMessage());

                self::log_event('sync_error', array('errormsg' => $errormsg,'module'=>$extcourseinfo,'crud'=>$crud));
                continue;
            }
            $stats['markcomplete']++;
                // Add new course to lookup.

        }                
    }

}

/* Log Completion Stats */
self::log_event('process_complete', self::format_stats_report($stats,$crud));
return true;        

}


public static function sync_allcourses(){

        global $SITE, $PAGE, $OUTPUT,$DB,$CFG,$USER;
        /* statistics variable */
        $stats = array();
        $stats['totalmodules'] = 0;
        $stats['totallpaths'] = 0;
        $stats['coursescreated'] = 0;
        $stats['coursescreatederrors'] = 0;
        $stats['coursesupdated'] = 0;
        $stats['coursesupdatederrors'] = 0;

        $settings = self::get_plugin_settings();
        
        if (empty($settings['enabled']) || empty($settings['ccategories'])) {
        //     // Log this and return.
           return self::log_event('sync_error', array('errormsg' => get_string('configerror',self::COMPONENT),'crud'=>$crud));
       }

        $catlog = null;     // The Catalog object.
        $maxcnt = null;     // Only used by testing - max count of eache set of records.
        $testing='';
      
        if (empty($catlog)) {

            // No local JSON file - so we call the percipio API.
            $c = new \curl(array('cache'=>true));

           // if ($response = $c->get($settings['apiurl'], $requestparams)) {
            $apicompletions= self::get_allcourses();

            if($apicompletions){

                if (($apicompletionsresult = json_decode($apicompletions)) === null) {
                    $errormsg = get_string('errorjsonparse',self::COMPONENT,  self::get_last_json_errormsg());
                    return self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
                }
            } else {
                $ci = $c->get_info();
                $errormsg =  get_string('errorapicall',self::COMPONENT, self::get_request_status($ci['http_code']));
                self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
                if ( (int) $ci['http_code'] >= 500) {       // Exception so cron will try again.
                    print_error('errorapicall',self::COMPONENT, null, self::get_request_status($ci['http_code']));
                }
                return false;
            }
        }

        if ($apicompletionsresult) {      // We have data to process.
            // Get the mappings for locallib.php.

           $existingcourses = get_courses("all", 'c.shortname ASC', 'c.id, c.shortname,c.category,c.idnumber,c.fullname');
            // We build a lookup of the courses - we will add to it if we have to create new courses.
           $courselookup = array();
           foreach ($existingcourses as $xcourse) {
            $courselookup[$xcourse->idnumber] = array('courseid' => $xcourse->id, 'count' => 0);
        }
        $courseproviderid= $DB->get_field('local_course_providers','id',array('shortname'=>"percipio"));
        $coursetypeid= $DB->get_field('local_course_types','id',array('shortname'=>"mooc"));

        if($USER->open_costcenterid){
            $parentcostcenterid = $USER->open_costcenterid;
        }else{
           $parentcostcenterid = $DB->get_field('local_costcenter', 'id', array('parentid'=>0));
       }

        // Get the existing courses.

        foreach ($apicompletionsresult as $msmodule) {

            $ctr++;

            $idnumber = $msmodule->id;
           /* St-1::check for todays completions */
           //enter into below condition only if course is not there LMS

            $contenttype= array();
             $contenttype= $msmodule->contentType;


          if($contenttype->percipioType=="COURSE"){

            echo "Below one is course to create";
             $courseinfo->title=$msmodule->localizedMetadata[0]->title;
             $courseinfo->description=$msmodule->localizedMetadata[0]->description;
             $courseinfo->url=$msmodule->link;
             $courseinfo->contentUuid=$msmodule->id;
             $courseinfo->contenttype= $msmodule->contentType->percipioType;
            $extlevelname= $msmodule->expertiseLevels[0];
            echo "the Level".$extlevelname;

             $format = $msmodule->duration;
             $hours = (int) filter_var(substr($format, 0, strpos($format, 'H')), FILTER_SANITIZE_NUMBER_INT);
             $minutes = (int) filter_var(substr($format, strpos($format, 'H'), strpos($format, 'M')-strpos($format, 'H')), FILTER_SANITIZE_NUMBER_INT);
            $seconds = (int) filter_var(substr($format, strpos($format, 'M')), FILTER_SANITIZE_NUMBER_INT);
            $courseinfo->duration =  ($hours * 3600) + ($minutes * 60) +  $seconds;

            if ($hours < 1) {
                $credits = '0.5';
            } elseif (($hours >= 1 && $hours <= 4) || ($hours == 4 && $minutes <= 59)) {
                $credits = '1';
            } elseif (($hours >= 5 && $hours <= 8) || ($hours == 8 && $minutes <= 59)) {
                $credits = '2';
            } elseif (($hours >= 9 && $hours <= 12) || ($hours == 12 && $minutes <= 59)) {
                $credits = '3';
            } else {
                $credits = '4';
            }
                // $levelname= $msmodule->expertiseLevels;

               // Get the mappings for locallib.php.
                $mappingflds = self::get_mapping_fields();
                            // Our configured catagory.
                $coursecategory = $settings['ccategories'];

                if (!empty($settings['coursemappings'])) {
                    $settings['coursemappings'] = unserialize($settings['coursemappings']);
                }

               // Get the hard coded mapped DB fields.
                $fldlist = array();
                $tagfields = array();

                foreach ($mappingflds['coursefields'] as $fld => $val) {
                  
                        $fldlist[] =  $val;
                  
                }

                $ctr++;
                $crserecord = new \stdClass();
                $crserecord->category = $coursecategory;

                foreach ($fldlist as $dbfld) {

                    $mappedfield = array_search($dbfld, $mappingflds['coursefields']);
                    $crserecord->$dbfld = $courseinfo->$mappedfield;
                }

                // To create a course we need a shortname.
                // Force the summary format to HTML
                $crserecord->summaryformat = FORMAT_HTML;
                $crserecord->enablecompletion = 1;
                $crserecord = self::add_new_customfields($crserecord, $apicourseinfo, $settings['coursemappings']);

            if (!isset($courselookup[$idnumber]) ) { 

            
                /* Insert into the DB */

                try {
                    $crserecord->open_costcenterid = $parentcostcenterid;
                    $crserecord->course_type = $parentcostcenterid ? 1 : 0 ;
                    $crserecord->open_departmentid =0;
                    $crserecord->open_subdepartment =0;
                    $crserecord->open_identifiedas=$coursetypeid;
                    $crserecord->format='toggletop';
                    $crserecord->selfenrol=0;
                    $crserecord->newsitems = 0;
                    $crserecord->open_courseprovider= $courseproviderid;
                    $crserecord->open_grade =-1;
                    $crserecord->open_careertrack= "All";
                    $crserecord->open_facilitatorcredits=0;
                    $crserecord->shortname = $courseinfo->contentUuid;
                    $crserecord->open_points = $credits;

                $level = $DB->get_field('local_levels','id',array('name'=>$extlevelname));
                if($level){
                  $crserecord->open_level =   $level;
                 }else {
                  $crserecord->open_level= '';
                }
                   echo "Below is params for course creation";
                  $newcourse = create_course($crserecord);

             //          $existing_method = $DB->get_record('enrol',array('courseid'=> $newcourse->id  ,'enrol' => 'self'));
             // $existing_method->status = 0;
             // $existing_method->customint6 = 1;
           
                   $DB->update_record('enrol', $existing_method);

                     $newshortname= "C_".$newcourse->id;
          
                     $DB->set_field("course", "shortname", $newshortname, array("id" => $newcourse->id));
                    $criterion = new \completion_criteria_role();
                    $data=new \stdClass();
                    $data->criteria_role=array('3'=>3);
                    $data->id=$newcourse->id;
                    $criterion->update_config($data);
                  

                } catch (\moodle_exception $me) {
                    echo "Course is not created";
                    $stats['coursescreatederrors']++;
                        // Log the error - this could cause lots of records to be created.
                    $errormsg = get_string('errorcoursecreate',self::COMPONENT, $me->getMessage());
                    $msmodule->moduleid=0;
                    $msmodule->modulecrud='create';
                    self::log_event('sync_error', array('errormsg' => $errormsg,'module'=>$extcourseinfo,'crud'=>$crud));
                    continue;
                }
                $stats['coursescreated']++;
                    // Add new course to lookup.
                $courselookup[$newcourse->idnumber] = array('courseid' => $newcourse->id, 'count' => 1);

            }
            else {
                 /* Update the DB */

                try {
                    $crserecord->open_costcenterid = $parentcostcenterid;
                    $crserecord->course_type = $parentcostcenterid ? 1 : 0 ;
                    $crserecord->open_departmentid =0;
                    $crserecord->open_subdepartment =0;
                    $crserecord->open_identifiedas=$coursetypeid;
                    $crserecord->format='toggletop';
                    $crserecord->selfenrol=0;
                    $crserecord->newsitems = 0;
                    $crserecord->open_courseprovider= $courseproviderid;
                    $crserecord->open_grade =-1;
                    $crserecord->open_careertrack= "All";
                    $crserecord->open_facilitatorcredits=0;


                $level = $DB->get_field('local_levels','id',array('name'=>$extlevelname));
                if($level){
                  $crserecord->open_level =   $level;
                 }else {
                  $crserecord->open_level= '';
                }
                $crserecord->id= $DB->get_field('course','id',array('idnumber'=>$idnumber));
                $newcourse = update_course($crserecord);

                                     

                } catch (\moodle_exception $me) {
                    echo "Course is not Updated";
                    $stats['coursescreatederrors']++;
                        // Log the error - this could cause lots of records to be created.
                    $errormsg = get_string('errorcourseupdate',self::COMPONENT, $me->getMessage());
                    $msmodule->moduleid=0;
                    $msmodule->modulecrud='create';
                    self::log_event('sync_error', array('errormsg' => $errormsg,'module'=>$extcourseinfo,'crud'=>$crud));
                    continue;
                }
                $stats['coursescreated']++;
                    // Add new course to lookup.
                $courselookup[$newcourse->idnumber] = array('courseid' => $newcourse->id, 'count' => 1);            }

        }
                    
    }

}else {
   

    $ci = $c->get_info();
    $errormsg =  get_string('nocompletionsrecords',self::COMPONENT, self::get_request_status($ci['http_code']));
    self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));

    return false;
}

        /* Log Completion Stats */
        $crud=1;
        self::log_event('process_complete', self::format_stats_report($stats,$crud));
        return true;        // Always - cron expects Exception to recognise errors.


}

public static function verify_userlicence($useremail) {

    global $SITE, $PAGE, $OUTPUT,$DB,$CFG;

    if(function_exists('curl_init')){
        $curl = curl_init();
    } else {
        $curl = false;
        return $curl;
    }

$authcode= "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzZXJ2aWNlX2FjY291bnRfaWQiOiIyYTgzNjVmMS05YzZhLTRiZjEtOGE0Ny01MDQxMzVmZmYzYWUiLCJvcmdhbml6YXRpb25faWQiOiIxODFlMmEyMS03MDNhLTRjZTktYjk5OC04ODI0OWYyODVjOTEiLCJpc3MiOiJhcGkucGVyY2lwaW8uY29tIiwiaWF0IjoxNjM3NTQzNDgyLCJzdWIiOiIyNmEwZTkyYzk0YWJhMTBjYTQ5OGUxZDU2Mjk0NzY4NTQzNzQ0MzdlIiwicG9saWN5LWlkIjoicGVyY2lwaW8tYXBpLXN0YW5kYXJkLXBvbGljeSJ9.B44T-oniRpXCwO7ZoXrWiXNiJ9s51i4fW4WOrwbtaKQj1IGdZZy34JJoVjoqi64Z0TJi_zimE3uUepf0I4IMcvKgCW0_iJocUF6LYQo3ZZWwoaWt51k1zLTYDz1_yrklEvTyBkLo8W4vGQuMm-K1EmP6QFETZIziHVLfx0stZEb9ta5uPDALuRwDqU9vP8DFPrxcaBY-G-N43QdKO3I8K2I0cMw4AOySAuvK8Cvoxd7oy9RVPH8vladKYeHkjsZFjDrXvZbptTO_vZMszBPcRqEaAVT6qoKJuwPkpRWUkYnAkx3mHTKiQ7gIG2cAIgkEgnaqYDA4skNDqNOs0FfiOcCsnvetvGpo21yAKtwYkGfPLyOdiKWu8ldH0rnWkibg7T43DbajTeKdK1cx2v5BhV7BwRvWbFaMlf7dJsCPmdwgIhqx03hNxSuldxnC-7tjQYanj687810CvTSMvHMI6YRaRpFxnUTY8EwHOh6tieZTGWAMXtVxgDjJGk2YC4MLDHLrbM207wK8vvFxycyMxd7YRluJSct50cUHJkC0w0HbF9yfHo2LpcNPKwnsQrQYPBKxYxAV6IJXxZ15QpfZolZ1l8-ObREcM4LBfWSgHxjHmc3oTC_uuQC8lh2gwPKqzjNLxQgNfTPbjD5U-zz9L6eZiXsO_YendtRIN0XXAFM";

  curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.percipio.com/user-management/v1/organizations/181e2a21-703a-4ce9-b998-88249f285c91/users/login-name-or-email/$useremail",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer $authcode"
  ),
));

$response = curl_exec($curl);

curl_close($curl);

    if($response){
        
        $value= json_decode($response);

        if(!$value->isActive){

            return false;
        }

        else {
            foreach($value->customAttributes as $lic){

                 if($lic->name =='License'){

                    if($lic->value)
                        return true;
                     else
                        return false;
                }

            }     

        }

    
    }/* end of first check*/


}

private static function update_usercompletion($userid,$course,$enrolledAt,$completedAt){
  global $DB, $CFG;

   //print_object($course);
   echo "Updating completions for User".$userid." in course ID".$course->id;
 
  $plugin = enrol_get_plugin('self');
  $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
  $roleid=5;

  if (empty($instance)) {
   $enrolid = $plugin->add_instance($course);
   $instance = $DB->get_record('enrol', array('id' => $enrolid));
}

    if($userid && $instance){

        if (!$enrol_manual = enrol_get_plugin('self')) {
        throw new coding_exception('Can not instantiate enrol_manual');
        }
        $sql = "SELECT e.id FROM {enrol} e
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id
                    WHERE e.courseid = :courseid and ue.userid = :userid";
        $userenrol = $DB->get_field_sql($sql,['courseid' => $instance->courseid, 'userid' => $userid]);
        if (!$userenrol) {
            $enrol_manual->enrol_user($instance, $userid, $roleid, 0,0);
        }
    }

 $DB->set_field("user_enrolments", "timecreated", $enrolledAt, array("enrolid" => $instance->id,'userid'=>$userid));
 $DB->set_field("role_assignments", "timemodified", $enrolledAt, array("contextid" => $contextid,'userid'=>$userid));

$ccompletion = new \completion_completion(array('course' => $course->id, 'userid' => $userid));
                              // Mark course as complete and get triggered event.
$ccompletion->mark_complete();
$DB->set_field("course_completions", "timecompleted", $completedAt, array("course" => $course->id,'userid'=>$userid));

echo "Successfully marked completed for above user";

$successmsg = get_string('successcoursecompletion',self::COMPONENT, $userid);
$msmodule->moduleid=$course->id;
$msmodule->modulecrud='markcomplete';
$msmodule->moduletype='course';

self::log_event('sync_success', array('errormsg' => $successmsg,'module'=>$msmodule,'crud'=>$crud));


}

}
