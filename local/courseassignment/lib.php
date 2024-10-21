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


use \local_courseassignment\form\grader_action_form as grader_action_form;
//use grade_grade;// Comment <Revathi>

defined('MOODLE_INTERNAL') || die();

/**
 * Function to display the courses form in popup to create course types
 * returns data of the popup 
 */
function local_courseassignment_output_fragment_grade_action($args)
{
    global $CFG, $DB, $PAGE;

    $args = (object) $args;
    $context = $args->context;

    $o = '';
    $formdata = [];
    $params = array();
    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $params = array(
        'moduleid' => $args->moduleid,
        'courseid' => $args->courseid,
        'userid' => $args->userid,
        'method' => $args->method,
        'options' => $args->options,
        'dataoptions' => $args->dataoptions,
        'filterdata' => $args->filterdata,
    );
    $mform = new local_courseassignment\form\grader_action_form(null, $params, 'post', '', null, true, (array)$formdata);
    $mform->set_data($formdata);
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

/**  Function to insert the course completion action in log table
 * reset the course module completion status if method is 'reset'
 * reset the course completion if timecompleted is NOT NULL if method is 'reset'
 * returns 
 */

function insert_graderaction($formdata)
{

    global $USER, $DB,$CFG;
    try {
        $data = new stdClass();
        $data->courseid = $formdata->courseid;
        $data->moduleid = $formdata->moduleid;
        $data->userid = $formdata->userid;
        $data->method = $formdata->method;
        $reasonfield = $data->method . 'reason';
        $data->reason =  $formdata->$reasonfield;
        $data->actiontakenby = $USER->id;
        $data->actiontakenon = time();
        $insert = $DB->insert_record('course_completion_action_log', $data);

        $userinfo = core_user::get_user($data->userid);
        $coursedetails = $DB->get_record('course',  array('id' => $data->courseid));

        $notification = new \local_courses\notification();

        if ($insert && $data->method === 'approve') {  
            $coursedetails->status = 'Approved';
            update_assign_submission($data->moduleid, $data->userid,1);                   
            update_completion_state($data->moduleid, $data->userid, $data->method);           
            insert_grade($data->moduleid, $data->userid,$data->courseid);
            if (class_exists('\local_courses\notification')) {
                $notification->send_course_assignment_gradeaction_notification($coursedetails, $userinfo, 'assign_approve');
            }
        }
        if ($insert && $data->method === 'reject') {
            $coursedetails->status = 'Rejected';
            $coursedetails->reason = $data->reason;
            update_assign_submission($data->moduleid, $data->userid,2);
            reset_certificate($data->courseid, $data->userid);
            if (class_exists('\local_courses\notification')) {
                $notification->send_course_assignment_gradeaction_notification($coursedetails, $userinfo, 'assign_reject');
            }
        }
        if ($insert && $data->method === 'reset') {      
           //mdl_course_modules_completion
            if (update_completion_state($data->moduleid, $data->userid, $data->method)) {
                //mdl_course_completions
                update_assign_submission($data->moduleid, $data->userid,3);
                reset_time_completed($data->courseid, $data->userid);
                reset_course_completion($data->courseid, $data->userid);
                reset_certificate($data->courseid, $data->userid);              
                reset_grades($data->moduleid,$data->userid,$data->courseid);
                $coursedetails->status = 'Reset';
                $coursedetails->reason = $data->reason;

                if (class_exists('\local_courses\notification')) {
                    $notification->send_course_assignment_gradeaction_notification($coursedetails, $userinfo, 'assign_reset');
                }
            }  
        }
        return true;
    } catch (dml_exception $ex) {
        return false;
    }
}

function get_assignment($coursemoduleid, $courseid){
    global $DB;
    $assignment = "";
    $params = array('moduletype'=>'assign', 'coursemoduleid'=>$coursemoduleid , 'courseid' => $courseid);
    $sql = 'SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
            FROM {assign} a, {course_modules} cm, {modules} m
            WHERE m.name=:moduletype AND m.id=cm.module AND cm.instance=a.id AND a.course=:courseid AND cm.id = :coursemoduleid';

    if ($assignment = $DB->get_record_sql($sql, $params)) {      
        return $assignment;
    }
    return $assignment;
}

function reset_grades($moduleid,$userid,$courseid){
    global $DB,$USER,$CFG;
    try {    
        $params = array('moduleid' => $moduleid,'moduletype'=>'assign','courseid' => $courseid , 'userid' => $userid);
        $grades = $DB->get_record_sql("SELECT g.*,gi.iteminstance FROM {grade_items} gi
                                        JOIN {grade_grades} g ON g.itemid = gi.id
                                        JOIN {user} u ON u.id = g.userid	
                                        JOIN {course_modules} cm ON  gi.iteminstance = cm.instance AND	cm.id = :moduleid 											
                                        WHERE g.userid = :userid AND gi.courseid = :courseid AND gi.itemmodule = :moduletype ", $params);
        $cmsinstanceid = $DB->get_field_sql("SELECT cm.instance FROM {course_modules} cm WHERE cm.id = $moduleid");

        if($grades && !is_null($grades->finalgrade)){
            require_once($CFG->libdir.'/gradelib.php');   
            $params['userid'] = $userid;
            $params['rawgrade'] = NULL;
            $params['finalgrade'] = NULL;
            grade_update('mod/assign', $courseid, 'mod', 'assign',  $cmsinstanceid, 0, $params, (array)$grades);
           
            $assigngradesid = $DB->get_field('assign_grades','id',array('assignment' => $grades->iteminstance,'userid' => $userid));
            $grade = new stdClass();            
            if($assigngradesid){
                $grade->id = $assigngradesid;
                $grade->assignment = $grades->iteminstance ;
                $grade->userid = $userid;
                $grade->timecreated = time();
                $grade->timemodified = time();
                $grade->grader = $USER->id;
                $grade->grade = 0;
                $DB->update_record('assign_grades', $grade);
            }
          
        }
    } catch (dml_exception $ex) {
        return false;
    }  
}

function insert_grade($moduleid,$userid,$courseid,$grades=null){
    global $DB, $USER,$CFG;  
    require_once($CFG->dirroot.'/mod/assign/lib.php');   
    require_once($CFG->libdir.'/gradelib.php');    
 
    $cmsinstanceid = $DB->get_field_sql("SELECT cm.instance FROM {course_modules} cm WHERE cm.id = $moduleid");
    $grades = $DB->get_record_sql("SELECT gi.* FROM {grade_items} gi WHERE gi.iteminstance = :iteminstance AND	gi.courseid = :courseid
                                        AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' ", array('iteminstance'=>$cmsinstanceid, 'courseid'=>$courseid )); 	
    $grade = new stdClass();
    $grade->assignment = $cmsinstanceid ;
    $grade->userid = $userid;
    $grade->timecreated = time();
    $grade->timemodified = time();
    $grade->grader = $USER->id;
    $grade->grade = $grades->grademax;
    $grade->locked = 0;
    $grade->mailed = 0;    
    
    $checkIfGraded = $DB->get_record('assign_grades', array('userid'=>$userid, 'assignment'=>$cmsinstanceid ));
    
    if($checkIfGraded){
        $grade->id = $checkIfGraded->id;
        $DB->update_record('assign_grades', $grade);
    } else {
        $DB->insert_record('assign_grades', $grade);
    }
    $params = array('itemname' => $grades->itemname);

    if ($grades->grademax > 0) {
       $params['userid'] = $userid;
       $params['rawgrade'] = $grades->grademax;
       $params['rawgrademax']  = $grades->grademax;
       $params['rawgrademin']  = 0;
       $params['finalgrade'] = $grades->gradepass;
    }
    else if($grades){
      $params['gradetype'] = GRADE_TYPE_VALUE;
    }
    else {
       $params['gradetype'] = GRADE_TYPE_NONE;
    }
    $params['gradetype'] = GRADE_TYPE_VALUE;
    grade_update('mod/assign', $courseid, 'mod', 'assign',  $cmsinstanceid, 0, $params, (array)$grades);
    
}

/* function insert_grade2($moduleid,$userid,$courseid,$grades=null){
    global $DB, $USER,$CFG;  
    require_once($CFG->dirroot.'/mod/assign/lib.php'); 
    require_once($CFG->libdir.'/grade/grade_grade.php');    
 
    $cmsinstanceid = $DB->get_field_sql("SELECT cm.instance FROM {course_modules} cm WHERE cm.id = $moduleid");
    $grades = $DB->get_record_sql("SELECT gi.* FROM {grade_items} gi WHERE gi.iteminstance = $cmsinstanceid AND	gi.courseid = $courseid
                                        AND gi.itemtype = 'mod' AND gi.itemmodule = 'assign' "); 	

    $grade = new grade_grade(array('itemid'=>$grades->id, 'userid'=>$userid));
    $grade->itemid = $grades->id;
    $grade->userid = $userid;
    $grade->rawgrade = $grades->grademax;
    $grade->rawgrademax = $grades->grademax;
    $grade->rawgrademin = $grades->grademin;
    $grade->finalgrade = $grades->grademax;
    $grade->timecreated = time();
    $grade->timemodified = time();
    $grade->usermodified = $USER->id;
    $checkIfGraded = $DB->get_record('grade_grades', array('itemid'=>$grades->id, 'userid'=>$userid));
    if($checkIfGraded){
        $grade->id = $checkIfGraded->id;
        $result = $DB->update_record('grade_grades', $grade);
    } else {
        $result = $DB->insert_record('grade_grades', $grade);
    }
    if ($result && !is_null($grade->finalgrade)) {
        \core\event\user_graded::create_from_grade($grade)->trigger();
    }
    return $result;
} */

/** Update the assign status flag based on action */
function update_assign_submission($moduleid,$userid,$status){
    global $DB;
    try {    
        $sql = " SELECT s.id FROM {assign_submission} s 
                        JOIN {course_modules} cm on s.assignment = cm.instance 
                        WHERE s.status = 'submitted' and s.userid = :userid AND cm.id = :moduleid";                        
        $assignid = $DB->get_field_sql($sql,array('moduleid' => $moduleid , 'userid' => $userid));
        $toupdate = new stdClass();
        $toupdate->id = $assignid;
        $toupdate->assignstatus = $status;
        $DB->update_record('assign_submission', $toupdate);
    } catch (dml_exception $ex) {
        return false;
    }  
}

function reset_course_completion($courseid,$userid){
    global $DB;
    try {    
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        
        $completion = new completion_info($course);
        $DB->delete_records_select('course_modules_completion',
        'coursemoduleid IN (SELECT id FROM {course_modules} WHERE course=?) AND userid=?',
        array($courseid, $userid));
        $DB->delete_records('course_completions', array('course' => $courseid, 'userid' => $userid));
        $DB->delete_records('course_completion_crit_compl', array('course' => $courseid, 'userid' => $userid));
        cache::make('core', 'completion')->purge();
        return true; 
       /*if ($completion->is_enabled() && $completion->is_tracked_user($userid) && $completion->is_course_complete($userid)) {
              
        }else{
            return false;
        } */
    } catch (dml_exception $ex) {
        return false;
    }
}

function reset_certificate( $courseid,$userid){
   global $DB;
    try {
        $fs = get_file_storage();
        $issues = $DB->get_records('tool_certificate_issues', ['moduleid' => $courseid,'userid' => $userid]);
        foreach ($issues as $issue) {
            $fs->delete_area_files(context_system::instance()->id, 'tool_certificate', 'issues', $issue->id);
        }

        $del = $DB->delete_records('tool_certificate_issues', ['moduleid' => $courseid,'userid' => $userid]);
        return true;
    } catch (dml_exception $ex) {
        return false;
    }
}

function update_completion_state($moduleid, $userid, $method)
{
    global $USER, $DB;
    try {
        if ($moduleid && $userid) {
            $toupdate = new stdClass();
            $id = $DB->get_field('course_modules_completion', 'id', array('coursemoduleid' => $moduleid, 'userid' => $userid));
            $toupdate->id = $id;
            if ($method === 'approve') {
                if($DB->record_exists('course_modules_completion', array('coursemoduleid' => $moduleid, 'userid' => $userid),'id')){
                    $toupdate->completionstate = 1; 
                    $toupdate->viewed = 1;  
                    $toupdate->overrideby = $USER->id;
                    $toupdate->timemodified = time();
                    $DB->update_record('course_modules_completion', $toupdate);          
                }else{
                    insert_coursemodule_completion($moduleid, $userid,1);
                }
            }
            if ($method === 'reset') {
                if($DB->record_exists('course_modules_completion', array('coursemoduleid' => $moduleid, 'userid' => $userid),'id')){
                    $toupdate->completionstate = 0;
                    $toupdate->overrideby = $USER->id;
                    $toupdate->timemodified = time();
                    $DB->update_record('course_modules_completion', $toupdate);
                }else{
                    insert_coursemodule_completion($moduleid, $userid,0);
                }
            }
        
            return true;
        }
    } catch (dml_exception $ex) {
        return false;
    }
}

function reset_time_completed($courseid, $userid)
{
    global $DB;
    if ($courseid && $userid) {
        $result = $DB->get_record('course_completions', array('course' => $courseid, 'userid' => $userid));
        if ($result->timestarted != 0 && $result->timecompleted != NULL) {
            $toupdate = new stdClass();
            $id = $DB->get_field('course_completions', 'id', array('course' => $courseid, 'userid' => $userid));
            $toupdate->id = $id;
            $toupdate->timestarted = 0;
            $toupdate->timecompleted = NULL;
            $return = $DB->update_record('course_completions', $toupdate);
        }
    }
    return true;
}

function insert_coursemodule_completion($moduleid, $userid, $completionstate){
    global $USER,$DB;
    $data = new stdClass();
    $data->coursemoduleid = $moduleid;
    $data->userid = $userid;
    $data->completionstate = $completionstate;
    $data->viewed = 0;
    $data->overrideby = $USER->id;
    $data->timemodified = time();
    $insert = $DB->insert_record('course_modules_completion', $data);
    if(!$insert){
        return false;
    }return true;
}


function get_listof_courses_assignments_new($stable, $filtervalues)
{
    global $CFG, $USER, $DB;
    $data = array();

    $filtercoursesparams = array();
    $filterstatusparams = array();
    $filterdateparams = array();
    $sqlparams = array();
    $searchparams = array();
    $count = 0;
    $systemcontext = context_system::instance();
    $sql = "SELECT s.id,MAX(f.id) as fileid, u.id as userid, c.id as courseid,u.firstname, u.lastname, u.email,u.open_employeeid, c.fullname,  cm.id as moduleid,s.assignstatus,
                m.name,MAX(f.itemid) as itemid,u.open_employeeid,s.timemodified, s.timecreated,cmc.timemodified as completiondate,cmc.completionstate as completionstate 
                FROM {course} c 
                JOIN {course_modules} cm on c.id = cm.course 
                JOIN {modules} m on cm.module= m.id 
                JOIN {assign_submission} s on s.assignment = cm.instance AND s.status = 'submitted'
                JOIN {assignsubmission_file} asf ON s.id = asf.submission AND s.assignment = asf.assignment
                JOIN {user} u on u.id = s.userid and u.suspended = 0 and u.deleted = 0
                JOIN {files} f ON f.userid = u.id AND f.component = 'assignsubmission_file' AND f.filearea = 'submission_files' AND f.filename != '.'
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id ";
    $wheresql = " WHERE c.visible = 1 ";
    if(!has_capability('local/courses:manage', $systemcontext) && !is_siteadmin()) {
        $sql .= "  JOIN {context} AS cxt ON cxt.instanceid = c.id
                    JOIN {role_assignments} AS ra ON ra.contextid = cxt.id
                    JOIN {role} as r on r.id=ra.roleid ";
        $wheresql .= " AND cxt.contextlevel = :cxtlevel AND r.shortname=:role AND ra.userid = :userid";
        $params['cxtlevel'] = 50;
        $params['role'] = 'sme';
        $params['userid'] = $USER->id;
    }

    $wheresql .= " AND c.open_costcenterid = :costcenterid AND m.name = :modulename  ";
    $params['costcenterid'] = $USER->open_costcenterid;
    $params['modulename'] = 'assign';
    if (isset($filtervalues->courseid) && trim($filtervalues->courseid) != 0) {
        $wheresql .= " AND c.id = $filtervalues->courseid  ";
    }
  
    if(isset($filtervalues->search_query) && !empty($filtervalues->search_query)){
        $wheresql .= " AND ( u.open_employeeid LIKE :idsearch OR c.fullname LIKE :coursesearch OR u.firstname LIKE :usersearch)";
        $searchparams =  array( 'idsearch' => '%' . trim($filtervalues->search_query) . '%','coursesearch' => '%' . trim($filtervalues->search_query) . '%','usersearch' => '%' . trim($filtervalues->search_query) . '%');
    } 

    if(!empty($filtervalues->courses)){ 
        $filtercourses = explode(',', $filtervalues->courses);
        list($filtercoursessql, $filtercoursesparams) = $DB->get_in_or_equal($filtercourses, SQL_PARAMS_NAMED, 'param', true, false);
        $wheresql .= " AND c.id $filtercoursessql";
    }

    if(isset($filtervalues->status) && $filtervalues->status != 'select' ){
        $filterstatus = $filtervalues->status;   
        if($filterstatus == 0)   { 
            $wheresql .= " AND  (s.assignstatus = 0 AND (cmc.completionstate = :completionstate OR cmc.completionstate IS NULL ) ) ";        
        } else if($filterstatus == 1)   { 
            $wheresql .= " AND  (s.assignstatus = 1 OR (cmc.completionstate != :completionstate AND cmc.completionstate IS NOT NULL ) ) ";        
        } else {
            $wheresql .= " AND s.assignstatus = $filterstatus ";     
        }
   
        $filterstatusparams =  array('completionstate' => 0);
    }
    $filterdata = (array) $filtervalues;

    if($filterdata['fromdate[year]'] && $filterdata['todate[year]']){ 
        
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ; 

        $to_year=$filterdata['todate[year]'];
        $to_month=$filterdata['todate[month]'];
        $to_day=$filterdata['todate[day]'];

        $filter_todate=mktime(0, 0, 0, $to_month, $to_day, $to_year);
        
        $wheresql .=" AND s.timemodified BETWEEN :filter_fromdate AND :filter_todate ";
        $params['filter_fromdate'] = $filter_fromdate;
        $params['filter_todate'] = $filter_todate;
    
    }else if($filterdata['fromdate[year]']){        
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ;        
        $wheresql .=" AND s.timemodified >= :filter_fromdate ";
        $params['filter_fromdate'] = $filter_fromdate;
    } 

    $groupbysql = " GROUP BY u.id,u.firstname, u.lastname ,u.email,c.fullname,  cm.id ,cmc.completionstate,s.assignstatus,
                m.name,u.open_employeeid,s.timemodified, s.timecreated,cmc.timemodified,cmc.completionstate
                ORDER BY  s.id desc";


    $params = array_merge( $params,$searchparams,$filtercoursesparams, $filterstatusparams,$filterdateparams);

    $result = $DB->get_records_sql($sql.$wheresql.$groupbysql , $params, $stable->start, $stable->length);
 
    $count = count($DB->get_records_sql($sql.$wheresql.$groupbysql, $params));
 
    if (!empty($result)) {
        foreach ($result as $res) {
            $array = array();
            $logsql = "SELECT reason FROM {course_completion_action_log} 
                        WHERE courseid = :courseid AND moduleid = :moduleid AND userid = :userid ";
        
            $logsql .= " ORDER BY id desc LIMIT 1";
            $sqlparams = array('courseid' => $res->courseid,'moduleid' => $res->moduleid,'userid' => $res->userid );
            $reason = $DB->get_field_sql($logsql,$sqlparams);          
          
            $array['id'] =  $res->id;
            $array['reason'] = $result[$res->id]->status = '';
            $array['approvestatus'] = true;
            $array['rejectstatus'] = true;
            $array['resetstatus'] = false;
            
            if ( $res->completionstate != 0 && !is_null($res->completionstate)) {//(in_array($res->assignstatus,array(0,1))) ||
                $array['status'] = "Completed";            
                $array['method'] = 'Approved';
                $array['approvestatus'] = false;
                $array['rejectstatus'] = false;
                $array['resetstatus'] = true;
            } else if($res->completionstate == 0 || is_null($res->completionstate)){
                if ($res->assignstatus == 2) {
                    $array['status'] = "Completed";            
                    $array['method'] = 'Reject';                  
                    $array['rejectstatus'] = false;            
                }else if ($res->assignstatus == 3) {
                    $array['status'] = "Completed";            
                    $array['method'] = 'Reset';
                } 
            }
            if (in_array($array['method'], array('Reset', 'Reject'))) {
                $array['reason'] = (!empty($reason)) ? $reason : 'N/A';
            }    
        
            if (empty($array['status'])) {
                $array['status'] = $result[$res->id]->status = 'Pending';
            } 
            $courseurl = new moodle_url('/course/view.php',array('id'=> $res->courseid));             
            $courseurl =$courseurl->out(false);

            $urlparams = array('id' => $res->moduleid,
                               'rownum' => 0,
                               'action' => 'grader',
                               'userid' => $res->userid
                            );   

            $gradeurl = new moodle_url('/mod/assign/view.php', $urlparams);
            $gradeurl =$gradeurl->out(false);
            
            $array['completiondate'] = ($res->completionstate != 0) ? date('d-m-Y h:i:s', $res->completiondate) : 'N/A' ;
            $array['completereason'] = $array['reason'];
            
            $array['reason'] = strlen($array['reason']) > 20 ? substr($array['reason'], 0, 50) . "..." : $array['reason'];
            $filedet =  $DB->get_record_sql("SELECT MAX(f.id) as fileid,f.contextid, f.itemid, f.filename,f.timecreated FROM {files} f JOIN {context} cxt ON cxt.id = f.contextid JOIN {course_modules} as cm ON  cm.course = $res->courseid AND cm.id = cxt.instanceid 
                                                WHERE  component = 'assignsubmission_file' AND filearea = 'submission_files' AND filename != '.' AND f.userid = $res->userid GROUP BY itemid, contextid,filename");
            $array['contextid'] = $filedet->contextid;
            $array['fileid'] = $filedet->fileid;
            $array['itemid'] = $filedet->itemid;
            $array['filename'] = $filedet->filename;
            $array['courseid'] = $res->courseid;
            $array['userid'] = $res->userid;
            $array['firstname'] = $res->firstname;
            $array['lastname'] = $res->lastname;
            $array['email'] = $res->email;
            $array['open_employeeid'] = $res->open_employeeid;
            $array['fullname'] = $res->fullname;
            $array['moduleid'] = $res->moduleid;
            $array['completionstate'] = $res->completionstate;
            $array['name'] = $res->name;
            $array['modifieddate'] = date('d-m-Y h:i:s', $res->timemodified);
            $array['initialdate'] = date('d-m-Y h:i:s', $res->timecreated);          		
            $array['assignurl'] = $CFG->wwwroot.'/pluginfile.php/'.$filedet->contextid.'/assignsubmission_file/submission_files/'.$filedet->itemid.'/'.$filedet->filename.'?forcedownload=1';
            $array['courseurl'] = $courseurl;
            $array['gradeurl'] = $gradeurl;
            $data[] = $array;
        } 
        
    }

    return array('totalrecords' => $count, 'result' => $data);      
    
} 

function get_listof_courses_assignments($stable, $filtervalues)
{
    global $CFG, $USER, $DB;
    $data = array();

    $filtercoursesparams = array();
    $filterstatusparams = array();
    $filterdateparams = array();
    $sqlparams = array();
    $searchparams = array();
    $count = 0;
    $systemcontext = context_system::instance();
    $sql = "SELECT s.id,MAX(f.id) as fileid, u.id as userid, c.id as courseid,u.firstname, u.lastname, u.email,u.open_employeeid, c.fullname,  cm.id as moduleid,
                m.name,MAX(f.itemid) as itemid,u.open_employeeid,s.timemodified, s.timecreated,cmc.timemodified as completiondate,cmc.completionstate as completionstate 
                FROM {course} c 
                JOIN {course_modules} cm on c.id = cm.course 
                JOIN {modules} m on cm.module= m.id 
                JOIN {assign_submission} s on s.assignment = cm.instance AND s.status = 'submitted'
                JOIN {assignsubmission_file} asf ON s.id = asf.submission AND s.assignment = asf.assignment
                JOIN {user} u on u.id = s.userid and u.suspended = 0 and u.deleted = 0
                JOIN {files} f ON f.userid = u.id AND f.component = 'assignsubmission_file' AND f.filearea = 'submission_files' AND f.filename != '.'
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id ";
    $wheresql = " WHERE c.visible = 1 ";
    if(!has_capability('local/courses:manage', $systemcontext) && !is_siteadmin()) {
        $sql .= "  JOIN {context} AS cxt ON cxt.instanceid = c.id
                    JOIN {role_assignments} AS ra ON ra.contextid = cxt.id
                    JOIN {role} as r on r.id=ra.roleid ";
        $wheresql .= " AND cxt.contextlevel = :cxtlevel AND r.shortname=:role AND ra.userid = :userid";
        $params['cxtlevel'] = 50;
        $params['role'] = 'sme';
        $params['userid'] = $USER->id;
    }

    $wheresql .= " AND c.open_costcenterid = :costcenterid AND m.name = :modulename  ";
    $params['costcenterid'] = $USER->open_costcenterid;
    $params['modulename'] = 'assign';
    if (isset($filtervalues->courseid) && trim($filtervalues->courseid) != 0) {
        $wheresql .= " AND c.id = $filtervalues->courseid  ";
    }
  
    if(isset($filtervalues->search_query) && !empty($filtervalues->search_query)){
        $wheresql .= " AND ( u.open_employeeid LIKE :idsearch OR c.fullname LIKE :coursesearch OR u.firstname LIKE :usersearch)";
        $searchparams =  array( 'idsearch' => '%' . trim($filtervalues->search_query) . '%','coursesearch' => '%' . trim($filtervalues->search_query) . '%','usersearch' => '%' . trim($filtervalues->search_query) . '%');
    } 

    if(!empty($filtervalues->courses)){ 
        $filtercourses = explode(',', $filtervalues->courses);
        list($filtercoursessql, $filtercoursesparams) = $DB->get_in_or_equal($filtercourses, SQL_PARAMS_NAMED, 'param', true, false);
        $wheresql .= " AND c.id $filtercoursessql";
    }
 
    if(isset($filtervalues->status) && $filtervalues->status != 'select' ){
        $filterstatus = $filtervalues->status;   
        if($filterstatus == 'pending')   {
            $wheresql .= " AND (cmc.completionstate = :completionstate OR cmc.completionstate IS NULL) ";        
        } 
        if($filterstatus == 'completed')   {
            $wheresql .= " AND cmc.completionstate != :completionstate ";        
        } 
        $filterstatusparams =  array('completionstate' => 0);
    }
    $filterdata = (array) $filtervalues;

    if($filterdata['fromdate[year]'] && $filterdata['todate[year]']){ 
        
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ; 

        $to_year=$filterdata['todate[year]'];
        $to_month=$filterdata['todate[month]'];
        $to_day=$filterdata['todate[day]'];

        $filter_todate=mktime(0, 0, 0, $to_month, $to_day, $to_year);
        
        $wheresql .=" AND s.timemodified BETWEEN :filter_fromdate AND :filter_todate ";
        $params['filter_fromdate'] = $filter_fromdate;
        $params['filter_todate'] = $filter_todate;
    
    }else if($filterdata['fromdate[year]']){        
        $from_year=$filterdata['fromdate[year]'];
        $from_month=$filterdata['fromdate[month]'];
        $from_day=$filterdata['fromdate[day]'];

        $filter_fromdate=mktime(0, 0, 0, $from_month, $from_day, $from_year) ;        
        $wheresql .=" AND s.timemodified >= :filter_fromdate ";
        $params['filter_fromdate'] = $filter_fromdate;
    } 

    $groupbysql = " GROUP BY u.id,u.firstname, u.lastname ,u.email,c.fullname,  cm.id ,cmc.completionstate,
                m.name,u.open_employeeid,s.timemodified, s.timecreated,cmc.timemodified,cmc.completionstate
                ORDER BY  s.id desc";


    $params = array_merge( $params,$searchparams,$filtercoursesparams, $filterstatusparams,$filterdateparams);

    $result = $DB->get_records_sql($sql.$wheresql.$groupbysql , $params, $stable->start, $stable->length);

    $count = count($DB->get_records_sql($sql.$wheresql.$groupbysql, $params));
   
    if (!empty($result)) {
        foreach ($result as $res) {
            $array = array();
            $logsql = "SELECT * FROM {course_completion_action_log} 
                        WHERE courseid = :courseid AND moduleid = :moduleid AND userid = :userid ";
        
            $logsql .= " ORDER BY id desc LIMIT 1";
            $sqlparams = array('courseid' => $res->courseid,'moduleid' => $res->moduleid,'userid' => $res->userid );
            $logresult = $DB->get_record_sql($logsql,$sqlparams);          

            $array['id'] =  $res->id;
            $array['reason'] = $result[$res->id]->status = '';
            $array['resetstatus'] = false;
            $array['rejectstatus'] = true;
            $array['approvestatus'] = true;
            $array['status'] = '';
            if ($res->completionstate != 0) {
                $array['status'] = "Completed";
                $array['method'] = 'Approved';
                $array['resetstatus'] = true;
                $array['rejectstatus'] = false;
                $array['approvestatus'] = false;
            } else if ($logresult) {
                $array['method'] =  ucfirst($logresult->method);
                if ($logresult->method == 'approve') {
                    $array['status'] = "Completed";
                    $array['method'] = 'Approved';
                    $array['resetstatus'] = true; 
                    $array['rejectstatus'] = false;
                    $array['approvestatus'] = false;
                }
                if ($logresult->method == 'reject') {
                    $array['rejectstatus'] = false;
                }

                if (in_array($logresult->method, array('reset', 'reject'))) {
                    $array['reason'] = $logresult->reason;
                }
            }
            
            if (empty($array['status'])) {
                $array['status'] = $result[$res->id]->status = 'Pending';
            } 
            $courseurl = new moodle_url('/course/view.php',array('id'=> $res->courseid));             
            $courseurl =$courseurl->out(false);
            $urlparams = array('id' => $res->moduleid,
                                'rownum' => 0,
                                'action' => 'grader',
                                'userid' => $res->userid
                            );
            $gradeurl = new moodle_url('/mod/assign/view.php', $urlparams);
            $gradeurl =$gradeurl->out(false);

            $array['completiondate'] = ($res->completionstate != 0) ? date('d-m-Y h:i:s', $res->completiondate) : 'N/A' ;
            $array['completereason'] = $array['reason'];
            
            $array['reason'] = strlen($array['reason']) > 20 ? substr($array['reason'], 0, 50) . "..." : $array['reason'];
            $filedet =  $DB->get_record_sql("SELECT MAX(f.id) as fileid,f.contextid, f.itemid, f.filename,f.timecreated FROM {files} f JOIN {context} cxt ON cxt.id = f.contextid JOIN {course_modules} as cm ON  cm.course = $res->courseid AND cm.id = cxt.instanceid 
                                                WHERE  component = 'assignsubmission_file' AND filearea = 'submission_files' AND filename != '.' AND f.userid = $res->userid GROUP BY itemid, contextid,filename");
            $array['contextid'] = $filedet->contextid;
            $array['fileid'] = $filedet->fileid;
            $array['itemid'] = $filedet->itemid;
            $array['filename'] = $filedet->filename;
            $array['courseid'] = $res->courseid;
            $array['userid'] = $res->userid;
            $array['firstname'] = $res->firstname;
            $array['lastname'] = $res->lastname;
            $array['email'] = $res->email;
            $array['open_employeeid'] = $res->open_employeeid;
            $array['fullname'] = $res->fullname;
            $array['moduleid'] = $res->moduleid;
            $array['completionstate'] = $res->completionstate;
            $array['name'] = $res->name;
            $array['modifieddate'] = date('d-m-Y h:i:s', $res->timemodified);
            $array['initialdate'] = date('d-m-Y h:i:s', $res->timecreated);          		
            $array['assignurl'] = $CFG->wwwroot.'/pluginfile.php/'.$filedet->contextid.'/assignsubmission_file/submission_files/'.$filedet->itemid.'/'.$filedet->filename.'?forcedownload=1';
            $array['courseurl'] = $courseurl;
            $array['gradeurl'] = $gradeurl;
            $data[] = $array;
        } 
        
    }

    return array('totalrecords' => $count, 'result' => $data);      
    
} 


function assignmentcourse_filter($mform)
{
    global $DB, $USER;
    $params = array();
    $systemcontext = context_system::instance();
    $sql = "SELECT DISTINCT c.id, c.fullname as fullname
                 FROM {course} c 
                JOIN {course_modules} cm on c.id = cm.course 
                JOIN {modules} m on cm.module= m.id 
                JOIN {assign_submission} s on s.assignment = cm.instance AND s.status = 'submitted'
                JOIN {assignsubmission_file} asf ON s.id = asf.submission AND s.assignment = asf.assignment
                JOIN {user} u on u.id = s.userid and u.suspended = 0 and u.deleted = 0
                JOIN {files} f ON f.userid = u.id AND f.component = 'assignsubmission_file' AND f.filearea = 'submission_files' AND f.filename != '.'
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id ";
   
    $wheresql = " WHERE  c.visible = 1";
    if(!has_capability('local/courses:manage', $systemcontext) && !is_siteadmin()) {
        $sql .= "  JOIN {context} AS cxt ON cxt.instanceid = c.id
                    JOIN {role_assignments} AS ra ON ra.contextid = cxt.id
                    JOIN {role} as r on r.id=ra.roleid ";
        $wheresql .= " AND cxt.contextlevel = :cxtlevel AND r.shortname=:role AND ra.userid = :userid";
        $params['cxtlevel'] = 50;
        $params['role'] = 'sme';
        $params['userid'] = $USER->id;
    }
    $wheresql .= " AND  m.name = 'assign'  ";
    $courseslist = $DB->get_records_sql_menu($sql.$wheresql, $params);
    
    $select = $mform->addElement('autocomplete', 'courses', '', $courseslist, array('placeholder' => get_string('course')));
    $mform->setType('courses', PARAM_RAW);
    $select->setMultiple(true);
}

function assignmentstatus_filter($mform)
{
    $statusarray = array('select'=>'Select Completion Status','pending'=>'Pending','completed' => 'Completed');
    $select = $mform->addElement('select', 'status', '', $statusarray, array('placeholder' => get_string('completionstatus','local_courseassignment')));
    $mform->setType('status', PARAM_RAW);
    $select->setSelected('c'); 
    $select->setMultiple(false);
}

function approvalstatus_filter($mform)
{
    $statusarray = array('select'=>'Select Completion Status','0'=>'Pending','1' => 'Approved','2' => 'Reject', '3' =>'Reset');
    $select = $mform->addElement('select', 'status', '', $statusarray, array('placeholder' => get_string('completionstatus','local_courseassignment')));
    $mform->setType('status', PARAM_RAW);
    $select->setSelected('c'); 
    $select->setMultiple(false);
}

function fromdate_filter($mform){
    $mform->addElement('date_selector', 'fromdate', get_string('selectfromdate','local_courseassignment'),array('optional' => true));        
}

function todate_filter($mform){
    $mform->addElement('date_selector', 'todate', get_string('selecttodate','local_courseassignment'),array('optional' => true),array('class' => 'date_selector'));        
}

// add sme courses icon in left menu
function local_courseassignment_leftmenunode()
{
    global $USER,$DB;
    $sql="SELECT ra.*
            FROM {context} as cxt
            JOIN {role_assignments} as ra on ra.contextid=cxt.id
            JOIN {role} as r on r.id=ra.roleid
            WHERE cxt.contextlevel=50 and r.shortname='sme' and ra.userid=$USER->id";
	$smecourses=$DB->record_exists_sql($sql);

    if(!is_siteadmin() && $smecourses && has_capability('local/smecourses:view', context_system::instance() )){
        $smenode = '';
        $smenode .= html_writer::start_tag('li', array('id' => 'id_leftmenu_sme', 'class' => 'pull-left user_nav_div sme'));
        $sme_url = new moodle_url('/local/courseassignment/smecourses.php');
        $sme = html_writer::link($sme_url, '<span class="icon fa fa-book left_menu_icons"></span><span class="user_navigation_link_text">' . get_string('sme_courses', 'local_courseassignment') . '</span>', array('class' => 'user_navigation_link'));
        $smenode .= $sme;
        $smenode .= html_writer::end_tag('li');
        return array('19' => $smenode);
  
    }
}

/**
    * function get_listof_smecourses
    * @todo all courses based  on costcenter / department
    * @param object $stable limit values
    * @param object $filterdata filterdata
    * @return  array courses
*/

function get_listof_smecourses($stable, $filterdata) {
   
    global $CFG,$DB,$USER;
    $core_component = new core_component();
   // require_once($CFG->libdir. '/coursecatlib.php');
    require_once($CFG->dirroot.'/course/renderer.php');
    require_once($CFG->dirroot . '/enrol/locallib.php');
    $autoenroll_plugin_exist = $core_component::get_plugin_directory('enrol','auto');
    if(!empty($autoenroll_plugin_exist)){
      require_once($CFG->dirroot . '/enrol/auto/lib.php');
    }
    
    $chelper = new coursecat_helper();
    $selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_points,c.open_costcenterid, c.open_identifiedas, c.visible, c.open_skill FROM {course} AS c"; 
    $countsql  = "SELECT count(c.id) FROM {course} AS c ";
    $fromsql = "";
    $fromsql .= "  JOIN {context} AS cxt ON cxt.instanceid = c.id
                    JOIN {role_assignments} AS ra ON ra.contextid = cxt.id
                    JOIN {role} as r on r.id=ra.roleid ";
           
   
    if(!empty($filterdata->coursetypes)){
        $fromsql .= " JOIN {local_course_types} AS ct ON ct.id = c.open_coursetype ";
    }
    if(!empty($filterdata->courseproviders)){
        $fromsql .= " JOIN {local_course_providers} AS cp ON cp.id = c.open_courseprovider";
    }
  
    $fromsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = :usercostcenter ";
   
    $fromsql .= " AND cxt.contextlevel = :cxtlevel AND r.shortname = :role AND ra.userid = :userid AND c.id > 1 ";
    $params['cxtlevel'] = 50;
    $params['role'] = 'sme';
    $params['userid'] = $USER->id;
    if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){      
        $fromsql .= " AND c.fullname LIKE :search";
        $searchparams = array('search' => '%'.trim($filterdata->search_query).'%');
    }else{
        $searchparams = array();
    }
   
    $params['usercostcenter'] = $USER->open_costcenterid;
    $params = array_merge( $params,$searchparams);

    $fromsql .=" ORDER BY c.id DESC";

    $totalcourses = $DB->count_records_sql($countsql.$fromsql, $params);

    $courses = $DB->get_records_sql($selectsql.$fromsql, $params, $stable->start,$stable->length);

    $ratings_plugin_exist = $core_component::get_plugin_directory('local', 'ratings');

    $courseslist = array();
    if(!empty($courses)){
        $count = 0;
        foreach ($courses as $key => $course) {
            $course_in_list = new course_in_list($course);
            $context = context_course::instance($course->id);
            $category = $DB->get_record('course_categories',array('id'=>$course->category));

            $params = array('courseid'=>$course->id);
            
            $enrolledusersssql = " SELECT COUNT(DISTINCT(ue.id)) as ccount
                                FROM {course} c
                                JOIN {course_categories} cat ON cat.id = c.category
                                JOIN {enrol} e ON e.courseid = c.id AND 
                                            (e.enrol = 'manual' OR e.enrol = 'self') 
                                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                                JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1 
                                                AND u.deleted = 0 AND u.suspended = 0
                                JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
                                JOIN {role_assignments} as ra ON ra.userid = u.id
                                JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
                                WHERE c.id = :courseid";

            $enrolled_count =  $DB->count_records_sql($enrolledusersssql, $params);


            $completedusersssql = " SELECT COUNT(DISTINCT(cc.id)) as ccount
                                FROM {course} c
                                JOIN {course_categories} cat ON cat.id = c.category
                                JOIN {enrol} e ON e.courseid = c.id AND 
                                            (e.enrol = 'manual' OR e.enrol = 'self') 
                                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                                JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1 
                                                AND u.deleted = 0 AND u.suspended = 0
                                JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
                                JOIN {role_assignments} as ra ON ra.userid = u.id
                                JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
                                JOIN {course_completions} as cc 
                                        ON cc.course = c.id AND u.id = cc.userid
                                WHERE c.id = :courseid AND cc.timecompleted IS NOT NULL ";

            $completed_count = $DB->count_records_sql($completedusersssql,$params);

            $coursename = $course->fullname;
            if (strlen($coursename)>23){
                $coursenameCut = substr($coursename, 0, 23)."...";
                $courseslist[$count]["coursenameCut"] = $coursenameCut;
            }
            $catname = $category->name;
            $catnamestring = strlen($catname) > 12 ? substr($catname, 0, 12)."..." : $catname;
       
            $courestypes = explode(',', $course->open_identifiedas);
            $displayed_names = array();
            foreach ($courestypes as $key => $courestype){ 
                $coursetypedetails = $DB->get_record('local_course_types',array('id'=>$courestype),'shortname,course_type');
                $displayed_names[] = '<span class="pl-10 '.$coursetypedetails->shortname.'">'.$coursetypedetails->course_type.'</span>';
            }
            if($ratings_plugin_exist){
                require_once($CFG->dirroot.'/local/ratings/lib.php');
                $ratingenable = True;
                $avgratings = get_rating($course->id, 'local_courses');
                $rating_value = $avgratings->avg == 0 ? 'N/A' : $avgratings->avg/*/2*/ ;
            }else{
                $ratingenable = False;
                $rating_value = 'N/A';
            }
       
            if($course->open_skill){
                $sql = "SELECT GROUP_CONCAT(name separator ', ')
                        FROM {local_skill}
                        WHERE id IN ($course->open_skill) ";
                $skill = $DB->get_field_sql($sql);
                if($skill){
                    $skillname = $skill;
                } else {
                    $skillname = 'N/A';
                }
            } else {
                $skillname = 'N/A';                
            }
            
            $displayed_names = implode(',' ,$displayed_names);
            $courseslist[$count]["coursename"] = \local_costcenter\lib::strip_tags_custom($coursename);
            $courseslist[$count]["skillname"] = \local_costcenter\lib::strip_tags_custom($skillname);
            $courseslist[$count]["ratings_value"] = $rating_value;
            $courseslist[$count]["ratingenable"] = $ratingenable;
            $courseslist[$count]["tagstring"] = \local_costcenter\lib::strip_tags_custom($tagstring);
            $courseslist[$count]["tagenable"] = $tagenable;
            $courseslist[$count]["catname"] = \local_costcenter\lib::strip_tags_custom($catname);
            $courseslist[$count]["catnamestring"] = \local_costcenter\lib::strip_tags_custom($catnamestring);
            $courseslist[$count]["enrolled_count"] = $enrolled_count;
            $courseslist[$count]["courseid"] = $course->id;
            $courseslist[$count]["completed_count"] = $completed_count;
            $courseslist[$count]["points"] = $course->open_points != NULL ? $course->open_points: 0;
            $courseslist[$count]["coursetype"] = \local_costcenter\lib::strip_tags_custom($displayed_names);
            $courseslist[$count]["course_class"] = $course->visible ? 'active' : 'inactive';
            
            $coursesummary = strip_tags($chelper->get_course_formatted_summary($course_in_list,
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
            $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
            $courseslist[$count]["coursesummary"] = \local_costcenter\lib::strip_tags_custom($summarystring);
    
            //course image
             
            $courseslist[$count]["courseimage"] = course_thumbimage($course);
            $courseslist[$count]["courseurl"] = $CFG->wwwroot."/course/view.php?id=".$course->id;
           
            $courseslist[$count]["facilitatorlink"] = $CFG->wwwroot."/local/courses/facilitator.php?courseid=".$course->id;
            $count++;
        }
        $nocourse = false;
    }else{
        $nocourse = true;
    }

    $coursesContext = array(
        "hascourses" => $courseslist,
        "nocourses" => $nocourse,
        "totalcourses" => $totalcourses,
        "length" => count($courseslist)
    );

    return $coursesContext;

}
