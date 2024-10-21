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
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*
* @author eabyas <info@eabyas.in>
* @subpackage local_skill
*/
namespace local_skill\upload;
require_once($CFG->dirroot.'/course/lib.php');
use core_text;
use core_user;
use DateTime;
use html_writer;
use stdClass;
define('MANUAL_ENROLL', 1);
define('LDAP_ENROLL', 2);
define('SAML2', 3);
define('ADwebservice', 4);
define('ONLY_ADD', 1);
define('ONLY_UPDATE', 2);
define('ADD_UPDATE', 3);
class syncfunctionality{
   private $data;
  //-------To hold error messages
  private $errors = array();
  //----To hold error field name
  private $mfields = array();
  //-----To hold warning messages----
  private $warnings = array();
  //-----To hold warning field names-----
  private $errormessage;

  private $errorcount=0;

  private $warningscount=0;

  private $updatedcount=0;
  //---It will holds the status(active or inactive) of the user
  private $activestatus;
  //-----It holds the unique username
  private $excel_line_number;

  private $mandatory_fields; 
  // private $mobileno;
  function __construct($data=null){
  $this->data = $data;
  }// end of constructor
  /**BULK UPLOAD FRONTEND METHOD
  * @param $cir [<csv_import_reader Object >]
  * @param $[filecolumns] [<colums fields in csv form>]
  * @param array $[formdata] [<data in the csv>]
  * for inserting record in local_institutionssyncdata.
  **/
  public function main_hrms_frontendform_method($cir,$filecolumns, $formdata){
    // print_object($filecolumns);
    // exit;
      global $DB,$USER, $CFG;
      $inserted = 0;
      $updated = 0;
      $linenum = 1;
      $mandatory_fields = ['course_code','skillcategory','skills'];
      while($line=$cir->next()){ $linenum++;
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
      $this->errors = array();
      $this->warnings = array();
      $this->mfields = array();
      $this->wmfields = array();
      $this->excel_line_number = $linenum;
      
            foreach($mandatory_fields AS $field){
              //mandatory field validation
              // print_object($field);
                $error =$this->mandatory_field_validation($user,$field);
            }
          // validation for username
           if(!empty($user->skillcategory)){
           $error =$this->skillcategory_validation($user);
           // print_object($user);
           }
             // validation for username_status_validation
           if(!empty($user->course_code)){
           $error =$this->course_code_validation($user);
           // print_object($user);
           }
           if(!empty($user->skills)){
           $error = $this->skills_validation($user);
           // print_object($error);
           }
           // write_error_db for insertion
           if(empty($error)) {
            $this->write_error_db($user);
          }



  }

    if($this->data){
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">'.get_string('empfile_syncstatus', 'local_skill').'</h3>';
            
            // $upload_info .= '<div class=local_users_sync_success>'.get_string('addedusers_msg', 'local_users', $this->insertedcount).'</div>';
            $upload_info .= '<div class=local_users_sync_success>'.get_string('updatedusers_msg', 'local_skill', $this->updatedcount).'</div>';
            $upload_info .= '<div class=local_users_sync_error>'.get_string('errorscount_msg', 'local_skill', $this->errorcount).'</div>
            </div>';
            
            // $upload_info .= '<div class=local_users_sync_warning>'.get_string('warningscount_msg', 'local_users', $this->warningscount).'</div>';
            // $upload_info .= '<div class=local_users_sync_warning>'.get_string('superwarnings_msg', 'local_users', $this->updatesupervisor_warningscount).'</div>';

            /*code added by Rizwan for continue button*/
            $button=html_writer::tag('button',get_string('button','local_users'),array('class'=>'btn btn-primary'));
            $link= html_writer::tag('a',$button,array('href'=>$CFG->wwwroot. '/local/courses/courses.php'));
            $upload_info .='<div class="w-full pull-left text-xs-center">'.$link.'</div>';
            /*end of the code*/
            mtrace( $upload_info);
        
            //-------code1 by rizwana starts-------//
            // $sync_data=new \stdClass();
            // $sync_data->newuserscount=$this->insertedcount;
            // $sync_data->updateduserscount=$this->updatedcount;
            // $sync_data->errorscount=$this->errorcount;
            // $sync_data->warningscount=$this->warningscount;
            // $sync_data->supervisorwarningscount=$this->updatesupervisor_warningscount;
            // $sync_data->usercreated=$USER->id;
            // $sync_data->usermodified=$USER->id;
            // $sync_data->timecreated=time();
            // $sync_data->timemodified=time();
            // $insert_sync_data = $DB->insert_record('local_userssyncdata',$sync_data);
            //-------code1 by rizwana ends-------//             
        } else {
            echo'<div class="critera_error">'.get_string('filenotavailable','local_skill').'</div>';
        }
}
//validation for mandatory missing fields
function mandatory_field_validation($user,$field){
// print_object($field);
 if(empty(trim($user->$field))){
      $strings = new stdClass;
      $strings->field = $field;
      $strings->linenumber = $this->excel_line_number;
      echo '<div class=local_users_sync_error>Missing field of "'. $field .'" uploaded excelsheet at line '.$this->excel_line_number.'</div>';
      $this->errors[] = 'please enter a valid fields in excel sheet at line '.$this->excel_line_number.'.';
      $this->mfields[] = $field;
      $this->errorcount++;
  }
   return $this->errors;
}
//write_error_db for insertion
function write_error_db($excel){
// print_object($excel);
  global $DB, $USER;
  $mandatory_fields = ['course_code','skillcategory','skills'];
  $syncerrors = new \stdclass();
  $users="SELECT * FROM {course} WHERE shortname='$excel->course_code'";
  $sql=$DB->get_record_sql($users);
  // print_object($sql);
  $syncerrors->id = $sql->id;
  $syncerrors->course_code =$sql->shortname;
  $skil = explode(',', $excel->skills);
  $skillid=array();
  foreach ($skil as $skillvalue) {
     $skillid[] =$DB->get_field('local_skill', 'id', array('shortname' => $skillvalue));

  }
  $skillids = implode(',', $skillid);
  $syncerrors->open_skill =$skillids;
   $skillcategory =$DB->get_field('local_skill_categories', 'id', array('shortname' => $excel->skillcategory));
  $syncerrors->open_skillcategory = $skillcategory;
  $syncerrors->mandatory_fields = $mandatory_fields;
  // if($sql->id && $excel->date){
  $DB->update_record('course', $syncerrors);
  echo "<div class='alert alert-success'>Skills for course ".$sql->fullname." succcessfully updated in system</div>";
  $this->updatedcount++;
  // }
}

//validation for user status
function skillcategory_validation($excel){
  global $DB, $USER;
  $skillcat= $DB->get_record('local_skill_categories', array('shortname'=>$excel->skillcategory));
  // print_object($skillcat->shortname);
  if($skillcat->shortname != $excel->skillcategory) {
    echo '<div class=local_users_sync_error>This data is not existed "' . $excel->skillcategory . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
    $this->errors[] = 'skillcategory' . $excel->skillcategory . ' in excel sheet at line '.$this->excel_line_number.'.';
    $this->mfields[] = 'skillcategory';
    $this->errorcount++;
  }
   return $this->errors;
} // end of skill_validation method

// course_code  validation
 function course_code_validation($excel){
   // print_object($excel);
     global $DB, $USER;
     $validusers = $DB->get_record('course', array('shortname'=>$excel->course_code));
    if ($excel->course_code != $validusers->shortname){
      echo '<div class=local_users_sync_error>This course_code is not exist "' . $excel->course_code  . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
      $this->errors[] = 'Invalid course_code' . $excel->course_code  . ' in excel sheet at line '.$this->excel_line_number.'.';
      $this->mfields[] = 'username';
      $this->errorcount++;
    }
     return $this->errors;
  }//end of course_code validation
  //skillcategory_validation
  function skills_validation($excel){
    // print_object($excel);
      global $DB, $USER;
      $size=explode(',' ,$excel->skills);
      foreach ($size as $exc) {
        // print_object($exc);
        $shortname = str_replace(' ', '',$exc); 
        $validusers =$DB->get_record('local_skill',array('shortname'=>$shortname));
        if($exc!=$validusers->shortname) {
          echo '<div class=local_users_sync_error>Invalid skills "' . $excel->skills . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
          $this->errors[] = 'Invalid skills' . $excel->skills  . ' in excel sheet at line '.$this->excel_line_number.'.';
          $this->mfields[] = 'skills';
          $this->errorcount++;
        }
        $skillcatid= $DB->get_field('local_skill_categories', 'id', array('shortname' => $excel->skillcategory));;

        $validskill =$DB->get_record_sql("SELECT id FROM `{local_skill}` WHERE category=:category and shortname =:shortname",array('category'=>$skillcatid,'shortname'=>$shortname));
        if(!$validskill){
          echo '<div class=local_users_sync_error>Invalid skills "' . $exc . '" under skillcategory "' . $excel->skillcategory . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
          $this->errors[] = 'Invalid skills' . $exc  . ' under skillcategory "' . $excel->skillcategory . '" in excel sheet at line '.$this->excel_line_number.'.';
          $this->mfields[] = 'skills';
          $this->errorcount++;
        }


     }
     return $this->errors;
   }//end of course_code validation
}//end of class
