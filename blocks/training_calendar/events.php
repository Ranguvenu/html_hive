<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Ajax page returns events for the selected date
 * 
 * @package block my calendar
 * @copyright  2018 Sreenivas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/training_calendar/lib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
global $CFG, $DB, $PAGE, $USER,$OUTPUT;
$systemcontext = context_system::instance();
$statustype = optional_param('statustype', 'all', PARAM_RAW);
$cal_startdate = optional_param('start',0,PARAM_RAW);
$cal_endtdate = optional_param('end',0,PARAM_RAW);
$PAGE->set_context($systemcontext);

// $startdate = strtotime($cal_startdate);
// $enddate = strtotime($cal_endtdate);

$prev_month_ts = date("Y-m-d", strtotime(''.$cal_endtdate.' -1 month'));
$startdate = strtotime(date("Y-m-01 00:00:00", strtotime($prev_month_ts )));
$enddate = strtotime(date("Y-m-t 23:59:59", strtotime($prev_month_ts )));

$onlinetests_sql = plugins_access_sql($table = 'local_onlinetests');
$evaluations_sql = plugins_access_sql($table = 'local_evaluations');
$classrooms_sql = plugins_access_sql($table = 'local_classroom');
$programs_sql = plugins_access_sql($table = 'local_program');
$certifications_sql = plugins_access_sql($table = 'local_certification');

$local_events_sql = "SELECT * 
					from {event} e 
					where timestart >= :timestart AND timestart <= :timeend AND 
					modulename = :modulename AND instance = :instance AND plugin like '%local_%' AND ";

if (has_capability('local/costcenter:manage_multiorganizations', $systemcontext ) OR is_siteadmin()) {
	$local_events_sql .= " 1=1 ";
} else if (has_capability('local/costcenter:manage_ownorganization',$systemcontext) OR has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
	$local_events_sql .= commonmodule_plugin_access_sql($onlinetests_sql,$evaluations_sql,$classrooms_sql,$programs_sql,$certifications_sql);
} else {
	$user_access_sql = users_plugin_access_sql();
	$local_events_sql .= commonuser_plugin_access_sql($user_access_sql);
	
}
$local_events = $DB->get_records_sql($local_events_sql, array('timestart'=>$startdate,'timeend'=>$enddate, 'modulename' => '0', 'instance' => 0));

// moodle default events
$enrolledlist = enrol_get_users_courses($USER->id);
foreach ($enrolledlist as $key => $value) {
	$enrolledcourses[] = $value->id;
}
$time=time();
if (is_siteadmin())
$defaultevents = default_calendar_get_events($startdate, $enddate, true, false, true);
else
$defaultevents = default_calendar_get_events($startdate, $enddate, $USER->id, false, $enrolledcourses);

$allevents = array_merge($local_events, $defaultevents);

$context = context_system::instance();
foreach ($allevents as $local_event) {
	$can_access = true;
	// check event access to user
	$can_access = calendar_check_event_access($local_event);
	if ($can_access['enrolled'] OR $can_access['self_enrol']) {

	  $local_eventstartdate = date('d-M-Y',$local_event->timestart);
		 
		if (( is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations',$context) OR has_capability('local/costcenter:manage_ownorganization',$context) OR has_capability('local/costcenter:manage_owndepartments',$context) )) {
			$can_access['self_enrol'] = false;
		}
		// $can_access['enrolled'] = true;
		
		if($can_access['training_info']->instance){
			$local_event->plugin_instance = $local_event->plugin_itemid = $can_access['training_info']->instance;
			$local_event->local_eventtype = $local_event->eventtype;
			$local_event->plugin = 'mod';
		}
		$capacity = $enrolledusers = $waitinglistinfo = '';
		switch ($local_event->plugin) {
			case 'local_evaluation':
                $pluginurl = $CFG->wwwroot.'/local/evaluation/eval_view.php?id='.$local_event->plugin_instance;
                $popup = false;
            break;
            case 'local_classroom':
                $pluginurl = 'javascript:void(0)';
                $classroominfo = $DB->get_record("local_classroom", array('id' => $local_event->plugin_instance));
                $local_eventstartdate_ilt = date('d-M-Y H:i', $classroominfo->startdate).' - '.date('d-M-Y H:i', $classroominfo->enddate);
                $capacity = $classroominfo->capacity ? $classroominfo->capacity : 'N/A';
                $enrolledusers = $DB->count_records_sql("SELECT count(lcu.id) FROM {local_classroom_users} AS lcu JOIN {user} AS u ON u.id = lcu.userid WHERE lcu.classroomid = :classroomid AND u.suspended = 0 AND u.deleted = 0 ", array('classroomid' => $classroominfo->id)) ;
                if($classroominfo->allow_waitinglistusers){
                	$waitinglist_enable = 'Yes';
                	$waitinglistinfo = $DB->count_records('local_classroom_waitlist', array('classroomid' => $classroominfo->id, 'enrolstatus' => 0));
                }else{
                	$waitinglistinfo = 'N/A';
                	$waitinglist_enable = 'No';
                }
                $popup = true;
            break;
            case 'local_program':
                 $pluginurl = 'javascript:void(0)';
                 $popup = true;
            break;
            case 'local_certification':
                $pluginurl = 'javascript:void(0)';
                $popup = true;
            break;
            case 'local_onlinetests':
                $pluginurl = $CFG->wwwroot.'/mod/quiz/view.php?id='.$local_event->plugin_itemid;
                $popup = false;
            break;
            case 'mod':
                $pluginurl = $CFG->wwwroot.'/'.$local_event->plugin.'/'.$local_event->modulename.'/view.php?id='.$local_event->plugin_instance;
                $popup = false;
            break;
            case 'user':
                $pluginurl = 'javascript:void(0)';
                $popup = true;
            break;

            default:
                $pluginurl = $CFG->wwwroot.'/';
                $popup = false;
		}

		if ($local_event->eventtype === "open" || $local_event->eventtype === "session_open" ) {
			$string = 'opens on';
			$sumstring = $local_event->name.' '.$string.' '.$local_eventstartdate;
		}else if($local_event->eventtype === "close" || $local_event->eventtype === "session_close"){
			//$string = 'closes on';
			//$sumstring = $local_event->name.' '.$string.' '.$local_eventstartdate;
			continue;
		}else{
			$string = $local_event->eventtype;
			$sumstring = $local_event->name;
		}

		if(strlen($sumstring) > 42){
			$smallstring = substr($sumstring, 0, 42);
			$eventsname = $smallstring.'...';
		}else{
			$eventsname = $sumstring;
		}

		if ($can_access['training_info']->endtime > 0) {
			$eventenddate = true;
		}else{
			$eventenddate = false;
		}

		if($can_access['training_info']->starttime > 0) {
			$eventstartdate = true;
		}else{
			$eventstartdate = false;
		}
        
        $coursename = null;
		$creditpoints = null;
		if($local_event->plugin == 'local_classroom'){
			$sql = "SELECT lcc.id, c.fullname, c.open_points
					FROM {local_classroom_courses} lcc
					JOIN {course} c ON c.id = lcc.courseid
					JOIN {local_classroom_sessions} lcs ON lcs.classroomid = lcc.classroomid
					WHERE lcs.id = $local_event->plugin_instance";
			$clcourseinfo = $DB->get_record_sql($sql);
			if($clcourseinfo){
				$coursename = $clcourseinfo->fullname;
				$creditpoints = $clcourseinfo->open_points;
			}
				$classroomsql= "SELECT lc.name from {local_classroom} lc 
					WHERE lc.id = $local_event->plugin_instance";
				$classroomname = $DB->get_record_sql($classroomsql);
		}
		


		 if(($can_access['enrolled'] == true)) {
		 	$local_event->status = 'enrol';
		 }else{
		 	if($time > $local_event->timestart){
			 	$local_event->status = 'expired';
			}
			if($time < $local_event->timestart){
			 	$local_event->status = 'upcoming';
			}
		 }
	   $singleevent = array(
			'id' => $local_event->id,
			'instance'=>(empty($local_event->plugin_instance))? ((!empty($local_event->instance))? $DB->get_field_sql("SELECT cm.id from {course_modules} cm, {modules} m where m.name LIKE '{$local_event->modulename}' AND m.id = cm.module AND cm.instance = {$local_event->instance}"): (($local_event->courseid)? $local_event->courseid: $local_event->id)): $local_event->plugin_instance,
			'itemid'=>$local_event->plugin_itemid,
			'local_eventname'=>$local_event->name,
			'local_classroomname'=> $classroomname->name,
			'title'=>$local_event->name,
			'summary'=>strip_tags($local_event->description),
			'local_eventstartdate' =>$local_eventstartdate,
			'local_eventenddate' =>$can_access['training_info']->endtime,
			'start' => date('Y-m-d', $local_event->timestart),
			'eventtype' => (is_null($local_event->local_eventtype))? $local_event->eventtype: $local_event->local_eventtype,
			'plugin' => (is_null($local_event->plugin))? ((!empty($local_event->instance))? 'mod': $local_event->eventtype): $local_event->plugin,
			'status' => (is_null($local_event->status))? ((!empty($local_event->status))? 'mod': $local_event->status): $local_event->status,
			'modulename' => $local_event->modulename,		
			'capability'=>1,
			'enrolled'=>$can_access['enrolled'],
			'self_enrol'=>$can_access['self_enrol'],
			'eventlocation'=>$can_access['training_info']->location,
			'training_type'=>$can_access['training_info']->type,
			'trainer'=>$can_access['training_info']->trianers,
			'prerequisites' => $can_access['training_info']->prerequisite,
	        'prerequisitecompletion' => $can_access['training_info']->prerequisite_completion,
	        'prerequisiteurl' => $can_access['training_info']->training_url,
	        'iltselfenrol' => $can_access['training_info']->selfenrol,
	        'waitinglist_enable' => $waitinglist_enable,
			'string'=>$string,
			'pluginurl'=>$pluginurl,
			'eventenddate' => $eventenddate,
			'popup' => $popup,
			'eventfullname' => $eventsname,
			'plugintype' => $local_event->plugin,
			'coursename' => $coursename,
			'creditpoints' => $creditpoints,
			'localeventstartdate' => $local_eventstartdate_ilt,
			'capacity' => $capacity,
			'enrolledusers' => $enrolledusers,
			'waitinglistinfo' => $waitinglistinfo
		);

		$singleevent['content'] = $OUTPUT->render_from_template('block_training_calendar/allevents', $singleevent);
		if($statustype != 'enrol'){
			if($time > $local_event->timestart){
			 	$singleevent['status'] = 'expired';
			}
			if($time < $local_event->timestart){
			 	$singleevent['status'] = 'upcoming';
			}
		}
		$events[] = $singleevent;
	} else {
		continue;
	}
}
echo $response = json_encode($events);
