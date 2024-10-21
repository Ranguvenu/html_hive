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
 * @subpackage local_onlinetest
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
// use \local_onlinetests\notificationemails as onlinetestsnotifications_emails;

/**
 * creates a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $onlinetest the object
 * @return int onlinetestid
 */
function onlinetests_add_instance($onlinetest) {
    global $DB, $USER;
    $context = context_system::instance();
    $quiz = new stdClass();
    $quiz->name = $onlinetest->name;
    if (!empty($onlinetest->introeditor['text']))
    $quiz->introeditor['text'] = $onlinetest->introeditor['text'];
    else
    $quiz->introeditor['text'] = $onlinetest->name;
    $quiz->introeditor['format'] = $onlinetest->introeditor['format'];
    $quiz->introeditor['itemid'] = 1;
    if ($onlinetest->timeopen)
    $quiz->timeopen = $onlinetest->timeopen;
    else
    $quiz->timeopen = 0;
    if ($onlinetest->timeclose)
    $quiz->timeclose = $onlinetest->timeclose;
    else
    $quiz->timeclose = 0;
    $quiz->timelimit = 0;
    $quiz->overduehandling = 'autosubmit';
    $quiz->graceperiod = 0;
    $quiz->gradecat = 2;
    $quiz->gradepass = $onlinetest->gradepass;
    $quiz->grade = $onlinetest->grade;
    $quiz->attempts = $onlinetest->attempts;
    $quiz->grademethod = 1;
    $quiz->questionsperpage = 1;
    $quiz->navmethod = 'free';
    $quiz->shuffleanswers = 1;
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->canredoquestions = 0;
    $quiz->attemptonlast = 0;
    $quiz->attemptimmediately = 1;
    $quiz->correctnessimmediately = 1;
    $quiz->marksimmediately = 1;
    $quiz->specificfeedbackimmediately = 1;
    $quiz->generalfeedbackimmediately = 1;
    $quiz->rightanswerimmediately = 1;
    $quiz->overallfeedbackimmediately = 1;
    $quiz->attemptopen = 1;
    $quiz->correctnessopen = 1;
    $quiz->marksopen = 1;
    $quiz->specificfeedbackopen = 1;
    $quiz->generalfeedbackopen = 1;
    $quiz->rightansweropen = 1;
    $quiz->overallfeedbackopen = 1;
    $quiz->attemptclosed = 1;
    $quiz->correctnessclosed = 1;
    $quiz->marksclosed = 1;
    $quiz->specificfeedbackclosed = 1;
    $quiz->generalfeedbackclosed = 1;
    $quiz->rightanswerclosed = 1;
    $quiz->overallfeedbackclosed = 1;
    $quiz->showuserpicture = 0;
    $quiz->decimalpoints = 2;
    $quiz->questiondecimalpoints = -1;
    $quiz->showblocks = 0;
    $quiz->quizpassword = '';
    $quiz->subnet = '';
    $quiz->delay1 = 0;
    $quiz->delay2 = 0;
    $quiz->browsersecurity = '-';
    $quiz->boundary_repeats = 1;
    $feedbacktext =array();
    $feedbacktext[0]=array('text' => '','format' => 1,'itemid' => 45741940);
    $feedbacktext[1]= array('text' => '','format' => 1,'itemid' => 139878390);
    $quiz->feedbacktext = $feedbacktext;
    $feedbackboundaries=array('0'=>'');
    $quiz->feedbackboundaries = $feedbackboundaries;;

    $quiz->visible = $onlinetest->visible;
    $quiz->cmidnumber = '';
    $quiz->groupmode = 0;
    $quiz->groupingid = 0;
    $quiz->availabilityconditionsjson = '';//{"op":"&","c":$quiz->,"showc":$quiz->}
    $quiz->completionunlocked = 1;
    $quiz->completion = 1;
    $quiz->completionpass = 0;
    $quiz->completionattemptsexhausted = 0;
    $quiz->completionexpected = 0;
    $quiz->tags = '';
    $quiz->course = 1;
    $quiz->coursemodule = 0;
    $quiz->section = 0;
    $quiz->module = $DB->get_field('modules','id',array('name'=>'quiz'));
    $quiz->modulename = 'quiz';
    $quiz->instance = 0;
    $quiz->add = 'quiz';
    $quiz->update = 0;
    $quiz->return = 0;
    $quiz->sr = 0;
    $quiz->competency_rule = 0;
    $quiz->submitbutton = 'Save and display';
    $course = $DB->get_record('course', array('id' => 1));
    $quizid = add_moduleinfo($quiz,$course);


    $assessment = new stdClass();
    $assessment->quizid = $quizid->id;
    $assessment->name = $onlinetest->name;
    $assessment->costcenterid = $onlinetest->costcenterid;
    if (is_array($onlinetest->departmentid))
    $assessment->departmentid = implode(',',$onlinetest->departmentid);
    else
    $assessment->departmentid = $onlinetest->departmentid;
    if ($onlinetest->timeopen)
    $assessment->timeopen = $onlinetest->timeopen;
    else
    $assessment->timeopen = 0;
    if ($onlinetest->timeclose)
    $assessment->timeclose = $onlinetest->timeclose;
    else
    $assessment->timeclose = 0;
    $assessment->timemodified = time();
    $assessment->usermodified = $USER->id;
    $assessment->visible = $onlinetest->visible;
    $assessment->open_points = $onlinetest->open_points;
    $assessment->certificateid = $onlinetest->certificateid;
    $assessmentid = $DB->insert_record('local_onlinetests', $assessment);

    // Trigger onlinetest created event.
    $assessment->id = $assessmentid;

    $cm = get_coursemodule_from_instance('quiz', $quizid->id, 0, false, MUST_EXIST);
    $assessment->moduleid = $cm->id;

    onlinetest_set_events($assessment);

    $params = array(
        'context' => $context,
        'objectid' => $assessmentid
    );

    $event = \local_onlinetests\event\onlinetest_created::create($params);
    $event->add_record_snapshot('local_onlinetests', $assessment);
    $event->trigger();

    // Update onlinetest tags.
    // if (isset($onlinetest->tags)) {
    //     local_tags_tag::set_item_tags('local_onlinetests', 'onlinetests', $quizid->id, context_system::instance(), $onlinetest->tags, 0, $onlinetest->costcenterid, $onlinetest->departmentid);
    // }

    return $assessmentid;
}



/**
 * this will update a existing instance and return the id number
 *
 * @global object
 * @param object $onlinetest the object
 * @return int
 */
function onlinetests_update_instance($onlinetest) {
    global $DB, $USER;
    
    $record = $DB->get_record('local_onlinetests', array('id'=>$onlinetest->id), '*', MUST_EXIST);
    $quiz = $DB->get_record('quiz', array('id'=>$record->quizid), '*', MUST_EXIST);
    $quiz->name = $onlinetest->name;
    if (!empty($onlinetest->introeditor['text']))
    $quiz->introeditor['text'] = $onlinetest->introeditor['text'];
    else
    $quiz->introeditor['text'] = $onlinetest->name;
    $quiz->introeditor['format'] = $onlinetest->introeditor['format'];
    $quiz->introeditor['itemid'] = 1;
    $quiz->gradepass = $onlinetest->gradepass;
    $quiz->grade = $onlinetest->grade;
    $quiz->attempts = $onlinetest->attempts;
    if ($onlinetest->timeopen)
    $quiz->timeopen = $onlinetest->timeopen;
    else
    $quiz->timeopen = 0;
    if ($onlinetest->timeclose)
    $quiz->timeclose = $onlinetest->timeclose;
    else
    $quiz->timeclose = 0;
    $quiz->quizpassword = '';
    $quiz->submitbutton = 'Save and display';
    $course = $DB->get_record('course', array('id'=>1));
    $cm = get_coursemodule_from_instance('quiz', $record->quizid, 0, false, MUST_EXIST);
    
    $quiz->coursemodule = $cm->id;
    $quiz->modulename = 'quiz';
    $quiz->course = $course->id;
    $quiz->groupingid = $cm->groupingid;
    $quiz->visible = $onlinetest->visible;
    $quiz->visibleoncoursepage = $onlinetest->visible;
    update_moduleinfo($cm, $quiz, $course, null);
    if ($quiz->grade != $onlinetest->grade) {
        quiz_set_grade($onlinetest->grade, $quiz);
        quiz_update_all_final_grades($quiz);
        quiz_update_grades($quiz, 0, true);
        local_onlinetest_update_grade_status($onlinetest);
    }
    
    
    $record->name = $onlinetest->name;
    $record->costcenterid = $onlinetest->costcenterid;
    if (is_array($onlinetest->departmentid))
    $record->departmentid = implode(',',$onlinetest->departmentid);
    else {
        if ($onlinetest->departmentid)
        $record->departmentid = $onlinetest->departmentid;
        else
        $record->departmentid = null;
    }
    $record->timemodified = time();
    $record->usermodified = $USER->id;
    $record->visible = $onlinetest->visible;
    if ($onlinetest->timeopen)
    $record->timeopen = $onlinetest->timeopen;
    else
    $record->timeopen = 0;
    if ($onlinetest->timeclose)
    $record->timeclose = $onlinetest->timeclose;
    else
    $record->timeclose = 0;
    
    $record->moduleid = $cm->id;
    $record->open_points = $onlinetest->open_points;

    if($onlinetest->map_certificate == 1){
        $record->certificateid = $onlinetest->certificateid;
    }else{
        $record->certificateid = null;
    }

    $DB->update_record('local_onlinetests', $record);

    // Update onlinetest tags.
    // if (isset($onlinetest->tags)) {
    //     local_tags_tag::set_item_tags('local_onlinetests', 'onlinetests', $record->quizid, context_system::instance(), $onlinetest->tags, 0, $onlinetest->costcenterid, $onlinetest->departmentid);
    // }
    
    // Trigger onlinetest updated event.
    onlinetest_set_events($record);
    $context = context_system::instance();
    $params = array(
        'context' => $context,
        'objectid' => $record->id
    );

    $event = \local_onlinetests\event\onlinetest_updated::create($params);
    $event->add_record_snapshot('local_onlinetests', $record);
    $event->trigger();
    return $record->id;
}

function local_onlinetest_update_grade_status($onlinetest){
    global $DB;
    $onlinetest_user_sql = "SELECT lou.id, lou.userid, lou.status, gg.finalgrade, gi.gradepass 
        FROM {local_onlinetest_users} AS lou 
        JOIN {local_onlinetests} AS lo ON lo.id = lou.onlinetestid 
        JOIN {grade_items} AS gi ON gi.iteminstance = lo.quizid AND itemmodule LIKE 'quiz' AND courseid = 1 
        JOIN {grade_grades} AS gg ON gg.itemid = gi.id
        WHERE lou.onlinetestid = :onlinetestid AND lou.status = :status "; 
    $onlinetest_users = $DB->get_records_sql($onlinetest_user_sql, array('onlinetestid' => $onlinetest->id, 'status' => 0));
    foreach($onlinetest_users AS $user){
        if($user->finalgrade >= $user->gradepass){
            unset($user->finalgrade);
            unset($user->gradepass);
            $user->status = 1;
            $DB->update_record('local_onlinetest_users', $user);
        }
    }
}

/**
 * This creates new events given as timeopen and closeopen by onlinetest.
 *
 * @global object
 * @param object $onlinetest
 * @return void
 */
function onlinetest_set_events($onlinetest) {
    global $DB, $CFG, $USER;
    // Include calendar/lib.php.
    require_once($CFG->dirroot.'/calendar/lib.php');

    // evaluation start calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => '0', 'instance' => 0, 'plugin'=> 'local_onlinetests', 'plugin_instance'=>$onlinetest->id, 'eventtype' => 'open', 'local_eventtype' => 'open'));

    if (isset($onlinetest->timeopen) && $onlinetest->timeopen > 0) {
        $event = new stdClass();
        $event->eventtype    = 'open';
        $event->type         = empty($onlinetest->timeclose) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->name         = $onlinetest->name;
        $event->description  = "<a href='$CFG->wwwroot/local/onlinetests/index.php'>$onlinetest->name</a>";
        $event->timestart    = $onlinetest->timeopen;
        $event->timesort     = $onlinetest->timeopen;
        $event->visible      = $onlinetest->visible;
        $event->timeduration = 0;
        $event->plugin_instance = $onlinetest->id;
        $event->plugin_itemid = $onlinetest->moduleid;
        $event->plugin = 'local_onlinetests';
        $event->local_eventtype    = 'open';
        $event->relateduserid    = $USER->id;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            // Event doesn't exist so create one.
            $event->courseid     = 0;
            $event->groupid      = 0;
            $event->userid       = 0;
            $event->modulename   = 0;
            $event->instance     = 0;
            $event->eventtype    = 'open';;
            calendar_event::create($event);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }

    // evaluation close calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => '0', 'instance' => 0, 'plugin'=> 'local_onlinetests', 'plugin_instance'=>$onlinetest->id, 'eventtype' => 'close', 'local_eventtype' => 'close'));

    if (isset($onlinetest->timeclose) && $onlinetest->timeclose > 0) {
        $event = new stdClass();
        $event->type         = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype    = 'close';
        $event->name         = $onlinetest->name;
        $event->description  = "<a href='$CFG->wwwroot/local/onlinetests/index.php'>$onlinetest->name</a>";
        $event->timestart    = $onlinetest->timeclose;
        $event->timesort     = $onlinetest->timeclose;
        $event->visible      = $onlinetest->visible;
        $event->timeduration = 0;
        $event->plugin_instance = $onlinetest->id;
        $event->plugin_itemid = $onlinetest->moduleid;
        $event->plugin = 'local_onlinetests';
        $event->local_eventtype    = 'close';
        $event->relateduserid    = $USER->id;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            // Event doesn't exist so create one.
            $event->courseid     = 0;
            $event->groupid      = 0;
            $event->userid       = 0;
            $event->modulename   = 0;
            $event->instance     = 0;
            calendar_event::create($event);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }
}
/**
 * this will return sql statement

 * @param $context int contexid of evaluation 
 * @return string
 */
function department_sql($context) {
    global $DB, $USER;
    if(has_capability('local/costcenter:manage_ownorganization',$context)) {
        $costcenter = $DB->get_record_sql("SELECT cc.id, cc.parentid FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");
        if ($costcenter->parentid == 0) {
            // $sql =" and costcenterid IN( $costcenter->id )";
            $sql =" and costcenterid = $costcenter->id ";
        } else {
            // $sql =" and ( find_in_set($costcenter->id, departmentid) <> 0)  ";
            $sql =" and departmentid = $costcenter->id  ";
        }
    } else {
        // $sql =" and ( find_in_set($USER->open_departmentid, departmentid) <> 0)  ";
        $sql =" and CONCAT(',',departmentid,',') LIKE CONCAT('%,',$USER->open_departmentid,',%') ";
        // $sql = " and costcenterid = {$USER->open_costcenterid} and (departmentid = $USER->open_departmentid OR departmentid = 0 OR departmentid IS NULL)";
    }
    return $sql;
}
/**
 * Serve the new evalaution form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_onlinetests_output_fragment_new_onlinetest_form($args) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/local/onlinetests/onlinetest_form.php');
    $args = (object) $args;
    $context = $args->context;
    $id = $args->testid;
    $o = '';
 
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = ($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

	$params = array('id' => $id);
    $mform = new onlinetests_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    $data = new stdclass();
    if ($id > 0) {
		$data = $DB->get_record('local_onlinetests', array('id'=>$id));
        $quiz = $DB->get_record('quiz', array('id'=>$data->quizid));
        $cm = get_coursemodule_from_instance('quiz', $data->quizid, 0, false, MUST_EXIST);
		$gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$data->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
        $data->grade = round($gradeitem->grademax, 2);
        $data->gradepass = round($gradeitem->gradepass, 2);
        $data->attempts = $quiz->attempts;
        $data->introeditor['text'] = $quiz->intro;
        $data->introeditor['format'] = $quiz->introformat;

        if(!empty($data->certificateid)){
            $data->map_certificate = 1;
        }
        // Populate tags.
        // $data->tags = local_tags_tag::get_item_tags_array('local_onlinetests', 'onlinetests', $quiz->id);
	}
    
    if (is_object($data)) {
		$default_values = (array)$data;
	}
	$mform->set_data($default_values);
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

function local_onlinetests_output_fragment_addquestions_or_enrol($args) {
    global $CFG, $DB, $OUTPUT;
    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $onlinetest = $DB->get_record('local_onlinetests', array('id'=>$id), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $onlinetest->quizid, 0, false, MUST_EXIST);
    if (!$cm)
    print_error('No module found, error occured while processing');
    require_capability('local/onlinetests:manage', $context);
    $path = 'index.php';
    $iconimage=html_writer::empty_tag('img', array('src'=>$OUTPUT->image_url('i/checked'),'size'=>'15px'));
    $out = "<div class='success_icon'><span class='iconimage'>".$iconimage."</span><span>".get_string('createdsuccessfully', 'local_onlinetests')."</span></div>";
    $out .= "<table class = 'generaltable'>
    <tr><td>".get_string('doaddquestions', 'local_onlinetests')."</td><td><a href='../../mod/quiz/edit.php?cmid=$cm->id' class='btn btn-primary'>".get_string('questions', 'local_onlinetests')."</a></td></tr>
    <tr><td>".get_string('doenrollusers', 'local_onlinetests')."</td><td><a href='users_assign.php?id=$id' class='btn btn-primary'>".get_string('assignusers', 'local_onlinetests')."</a></td></tr>
    </table>
    ";
    $out .= "<div style='text-align:center;'><a href='$path' class='btn btn-primary'>".get_string('skip', 'local_evaluation')."</a></div>";
    return $out;
}


/**
* [available_enrolled_users description]
* @param  string  $type       [description]
* @param  integer $onlinetestid [description]
* @param  [type]  $params     [description]
* @param  integer $total      [description]
* @param  integer $offset    [description]
* @param  integer $perpage    [description]
* @param  integer $lastitem   [description]
* @return [type]              [description]
*/
function onlinetest_enrolled_users($type = null, $onlinetestid = 0, $params, $total=0, $offset=-1, $perpage=-1, $lastitem=0){

    global $DB, $USER;
    $context = context_system::instance();
    $onlinetest = $DB->get_record('local_onlinetests', array('id' => $onlinetestid), '*', MUST_EXIST);
 
    $params['suspended'] = 0;
    $params['deleted'] = 0;
 
    if($total==0){
         $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname";
    }else{
        $sql = "SELECT count(u.id) as total";
    }
    $sql.=" FROM {user} AS u WHERE  u.id > 2 AND u.suspended = :suspended AND u.deleted = :deleted ";
    if($lastitem!=0){
       $sql.=" AND u.id > $lastitem";
    }
    if (!is_siteadmin()) {
        $user_detail = $DB->get_record('user', array('id'=>$USER->id));
        $sql .= " AND u.open_costcenterid = :costcenter";
        $params['costcenter'] = $user_detail->open_costcenterid;
        if (has_capability('local/costcenter:manage_owndepartments',$context) AND !has_capability('local/costcenter:manage_ownorganization',$context)) {
            $sql .=" AND u.open_departmentid = :department";
            $params['department'] = $user_detail->open_departmentid;
        }
    }
    $sql .=" AND u.id <> $USER->id";
    if (!empty($params['email'])) {
         $sql.=" AND u.id IN ({$params['email']})";
    }
    if (!empty($params['uname'])) {
         $sql .=" AND u.id IN ({$params['uname']})";
    }
    if (!empty($params['username'])) {
         $sql .=" AND u.id IN ({$params['username']})";
    }
    if (!empty($params['department'])) {
         $sql .=" AND u.open_departmentid IN ({$params['department']})";
    }
    if (!empty($params['organization'])) {
         $sql .=" AND u.open_costcenterid IN ({$params['organization']})";
    }
    if (!empty($params['idnumber'])) {
         $sql .=" AND u.id IN ({$params['idnumber']})";
    }
    if (!empty($params['groups'])) {
         $group_list = $DB->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']})");
         
         $groups_members = implode(',', $group_list);
         if (!empty($groups_members))
         $sql .=" AND u.id IN ({$groups_members})";
         else
         $sql .=" AND u.id =0";
    }

    if ($type=='add') {
        $sql .= " AND u.id NOT IN (SELECT lcu.userid as userid
                              FROM {local_onlinetest_users} AS lcu
                              WHERE lcu.onlinetestid = $onlinetestid)";
    }elseif ($type=='remove') {
        $sql .= " AND u.id IN (SELECT lcu.userid as userid
                              FROM {local_onlinetest_users} AS lcu
                              WHERE lcu.onlinetestid = $onlinetestid)";
    }

    $order = ' ORDER BY u.id ASC ';

    if($total==0){
        $availableusers = $DB->get_records_sql_menu($sql .$order,$params, $offset, $perpage);
    }else{
        $availableusers = $DB->count_records_sql($sql,$params);
    }
    return $availableusers;
}
/**
 * Onlinetests info of the user.
 *
 * @param int $userid user id
 * @return array contains enrolled online tests details
 */
function user_tests($userid, $tabstatus) {
    global $DB, $OUTPUT;
    $sql = "SELECT a.*, ou.timecreated, ou.timemodified as joinedate from {local_onlinetests} a, {local_onlinetest_users} ou where a.id = ou.onlinetestid AND ou.userid = ? AND a.visible = 1";
    $sql .= " ORDER BY ou.timecreated DESC";
    $onlinetests = $DB->get_records_sql($sql, [$userid]);
    $data = array();
    if ($onlinetests) {
        foreach($onlinetests as $record) {
            $row = array();
            $cm = get_coursemodule_from_instance('quiz', $record->quizid, 0, false, MUST_EXIST);
            $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$record->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
            $sql="SELECT * FROM {quiz_attempts} where id = (SELECT max(id) id from {quiz_attempts} where userid = ? and quiz= ? )";
            $userattempt = $DB->get_record_sql($sql, [$userid, $record->quizid]);
            $attempts = ($userattempt->attempt) ? $userattempt->attempt : 0;
            $grademax = ($gradeitem->grademax) ? round($gradeitem->grademax): '-';
            $gradepass = ($gradeitem->gradepass) ? round($gradeitem->gradepass): '-';
            $userquizrecord = $DB->get_record_sql("SELECT * from {local_onlinetest_users} where onlinetestid = ? AND userid = ? ", [$record->id, $userid]);
            $enrolledon = date("d-m-Y", $userquizrecord->timecreated);
            $buttons = array();
            $time = time();
            if ($record->timeclose !=0 AND $time >= $record->timeclose)
            $buttons[] = '-';
            else
            $buttons[] = html_writer::link(new moodle_url('/mod/quiz/view.php', array('id' => $cm->id,'sesskey' => sesskey())), $OUTPUT->pix_icon('t/go', get_string('attemptnumber', 'quiz'), 'moodle', array('class' => 'iconsmall', 'title' => '')));
            if ($attempts)
            $buttons[] = html_writer::link(new moodle_url('/mod/quiz/review.php', array('attempt' => $userattempt->id,'sesskey' => sesskey())), $OUTPUT->pix_icon('i/preview', get_string('review', 'quiz'), 'moodle', array('class' => 'iconsmall', 'title' => '')));
            if ($gradeitem->id)
            $usergrade = $DB->get_record_sql("SELECT * from {grade_grades} where itemid = ? AND userid = ? ", [$gradeitem->id, $userid]);
            if ($usergrade) {
                $mygrade = round($usergrade->finalgrade, 2);
                if ($usergrade->finalgrade >= $gradepass) {
                    $completedon = date("d-m-Y", $usergrade->timemodified);
                    $status = 'Completed';
                    if ($tabstatus == 2) // incomplete
                    continue;
                } else {
                    $status = 'Incomplete';
                    $completedon = '-';
                    if ($tabstatus == 1) // complete
                    continue;
                }
                
            } else {
                if ($tabstatus == 1) // incomplete
                    continue;
                $mygrade = '-';
                $status = 'Pending';
                $completedon = '-';
                $attempts = 0;
            }
            $buttons = implode('',$buttons);
            $row[] = $record->name;
            $row[] = $grademax;
            $row[] = $gradepass;
            $row[] = $mygrade;
            $row[] = $attempts;
            $row[] = $enrolledon;
            $row[] = $completedon;
            $row[] = $status;
            $row[] = $buttons;
            $data[] = $row;
            
        }
    }
    return $data;
}
/**
 * [function to get user enrolled onlinetests]
 * @param  [INT] $userid [id of the user]
 * @return [INT]         [count of the onlinetests enrolled]
 */
function enrol_get_users_onlinetest_count($userid){
    global $DB;
    $onlinetest_sql = "SELECT count(id) FROM {local_onlinetest_users} WHERE userid = :userid";
    $onlinetest_count = $DB->count_records_sql($onlinetest_sql, array('userid' => $userid));
    return $onlinetest_count;
}

function get_enrolled_onlinetest_as_employee($userid){
    global $DB;
    $sql = "SELECT lot.* FROM {local_onlinetests} AS lot
        JOIN {local_onlinetest_users} AS lotu ON lotu.onlinetestid=lot.id
        WHERE  lotu.userid = ?";
    $employeeonlinetests = $DB->get_records_sql($sql, [$userid]);
    return $employeeonlinetests;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_onlinetests_leftmenunode(){
    $systemcontext = context_system::instance();
    $onlinetestsnode = '';
    if(has_capability('local/onlinetests:view', $systemcontext) || is_siteadmin()){
        $onlinetestsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_browseonlinetests', 'class'=>'pull-left user_nav_div browseonlinetests'));
        $onlinetests_url = new moodle_url('/local/onlinetests/index.php');

        if(has_capability('local/onlinetests:manage', $systemcontext)) {
            $onlinetests_label = get_string('left_menu_onlinetests','local_onlinetests');
        }else{
            $onlinetests_label = get_string('left_menu_myonlinetests','local_onlinetests');
        }
        $onlinetests = html_writer::link($onlinetests_url, '<span class="manage_onlinequizzes_icon left_menu_icons"></span><span class="user_navigation_link_text">'.$onlinetests_label.'</span>',array('class'=>'user_navigation_link'));
        $onlinetestsnode .= $onlinetests;
        $onlinetestsnode .= html_writer::end_tag('li');
    }

    return array('10' => $onlinetestsnode);
}
function local_onlinetests_quicklink_node(){
    global $DB, $PAGE, $USER, $CFG, $OUTPUT;
    $systemcontext = context_system::instance();
    if (is_siteadmin() || has_capability('local/onlinetests:view',$systemcontext)) {
        if(is_siteadmin()){
            $count_ot = $DB->count_records('local_onlinetests');
            $count_otactive = $DB->count_records('local_onlinetests', array('visible' => 1));
            $count_otinactive = $DB->count_records('local_onlinetests', array('visible' => 0));
        }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $count_ot = $DB->count_records('local_onlinetests', array('costcenterid' => $USER->open_costcenterid));
            $count_otactive = $DB->count_records('local_onlinetests', array('costcenterid' => $USER->open_costcenterid, 'visible' => 1));
            $count_otinactive = $DB->count_records('local_onlinetests', array('costcenterid' => $USER->open_costcenterid, 'visible' => 0));
        }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $count_ot = $DB->count_records('local_onlinetests', array('costcenterid' => $USER->open_costcenterid, 'departmentid' => $USER->open_departmentid));
            $count_otactive = $DB->count_records('local_onlinetests', array('costcenterid' => $USER->open_costcenterid, 'departmentid' => $USER->open_departmentid, 'visible' => 1));
            $count_otinactive = $DB->count_records('local_onlinetests', array('costcenterid' => $USER->open_costcenterid, 'departmentid' => $USER->open_departmentid, 'visible' => 0));
        }
        if($count_otactive==0 || $count_ot==0){
            $otpercentage = 0;
        }else{
            $otpercentage = round(($count_otactive/$count_ot)*100);
            $otpercentage = (int)$otpercentage;
        }
        //local onlinetests content
        // $id = 0; //default for /local/users/index.php
        $PAGE->requires->js_call_amd('local_onlinetests/newonlinetests', 'init', array('[data-action=createonlinetestsmodal]', $systemcontext->id, $id));
        // $local_onlinetests_content = $PAGE->requires->js_call_amd('local_onlinetests/newonlinetests', 'init', array('[data-action=createonlinetestsmodal]', $systemcontext->id, $id));
        // $local_onlinetests_content .= "<span class='anch_span'><i class='fa fa-desktop' aria-hidden='true'></i></span>";
        // $local_onlinetests_content .= "<div class='w-100 pull-left'>
        //                                     <div class='quick_navigation_detail'>
        //                                     <div class='span_str'>".get_string('manage_br_onlineexams', 'local_onlinetests')."</div>";
        //     $display_line = false;
        // if(has_capability('local/onlinetests:create', $systemcontext) || is_siteadmin()){
        //     $local_onlinetests_content .= "<span class='span_createlink'>
        //                                         <a href='javascript:void(0);' class='quick_nav_link goto_local_onlinetest' title='".get_string('create_onlinetest', 'local_onlinetests')."' data-action='createonlinetestsmodal'>".get_string('create')."</a>"; 
        //     $display_line = true; 
        // }
                    
        // if($display_line){
        //     $local_onlinetests_content .= " | ";
        // }
        // $local_onlinetests_content .="<a href='".$CFG->wwwroot."/local/onlinetests/index.php' class='viewlink' title= '".get_string('viewonlinetest', 'local_onlinetests')." '>".get_string('view')."</a>";
        // $local_onlinetests_content .="</span>";
        // $local_onlinetests_content .= "</div>
        //                                </div>";
        // $local_onlinetests_content .= '<div class="progress-chart-container">
        //                                <div class="progress-doughnut">
        //                                     <div class="progress-text has-percent">'.$otpercentage.'%</div>
        //                                     <div class="progress-indicator">
        //                                         <svg xmlns="http://www.w3.org/2000/svg">
        //                                             <g>
        //                                                 <title aria-hidden="true">'.$otpercentage.'</title>
        //                                                 <circle class="circle percent-'.$otpercentage.'" r="27.5" cx="35" cy="35"></circle>
        //                                             </g>
        //                                         </svg>
        //                                     </div>
        //                                 </div>
        //                             </div>';
        // $local_onlinetests_content .= '<div class="w-100 pull-left">
        //                                 <div class="progress w-75 mx-auto my-5">
        //                                     <div class="progress-bar" role="progressbar" style="width: '.$otpercentage.'%;" aria-valuenow="'.$otpercentage.'" aria-valuemin="0" aria-valuemax="100">'.$otpercentage.'%</div>
        //                                 </div>
        //                             </div>';
        // $local_onlinetests_content .= '<ul class="dashboard_count_list w-full pull-left p-15">
        //                             <li class="dashbaord_count_item"><span class="">
        //                                 <span class="d-block dashboard_count_string">Total</span><span class="dashboard_count_value">'.$count_ot.'</span></span>
        //                             </li>
        //                             <li class="dashbaord_count_item"><span class="">
        //                                 <span class="d-block dashboard_count_string">Active</span><span class="dashboard_count_value">'.$count_otactive.'</span></span>
        //                             </li>
        //                             <li class="dashbaord_count_item"><span class="">
        //                                 <span class="d-block dashboard_count_string">In Active</span><span class="dashboard_count_value">'.$count_otinactive.'</span></span>
        //                             </li>
        //                         </ul>';
        // $local_onlinetests = '<div class="quick_nav_list manage_onlineexams two_of_three_columns" >'.$local_onlinetests_content.'</div>';


        $local_onlinetest = array();
        $local_onlinetest['pluginname'] = 'onlineexams';
        $local_onlinetest['plugin_icon_class'] = 'fa fa-desktop';
        $local_onlinetest['node_header_string'] = get_string('manage_br_onlineexams', 'local_onlinetests');
        $local_onlinetest['create'] = (has_capability('local/onlinetests:create', $systemcontext) || is_siteadmin()) ? TRUE : FALSE;
        $local_onlinetest['create_element'] = html_writer::link('javascript:void(0)', get_string('create'), array('data-action' =>'createonlinetestsmodal', 'title' => get_string('create_onlinetest', 'local_onlinetests'), 'class' => 'quick_nav_link goto_local_onlinetest'));
        $local_onlinetest['viewlink_url'] = $CFG->wwwroot.'/local/onlinetests/index.php';
        $local_onlinetest['percentage'] = $otpercentage;
        $local_onlinetest['count_total'] = $count_ot;
        $local_onlinetest['count_active'] = $count_otactive;
        $local_onlinetest['inactive_string'] = get_string('inactive_string', 'block_quick_navigation');
        $local_onlinetest['displaystats'] = TRUE;
        $local_onlinetest['count_inactive'] = $count_otinactive;
        $local_onlinetest['view'] = TRUE;
        $local_onlinetest['space_count'] = 'two';
        $content = $OUTPUT->render_from_template('block_quick_navigation/quicklink_node', $local_onlinetest);
    }
    return array('9' => $content);
}
/**
 * process the onlinetest_mass_enroll
 * @param csv_import_reader $cir  an import reader created by caller
 * @param Object $onlinetest  a onlinetest record from table mdl_local_onlinetest
 * @param Object $context  course context instance
 * @param Object $data    data from a moodleform
 * @return string  log of operations
 */
function onlinetest_mass_enroll($cir, $onlinetest, $context, $data) {
    global $CFG,$DB, $USER;
    require_once ($CFG->dirroot . '/group/lib.php');
    // init csv import helper
    // require_once($CFG->dirroot.'/local/onlinetests/notifications_emails.php');
    // $emaillogs = new onlinetestsnotifications_emails();
    $systemcontext = context_system::instance();
    $notification = new \local_onlinetests\notification();
    $useridfield = $data->firstcolumn;
    $cir->init();
    $enrollablecount = 0;
    while ($fields = $cir->next()) {
        $a = new stdClass();
        if (empty ($fields))
            continue;
        $fields[0]= str_replace('"', '', trim($fields[0]));
        /*First Condition To validate users*/
        $sql = "SELECT u.* from {user} u where u.deleted=0 and u.suspended=0 and u.$useridfield='$fields[0]'";
        if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))){
            $sql .= " AND u.open_costcenterid = {$USER->open_costcenterid} ";
            if(!has_capability('local/costcenter:manage_ownorganization',$context)){
                $sql .= " AND u.open_departmentid = {$USER->open_departmentid} ";
            }
        }

        if (!$user = $DB->get_record_sql($sql)) {
            $result .= '<div class="alert alert-danger">'.get_string('im:user_unknown', 'local_courses', $fields[0] ). '</div>';
            continue;
        } else {
            $submitted = new stdClass();
            $submitted->timemodified = time();
            $submitted->timecreated = time();
            $type = 'onlinetest_enrollment';
            $dataobj = $onlinetest->id;
            $fromuserid = $USER->id;
            $submitted->userid = $user->id;
            $submitted->onlinetestid = $onlinetest->id;
            $submitted->creatorid = $USER->id;
            $submitted->status = 0;
            $quizid = $DB->get_field('local_onlinetests','quizid',array('id'=>$onlinetestid));
            $submitted->quizid = $quizid;
            $exist = $DB->record_exists('local_onlinetest_users',array('userid'=>$user->id,'onlinetestid'=>$onlinetest->id));
            if(empty($exist)){
              $insert = $DB->insert_record('local_onlinetest_users',$submitted);
              $params = array(
                  'context' => $systemcontext,
                  'relateduserid' => $user->id,
                  'objectid' => $onlinetest->id
              );
              $event = \local_onlinetests\event\onlinetest_enrolled::create($params);
              $event->add_record_snapshot('local_onlinetests', $onlinetest);
              $event->trigger();

                // $touser = \core_user::get_user($userid);
                // $fromuser = \core_user::get_user($userquiz->creatorid);
                $logmail = $notification->onlinetest_notification($type, $user, $USER, $onlinetest);
              // $email_logs = $emaillogs->onlinetests_emaillogs($type,$dataobj,$user->id,$fromuserid);
              $result .= '<div class="alert alert-success">'.get_string('im:enrolled_ok', 'local_courses', fullname($user)).'</div>';

              $enrollablecount ++;
            } else {
                $result .= '<div class="alert alert-error">'.get_string('user_exist', 'local_onlinetests', $fields[0] ). '</div>';
                continue;
            }
        }
    }
    $result .= '<br />';
    $result .= get_string('im:stats_i', 'local_onlinetests', $enrollablecount) . "";
    return $result;
}

/*
* Author Sarath
* return count of onlinetests under selected costcenter
* @return  [type] int count of onlinetests
*/
function costcenterwise_onlinetests_count($costcenter,$department = false){
    global $USER, $DB;
        $params = array();
        $params['costcenter'] = $costcenter;
        $countonlinetestsql = "SELECT count(id) FROM {local_onlinetests} WHERE costcenterid = :costcenter ";
        if($department){
            $countonlinetestsql .= " AND departmentid = :department ";
            $params['department'] = $department;
        }
        $activesql = " AND visible = 1 ";
        $inactivesql = " AND visible= 0 ";

        $countont = $DB->count_records_sql($countonlinetestsql, $params);
        $activeontests = $DB->count_records_sql($countonlinetestsql.$activesql, $params);
        $inactiveontests = $DB->count_records_sql($countonlinetestsql.$inactivesql, $params);
    return array('dept_ont' => $countont,'dept_ontactive' => $activeontests,'dept_ontinactive' => $inactiveontests);
}


/**
    * function onlinetestslist
    * @todo all exams based  on costcenter / department
    * @param object $stable limit values
    * @param object $filterdata filterdata
    * @return  array courses
*/
function onlinetestslist($stable, $filterdata) {
    global $DB, $PAGE, $CFG, $USER;
    $context = context_system::instance();
    $departmentsparams = array();
    $organizationsparams = array();
    $userorg = array();
    $departmentsparams = array();
    $organizationsparams = array();
    $onlinetestsparams = array();
    $data = array();
    $countsql = "SELECT count(a.id) ";
    $sql ="SELECT a.* ";

    if (has_capability('local/costcenter:manage_multiorganizations', $context ) OR is_siteadmin()) {
        $fromsql = " FROM {quiz} q, {local_onlinetests} a where q.course=1 AND a.quizid = q.id";
    } else if ( has_capability('local/onlinetests:manage',$context) ) { // check for department head
        $deptsql = department_sql($context);
        $fromsql = " FROM {local_onlinetests} a where a.id > 0 $deptsql "; 
    } else { // check for users
        $fromsql = " FROM {local_onlinetests} a, {local_onlinetest_users} eu where a.id = eu.onlinetestid AND eu.userid = :userid AND a.visible = 1";
        $userorder = 1;
        $userorg = array('userid'=>$USER->id);
    }
    $filter = new stdClass();
    $filter->onlinetest = str_replace('_qf__force_multiselect_submission', '', $filterdata->onlinetest);   
    $filter->organizations = str_replace('_qf__force_multiselect_submission', '', $filterdata->organizations);
    $filter->departments = str_replace('_qf__force_multiselect_submission', '', $filterdata->departments);
    $filter->status = str_replace('_qf__force_multiselect_submission', '', $filterdata->status);
    if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
        $fromsql .= " AND a.name LIKE :search ";
        $searchparams = array('search' => '%'.trim($filterdata->search_query).'%');
    }else{
        $searchparams = array();
    }
    if(!empty($filter->departments)){

        $departments = explode(',', $filter->departments);
        list($departmentssql, $departmentsparams) = $DB->get_in_or_equal($departments, SQL_PARAMS_NAMED, 'param', true, false);
        $fromsql .= " AND a.departmentid $departmentssql";    
    }
    
    if(!empty($filter->organizations)){
        $organizations = explode(',', $filter->organizations);
        list($organizationssql, $organizationsparams) = $DB->get_in_or_equal($organizations, SQL_PARAMS_NAMED, 'param', true, false);
        $fromsql .= " AND a.costcenterid $organizationssql";    
    }

    if(!empty($filter->status)){
        $status = explode(',',$filter->status);
        if(!(in_array('active',$status) && in_array('inactive',$status))){
            if(in_array('active' ,$status)){
                $fromsql .= " AND a.visible = 1 ";           
            }else if(in_array('inactive' ,$status)){
                $fromsql .= " AND a.visible = 0 ";
            }
        }
    }

    if(!empty($filter->onlinetest)){
        $onlinetests = explode(',', $filter->onlinetest);
        list($onlinetestssql, $onlinetestsparams) = $DB->get_in_or_equal($onlinetests, SQL_PARAMS_NAMED, 'param', true, false);
        $fromsql .= " AND a.id $onlinetestssql ";    
    }

    if ($userorder == 1)
    $ordersql = " order by eu.timecreated DESC ";
    else
    $ordersql = " order by a.id DESC ";

    $params = $userorg+$departmentsparams+$organizationsparams+$onlinetestsparams+$searchparams;
    $recordscount = $DB->count_records_sql($countsql.$fromsql, $params);
    $onlinetests = $DB->get_records_sql($sql.$fromsql.$ordersql, $params, $stable->start, $stable->length);

    foreach($onlinetests as $record){
        $row = array(); 
        $line = array();
        
        $cm = get_coursemodule_from_instance('quiz', $record->quizid, 0, false, MUST_EXIST);
        $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$record->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));     
        $buttons=array();
        $is_admin = '';
        $actions = '';
        if(has_capability('local/onlinetests:manage', $context)){
            $actions = true;
            if (has_capability('local/onlinetests:create', $context)) {
                $edit = true;
                $hide_show = true;
            }
            if (has_capability('local/onlinetests:create', $context)) {
                $questions = true;
            }
            if (has_capability('local/onlinetests:enroll_users', $context)) {
                $users = true;
                $addusers = true;
                $bulkenrollusers = true;
            }
            if (has_capability('local/onlinetests:delete', $context)) {
                $delete = true;
            }
        }
        $extrainfo = '';
        $enrolled_sql = "SELECT count(ou.id) as attendcount 
            FROM {local_onlinetest_users} AS ou, {user} AS u 
            where u.id = ou.userid AND u.deleted = 0 
            AND u.suspended = 0 AND  ou.onlinetestid= ? ";
        if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))){
            $enrolled_sql .= " AND u.open_costcenterid = {$USER->open_costcenterid}";
        }
        if(!has_capability('local/costcenter:manage_ownorganization', $context)){
            $enrolled_sql .= " AND u.open_departmentid = {$USER->open_departmentid}";
        }
        $attendcount = $DB->get_record_sql($enrolled_sql, [$record->id]);
        
        if ($record->visible) {
            $hide = 1;
            $show = 0;
        } else {
            $show = 1;
            $hide = 0;
        }
        

        if($record->timeopen==0 AND $record->timeclose==0) {
            $dates= get_string('open', 'local_onlinetests');
        } elseif(!empty($record->timeopen) AND empty($record->timeclose)) {
            $dates = 'From '.date('d-M-Y', $record->timeopen);
        } elseif (empty($record->timeopen) AND !empty($record->timeclose)) {
            $dates = 'Ends on '. date('d-M-Y', $record->timeclose);
        } else {
            $dates = date('d-M-Y', $record->timeopen).  ' to '  .date('d-M-Y', $record->timeclose);
        }
        $completed_sql = "SELECT count(ou.id) from {local_onlinetest_users} AS ou, {user} AS u 
        WHERE u.id = ou.userid AND u.deleted = 0 
        AND u.suspended = 0 AND ou.onlinetestid=? AND ou.status=?";
        if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))){
            $completed_sql .= " AND u.open_costcenterid = {$USER->open_costcenterid}";
        }
        if(!has_capability('local/costcenter:manage_ownorganization', $context)){
            $completed_sql .= " AND u.open_departmentid = {$USER->open_departmentid}";
        }
        $completed_count = $DB->count_records_sql($completed_sql, array($record->id, 1));

        $grademax = ($gradeitem->grademax) ? round($gradeitem->grademax,2): '-';
        $gradepass = ($gradeitem->gradepass) ? round($gradeitem->gradepass,2): '-';
        $testname = strlen($record->name) > 10 ? substr($record->name, 0, 10)."..." : $record->name;
        if ($record->departmentid)
        $departments = $DB->get_field_sql("select c.fullname as depts from {local_costcenter} c where CONCAT(',',c.id,',') LIKE CONCAT('%,',$record->departmentid,',%')  ");
        $departmentsCut = $departments;

        if (is_siteadmin() OR has_capability('local/onlinetests:manage', $context)) {
            $is_admin = true;
            $departmentscount =   explode(',',$departmentsCut); 
            if(count($departmentscount)>1){ 
               $departmentname = strlen($departmentscount[0]) > 15 ? substr($departmentscount[0], 0, 15) : $departmentscount[0];
              $departmentsCut =  $departmentname. '...';
            }elseif(count($departmentscount)==1){
               $departmentname = strlen($departmentscount[0]) > 16 ? substr($departmentscount[0], 0, 16) : $departmentscount[0];
               $departmentsCut = $departmentname. '...';
            }
            if(empty($departments)){
               $departmentsCut = 'All';
            }
            // print_object($record);
            $line['testname'] = $testname;
            $line['testfullname'] = $record->name;
            $line['testdate'] = $dates;
            $line['maxgrade'] = $grademax;
            $line['mygrade'] = '';
            $line['passgrade'] = $gradepass;
            $line['configpath'] = $CFG->wwwroot;
            $line['edit'] = $edit;
            $line['questions'] = $questions;
            $line['users'] = $users;
            $line['addusers'] = $addusers;
            $line['bulkenrollusers'] = $bulkenrollusers;
            $line['delete'] = $delete;
            $line['enrolled'] = $attendcount->attendcount;
            $line['testid'] = $record->id;
            $line['quizid'] = $record->quizid;
            $line['is_admin'] = $is_admin;
            $line['cmid'] = $cm->id;
            $line['completed'] = $completed_count;
            $line['sesskey'] = sesskey();
            $line['attempts'] = 0;
            $line['departmentsCut'] = $departmentsCut;
            $line['deptname'] = $departments;
            $line['enrolledon'] = '';
            $line['completedon'] = '';
            $line['status'] = '';
            $line['canreview'] = 0;
            $line['userque'] = false;
            $line['usertwoactions'] = false;
            $line['userreview'] = false;
            $line['userhasactions'] = false;
            $line['userattemptid'] = 0;
            $line['completed'] = $completed_count;
            $line['starttest_url'] = $CFG->wwwroot.'/mod/quiz/view.php?id='.$cm->id.'';
            $line['hide_show'] = $hide_show;
            $line['hide'] = $hide;
            $line['show'] = $show;
            $line['actions'] = $actions;
            $line['contextid'] = $context->id;
        } else {
            $is_admin = false;
            $actions = true;
            $can_review = 0;
            $userquizrecord = $DB->get_record_sql("select * from {local_onlinetest_users} where onlinetestid=? AND userid =? ", [$record->id, $USER->id]);
            $enrolledon = date("j M 'y", $userquizrecord->timecreated);
            $userattempt = new stdclass();
            $attempts = 0;
            $userreview = false;
            $userque = false;
            $sql="SELECT * FROM {quiz_attempts} where id=(SELECT max(id) id from {quiz_attempts} where userid=? and quiz=?)";
            $userattempt = $DB->get_record_sql($sql, [$USER->id, $record->quizid]);
            $attempts = ($userattempt->attempt) ? $userattempt->attempt : 0;
            $time = time();
            if ($record->timeclose !=0 AND $time >= $record->timeclose)
              $timeclose = true;
            else
              $userque = true;
            
            if ($attempts) {                    
              $userreview = true;
            }
            if ($gradeitem->id)
            $usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = ? AND userid = ? ", [$gradeitem->id, $USER->id]);
            if ($usergrade) {
                $mygrade = round($usergrade->finalgrade, 2);
                if ($usergrade->finalgrade >= $gradepass) {
                    $completedon = date("j M 'y", $usergrade->timemodified);
                    $can_review = 1;
                    $status = 'Completed';
                } else {
                    $status = 'Incomplete';
                    $completedon = 'N/A';
                }                   
            } else {
                $mygrade = '-';
                $status = 'Pending';
                $completedon = 'N/A';
                $attempts = 0;
            }    
            $usertwoactions = false;
            $student_attemptid =  $userattempt->id;
            if($userque && $student_attemptid){
                $usertwoactions = true;
            }else{
                $usertwoactions = false;
            }
            // $userhasactions = flase;
            if(empty($userque || $student_attemptid)){
                $userhasactions = true;
            }else{
                $userhasactions = false;
            }
            if(!is_siteadmin()){
            $switchedrole = $USER->access['rsw']['/1'];
            if($switchedrole){
                $userrole = $DB->get_field('role', 'shortname', array('id' => $switchedrole));
            }else{
                $userrole = null;
            }
            if(is_null($userrole) || $userrole == 'user'){
             $certificate_plugin_exist = \core_component::get_plugin_directory('local', 'certificates');
            if($certificate_plugin_exist){
                if(!empty($record->certificateid)){
                    $certificate_exists = true;
                    $usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = ? AND userid = ? ", [$gradeitem->id, $USER->id]);
            if($usergrade) {
                $mygrade = round($usergrade->finalgrade, 2);
                if ($usergrade->finalgrade >= $gradepass) {
                    //$completedon = date("j M 'y", $usergrade->timemodified);
                    $can_review = 1;
                    $status = 'Completed';
                    $certificate_download= true;
                } else {
                    $status = 'Incomplete';
                    $certificate_download = false;
                    //$completedon = 'N/A';
                }                   
            }
                    /*$sql = "SELECT id 
                            FROM {local_onlinetest_users}
                            WHERE onlinetestid = $record->id
                            AND status = 1 ";
                    $completed = $DB->record_exists_sql($sql, array('userid'=>$USER->id));*/
               /* if($usergrade){
                    $certificate_download= true;
                 
                }else{
                    $certificate_download = false;
                }*/
                $certificateid = $record->certificateid;
                //$certificate_download['moduletype'] = 'classroom';
                }
            }
       
        }
    }

            $line['testid'] = $record->id;
            $line['testname'] = $testname;
            $line['testfullname'] = $record->name;
            $line['quizid'] = $record->quizid;
            $line['testdate'] = $dates;
            $line['maxgrade'] = $grademax;
            $line['passgrade'] = $gradepass;
            $line['mygrade'] = $mygrade;
            $line['sesskey'] = sesskey();
            $line['attempts'] = $attempts;
            $line['enrolledon'] = $enrolledon;
            $line['enrolled'] = 0;
            $line['completed'] = 0;
            $line['users'] = 0;
            $line['addusers'] = false;
            $line['bulkenrollusers'] = false;
            $line['delete'] = false;
            $line['edit'] = false;
            $line['questions'] = false;
            $line['completedon'] = $completedon;
            $line['status'] = $status;
            $line['canreview'] = $can_review;
            $line['is_admin'] = $is_admin;
            $line['configpath'] = $CFG->wwwroot;
            $line['timeclose'] = $timeclose;
            $line['actions'] = $actions;
            $line['deptname'] = $departments;
            $line['departmentsCut'] = '';
            $line['userque'] = $userque;
            $line['usertwoactions'] = $usertwoactions;
            $line['userreview'] = $userreview;
            $line['userhasactions'] = $userhasactions;
            $line['userattemptid'] = $userattempt->id;
            $line['cmid'] = $cm->id;
            $line['hide_show'] = false;
            $line['hide'] = 0;
            $line['show'] = 0;
            $line['contextid'] = $context->id;
            $line['starttest_url'] = $CFG->wwwroot.'/mod/quiz/view.php?id='.$cm->id.'';
            $line['certificate_exists'] = $certificate_exists;
            $line['certificate_download'] = $certificate_download;
            $line['certificateid'] = $certificateid;
        }
        $data[] = $line;
    }
    return array('totalrecords' => $recordscount,'records' => $data);
}

function local_onlinetests_output_fragment_enrolled_users($args) {
    global $DB, $USER;
    $record = (object) $args;
    $sytemcontext = \context_system::instance();

    $core_component = new \core_component();
    $certificate_plugin_exist = $core_component::get_plugin_directory('local', 'certificates');

    if($certificate_plugin_exist){
        $certid = $DB->get_field('local_onlinetests', 'certificateid', array('id'=>$record->testid));
    }else{
        $certid = false;
    }


    if ($record->type == 1) {
        $sql ="SELECT ou.*,u.id as userid,u.firstname, u.lastname, u.email, u.open_employeeid, 
        o.id as onlinetestid, o.quizid
                           from {local_onlinetest_users} ou
                           JOIN {local_onlinetests} o ON ou.onlinetestid = o.id
                           JOIN {user} u ON ou.userid=u.id AND u.deleted = 0 AND u.suspended = 0
                           where ou.onlinetestid = ?  ";
        if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $sytemcontext))){
            $sql .= " AND u.open_costcenterid = {$USER->open_costcenterid}";
        }
        if(!has_capability('local/costcenter:manage_ownorganization', $sytemcontext)){
            $sql .= " AND u.open_departmentid = {$USER->open_departmentid}";
        }
        $assignedusers= $DB->get_records_sql($sql, array($record->testid));
        $out='';
        $data=array();
        if(!empty($assignedusers)){
            foreach($assignedusers as $assigneduser){
                $row=array();
                // $user=$DB->get_record_sql("SELECT * FROM {user} WHERE id=$assigneduser->userid");
                
                $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$assigneduser->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
                $gradepass = ($gradeitem->gradepass) ? round($gradeitem->gradepass,2): '-';
                if ($gradeitem->id)
                $usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = ? AND userid = ? ", [$gradeitem->id, $assigneduser->userid]);
                if ($usergrade) {
                    $mygrade = round($usergrade->finalgrade, 2);
                    if ($usergrade->finalgrade >= $gradepass) {
                        $status = get_string('completed', 'local_onlinetests');
                    } else {
                        $status = get_string('incompleted', 'local_onlinetests');
                    }                   
                } else {
                    $mygrade = '-';
                    $attempt = $DB->get_record_sql("SELEct max(attempt) as noofattempts from {quiz_attempts} where quiz = ? AND userid = ? ", [$assigneduser->quizid, $assigneduser->userid]);
                    if ($attempt->noofattempts)
                    $status = get_string('pending', 'local_onlinetests');
                    else
                    $status = get_string('notyetstart', 'local_onlinetests');
                }
                // if($user){
                    $row[] = $assigneduser->firstname. ' '. $assigneduser->lastname;
                    $row[] = $assigneduser->email;
                    $row[] = ($assigneduser->open_employeeid) ? $assigneduser->open_employeeid:'-';
                    $row[] = date('d-m-Y', $assigneduser->timecreated);
                    $row[] = $mygrade;
                    $row[] = $status;
                // }
                $data[]=$row;
            }
        } 
        $table = new html_table();
        $head = array('<b>'.get_string('employee', 'local_onlinetests').'</b>', '<b>'.get_string('email').'</b>','<b>'.get_string('employeeid', 'local_users').'</b>','<b>'.get_string('enrolledon', 'local_onlinetests').'</b>', '<b>'.get_string('grade', 'local_onlinetests').'</b>','<b>'.get_string('status', 'local_onlinetests').'</b>');

        $table->head = $head;
        $table->width = '100%';
        $table->align = array('left','left','center','center','center','left');
        $table->id ='onlinetest_assigned_users'.$id.'';
        $table->attr['class'] ='onlinetest_assigned_users';
        if ($data)
        $table->data = $data;
        else
        $table->data = 'No users';
        $out.= html_writer::table($table);
        $out.=html_writer::script('$(document).ready(function() {
            $("#onlinetest_assigned_users'.$id.'").dataTable({
                language: {
                    emptyTable: "No Records Found",
                    paginate: {
                        previous: "<",
                        "next": ">"
                    }
                },
            });
        });');
    }  else {
        $sql = "SELECT distinct(u.id) as userid, u.firstname,u.lastname,u.email, u.open_employeeid, o.id as testid, o.quizid, ou.timecreated,ou.timemodified
                       from {local_onlinetest_users} ou
                       JOIN {local_onlinetests} o ON ou.onlinetestid = o.id
                       JOIN {user} u ON ou.userid = u.id AND u.deleted = 0 AND u.suspended = 0
                       where o.id = ? AND ou.status = 1 ";
        if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $sytemcontext))){
            $sql .= " AND u.open_costcenterid = {$USER->open_costcenterid}";
        }
        if(!has_capability('local/costcenter:manage_ownorganization', $sytemcontext)){
            $sql .= " AND u.open_departmentid = {$USER->open_departmentid}";
        }
        $assignedusers = $DB->get_records_sql($sql, array($record->testid));
        $out = '';
        $data = array();
        if(!empty($assignedusers)){
           foreach($assignedusers as $assigneduser){
                $row = array();
                // $user = $DB->get_record_sql("SELECT * FROM {user} WHERE id = ? ", [$assigneduser->userid]);
                $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$assigneduser->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
                if ($gradeitem->id)
                $usergrade = $DB->get_record_sql("SELECT * FROM {grade_grades} where itemid = ? AND userid = ? ", [$gradeitem->id, $assigneduser->userid]);
                if ($usergrade) {
                    $mygrade = round($usergrade->finalgrade, 2);
                } else {
                    $mygrade = '-';
                }
                // if($user){
                    $row[] = $assigneduser->firstname. ' '. $assigneduser->lastname;
                    $row[] = $assigneduser->email;
                    $row[] = ($assigneduser->open_employeeid) ? $assigneduser->open_employeeid:'-';
                    $row[] = $mygrade;
                    $row[] = date('d-m-Y', $assigneduser->timecreated);
                    $row[] = date('d-m-Y', $assigneduser->timemodified);
                    
                    if($certid){
                        $array = array('ctid'=>$certid, 'mtype'=>'onlinetest','mid'=>$record->testid, 'uid'=>$assigneduser->userid);
                        $icon = '<i class="icon fa fa-download" aria-hidden="true"></i>';
                        $url = new moodle_url('/local/certificates/view.php',$array);
                        $downloadlink = html_writer::link($url, $icon, array('title'=>get_string('download_certificate','local_certificates')));
                        $row[] = $downloadlink;
                    }
                    

                // }
                $data[] = $row;
             }        
        }
        $table = new html_table();
        $head = array('<b>'.get_string('username', 'local_onlinetests').'</b>', '<b>'.get_string('email').'</b>','<b>'.get_string('employeeid', 'local_users').'</b>', '<b>'.get_string('grade', 'local_onlinetests').'</b>','<b>'.get_string('enrolledon', 'local_onlinetests').'</b>','<b>'.get_string('completedon', 'local_onlinetests').'</b>');
        if($certid){
            $head[] = get_string('certificate','local_certificates');
        }
        $table->head = $head;
        $align = array('left','left','center','center','center','center');
        if($certid){
            $align[] = 'center';
        }
        $table->align = $align;
        if ($data)
        $table->data = $data;
        else
        $table->data = 'No users';
        $table->width = '100%';
        $table->id ='completed_users_view'.$id.'';
        $table->attr['class'] ='completed_users_view';        
        $out.= html_writer::table($table);
        $out.=html_writer::script('$(document).ready(function() {
             $("#completed_users_view'.$id.'").dataTable({
                bInfo : false,
                lengthMenu: [5, 10, 25, 50, -1],
                    language: {
                              emptyTable: "No Records Found",
                                paginate: {
                                            previous: "<",
                                            next: ">"
                                        }
                         },
             });
        });');
    }

    return $out;
}

/*
* Author sarath
* @return true for reports under category
*/
function learnerscript_onlinetests_list(){
    return 'Onlinetests';
}
function onlinetests_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $onlinetestlist=array();
    $data=data_submitted();
    
    if(is_siteadmin()){
        $onlinetest_sql="SELECT id, name AS fullname FROM {local_onlinetests} WHERE 1=1 ";
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $onlinetest_sql="SELECT id, name AS fullname FROM {local_onlinetests} WHERE costcenterid={$USER->open_costcenterid} ";
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $onlinetest_sql="SELECT id, name AS fullname FROM {local_onlinetests} WHERE costcenterid={$USER->open_costcenterid} AND departmentid = {$USER->open_departmentid} ";
    }else{
        $onlinetest_sql="SELECT id, name AS fullname FROM {local_onlinetests} WHERE id IN (SELECT onlinetestid FROM {local_onlinetest_users} WHERE userid = {$USER->id}) AND visible=1 ";
    }

    if(!empty($query)){ 
        if ($searchanywhere) {
            $onlinetest_sql.=" AND name LIKE '%$query%' ";
        } else {
            $onlinetest_sql.=" AND name LIKE '$query%' ";
        }
    }
    if(isset($data->onlinetest)&&!empty(($data->onlinetest))){
    
        $implode=implode(',',$data->onlinetest);
        
        $onlinetest_sql.=" AND id in ($implode) ";
    }
    // $departmentslist_sql.="  LIMIT $page, $perpage";
    if(!empty($query)||empty($mform)){ 
        $onlinetestlist = $DB->get_records_sql($onlinetest_sql, array(), $page, $perpage);
        return $onlinetestlist;
    }
    if((isset($data->departments)&&!empty($data->departments))){ 
        $onlinetestlist = $DB->get_records_sql_menu($onlinetest_sql, array(), $page, $perpage);
    }
    
    $options = array(
            'ajax' => 'local_courses/form-options-selector',
            'multiple' => true,
            'data-action' => 'onlinetests',
            'data-options' => json_encode(array('id' => 0)),
            'placeholder' => get_string('onlinetest','local_onlinetests')
    );
        
    $select = $mform->addElement('autocomplete', 'onlinetest', '', $onlinetestlist,$options);
    $mform->setType('onlinetest', PARAM_RAW);
}

/**
 * Returns onlinetests tagged with a specified tag.
 *
 * @param local_tags_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \local_tags\output\tagindex
 */
function local_onlinetests_get_tagged_tests($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0, $sort = '') {
    global $CFG, $PAGE;
    // prepare for display of tags related to tests
    $perpage = $exclusivemode ? 10 : 5;
    $displayoptions = array(
        'limit' => $perpage,
        'offset' => $page * $perpage,
        'viewmoreurl' => null,
    );
    $renderer = $PAGE->get_renderer('local_onlinetests');
    $totalcount = $renderer->tagged_onlinetests($tag->id, $exclusivemode, $ctx, $rec, $displayoptions, $count = 1,$sort);
    $content = $renderer->tagged_onlinetests($tag->id, $exclusivemode, $ctx, $rec, $displayoptions,0,$sort);
    $totalpages = ceil($totalcount / $perpage);
    if ($totalcount)
    return new local_tags\output\tagindex($tag, 'local_onlinetests', 'onlinetests', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
    else
    return '';
}


function get_test_details($testid) { // test id not quizid
    global $USER, $DB;
    $context = context_system::instance();    
    $details = array();
    $joinsql = '';
    if(has_capability('local/costcenter:manage_ownorganization',$context) OR 
        has_capability('local/costcenter:manage_owndepartments',$context)) {
        $selectsql = "select o.id as oid, o.quizid ";
        $fromsql = " from  {local_onlinetests} o ";
        if ($DB->get_manager()->table_exists('local_rating')) {
                $selectsql .= " , AVG(rating) as avg ";
                $joinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = o.id AND r.ratearea = 'local_onlinetests' ";
            }

        $wheresql = "where o.id = ?";

        $adminrecord = $DB->get_record_sql($selectsql.$fromsql.$joinsql.$wheresql, [$testid]);
        $details['manage'] = 1;
        $completedcount = $DB->count_records_sql("select count(ou.id) from {local_onlinetest_users} ou, {user} u where
            u.id = ou.userid AND u.deleted = 0 AND u.suspended = 0 AND ou.onlinetestid=? AND ou.status=?", array($testid, 1));
        $enrolledcount = $DB->count_records_sql("select count(ou.id) from {local_onlinetest_users} ou, {user} u where
            u.id = ou.userid AND u.deleted = 0 AND u.suspended = 0 AND ou.onlinetestid=? ", array($testid));
        $details['completed'] = $completedcount;
        $details['enrolled'] = $enrolledcount;
        $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$adminrecord->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
        $details['maxgrade'] = ($gradeitem->grademax) ? round($gradeitem->grademax): '-';
        $details['passgrade'] = ($gradeitem->gradepass) ? round($gradeitem->gradepass): '-';
    } else {
        $selectsql = "select ou.*, o.quizid, o.id as oid ";
        $fromsql = " from {local_onlinetest_users} ou 
        JOIN {local_onlinetests} o ON o.id = ou.onlinetestid ";
        if ($DB->get_manager()->table_exists('local_rating')) {
            $selectsql .= " , AVG(rating) as avg ";
            $joinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = o.id AND r.ratearea = 'local_evaluation' ";
        }
        $wheresql = "where 1 = 1 AND userid = ? AND o.id = ? ";

        $record = $DB->get_record_sql($selectsql.$fromsql.$joinsql.$wheresql, [$USER->id, $testid]);


        $sql="SELECT * FROM {quiz_attempts} where id=(SELECT max(id) id from {quiz_attempts} where userid=? and quiz=?)";
        $userattempt = $DB->get_record_sql($sql, [$USER->id, $record->quizid]);
        $details['manage'] = 0;
        $details['status'] = ($record->status == 1) ? get_string('completed', 'local_onlinetests'):get_string('pending', 'local_onlinetests');
        $details['enrolled'] = ($record->timecreated) ? date('d-m-Y', $record->timecreated): '-';
        $details['completed'] = ($record->timemodified) ? date('d-m-Y', $record->timemodified): '-';
        $details['attempts'] = $userattempt;
    }
    
    return $details;
}
