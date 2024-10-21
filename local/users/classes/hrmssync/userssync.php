<?php
namespace local_users\hrmssync;
use core_text;
// use moodle_url;
class userssync {
    function users_sync() {
    global $DB, $USER, $CFG;
    // require_once($CFG->dirroot . '/user/lib.php');
    require_once($CFG->dirroot . '/local/costcenter/lib.php');
    require_once($CFG->dirroot . '/local/users/classes/hrmssync/parseCSV.php');
    // Instantiate a DateTime with microseconds.
    $d = new \DateTime('NOW');
    $filedate=$d->format('dmY');
    $csv = new \parseCSV();
    $costcenterobj = new \costcenter();

    // $csv->auto('/var/sftp-files/FAEMPDATA_'.$filedate.'.csv');
    $csv->auto('/var/sftp-files/FAEMPDATA_'.$filedate.'.csv');
    
    $path = '/var/sftp-files/FAEMPDATA_'.$filedate.'.csv';
    if(file_exists($path)){
        echo'<div>We have for today. File name is -- FAEMPDATA_'.$filedate.'.csv</div>';
    }else{
        echo "<div>We dont have today's file</div>";
    }

    # Output result.
    if(!empty($csv->data)){
        $inserted = 0;
        $updated = 0;
        $errorcount = 0;
        $warningscount = 0;
        foreach($csv->data as $excel) {
            $excel = (object)$excel;
            $errors = array();
            $warnings = array();
            $mfields = array();
            $wmfields = array();
            $doberror = 0;
            $dojerror = 0;
            $user = new \stdClass();
            
            // check username / employeeid
            if (isset($excel->employeeid)) {
                $excel->employeeid = strtolower($excel->employeeid);
                if (!empty($excel->employeeid)) {
                    //check allowed characters for username
                   if ($excel->employeeid !== core_text::strtolower($excel->employeeid)) {
                       $errors[] = ''.get_string('usernamelowercase').' for  employeeid "' . $excel->employeeid . '" of uploaded sheet.';
                       $mfields[] = 'Employeeid';
                   } else {
                       if ($excel->employeeid !== clean_param($excel->employeeid, PARAM_USERNAME)) {
                           $errors[] = ''.get_string('invalidusername').' for  employeeid "' . $excel->employeeid . '" of uploaded sheet.';
                           $mfields[] = 'Employeeid';
                       }
                   }
                } else {
                       $errors[] = 'Please enter employeeid for  employeeid "' . $excel->employeeid . '" of uploaded sheet.';
                       $mfields[] = 'Employeeid';
                }
            } else {
                $errormessage = 'employeeid column doesn\'t exists in usv file';
                goto errorloop;
            }

            // check firstname
            if (isset($excel->firstname) ) {
                 if (empty($excel->firstname)) {
                     $errors[] = 'Please enter first name for  employeeid "' . $excel->employeeid . '" of uploaded excelsheet.';
                     $mfields[] = 'firstname';
                 }
            } else {
                $errormessage = 'firstname column doesn\'t exists in usv file ';
                goto errorloop;
            }
             // check lastname
            if (isset($excel->lastname)) {
                if (empty($excel->lastname)) {
                   $errors[] = 'Please enter last name for  employeeid "' . $excel->employeeid . '" of uploaded excelsheet.';
                   $mfields[] = 'lastname';
                }
            } else {
                $errormessage = 'lastname column doesn\'t exists in usv file ';
                goto errorloop;
            }
            // check emailid
            if ( isset($excel->emailid) ) {
                if (empty($excel->emailid)) {
                    $errors[] = 'Please enter emailid for  employeeid "' . $excel->employeeid . '" of uploaded excelsheet.';
                    $mfields[] = 'emailid';
                } else {
                    if (! validate_email($excel->emailid)) {
                        $errors[] = 'Invalid emailid entered for  employeeid "' . $excel->employeeid . '" of uploaded excelsheet.';
                        $mfields[] = 'emailid';
                    }
                }
                // if (!$DB->record_exists('user', array('idnumber'=>$excel->employeeid, 'email'=>$excel->emailid))) {
                //    if ($DB->record_exists('user', array('email'=>$excel->emailid))) {
                //         $errors[] = 'This Email ( '.$excel->emailid.'), already exists in the system employeeid "' . $excel->employeeid . '" of uploaded excelsheet is already exists in the system';
                //         $mfields[] = 'emailid';
                //     }
                // }
            } else {
                $errormessage = 'emailid column doesn\'t exists in usv file ';
                goto errorloop;
            }
            
            // check doj
            if (isset($excel->dateofjoining)) {
                if (!empty($excel->dateofjoining)) {
                    if (!empty($excel->dateofjoining)) {
                        $d = explode('-', $excel->dateofjoining);
                        $e = explode(' ', $d[2]);
                        if (!checkdate($d[1], $e[0], $d[0])) {
                            $warnings[] = 'Invalid  date of joining "' . $excel->dateofjoining . '" entered for  employeeid "' . $excel->employeeid . '" of uploaded sheet. Date format should be in the order of "dd-mm-YY" ';
                            $wmfields[] = 'date of joining' ;
                            $dojerror = 1;
                        }
                    }
                }
            }
            $orgid = $DB->get_field('local_costcenter', 'id', array('shortname'=>'Fractal'));
            $user->open_costcenterid = $orgid;
             // check department 
            if (isset($excel->departmentname) ) {
                if ( !empty($excel->departmentname) ) {
                    $depname = trim($excel->departmentname);
                    if($orgid){
                        $departmentid = $DB->get_field('local_costcenter', 'id', array('parentid'=>$orgid, 'shortname'=>$depname));
                        if (empty($departmentid)) {
                            // create department if not exists under the Fractal organization
                            $newdep = new \stdClass();
                            $newdep->fullname = $depname;
                            $newdep->shortname = $depname;
                            $newdep->parentid = $orgid;
                            $newdep->type = 1;
                            $newdep->depth = 2;
                            $newdep->usermodified  = $USER->id;
                            $newdep->timecreated = time();                            
                            if (!$sortorder = $costcenterobj->get_next_child_sortthread($orgid, 'local_costcenter')) {
                                return false;
                            }
                            $newdep->sortorder = $sortorder;
                            $newdeptid = $DB->insert_record('local_costcenter', $newdep);                        
                            $DB->set_field('local_costcenter', 'path', $orgid.'/'.$newdeptid, array('id'=>$newdeptid));   
                            $user->open_departmentid = $newdeptid;
                        } else {
                            $user->open_departmentid = $departmentid;
                        }
                    }
                } 
            }
             // check dob
            if (isset($excel->dateofbirth) ) {
                if (!empty($excel->dateofbirth)) {                    
                    $d = explode('-', $excel->dateofbirth);
                    $e = explode(' ', $d[2]);
                    if (!checkdate($d[1], $e[0], $d[0])) {
                        $warnings[] = 'Invalid  date of birth "' . $excel->dateofbirth . '" entered for  employeeid "' . $excel->employeeid . '" of uploaded sheet. ';
                        $wmfields[] =  'date of birth';
                        $doberror = 1;
                    }
                }
                
            }
            // check grade
            if (isset($excel->gradename)) {
                if (empty($excel->gradename)) {
                    $errors[] = 'Please enter gradename for  employeeid "' . $excel->employeeid . '" of uploaded excelsheet.';
                    $mfields[] = 'gradename';
                }
            } else {
                $errormessage = 'Error in arrangement of columns in uploaded sheet ';
                goto errorloop;
            }
           // check employeestatus
            if (isset($excel->employeestatus)) {
                if (empty($excel->employeestatus)) {
                    $errors[] = 'Please enter employeestatus for  employeeid "' . $excel->employeeid . '" of uploaded excelsheet.';
                    $mfields[] = 'employeestatus';
                } else {
                    if (strtolower($excel->employeestatus) == 'active') {
                        $empstatus = 0;
                    } elseif ( strtolower($excel->employeestatus) == 'inactive' ) {
                        $empstatus = 1;
                    } else {
                        $empstatus = 0;
                    }
                }
            } else {
                $errormessage = 'Error in arrangement of columns in uploaded excelsheet ';
                goto errorloop;
            }
            
            if (count($errors) > 0) {
                // write error message to db and inform admin
                $syncerrors = new \stdClass();
                $syncerrors->date_created = time();
                $errors_list = implode(',',$errors);
                $mandatory_list = implode(',',$mfields);
                $syncerrors->error = $errors_list;
                $syncerrors->modified_by = $USER->id;
                $syncerrors->mandatory_fields = $mandatory_list;
                $syncerrors->email = $excel->emailid;
                $syncerrors->idnumber = $excel->employeeid;
                $syncerrors->open_employeeid = $excel->employeeid;

                $syncerrors->firstname = $excel->firstname;
                $syncerrors->lastname = $excel->firstname;
                $syncerrors->sync_file_name="Employee";
                $syncerrors->type = 'Error';
                $DB->insert_record('local_syncerrors', $syncerrors);
                $errorcount++;
            } else {
                // add or update information
                $user->mnethostid = 1;
                $user->confirmed = 1;
                $user->idnumber = $excel->employeeid;
                $user->open_employeeid = $excel->employeeid;

                if($empstatus == 0){
                    $user->username = $excel->emailid;
                    $user->email = $excel->emailid;
                }elseif($empstatus == 1){
                    $user->username = $excel->emailid.'.'.time();
                    $user->email = $excel->emailid.'.'.time();
                    // $user->idnumber = $excel->employeeid.'.'.time();
                    $user->open_employeeid = $excel->employeeid.'.'.time();
                }
                $user->suspended = $empstatus;
                $user->firstname =$excel->firstname;
                $user->lastname =$excel->lastname;
                $user->open_dob = ($doberror == 0) ? strtotime($excel->dateofbirth) : null;
                $user->open_doj = ($dojerror == 0) ? strtotime($excel->dateofjoining) : null;
                $user->open_gender = $excel->gender;
                $user->country = 'IN';
                $user->open_designation = $excel->designationname;

                $user->open_position = $excel->position;

                $user->open_calendar = $excel->calendarname;
                $user->open_grade = $excel->gradename;
                $user->open_ouname = $excel->ouname;
                $user->open_location = $excel->location;
                $user->city = $excel->location;
                $user->open_careertrack = $excel->careertrack;
                $user->open_costcenter = $excel->costcenter;
                $user->open_subdepart = $excel->subdepartment;
                $user->open_country = $excel->country;
                $user->open_address = $excel->address;
                $user->usermodified = $USER->id;
                $user->auth = "oidc";

                $userexists = $DB->record_exists('user', array('idnumber'=> "$excel->employeeid", 'deleted'=>0));
                if ($userexists) {
                    unset($user->timecreated);
                    $existinguser = $DB->get_record('user',array('idnumber'=> "$excel->employeeid", 'deleted'=>0),'id,username,suspended');
                    $user->id = $existinguser->id;
                    $user->timemodified = time();
                    if($existinguser->suspended == 0){
// try
// {
            $sql_chk = "SELECT * FROM {user} WHERE email = '".$excel->emailid."' AND idnumber != '".$excel->employeeid."'";
            $emailexist = $DB->get_record_sql($sql_chk);
            if ($emailexist) {
                // write error message to db and inform admin
                $syncerrors = new \stdClass();
                $syncerrors->date_created = time();
                // $errors_list = implode(',',$errors);
                $mandatory_list = implode(',',$mfields);
                $syncerrors->error = $excel->emailid.' Emailid is already exists in the system. '.$excel->employeeid;
                $syncerrors->modified_by = $USER->id;
                $syncerrors->mandatory_fields = $mandatory_list;
                $syncerrors->email = $excel->emailid;
                $syncerrors->idnumber = $excel->employeeid;
                $syncerrors->open_employeeid = $excel->employeeid;
                $syncerrors->firstname = $excel->firstname;
                $syncerrors->lastname = $excel->firstname;
                $syncerrors->sync_file_name="Employee";
                $syncerrors->type = 'Error';
                $DB->insert_record('local_syncerrors', $syncerrors);
            }else{
                        $DB->update_record('user', $user);
            }
// } catch (Exception $e){
//  var_dump($e);
// }
                    }
                    $updated++;
                } else {
                    if($user->suspended == 0){  // condition for create only active users
                        $user->password = hash_internal_user_password("Welcome#3");
                        $user->timecreated = time();
                        $user->timemodified = 0;
                        $user_emailexists = $DB->record_exists('user', array('email'=> "$excel->emailid"));
                        if ($user_emailexists) {
                            // write error message to db and inform admin
                            $syncerrors = new \stdClass();
                            $syncerrors->date_created = time();
                            // $errors_list = implode(',',$errors);
                            $mandatory_list = implode(',',$mfields);
                            $syncerrors->error = $excel->emailid.' Emailid is already exists in the system.';
                            $syncerrors->modified_by = $USER->id;
                            $syncerrors->mandatory_fields = $mandatory_list;
                            $syncerrors->email = $excel->emailid;
                            $syncerrors->idnumber = $excel->employeeid;
                            $syncerrors->open_employeeid = $excel->employeeid;
                            $syncerrors->firstname = $excel->firstname;
                            $syncerrors->lastname = $excel->firstname;
                            $syncerrors->sync_file_name="Employee";
                            $syncerrors->type = 'Error';
                            $DB->insert_record('local_syncerrors', $syncerrors);
                        }else{
// try
// {
                            $id = $DB->insert_record('user', $user);
// } catch (Exception $e){
//  var_dump($e);
// }
                            // $id = user_create_user($user);
                            echo "<div class='alert alert-success'>User with Employee id ".$user->idnumber." succcessfully inserted in LMS</div>";
                            $inserted++;
                        }
                    }
                }
                // write warnings to db and inform admin
                if ( count($warnings) > 0 ) {
                    $syncwarnings = new \stdClass();
                    $syncwarnings->date_created = time();
                    $werrors_list = implode(',',$warnings);
                    $wmandatory_list = implode(',', $wmfields);
                    $syncwarnings->error = $werrors_list;
                    $syncwarnings->modified_by = $USER->id;
                    $syncwarnings->mandatory_fields = $wmandatory_list;
                     if (empty($excel->emailid))
                    $syncwarnings->email = '-';
                    else
                    $syncwarnings->email = $excel->emailid;
                    
                    if (empty($excel->employeeid))
                    $syncwarnings->idnumber = '-';
                    else
                    $syncwarnings->idnumber = $excel->employeeid;
                    $syncwarnings->firstname = $excel->firstname;
                    $syncwarnings->lastname = $excel->lastname;
                    $syncwarnings->type = 'Warning';
                    $DB->insert_record('local_syncerrors', $syncwarnings);
                    $warningscount++;
                }
            }
        }
        errorloop:
        // update Reporting to and Functional Reporting to for users
        foreach($csv->data as $excel){
            $excel = (object)$excel;
            $errors = array();
            $warnings = array();
            $mfields = array();
            $wmfields = array();
            if ($DB->record_exists('user', array('idnumber'=>$excel->employeeid, 'deleted'=>0))) {
                // update Reporting to
                if (!empty($excel->reportingto)) {
                    if ($DB->record_exists('user', array('email'=>$excel->reportingto, 'deleted'=>0))) {
                        $reportingtouserid = $DB->get_field('user', 'id', array('email'=>$excel->reportingto, 'deleted'=>0));
                        $DB->set_field('user', 'open_supervisorid', $reportingtouserid, array('idnumber'=>$excel->employeeid));
                    } else {
                        $warnings[] = '"Reporting to" column value "'.$excel->reportingto.'"  entered for employeeid "' . $excel->employeeid . '" of uploaded sheet does not exists in the system.';
                        $wmfields[] = 'reportingto';
                    }
                }

                // update Functional Reporting to
                if (!empty($excel->functionalreportingto)) {
                    if ($DB->record_exists('user', array('email'=>$excel->functionalreportingto, 'deleted'=>0))) {
                        $funcreporting = $DB->get_field('user', 'id', array('email'=>$excel->functionalreportingto, 'deleted'=>0));
                        $DB->set_field('user', 'open_functionalreportingto', $funcreporting, array('idnumber'=>$excel->employeeid));
                    } else {
                        $warnings[] = '"Functional Reporting to" column value "'.$excel->functionalreportingto.'" entered for employeeid "' . $excel->employeeid  . '" of uploaded excelsheet does not exists in the system.';
                        $wmfields[] = 'functionalreportingto';
                    }
                }
            }
                // write warnings to db and inform admin
            if ( count($warnings) > 0 ) {
                $syncwarnings = new \stdClass();
                $syncwarnings->date_created = time();
                $werrors_list = implode(',',$warnings);
                $wmandatory_list = implode(',', $wmfields);
                $syncwarnings->error = $werrors_list;
                $syncwarnings->modified_by = $USER->id;
                $syncwarnings->mandatory_fields = $wmandatory_list;
                 if (empty($excel->emailid))
                $syncwarnings->email = '-';
                else
                $syncwarnings->email = $excel->emailid;
                
                if (empty($excel->employeeid))
                    $syncwarnings->idnumber = '-';
                else
                    $syncwarnings->idnumber = $excel->employeeid;

                $syncwarnings->firstname = $excel->firstname;
                $syncwarnings->lastname = $excel->lastname;
                $syncwarnings->type = 'Warning';
                $DB->insert_record('local_syncerrors', $syncwarnings);
                $warningscount++;
            }
        }
     
        if (!empty($errormessage)) {
            $syncerrors = new \stdClass();
            $syncerrors->date_created = time();
            $syncerrors->error = $errormessage;
            $syncerrors->modified_by = $USER->id;
            $syncerrors->mandatory_fields = '-';
            $syncerrors->email = '-';
            $syncerrors->idnumber = '-';
            $syncerrors->firstname = '-';
            $syncerrors->lastname = '-';
            $syncerrors->sync_file_name="Employee";
            $DB->insert_record('local_syncerrors', $syncerrors);
            $errorcount++;
        }

        $statsobj = new \stdClass();
        $statsobj->timecreated = time();
        $statsobj->timemodified = time();
        $statsobj->newuserscount = $inserted;
        $statsobj->updateduserscount = $updated;
        $statsobj->errorscount = $errorcount;
        $statsobj->warningscount = $warningscount;
        $statsobj->usermodified = 2;

        $DB->insert_record('local_userssyncdata', $statsobj);
        

        $upload_info =  '<div class="critera_error1"><h3 style="text-decoration: underline;">Employee file sync status</h3>
        <div>Total '.$inserted . ' new users added to the system.</div>
        <div>Total '.$updated . ' users details updated.</div>
        <div>Total '.$errorcount . ' errors occured in the sync update.</div></div>
        <div>Total '.$warningscount . ' warnings occured in the sync update.</div>
        ';
        mtrace($upload_info);
    } else {
        echo'<div class="critera_error">File with Employee data is not available for today.</div>';
    }
} // end of synch hrms

}//End of users class.
