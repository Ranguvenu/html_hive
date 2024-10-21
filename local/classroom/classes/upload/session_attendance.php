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
 * @subpackage local_classroom
 */

namespace local_classroom\upload;

require_once($CFG->dirroot . '/course/lib.php');
use html_writer;
use stdClass;
 
class session_attendance {
    private $data;
    //-------To hold error messages
    private $errors = array();
    //----To hold error field name
    private $mfields = array(); 

    private $errorcount = 0;   

    private $updatedcount = 0;
   
    //-----It holds the unique username
    private $excel_line_number;

    public $classroomid;
  
    function __construct($data = null)
    {
        $this->data = $data;
    } // end of constructor
    /**BULK UPLOAD FRONTEND METHOD
     * @param $cir [<csv_import_reader Object >]
     * @param $[filecolumns] [<colums fields in csv form>]
     * @param array $[formdata] [<data in the csv>]
     * .
     **/
    public function session_attendanceupload($cir, $filecolumns, $formdata) {
        
        global $DB, $USER, $CFG;    
          
        $linenum = 1;
        $mandatory_fields = ['employee_id','employee_email','attendance_status'];
        while ($line = $cir->next()) {
            $linenum++;
            $attendance = new \stdClass();
            foreach ($line as $keynum => $value) {
                if (!isset($filecolumns[$keynum])) {
                    // this should not happen
                    continue;
                }
                $key = $filecolumns[$keynum];
                $attendance->$key = trim($value);
            }
            $this->data[] = $attendance;
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;
            

            foreach ($mandatory_fields as $field) {
               $error = $this->mandatory_field_validation($attendance, $field);
            }
 
           //validation for employee id 
            if(!empty($attendance->employee_id)){
                $error = $this->employeeid_validation($attendance,$formdata);
            }

            //validation for employee_email
            if(!empty($attendance->employee_email)){
                $error = $this->employee_email_validation($attendance);
            }

            //validation of attendance_status
            if(!empty($attendance->attendance_status)){
                $error = $this->attendance_status_validation($attendance);
            }

            // write_error_db for insertion
            if (empty($error)) {
                  $this->upload_sessionattendance($attendance,$formdata);
            } 
        }

        if ($this->data) {
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">' . get_string('empfile_syncstatus', 'local_classroom') . '</h3>';

            $upload_info .= '<div class=local_classroom_sync_success>' . get_string('updatedusers_msg', 'local_classroom', $this->updatedcount) . '</div>';
            $upload_info .= '<div class=local_classroom_sync_error>' . get_string('errorscount_msg', 'local_classroom', $this->errorcount) . '</div>
            </div>';
            
            $button = html_writer::tag('button', get_string('button', 'local_users'), array('class' => 'btn btn-primary'));
            $link = html_writer::tag('a', $button, array('href' => $CFG->wwwroot . '/local/classroom/index.php'));
            $upload_info .= '<div class="w-full pull-left text-xs-center">' . $link . '</div>';
            mtrace($upload_info);
           
        } else {
            echo '<div class="critera_error">' . get_string('filenotavailable', 'local_classroom') . '</div>';
        }
    }


    //validation for mandatory missing fields
    function mandatory_field_validation($attendance, $field) {
        if (empty(trim($attendance->$field))) {
            $strings = new stdClass;
            $strings->field = $field;
            $strings->linenumber = $this->excel_line_number;
            echo '<div class=local_classroom_sync_error>Missing field of "' . $field . '" uploaded excelsheet at line ' . $this->excel_line_number . '</div>';
            echo '<div class=local_classroom_sync_error>'.get_string('mandatory_fields','local_classroom',$strings).'</div>';
            $this->errors[] = get_string('mandatory_fields','local_classroom',$strings);
            $this->mfields[] = $field;
            $this->errorcount++;
        }
        return $this->errors;
    }

    //Upload Classroom Session attendance 

    function upload_sessionattendance($excel,$formdata = null){
        global $DB,$USER;
        $mandatory_fields = ['employee_id','employee_email','attendance_status'];
        $excel->employee_id = trim($excel->employee_id);
        $userdata = $DB->get_field('user','id',array('open_employeeid' => $excel->employee_id));
        $data = new \stdclass();
        $sql = "SELECT id FROM {local_classroom_attendance} WHERE classroomid = '$formdata->cid' AND sessionid = 'formdata->sid' AND userid = '$userdata'";

        $attendancesql = $DB->get_record_sql($sql);

        if($attendancesql->id > 0) {
          $data->id = $attendancesql->id;
          $data->classroomid = $formdata->cid;
          $data->sessionid = $formdata->sid;
          $userid = $DB->get_record('user',array('open_employeeid' => $excel->employee_id));
          $data->userid = $userid->id;
          $excel->attendance_status = trim($excel->attendance_status);
          if($excel->attendance_status == 'Present'){
            $data->status = 1;
          }else if($excel->attendance_status == 'Absent'){
            $data->status = 2;
          }else if($excel->attendance_status == 'NA'){
            $data->status = 0;
          }
          $data->timemodified = time();

        $data->mandatory_fields = $mandatory_fields;
        $DB->update_record('local_classroom_attendance',$data);

        } else {  
          $sessiondata = new \stdclass();
          
          $sessiondata->classroomid = $formdata->cid;
          $sessiondata->sessionid = $formdata->sid;
          $userid = $DB->get_record('user',array('open_employeeid' => $excel->employee_id));
          $sessiondata->userid = $userid->id;
          $excel->attendance_status = trim($excel->attendance_status);
          if($excel->attendance_status == 'Present'){
            $sessiondata->status = 1;
          }else if($excel->attendance_status == 'Absent'){
            $sessiondata->status = 2;
          }else if($excel->attendance_status == 'NA'){
            $sessiondata->status = 0;
          }
          $sessiondata->usercreated = $USER->id;
          $sessiondata->timecreated = time();
         
          $DB->insert_record('local_classroom_attendance',$sessiondata);
       }

        if(empty($this->errors)){
        echo "<div class='alert alert-success'>Attendance for the session " . $formdata->sid." for the " . $excel->employee_id . " employee is  succcessfully updated in system</div>";
        $this->updatedcount++; 
        }   

    }
 
    //validation for employeeid
   function employeeid_validation($excel,$formdata = null){
        global $DB, $USER;
        $strings = new stdClass();
        $strings->employeeid = $excel->employee_id;
        $strings->linenumber = $this->excel_line_number;
        $excel->employee_id = trim($excel->employee_id);
        $userdata = $DB->get_record('user',array('open_employeeid' => $excel->employee_id));
        $classroom_userdata = $DB->get_records('local_classroom_users',array('userid' => $userdata->id,'classroomid' => $formdata->cid));
        if($excel->employee_id != $userdata->open_employeeid && !($classroom_userdata)){
            echo '<div class=local_classroom_sync_error>'.get_string('employeeid_validation','local_classroom',$strings).'</div>';
            $this->errors[] = get_string('employeeid_validation','local_classroom',$strings);
            $this->mfields[] = 'employee_id';
            $this->errorcount++; 
        }
        return $this->errors;
     }

     function employee_email_validation($excel){
        global $DB, $USER;
        $strings = new stdClass();
        $strings->employeeid = $excel->employee_id;
        $strings->linenumber = $this->excel_line_number;
        $excel->employee_email = trim($excel->employee_email);
        $userdata = $DB->get_record('user',array('open_employeeid' => $excel->employee_id));
        if($excel->employee_email != $userdata->email){
            echo '<div class=local_classroom_sync_error>'.get_string('employee_email_validate','local_classroom',$strings).'</div>';
            $this->errors[] = get_string('employee_email_validate','local_classroom',$strings);
            $this->mfields[] = 'employee_email';
            $this->errorcount++; 
        } 
        return $this->errors;
     }


    function attendance_status_validation($excel){
         global $DB, $USER;
         $strings = new stdClass();
         $strings->attendance_status = $excel->attendance_status;
         $strings->linenumber = $this->excel_line_number;
         $status_present = 'Present';
         $status_absent = 'Absent';
         $status_na = 'NA';
         if(!(($excel->attendance_status == $status_present) || ($excel->attendance_status == $status_absent) || ($excel->attendance_status == $status_na))){
            echo '<div class=local_classroom_sync_error>'.get_string('attendance_status_validate','local_classroom',$strings).'</div>';
            $this->errors[] = get_string('attendance_status_validate','local_classroom',$strings);
            $this->mfields[] = 'attendance_status';
            $this->errorcount++;  
         }
        return $this->errors;
     }
 
 }
