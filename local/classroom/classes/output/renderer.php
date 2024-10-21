<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This classroom is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This classroom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this classroom.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms 
 * @subpackage local_classroom
 */
namespace local_classroom\output;
require_once($CFG->dirroot . '/local/classroom/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
defined('MOODLE_INTERNAL') || die;

use context_system;
use html_table;
use html_writer;
use local_classroom\classroom;
use plugin_renderer_base;
use user_course_details;
use moodle_url;
use stdClass;
use single_button;
use core_completion\progress;

class renderer extends plugin_renderer_base {
    /**
     * [render_classroom description]
     * @method render_classroom
     * @param  \local_classroom\output\classroom $page [description]
     * @return [type]                                  [description]
     */
    public function render_classroom(\local_classroom\output\classroom $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_classroom/classroom', $data);
    }
    /**
     * [render_form_status description]
     * @method render_form_status
     * @param  \local_classroom\output\form_status $page [description]
     * @return [type]                                    [description]
     */
    public function render_form_status(\local_classroom\output\form_status $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_classroom/form_status', $data);
    }
    /**
     * [render_session_attendance description]
     * @method render_session_attendance
     * @param  \local_classroom\output\session_attendance $page [description]
     * @return [type]                                           [description]
     */
    public function render_session_attendance(\local_classroom\output\session_attendance $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_classroom/session_attendance', $data);
    }
    /**
     * Display the classroom tabs
     * @return string The text to render
     */
    public function get_classroom_tabs() {
        global $CFG, $OUTPUT;
        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';
        // $tabscontent = $this->get_classrooms('-1');
        $context = context_system::instance();
        
        $all_tab=$new_tab=$active_tab=$hold_tab=$cancelled_tab=$completed_tab=false;
        //if(has_capability('local/classroom:view_allclassroomtab', context_system::instance())){
            $all_tab=true;
        //}
        if(has_capability('local/classroom:view_newclassroomtab', context_system::instance())){
            $new_tab=true;
        }
        //if(has_capability('local/classroom:view_activeclassroomtab', context_system::instance())){
            $active_tab=true;
        //}
        if(has_capability('local/classroom:view_holdclassroomtab', context_system::instance())){
            $hold_tab=true;
        }
        //if(has_capability('local/classroom:view_cancelledclassroomtab', context_system::instance())){
            $cancelled_tab=true;
        //}
        //if(has_capability('local/classroom:view_completedclassroomtab', context_system::instance())){
            $completed_tab=true;
        //}
    
        $classroomtabslist = [
            //'classroomtabslist' => $tabscontent,
            'contextid' => $context->id,
            'plugintype' => 'local',
            'plugin_name' =>'classroom',
            'all_tab'=>$all_tab,
            'new_tab'=>$new_tab,
            'active_tab'=>$active_tab,
            'hold_tab'=>$hold_tab,
            'cancelled_tab'=>$cancelled_tab,
            'completed_tab'=>$completed_tab,
            'creataclassroom' => ((has_capability('local/classroom:manageclassroom',
            context_system::instance()) && has_capability('local/classroom:createclassroom',
            context_system::instance())) || is_siteadmin()) ? true : false,
            'unenrolclassroom' => ((has_capability('local/classroom:manageclassroom',
            context_system::instance()) && has_capability('local/classroom:createclassroom',
            context_system::instance())) || is_siteadmin()) ? true : false,
            'unenrolclassroomurl' => new moodle_url('/local/classroom/usersunenrolled.php'),
        ];
        if ((has_capability('local/location:manageinstitute', context_system::instance()) || has_capability('local/location:viewinstitute', context_system::instance()))&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
             $classroomtabslist['location_url']=$CFG->wwwroot.'/local/location/index.php?component=classroom';

        }
        if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
             $classroomtabslist['request_url']=$CFG->wwwroot.'/local/request/index.php?component=classroom';

        }
        return $this->render_from_template('local_classroom/classroomtabs', $classroomtabslist);
    }
    /**
     * [viewclassrooms description]
     * @method viewclassrooms
     * @param  [type]         $stable [description]
     * @return [type]                 [description]
     */
    public function viewclassrooms($stable) {
        global $OUTPUT, $CFG, $DB;
        $systemcontext = context_system::instance();
        $includesfile = false;
        if(file_exists($CFG->dirroot.'/local/includes.php')){
            $includesfile = true;
            require_once($CFG->dirroot.'/local/includes.php');
            $includes = new user_course_details();
            $classroominclude = new \local_classroom\includes();
           
        }
        if ($stable->thead) {
            $classrooms = (new classroom)->classrooms($stable);
            if ($classrooms['classroomscount'] > 0) {
                $table = new html_table();
                $table->head = array('','');
                $table->id = 'viewclassrooms';
                $return = html_writer::table($table);
            } else {
                $return = "<div class='alert alert-info text-center'>" . get_string('noclassrooms', 'local_classroom') . "</div>";
            }
        } else {
            $classrooms = (new classroom)->classrooms($stable);
            $data = array();
            $classroomchunks = array_chunk($classrooms['classrooms'], 2);
            $startTime = microtime(true);
            foreach($classroomchunks as $cr_data) {
                $row = [];
                foreach ($cr_data as $sdata) {
                    $line = array();
                    //-----class room summary image
                    /* if ($sdata->classroomlogo > 0) {
                        $classesimg = (new classroom)->classroom_logo($sdata->classroomlogo);
                        if($classesimg == false){
                            if($classroominclude){
                                $classesimg = $classroominclude->get_classroom_summary_file($sdata); 
                                //$classesimg = $includes->get_classes_summary_files($sdata); 
                            }
                        }
                    } else {
                        if($includesfile){
                                 $classesimg = $classroominclude->get_classroom_summary_file($sdata); 
                                //$classesimg = $includes->get_classes_summary_files($sdata); 
                            }
                    } */
                    $classesimg = (new classroom)->classroom_logo($sdata->classroomlogo);
                    if($classesimg == false){
                        $classesimg = $OUTPUT->image_url('classviewnew', 'local_classroom');
                    } 
                    $classesimg = $classesimg->out(); 
                    //-------data variables
                    $classname = $sdata->name;
                    $classname_string = strlen($classname) > 48 ? substr($classname, 0, 48)."..." : $classname;
                    $usercreated = $sdata->usercreated;
                    
                    $startdate = date("j M 'y", $sdata->startdate);
                    $enddate = date("j M 'y", $sdata->enddate);

                    $description = strip_tags(html_entity_decode($sdata->description));
                    $isdescription = '';
                    if (empty($description)) {
                       $isdescription = false;
                    } else {
                        $isdescription = true;
                        if (strlen($description) > 75) {
                            $decsriptionCut = substr($description, 0, 75);
                            $decsriptionstring = strip_tags(html_entity_decode($decsriptionCut),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
                        }else{
                            $decsriptionstring="";
                        }
                    }
                    
                    $enrolled_users = $sdata->enrolled_users;
                    if ($sdata->department == -1) {
                        $departmentname = 'All';
                        $departmenttitle = 'All departments';
                    } else {
                        $classroomdepartment = $DB->get_fieldset_select('local_costcenter', 'fullname', " CONCAT(',',$sdata->department,',') LIKE CONCAT('%,',id,',%') ", array()); //FIND_IN_SET(id, '$sdata->department')
                        $departmentname = (count($classroomdepartment)>1) ? $classroomdepartment[0].'...' : $classroomdepartment[0];
                        $departmenttitle = implode(', ', $classroomdepartment);
                    }
                    switch($sdata->status) {
                        case CLASSROOM_NEW:
                           if(has_capability('local/classroom:view_newclassroomtab', context_system::instance())){
                                $line ['classroomstatusclass'] = 'classroomnew';
                                $line ['crstatustitle'] = get_string('newclasses', 'local_classroom');
                            }
                        break;
                        case CLASSROOM_ACTIVE:
                           //if(has_capability('local/classroom:view_activeclassroomtab', context_system::instance())){ 
                                $line ['classroomstatusclass'] = 'classroomactive';
                                $line ['crstatustitle'] = get_string('activeclasses', 'local_classroom');
                           //}
                        break;
                        case CLASSROOM_HOLD:
                           if(has_capability('local/classroom:view_holdclassroomtab', context_system::instance())){ 
                                $line ['classroomstatusclass'] = 'classroomhold';
                                $line ['crstatustitle'] = get_string('holdclasses', 'local_classroom');
                           }
                        break;
                        case CLASSROOM_CANCEL:
                            //if(has_capability('local/classroom:view_cancelledclassroomtab', context_system::instance())){ 
                                $line ['classroomstatusclass'] = 'classroomcancelled';
                                $line ['crstatustitle'] = get_string('cancelledclasses', 'local_classroom');
                            //}
                        break;
                        case CLASSROOM_COMPLETED:
                          //if(has_capability('local/classroom:view_completedclassroomtab', context_system::instance())){  
                            $line ['classroomstatusclass'] = 'classroomcompleted';
                            $line ['crstatustitle'] = get_string('completedclasses', 'local_classroom');
                          //}
                        break;
                    }
                    $classroom_actionstatus=$this->classroom_actionstatus_markup($sdata);
                    $line ['seatallocation'] = empty($sdata->capacity)?'N/A':$sdata->capacity;
                    $line ['classesimg'] = $classesimg;
                    $line ['classname'] = $classname;
                    $line ['classname_string'] = $classname_string;
                    $line ['usercreated'] = fullname($user);
                    $line ['startdate'] = $startdate;
                    $line ['enddate'] = $enddate;
                    $line ['description'] =  strip_tags(html_entity_decode($sdata->description));
                    $line ['descriptionstring'] = $decsriptionstring;
                    $line ['isdescription'] = $isdescription;
                    $line ['classroom_actionstatus'] = array_values(($classroom_actionstatus));
                    $classroomcoursessql = "SELECT c.id, c.fullname
                                              FROM {course} AS c
                                             
                                              JOIN {local_classroom_courses} AS cc ON cc.courseid = c.id
                                             WHERE c.visible = 1 AND cc.classroomid = :classroomid ";

                    $classroomcourses = $DB->get_records_sql($classroomcoursessql,array('classroomid' => $sdata->id),0,2);
                    $line ['courses'] = array();
                    if (!empty($classroomcourses)) {
                        foreach($classroomcourses as $classroomcourse) {
                            $courseslimit = true;
                            $coursename = strlen($classroomcourse->fullname) > 15 ? substr($classroomcourse->fullname, 0, 15)."..." : $classroomcourse->fullname;
                            $line ['courses'][] = array('coursesdata'=>'<a href="' . $CFG->wwwroot .'/course/view.php?id=' . $classroomcourse->id .'" title="' . $classroomcourse->fullname . '">' . $coursename . '</a>');

                        }
                    }
                    $line ['enrolled_users'] = $enrolled_users;
                    $line ['departmentname'] = $departmentname;
                    $line['departmenttitle'] = $departmenttitle;
                    $line ['classroomid'] = $sdata->id;
                    $classroomtrainerssql = "SELECT u.id, u.picture, u.firstname, u.lastname,
                                        u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt, u.email
                                              FROM {user} AS u
                                              JOIN {local_classroom_trainers} AS ct ON ct.trainerid = u.id
                                              WHERE u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND ct.classroomid = :classroomid ";

                    $classroomtrainers = $DB->get_records_sql($classroomtrainerssql,array('classroomid' => $sdata->id),0,2);
                    $line['trainers']  = array();
                    if(!empty($classroomtrainers)) {
                        $trainerslimit = false;
                        foreach($classroomtrainers as $classroomtrainer) {
                            $trainerslimit = true;
                            $trainername = strlen(fullname($classroomtrainer)) > 8 ? substr(fullname($classroomtrainer), 0, 8)."..." : fullname($classroomtrainer);
                            $classroomtrainerpic = $OUTPUT->user_picture($classroomtrainer, array('size' => 35, 'class'=>'trainer_img','link'=>false));
                            $line['trainers'][] = array('classroomtrainerpic' => $classroomtrainerpic, 'trainername' => $trainername, 'trainerdesignation' => '');
                        }
                    }
                    if(count($classroomtrainers) > 2){
                        $trainerslimit = false;
                        $line['moretrainers'] = array_slice($line['trainers'], 0, 2);
                    }

                    $line ['trainerslimit'] = $trainerslimit;
                    $line ['editicon'] = $OUTPUT->image_url('t/edit');
                    $line ['deleteicon'] = $OUTPUT->image_url('t/delete');
                    $line ['assignusersicon'] = $OUTPUT->image_url('t/assignroles');
                     $classroomcompletion_id=$DB->get_field('local_classroom_completion','id',array('classroomid'=>$sdata->id));
                        if(!$classroomcompletion_id){
                            $classroomcompletion_id=0;
                        }

                    $line['classroomcompletion'] = false;
                    $mouse_overicon=false;
                    if ((has_capability('local/classroom:manageclassroom', context_system::instance()) || is_siteadmin())) {
                        $line['action'] = true;
                    }

                    if ((has_capability('local/classroom:editclassroom', context_system::instance()) || is_siteadmin())) {
                            $line ['edit'] =  true;
                            $mouse_overicon=true;
                    }

                    if ((has_capability('local/classroom:deleteclassroom', context_system::instance()) || is_siteadmin())) {
                            $line ['delete'] =  true;
                            $mouse_overicon=true;
                    }
                    if ((has_capability('local/classroom:manageusers', context_system::instance()) || is_siteadmin())) {
                            $line ['assignusers'] =  true;
                            $line ['assignusersurl'] = new moodle_url("/local/classroom/enrollusers.php?cid=".$sdata->id."");
                            $mouse_overicon=true;
                    }
                     if ((has_capability('local/classroom:classroomcompletion', context_system::instance()) || is_siteadmin())) {
                        $line['classroomcompletion'] =  true;
                    }
                    $line['classroomcompletion_id'] = $classroomcompletion_id;
                    $line['mouse_overicon']=$mouse_overicon;
                    $row[] = $this->render_from_template('local_classroom/browseclassroom', $line);
                }
              if (!isset($row[1])) {
                    $row[1] = '';
                }
                $time = number_format((microtime(true) - $startTime), 4);
                $data[] = $row;
            }
            
            $return = array(
                "recordsTotal" => $classrooms['classroomscount'],
                "recordsFiltered" => $classrooms['classroomscount'],
                "data" => $data,
                "time" => $time
            );
        }
        return $return;
    }

    /**
     * [get_classrooms] to get the Clasrooms by status given
     * @method get_classrooms
     * @param  [type]         $stable [description]
     * @return [type]                 [description]
     */
    // public function get_classrooms($status) {
    //     global $OUTPUT;

    //     $options = json_encode(array('targetID' => 'all', 'templateName' => 'local_classroom/classrooms_list', 'methodName' => 'local_classroom_get_classrooms',  'perPage' => 6, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card'));
    //     $dataoptions = json_encode(array('status' => $status));

    //     $context = [
    //                 "targetID" => 'all',
    //                 "options" => $options,
    //                 "dataoptions" => $dataoptions,
    //                 ];
    //     $return = $OUTPUT->render_from_template('local_costcenter/cardPaginate', $context);
    //     return $return;
    // }


    /**
     * [viewclassroomsessions description]
     * @method viewclassroomsessions
     * @param  [type]                $sessionsdata [description]
     * @param  [type]                $stable      [description]
     * @return [type]                             [description]
     */
    public function viewclassroomsessions($sessions,$classroomid) {
        global $OUTPUT, $CFG, $DB,$USER;
        $context = context_system::instance();
            $data = array();
            $createsession = false;
            if (has_capability('local/classroom:createsession', $context)&&(has_capability('local/classroom:manageclassroom', $context))) {
                $createsession = true;
            }
            if ((has_capability('local/classroom:editsession', context_system::instance()) || has_capability('local/classroom:deletesession', context_system::instance())|| has_capability('local/classroom:takesessionattendance', context_system::instance()))&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                $createsession = true;
            }
            foreach ($sessions as $sdata) {
                $line = array();
                $line['cfgwwwroot'] = $CFG->wwwroot;
                $line['id'] = $sdata->id;
                $line['name'] = $sdata->name;
                $line['date'] = date("d/m/Y", $sdata->timestart);
                $line['starttime'] = date("H:i:s", $sdata->timestart);
                $line['endtime'] = date("H:i:s", $sdata->timefinish);
                $link=get_string('pluginname', 'local_classroom');
                if($sdata->onlinesession==1){
                       
                        $moduleids = $DB->get_field('modules', 'id', array('name' =>$sdata->moduletype));
                        if($moduleids){
                            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $sdata->moduleid, 'module' => $moduleids));
                            if($moduleid){
                                $link=html_writer::link($CFG->wwwroot . '/mod/' .$sdata->moduletype. '/view.php?id=' . $moduleid,get_string('join', 'local_classroom'), array('title' => get_string('join', 'local_classroom')));
                                
                                if (!is_siteadmin() && !has_capability('local/classroom:manageclassroom', context_system::instance())) {
                                    $userenrolstatus = $DB->record_exists('local_classroom_users', array('classroomid' => $classroomid, 'userid' => $USER->id));
                                   
                                    if (!$userenrolstatus) {
                                        $link=get_string('join', 'local_classroom');
                            
                                    }
                                }
                                
                            }
                        }   
                }
                $line['link'] = $link;
                $line['room'] = $sdata->room ? $sdata->room : 'N/A';
                
                $countfields = "SELECT COUNT(DISTINCT u.id) ";
                $params['classroomid'] = $classroomid;
                $params['confirmed'] = 1;
                $params['suspended'] = 0;
                $params['deleted'] = 0;
                $sql = " FROM {user} AS u
                        JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                         WHERE u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                            AND u.deleted = :deleted AND cu.classroomid = :classroomid";
                $classroom_totalusers     =$DB->count_records_sql($countfields . $sql, $params);

                //$classroom_totalusers = $DB->count_records('local_classroom_users', array('classroomid' => $classroomid));
                $attendedsessions_users = $DB->count_records('local_classroom_attendance',
                array('classroomid' => $classroomid,
                    'sessionid' =>$sdata->id, 'status' => SESSION_PRESENT));

                

                if(has_capability('local/classroom:manageclassroom', context_system::instance())){
                    if ($sdata->timefinish <= time() && $sdata->attendance_status == 1) {
                        $line['status'] = get_string('completed', 'local_classroom');
                    } else {
                        $line['status'] = get_string('pending', 'local_classroom');
                    }
               
                }else{
                    $attendance_status=$DB->get_field_sql("SELECT status  FROM {local_classroom_attendance} where classroomid = :classroomid and sessionid = :sessionid and userid = :userid and status = :status",array('classroomid' => $classroomid,'sessionid' =>$sdata->id,'userid' => $USER->id,'status' => 1));
                    if ($sdata->timefinish <= time() && $attendance_status == 1) {
                        $line['status'] = get_string('completed', 'local_classroom');
                    } else {
                        $line['status'] = get_string('pending', 'local_classroom');
                    }
                }
               $line['attendacecount'] = $attendedsessions_users. '/' .$classroom_totalusers;
                if($sdata->trainerid){
                     $trainer = $DB->get_record('user', array('id' => $sdata->trainerid));
                     $trainerimg = $OUTPUT->user_picture($trainer, array('size' => 30)) . fullname($trainer);
                     $line['trainer'] =  $trainerimg;
                }else{
                     $line['trainer'] ="N/A";
                }

                $line['uploadattendanceicon'] = $line['assignrolesicon']=$line['deleteicon']=$line['editicon']=$line['action'] = false;
                if ((has_capability('local/classroom:editsession', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                    $editimg = $OUTPUT->image_url('t/edit');
                    $line['editicon'] = $editimg->out_as_local_url();
                }
                if ((has_capability('local/classroom:deletesession', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
               
                    $deleteimg = $OUTPUT->image_url('t/delete');
                    $line['deleteicon'] = $deleteimg->out_as_local_url(); 
                }
                
                if ((has_capability('local/classroom:takesessionattendance', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                   
                    $assignrolesimg = $OUTPUT->image_url('t/assignroles');
                    $line['assignrolesicon'] = $assignrolesimg->out_as_local_url();
                }
                if ((has_capability('local/classroom:takesessionattendance', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                   
                    $takeattendanceimg = $OUTPUT->image_url('t/assignroles');
                    $line['uploadattendanceicon'] = $takeattendanceimg->out_as_local_url();
                }
                if ((has_capability('local/classroom:editsession', context_system::instance()) || has_capability('local/classroom:deletesession', context_system::instance())|| has_capability('local/classroom:takesessionattendance', context_system::instance()))&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                    $line['action'] = true;
                }
                $data[] = $line;
            }
        return array('createsession' => $createsession,'data' => $data);
    }

 /**
     * [viewclassroomfeedbacks description]
     * @method viewclassroomfeedbacks
     * @param  [type]                   $classroomid [description]
     * @param  [type]                   $stable      [description]
     * @return [type]                                [description]
     */
    public function viewclassroomfeedbacks($feedbacks,$classroomid) {
        global $OUTPUT, $CFG, $PAGE, $DB, $USER;
        $systemcontext = context_system::instance();
        $exist = $DB->record_exists('local_classroom',array('id'=>$classroomid,'trainingfeedbackid'=>0));
        $exist_with_tr_fd = $DB->count_records_sql("SELECT count(id) as total FROM {local_classroom_trainers} where classroomid = :classroomid AND feedback_id>0",array('classroomid' => $classroomid));
        $exist_with_tr = $DB->count_records('local_classroom_trainers',array('classroomid'=>$classroomid));
        $createfeedback = false;
        if ((has_capability('local/classroom:createfeedback', $systemcontext)) && (has_capability('local/classroom:manageclassroom', $systemcontext)) && ($exist || $exist_with_tr_fd!=$exist_with_tr)) {    
            $createfeedback = true;
        }
        $data = array();
        foreach ($feedbacks as $sdata) {
             $classroomtrainerssql = "SELECT CONCAT(u.firstname, ' ', u.lastname) AS fullname FROM {user} AS u JOIN {local_classroom_trainers} AS ct ON ct.trainerid = u.id
                    WHERE ct.classroomid = :classroomid AND ct.feedback_id=:feedbackid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
                $params = array();
                $params['classroomid'] = $classroomid;
                $params['feedbackid'] =  $sdata->id;
            $classroomtrainer = $DB->get_field_sql($classroomtrainerssql, $params);

            $line = array();
            if(has_capability('local/classroom:createfeedback', $systemcontext)){
                $feedbackview = true;
            }else{
                $feedbackview = false;
            }
            $line['cfgwwwroot'] = $CFG->wwwroot;
            $line['id'] = $sdata->id;
            $line['name'] = $sdata->name;
            $line['feedbackview'] = $feedbackview;

            if($sdata->evaluationtype==1){
                $feedbacktype = get_string('training_feeddback', 'local_classroom');
                $trainer = "N/A";
            }else{
                $feedbacktype = get_string('trainer_feedback', 'local_classroom');
                $trainer = $classroomtrainer;
            }

            $line['feedbacktype'] = $feedbacktype;
            $line['trainer'] = $trainer;
            
            $countfields = "SELECT COUNT(DISTINCT u.id) ";
            $params['classroomid'] = $classroomid;
            $params['confirmed'] = 1;
            $params['suspended'] = 0;
            $params['deleted'] = 0;
            $mainsql=$sql = " FROM {user} AS u
                    JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                     WHERE u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                        AND u.deleted = :deleted AND cu.classroomid = :classroomid";
            $total_count     =$DB->count_records_sql($countfields . $sql, $params);

            //$total_count=$DB->count_records_sql("SELECT count(id) as total FROM {local_classroom_users} lcu where lcu.classroomid=:classroomid ",array('classroomid' => $classroomid));

            if($sdata->evaluationtype==1){
                 $sql.=" AND trainingfeedback = :trainingfeedback";
                 $params['trainingfeedback'] = 1;
                 $submitted_count     =$DB->count_records_sql($countfields . $sql, $params);
                 //$submitted_count=$DB->count_records_sql("SELECT count(id) as total FROM {local_classroom_users} where classroomid = :classroomid AND trainingfeedback = :trainingfeedback",array('trainingfeedback' => 1, 'classroomid' => $classroomid));
            }else{
                $submitted_count=$DB->count_records_sql("SELECT count(fb.id) as total FROM {local_classroom_trainerfb} as fb JOIN {local_classroom_trainers} as f ON f.id=fb.clrm_trainer_id where f.classroomid=:classroomid AND f.feedback_id=:id",array('id' => $sdata->id,'classroomid' => $classroomid));
            }

            $line['submittedcount'] = "$submitted_count/$total_count";

            if(!has_capability('local/classroom:manageclassroom', context_system::instance())){
                if($sdata->evaluationtype==1){
                    $sql=$mainsql;
                    
                    $sql.=" AND trainingfeedback = :trainingfeedback and userid= :userid";
                    $params['trainingfeedback'] = 1;
                    $params['userid'] = $USER->id;
                    $submitted_count     =$DB->count_records_sql($countfields . $sql, $params);
                    
                    //$submitted_count=$DB->count_records_sql("SELECT count(id) as total FROM {local_classroom_users} where classroomid = :classroomid AND trainingfeedback = :trainingfeedback and userid= :userid",array('classroomid' => $classroomid,'trainingfeedback' => $trainingfeedback,'userid' => $USER->id));
                }else{
                    $submitted_count=$DB->count_records_sql("SELECT count(fb.id) as total FROM {local_classroom_trainerfb} as fb JOIN {local_classroom_trainers} as f ON f.id=fb.clrm_trainer_id where f.classroomid =:classroomid AND f.feedback_id= :id and fb.userid = :userid",array('classroomid' => $classroomid,'id' => $sdata->id,'userid' => $USER->id));
                }
                $params = array('classroomid'=>$classroomid, 'userid'=>$USER->id);
                $enrolled = $DB->record_exists('local_classroom_users', $params);
                if($enrolled){
                    $line['is_enrolled'] = true;
                }else{
                    $line['is_enrolled'] = false;
                }
                if($submitted_count==0){
                    $line['url'] = 'complete';
                    $line['string'] = true;
                }else{
                    $line['url'] = 'show_entries';
                    $line['string'] = false;
                }
            }elseif(has_capability('local/classroom:manageclassroom', context_system::instance())){
                $line['url'] = 'show_entries';
                $line['string'] = false;
                $line['is_enrolled'] = true;
            }else{
                $line['url'] = $classroom_evaluationtypes[$sdata->evaluationtype];
                $line['string'] = false;
                $line['is_enrolled'] = false;
            }

            $line['deleteicon']=$line['preview']=$line['editicon']=$line['action'] = false;
            if ((has_capability('local/classroom:editfeedback', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {

                $editimg = $OUTPUT->image_url('t/edit');
                $line['editicon'] = $editimg->out_as_local_url();
            }
            // if ((has_capability('local/classroom:editclassroom', context_system::instance()) || is_siteadmin())) {
            //     $preview = $OUTPUT->image_url('t/preview');
            //     $line['preview'] = $preview->out_as_local_url();
            // }
            if ((has_capability('local/classroom:deletefeedback', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()) )) {
               
                $deleteimg = $OUTPUT->image_url('t/delete');
                $line['deleteicon'] = $deleteimg->out_as_local_url();
            }
            if ((has_capability('local/classroom:editfeedback', context_system::instance()) || has_capability('local/classroom:deletefeedback', context_system::instance()))&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                $line['action'] = true;
            }
            $data[] = $line;
        }
        return array('createfeedback' => $createfeedback,'data' => $data);
    }


    /**
     * [viewclassroomcourses description]
     * @method viewclassroomcourses
     * @param  [type]               $classroomid [description]
     * @return [type]                            [description]
     */
    public function viewclassroomcourses($courses, $classroomid) {
        global $OUTPUT, $CFG, $DB,$USER;
        $systemcontext = context_system::instance();
        $data = array();
        $assign_courses = false;
        if (has_capability('local/classroom:createcourse', $systemcontext)&&(has_capability('local/classroom:manageclassroom', $systemcontext))) {
            $assign_courses = true;  
        }


            $selfenrolmenttabcap = false;
            if ((has_capability('local/classroom:deletecourse', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                $selfenrolmenttabcap = true;      
            }
                        
            $courseprogress = new progress();
            foreach ($courses as $sdata) {
                $line = array();
                $line['id'] = $sdata->classroomcourseinstance;
                $line['name'] = $sdata->fullname;
                
               
                if(is_siteadmin() || has_capability('local/classroom:manageclassroom', context_system::instance())) {

                    $countfields = "SELECT cu.id,cu.userid ";
                    $params['classroomid'] = $classroomid;
                    $params['confirmed'] = 1;
                    $params['suspended'] = 0;
                    $params['deleted'] = 0;
                    $sql = " FROM {user} AS u
                    JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                    WHERE u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                    AND u.deleted = :deleted AND cu.classroomid = :classroomid";
                    $enrolledusers = $DB->get_records_sql_menu($countfields . $sql, $params);

                    // $enrolledusers = $DB->get_records_menu('local_classroom_users',  array('classroomid' =>$classroomid), 'id', 'id, userid'); 

                    $course_completions = $DB->get_records_sql_menu("SELECT id,userid  FROM {course_completions} WHERE course = :courseid AND timecompleted IS NOT NULL",array('courseid' => $sdata->id));
                    $result=array_intersect($enrolledusers,$course_completions);
             
                    $line['status'] = count($result) . '/' . count($enrolledusers);

                } else {
                    $completionstatus = $courseprogress->get_course_progress_percentage($sdata);
                    $line['status'] =  $completionstatus !== null ? round($completionstatus,2) : '--';
                }

                $line['action'] = false;
                if ((has_capability('local/classroom:deletecourse', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                   
                    $deleteimg = $OUTPUT->image_url('t/delete');
                    $line['deleteicon'] = $deleteimg->out_as_local_url();
                }
                if ((has_capability('local/classroom:deletecourse', context_system::instance()) || is_siteadmin())&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                    $line['action'] = true;
                }
                $line['linkpath']=$CFG->wwwroot."/course/view.php?id=$sdata->id";
                $data[] = $line;
                
            }
        return array('selfenrolmenttabcap' => $selfenrolmenttabcap,'assigncourses' => $assign_courses,'data' => $data);
    }

    /**
     * Display the classroom view
     * @return string The text to render
     */
    public function get_content_viewclassroom($classroomid) {
        global $OUTPUT, $CFG, $DB, $USER, $PAGE;
       /* $core_component = new core_component();
        $block_content = '';
        $local_pluginlist = $core_component::get_plugin_list('local');
        $block_pluginlist = $core_component::get_plugin_list('block');
        */
        $stable = new stdClass();
        $stable->classroomid = $classroomid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $classroom = (object)(new classroom)->classrooms($stable);
        $daysdiff = 0;
        $unenroll = false;
        $classroom_status = $classroom->status;
        if(!has_capability('local/classroom:view_newclassroomtab', context_system::instance()) && $classroom_status==0){
            print_error("You don't have permissions to view this page.");
        }
        elseif(!has_capability('local/classroom:view_holdclassroomtab', context_system::instance())&& $classroom_status==2){
            print_error("You don't have permissions to view this page.");
        }
        if(empty($classroom)) {
            print_error("Classroom Not Found!");
        }
        if (!has_capability('local/classroom:manageclassroom', context_system::instance()) && !is_siteadmin()
            && !has_capability('local/classroom:manage_multiorganizations', context_system::instance())
            && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())&& !has_capability('local/classroom:manage_owndepartments', context_system::instance())
                 && !has_capability('local/costcenter:manage_owndepartments', context_system::instance()) && !has_capability('local/classroom:trainer_viewclassroom', context_system::instance())) {

            $now = time(); // or your date as well
            $your_date = $classroom->startdate;
            $datediff = $now - $your_date;

            $daysdiff=round($datediff / (60 * 60 * 24));

            $exists=$DB->record_exists('local_classroom_users',  array('classroomid'=>$classroomid,'userid'=>$USER->id));
            if($exists){
                $unenroll = true;
            }
            // if(!$exists){
            //     print_error("You don't have permissions to view this page.");
            // }else{
            //     $unenroll=true;
            // }
        }
        if(file_exists($CFG->dirroot.'/local/includes.php')){
            require_once($CFG->dirroot.'/local/includes.php');
        }
        $includes = new user_course_details();
        $classroominclude = new \local_classroom\includes();
        if ($classroom->classroomlogo > 0){
            $classroom->classroomlogoimg = (new classroom)->classroom_logo($classroom->classroomlogo);
            if($classroom->classroomlogoimg == false){
                //$classroom->classroomlogoimg = $includes->get_classes_summary_files($classroom); 
                $classroom->classroomlogoimg  = $classroominclude->get_classroom_summary_file($classroom);
            }
        } else {
            $classroom->classroomlogoimg  = $classroominclude->get_classroom_summary_file($classroom);
        }
        if ($classroom->instituteid > 0) {
            $classroom->classroomlocation = $DB->get_field('local_location_institutes', 'fullname', array('id' => $classroom->instituteid));
        } else {
            $classroom->classroomlocation = 'N/A';
        }

        if ($classroom->department == -1) {
            $classroom->classroomdepartment = 'All';
            $classroom->classroomdepartmenttitle = 'All';
        } else {
            $classroomdepartment = $DB->get_fieldset_select('local_costcenter', 'fullname', " CONCAT(',',$classroom->department,',') LIKE CONCAT('%,',id,',%') ", array());//FIND_IN_SET(id, '$classroom->department')
            $classroom->classroomdepartment =  (count($classroomdepartment)>1) ? $classroomdepartment[0].'...' : $classroomdepartment[0];
            $classroom->classroomdepartmenttitle = implode(', ', $classroomdepartment);
        }

        $classroomtrainerssql = "SELECT u.id, u.picture, u.firstname, u.lastname,
                                        u.firstnamephonetic, u.lastnamephonetic, u.middlename,
                                        u.alternatename, u.imagealt, u.email
                                   FROM {user} AS u
                                   JOIN {local_classroom_trainers} AS ct ON ct.trainerid = u.id
                                  WHERE u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND ct.classroomid = $classroomid";

        $classroomtrainers = $DB->get_records_sql($classroomtrainerssql);
        $totalclassroomtrainers = count($classroomtrainers);
        $classroom->trainerpagination = false;
        if ($totalclassroomtrainers > 3) {
            $classroom->trainerpagination = true;
        }
        $classroom->trainers  = array();
        if (!empty($classroomtrainers)) {
            foreach($classroomtrainers as $classroomtrainer) {
                $classroomtrainerpic = $OUTPUT->user_picture($classroomtrainer, array('size' => 50, 'class'=>'trainerimg','link'=>false));
                $classroomtrainername = strlen(fullname($classroomtrainer)) > 10 ? substr(fullname($classroomtrainer), 0, 10)."..." : fullname($classroomtrainer);
                $classroom->trainers[] = array('classroomtrainerpic' => $classroomtrainerpic, 'trainername' => $classroomtrainername, 'trainerdesignation' => 'Trainer', 'traineremail' => $classroomtrainer->email);
            }
        }
        $return="";
        $classroom->userenrolmentcap = (has_capability('local/classroom:manageclassroom', context_system::instance())&&has_capability('local/classroom:manageusers', context_system::instance()) && $classroom->status == 0) ? true : false;
        $classroom->selfenrolmentcap = false;
        if($classroom->open_prerequisites){//added by anusha for prerequisites
                $prerequisiteslist = $DB->get_records_sql_menu("SELECT c.id,c.fullname  FROM {course} c WHERE  c.id IN ({$classroom->open_prerequisites}) ");
                
                foreach($prerequisiteslist as $key => $prerequisites){
                    
                    $id=$USER->id;
                    $completionss=$DB->get_record_sql("SELECT cc.id FROM {course_completions} cc WHERE cc.course = $key AND userid = $id AND cc.timecompleted IS NOT NULL");   

                }
                $coursename = array();
                foreach ($prerequisiteslist as $key => $course) {

                    $id=$USER->id;
                    $completionss=$DB->get_field_sql("SELECT cc.id FROM {course_completions} cc WHERE cc.course = $key AND userid = $id AND cc.timecompleted IS NOT NULL");

                   if($completionss) {

                         $coursecompleted = true;

                    } else {

                         $coursecompleted = false;
                    }

                    $coursename['coursename'] = $course;
                    $systemcontext = context_system::instance();
                     if(!has_capability('local/classroom:view', $systemcontext) || !is_siteadmin()){
                            
                             $coursename['show'] = true;
                        }
                    if(!has_capability('local/classroom:view', $systemcontext) || !is_siteadmin()){



                           $coursename['coursecompleted'] = $coursecompleted;
                        }
                        $coursename['courseid'] = $key;
                    // $coursename['course'] = $config->wwwroot;
                    $coursestatus[] = $coursename;
                }  
                $classroom->iltprerequisites = $coursestatus;
            }
        if (!has_capability('local/classroom:manageclassroom', context_system::instance())) {
            $userenrolstatus = $DB->record_exists('local_classroom_users', array('classroomid' => $classroom->id, 'userid' => $USER->id));

            $return=false;
            if($classroom->id > 0 && $classroom->nomination_startdate!=0 && $classroom->nomination_enddate!=0){
                $params1 = array();
                $params1['classroomid'] = $classroom->id;
                // $params1['nomination_startdate'] = date('Y-m-d H:i',time());
                // $params1['nomination_enddate'] = date('Y-m-d H:i',time());
                $params1['nomination_startdate'] = time();
                $params1['nomination_enddate'] = time();

                $sql1="SELECT * FROM {local_classroom} where id=:classroomid and nomination_startdate<=:nomination_startdate and nomination_enddate >= :nomination_enddate";
               
                $return=$DB->record_exists_sql($sql1,$params1); 

            }elseif($classroom->id > 0 && $classroom->nomination_startdate==0 && $classroom->nomination_enddate==0){
                $return=true;
            }
          
            if ($classroom->status == 1 && !$userenrolstatus && $return) {
                $classroom->selfenrolmentcap = true;
                $url = new moodle_url('/local/classroom/view.php', array('cid' =>$classroom->id,'action' => 'selfenrol'));
                // $prerequisites = $DB->get_record_sql("SELECT lc.open_prerequisites  FROM {local_classroom} lc WHERE  lc.id =:classroomid",array('classroomid'=>$classroom->id));
                
                    if($classroom->open_prerequisites){
                         $user_completedstatus = $this->completed_prereqcourses_ilt_enroluser($classroom->open_prerequisites,$USER->id);
                        if($user_completedstatus){
                            //$classroom->selfenrolmentcap='<a href="javascript:void(0);" class="" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('enroll','local_classroom').'</a>';
                            $classroom->selfenrolmentcap='<a href="javascript:void(0);" class="iltenrolbutton" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><button class="crs_content btn btn-lg btn-primary ng-binding mb-2">'.get_string('enroll','local_classroom').'</button></a>';
               
                        }else{
                            // $classroom->selfenrolmentcap = '<a class="" id="usernotcompleted_prereq" alt = ' . get_string('noenroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' ><button class="cat_btn viewmore_btn">'.get_string('enroll','local_classroom').'</button></a>';

                       
                         $open_prerequisite = $classroom->open_prerequisites;
                        
                          if($open_prerequisite){
                            
                            if ($prerequisitecourse_list = $DB->get_records_sql_menu("SELECT c.id, c.fullname as prerequisite FROM {course} c where c.id in ($classroom->open_prerequisites)") ) {
                            $courses = [];
                            foreach($prerequisitecourse_list AS $courseid => $coursename){
                                $userid = $USER->id;
                                $course_completions_sql = "SELECT id FROM {course_completions} WHERE course = {$courseid} AND userid = {$userid} ";
                                $course_completions = $DB->get_record_sql($course_completions_sql);

                                 if($course_completions){
                                    $courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-check text-success icon','title' => get_string('completed','local_classroom')));
                                 } else {
                                    $courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-times text-danger icon','title' => get_string('notcompleted','local_classroom')));
                                 }  

                             }
                            $prerequisitecourse = implode('<br>',$courses);

                           
                           $prerequisite_course = $prerequisitecourse;
                            
                       } else { 
                          $prerequisite_course = 'N/A';
                     }
                    
                  } else if($open_prerequisite == NULL || $open_prerequisite == ''){
                     $prerequisite_course = 'N/A';
                  }
                            
                  $classroom->selfenrolmentcap = html_writer::link('javascript:void(0)', html_writer::tag('button', get_string('enroll','local_classroom'), array('class' => 'crs_content btn btn-lg btn-primary ng-binding mb-2' )) , array('title' => get_string('enroll','local_classroom'), 'alt' => get_string('enroll','local_classroom'),'class'=>'iltenrolbutton','onclick' =>'(function(e){ require("local_classroom/classroom").prerequisite_info({classroomname:'.json_encode($classroom->name).', classroomprerequisite:'.json_encode($prerequisite_course).' }) })(event)'));
                 }
                        
                }else{
                        //$classroom->selfenrolmentcap='<a href="javascript:void(0);" class="" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('enroll','local_classroom').'</a>';
                        $classroom->selfenrolmentcap='<a href="javascript:void(0);" class="iltenrolbutton" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><button class="crs_content btn btn-lg btn-primary ng-binding mb-2">'.get_string('enroll','local_classroom').'</button></a>';
               
               }
                  
                     
            }
                $classroom_capacity_check=(new classroom)->classroom_capacity_check($classroomid);
                if($classroom_capacity_check&&$classroom->status == 1 && !$userenrolstatus){
                   // $classroom->selfenrolmentcap=get_string('capacity_check', 'local_classroom');
                       if($classroom->open_prerequisites){
                         $user_completedstatus = $this->completed_prereqcourses_ilt_enroluser($classroom->open_prerequisites,$USER->id);
                        if($user_completedstatus){
                            $classroom->selfenrolmentcap='<a href="javascript:void(0);" class="" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><button class="crs_content btn btn-lg btn-primary ng-binding mb-2">'.get_string('enroll','local_classroom').'</button></a>';
                        }else{
                         $open_prerequisite = $classroom->open_prerequisites;
                        
                          if($open_prerequisite){
                            
                            if ($prerequisitecourse_list = $DB->get_records_sql_menu("SELECT c.id, c.fullname as prerequisite FROM {course} c where c.id in ($classroom->open_prerequisites)") ) {
                            $courses = [];
                            foreach($prerequisitecourse_list AS $courseid => $coursename){
                                $userid = $USER->id;
                                $course_completions_sql = "SELECT id FROM {course_completions} WHERE course = {$courseid} AND userid = {$userid} ";
                                $course_completions = $DB->get_record_sql($course_completions_sql);

                                 if($course_completions){
                                    $courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-check text-success icon','title' => get_string('completed','local_classroom')));
                                 } else {
                                    $courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-times text-danger icon','title' => get_string('notcompleted','local_classroom')));
                                 }  

                             }
                            $prerequisitecourse = implode('<br>',$courses);

                           
                           $prerequisite_course = $prerequisitecourse;
                            
                       } else { 
                          $prerequisite_course = 'N/A';
                     }
                    
                  } else if($open_prerequisite == NULL || $open_prerequisite == ''){
                      $prerequisite_course = 'N/A';
                  }
                            
                 $classroom->selfenrolmentcap = html_writer::link('javascript:void(0)', html_writer::tag('button', get_string('enroll','local_classroom'), array('class' => 'crs_content btn btn-lg btn-primary ng-binding mb-2' )) , array('title' => get_string('enroll','local_classroom'), 'alt' => get_string('enroll','local_classroom'),'class'=>'iltenrolbutton','onclick' =>'(function(e){ require("local_classroom/classroom").prerequisite_info({classroomname:'.json_encode($classroom->name).', classroomprerequisite:'.json_encode($prerequisite_course).' }) })(event)'));
                
                  }
                        
                }else{
                    $classroom->selfenrolmentcap='<a href="javascript:void(0);" class="" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><button class="crs_content btn btn-lg btn-primary ng-binding mb-2">'.get_string('enroll','local_classroom').'</button></a>';
               }
  
                        
            }//end of classroom capacity check
            //User grade check check
            if(!empty($USER->open_grade) && $USER->open_grade != ""){
                $sql = "SELECT * FROM {local_classroom} lc WHERE lc.visible = 1 
                    AND (
                        1 = CASE 
                        WHEN lc.open_grade <> -1 THEN 
                            CASE 
                                WHEN CONCAT(',', lc.open_grade, ',') LIKE ? THEN 1
                                ELSE 0
                            END 
                        ELSE 1 
                    END 
                ) AND lc.id = $classroomid";

                $sqlparams[] = "%,$USER->open_grade,%";

                $result = $DB->get_record_sql($sql,$sqlparams);
                if(empty($result)){
                    $classroom->selfenrolmentcap= false;
                }
            }

        }
    
        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';

         $waitinglist_users_tab=$requested_users_tab=$classroomcompletion=$feedback_tab=$user_tab=$course_tab=$session_tab=$action =$edit= $delete =$assignusers=$assignusersurl=false;
        //if(has_capability('local/classroom:viewsession', context_system::instance())){
            $session_tab=true;
            //$classroom->classroomsessions = $this->viewclassroomsessions($classroomid, $stable);
        //}
        //if(has_capability('local/classroom:viewcourse', context_system::instance())){
            $course_tab=true;
            //$classroom->classroomsessions = $this->viewclassroomcourses($classroomid, $stable);
        //}
        if(has_capability('local/classroom:viewusers', context_system::instance())){
            $user_tab=true;
            //$classroom->classroomsessions = $this->viewclassroomusers($classroomid, $stable);
        }
        //if(has_capability('local/classroom:viewfeedback', context_system::instance())){
            $feedback_tab=true;
            //$classroom->classroomsessions = $this->viewclassroomevaluations($classroomid, $stable);
        //}
        if ((has_capability('local/classroom:manageclassroom', context_system::instance()) || is_siteadmin())) {
            $action = true;
        }
        if ((has_capability('local/classroom:classroomcompletion', context_system::instance()) || is_siteadmin())) {
            $classroomcompletion =  true;
        }
        if ((has_capability('local/classroom:editclassroom', context_system::instance()) || is_siteadmin())) {
            $edit =  true;
        }

        if ((has_capability('local/classroom:deleteclassroom', context_system::instance()) || is_siteadmin())) {
            $delete =  true;
        }
        if ((has_capability('local/classroom:manageusers', context_system::instance()) || is_siteadmin())) {
            $assignusers =  true;
            $assignusersurl = new moodle_url("/local/classroom/enrollusers.php?cid=".$classroomid."");
            $downloadusers =  true;
            $downloadurl = new moodle_url("/local/classroom/export/csvfiledata.php?id=".$classroomid."");
        }
        if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
            $requested_users_tab = true;
        }
        $completedwaitingseats=$waitingseats=$waitingseats_progress = 0;

        $seats_sql="SELECT count(distinct(u.id)) FROM {user} AS u
            JOIN {local_classroom_waitlist} AS cu ON cu.userid = u.id
            WHERE cu.classroomid = $classroomid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2 and cu.enrolstatus=0";
        $waitingseats=$DB->count_records_sql($seats_sql) ;


        if ((has_capability('local/classroom:viewwaitinglist_userstab', context_system::instance()) || is_siteadmin())) {
            $waitinglist_users_tab = true;
          
            $seats_sql.=" AND cu.enrolstatus=1 ";
            $completedwaitingseats=$DB->count_records_sql($seats_sql) ;

            if (empty($waitingseats)||$waitingseats==0) {
                $waitingseats_progress = 0;
            } else {
                $waitingseats_progress = round(($completedwaitingseats/$waitingseats)*100);
            }
        }
        $selfenrolmenttabcap = true;
        if (!has_capability('local/classroom:manageclassroom', context_system::instance())) {

                $selfenrolmenttabcap = false;


        }
        $classroom_actionstatus=$this->classroom_actionstatus_markup($classroom,'classroom');
        $totalseats=$DB->get_field('local_classroom','capacity',array('id'=>$classroomid)) ;
        $seats_sql="SELECT count(distinct(u.id)) FROM {user} AS u
                                                JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                                                WHERE cu.classroomid = $classroomid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $allocatedseats=$DB->count_records_sql($seats_sql) ;
        $seats_sql.=" AND cu.completion_status=1 ";
        $completed_seats=$DB->count_records_sql($seats_sql) ;
        if(!empty($classroom->description)){
            $description = strip_tags(html_entity_decode($classroom->description));
        }else{
            $description="";
        }
        $isdescription = '';
        if (empty($description)) {
           $isdescription = false;
           $decsriptionstring="";
        } else {
            $isdescription = true;
            if (strlen($description) > 270) {
                // $decsriptionCut = substr($description, 0, 270);
                // $decsriptionstring =  strip_tags(html_entity_decode($decsriptionCut));
                $decsriptionstring = format_text(strip_tags($description,FORMAT_HTML));
            }else{
                 $decsriptionstring="";
            }
        }

        if (empty($totalseats) || $totalseats==0 || $allocatedseats == 0) {
            $seats_progress = 0;
            $completion_seats_progress = 0;
        } else {
            $seats_progress = round(($allocatedseats/$totalseats)*100);
            
            $completion_seats_progress = round(($completed_seats/$allocatedseats)*100);
        }
        
        $classroomcompletion_id=$DB->get_field('local_classroom_completion','id',array('classroomid'=>$classroomid));
        if(!$classroomcompletion_id){
            $classroomcompletion_id=0;
        }
      
        $classroom_status=(new classroom)->classroom_status_strip($classroomid,$classroom->status);
        
        $systemcontext = context_system::instance();
        $createsession = false;
        if (is_siteadmin() || (has_capability('local/classroom:createsession', $systemcontext)&&(has_capability('local/classroom:manageclassroom', $systemcontext)))) {
            $createsession = true;
        }

        
        $exist = $DB->record_exists('local_classroom',array('id'=>$classroomid,'trainingfeedbackid'=>0));
        $exist_with_tr_fd = $DB->count_records_sql("SELECT count(id) as total FROM {local_classroom_trainers} where classroomid = :classroomid AND feedback_id>0",array('classroomid' => $classroomid));
        $exist_with_tr = $DB->count_records('local_classroom_trainers',array('classroomid'=>$classroomid));
        $createfeedback = false;
        if (is_siteadmin() || ((has_capability('local/classroom:createfeedback', $systemcontext)) && (has_capability('local/classroom:manageclassroom', $systemcontext))) && ($exist || $exist_with_tr_fd!=$exist_with_tr)) {    
            $createfeedback = true;
        }


        $assign_courses = false;
        if (is_siteadmin() || (has_capability('local/classroom:createcourse', $systemcontext)&&(has_capability('local/classroom:manageclassroom', $systemcontext)))) {
            $assign_courses = true;  
        }

        if($action==false&&$classroom->status==1&&$unenroll==true&&$daysdiff<0){
            $action=true;
        }

        $ratings_exist = \core_component::get_plugin_directory('local', 'ratings');
        if($ratings_exist){
            require_once($CFG->dirroot.'/local/ratings/lib.php');
            $display_ratings = display_rating($classroomid, 'local_classroom');
            $display_like = display_like_unlike($classroomid, 'local_classroom');
            $display_review = display_comment($classroomid, 'local_classroom');
        }else{
            $display_ratings = $display_like = null;
        }
        if(!is_siteadmin()) {
             $switchedrole = $USER->access['rsw']['/1'];
            if($switchedrole){
                $userrole = $DB->get_field('role', 'shortname', array('id' => $switchedrole));
            }else{
                $userrole = null;
            }
            if(is_null($userrole) || $userrole == 'user'){
                $certificate_plugin_exist = \core_component::get_plugin_directory('local', 'certificates');
                if($certificate_plugin_exist){
                    if(!empty($classroom->certificateid)){
                        $certificate_exists = true;
                        $sql = "SELECT id 
                                FROM {local_classroom_users}
                                WHERE classroomid = :classroomid AND userid = :userid
                                AND completion_status = :completion_status ";
                        $params['classroomid'] = $classroom->id;
                        $params['userid'] = $USER->id;
                        $params['completion_status'] = 1;
                        $completed = $DB->record_exists_sql($sql, $params);
                        if($completed){
                            $certificate_download = true;
                        }else{
                            $certificate_download = false;
                        }
                        $certificateid = $classroom->certificateid;
                    }
                }
            }
        }

        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
             $uploadattendance = true;
        }
      
        $classroomcontext = [
            'classroomcompletion_id'=>$classroomcompletion_id,
            'classroom' => $classroom,
            'classroomid' => $classroomid,
            'action' => $action,
            'unenroll' => $unenroll,
            'edit' => $edit,
            'createsession' => $createsession,
            'createfeedback' => $createfeedback,
            'assign_courses' => $assign_courses,
            'classroomcompletion'=>$classroomcompletion,
            'delete' => $delete,
            'assignusers' => $assignusers,
            'certificate_download' => $certificate_download,
            'certificate_exists' => $certificate_exists,
            'certificateid' => $certificateid,
            'downloadusers' => $downloadusers,
            'assignusersurl' => $assignusersurl,
            'downloadurl' => $downloadurl,
            'classroom_actionstatus'=>array_values(($classroom_actionstatus)),
            'totalseats'=>empty($totalseats)?'N/A':$totalseats,
            'allocatedseats'=>$allocatedseats,
            'completed_seats'=>$completed_seats,
            'selfenrolmenttabcap'=> $selfenrolmenttabcap,
            'description'=>$description,
            'descriptionstring'=>$decsriptionstring,
            'isdescription'=>$isdescription,
            'seats_progress'=>$seats_progress,
            'completion_seats_progress'=>$completion_seats_progress,
            'feedback_tab'=>$feedback_tab,
            'completion_settings_tab'=>true,
            'target_audience_tab'=>true,
            'requested_users_tab'=>$requested_users_tab,
            'waitinglist_users_tab'=>$waitinglist_users_tab,
            'user_tab'=>$user_tab,
            'course_tab'=>$course_tab,
            'session_tab'=>$session_tab,
            'classname'=>$classroom->name,
            'classname_string'=>$classroom->name,
            'classroom_status'=>$classroom_status,
            'seats_image'=> $OUTPUT->image_url('GraySeatNew', 'local_classroom'),
            'waitingseats'=>$waitingseats,
            'completedwaitingseats'=>$completedwaitingseats,
            'waitingseats_progress'=>$waitingseats_progress,
            'display_ratings' => $display_ratings,
            'display_like' => $display_like,
            'display_review' => $display_review,
            'uploadsessionattendance' => $uploadattendance,
            'cfgwwwroot' => $CFG->wwwroot,
        ];

        $return = $this->render_from_template('local_classroom/classroomContent', $classroomcontext);
        return $return;
    }
    /**
     * [viewclassroomusers description]
     * @method viewclassroomusers
     * @param  [type]             $classroomid [description]
     * @param  [type]             $stable      [description]
     * @return [type]                          [description]
     */
    public function viewclassroomusers($users, $classroomid) {
        global $OUTPUT, $CFG, $DB, $USER;
        $systemcontext = context_system::instance();
        $data = array();
        $assign_users = false;
        if(has_capability('local/classroom:manageusers',  context_system::instance()) && has_capability('local/classroom:manageclassroom',  context_system::instance())){
            $assign_users = true;
        }
        if (is_siteadmin() || (has_capability('local/classroom:createcourse', $systemcontext)&&(has_capability('local/classroom:manageclassroom', $systemcontext)))) {
            $certificate_plugin_exist = \core_component::get_plugin_directory('local', 'certificates');
            if($certificate_plugin_exist){
                $cl_certificateid = $DB->get_field('local_classroom', 'certificateid',array('id' =>$classroomid)); 
                if($cl_certificateid){
                    $mapped_certificate = true;
                }else{
                    $mapped_certificate = false;
                }
            }
        }
        foreach ($users as $sdata) {
            $line = array();
            $line['id'] = $sdata->id;
            $line['certificateid'] = $cl_certificateid;
            $line['moduleid'] = $classroomid;
            $line['userid'] = $sdata->id;
            $line['name'] = $OUTPUT->user_picture($sdata) . ' ' . fullname($sdata);
            $line['employeeid'] = $sdata->open_employeeid;
            $line['email'] = $sdata->email;
            $supervisor = $DB->get_field('user', "concat(firstname,' ',lastname)", array('id' => $sdata->open_supervisorid));
            $line['supervisor'] = !empty($supervisor) ? $supervisor : '--';
            $line['attendedsessions'] = $sdata->attended_sessions . '/' . $sdata->totalsessions;
            $line['hours'] = $sdata->hours;
            $line['completionstatus'] = $sdata->completion_status == 1 ? true : false;
            if($sdata->completion_status == 1){
                $line['downloadcertificate'] = true;
            }else{
                $line['downloadcertificate'] = false;
            }

            $data[] = $line;
        }
        return array('assignusers' => $assign_users,'data' => $data, 'mapped_certificate'=> $mapped_certificate);
    }
    /**
     * [classroom_actionstatus_markup description]
     * @method classroom_actionstatus_markup
     * @param  [type]                        $classroom [description]
     * @return [type]                                   [description]
     */
    public function classroom_actionstatus_markup($classroom,$view="browseclassrooms") {
    global $DB, $PAGE, $OUTPUT;
        if($view=="browseclassrooms"){
        $class="";
        }else{
            $class="course_extended_menu_itemlink";
        }
        $return = array();
        $classroomcourseexist = $DB->record_exists('local_classroom_courses', array('classroomid' => $classroom->id));
        $classroomsessionsexist = $DB->record_exists('local_classroom_sessions', array('classroomid' => $classroom->id));
        $classroomusersexist = $DB->record_exists('local_classroom_users', array('classroomid' => $classroom->id));
        //if ($classroomcourseexist && $classroomsessionsexist && $classroomusersexist && $classroom->status == 0) {
        if ($classroom->status == 0 && has_capability('local/classroom:manageclassroom',  context_system::instance())&&has_capability('local/classroom:publish',  context_system::instance())) {   
            // $url = new moodle_url($PAGE->url, array('cid' => $classroom->id, 'status' => 1, 'action' => 'classroomstatus'));
            // $btn = new single_button($url, '', 'POST', array('class'=>'publich_btn'));
            // $btn->add_confirm_action(get_string('classroom_active_action', 'local_classroom'));
            // $cbutton=str_replace('title=""','title="Publish"',$OUTPUT->render($btn));
            // $return[]= '<div class="publish">'.$cbutton.'</div>';
            $return[]= '<a href="javascript:void(0);" class="'.$class.'" alt = ' . get_string('publish','local_classroom') . ' title = ' .get_string('publish','local_classroom')  . ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:1, id: ' . $classroom->id . ', classroomid: ' . $classroom->id .',actionstatusmsg:\'classroom_active_action\'}) })(event)" ><i class="icon fa fa-share fa-fw" aria-hidden="true" aria-label="" title ="'.get_string('publish','local_classroom').'"></i></a>';
            
        }
        if ($classroom->status == 2 && has_capability('local/classroom:release_hold',  context_system::instance())&&has_capability('local/classroom:manageclassroom',  context_system::instance())) {   
            // $url = new moodle_url($PAGE->url, array('cid' => $classroom->id, 'status' =>0, 'action' => 'classroomstatus'));
            // $btn = new single_button($url, '', 'POST', array('class'=>'publich_btn'));
            // $btn->add_confirm_action(get_string('classroom_release_hold_action', 'local_classroom'));
            // $cbutton=str_replace('title=""','title="Release Hold"',$OUTPUT->render($btn));
            // $return[]= '<div class="publish">'.$cbutton.'</div>';

            $return[]= '<a href="javascript:void(0);" class="'.$class.'" alt = ' . get_string('release_hold','local_classroom') . ' title = ' .get_string('release_hold','local_classroom')  . ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:0, id: ' . $classroom->id . ', classroomid: ' . $classroom->id .',actionstatusmsg:\'classroom_release_hold_action\'}) })(event)" ><i class="icon fa fa-share fa-fw" aria-hidden="true" aria-label="" title ="'.get_string('release_hold','local_classroom').'"></i></a>';
        }
        if($classroom->status == 1) {
            
           if(has_capability('local/classroom:cancel',  context_system::instance())&&has_capability('local/classroom:manageclassroom',  context_system::instance())) {   
                // $url = new moodle_url($PAGE->url, array('cid' => $classroom->id, 'status' => 3, 'action' => 'classroomstatus'));
             
                // $btn = new single_button($url, '', 'POST');
                // $btn->add_confirm_action(get_string('classroom_close_action', 'local_classroom'));
                
                // // $cbutton=str_replace("Close",'<i class="icon fa fa-lock" aria-hidden="true" aria-label="" title="Close"></i>',$OUTPUT->render($btn));
                // $cbutton=str_replace('title=""','title="Cancel"',$OUTPUT->render($btn));
                // $return[]= '<div class="close_btn">'.$cbutton.'</div>';
                $return[]= '<a href="javascript:void(0);" class="'.$class.'" alt = ' . get_string('cancel','local_classroom') . ' title = ' .get_string('cancel','local_classroom')  . ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:3, id: ' . $classroom->id . ', classroomid: ' . $classroom->id .',actionstatusmsg:\'classroom_close_action\'}) })(event)" ><i class="icon fa fa-lock fa-fw" aria-hidden="true" aria-label="" title ="'.get_string('cancel','local_classroom').'"></i></a>';
           }
            
           if(has_capability('local/classroom:hold',context_system::instance())&&has_capability('local/classroom:manageclassroom',  context_system::instance())) {   
                // $url = new moodle_url($PAGE->url, array('cid' => $classroom->id, 'status' => 2, 'action' => 'classroomstatus'));
                // $btn = new single_button($url, '', 'POST');
                // $btn->add_confirm_action(get_string('classroom_hold_action', 'local_classroom'));
                
                // // $cbutton=str_replace("Hold",'<i class="icon fa fa-hand-o-up" aria-hidden="true" aria-label="" title="Hold"></i>',$OUTPUT->render($btn));
                // $cbutton=str_replace('title=""','title="Hold"',$OUTPUT->render($btn));
                // $return[]= '<div class="hold">'.$cbutton.'</div>';
            $return[]= '<a href="javascript:void(0);" class="'.$class.'" alt = ' . get_string('hold','local_classroom') . ' title = ' .get_string('hold','local_classroom')  . ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:2, id: ' . $classroom->id . ', classroomid: ' . $classroom->id .',actionstatusmsg:\'classroom_hold_action\'}) })(event)" ><i class="icon fa fa-hand-o-up fa-fw" aria-hidden="true" aria-label="" title ="'.get_string('hold','local_classroom').'"></i></a>';
           }
            
             
            $sessionnotattendancetaken = $DB->record_exists('local_classroom_sessions', array('classroomid' => $classroom->id, 'attendance_status' => 0));
            if(!$sessionnotattendancetaken && $classroom->enddate <= time() && has_capability('local/classroom:complete',  context_system::instance())&&has_capability('local/classroom:manageclassroom',  context_system::instance())) {
            // if($classroom->enddate <= time() && has_capability('local/classroom:complete',  context_system::instance())&&has_capability('local/classroom:manageclassroom',  context_system::instance())) {    
                // $url = new moodle_url($PAGE->url, array('cid' => $classroom->id, 'status' => 4, 'action' => 'classroomstatus'));
                // $btn = new single_button($url, '', 'POST');
                // $btn->add_confirm_action(get_string('classroom_complete_action', 'local_classroom'));
                // // $cbutton=str_replace("Mark Complete",'<i class="icon fa fa-check" aria-hidden="true" aria-label="" title="Mark Complete"></i>',$OUTPUT->render($btn));
                // $cbutton=str_replace('title=""','title="Mark Complete"',$OUTPUT->render($btn));
                // $return[]= '<div class="complete">'.$cbutton.'</div>';
                $return[]= '<a href="javascript:void(0);" class="'.$class.'" alt = ' . get_string('mark_complete','local_classroom') . ' title = ' .get_string('mark_complete','local_classroom')  . ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:4, id: ' . $classroom->id . ', classroomid: ' . $classroom->id .',actionstatusmsg:\'classroom_complete_action\'}) })(event)" ><i class="icon fa fa-check fa-fw" aria-hidden="true" aria-label="" title ="'.get_string('mark_complete','local_classroom').'"></i></a>';
            }
        }
       
       return $return;
    }

  public function viewclassroomattendance($classroomid, $sessionid = 0) {
        global $PAGE, $OUTPUT, $DB;
        $classroom = new classroom();
        $attendees = $classroom->classroom_get_attendees($classroomid, $sessionid);
        $return = '';
        if (empty($attendees)) {
            $return .= "<div class='alert alert-info'>" . get_string('noclassroomusers', 'local_classroom') . "</div>";
        } else {
            $return .= '<form method="post" id="formattendance" action="' . $PAGE->url . '">';
            $return .= '<input type="hidden" name="action" value="attendance" />';
            $params = array();
            $params['classroomid'] = $classroomid;
            $sqlsessionconcat = '';
            if ($sessionid > 0) {
                $sqlsessionconcat = " AND id = :sessionid";
                $params['sessionid'] = $sessionid;
            }
            $sessions = $DB->get_fieldset_select('local_classroom_sessions', 'id',
                'classroomid = :classroomid ' . $sqlsessionconcat, $params);
            foreach ($attendees as $attendee) {
                if (!$sessionid) {
                    $attendancestatuslist = $DB->get_records_sql('SELECT sessionid, id AS attendanceid, sessionid, status, userid FROM {local_classroom_attendance} WHERE classroomid = :classroomid AND userid = :userid', array('classroomid' => $classroomid, 'userid' => $attendee->id));
                }
                $list = array();
                $list[] = $OUTPUT->user_picture($attendee, array('size' => 30)) .
                fullname($attendee);
                foreach($sessions as $session) {
                    if($sessionid > 0) {
                        $attendanceid = $attendee->attendanceid;
                        $attendancestatus = $attendee->status;
                    } else {
                        $attendanceid = isset($attendancestatuslist[$session]->attendanceid) && $attendancestatuslist[$session]->attendanceid > 0 ? $attendancestatuslist[$session]->attendanceid : 0;
                        $attendancestatus = isset($attendancestatuslist[$session]->status) && $attendancestatuslist[$session]->status > 0 ? $attendancestatuslist[$session]->status : 0;
                    }

                    $encodeddata = base64_encode(json_encode(array(
                            'classroomid' => $classroomid, 'sessionid' => $session,
                            'userid' => $attendee->id, 'attendanceid' => $attendanceid)));
                    $radio = '<input type="hidden" value="' . $encodeddata . '"
                    name="attendeedata[]">';
                    
                    $check_exist=$DB->get_field('local_classroom_attendance','id',array('sessionid'=>$session,'userid'=>$attendee->id));
                    if($check_exist){
                        $checked = '';
                    }else{
                        $checked = 'checked';
                    }
                    
                    if ($attendancestatus == 2) {
                        $checked = '';
                        $status = $sessionid > 0 ? "Absent" : "A";
                        $status = '<span class="tag tag-danger">'.$status.'</span>';
                    } else if ($attendancestatus == 1) {
                        $status = $sessionid > 0 ? "Present" : "P";
                        $checked = 'checked';
                        $status = '<span class="tag tag-success">'.$status.'</span>';
                    } else {
                        $status = $sessionid > 0 ? "Not yet given" : "NY";
                        $status = '<span class="tag tag-warning">'.$status.'</span>';
                    }
                    $radio .= '<input type="checkbox" name="status[' . $encodeddata .']"
                         ' . $checked  .' class="checksingle'.$session.'">';
                    if ($sessionid > 0) {
                        $list[] = $status;
                    } else {
                        //$radio .= "<div>$status</div>";
                    }
                    $list[] = $radio;
                }
                $data[] = $list;
            }
            $table = new html_table();
            $script="";
            if ($sessionid > 0) {
                $table->head = array('Employee', 'Status', 'Attendance<p><input type=checkbox name=checkAll id=checkAll'.$sessionid.'> Select All</p>');
                 $script .= html_writer::script("
                         $('#checkAll$sessionid').change(function () {
                                $('.checksingle$sessionid').prop('checked', $(this).prop('checked'));
                         });        
                     ");
            } else {
                $table->head[] = 'Employee';
                foreach ($sessions as $session) {
                    $table->head[] = 'Session ' . $session.'<p><input type=checkbox name=checkAll id=checkAll'.$session.'> Select All</p>';
                     $script .= html_writer::script("
                         $('#checkAll$session').change(function () {
                                $('.checksingle$session').prop('checked', $(this).prop('checked'));
                         });        
                     ");
                }
            }
            $table->data = $data;
            $return .= html_writer::table($table);
            $return .= '<input type="submit" name="submit" value="Submit">';
            $return .= '<input type="submit" name="reset" value="Reset Selected">';
            $return .= '</form>';
            $return .= "<div id='result'></div>".$script;
           
        }
        return $return;
    }
    //  public function manageclassroomcategories() {
    //     $stable = new stdClass();
    //     $stable->thead = true;
    //     $stable->start = 0;
    //     $stable->length = -1;
    //     $stable->search = '';
    //     $tabscontent = $this->viewclassrooms($stable);
    //     $classroomtabslist = [
    //         'classroomtabslist' => $tabscontent
    //     ];
    //     return $this->render_from_template('local_classroom/classroomtabs', $classroomtabslist);
    // }
    public function viewclassroomlastchildpopup($classroomid){
         global $OUTPUT, $CFG, $DB, $USER, $PAGE;
        $stable = new stdClass();
        $stable->classroomid = $classroomid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $classroom = (new classroom)->classrooms($stable);
        $context = context_system::instance();
        $classroom_status = $DB->get_field('local_classroom','status',array('id' => $classroomid));
        if(!has_capability('local/classroom:view_newclassroomtab', context_system::instance()) && $classroom_status==0){
            print_error("You don't have permissions to view this page.");
        }
        elseif(!has_capability('local/classroom:view_holdclassroomtab', context_system::instance())&& $classroom_status==2){
            print_error("You don't have permissions to view this page.");
        }
        if(empty($classroom)) {
            print_error("Classroom Not Found!");
        }
        if(file_exists($CFG->dirroot.'/local/includes.php')){
            require_once($CFG->dirroot.'/local/includes.php');
        }
        $includes = new user_course_details();
        $classroominclude = new \local_classroom\includes();

        $classesimg = (new classroom)->classroom_logo($sdata->classroomlogo);
        if($classesimg == false){
            $classesimg = $OUTPUT->image_url('classviewnew', 'local_classroom');
        } 
        $classesimg = $classesimg->out(); 
        $classroom->classroomlogoimg  = $classesimg;
        if ($classroom->instituteid > 0) {
            $classroom->classroomlocation = $DB->get_field('local_location_institutes', 'fullname', array('id' => $classroom->instituteid));
        } else {
            $classroom->classroomlocation = 'N/A';
        }


        if ($classroom->department == -1) {
             $classroom->classroomdepartment = 'All';
            $classroom->classroomdepartmenttitle = 'All';
        } else {
            $classroomdepartment = $DB->get_fieldset_select('local_costcenter', 'fullname', " CONCAT(',',$classroom->department,',') LIKE CONCAT('%,',id,',%') ", array());//FIND_IN_SET(id, '$classroom->department')
             $classroom->classroomdepartment =  (count($classroomdepartment)>1) ? $classroomdepartment[0].'...' : $classroomdepartment[0];
            $classroom->classroomdepartmenttitle = implode(', ', $classroomdepartment);
        }

        $classroomtrainerssql = "SELECT u.id, u.picture, u.firstname, u.lastname,
                                        u.firstnamephonetic, u.lastnamephonetic, u.middlename,
                                        u.alternatename, u.imagealt, u.email
                                   FROM {user} AS u
                                   JOIN {local_classroom_trainers} AS ct ON ct.trainerid = u.id
                                  WHERE u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND ct.classroomid = :classroomid";

        $classroomtrainers = $DB->get_records_sql($classroomtrainerssql,array('classroomid' => $classroom->id));
        $totalclassroomtrainers = count($classroomtrainers);
        $classroom->trainerpagination = false;
        if ($totalclassroomtrainers > 3) {
            $classroom->trainerpagination = true;
        }
        $trainers  = array();
        if (!empty($classroomtrainers)) {
            foreach($classroomtrainers as $classroomtrainer) {
                $classroomtrainerpic = $OUTPUT->user_picture($classroomtrainer, array('size' => 50, 'class'=>'trainerimg','link'=>false));
                $trainers[] = array('classroomtrainerpic' => $classroomtrainerpic, 'trainername' => fullname($classroomtrainer), 'trainerdesignation' => 'Trainer', 'traineremail' => $classroomtrainer->email);
            }
        }else{

            $trainers[] = array('classroomtrainerpic' => '', 'trainername' => '', 'trainerdesignation' => '', 'traineremail' => '');
        }
        $return="";
        $classroom->userenrolmentcap = (has_capability('local/classroom:manageusers', context_system::instance()) &&has_capability('local/classroom:manageclassroom', context_system::instance()) && $classroom->status == 0) ? true : false;
    
        $stable = new stdClass();
        $stable->thead = true;
        $stable->start = 0;
        $stable->length = -1;
        $stable->search = '';
        
        $totalseats=$DB->get_field('local_classroom','capacity',array('id'=>$classroomid)) ;
        
            $countfields = "SELECT COUNT(DISTINCT u.id) ";
        $params['classroomid'] = $classroomid;
        $params['confirmed'] = 1;
        $params['suspended'] = 0;
        $params['deleted'] = 0;
        $sql = " FROM {user} AS u
                JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                 WHERE u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                    AND u.deleted = :deleted AND cu.classroomid = :classroomid";
        $allocatedseats     =$DB->count_records_sql($countfields . $sql, $params);

        
        //$allocatedseats=$DB->count_records('local_classroom_users',array('classroomid'=>$classroomid)) ;
        $coursesummary = strip_tags($course->summary,
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false));
        $description = strip_tags(html_entity_decode($classroom->description));
        $isdescription = '';
        if (empty($description)) {
           $isdescription = false;
        } else {
            $isdescription = true;
            if (strlen($description) > 250) {
                $decsriptionCut = substr($description, 0, 250);
                $decsriptionstring =  strip_tags(html_entity_decode($decsriptionCut),array('overflowdiv' => false, 'noclean' => false, 'para' => false));;
            }else{
                $decsriptionstring="";
            }
        }

        if (empty($totalseats)||$totalseats==0) {
            $seats_progress = 0;
        } else {
            $seats_progress = round(($allocatedseats/$totalseats)*100);
        }
        $classroomcontext = [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'startdate' => $classroom->startdate,
            'enddate' => $classroom->enddate,
            'classroomlocation' => $classroom->classroomlocation,
            'classroomdepartment' => $classroom->classroomdepartment,
            'trainers' => $trainers[0],
            'classroomid' => $classroomid,
            'totalseats'=>empty($totalseats)?'N/A':$totalseats,
            'allocatedseats'=>$allocatedseats,
            'description'=>$description,
            'descriptionstring'=>$decsriptionstring,
            'isdescription'=>$isdescription,
            'seats_progress'=>$seats_progress,
            'contextid' => $context->id,
            'linkpath'=>$CFG->wwwroot."/local/classroom/view.php?cid=$classroomid"
        ];
        return $classroomcontext;
        //return $this->render_from_template('local_classroom/classroomview', $classroomcontext);
    }
    /**
     * [viewclassroomcompletion_settings_tab description]
     * @param  [type] $classroomid [description]
     * @return [type]              [description]
     */
    public function viewclassroomcompletion_settings_tab($classroomid) {
        global $OUTPUT, $CFG, $DB,$USER;
         $completion_settings = (new classroom)->classroom_completion_settings_tab($classroomid);

         return $completion_settings;
    }
    public function viewclassroomtarget_audience_tab($classroomid) {
        global $OUTPUT, $CFG, $DB,$USER;
         $completion_settings = (new classroom)->classroomtarget_audience_tab($classroomid);

         return $completion_settings;
    }
    public function view_classroom_sessions($classroomid) {
        global $OUTPUT, $CFG, $DB,$USER;
        $context = context_system::instance();
        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $sessions = (new classroom)->classroomsessions($classroomid,$stable);
        $out="";
        if ($sessions['sessionscount'] > 0) {
                $table = new html_table();
                if ((has_capability('local/classroom:manageclassroom', context_system::instance())|| is_siteadmin())) {
                    $out.='<table style="border-collapse: collapse;"  width="99%">
                            <thead>
                            <tr>
                            <th class="header c0" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('name').'</th>
                            <th class="header c1" style="text-align:center;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('date').'</th>
                            <th class="header c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('type', 'local_classroom').'</th>
                            <th class="header c3" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('room', 'local_classroom').'</th>
                            <th class="header c4" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('status', 'local_classroom').'</th>
                            <th class="header c5 lastcol" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('faculty', 'local_classroom').'</th>
                            </tr>
                            </thead>';
                } else {
                    $out.='<table style="border-collapse: collapse;"  width="99%">
                            <thead>
                            <tr>
                            <th class="header c0" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('name').'</th>
                            <th class="header c1" style="text-align:center;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('date').'</th>
                            <th class="header c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('type', 'local_classroom').'</th>
                            <th class="header c3" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('room', 'local_classroom').'</th>
                            <th class="header c4" style="text-align:left;border: 1px solid #dddddd;padding: 8px;" scope="col">'.get_string('status', 'local_classroom').'</th>
                            </tr>
                            </thead>';
                }
            $out.='<tbody>';
            foreach ($sessions['sessions'] as $sdata) {
                $out.='<tr class="">
                        <td class="cell c0" style="text-align:left;border: 1px solid #dddddd;padding: 8px;">'.$sdata->name.'</td>';
                $out.='<td class="cell c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;">'.date("Y-m-d H:i:s", $sdata->timestart) . ' to ' . date("Y-m-d H:i:s", $sdata->timefinish).'</td>';

                $link=get_string('pluginname', 'local_classroom');
                if($sdata->onlinesession==1){

                        $moduleids = $DB->get_field('modules', 'id', array('name' =>$sdata->moduletype));
                        if($moduleids){
                            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $sdata->moduleid, 'module' => $moduleids));
                            if($moduleid){
                                $link=html_writer::link($CFG->wwwroot . '/mod/' .$sdata->moduletype. '/view.php?id=' . $moduleid,get_string('join', 'local_classroom'), array('title' => get_string('join', 'local_classroom')));

                                if (!has_capability('local/classroom:manageclassroom', context_system::instance())) {
                                    $userenrolstatus = $DB->record_exists('local_classroom_users', array('classroomid' => $classroomid, 'userid' => $USER->id));

                                    if (!$userenrolstatus) {
                                        $link=get_string('join', 'local_classroom');

                                    }
                                }

                            }
                        }
                }
                $out.= '<td class="cell c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;">'.$link.'</td>';
                $room= $sdata->room ? $sdata->room : 'N/A';

                $out.= '<td class="cell c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;">'.$room.'</td>';

                if ($sdata->timefinish <= time() && $sdata->attendance_status == 1) {
                    $out.= '<td class="cell c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;">'.get_string('completed', 'local_classroom').'</td>';
                } else {
                    $out.= '<td class="cell c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;">'.get_string('pending', 'local_classroom').'</td>';
                }
               if ((has_capability('local/classroom:manageclassroom', context_system::instance())|| is_siteadmin())) {
                $trainer = $DB->get_record('user', array('id' => $sdata->trainerid));

                $trainername=  $trainer ? fullname($trainer) : 'N/A';
                
                $out.= '<td class="cell c2" style="text-align:left;border: 1px solid #dddddd;padding: 8px;">'.$trainername.'</td>';
                }
            $out.= '</tr>';

            }
            $out.='</tbody></table>';
        }

        return $out;

    }
      /**
     * [viewclassroomusers description]
     * @method viewclassroomusers
     * @param  [type]             $classroomid [description]
     * @param  [type]             $stable      [description]
     * @return [type]                          [description]
     */
    public function viewclassroomwaitinglistusers($users, $classroomid,$stable) {
        global $OUTPUT, $CFG, $DB;
        $data = array();
        $i=$stable->start+1;
        foreach ($users as $sdata) {
            $line = array();
            $line['id'] = $sdata->id;
            $line['name'] = $OUTPUT->user_picture($sdata) . ' ' . fullname($sdata);
            $line['employeeid'] = $sdata->open_employeeid;
            $line['email'] = $sdata->email;
            $supervisor = $DB->get_field('user', "concat(firstname,' ',lastname)", array('id' => $sdata->open_supervisorid));
            $line['supervisor'] = !empty($supervisor) ? $supervisor : '--';
            $line['sortorder'] = $i;
            $line['enroltype'] = ($sdata->enroltype==1) ? 'Request' : ($sdata->enroltype==2 ? 'My Team' : 'Self' );
            $line['waitingtime'] = $sdata->timecreated ? date('Y-m-d h:i:s A',$sdata->timecreated) : 'N/A';
            $data[] = $line;
            $i++;
        }

        return array('data' => $data);
    }
    public function classroomview_check($classroomid){
        global $OUTPUT, $CFG, $DB, $USER, $PAGE;
        $stable = new stdClass();
        $stable->classroomid = $classroomid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
        $classroom = (new classroom)->classrooms($stable);
        $context = context_system::instance();
        $classroom_status = $DB->get_field('local_classroom', 'status', array('id' => $classroomid));
        if (empty($classroom)) {
            print_error("classroom Not Found!");
        }

        return $classroom;
    }

    /**
     * Renders html to print list of classrooms tagged with particular tag
     *
     * @param int $tagid id of the tag
     * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
     *             are displayed on the page and the per-page limit may be bigger
     * @param int $fromctx context id where the link was displayed, may be used by callbacks
     *            to display items in the same context first
     * @param int $ctx context id where to search for records
     * @param bool $rec search in subcontexts as well
     * @param array $displayoptions
     * @return string empty string if no courses are marked with this tag or rendered list of courses
     */
  public function tagged_classrooms($tagid, $exclusivemode, $ctx, $rec, $displayoptions, $count = 0) {
    global $CFG, $DB, $USER;
    $systemcontext = context_system::instance();
    if ($count > 0)
    $sql =" select count(c.id) from {local_classroom} c ";
    else
    $sql =" select c.* from {local_classroom} c ";

    $where = " where c.id IN (SELECT t.itemid FROM {tag_instance} t WHERE t.tagid = :tagid AND t.itemtype = :itemtype AND t.component = :component)";
    $joinsql = $groupby = $orderby = '';
    if (!empty($sort)) {
      switch($sort) {
        case 'highrate':
        if ($DB->get_manager()->table_exists('local_rating')) {
          $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_classroom' ";
          $groupby .= " group by c.id ";
          $orderby .= " order by AVG(rating) desc ";
        }        
        break;
        case 'lowrate':  
        if ($DB->get_manager()->table_exists('local_rating')) {  
          $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_classroom' ";
          $groupby .= " group by c.id ";
          $orderby .= " order by AVG(rating) asc ";
        }
        break;
        case 'latest':
        $orderby .= " order by c.timecreated desc ";
        break;
        case 'oldest':
        $orderby .= " order by c.timecreated asc ";
        break;
        default:
        $orderby .= " order by c.timecreated desc ";
        break;
        }
    }
    $whereparams = array();
    $conditionalwhere = '';
    if (!is_siteadmin()) {
        $wherearray = org_dep_sql($systemcontext); // get records department wise
        $whereparams = $wherearray['params'];
        $conditionalwhere = $wherearray['sql'];
    }    

    $tagparams = array('tagid' => $tagid, 'itemtype' => 'classroom', 'component' => 'local_classroom');
    $params = array_merge($tagparams, $whereparams);
    if ($count > 0) {
      $records = $DB->count_records_sql($sql.$where.$conditionalwhere, $params);
      return $records;
    } else {
      $records = $DB->get_records_sql($sql.$joinsql.$where.$conditionalwhere.$groupby.$orderby, $params);
    }
    $tagfeed = new \local_tags\output\tagfeed(array(), 'classrooms');
    $img = $this->output->pix_icon('i/course', '');
    foreach ($records as $key => $value) {
      $url = $CFG->wwwroot.'/local/classroom/view.php?cid='.$value->id.'';
      $imgwithlink = html_writer::link($url, $img);
      $modulename = html_writer::link($url, $value->name);
      $testdetails = get_classroom_details($value->id);
      $details = '';
      $details = $this->render_from_template('local_classroom/tagview', $testdetails);
      $tagfeed->add($imgwithlink, $modulename, $details,$rating);
    }
    return $this->output->render_from_template('local_tags/tagfeed', $tagfeed->export_for_template($this->output));
    }
    public function get_userdashboard_classroom($tab, $filter = false){
        $systemcontext = context_system::instance();

        $options = array('targetID' => 'dashboard_classrooms', 'perPage' => 6, 'cardClass' => 'col-md-6 col-12', 'viewType' => 'card');
        $options['methodName']='local_classroom_userdashboard_content_paginated';
        $options['templateName']='local_classroom/userdashboard_paginated';
        $options['filter'] = $tab;
        $options = json_encode($options);
        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
            'targetID' => 'dashboard_classrooms',
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata
        ];
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }
    function completed_prereqcourses_ilt_enroluser($iltcourseids,$userid){
        global $DB;
        if($iltcourseids){

            $pre_courses = explode(',',$iltcourseids);
            $completed=array();
            foreach($pre_courses as $course){
            $sql="SELECT * from {course_completions} where course=$course and userid= {$userid} and timecompleted is not NULL ";
            $check=$DB->get_record_sql($sql);
                if($check){
                  $completed[]=1;
                }else{
                  $completed[]=0;
                }
            }
            if (in_array("0", $completed)){
                return false;
            }else{
                return true;
            }

        }else{
                return false;

            }
     }

    function unenrol_confirm($classroomid )
    {
        $output = html_writer::tag('p', get_string('unenrol_reason','local_courses'));
       $output .= html_writer:: tag('textarea','',array('name' => 'reason', 'class'=>' reason form-control ','size'=>'50', 'required'=>true));
       $output .= html_writer::tag('span', 'Please specify the reason</span>', array('class' => 'unenrolerror', 'style' => 'display:none;color:red;'));
 
       return $output;
 
   }
}
