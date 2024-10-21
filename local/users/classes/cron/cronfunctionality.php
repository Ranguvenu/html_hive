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
 * @subpackage local_users
 */

namespace local_users\cron;
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
define('ONLY_ADD', 1);
define('ONLY_UPDATE', 2);
define('ADD_UPDATE', 3);
class cronfunctionality{
    

    private $data;
    
    //-------To hold error messages
    private $errors = array();
    
    //----To hold error field name       
    private  $mfields = array();
    
    //-----To hold warning messages----
    private $warnings = array();
    
    //-----To hold warning field names-----
    private $wmfields = array();
    
    private $errormessage;
    
    //-----It hold user field cost center id
    private $costcenterid;
    
    //-----It will hold the Deparment id
    private $leve1_departmentid;
    
    //----It will hold the Sub_department id
    // private $leve2_departmentid;
    
    //---It will holds the status(active or inactive) of the user
    private $activestatus;
    
    //----It will holds the count of inserted record
    private $insertedcount=0;
    
    //----It will holds the count of updated record
    private $updatedcount=0;
    
    
    public $costcenterobj;

    private $errorcount=0;

    private $warningscount=0;

    private $updatesupervisor_warningscount =0;
    
    //---It will holds the costcenter shortname    
    private $costcenter_shortname;

    //-----It holds the unique username    
    private $username;
    
    //----It holds the unique employee id
    private $employee_id;

    private $department_shortname;

    private $excel_line_number;

    private $mobileno;
    
    
    function __construct($data=null){    
        $this->data = $data;
        $this->costcenterobj = new costcenter();
    }// end of constructor
    
    /**BULK UPLOAD FRONTEND METHOD
    * @param  $cir [<csv_import_reader Object >]
    * @param  $[filecolumns] [<colums fields in csv form>]
    * @param array $[formdata] [<data in the csv>]
    * for inserting record in local_userssyncdata.
     **/
    public function  main_hrms_frontendform_method($cir,$filecolumns, $formdata){
           
        global $DB,$USER, $CFG;
    
        $inserted = 0; $updated = 0; 
        $linenum = 1;    
        while($line=$cir->next()){

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
            $this->errors = array();
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $this->excel_line_number = $linenum;
            
            //---to get the costcenter shortname------
            $this->to_get_the_costcentershortname($user);      
                       
            //---to get the department shortname------
            $this->to_get_the_departmentshortname($user);
            
            //---It will set the username and employee id-----
            $this->to_get_the_username_employeeid($user,$formdata->option);  

            //--username validation and also creating costcenter if not available
            $this->costcenter_validation($user);
             
            //-----It includes firstname and lastname, email fields validation
            $this->required_fields_validations($user,$formdata->option);              
            
            //-----It includes employee status validation , if find  other than the existing string,it will suspend the user
            $this->employee_status_validation($user);

            //--domain validation   
            $this->domain_validation($user);    

            //--position validation 
            $this->position_validation($user);

            if(!empty($user->mobileno)){
                // It includes validation of the mobile number to be numeric and of 10 digit else throws an errror
                $this->mobileno_validation($user);
            }

            //---It will set the  level1_departmentid-----------
            if(!empty($this->open_costcenterid)){
                $this->get_departmentid('department', $this->open_costcenterid, $user, 'level1_departmentid');
                // if(!empty($this->level1_departmentid)){
                // //---It will set the  level2_departmentid-----------
                //     $this->get_departmentid('sub_department', $this->level1_departmentid, $user, 'level2_departmentid');
                // }else{
                //     $this->level2_departmentid = null;
                // }
            }else{
                $this->level1_departmentid = null;
            }
            
            if($this->errormessage){
                echo '<div class="local_users_sync_error">'.$this->errormessage.'</div>';
                if (count($this->errors) > 0) {
                    // print_object($this->errors);
                    // write error message to db and inform admin
                    $this->write_error_db($user);
                    // $this->errorcount = $this->errorcount+count($this->errors);
                }
                goto errorloop;
            } 
                
            if (count($this->errors) > 0) {
                // write error message to db and inform admin
                $this->write_error_db($user);
                // $this->errorcount = $this->errorcount+count($this->errors);
            } else {
                //-----based on selected form option add and update operation will dones
                if($formdata->option==ONLY_ADD){

                    $exists=$DB->record_exists('user',array('email'=>$user->email));

                    if($exists){ 
                        
                        echo "<div class='local_users_sync_error'>User with email $user->email already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with email $user->email already exist at line $this->excel_line_number.";
                        $this->mfields[] = "useremail";
                        $this->errorcount++;
                        $flag=1;
                        continue;
                    }
                    else if($DB->record_exists('user',  array('username' => $user->username))){
                        echo "<div class='local_users_sync_error'>User with username $user->username already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with username $user->username already exist at line $this->excel_line_number";
                        $this->mfields[] = "username";  
                        $this->errorcount++;
                        $flag=1;
                        continue;

                    } else if($DB->record_exists('user',  array('open_employeeid' => $user->employee_id))){
                        echo "<div class='local_users_sync_error'>User with employee id $user->employee_id already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with employee id $user->employee_id already exist at line $this->excel_line_number.";
                        $this->mfields[] = "useremployeeid";
                        $this->errorcount++;
                        $flag=1;
                        continue;

                    }      
                } 
                if($formdata->option==ONLY_ADD || $formdata->option==ADD_UPDATE){

                	$exists=$DB->record_exists('user',array('username'=>$user->username));
                    if(!$exists){ 
                        $err=$this->specific_costcenter_validation($user,$formdata->option);
                        if(!$err)
                        $this->add_rows($user, $formdata);
                    }else if($formdata->option==ONLY_ADD){
                        echo "<div class='local_users_sync_error'>User with employee id $user->employee_id already exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with employee id $user->employee_id already exist at line $this->excel_line_number.";
                        $this->mfields[] = "employee_id";
                        $this->errorcount++;
                        $flag=1;
                        continue;
                    }
                       
                }
                if($formdata->option==ONLY_UPDATE || $formdata->option==ADD_UPDATE){
                    $user_sql = "SELECT id  FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :employeeid) AND deleted = 0";
                    $user_exists = $DB->get_record_sql($user_sql,  array('username' => $user->username, 'email' => $user->email, 'employeeid' => $user->employee_id));
                    if ($user_exists) {                    
                    //-----Update functionality------------------
                    $userobject=$this->preparing_user_object($user, $formdata);
                    $this->update_rows($user, $userobject);                               
                    }else if($formdata->option==ONLY_UPDATE) {
                        echo "<div class='local_users_sync_error'>User with employee id $user->employee_id doesn't exist at line $this->excel_line_number.</div>";
                        $this->errors[] = "User with employee id $user->employee_id doesn't exist at line $this->excel_line_number.";
                        $this->mfields[] = "employee_id";
                        $this->errorcount++;
                        $flag=1;
                        continue;
                    }              
                }                
                // write warnings to db and inform admin
                if ( count($this->warnings) > 0) {
                    $this->write_warning_db($user);
                    $this->warningscount = count($this->warnings);
                    
                }
            }       
                
            
            $data[]=$user;		
	    }
         errorloop:
        
        
        //-----updating Reporting Manager (supervisor id )
        $this->update_supervisorid($this->data);
        if ( count($this->warnings) > 0 ) {
            $this->write_warning_db($excel);
            $this->updatesupervisor_warningscount= count($this->warnings); 
                    
        }
        
        if($this->data){
            $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">Employee file sync status</h3>
            <div class=local_users_sync_success>Total '.$this->insertedcount . ' new users added to the system.</div>
            <div class=local_users_sync_success>Total '.$this->updatedcount . ' users details updated.</div>
            <div class=local_users_sync_error>Total '.$this->errorcount . ' errors occured in the sync update.</div></div>
            <div class=local_users_sync_warning>Total '.$this->warningscount . ' warnings occured in the sync update.</div>
            <div class=local_users_sync_warning>Total '.$this->updatesupervisor_warningscount.' Warnings occured  while updating supervisor.</div>
            ';
            /*code added by Rizwan for continue button*/
            $button=html_writer::tag('button',get_string('button','local_users'),array('class'=>'btn btn-primary'));
            $link= html_writer::tag('a',$button,array('href'=>$CFG->wwwroot. '/local/users/index.php'));
            $upload_info .='<div class="w-full pull-left text-xs-center">'.$link.'</div>';
            /*end of the code*/
            mtrace( $upload_info);
        
            //-------code1 by rizwana starts-------//
            $sync_data=new \stdClass();
            $sync_data->newuserscount=$this->insertedcount;
            $sync_data->updateduserscount=$this->updatedcount;
            $sync_data->errorscount=$this->errorcount;
            $sync_data->warningscount=$this->warningscount;
            $sync_data->supervisorwarningscount=$this->updatesupervisor_warningscount;
            $sync_data->usercreated=$USER->id;
            $sync_data->usermodified=$USER->id;
            $sync_data->timecreated=time();
            $sync_data->timemodified=time();
            $insert_sync_data = $DB->insert_record('local_userssyncdata',$sync_data);
            //-------code1 by rizwana ends-------//             
        } else {
            echo'<div class="critera_error">File with Employee data is not available for today.</div>';
        }
        
    } // end of main_hrms_frontendform_method function
    
    /**
     * @param   $excel [<data in excel or csv uploaded>]
     */
    private function to_get_the_costcentershortname($excel){        
        $costcenter_shortname= core_text::strtolower($excel->organization);
        
        if(empty($costcenter_shortname)){
            echo '<div class=local_users_sync_error>Provide the organization info for employee id "' . $excel->employee_id . '" of uploaded sheet at line '.$this->excel_line_number.'.</div>';
            $this->errors[] = 'Provide the organization info for employee id "' . $excel->employee_id . '" of uploaded sheet at line '.$this->excel_line_number.'.';
            $this->mfields[] = 'organization';
            $this->errorcount++;
        }
        else{            
            $this->costcenter_shortname = $costcenter_shortname;            
        }        
    } // end of the to_get_the_costcentershortname  

    /**
     * @param   $excel [<data in excel or csv uploaded>]
     */
    private function to_get_the_departmentshortname($excel){ 
    global $DB;       
        $department_shortname= core_text::strtolower($excel->department);
        
        if(empty($department_shortname)){
            // echo "<div class=local_users_sync_error>Provide the Department for user at line $this->excel_line_number .</div>";
            // $this->errors[] = 'Provide the Department info for employee id "' . $excel->employee_id . '" of uploaded sheet in line '.$this->excel_line_number.'.';
            // $this->mfields[] = 'organization';
            // $this->errorcount++;
        }
        else{            
            $this->department_shortname = $department_shortname;
            $this->level1_departmentid = $DB->get_field('local_costcenter', 'id', array('shortname' => $department_shortname));            
        }        
    } // end of the to_get_the_departmentshortname 

    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function costcenter_validation($excel){
         global $DB, $USER;
        $systemcontext = \context_system::instance();
         //------username validation-------------------
            if ( $this->costcenter_shortname) {
                $costcenter_shortname=$this->costcenter_shortname;
                $department_shortname=$this->department_shortname;
                // checking cost center available if not inserting new costcenter
                $costcenterinfo = $DB->get_record_sql("SELECT * FROM {local_costcenter} WHERE lower(shortname)='$costcenter_shortname'");
                $departmentinfo = $DB->get_record_sql("SELECT * FROM {local_costcenter} WHERE lower(shortname)='$department_shortname'");
                if(empty($costcenterinfo)){
                    echo '<div class=local_users_sync_error>Organisation "'.$costcenter_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'Organisation "'.$costcenter_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'Organisation';
                    $this->errorcount++;
                }elseif ((!$DB->record_exists('user', array('id'=> $USER->id, 'open_costcenterid'=>$costcenterinfo->id))) && (!is_siteadmin()) && (!has_capability('local/costcenter:manage_multiorganizations', $systemcontext))){
                    echo '<div class=local_users_sync_error>Organisation "'.$costcenter_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .</div>';
                    $this->errors[] = 'Organisation "'.$costcenter_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .';
                    $this->mfields[] = 'Organisation';
                    $this->errorcount++;
                }else {
                    $this->open_costcenterid = $costcenterinfo->id;
                }

                if(empty($departmentinfo)){
                    echo '<div class=local_users_sync_error>Department "'.$department_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'Department "'.$department_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'Department';
                    $this->errorcount++;
                }elseif ((!$DB->record_exists('user', array('id'=> $USER->id, 'open_departmentid'=>$departmentinfo->id))) && (!is_siteadmin()) && (!has_capability('local/costcenter:manage_multiorganizations', $systemcontext))&&(has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
                    echo '<div class=local_users_sync_error>Department "'.$department_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .</div>';
                    $this->errors[] = 'Department "'.$department_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .';
                    $this->mfields[] = 'Department';
                    $this->errorcount++;
                } else {
                    $this->open_departmentid = $departmentinfo->id;
                }
            }
        
    } // end of costcenter_validation function
    
    
   
    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function required_fields_validations($excel,$option=0){
        global $DB;        
        if(!empty($excel->employee_id) && !empty($excel->email) && !empty($excel->username)){

            $exist_sql = "SELECT id,username FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :employeeid) AND deleted = 0";
            $users_exist = $DB->get_records_sql_menu($exist_sql, array('username' => $excel->username ,'email' => $excel->email, 'employeeid' => $excel->employee_id));
            $cexist_users = count($users_exist);
        }
        //------employee code validation-------------------    
         if ( array_key_exists('employee_id', (array)$excel) ) {
              //  $excel->employeeid = strtolower($excel->employee_id);
                if (!empty($excel->employee_id)) {
                    $this->employee_id = $excel->employee_id;
                    $stringhelpers = new stdClass();
                    $stringhelpers->linenumber = $this->excel_line_number;
                    $stringhelpers->employee_id = $this->employee_id;
                    if(ctype_alnum($excel->employee_id)){
                        if($option!=0){
                            $user_exist = $DB->get_record('user', array('open_employeeid' => $excel->employee_id));
                            if($option==ONLY_ADD){
                                if($user_exist){
                                    $error_string = get_string('cannotcreateuseremployeeidadderror', 'local_users',$stringhelpers);
                                    echo "<div class='local_users_sync_error'>".$error_string.".</div>";
                                    $this->errors[] = $error_string;
                                    $this->mfields[] = 'Employee_id';
                                    $this->errorcount++;
                                    // return; 
                                }
                            }else if($option == ONLY_UPDATE || $option == ADD_UPDATE){
                                
                                $sql = "SELECT id,username,email FROM {user} WHERE  open_employeeid = :employeeid AND deleted = 0";

                                $user_object = $DB->get_record_sql($sql , array('employeeid'=>$excel->employee_id));
                                if($user_object){
                                    if(!($user_object->username == $this->username || $user_object->email == $excel->email) && $cexist_users >1){
                                        // if($user_object->username == $this->username){
                                        //     $error_string = get_string('multipleedituserusernameediterror','local_users',$this->username);
                                        //     $error_field = 'username';
                                        // }else if($user_object->email == $excel->email){
                                        //     $error_string = get_string('multipleedituseremailupdateerror','local_users',$excel->email);
                                        //     $error_field = 'email';
                                        // }
                                        $error_string = get_string('multipleuseremployeeidupdateerror','local_users', $stringhelpers);
                                        $error_field = 'employee_id';
                                        echo "<div class='local_users_sync_error'>".$error_string.".</div>";
                                        $this->errors[] = $error_string;
                                        $this->mfields[] = $error_field;
                                        $this->errorcount++;
                                    }
                                }
                            }if($option == ONLY_UPDATE){
                                if(!$user_exist){
                                    $error_string = get_string('cannotfinduseremployeeidupdateerror', 'local_users',$stringhelpers);
                                    echo "<div class='local_users_sync_error'>".$error_string.".</div>";
                                    $this->errors[] = $error_string;
                                    $this->mfields[] = 'Employee_id';
                                    $this->errorcount++;
                                    // return; 
                                }
                            }

                        }
                    }else{
                        // echo '<div class=local_users_sync_error>Error in Employee id - Invalid employee id "'.$excel->employee_id.'" in uploaded excelsheet  at line '.$this->excel_line_number.'.</div>';
                        $errormessage = 'Provide valid employee id value '.$excel->employee_id.' inserted in the excelsheet at line'.$this->excel_line_number.'.';
                        echo '<div class=local_users_sync_error>'.$errormessage.'</div>';
                        $this->errors[] = $errormessage;
                        $this->mfields[] = 'Employee_id';
                        $this->errorcount++;
                        // return;
                    }
                    
                } else {
                       echo '<div class=local_users_sync_error>Provide employee id for username "' . $excel->username . '" of uploaded sheet at line '.$this->excel_line_number.'.</div>';
                       $this->errors[] = 'Provide employee id for username "' . $excel->username . '" of uploaded sheet at line '.$this->excel_line_number.'.';
                       $this->mfields[] = 'Employee_id';
                       $this->errorcount++;
                       // return;
                }
            } else {
                echo '<div class=local_users_sync_error>Error in Employee id column heading in uploaded excelsheet </div>';
                $this->errormessage = 'Error in Employee id column heading in uploaded excelsheet ';
                $this->errorcount++;
                // return;
            
            }
            //---------end of employee code validation------------------
            
            //-----------check firstname-----------------------------------
            if ( array_key_exists('first_name', (array)$excel) ) {
                 if (empty($excel->first_name)) {
                     echo '<div class=local_users_sync_error>Provide first name for  employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>'; 
                     $this->errors[] = 'Provide first name for  employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'.' ;
                     $this->mfields[] = 'firstname';
                     $this->errorcount++;
                     // return;
                       
                 }
            } else {
               echo '<div class=local_users_sync_error>Error in first name column heading in uploaded excelsheet</div>'; 
               $this->errormessage = 'Error in first name column heading in uploaded excelsheet';
               $this->errorcount++;
               // return;
                
            }
            
            //-------- check lastname-------------------------------------
            if ( array_key_exists('last_name', (array)$excel) ) {
                if (empty($excel->last_name)) {
                   echo '<div class=local_users_sync_error>Provide last name for  employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>'; 
                   $this->errors[] = 'Provide last name for  employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                   $this->mfields[] = 'last_name';
                   $this->errorcount++;
                   // return;
                }
            } else {
                echo '<div class=local_users_sync_error>Error in last name column heading in uploaded excelsheet </div>'; 
                $this->errormessage = 'Error in last name column heading in uploaded excelsheet ';
                $this->errorcount++;
                // return;
                
            }
             
            
            //----------------- check email id------------------------------------------------
            if ( array_key_exists('email', (array)$excel) ) {
                
                if (empty($excel->email)) {
                    echo '<div class=local_users_sync_error>Provide email id for  employee id "' . $excel->employee_id. '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'Provide email id for  employee id "' . $excel->employee_id. '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'email';
                    $this->errorcount++;
                    // return;
                } else {
                    if (! validate_email($excel->email)) {
                        echo '<div class=local_users_sync_error>Invalid email id entered for  employeeid "' . $excel->employee_id. '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>';
                        $this->errors[] = 'Invalid email id entered for  employee id "' . $excel->employee_id. '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                        $this->mfields[] = 'email';
                        $this->errorcount++;
                        // return;
                    }
                    if($option!=0){
                        $sql = "SELECT id FROM {user} WHERE (username = :username OR email = :email) AND deleted = 0";

                        $user_exist = $DB->get_record_sql($sql , array('username' => $excel->username, 'email' => $excel->email));
                        if($option == ONLY_ADD){
                            
                            // if($user_exist){
                            //     echo "<div class='local_users_sync_error'>".get_string('cannotcreateuseremailadderror', 'local_users',$excel->email)."</div>";
                            //     $this->errors[] = get_string('cannotcreateuseremailadderror', 'local_users',$excel->email);
                            //     $this->mfields[] = 'email';
                            //     $this->errorcount++;
                            //     // return;
                            // }

                            if($user_exist){

                                if($DB->record_exists('user',array('username'=>$user->username,'deleted' => 0))){
                                    $field = 'username';
                                    $fieldvalue = $user->username;
                                }

                                if($DB->record_exists('user',array('email'=>$user->email,'deleted' => 0))){
                                    $field = 'email';
                                    $fieldvalue = $user->email;
                                }
                                if(!empty($field)){
                                    $stringhelper = new stdClass();
                                    $stringhelper->$fieldvalue = $fieldvalue;
                                    $stringhelper->linenumber = $this->excel_line_number;
                                    $error_string = get_string('cannotcreateuser'.$field.'adderror', 'local_users',$stringhelper);
                                    echo "<div class='local_users_sync_error'>".$error_string."</div>";
                                    $this->errors[] = $error_string;
                                    $this->mfields[] = $field;
                                    $this->errorcount++;
                                }
                                // return;
                            }
                            
                        }else if($option == ONLY_UPDATE || $option == ADD_UPDATE){
                            $sql = "SELECT id,username,open_employeeid FROM {user} WHERE email = :email AND deleted = 0";
                            $user_object = $DB->get_record_sql($sql,  array('email' => $excel->email));
                            if($user_object){
                                if(!($user_object->username == $this->username || $user_object->open_employeeid == $excel->employee_id) && $cexist_users > 1){
                                    $stringhelpers = new stdClass();
                                    $stringhelpers->email = $excel->email;
                                    $stringhelpers->linenumber = $this->excel_line_number;
                                    $error_string = get_string('multipleedituseremailupdateerror', 'local_users', $stringhelpers);
                                    $error_field = 'email';
                                    echo "<div class='local_users_sync_error'>".$error_string."</div>";
                                    $this->errors[] = $error_string;
                                    $this->mfields[] = $error_field;
                                    $this->errorcount++;
                                    // return;
                                }
                            }
                        }else if($option == ONLY_UPDATE){
                            if(!$user_exist){
                                $stringhelpers = new stdClass();
                                $stringhelpers->email = $excel->email;
                                $stringhelpers->linenumber = $this->excel_line_number;
                                $error_string = get_string('cannotedituseremailupdateerror', 'local_users', $stringhelpers);
                                echo "<div class='local_users_sync_error'>".$error_string."</div>";
                                $this->errors[] = $error_string;
                                $this->mfields[] = 'email';
                                $this->errorcount++;
                                // return;
                            }
                        }
                    }
                }
            } else {
                
                $this->errormessage = 'Error in arrangement of columns in uploaded excelsheet at line '.$this->excel_line_number.'.';
                $this->errorcount++;
              
            }        
    } // end of required_fields_validations function
    
    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function to_get_the_username_employeeid($excel,$option=0){                
        global $CFG, $DB;
        if($excel->username){
            if(($excel->username)){
                     $this->username = strtolower($excel->username);
            } else {
                echo '<div class=local_users_sync_error>Provide valid username for employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
                $this->errors[] = 'Provide the valid username for employee id ' . $excel->employee_id . ' in excel sheet at line '.$this->excel_line_number.'.';
                $this->mfields[] = 'username';
                $this->errorcount++;
            }
            $sql = "SELECT id,email,open_employeeid FROM {user} WHERE username = :username AND deleted = 0";
            $user_object = $DB->get_record_sql($sql, array('username' => $this->username));
            if($option == ONLY_ADD){
                if($user_object){
                    $stringhelper = new stdClass();
                    $stringhelper->linenumber = $this->excel_line_number;
                    $stringhelper->username = $this->username;
                    $error_string = get_string('cannotcreateuserusernameadderror', 'local_users', $stringhelper);
                    echo "<div class='local_users_sync_error'>".$error_string.".</div>";
                    $this->errors[] = $error_string;
                    $this->mfields[] = 'username';
                    $this->errorcount++;
                }
            }else if($option == ONLY_UPDATE || $option == ADD_UPDATE){
                if(!empty($excel->employee_id) && !empty($excel->email) && !empty($excel->username)){
                    $exist_sql = "SELECT id FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :employeeid) AND deleted = 0";
                    $users_exist = $DB->get_records_sql_menu($exist_sql, array('username' => strtolower($excel->username) ,'email' => strtolower($excel->email), 'employeeid' => $excel->employee_id));
                    $cexist_users = count($users_exist);
                }
                if($user_object){
                    if(!($user_object->email == $excel->email || $user_object->open_employeeid == $excel->employee_id) && $cexist_users > 1){    
                        
                        $error_string = get_string('multipleedituserusernameediterror','local_users',$this->username);
                        $error_field = 'username';
                        echo "<div class='local_users_sync_error'>".$error_string.".</div>";
                        $this->errors[] = $error_string;
                        $this->mfields[] = $error_field;
                        $this->errorcount++;
                    }
                }
            }
            if($option == ONLY_UPDATE){
                if(!$user_object){
                    echo "<div class='local_users_sync_error'>".get_string('cannotedituserusernameediterror', 'local_users',$this->username).".</div>";
                    $this->errors[] = get_string('cannotedituserusernameediterror', 'local_users',$this->username);
                    $this->mfields[] = 'username';
                    $this->errorcount++;
                }
            }
        }else{
            echo '<div class=local_users_sync_error>Provide username for employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'</div>';
            $this->errors[] = 'Provide the username for employee id ' . $excel->employee_id . ' in excel sheet at line '.$this->excel_line_number.'.';
            $this->mfields[] = 'username_notexist';
            $this->errorcount++;
        }
    } // end of function to_ge_the_username_employeeid
    
    
    
    
    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    
    private function employee_status_validation($excel){
        
        // check employeestatus
        if (array_key_exists('employee_status', (array)$excel)) {
            if (empty($excel->employee_status)) {
                echo '<div class=local_users_sync_error>Provide employee status for  employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'.</div>';
                $this->errors[] = 'Provide employee status for  employee id "' . $excel->employee_id . '" of uploaded excelsheet at line '.$this->excel_line_number.'.';
                $this->mfields[] = 'employee_status';
                $this->errorcount++;
            } else {
                if (strtolower($excel->employee_status) == 'active') {
                    $this->activestatus = 0;
                } elseif ( strtolower($excel->employee_status) == 'inactive' ) {
                    $this->activestatus = 1;
                } else {
                    $this->activestatus = 0;
                }
            }
        } else {
            // echo 'ststy validation';
            echo '<div class=local_users_sync_error>Error in arrangement of columns in uploaded excelsheet at line '.$this->excel_line_number.'</div>';
            $this->errormessage = 'Error in arrangement of columns in uploaded excelsheet at line '.$this->excel_line_number.'.';
            $this->errorcount++;

        }        
    } // end of  employee_status_validation method
    
    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function domain_validation($excel){
         global $DB, $USER;
        $systemcontext = \context_system::instance();
         //------username validation-------------------
            if ($excel->domain) {
                $costcenter_shortname=$this->costcenter_shortname;
                $domain_shortname=$excel->domain;
                // checking cost center available if not inserting new costcenter
                $costcenterinfo = $DB->get_record_sql("SELECT * FROM {local_costcenter} WHERE lower(shortname)='$costcenter_shortname'");
                $domaininfo = $DB->get_record_sql("SELECT * FROM {local_domains} WHERE lower(code)='$domain_shortname'");
                if(empty($domaininfo)){
                    echo '<div class=local_users_sync_error>Domain "'.$domain_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'Domain "'.$domain_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'Domain';
                    $this->errorcount++;
                }if(!empty($domaininfo) && !$DB->record_exists('local_domains', array('id'=> $domaininfo->id, 'costcenter'=>$costcenterinfo->id))){
                    echo '<div class=local_users_sync_error>Domain "'.$domain_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under organization "'.$costcenter_shortname.'".</div>';
                    $this->errors[] = 'Position "'.$position_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under organization "'.$costcenter_shortname.'" .';
                    $this->mfields[] = 'Domain';
                    $this->errorcount++;
                }elseif ((!$DB->record_exists('user', array('id'=> $USER->id, 'open_domainid'=>$domaininfo->id))) && (!is_siteadmin()) && (!has_capability('local/costcenter:manage_multiorganizations', $systemcontext))&&(has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
                    echo '<div class=local_users_sync_error>Domain "'.$domain_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .</div>';
                    $this->errors[] = 'Domain "'.$domain_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .';
                    $this->mfields[] = 'Domain';
                    $this->errorcount++;
                } else {
                    $this->open_domainid = $domaininfo->id;
                }
            }
        
    } // end of domain_validation function

    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function position_validation($excel){
         global $DB, $USER;
        $systemcontext = \context_system::instance();
         //------username validation-------------------
            if ($excel->position) {
                $costcenter_shortname=$this->costcenter_shortname;
                $domain_shortname=$excel->domain;
                $position_shortname=$excel->position;
                // checking cost center available if not inserting new costcenter
                $costcenterinfo = $DB->get_record_sql("SELECT * FROM {local_costcenter} WHERE lower(shortname)='$costcenter_shortname'");
                $domaininfo = $DB->get_record_sql("SELECT * FROM {local_domains} WHERE lower(code)='$domain_shortname'");
                $positioninfo = $DB->get_record_sql("SELECT * FROM {local_positions} WHERE lower(code)='$position_shortname'");
                if(empty($positioninfo)){
                    echo '<div class=local_users_sync_error>Position "'.$position_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = 'Position "'.$position_shortname.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist in system at line '.$this->excel_line_number.'.';
                    $this->mfields[] = 'Position';
                    $this->errorcount++;
                }elseif (!empty($domaininfo) && !empty($positioninfo) && !$DB->get_record_sql("SELECT * FROM {local_positions} WHERE id={$positioninfo->id} and domain={$domaininfo->id}")){
                    echo '<div class=local_users_sync_error>Position "'.$position_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under domain "'.$domain_shortname.'".</div>';
                    $this->errors[] = 'Position "'.$position_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under domain "'.$domain_shortname.'" .';
                    $this->mfields[] = 'Position';
                    $this->errorcount++;
                }elseif (!empty($costcenterinfo) && !empty($domaininfo) &&!empty($positioninfo) && !$DB->get_record_sql("SELECT * FROM {local_positions} WHERE id={$positioninfo->id} and domain={$domaininfo->id} and costcenter={$costcenterinfo->id}")){
                    echo '<div class=local_users_sync_error>Position "'.$position_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under organization "'.$domain_shortname.'".</div>';
                    $this->errors[] = 'Position "'.$position_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under organization"'.$costcenter_shortname.'" .';
                    $this->mfields[] = 'Position';
                    $this->errorcount++;
                }elseif ((!$DB->record_exists('user', array('id'=> $USER->id, 'open_positionid'=>$positioninfo->id))) && (!is_siteadmin()) && (!has_capability('local/costcenter:manage_multiorganizations', $systemcontext))&&(has_capability('local/costcenter:manage_owndepartments', $systemcontext))){
                    echo '<div class=local_users_sync_error>Position "'.$position_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .</div>';
                    $this->errors[] = 'Position "'.$position_shortname.'" entered at line '.$this->excel_line_number.' for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not belongs to you .';
                    $this->mfields[] = 'Position';
                    $this->errorcount++;
                } else {
                    $this->open_positionid = $positioninfo->id;
                }
            }
        
    } // end of position_validation function

    /**
     * [mobileno_validation description]
     * @param  [type] $excel [description]
     * @return [type]        [description]
     */
    private function mobileno_validation($excel){
        $this->mobileno = $excel->mobileno;
        if(!is_numeric($this->mobileno)){
            echo '<div class=local_users_sync_error>Enter a valid mobile number for employee id '.$excel->employee_id.' at line '.$this->excel_line_number.'</div>';
            $this->errors[] = 'Enter a valid mobile number for employee id '.$excel->employee_id.' at line '.$this->excel_line_number.'';
            $this->mfields[] = 'mobileno';
            $this->errorcount++;
        }else if(($this->mobileno<999999999 || $this->mobileno>10000000000)){
            echo '<div class=local_users_sync_error>Enter a valid mobile number of 10 digits for employee id '.$excel->employee_id.' at line '.$this->excel_line_number.'</div>';
            $this->errors[] = 'Enter a valid mobile number of 10 digits for employee id '.$excel->employee_id.' at line '.$this->excel_line_number.'';
            $this->mfields[] = 'mobileno';
            $this->errorcount++;
        }
    }


    /**
     * @param   $excel [<data in excel or csv uploaded>] for validation
     */
    private function specific_costcenter_validation($excel,$option = 0){
        global $DB; $flag=0;
               $costcenter_shortname= core_text::strtolower($excel->organization);
   
        if (!$DB->record_exists('user', array('open_employeeid'=> $excel->employee_id))) {
     
            if($DB->get_record('user', array('username'=>  $this->username))){
                if($option==0){
                   echo '<div class=local_users_sync_error>username for  employee id "' . $excel->employee_id . '" of uploaded excelsheet is already exists  in the system</div>';
                   $this->errors[] = 'username for  employee id "' . $excel->employee_id . '" of uploaded excelsheet is already exists in the system at line '.$this->excel_line_number.'.';
                   $this->mfields[] = 'username';
                   $this->errorcount++;
                  $flag=1;
                  return $flag;
                }
            }

        /******To Check Employee id already exist with costcenter a employee id can be there with other costcenter****/
        $sql="select u.id,u.open_costcenterid from {user} u where u.open_employeeid='".$excel->employee_id."'";
        $employecodevalidation=$DB->get_record_sql($sql);
        $excel_costcenter=$this->open_costcenterid;
        $id_costcenter=$employecodevalidation->open_costcenterid;

        if($id_costcenter==$excel_costcenter){
            if($option==0){
                /*****Here we check and throw the error of employee id****/
                echo '<div class=local_users_sync_error>Employee code for  employee id "' . $excel->employee_id . '" of uploaded excelsheet is already under this organization</div>';
                $this->errors[] = 'username for  employee id "' . $excel->employee_id . '" of uploaded excelsheet is already exists in the system at line '.$this->excel_line_number.'.';
                $this->mfields[] = 'username';
                $flag=1;
                $this->errorcount++;
                }
            }
        }
        return $flag;
         
    } //end of specific_costcenter_validation
    
    
    /* method  get_departmentid
     * used to get the department(costcenter) id
     * @param : $field string (excel field name)
     * @param : $parentid int
     * @param : $excel object it holds single row
     * @param : $classmember 
     * @return : int department id  
    */
    private function get_departmentid($field, $parentid, $excel, $classmember){
        global $DB, $USER;
   
        if ( array_key_exists($field, (array)$excel) ) {
            if ( !empty( $excel->$field ) ) {
                $dep = trim($excel->$field);
                $dep =strtolower($dep);
                if($field == "department"){
                   $head = "organization";
                   $parent_name = $excel->organization; 
                }
                /*else if($field == "sub_department"){
                    $head = "department";
                    $parent_name = $excel->department;
                }*/
                 
                $dep=str_replace("\n", "", $dep);

                $departmentname = $DB->get_record_sql("SELECT * from {local_costcenter} where lower(shortname) = '$dep' AND parentid= $parentid");      
                  
                if (empty($departmentname)) {     
                    echo '<div class=local_users_sync_error>'.ucfirst($field).' "'.$dep.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under '.$head.' '.$parent_name.' at line '.$this->excel_line_number.'.</div>';
                    $this->errors[] = ucfirst($field).' "'.$dep.'"for employee id "'.$excel->employee_id.'" in uploaded excelsheet does not exist under '.$head.' '.$parent_name.' at line '.$this->excel_line_number.'.';
                    $this->mfields[] = $field;
                    $this->errorcount++;
                    $this->$classmember = null;              
                } else {
                    $this->$classmember = $departmentname->id;
                }
            }else{
                echo '<div class=local_users_sync_error>Provide '.ucfirst($field).' for employeeid "'.$excel->employee_id.'" at line'.$this->excel_line_number.'.</div>';
                // $this->warningscount++;
                // $this->$classmember = null;
                $this->errors[] = 'Provide '.ucfirst($field).' for employeeid "'.$excel->employee_id.'" at line '.$this->excel_line_number.'.';
                $this->mfields[] = $field;
                $this->errorcount++;
                $this->$classmember = null;  

            }        
        }

    } // end of  get_departmentid method
    
    
    private function write_error_db($excel){
        global $DB, $USER;
        // write error message to db and inform admin
        $syncerrors = new \stdclass();
        $today = date('Y-m-d');
        $syncerrors->date_created = time();
        $errors_list = implode(',',$this->errors);
        $mandatory_list = implode(',',$this->mfields);
        $syncerrors->error = $errors_list;
        $syncerrors->modified_by = $USER->id;
        $syncerrors->mandatory_fields = $mandatory_list;
        if (empty($excel->email))
            $syncerrors->email = '-';
        else
            $syncerrors->email = $excel->email;
                
        if (empty($excel->employee_id))
            $syncerrors->idnumber = '-';
        else
            $syncerrors->idnumber =$excel->employee_id;
            $syncerrors->firstname = $excel->first_name;
            $syncerrors->lastname = $excel->first_name;
            $syncerrors->sync_file_name="Employee";
           // $syncwarnings->type = 'Error';
            $DB->insert_record('local_syncerrors', $syncerrors);   
        
    } // end of write_error_db method
    
    private function preparing_user_object($excel, $formdata=null){
        global $USER;
        $user = new \stdclass();    
      
        $user->suspended = $this->activestatus;

        $user->idnumber = $this->employee_id;
        // $user->serviceid = $excel->employee_id;
        $user->open_employeeid = $excel->employee_id;
        $user->username = strtolower($this->username);
        
        $user->firstname = $excel->first_name;
        $user->lastname = $excel->last_name;
        $user->middlename = $excel->middle_name ? $excel->middle_name : ' ';
        $user->phone1 = $excel->mobileno ? $excel->mobileno : '';
        $user->email = strtolower($excel->email);
        $user->open_country = 'IN';
        $user->open_designation = $excel->role_designation ? $excel->role_designation : ' ';
        $user->open_group = $excel->level ? $excel->level : ' ';
        $user->employee_status = $excel->employee_status;
        $user->open_location = $excel->city ? $excel->city : ' ';
        $user->open_state = $excel->state_name ? $excel->state_name : ' ';
        $user->city =  $excel->city ? $excel->city : ' ';
        $user->location = $user->city;
        $user->area =  $excel->area ? $excel->area : ' ';
        $user->address = $excel->address ? $excel->address : ' ';
        $user->open_address = $excel->address ? $excel->address : ' ';
        $user->open_client = $excel->client ? $excel->client : null;
        $user->open_team = $excel->team ? $excel->team : null;
        $user->open_grade = $excel->grade ? $excel->grade :null;
        $user->open_level = $excel->level ? $excel->level :null;
      
        //----costcenter and department info -----
        $user->open_costcenterid =$this->open_costcenterid;
        $user->open_departmentid = $this->level1_departmentid;
        $user->open_subdepartment = null;
        //----Domain and Position info -----  
        $user->open_positionid = $this->open_positionid;    
        $user->open_domainid = $this->open_domainid;
        $user->usermodified = $USER->id;

         
        if($formdata){ 
            switch($formdata->enrollmentmethod){
                case MANUAL_ENROLL:
                      $user->auth = "manual";
                      break;
                case LDAP_ENROLL:
                      $user->auth = "ldap";
                      break;
                case SAML2:
                      $user->auth = "saml2";
                      break; 
                case ADwebservice:
                      $user->auth = "adwebservice";
                      break; 				     
            }
        }

        return $user;
    } // end of function
    
    
    
    private function add_newuser_instance_fromhrmssync($excel, $user){
        global $DB, $USER;
              
        //--------Insertion part--------------------    
        $user->password = hash_internal_user_password("Welcome#3");
        $user->timecreated = time();
        $user->timemodified = 0;
        $user->mnethostid = 1;
        $user->confirmed = 1;
        $user->suspended = 0;  
        $id  = user_create_user($user, false);              
        $this->insertedcount++;
    } // end of add_newuser_instance
    
    
    private function add_rows($excel,$formdata){
        global $DB, $USER, $CFG;
        $user=$this->preparing_user_object($excel,$formdata);
        $sql = "SELECT id FROM {user} WHERE (username = :username OR open_employeeid = :employeeid OR email = :email) AND deleted = 0";

        $userexist = $DB->get_record_sql($sql , array('username' => $user->username, 'employeeid'=>$user->open_employeeid, 'email' => $user->email));
        //added by sarath
        if($userexist){

            if($DB->record_exists('user',array('open_employeeid'=>$user->open_employeeid,'deleted' => 0))){
                $field = 'open_employeeid';
                $fieldvalue = $user->open_employeeid;

                echo "<div class='local_users_sync_error'>User with ".$field." ".$fieldvalue." already exist at line $this->excel_line_number.</div>";
                $this->errors[] = "User with ".$field." ".$fieldvalue." already exist at line $this->excel_line_number.";
                $this->mfields[] = $field;
                $this->errorcount++;
            }
        }//ended

        if(empty($userexist)){        
            $this->add_newuser_instance_fromhrmssync($excel, $user);
        }
    
    } // end of add_rows function   
    
    private function add_update_rows($excel){
        global $DB, $USER;
        // add or update information       
        $user=$this->preparing_user_object($excel);
        $user_sql = "SELECT id  FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :employeeid) AND deleted = 0";
        $user_object = $DB->get_record_sql($user_sql,  array('username' => $user->username, 'email' => $user->email, 'employeeid' => $user->open_employeeid));
             
        if ($user_object) {
            //-----Update functionality------------------
            $this->update_rows($excel, $user);                               
        } else{              

                $err=$this->specific_costcenter_validation($user);
                if(!$err)             
                    $this->add_newuser_instance_fromhrmssync($excel, $user);            
            
        } // end of else
        
    } // end of add_update_rows method


    public function update_rows($excel, $user){
        global $USER, $DB;
        //---------Updation part------------------------------
        //-----if user exists updating user(mdl_user) record 
        $user_sql = "SELECT username,id FROM {user} WHERE (username = :username OR email = :email OR open_employeeid = :employeeid) AND deleted = 0";
        $user_object = $DB->get_records_sql_menu($user_sql,  array('username' => $excel->username, 'email' => $excel->email, 'employeeid' => $excel->employee_id));
        if(count($user_object) == 1){
            $userid=$user_object[$user->username];
            if($userid){ 
                $user->id = $userid;
                $user->timemodified = time();
                $user->suspended = $this->activestatus;
                $user->idnumber = $excel->employee_id;
                $user->open_costcenterid =$this->open_costcenterid;
                $user->open_departmentid = $this->level1_departmentid;
                $user->open_subdepartment = null;
                $user->phone1 =$excel->mobileno;
                $user->open_state = $excel->state_name;
                $user->usermodified = $USER->id;
                $user->open_group = $excel->level;
                $user->open_client = $excel->client;
                $user->open_team = $excel->team;
                $user->open_grade = $excel->grade ? $excel->grade :null;
                user_update_user($user, false);
                $this->updatedcount++;
            }
        }
    } // end of  update_rows method

    
    
    private function write_warning_db($excel){
        global $DB, $USER;
        if(!empty($this->warnings) && !empty($this->wmfields)){
            $syncwarnings = new \stdclass();
            $today = date('Y-m-d');
            $syncwarnings->date_created = strtotime($today);
            $werrors_list = implode(',',$this->warnings);
            $wmandatory_list = implode(',', $this->wmfields);
            $syncwarnings->error = $werrors_list;
            $syncwarnings->modified_by = $USER->id;
            $syncwarnings->mandatory_fields = $wmandatory_list;
            if (empty($excel->email))
                $syncwarnings->email = '-';
            else
                $syncwarnings->email = $excel->email;
                        
            if (empty($excel->employee_id))
                $syncwarnings->idnumber = '-';
                else
                $syncwarnings->idnumber = $excel->employee_id;
                
            $syncwarnings->firstname = $excel->first_name;
            $syncwarnings->lastname = $excel->last_name;
            $syncwarnings->type = 'Warning';
            $DB->insert_record('local_syncerrors', $syncwarnings);
            //$warningscount++;
        }
        
    } // end of write_warning_db method
    
    
    
    private function update_supervisorid($data){
        global $DB;      
       
            $this->warnings = array();
            $this->mfields = array();
            $this->wmfields = array();
            $linenum = 1;
         // supervisor id check after creating all users
        foreach($data as $excel){
            $linenum++;
            if(!is_object($excel))
                $excel = (object)$excel;
            
            //---to get the costcenter shortname------
            // $this->to_get_the_costcentershortname($excel);
            if(!empty($excel->organization)){
                $this->costcenter_shortname = $excel->organization;
            }
            
            $this->employee_id = $excel->employee_id;

                 
            if($excel->reportingmanager_email!=''){
                $costcenter = $DB->get_field('user', 'open_costcenterid', array('username' => $excel->username));           
                $super_userid = $DB->get_record('user', array('email' => $excel->reportingmanager_email, 'open_costcenterid' => $costcenter));

                if($super_userid){
                    $user_exist = $DB->record_exists('user', array('idnumber'=> $this->employee_id));
                    if ($user_exist) {
                        $userid = $DB->get_field('user', 'id', array('open_employeeid'=>$this->employee_id));
                        $local_user = $DB->get_record('user', array('id'=>$userid));          
                        $local_user->open_supervisorempid = $super_userid->open_employeeid;
                        $local_user->open_supervisorid=$super_userid->id;
                       
                        if(!empty($local_user->id)){
                            $data=$DB->update_record('user', $local_user); 
                        }
                    }
                }else{
                    $strings = new \stdClass();
                    $strings->email = $excel->reportingmanager_email;
                    $strings->line = $linenum;
                    $warningmessage = get_string('nosupervisormailfound','local_users',$strings);
                    $this->errormessage = $warningmessage;
                    echo '<div class=local_users_sync_warning>'.$warningmessage.'</div>';
                    $this->warningscount++; 
                }
            }   
            $this->write_warning_db($excel);
            
        }
    } // end of  update_supervisorid method

}  // end of class

