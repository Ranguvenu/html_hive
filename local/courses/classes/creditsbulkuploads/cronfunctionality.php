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

namespace local_courses\creditsbulkuploads;
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
define('credits', 1);
define('duration', 2);
define('levels', 3);
define('credits_duration_levels', 4);

class cronfunctionality {
    

  private $data;

  private $errors = array();

  private $mfields = array();

  private $warnings = array();

  private $errormessage;

  private $activestatus;

  private $excel_line_number;

  private $insertedcount = 0;

  private $errorcount = 0;

  function __construct($data = null) {
   $this->data = $data;
  }// end of constructor
 
  public function main_hrms_frontendform_method($cir, $filecolumns, $formdata) {

      global $DB,$USER, $CFG;
      $inserted = 0;
      $updated = 0;
      $linenum = 1;
      while($line=$cir->next()) { 
        $linenum++;
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

    // print_object($formdata->optiom);
        $mandatory_field = ['coursecode','credits'];

            foreach($mandatory_field AS $field) { 
                $error = $this->mandatory_field_validation($user, $field, $formdata->option);
              }

        $mandatoryy_fields = ['coursecode','duration_in_hours'];

            foreach($mandatoryy_fields AS $field) {
                $error = $this->mandatoryy_fields_validation($user, $field, $formdata->option);
              }

        $mandatory_levels_fields = ['coursecode','levelcode'];

            foreach($mandatory_levels_fields AS $field) {
                $error = $this->mandatory_levels_fields_validation($user, $field, $formdata->option);
                // print_object($formdata->option);
              }

        $mandatoryyy_fields = ['coursecode','credits','duration_in_hours','levelcode'];

            foreach($mandatoryyy_fields AS $field) {
                $error = $this->mandatoryyy_fields_validation($user, $field, $formdata->option);
              }

           if(!empty($user->coursecode)) {
           $error = $this->courseid_validation($user);
           }
          
           if(!empty($user->credits)) {
           $error =$this->credits_validation($user, $formdata->option);
           }

           if(!empty($user->levelcode)) {
           $error =$this->levels_validation($user, $formdata->option);
           }

           // if(!empty($user->duration)) {
           // $error =$this->duration_validation($user, $formdata->option);
           // }
            if(!empty($user->duration_in_hours)) {
              $error =$this->duration_in_hours_validation($user, $formdata->option);
           }
           if(!empty($user->duration_in_minutes)) {
              $error =$this->duration_in_minutes_validation($user, $formdata->option);
            }


           if(empty($error)) {
            $this->write_error_db($user,$formdata->option);

           }

       $data[]=$user;   
        }
            if($this->data) {
            $upload_info =  '<div class = "critera_error1"><h3 style = "text-decoration: underline;">'.get_string('empfile_syncstatuss', 'local_courses').'</h3>';
            
            $upload_info .= '<div class = local_users_sync_success>'.get_string('addedcredits_msg', 'local_courses', $this->insertedcount).'</div>';
            $upload_info .= '<div class = local_users_sync_error>'.get_string('errorscount_msgs', 'local_courses', $this->errorcount).'</div>
            </div>';


            $button = html_writer::tag('button',get_string('button','local_courses'),array('class' => 'btn btn-primary'));
            $link = html_writer::tag('a',$button,array('href' => $CFG->wwwroot. '/local/courses/courses.php'));
            $upload_info .= '<div class="w-full pull-left text-xs-center">'.$link.'</div>';
            mtrace( $upload_info);
        
            $sync_data = new \stdClass();
            $sync_data->insertedcount = $this->insertedcount;
            $sync_data->errorscount = $this->errorcount;
            $sync_data->usercreated = $USER->id;
            $sync_data->usermodified = $USER->id;
            $sync_data->timecreated = time();
            $sync_data->timemodified = time();
            $insert_sync_data = $DB->insert_record('local_userssyncdata',$sync_data);
            //-------code1 by rizwana ends-------//             
        } else {
            echo'<div class = local_users_sync_error>'.get_string('filenotavailable','local_courses').'</div>';
        }
        
    } 
      

    function write_error_db($excel, $option) {

        global $DB, $USER;
        if($excel->credits && $option == 1 || $excel->credits == 0) {
        $coursecode = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname ='$excel->coursecode'");
          $credit = new \stdClass();
        $credit->id = $coursecode->id;
        $credit->shortname = $coursecode->shortname;
        $credit->open_points = $excel->credits;
          
          $credituploads = $DB->update_record('course', $credit);

      } elseif($excel->duration_in_hours && $option == 2) {

      $coursecode = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname ='$excel->coursecode'");
        
        $durations = new \stdClass();
        $durations->id = $coursecode->id;
        $durations->shortname = $coursecode->shortname;
        // $duration = $excel->duration;
        // sscanf($duration, "%d:%d", $hours, $minutes);
        // $hours = $hours * 3600 ;
        // $minutes = $minutes * 60;
        // $durations->duration = $hours + $minutes; 

        $duration_in_hours =  $excel->duration_in_hours;
        $duration_in_minutes =  $excel->duration_in_minutes;
        $hours = $duration_in_hours * 3600 ;
        $minutes = $duration_in_minutes * 60;
        $durations->duration = $hours + $minutes; 
       
        $durationss = $DB->update_record('course', $durations);
  
      } elseif($option == 3) {

        $levels = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname = '$excel->coursecode'");
        $ids = $DB->get_record_sql("SELECT * FROM {local_levels} WHERE name = '$excel->levelcode'");
        
         $level = new \stdclass();

            $level->id = $levels->id;
            $level->shortname = $levels->shortname;
            $level->open_level  = $ids->id;
            $course_levels = $DB->update_record('course', $level);

      } elseif (($option == 4)) {
        
      $coursecode = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname ='$excel->coursecode'");
      $ids = $DB->get_record_sql("SELECT * FROM {local_levels} WHERE name = '$excel->levelcode'");
      $syncerrors = new \stdclass();
        $syncerrors->id = $coursecode->id;
        $syncerrors->shortname = $coursecode->shortname;
        $syncerrors->open_points = $excel->credits;
        // $durations = $excel->duration;
        // sscanf($durations, "%d:%d", $hours, $minutes);
        // $hours = $hours * 3600 ;
        // $minutes = $minutes * 60;
        // $syncerrors->duration = $hours + $minutes;
        $duration_in_hours =  $excel->duration_in_hours;
        $duration_in_minutes =  $excel->duration_in_minutes;
        $hours = $duration_in_hours * 3600 ;
        $minutes = $duration_in_minutes * 60;
        $syncerrors->duration = $hours + $minutes; 

        $syncerrors->open_level  = $ids->id;
          // print_object($syncerrors);
      
         if($syncerrors) {
          $syncerrorss = $DB->update_record('course', $syncerrors);
        }
    }
      $this->insertedcount++;
      if($this->insertedcount) {
             echo '<div class=local_users_sync_success>This data is inserted successfully at line number '.$this->excel_line_number.'</div>';
        }
     }

   function mandatory_field_validation($user,$field,$option) {
      if($option == 1) {
      if(!isset($user->$field) || $user->$field === '') {
        $strings = new stdClass;
        $strings->field = $field;
        $strings->linenumber = $this->excel_line_number;
        echo '<div class=local_users_sync_error>Missing field of "'. $field .'" uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'please enter a valid fields in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = $field;
        $this->errorcount++;
      
       }  
     }
     return $this->errors;
  }

  function mandatoryy_fields_validation($user,$field,$option) {
      
      if($option == 2) {
       if(empty($user->$field)) {
        $strings = new stdClass;
        $strings->field = $field;
        $strings->linenumber = $this->excel_line_number;
        echo '<div class=local_users_sync_error>Missing field of "'. $field .'" uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'please enter a valid fields in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = $field;
        $this->errorcount++;
      
       }  
     }
     return $this->errors;
  }

   function mandatory_levels_fields_validation($user,$field,$option) {
      
      if($option == 3) {
         // print_object($user);
       if(empty($user->$field)) {
        $strings = new stdClass;
        $strings->field = $field;
        $strings->linenumber = $this->excel_line_number;
        echo '<div class=local_users_sync_error>Missing field of "'. $field .'" uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'please enter a valid fields in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = $field;
        $this->errorcount++;
      
       }  
     }
     return $this->errors;
  }


  function mandatoryyy_fields_validation($user,$field,$option) {
      if($option == 4) {
    // print_object($option);
      if(empty($user->$field)){
        $strings = new stdClass;
        $strings->field = $field;
        $strings->linenumber = $this->excel_line_number;
        echo '<div class=local_users_sync_error>Missing field of "'. $field .'" uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'please enter a valid fields in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = $field;
        $this->errorcount++;
      
       }  
     }
     return $this->errors;
  }


  function courseid_validation($excel) {
    global $DB, $USER;
     
     $courseid = $DB->get_record('course', array('shortname'=>$excel->coursecode));
      if(!$courseid->shortname) {

         echo '<div class=local_users_sync_error>This courseid "' . $excel->coursecode . '" is not existing of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
         $this->errors[] = 'coursecode' . $excel->coursecode . ' in excel sheet at line '.$this->excel_line_number.'.';
         $this->mfields[] = 'coursecode';
         $this->errorcount++;

       }

     return $this->errors;
  } 


  function credits_validation($excel, $option) {
   global $DB, $USER;
  
    if ($option == 1) {
       if (!is_numeric($excel->credits)) {
          echo '<div class=local_users_sync_error>Please enter credits "' . $excel->credits . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
       $this->errors[] = 'credits' . $excel->credits . ' in excel sheet at line '.$this->excel_line_number.'.';
       $this->mfields[] = 'credits';
       $this->errorcount++;
      }
    }

     if ($option == 4) {
       if (!is_numeric($excel->credits)) {
          echo '<div class=local_users_sync_error>Please enter credits "' . $excel->credits . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
       $this->errors[] = 'credits' . $excel->credits . ' in excel sheet at line '.$this->excel_line_number.'.';
       $this->mfields[] = 'credits';
       $this->errorcount++;
      }
    }

   return $this->errors;
  }

 function levels_validation($excel, $option) {
   global $DB, $USER;
  
   if($option == 3) {

    $levelsids = $DB->get_record_sql("SELECT * FROM {local_levels} WHERE name = '$excel->levelcode'");

        if(!$levelsids->name) {

         echo '<div class=local_users_sync_error>This level "' . $excel->levelcode . '" is not existing of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
         $this->errors[] = 'levelcode' . $excel->levelcode . ' in excel sheet at line '.$this->excel_line_number.'.';
         $this->mfields[] = 'levelcode';
         $this->errorcount++;

       }
   }
    if($option == 4) {

    $levelsids = $DB->get_record_sql("SELECT * FROM {local_levels} WHERE name = '$excel->levelcode'");

        if(!$levelsids->name) {

         echo '<div class=local_users_sync_error>This level "' . $excel->levelcode . '" is not existing of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
         $this->errors[] = 'levelcode' . $excel->levelcode . ' in excel sheet at line '.$this->excel_line_number.'.';
         $this->mfields[] = 'levelcode';
         $this->errorcount++;

       }
   }
  
   return $this->errors;
  }



 // function duration_validation($excel, $option) {
 //   global $DB, $USER;
 //   if($option == 2) {
 //      if(!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $excel->duration)) {
 //        echo '<div class=local_users_sync_error>Please enter duration "' . $excel->duration . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
 //        $this->errors[] = 'duration' . $excel->duration . ' in excel sheet at line '.$this->excel_line_number.'.';
 //        $this->mfields[] = 'duration';
 //       $this->errorcount++;
 //     }
 //   }
 //    if($option == 4) {
 //      if(!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $excel->duration)) {
 //        echo '<div class=local_users_sync_error>Please enter duration "' . $excel->duration . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
 //        $this->errors[] = 'duration' . $excel->duration . ' in excel sheet at line '.$this->excel_line_number.'.';
 //        $this->mfields[] = 'duration';
 //       $this->errorcount++;
 //     }
 //    }
 //   return $this->errors;
 //  }

  function duration_in_minutes_validation($excel, $option) {
   global $DB, $USER;
   if($option == 2) {
     if( !is_numeric($excel->duration_in_minutes)  || (int)$excel->duration_in_minutes < 0 || (int)$excel->duration_in_minutes > 59 ){
         echo '<div class=local_users_sync_error>Please enter duration in minutes "' . $excel->duration_in_minutes . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
          $this->errors[] = 'duration_in_minutes' . $excel->duration_in_minutes . ' in excel sheet at line '.$this->excel_line_number.'.';
          $this->mfields[] = 'duration_in_minutes';
          $this->errorcount++;
      }
  
   }
    if($option == 4) {
       if(!is_numeric($excel->duration_in_minutes) || (int)$excel->duration_in_minutes < 0 || (int)$excel->duration_in_minutes > 59 ){
         echo '<div class=local_users_sync_error>Please enter duration in minutes "' . $excel->duration_in_minutes . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
          $this->errors[] = 'duration_in_minutes' . $excel->duration_in_minutes . ' in excel sheet at line '.$this->excel_line_number.'.';
          $this->mfields[] = 'duration_in_minutes';
          $this->errorcount++;
      }
   
    }
   return $this->errors;
  }
  function duration_in_hours_validation($excel, $option) {
   global $DB, $USER;
   if($option == 2) {
    if(!is_numeric($excel->duration_in_hours) || (int)$excel->duration_in_hours <= 0 ){
        echo '<div class=local_users_sync_error>Please enter duration in hours "' . $excel->duration_in_hours . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'duration_in_hours' . $excel->duration_in_hours . ' in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = 'duration_in_hours';
        $this->errorcount++;
    }
 
   }
    if($option == 4) {
       if(!is_numeric($excel->duration_in_hours) || (int)$excel->duration_in_hours <= 0 ){
       echo '<div class=local_users_sync_error>Please enter duration in hours "' . $excel->duration_in_hours . '" in integer formate of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
        $this->errors[] = 'duration_in_hours' . $excel->duration_in_hours . ' in excel sheet at line '.$this->excel_line_number.'.';
        $this->mfields[] = 'duration_in_hours';
        $this->errorcount++;
      }
   
    }
   return $this->errors;
  }
}//end of class
