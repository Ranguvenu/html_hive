<?php

namespace local_courses\action;
defined('MOODLE_INTERNAL') or die;
use enrol_get_plugin;
use stdClass;

class senddisprzdata{

    /*
     * @method local_postcourse
     * @param $courseinfo 
     * @output data will be insert into mdl_local_logs table
     */    
    function local_postcoursestatus($event,$userid,$status){
    
        global $DB, $USER, $CFG, $SESSION;

          
            $courseid= $event->courseid;
            $moduleid= $event->contextinstanceid;
        
           $scormid=$event->objectid;
           $attemptid = $event->other['attemptid'];

  
           $course=$DB->get_record('course',array('id'=>$courseid));
           $sql= "SELECT gg.userid,gg.finalgrade,gg.timemodified from {course_modules} cm join {grade_items} gi on gi.iteminstance=cm.instance AND cm.id={$moduleid} AND cm.course={$courseid} 
                   JOIN {grade_grades} gg on gg.itemid=gi.id AND gg.userid={$userid} where gi.courseid={$courseid}";
        
          $grades=$DB->get_record_sql($sql);
          $userscore=$grades->finalgrade;

          $status= "Completed";
          $useremail= $DB->get_field('user','email',array('id'=>$userid));

            $course=$course->shortname;
            $host=get_config('local_courses', 'disperzhost');
            $partnerid=get_config('local_courses', 'partnerid');
            $token =get_config('local_courses', 'token');
            $time= gmdate('Y-m-d\TH:i:s\Z');

            $courseinfo =new stdClass();
            $courseinfo->partnerId=39;
            $courseinfo->userName = $useremail;
            $courseinfo->partnerCourseid="$course";
            $courseinfo->completionPercentage=100;
            $courseinfo->enrolledOn="$time";
            $courseinfo->completedOn = "$time";
            $courseinfo->timeSpent=0;
            $courseinfo=json_encode($courseinfo);
      
           $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $host,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_SSL_VERIFYPEER => false,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => $courseinfo,
              CURLOPT_HTTPHEADER => array(
              
                "Content-Type: application/json",
                "Accept: application/json",
                "Learntron-Api-Token: $token"
              ),
            ));

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
           
           if ($httpcode==200) {

             $cologs = new stdClass();
                $cologs->courseid=$courseid;
                $cologs->message= "Success";
                $cologs->response= $httpcode;
                $cologs->userid= $userid;
                $cologs->event= "Disperz grade Sent";
                $cologs->data= $courseinfo;
                $cologs->timecreated=time();
                
               $DB->insert_record('local_disprzlogs', $cologs, false);
         
            } else {
                                     
                $cologs = new stdClass();
                $cologs->courseid=$courseid;
                $cologs->message= "Fail";
                $cologs->response= $httpcode;
                $cologs->userid= $userid;
                $cologs->data= $courseinfo;
                $cologs->event= "Disperz grade Sent";
                $cologs->timecreated=time();
         
               $DB->insert_record('local_disprzlogs', $cologs, false);
            }
             
	  

          
     
   }


}
?>