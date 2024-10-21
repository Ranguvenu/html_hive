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
 * @subpackage local_courses
 */

namespace local_courses\timeupload;
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/local/costcenter/lib.php');
use costcenter;
use core_text;
use core_user;
use DateTime;
use html_writer;
use stdClass;
define('MANUAL_ENROLL', 1);
define('LDAP_ENROLL', 2);
define('SAML2', 3);
define('ADwebservice', 4);

class cronfunctionality {
    

  private $data;

  private $errors = array();

  private $mfields = array();

  private $errormessage;

  private $excel_line_number;

  private $insertedcount = 0;

  private $errorcount = 0;
  // starts of constructor function
  function __construct($data = null) {
  $this->data = $data;
  }// end of constructor
  /**BULK UPLOAD FRONTEND METHOD
  * @param $cir [<csv_import_reader Object >]
  * @param $[filecolumns] [<colums fields in csv form>]
  * @param array $[formdata] [<data in the csv>]
  * for inserting record in local_institutionssyncdata.
  **/
  public function main_hrms_frontendform_method($cir,$filecolumns, $formdata) {

      global $DB,$USER, $CFG;
      $inserted = 0;
      $updated = 0;
      $linenum = 1;
      while($line=$cir->next()) { $linenum++;
      $user = new \stdClass();
      foreach ($line as $keynum => $value) {
        if (!isset($filecolumns[$keynum])) {
        // this should not happen
        continue;
      }
      $key = $filecolumns[$keynum];
      $user->$key = trim($value);
      }
      $this->data[]=$user;
      $this->success = array();
      $this->errors = array();
      $this->warnings = array();
      $this->mfields = array();
      $this->wmfields = array();
      $this->excel_line_number = $linenum;
      $mandatory_fields = ['employeeid','coursecode','completion_date'];
      
            foreach($mandatory_fields AS $field){
                $error = $this->mandatory_field_validation($user,$field);
            }

           if(!empty($user->coursecode)) {
           $error = $this->courseid_validation($user);
           }
    
           if(!empty($user->employeeid)) {
           $error = $this->employeeid_validation($user);
           }

           if(!empty($user->employeeid)) {
           $error = $this->enrolled_employeeid_validation($user);
           }
        
            if(empty($error)) {
            $this->write_error_db($user);
           } 

       $data[] = $user;   
      }
            if($this->data) {
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">'.get_string('empfile_syncstatus', 'local_courses').'</h3>';
            
            $upload_info .= '<div class=local_users_sync_success>'.get_string('addedusers_msg', 'local_courses', $this->insertedcount).'</div>';
            $upload_info .= '<div class=local_users_sync_error>'.get_string('errorscount_msg', 'local_courses', $this->errorcount).'</div>
            </div>';

            $button = html_writer::tag('button',get_string('button','local_courses'),array('class'=>'btn btn-primary'));
            $link = html_writer::tag('a',$button,array('href' => $CFG->wwwroot. '/local/courses/courses.php'));
            $upload_info .='<div class="w-full pull-left text-xs-center">'.$link.'</div>';
    
            mtrace( $upload_info);
        
            $sync_data = new \stdClass();
            $sync_data->insertedcount = $this->insertedcount;
            $sync_data->errorscount = $this->errorcount;
            $sync_data->usercreated = $USER->id;
            $sync_data->usermodified = $USER->id;
            $sync_data->timecreated = time();
            $sync_data->timemodified = time();
            $insert_sync_data = $DB->insert_record('local_userssyncdata',$sync_data);         
        } else {
            echo'<div class=local_users_sync_error>'.get_string('filenotavailable','local_courses').'</div>';
        }
        
    }
      

    function write_error_db($excel) { 
      global $DB, $USER;
      
      $syncerrors = new \stdclass();

      $employes = $DB->get_field('user', 'id', array('open_employeeid' => $excel->employeeid));
      
      $coursecode = $DB->get_field('course','id',array('shortname' => $excel->coursecode));

      $users = $DB->get_record_sql("SELECT * FROM {course_completions} WHERE userid = $employes AND course = $coursecode");
      if($users) {
      $syncwarnings = new \stdclass();  
      $syncwarnings->id = $users->id;
      $syncwarnings->userid = $users->userid;
      $syncwarnings->course = $users->course;
      $timecompleted = strtotime($excel->completion_date);
      $syncwarnings->timecompleted = $timecompleted;
      $timeupload = $DB->update_record('course_completions', $syncwarnings);
      $this->insertedcount++;
    } else {

        $userss = new \stdClass();
         $userenrollments = $DB->get_record_sql("SELECT * FROM mdl_course AS c
            JOIN {enrol} AS en ON en.courseid = c.id
            JOIN {user_enrolments} AS ue ON ue.enrolid = en.id
            JOIN {user} u ON u.id = ue.userid
            WHERE c.shortname = '$excel->coursecode' AND u.open_employeeid = '$excel->employeeid'");
        $userss->id = $userenrollments->id;
        $userss->userid = $userenrollments->userid;
        $userss->course = $userenrollments->courseid;
        $timecompleted = strtotime($excel->completion_date);
        $userss->timecompleted = $timecompleted;
       $userenroll = $DB->insert_record('course_completions',$userss);
       $this->insertedcount++;
    }
     if($this->insertedcount) {
             echo '<div class = local_users_sync_success>This data is inserted successfully at line number '.$this->excel_line_number.'</div>';
      }
  }

   function mandatory_field_validation($user,$field) {
      
      if(empty($user->$field)) {
        $strings = new stdClass;
        $strings->field = $field;
        $strings->linenumber = $this->excel_line_number;
        echo '<div class = local_users_sync_error>Missing field of "'. $field .'" uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'please enter a valid fields in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = $field;
        $this->errorcount++;
      
       }
     
     return $this->errors;
  }

  function courseid_validation($excel) {
    global $DB, $USER;
     
     $courseid = $DB->get_record('course', array('shortname'=>$excel->coursecode));
      if(!$courseid->shortname) {

         echo '<div class = local_users_sync_error>This courseid "' . $excel->coursecode . '" is not existing of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
         $this->errors[] = 'coursecode' . $excel->coursecode . ' in excel sheet at line '.$this->excel_line_number.'.';
         $this->mfields[] = 'coursecode';
         $this->errorcount++;

       }

     return $this->errors;
  }


 function employeeid_validation($excel) {
     global $DB, $USER;
    
    $validusers = $DB->get_record('user', array('open_employeeid' => $excel->employeeid));
    
      if (!$validusers->open_employeeid) {

        echo '<div class = local_users_sync_error>This employeeid "' . $excel->employeeid. '" is not existed in course of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'Invalid employeeid' . $excel->employeeid  . ' in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = 'employeeid';
        $this->errorcount++;
      
      }
     return $this->errors;
  
  }

  function enrolled_employeeid_validation($excel) {
     global $DB, $USER;

     $userenrollments = $DB->get_record_sql("SELECT * FROM mdl_course AS c
            JOIN {enrol} AS en ON en.courseid = c.id
            JOIN {user_enrolments} AS ue ON ue.enrolid = en.id
            JOIN {user} u ON u.id = ue.userid
            WHERE c.shortname = '$excel->coursecode' AND u.open_employeeid = '$excel->employeeid'");

          if (!$userenrollments->shortname) {
            echo '<div class = local_users_sync_error>This coursecode "' . $excel->coursecode. '" is not enrolled to course of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
            $this->errors[] = 'Invalid coursecode' . $excel->coursecode  . ' in excel sheet at line '.$this->excel_line_number.'.';
            $this->mfields[] = 'coursecode';
            $this->errorcount++;
          
          }
     return $this->errors;
  
  }

}//end of class
