<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This coursera is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This coursera is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this coursera.  If not, see <http://www.gnu.org/licenses/>.

/**
 * coursera local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_coursera
 */
namespace local_coursera;

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
    const COMPONENT = 'local_coursera';
    const SYNCERROR = 0;
    const SYNCCOMPLETE = 1;
    const SYNCFAILED = 2;
    const SYNCSUCCESS= 3;

    const SYNCTYPEMODULE = 'course';

    const courseratype = array('module'=>self::SYNCTYPEMODULE);
    const courserastatus = array(self::SYNCERROR=>'Error',self::SYNCCOMPLETE=>'Complete',self::SYNCFAILED=>'Failed',self::SYNCSUCCESS=>'Success');

    /**
     * Returns the plugin's saved settings or the defaults
     *
     * @return array $settings
     */
    public static function crud_plugin_data($data,$status,$statusmessage) {
        global $DB,$USER;
        $courseradata=new \stdClass();
        $courseradata->status=$status;
        $courseradata->statusmessage=$statusmessage;
        $courseradata->moduleid=$data->moduleid;
        $courseradata->modulecrud=$data->modulecrud;
        $files = (array)$data;

        $exclude = array('moduleid'=>'moduleid','modulecrud'=>'modulecrud');
        $filtered = array_diff_key($files, $exclude);

        $courseradata->module = json_encode((object)$filtered);
        $courseradata->moduletype =self::courseratype[$data->type];
        $courseradata->timecreated = time();
        $courseradata->usercreated = $USER->id;
        $DB->insert_record('local_coursera_modules', $courseradata);
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
            'apiurl' => '',
            'clientid' => '',
            'secretkey' => '',
            'refreshtoken' => '',
            'authtoken' => '',
            'orgid' => '',
            'programlist' => '',
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
                'contentId' => 'idnumber',
                'title' => 'fullname',
                'duration' => 'duration',
                'url' => 'open_url',
                'difficultyLevel' => 'open_level'

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


    public static function get_usercompletions($refreshtoken,$programid,$limit,$start){
        global $DB, $CFG;
        $settings = self::get_plugin_settings();

        $todate = date("Y-m-d");
      //  $fromdate=date("Y-m-d",strtotime("-7 days"));
        $fromdate="2021-07-01";
        echo "From Address".$fromdate;
        $from= strtotime($fromdate);

        echo "From Address Decode".$from;
         $hosturl= $settings['apiurl'];
        $username= $settings['accountid'];
        $password= $settings['clientsecret'];
        $authcode= $refreshtoken;

        $token=self::get_token();

        if(function_exists('curl_init')){
            $curl = curl_init();
        } else {
            $curl = false;
            return $curl;
        }

         $host= "https://api.coursera.org/api/businesses.v1/sBAMf4jdQfiQJDDVFZIV5g/enrollmentReports?includeS12n=true&limit=$limit&start=$start&q=byProgramId&programId=$programid&completedAtAfter=$from";

         echo $host;
         print_object($token);

        curl_setopt_array($curl, array(
         CURLOPT_URL =>$host,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array("Authorization: Bearer $token->access_token"),
     ));

        $response = curl_exec($curl);

      
        curl_close($curl);
        $usercompletions= json_decode($response);

        print_object($response);

        echo "Response from API Service";
        foreach($usercompletions->elements as $userinfo){

            echo "Course completion data";
            print_object($userinfo);

            if($userinfo->isCompleted){
                $course=$DB->get_record('course',array('idnumber'=>$userinfo->contentId));
               $userid=$DB->get_field('user','id',array('email'=>$userinfo->externalId));
                    if($course && $userid){
                      $completionsql="SELECT id from {course_completions} where userid={$userid} AND course={$course->id} and timecompleted is not NULL";
                      $completionexist= $DB->get_field_sql($completionsql);

                   if(empty($completionexist)){
                    self::update_usercompletion($userid,$course,$userinfo->enrolledAt,$userinfo->completedAt);
                   }

                 }

                 
            }

        }

        if($usercompletions->paging->next){
            echo "Got Next page".$usercompletions->paging->next;
                $limit=1000;
                $start= $usercompletions->paging->next;
		self::get_usercompletions($refreshtoken,$programid,$limit,$start);

        }






    }

    public static function get_coursedetails($refreshtoken,$programid,$limit,$start){
        global $DB, $CFG;

        $settings = self::get_plugin_settings();
        
        $hosturl= $settings['apiurl'];
        $orgid= $settings['orgid'];
        $token=self::get_token();


         if(function_exists('curl_init')){
            $curl = curl_init();
        } else {
            $curl = false;
            return $curl;
        }
         $host= "https://api.coursera.org/api/businesses.v1/$orgid/contents?limit=$limit&start=$start&q=byProgramIds&programIds=$programid";
        echo $host;

        curl_setopt_array($curl, array(
         CURLOPT_URL => $host,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array("Authorization: Bearer $token->access_token"),
     ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);

    }


    public static function get_token(){
        global $DB, $CFG;

        $settings = self::get_plugin_settings();
        
     //   $hosturl= $settings['apiurl'];
        $clientid= $settings['clientid'];
        $secretkey= $settings['secretkey'];
        $code= $CFG->coursera_token;
        $refreshtoken=$settings['refreshtoken'];

         if(function_exists('curl_init')){
            $curl = curl_init();
        } else {
            $curl = false;
            return $curl;
        }

        $curl = curl_init();

        $data= "grant_type=refresh_token&client_id=$clientid&client_secret=$secretkey&refresh_token=$refreshtoken&code=$code";


        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://accounts.coursera.org/oauth2/v1/token',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: CSRF3-Token=1670059047.QAuL2L90kW6WbiKQ; __204u=1827445190-1668017526722'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);

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
            $event = \local_coursera\event\sync_failed::create($eventdata);
            $event->trigger();
            if(isset($otherdata['module'])){
                plugin::crud_plugin_data($otherdata['module'],plugin::SYNCFAILED,$otherdata['errormsg']);
            }


            break;
            case 'process_error' :

            if($otherdata['crud']=='r'){
                echo $OUTPUT->notification($otherdata['errormsg'], 'error');
            }

            $event = \local_coursera\event\sync_error::create($eventdata);
            $event->trigger();
            if(isset($otherdata['module'])){
                plugin::crud_plugin_data($otherdata['module'],plugin::SYNCERROR,$otherdata['errormsg']);
            }

            break;
            case 'process_complete':

            if($otherdata){

                $event = \local_coursera\event\sync_complete::create($eventdata);
                $event->trigger();

            }

            break;
            case 'sync_success' :

            if($otherdata['crud']=='r'){
                echo $OUTPUT->notification($otherdata['errormsg'], 'success');
            }

            $event = \local_coursera\event\sync_failed::create($eventdata);
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
    
    
private static function update_usercompletion($userins,$course,$enrolledAt,$completedAt){
  global $DB, $CFG;

  $plugin = enrol_get_plugin('self');
  $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
  $roleid = 5; 

  if (empty($instance) && $course) {
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

$enrolledAt=substr($enrolledAt,0,10);
$completedAt=substr($completedAt,0,10);
 $contextid= $DB->get_field('context','id',array('instanceid'=>$course->id,'contextlevel'=>50));
   $DB->set_field("user_enrolments", "timecreated", $enrolledAt, array("enrolid" => $instance->id,'userid'=>$userins));
$DB->set_field("role_assignments", "timemodified", $enrolledAt, array("contextid" => $contextid,'userid'=>$userins));


$ccompletion = new \completion_completion(array('course' => $course->id, 'userid' => $userins));
                              // Mark course as complete and get triggered event.
$ccompletion->mark_complete();

$successmsg = get_string('successcoursecreate',self::COMPONENT, $course->id);
$msmodule=new \stdClass();
$msmodule->moduleid=$newcourse->id;
$msmodule->modulecrud='create';
self::log_event('sync_success', array('errormsg' => $successmsg,'module'=>$course,'crud'=>$crud));
echo "About to complete the course with Date".$completedAt;
$DB->set_field("course_completions", "timecompleted", $completedAt, array("course" => $course->id,'userid'=>$userins));

}


public static function get_programcourses($programid,$limit,$start) {

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

       //  if (empty($settings['enabled']) || empty($settings['ccategories'])) {
       //  //     // Log this and return.
       //   return self::log_event('sync_error', array('errormsg' => get_string('configerror',self::COMPONENT),'crud'=>$crud));
       // }

        $catlog = null;     // The Catalog object.
        $maxcnt = null;     // Only used by testing - max count of eache set of records.
        $testing='';

        // No local JSON file - so we call the Udemy API.
        $c = new \curl(array('cache'=>true));

        if ($settings['catlanguage'] !== 'en-us') {
            $requestparams['locale'] = $settings['catlanguage'];
        }
            // HTTP GET Method
        $token=self::get_token();

        $response= self::get_coursedetails($token->access_token,$programid,$limit,$start);

        echo "Response from course details";

        print_object($response);
        
        if ($response) {      // We have data to process.
            // Get the mappings for locallib.php.

           $existingcourses = get_courses("all", 'c.shortname ASC', 'c.id, c.shortname,c.category,c.idnumber,c.fullname');
            // We build a lookup of the courses - we will add to it if we have to create new courses.
           $courselookup = array();

        foreach ($existingcourses as $xcourse) {
            $courselookup[$xcourse->idnumber] = array('courseid' => $xcourse->id, 'count' => 0);
        }

        $courseproviderid= $DB->get_field('local_course_providers','id',array('shortname'=>"coursera"));
        $coursetypeid= $DB->get_field('local_course_types','id',array('shortname'=>"mooc"));


       if($USER->open_costcenterid){
            $parentcostcenterid = $USER->open_costcenterid;
        }else{
           $parentcostcenterid = $DB->get_field('local_costcenter', 'id', array('parentid'=>0));
       }

       echo "Below Pagination";

  //forloop Start to check course exist and create
     foreach ($response->elements as $msmodule) {

        $ctr++;
        echo "Count of Course Creation".$ctr;

        $extcourseinfo = new \stdClass();
        $idnumber = $msmodule->contentId;

            /* If file is empty get call the curl to get single course information */
            $coursecategory = $settings['ccategories'];
            $extcourseinfo->description = $msmodule->description ? clean_text($msmodule->description ) : '-';
            $extcourseinfo->shortname = $msmodule->contentId ? clean_text($msmodule->contentId) : $idnumber.$ctr ;
            $extcourseinfo->title = $msmodule->name;
            $extcourseinfo->contentId = $msmodule->contentId;
            $extcourseinfo->difficultyLevel = $msmodule->difficultyLevel;

                foreach($msmodule->programs as $programs){
                        $extcourseinfo->url = $programs->contentUrl;

                }

                foreach($msmodule->extraMetadata as $duration){
                        $extcourseinfo->duration = $duration->estimatedLearningTime;
                }

            if (!empty($settings['coursemappings'])) {
                $settings['coursemappings'] = unserialize($settings['coursemappings']);
            }

            // Get the hard coded mapped DB fields.
            $fldlist = array();
            $tagfields = array();

            $mappingflds = self::get_mapping_fields();

            foreach ($mappingflds['coursefields'] as $fld => $val) {

                $fldlist[] =  $val;
            }

            $ctr++;
            $crserecord = new \stdClass();
            $crserecord->category = $coursecategory;
            foreach ($fldlist as $dbfld) {

                $mappedfield = array_search($dbfld, $mappingflds['coursefields']);
                $crserecord->$dbfld = $extcourseinfo->$mappedfield;
            }

                           // To create a course we need a shortname.
            $crserecord->shortname = $msmodule->contentId;
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
                $crserecord->duration = $crserecord->duration;
                $hours = floor($crserecord->duration/3600);
                $minutes = ($crserecord->duration/60)%60;
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
                $crserecord->open_points = $credits;
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

        /* St-1::check for todays completions */

        if (!isset($courselookup[$idnumber])) {  

           /* Update the DB */
            try {

               print_object($crserecord);
            
                $newcourse = create_course($crserecord);

                echo "Course is Created";
                $courseradata=new \stdClass();
                $courseradata->courseid=$newcourse->id;
                $courseradata->programcode=$programid;
                $courseradata->url=$programs->contentUrl;
                $courseradata->timecreated = time();
                $courseradata->usercreated = $USER->id;
                $courseradata->coursecode= $idnumber;

                $DB->insert_record('local_coursera_programs', $courseradata);

                $newshortname= "C_".$newcourse->id;
          
                $DB->set_field("course", "shortname", $newshortname, array("id" => $newcourse->id));
                
                $existing_method = $DB->get_record('enrol',array('courseid'=> $newcourse->id  ,'enrol' => 'self'));
                $existing_method->status = 0;
                $existing_method->customint6 = 1;
                $DB->update_record('enrol', $existing_method);

            
            $criterion = new \completion_criteria_role();
            $data=new \stdClass();
            $data->criteria_role=array('3'=>3);
            $data->id=$newcourse->id;
            $criterion->update_config($data);
          

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
    //update the course
    else {
          try {

        $crserecord->id = $DB->get_field("course", "id", array("idnumber" => $idnumber));
            echo "Course is getting updated";
            $crserecord->shortname= "C_".$crserecord->id;
            print_object($crserecord);
            $newcourse = update_course($crserecord);
       
                $existing_method = $DB->get_record('enrol',array('courseid'=> $crserecord->id  ,'enrol' => 'self'));
                $existing_method->status = 0;
                $existing_method->customint6 = 1;
                $DB->update_record('enrol', $existing_method);


            } catch (\moodle_exception $me) {

            echo "Error in updating the course";
            print_object($me);

            $stats['coursesupdateerrors']++;
                        // Log the error - this could cause lots of records to be created.
            $errormsg = get_string('errorcoursecreate',self::COMPONENT, $me->getMessage());
            $msmodule->moduleid=0;
            $msmodule->modulecrud='Update';
            self::log_event('sync_error', array('errormsg' => $errormsg,'module'=>$extcourseinfo,'crud'=>$crud));
            continue;
            }


    }

          
}

$nextpage= $response->paging->next;

echo "Check the courses with Pagination".$nextpage;
echo "Naxt Pagination".$nextpage;
echo "Check the courses with Pagination";
            echo "Naxt Pagination".$cpaging->next;;
            
             if($nextpage){
                echo "Going to start the New pagination";
                $limit=1000;
                $start= $nextpage;
                $courses= self::get_programcourses($programid,$limit,$start);   

             }
 
}else {

   $ci = $c->get_info();
   $errormsg =  get_string('nocompletionsrecords',self::COMPONENT, self::get_request_status($ci['http_code']));
   self::log_event('sync_error', array('errormsg' => $errormsg,'crud'=>$crud));

   return false;
 }



       /* Log Completion Stats */
       self::log_event('process_complete', self::format_stats_report($stats,$crud));

        return true;        // Always - cron expects Exception to recognise errors.

    }


 public static function verify_userlicence($useremail,$programcode) {

    global $SITE, $PAGE, $OUTPUT,$DB,$CFG;
    
    if(function_exists('curl_init')){
        $curl = curl_init();
    } else {
        $curl = false;
        return $curl;
    }
    $authtoken=plugin::get_token();

   $settings = self::get_plugin_settings();
        
  // $access_token=$settings['authtoken'];
  $accesstoken= $authtoken->access_token;
    if($accesstoken) {


   $host= "https://api.coursera.org/api/businesses.v1/sBAMf4jdQfiQJDDVFZIV5g/programs/$programcode/memberships?limit=1000&start=0";

         curl_setopt_array($curl, array(
         CURLOPT_URL => "$host",
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array("Authorization: Bearer $accesstoken"),
     ));

    $response = curl_exec($curl);

    curl_close($curl);
    $value= json_decode($response);

    }

    if($value){
        
           foreach($value->elements as $lic){

                           
                   $emailCheck = trim(strtolower($useremail)); 
                   $emailConfirm = trim(strtolower($lic->externalId));
                     
                    if ($emailCheck == $emailConfirm) {
                        $userexist=1;
                        return true;
                    }
                    
                                       
           } 
           if(empty($userexist)){
             return false;
           }


    }/* end of first check*/
 }

 public static function custom_verify_userlicence($useremail,$programcode) {

    global $SITE, $PAGE, $OUTPUT,$DB,$CFG;
    
  
    if(function_exists('curl_init')){
        $curl = curl_init();
    } else {
        $curl = false;
        return $curl;
    }
    $authtoken=plugin::get_token();

  // $settings = self::get_plugin_settings();
        
  // $access_token=$settings['authtoken'];
  $accesstoken= $authtoken->access_token;
    if($accesstoken) {


   $host= "https://api.coursera.org/api/businesses.v1/sBAMf4jdQfiQJDDVFZIV5g/programs/$programcode/memberships?limit=1000&start=0";


         curl_setopt_array($curl, array(
         CURLOPT_URL => "$host",
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array("Authorization: Bearer $accesstoken"),
     ));

    $response = curl_exec($curl);

    curl_close($curl);
    $value= json_decode($response);

    }


    if($value){
        
           foreach($value->elements as $lic){

                           
                   $emailCheck = trim(strtolower($useremail)); 
                   $emailConfirm = trim(strtolower($lic->externalId));
                     
                    if ($emailCheck == $emailConfirm) {
                        $userexist=1;
                        return true;
                    }
                    
                                       
           } 
           if(empty($userexist)){
             return false;
           }


    }/* end of first check*/
 }

}
