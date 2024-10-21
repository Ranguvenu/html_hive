<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This udemysync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This udemysync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this udemysync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * udemysync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_udemysync
 */
namespace local_udemysync;

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


class plugin {

    /** @var string */
    const COMPONENT = 'local_udemysync';
    const SYNCERROR = 0;
    const SYNCCOMPLETE = 1;
    const SYNCFAILED = 2;
    const SYNCSUCCESS= 3;

    const SYNCTYPEMODULE = 'course';

    const udemysynctype = array('module'=>self::SYNCTYPEMODULE);
    const udemysyncstatus = array(self::SYNCERROR=>'Error',self::SYNCCOMPLETE=>'Complete',self::SYNCFAILED=>'Failed',self::SYNCSUCCESS=>'Success');

    /**
     * Returns the plugin's saved settings or the defaults
     *
     * @return array $settings
     */
    public static function crud_plugin_data($data,$status,$statusmessage) {
        global $DB,$USER;
        $udemysyncdata=new \stdClass();
        $udemysyncdata->status=$status;
        $udemysyncdata->statusmessage=$statusmessage;
        $udemysyncdata->moduleid=$data->moduleid;
        $udemysyncdata->modulecrud=$data->modulecrud;
        $files = (array)$data;

        $exclude = array('moduleid'=>'moduleid','modulecrud'=>'modulecrud');
        $filtered = array_diff_key($files, $exclude);

        $udemysyncdata->module = json_encode((object)$filtered);
        $udemysyncdata->moduletype =self::udemysynctype[$data->type];
        $udemysyncdata->timecreated = time();
        $udemysyncdata->usercreated = $USER->id;
        $DB->insert_record('local_udemysync_modules', $udemysyncdata);
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
            'apiurl' => 'https://fractal.udemy.com/api-2.0/organizations/',
            'accountid' => '',
            'clientsecret' => '',
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
                'id' => 'idnumber',
                'title' => 'fullname',
                'estimated_content_length' => 'duration',
                'url' => 'open_url',
                'level' => 'open_level'

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




    public static function crud_udemysync($testing = null,$page) {

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
      //  $testing='';
        
         if (isset($testing) && $testing > -1) {
             
              $maxcnt = (int) $testing;
              $jsonfile = __DIR__ . '/udemyhistory.json';
              echo $jsonfile;
            if (file_exists($jsonfile)) {
                $response = file_get_contents($jsonfile);
                echo "File exist and below respone";
                print_object($response);
                  if ($response === null) {
                    $errormsg = get_string('errorjsonparse',self::COMPONENT, self::get_last_json_errormsg());
                    return self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
                  }
                  if ($maxcnt == 0) {     // We want all the records.
                    $maxcnt = count($response->modules) + 1;
                  }
                } // Else we are using curl to get data.
            }else {
                  $response= self::get_usercompletions($page);

            }

        // No local JSON file - so we call the Udemy API.
        $c = new \curl(array('cache'=>true));

        if ($settings['catlanguage'] !== 'en-us') {
            $requestparams['locale'] = $settings['catlanguage'];
        }
            // HTTP GET Method

        print_object($response);
         
        if ($response) {      // We have data to process.
            // Get the mappings for locallib.php.

        $existingcourses = self::get_courselist();
            // We build a lookup of the courses - we will add to it if we have to create new courses.
           $courselookup = array();

        echo "Step-0: Existing Course";

        foreach ($existingcourses as $xcourse) {
        $courselookup[$xcourse->idnumber] = array('courseid' => $xcourse->id, 'count' => 0);
        }

        $courseproviderid= $DB->get_field('local_course_providers','id',array('shortname'=>"udemy"));
        $coursetypeid= $DB->get_field('local_course_types','id',array('shortname'=>"mooc"));


       if($USER->open_costcenterid){
            $parentcostcenterid = $USER->open_costcenterid;
        }else{
        $parentcostcenterid = $DB->get_field('local_costcenter', 'id', array('parentid'=>0));
       }

        echo "Step-1: Got the Response from Course details";
            // Get the existing courses.
        $i=0;
       $response= json_decode($response);

       foreach ($response->results as $msmodule) {

      //  $ctr++;
       
        $idnumber = $msmodule->course_id;
     $userid= $DB->get_field('user','id',array('email'=>$msmodule->user_email));

        /* St-1::check for todays completions */

    if (!isset($courselookup[$idnumber]) && isset($msmodule->course_first_completion_date) && !empty($userid)) {  


 $crserecord=  self::get_coursedetails($msmodule->course_id,$parentcostcenterid,$coursetypeid,$courseproviderid);
 
 
         echo "Course ID ".$msmodule->course_id."not exisit in LMS";
         print_object($crserecord);
         /*Get user information from API and Start creating course */
     if($crserecord->shortname) {
                        
            /* Update the DB */

            try {
                echo "Step-2::: About to create a new course";
              
                //Just commented for testing
                $newcourse = create_course($crserecord);
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

                $userins= $DB->get_field('user','id',array('email'=>$msmodule->user_email));

            if(!empty($userins) && !empty($newcourse)){
             $completiondate= strtotime($msmodule->course_first_completion_date);
             $enrolleddate= strtotime($msmodule->course_enroll_date);
            self::update_usercompletion($userins,$newcourse,$enrolleddate,$completiondate);
              }
              
             $stats['coursescreated']++;
                    // Add new course to lookup.
            $courselookup[$newcourse->idnumber] = array('courseid' => $newcourse->id, 'count' => 1);

    $successmsg = get_string('successcoursecreate',self::COMPONENT, $newcourse->id);
            $msmodule->moduleid=$newcourse->id;
            $msmodule->modulecrud='coursecreated';
            $msmodule->moduletype='course';

            self::log_event('sync_success', array('errormsg' => $successmsg,'module'=>$msmodule,'crud'=>$crud));
            

        } catch (\moodle_exception $me) {
            
            $stats['coursescreatederrors']++;
                        // Log the error - this could cause lots of records to be created.
            $errormsg = get_string('errorcoursecreate',self::COMPONENT, $me->getMessage());
            $msmodule->moduleid=0;
            $msmodule->modulecrud='errorcoursecreate';
            self::log_event('sync_error', array('errormsg' => $errormsg,'module'=>$extcourseinfo,'crud'=>$crud));
            continue;
        }

    }
       

   }  else if (isset($courselookup[$idnumber]) && isset($msmodule->course_first_completion_date) && !empty($userid)) { 

    echo "BBB".$i++;
        
    $crserecord=  self::get_coursedetails($msmodule->course_id,$parentcostcenterid,$coursetypeid,$courseproviderid);
   /*Get user information from API and Start creating course */
     if($crserecord->shortname) {

     $cid = $DB->get_field("course", "id", array("idnumber" => $idnumber));
     $crserecord->id= $cid;
     
     $crserecord->shortname= "C_".$cid;

     update_course($crserecord); 
        
     echo "Course Exist on HIVE about to add the completions";
    
     $course=$DB->get_record('course',array('idnumber'=>$msmodule->course_id));

     $completiondate= strtotime($msmodule->course_first_completion_date);
     $enrolleddate= strtotime($msmodule->course_enroll_date);

      //commented for testing
       if(!empty($course) && !empty($userid) ) {
              $completionsql="SELECT id from {course_completions} where userid={$userid} AND course={$course->id} and timecompleted is not NULL";
              $completionexist= $DB->get_field_sql($completionsql);
              echo $completionsql;
              print_object($completionexist);
              
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

            $successmsg = get_string('successcourseupdate',self::COMPONENT, $userid);
            $msmodule->moduleid=$course->id;
            $msmodule->modulecrud='courseupdated';
            $msmodule->moduletype='course';

            self::log_event('sync_success', array('errormsg' => $successmsg,'module'=>$msmodule,'crud'=>$crud));

       
       }   

    }              
}

}else {
    echo "Step-5:: No completions for today";

   $ci = $c->get_info();
   $errormsg =  get_string('nocompletionsrecords',self::COMPONENT, self::get_request_status($ci['http_code']));
   self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));

   return false;
 }
   
    /* Log Completion Stats */

        if (!empty($response->next)) {
            $page++;
            echo "Taking the second page of udemy";
            $response = self::crud_udemysync($testing = null,$page);
         }
    echo "Step-6::End of the SYNC";
       /* Log Completion Stats */
       self::log_event('process_complete', self::format_stats_report($stats,$crud));

        return true;        // Always - cron expects Exception to recognise errors.

    }

 private static function get_usercompletions($page){
        global $DB, $CFG;
        $settings = self::get_plugin_settings();

        $todate = date("Y-m-d");
       $fromdate=date("Y-m-d",strtotime("-7 days"));
        //$fromdate="2022-10-07";
        $hosturl= $settings['apiurl'];
        $username= $settings['accountid'];
        $password= $settings['clientsecret'];
        $authcode= base64_encode($username.':'.$password);

        if(function_exists('curl_init')){
            $curl = curl_init();
        } else {
            $curl = false;
            return $curl;
        }

        curl_setopt_array($curl, array(
         CURLOPT_URL => "$hosturl/analytics/user-course-activity/?fields%5Bcoursecourse_completion_date%5D=@all&page=$page&page_size=100&from_date=$fromdate&to_date=$todate",
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array("Authorization: Basic $authcode"),
     ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);

    }

    private static function get_coursedetails($coursecode,$parentcostcenterid,$coursetypeid,$courseproviderid){
        global $DB, $CFG;

        $settings = self::get_plugin_settings();
        $mappingflds = self::get_mapping_fields();
        
        $hosturl= $settings['apiurl'];
        $username= $settings['accountid'];
        $password= $settings['clientsecret'];
        $authcode= base64_encode($username.':'.$password);

         if(function_exists('curl_init')){
            $curl = curl_init();
        } else {
            $curl = false;
            return $curl;
        }

        curl_setopt_array($curl, array(
         CURLOPT_URL => "$hosturl/courses/list/$coursecode",
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array("Authorization: Basic $authcode"),
     ));

        $response = curl_exec($curl);

        curl_close($curl);
        $extcourseinfo= json_decode($response);
        echo "Step-888::The course".$coursecode;
        print_object($extcourseinfo);
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

          //  $ctr++;
            $crserecord = new \stdClass();
            $crserecord->category = $coursecategory;

            foreach ($fldlist as $dbfld) {

                $mappedfield = array_search($dbfld, $mappingflds['coursefields']);
                $crserecord->$dbfld = $extcourseinfo->$mappedfield;
            }

            // To create a course we need a shortname.
            $crserecord->shortname = $extcourseinfo->id;
            // Force the summary format to HTML
            $crserecord->summaryformat = FORMAT_HTML;
            $crserecord->enablecompletion = 1;

            //Now to the custom fields.
            $crserecord = self::add_new_customfields($crserecord, $extcourseinfo, $settings['coursemappings']);
            $crserecord->open_url = $extcourseinfo->url;
            $crserecord->open_costcenterid = $parentcostcenterid;
            $crserecord->course_type = $parentcostcenterid ? 1 : 0 ;
            $crserecord->open_departmentid =0;
            $crserecord->open_subdepartment =0;
            $crserecord->open_identifiedas=$coursetypeid;
            $crserecord->format='toggletop';
            $crserecord->selfenrol=1;
            $crserecord->newsitems = 0;
            $crserecord->duration = $crserecord->duration * 60;
            $crserecord->open_grade =-1;
            $crserecord->open_ouname=-1;
            $crserecord->open_careertrack= "All";
            $crserecord->open_facilitatorcredits=0;
            $crserecord->open_courseprovider= $courseproviderid;

            $level = $DB->get_field('local_levels','id',array('name'=>$crserecord->open_level));
            if($level){
                  $crserecord->open_level =   $level;
            }else {
                $crserecord->open_level= '';
            }

          return $crserecord;

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
            $event = \local_udemysync\event\sync_failed::create($eventdata);
            $event->trigger();
            if(isset($otherdata['module'])){
                plugin::crud_plugin_data($otherdata['module'],plugin::SYNCFAILED,$otherdata['errormsg']);
            }


            break;
            case 'process_error' :

            if($otherdata['crud']=='r'){
                echo $OUTPUT->notification($otherdata['errormsg'], 'error');
            }

            $event = \local_udemysync\event\sync_error::create($eventdata);
            $event->trigger();
            if(isset($otherdata['module'])){
                plugin::crud_plugin_data($otherdata['module'],plugin::SYNCERROR,$otherdata['errormsg']);
            }

            break;
            case 'process_complete':

            if($otherdata){

                $event = \local_udemysync\event\sync_complete::create($eventdata);
                $event->trigger();

            }

            break;
            case 'sync_success' :

            if($otherdata['crud']=='r'){
                echo $OUTPUT->notification($otherdata['errormsg'], 'success');
            }

            $event = \local_udemysync\event\sync_failed::create($eventdata);
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
    
// public static function markcomplete_udemycourse($testing = null,$crud=null) {

//         global $SITE, $PAGE, $OUTPUT,$DB,$CFG;
//         /* statistics variable */
//         $stats = array();
//         $stats['totalmodules'] = 0;
//         $stats['totallpaths'] = 0;
//         $stats['coursescreated'] = 0;
//         $stats['coursescreatederrors'] = 0;
//         $stats['coursesupdated'] = 0;
//         $stats['coursesupdatederrors'] = 0;

//         $settings = self::get_plugin_settings();
        
//         if (empty($settings['enabled']) || empty($settings['ccategories'])) {
//              // Log this and return.
//            return self::log_event('sync_error', array('errormsg' => get_string('configerror',self::COMPONENT),'crud'=>$crud));
//        }

//         $catlog = null;     // The Catalog object.
//         $maxcnt = null;     // Only used by testing - max count of eache set of records.
//         $testing='';

//         if (empty($catlog)) {

//            // HTTP GET Method
//             $response= self::get_usercompletions();
//             if($response){

//                 if (($catlog = json_decode($response)) === null) {
//                     $errormsg = get_string('errorjsonparse',self::COMPONENT,  self::get_last_json_errormsg());
//                     return self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
//                 }
//             } else {
//                 $ci = $c->get_info();
//                 $errormsg =  get_string('errorapicall',self::COMPONENT, self::get_request_status($ci['http_code']));
//                 self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));
//                 if ( (int) $ci['http_code'] >= 500) {       // Exception so cron will try again.
//                     print_error('errorapicall',self::COMPONENT, null, self::get_request_status($ci['http_code']));
//                 }
//                 return false;
//             }
//         }

//         if ($catlog) {      // We have data to process.
//             // Get the mappings for locallib.php.

//            foreach ($catlog->results as $msmodule) {

//             $ctr++;
//             /* St-1::check for todays completions */

//             if (isset($msmodule->course_completion_date)) {  


//                 try {

//                  $courseid= $DB->get_field('course','id',array('idnumber'=>$msmodule->course_id));   
//                  $userins= $DB->get_field('user','id',array('email'=>$msmodule->user_email));

//                  $ccompletion = new \completion_completion(array('course' => $courseid, 'userid' => $userins));
//                     // Mark course as complete and get triggered event.
//                  $ccompletion->mark_complete();

//                  $successmsg = get_string('successcoursecreate',self::COMPONENT, $extcourseinfo->id);
//                  self::log_event('markcompletionsync_success', array('errormsg' => $successmsg,'module'=>$extcourseinfo,'crud'=>$crud));
//                  $stats['coursecompletionmarkerrors']++;

//              } catch (\moodle_exception $me) {
//                     // Log the error - this could cause lots of records to be created.
//                 $errormsg = get_string('errorcoursecompletionmarking',self::COMPONENT, $me->getMessage());

//                 self::log_event('sync_error', array('errormsg' => $errormsg,'module'=>$extcourseinfo,'crud'=>$crud));
//                 continue;
//             }
//             $stats['markcomplete']++;
//                 // Add new course to lookup.

//         }                
//     }

// }

// /* Log Completion Stats */
// self::log_event('process_complete', self::format_stats_report($stats,$crud));
// return true;        

// }

private static function update_usercompletion($userins,$course,$enrolledAt,$completedAt){
  global $DB, $CFG;

  $plugin = enrol_get_plugin('self');
  $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
  
  echo "Below one is for Instance";
  
  $roleid=5;
  if (empty($instance)) {
     $enrolid = $plugin->add_instance($course);
     $instance = $DB->get_record('enrol', array('id' => $enrolid));
 }

    if($userins && $instance){

        if (!$enrol_manual = enrol_get_plugin('self')) {
            throw new coding_exception('Can not instantiate enrol_manual');
        }
        $sql = "SELECT e.id FROM {enrol} e
        JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE e.courseid = :courseid and ue.userid = :userid";
        $userenrol = $DB->get_field_sql($sql,['courseid' => $instance->courseid, 'userid' => $userins]);
        if (!$userenrol) {
            $enrol_manual->enrol_user($instance, $userins, $roleid, 0,0);
        }
    }

 $DB->set_field("user_enrolments", "timecreated", $enrolledAt, array("enrolid" => $instance->id,'userid'=>$userins));
 $DB->set_field("role_assignments", "timemodified", $enrolledAt, array("contextid" => $contextid,'userid'=>$userins));

$ccompletion = new \completion_completion(array('course' => $course->id, 'userid' => $userins));
                              // Mark course as complete and get triggered event.
$ccompletion->mark_complete();

$DB->set_field("course_completions", "timecompleted", $completedAt, array("course" => $course->id,'userid'=>$userins));

$successmsg = get_string('successcoursecompletion',self::COMPONENT, $userins);
$msmodule->moduleid=$course->id;
$msmodule->modulecrud='markcomplete';
$msmodule->moduletype='course';

self::log_event('sync_success', array('errormsg' => $successmsg,'module'=>$msmodule,'crud'=>$crud));


}

private static function get_courselist() {

    global $USER, $CFG, $DB;

    $sortstatement = "ORDER BY c.shortname";
    $visiblecourses = array();
    $sql = "SELECT c.id,c.idnumber,c.shortname,c.fullname,c.category
              FROM {course} c JOIN {local_course_providers} cp on cp.id=c.open_courseprovider AND cp.shortname='udemy'
            where c.idnumber is not NULL";

    // pull out all course matching the cat
    if ($courses = $DB->get_records_sql($sql)) {

        // loop throught them
        foreach ($courses as $course) {
            //context_helper::preload_from_record($course);
           // if (core_course_category::can_view_course_info($course)) {
                $visiblecourses [$course->id] = $course;
           // }
        }
    }
    return $visiblecourses;
}




}
