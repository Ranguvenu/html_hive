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
// use \local_onlinetests\notificationemails as onlinetestsnotifications_emails;

/**
 * Event observer for local_onlinetests.
 */
class local_onlinetests_observer {

    /**
     * Triggered via attempt_submitted event.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB, $CFG, $USER;
        if(file_exists($CFG->dirroot.'/local/lib.php')){
            require_once($CFG->dirroot.'/local/lib.php');
        }
        // require_once($CFG->dirroot.'/local/onlinetests/notifications_emails.php');
        $cp = (object)$event->other;
        $quizid = $cp->quizid;
        $userid = $cp->submitterid;
        $cm = get_coursemodule_from_instance('quiz', $quizid, 0, false, MUST_EXIST);
        if ($cm) { // if module exists only
            $userquiz = null;
            $onlinetest = $DB->get_record('local_onlinetests', array('quizid'=>$quizid));
            $type = 'onlinetest_completed';
            if ($onlinetest)
                $userquiz = $DB->get_record('local_onlinetest_users', array('onlinetestid'=>$onlinetest->id, 'userid'=>$userid));
            if ($userquiz AND ($userquiz->status != 1) ) {
                $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
                $usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = $gradeitem->id AND userid = $userid");
                $gradepass = round($gradeitem->gradepass);
                if ($usergrade) {
                    if ($usergrade->finalgrade >= $gradepass) {
                        $userquiz->status = 1;
                        $userquiz->timemodified = $usergrade->timemodified;
                    }		
                }
                $DB->update_record('local_onlinetest_users', $userquiz);
                if($userquiz->status){
                    $params = array(
                        'context' => \context_system::instance(),
                        'objectid' => $onlinetest->id,
                        'courseid' => 1,
                        'userid' => $userid,
                        'relateduserid' => $userid,
                    );
                    $event = \local_onlinetests\event\onlinetest_completed::create($params);
                    $event->add_record_snapshot('local_onlintest', $onlinetest->id);
                    $event->trigger();
                }
                $notification = new \local_onlinetests\notification();
                $touser = \core_user::get_user($userid);
                $fromuser = \core_user::get_user($userquiz->creatorid);
                $logmail = $notification->onlinetest_notification($type, $touser, $fromuser, $onlinetest);
            }
        }
    }
    public static function onlinetest_attempt_started(\mod_quiz\event\course_module_viewed $event){
        global $DB, $CFG, $USER, $PAGE;
        $courseid = $event->courseid;
        $systemcontext = \context_system::instance();
        if($courseid == 1){
            $PAGE->requires->js_call_amd('local_onlinetests/onlinetests', 'hide_element_icon', array('#action-menu-3-menu .dropdown-item:first-child'));//selector to be sent
            // $PAGE->requires->js_call_amd('local_onlinetests/onlinetests', 'hide_element_icon', array('#action-menu-3-menubar'));//selector to be sent
            $PAGE->requires->js_call_amd('local_onlinetests/onlinetests', 'change_element_attribute', array('.continuebutton form', 'action', $CFG->wwwroot.'/local/onlinetests/index.php'));//selector to be sent
            $PAGE->requires->js_call_amd('local_onlinetests/onlinetests', 'hide_element_icon', array('.page-context-header .page-header-headings h3'));
            $onlinetestid = $event->objectid;
            $onlinetest = $DB->get_record('local_onlinetests', array('quizid' => $onlinetestid), 'id, costcenterid, departmentid');

            if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext))){ // || has_capability('local/onlinetests:manage', $systemcontext)
                $isenrolled_sql = "SELECT lou.id FROM {local_onlinetest_users} AS lou
                    JOIN {local_onlinetests} AS lo ON lo.id=lou.onlinetestid 
                    WHERE lo.quizid = :moduleid AND lo.visible=1 AND lou.userid = :userid ";
                $isenrolled = $DB->record_exists_sql($isenrolled_sql,  array('moduleid' => $onlinetestid, 'userid' => $USER->id));
                if(!$isenrolled){
                    $is_oh = has_capability('local/costcenter:manage_ownorganization', $systemcontext);
                    $is_dh = has_capability('local/costcenter:manage_owndepartments', $systemcontext);
                    if(($is_oh || $is_dh) && $onlinetest->costcenterid != $USER->open_costcenterid){
                        redirect($CFG->wwwroot.'/local/onlinetests/index.php');
                    }else if(!$is_oh && $is_dh && $onlinetest->departmentid != $USER->open_departmentid){
                        redirect($CFG->wwwroot.'/local/onlinetests/index.php');   
                    }else if (!$is_oh && !$is_dh){
                        redirect($CFG->wwwroot.'/local/onlinetests/index.php');
                    }
                }
            // }else if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext))){
                // $onlinetest = $DB->get_record('local_onlinetests', array('quizid' => $onlinetest));
            }
        // }else if(!is_siteadmin() && $courseid == 1 && ){
        }

    }
}
