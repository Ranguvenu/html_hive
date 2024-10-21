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
 * Classroom View
 *
 * @package    local_classroom
 * @copyright  2017 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_classroom;
defined('MOODLE_INTERNAL') || die();
use context_system;
use stdClass;
use moodle_url;
use completion_completion;
use html_table;
use html_writer;
use core_component;
require_once($CFG->dirroot . '/local/classroom/lib.php');
// Classroom.
define('CLASSROOM_NEW', 0);
define('CLASSROOM_ACTIVE', 1);
define('CLASSROOM_HOLD', 2);
define('CLASSROOM_CANCEL', 3);
define('CLASSROOM_COMPLETED', 4);
// Session Attendance.
define('SESSION_PRESENT', 1);
define('SESSION_ABSENT', 2);
// Types.
define('CLASSROOM', 1);
define('LEARNINGPLAN', 2);
define('CERTIFICATE', 3);
class classroom {
    protected $classroomid;
    protected $classroom;
    protected $clasroomcourses = array();
    protected $classroomcourse;
    protected $clasroomusers = array();
    protected $classroomuser;
    protected $clasroomsessions = array();
    protected $classroomsession;
    protected $clasroomtrainers = array();
    protected $classroomtrainer;
    protected $clasroomevaluations = array();
    protected $clasroomevaluation;
    protected $clasroomattendance = array();
    /**
     * [classroomtypes description]
     * @method classroomtypes
     * @return [type]         [description]
     */
    public static function classroomtypes() {
        return array(
            1 => get_string('classroom', 'local_classroom'),
            2 => get_string('learningplan', 'local_classroom'),
            3 => get_string('certificate', 'local_classroom')
        );
    }
    /**
     * Manage Classroom (Create or Update the classroom)
     * @method manage_classroom
     * @param  Object           $data Clasroom Data
     * @return Integer               Classroom ID
     */
    public function manage_classroom($classroom) {
        global $DB, $USER;
        $classroom->shortname = $classroom->name;
        if (empty($classroom->trainers)) {
            $classroom->trainers = null;
        } //empty($classroom->trainers)
        if (empty($classroom->capacity) || $classroom->capacity == 0) {
            $classroom->capacity = null;
        } //empty($classroom->capacity) || $classroom->capacity == 0
        try
        {
            if ($classroom->id > 0) {
                $classroom->timemodified = time();
                $classroom->usermodified = $USER->id;
                $localclassroom          = $DB->get_record_sql("SELECT id,startdate,enddate,
                    allow_multi_session,instituteid FROM{local_classroom}
                    where id=$classroom->id");
                $allowmultisession       = $localclassroom->allow_multi_session;
                // If(($classroom->startdate != $localclassroom->startdate) ||
                // ($classroom->enddate != $localclassroom->enddate)){
                // $this->classroom_sessions_delete($classroom->id);
                // }.
                $DB->update_record('local_classroom', $classroom);
                $this->classroom_set_events($classroom); // Added by sreenivas.
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroom->id,
                    'other' => 'classroom',
                    'url' => 'view.php',
                    ''
                );
                // Trigger classroom updated event.
                $event  = \local_classroom\event\classroom_updated::create($params);
                $event->add_record_snapshot('local_classroom', $classroom->id);
                $event->trigger();
            } //$classroom->id > 0
            else {
                $classroom->status      = 0;
                $classroom->timecreated = time();
                $classroom->usercreated = $USER->id;
                if (has_capability('local/classroom:manageclassroom', context_system::instance())) {
                    $classroom->department = -1;
                    if ((has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                        $classroom->department = $USER->open_departmentid;
                    } //(has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))
                } //has_capability('local/classroom:manageclassroom', context_system::instance())
                $classroom->id = $DB->insert_record('local_classroom', $classroom);
                $params        = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroom->id
                );
                $event         = \local_classroom\event\classroom_created::create($params);
                $event->add_record_snapshot('local_classroom', $classroom->id);
                $event->trigger();
                $classroom->shortname = 'class' . $classroom->id;
                $DB->update_record('local_classroom', $classroom);
                // Trigger classroom updated event.
                // $event = \local_classroom\event\classroom_updated::create($params);
                // $event->add_record_snapshot('local_classroom', $classroom->id);
                // $event->trigger();
            }
            if ($classroom->id) {
                $this->manage_classroom_trainers($classroom->id, 'all', $classroom->trainers);
                $sessionscount = $DB->count_records('local_classroom_sessions', array(
                    'classroomid' => $classroom->id
                ));
                if (($classroom->id == 0 && $classroom->allow_multi_session == 1) || (($classroom->allow_multi_session != $allowmultisession || $sessionscount == 0) && $classroom->id > 0 && $classroom->allow_multi_session == 1)) {
                    $this->manage_classroom_automatic_sessions($classroom->id, $classroom->startdate, $classroom->enddate);
                } //($classroom->id == 0 && $classroom->allow_multi_session == 1) || (($classroom->allow_multi_session != $allowmultisession || $sessionscount == 0) && $classroom->id > 0 && $classroom->allow_multi_session == 1)
            } //$classroom->id
        }
        catch (dml_exception $ex)
        {
            print_error($ex);
        }
        return $classroom->id;
    }
    /**
     * This creates new events given as timeopen and closeopen by classroom.
     * @global object
     * @param object $classroom
     * @return void
     */
    public function classroom_set_events($classroom) {
        global $DB, $CFG, $USER;
        // Include calendar/lib.php.
        require_once($CFG->dirroot . '/calendar/lib.php');
        // evaluation start calendar events.
        $eventid = $DB->get_field('event', 'id', array(
            'modulename' => '0',
            'instance' => 0,
            'plugin' => 'local_classroom',
            'plugin_instance' => $classroom->id,
            'eventtype' => 'open',
            'local_eventtype' => 'open'
        ));
        if (isset($classroom->startdate) && $classroom->startdate > 0) {
            $event                  = new stdClass();
            $event->eventtype       = 'open';
            $event->type            = empty($classroom->enddate) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
            $event->name            = $classroom->name;
            $event->description     = $classroom->name;
            $event->timestart       = $classroom->startdate;
            $event->timesort        = $classroom->startdate;
            $event->visible         = 1;
            $event->timeduration    = 0;
            $event->plugin_instance = $classroom->id;
            $event->plugin          = 'local_classroom';
            $event->local_eventtype = 'open';
            $event->relateduserid   = $USER->id;
            if ($eventid) {
                // Calendar event exists so update it.
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } //$eventid
            else {
                // Event doesn't exist so create one.
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                $event->eventtype  = 'open';
                ;
                \calendar_event::create($event);
            }
        } //isset($classroom->startdate) && $classroom->startdate > 0
        else if ($eventid) {
            // Calendar event is on longer needed.
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        } //$eventid
        // evaluation close calendar events.
        $eventid = $DB->get_field('event', 'id', array(
            'modulename' => '0',
            'instance' => 0,
            'plugin' => 'local_classroom',
            'plugin_instance' => $classroom->id,
            'eventtype' => 'close',
            'local_eventtype' => 'close'
        ));
        if (isset($classroom->enddate) && $classroom->enddate > 0) {
            $event                  = new stdClass();
            $event->type            = CALENDAR_EVENT_TYPE_ACTION;
            $event->eventtype       = 'close';
            $event->name            = $classroom->name;
            $event->description     = $classroom->name;
            $event->timestart       = $classroom->enddate;
            $event->timesort        = $classroom->enddate;
            $event->visible         = 1;
            $event->timeduration    = 0;
            $event->plugin_instance = $classroom->id;
            $event->plugin          = 'local_classroom';
            $event->local_eventtype = 'close';
            $event->relateduserid   = $USER->id;
            if ($eventid) {
                // Calendar event exists so update it.
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } //$eventid
            else {
                // Event doesn't exist so create one.
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                \calendar_event::create($event);
            }
        } //isset($classroom->enddate) && $classroom->enddate > 0
        else if ($eventid) {
            // Calendar event is on longer needed.
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        } //$eventid
    }
    /**
     * Manage Classroom Sessions (Create / Update)
     * @method session_management
     * @param  Object             $data Session Data
     * @return Integer                  Session ID
     */
    public function manage_classroom_sessions($session) {
        global $DB, $USER;
        $session->description = $session->cs_description['text'];
        try {
            $sessionsvalidationstart = $this->sessions_validation($session->classroomid, $session->timestart, $session->id);
            $session->duration       = ($session->timefinish - $session->timestart) / 60;
            if ($sessionsvalidationstart) {
                return true;
            } //$sessionsvalidationstart
            $sessionsvalidationend = $this->sessions_validation($session->classroomid, $session->timefinish, $session->id);
            if ($sessionsvalidationend) {
                return true;
            } //$sessionsvalidationend
            if ($session->id > 0) {
                $session->timemodified = time();
                $session->usermodified = $USER->id;
                // print_object($session);exit;
                $DB->update_record('local_classroom_sessions', $session);
                $this->session_set_events($session); // added by sreenivas
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $session->id
                );
                $event  = \local_classroom\event\classroom_sessions_updated::create($params);
                $event->add_record_snapshot('local_classroom', $session->classroomid);
                $event->trigger();
                if ($session->onlinesession == 1) {
                    $onlinesessionsintegration = new \local_classroom\event\online_sessions_integration();
                    $onlinesessionsintegration->online_sessions_type($session, $session->id, $type = 1, 'update');
                } //$session->onlinesession == 1
                $classroom                = new stdClass();
                $classroom->id            = $session->classroomid;
                $classroom->totalsessions = $DB->count_records('local_classroom_sessions', array(
                    'classroomid' => $session->classroomid
                ));
                $DB->update_record('local_classroom', $classroom);
            } //$session->id > 0
            else {
                //calender event
                // $main = new \local_o365\feature\calsync\main();
                // $apiclient = $main->construct_calendar_api(996, true);
                // $attendees = $DB->get_records('user', [], 'id DESC', '*', 0,2);
                // $response = $apiclient->create_event('this is subject', 'This is message body', time()+86400, time()+90000, $attendees, [],null);
                // $outlookeventid = $response->id; 
                $session->timecreated = time();
                $session->usercreated = $USER->id;
              //  $session->outlookeventid = $outlookeventid;
                $session->id = $DB->insert_record('local_classroom_sessions', $session);
                $this->session_set_events($session); // added by sreenivas
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $session->id
                );
                $event  = \local_classroom\event\classroom_sessions_created::create($params);
                $event->add_record_snapshot('local_classroom', $session->classroomid);
                $event->trigger();
                if ($session->id) {
                    if ($session->onlinesession == 1) {
                        $onlinesessionsintegration = new \local_classroom\event\online_sessions_integration();
                        $onlinesessionsintegration->online_sessions_type($session, $session->id, $type = 1, 'create');
                    } //$session->onlinesession == 1
                    $classroom                 = new stdClass();
                    $classroom->id             = $session->classroomid;
                    $classroom->totalsessions  = $DB->count_records('local_classroom_sessions', array(
                        'classroomid' => $session->classroomid
                    ));
                    $classroom->activesessions = $DB->count_records('local_classroom_sessions', array(
                        'classroomid' => $session->classroomid,
                        'attendance_status' => 1
                    ));
                    $DB->update_record('local_classroom', $classroom);
                } //$session->id
            }
        }
        catch (dml_exception $ex) {
            print_error($ex);
        }
        return $session->id;
    }
    /**
     * This creates new events given as timeopen and timeclose by session.
     *
     * @global object
     * @param object session
     * @return void
     */
    public function session_set_events($session) {
        global $DB, $CFG, $USER;
        // Include calendar/lib.php.
        require_once($CFG->dirroot . '/calendar/lib.php');
        // session start calendar events.
        $eventid = $DB->get_field('event', 'id', array(
            'modulename' => '0',
            'instance' => 0,
            'plugin' => 'local_classroom',
            'plugin_instance' => $session->classroomid,
            'plugin_itemid' => $session->id,
            'eventtype' => 'open',
            'local_eventtype' => 'session_open'
        ));
        if (isset($session->timestart) && $session->timestart > 0) {
            $event                  = new stdClass();
            $event->eventtype       = 'open';
            $event->type            = empty($session->timefinish) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
            $event->name            = $session->name;
            $event->description     = $session->name;
            $event->timestart       = $session->timestart;
            $event->timesort        = $session->timestart;
            $event->visible         = 1;
            $event->timeduration    = 0;
            $event->plugin_instance = $session->classroomid;
            $event->plugin_itemid   = $session->id;
            $event->plugin          = 'local_classroom';
            $event->local_eventtype = 'session_open';
            $event->relateduserid   = $USER->id;
            if ($eventid) {
                // Calendar event exists so update it.
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } //$eventid
            else {
                // Event doesn't exist so create one.
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                $event->eventtype  = 'open';
                \calendar_event::create($event);
            }
        } //isset($session->timestart) && $session->timestart > 0
        else if ($eventid) {
            // Calendar event is on longer needed.
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        } //$eventid
        // session close calendar events.
        $eventid = $DB->get_field('event', 'id', array(
            'modulename' => '0',
            'instance' => 0,
            'plugin' => 'local_classroom',
            'plugin_instance' => $session->classroomid,
            'plugin_itemid ' => $session->id,
            'eventtype' => 'close',
            'local_eventtype' => 'session_close'
        ));
        if (isset($session->timefinish) && $session->timefinish > 0) {
            $event                  = new stdClass();
            $event->type            = CALENDAR_EVENT_TYPE_ACTION;
            $event->eventtype       = 'close';
            $event->name            = $session->name;
            $event->description     = $session->name;
            $event->timestart       = $session->timefinish;
            $event->timesort        = $session->timefinish;
            $event->visible         = 1;
            $event->timeduration    = 0;
            $event->plugin_instance = $session->classroomid;
            $event->plugin_itemid   = $session->id;
            $event->plugin          = 'local_classroom';
            $event->local_eventtype = 'session_close';
            $event->relateduserid   = $USER->id;
            if ($eventid) {
                // Calendar event exists so update it.
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } //$eventid
            else {
                // Event doesn't exist so create one.
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                \calendar_event::create($event);
            }
        } //isset($session->timefinish) && $session->timefinish > 0
        else if ($eventid) {
            // Calendar event is on longer needed.
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        } //$eventid
    }
    public function manage_classroom_completions($completions) {
        global $DB, $USER;
        if (!empty($completions->sessionids) && is_array($completions->sessionids)) {
            $completions->sessionids = implode(',', $completions->sessionids);
        } //!empty($completions->sessionids) && is_array($completions->sessionids)
        else {
            $completions->sessionids = null;
        }
        if (!empty($completions->courseids) && is_array($completions->courseids)) {
            $completions->courseids = implode(',', $completions->courseids);
        } //!empty($completions->courseids) && is_array($completions->courseids)
        else {
            $completions->courseids = null;
        }
        if (empty($completions->sessiontracking)) {
            $completions->sessiontracking = null;
        } //empty($completions->sessiontracking)
        if (empty($completions->coursetracking)) {
            $completions->coursetracking = null;
        } //empty($completions->coursetracking)
        try {
            if ($completions->id > 0) {
                $completions->timemodified = time();
                $completions->usermodified = $USER->id;
                $DB->update_record('local_classroom_completion', $completions);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id
                );
                $event  = \local_classroom\event\classroom_completions_settings_updated::create($params);
                $event->add_record_snapshot('local_classroom', $completions->classroomid);
                $event->trigger();
            } //$completions->id > 0
            else {
                $completions->timecreated = time();
                $completions->usercreated = $USER->id;
                $completions->id          = $DB->insert_record('local_classroom_completion', $completions);
                $params                   = array(
                    'context' => context_system::instance(),
                    'objectid' => $completions->id
                );
                $event                    = \local_classroom\event\classroom_completions_settings_created::create($params);
                $event->add_record_snapshot('local_classroom', $completions->classroomid);
                $event->trigger();
            }
        }
        catch (dml_exception $ex) {
            print_error($ex);
        }
        return $completions->id;
    }
    /**
     * [classroom_sessions_delete description]
     * @param  [type] $classroomid [description]
     * @return [type]              [description]
     */
    public function classroom_sessions_delete($classroomid) {
        global $DB, $USER;
        $classroomsessions = $DB->get_records_sql_menu("SELECT id,id as sessionid FROM {local_classroom_sessions}
                                                where classroomid = $classroomid");
        foreach ($classroomsessions as $id) {
            $DB->delete_records('local_classroom_attendance', array(
                'sessionid' => $id
            ));
            $params = array(
                'context' => context_system::instance(),
                'objectid' => $id
            );
            $event  = \local_classroom\event\classroom_sessions_deleted::create($params);
            $event->add_record_snapshot('local_classroom', $classroomid);
            $event->trigger();
            $DB->delete_records('local_classroom_sessions', array(
                'id' => $id
            ));
            $classroom                 = new stdClass();
            $classroom->id             = $classroomid;
            $classroom->totalsessions  = $DB->count_records('local_classroom_sessions', array(
                'classroomid' => $classroomid
            ));
            $classroom->activesessions = $DB->count_records('local_classroom_sessions', array(
                'classroomid' => $classroomid,
                'attendance_status' => 1
            ));
            $DB->update_record('local_classroom', $classroom);
        } //$classroomsessions as $id
        $classroomusers = $DB->get_records_menu('local_classroom_users', array(
            'classroomid' => $classroomid
        ), 'id', 'id, userid');
        foreach ($classroomusers as $classroomuser) {
            $attendedsessions      = $DB->count_records('local_classroom_attendance', array(
                'classroomid' => $classroomid,
                'userid' => $classroomuser,
                'status' => SESSION_PRESENT
            ));
            $attendedsessionshours = $DB->get_field_sql("SELECT ((sum(lcs.duration))/60) AS hours
                                                FROM {local_classroom_sessions} as lcs
                                                WHERE  lcs.classroomid = $classroomid
                                                and lcs.id in(SELECT sessionid  FROM {local_classroom_attendance}
                                                where classroomid = $classroomid and userid = $classroomuser
                                                and status = 1)");
            if (empty($attendedsessionshours)) {
                $attendedsessionshours = 0;
            } //empty($attendedsessionshours)
            $DB->execute('UPDATE {local_classroom_users} SET attended_sessions = ' . $attendedsessions . ',hours = ' . $attendedsessionshours . ', timemodified = ' . time() . ',
                        usermodified = ' . $USER->id . ' WHERE classroomid = ' . $classroomid . ' AND userid = ' . $classroomuser);
        } //$classroomusers as $classroomuser
    }
    /**
     * Update Classroom Location and Date
     * @method location_date
     * @param  Object        $data Classroom Location and Nomination Data
     * @return Integer        Classroom ID
     */
    public function location_date($data) {
        global $DB, $USER;
        $location                       = new stdClass();
        $location->institute_type       = $data->institute_type;
        $location->instituteid          = $data->instituteid;
        $location->nomination_startdate = $data->nomination_startdate;
        $location->nomination_enddate   = $data->nomination_enddate;
        try {
            $localclassroom = $DB->get_record_sql("SELECT id,instituteid FROM {local_classroom} where id = $data->id");
            if (isset($location->instituteid) && ($location->instituteid != $localclassroom->instituteid) && ($localclassroom->instituteid != 0)) {
                $DB->execute('UPDATE {local_classroom_sessions} SET roomid =0,timemodified = ' . time() . ',
                   usermodified = ' . $USER->id . ' WHERE classroomid = ' . $data->id . '');
            } //isset($location->instituteid) && ($location->instituteid != $localclassroom->instituteid) && ($localclassroom->instituteid != 0)
            $location->id           = $data->id;
            $location->timemodified = time();
            $location->usermodified = $USER->id;
            $DB->update_record('local_classroom', $location);
        }
        catch (dml_exception $ex) {
            print_error($ex);
        }
        return $data->id;
    }
    /**
     * classrooms
     * @method classrooms
     * @param  Object     $stable Datatable fields
     * @return Array  Classrooms and totalclassroomcount
     */
    public function classrooms($stable, $request = false) {
        global $DB, $USER;
        $params          = array();
        $classrooms      = array();
        $classroomscount = 0;
        $concatsql       = '';
        $statusarray     = array();
        if (has_capability('local/classroom:view_newclassroomtab', context_system::instance())) {
            $statusarray[] = 0;
        } //has_capability('local/classroom:view_newclassroomtab', context_system::instance())
        $statusarray[] = 1;
        if (has_capability('local/classroom:view_holdclassroomtab', context_system::instance())) {
            $statusarray[] = 2;
        } //has_capability('local/classroom:view_holdclassroomtab', context_system::instance())
        $statusarray[] = 3;
        $statusarray[] = 4;
        if (!empty($stable->search)) {
            $fields = array(
                "name"
            );
            $fields = implode(" LIKE :search1 OR ", $fields);
            $fields .= " LIKE :search2 ";
            $params['search1'] = '%' . $stable->search . '%';
            $params['search2'] = '%' . $stable->search . '%';
            $concatsql .= " AND ($fields) ";
        } //!empty($stable->search)
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $condition            = " AND (cc.id = :costcenter)";
            $params['costcenter'] = $USER->open_costcenterid;
            $statusarrays         = implode(',', $statusarray);
            $concatsql .= " AND c.status in ($statusarrays) ";
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $condition .= " AND (c.department = :department )";
                $params['department'] = $USER->open_departmentid;
            } //(has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))
            $concatsql .= $condition;
            if (has_capability('local/classroom:trainer_viewclassroom', context_system::instance())) {
                $myclassrooms = $DB->get_records_menu('local_classroom_trainers', array(
                    'trainerid' => $USER->id
                ), 'id', 'id, classroomid');
                if (!empty($myclassrooms)) {
                    $myclassrooms = implode(', ', $myclassrooms);
                    $concatsql .= " AND c.id IN ( $myclassrooms )";
                } //!empty($myclassrooms)
                else {
                    return compact('classrooms', 'classroomscount');
                }
            } //has_capability('local/classroom:trainer_viewclassroom', context_system::instance())
        } //(has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))
        elseif (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) {
            $myclassrooms = $DB->get_records_menu('local_classroom_users', array(
                'userid' => $USER->id
            ), 'id', 'id, classroomid');
            if (isset($stable->classroomid) && !empty($stable->classroomid)) {
                $userenrolstatus      = $DB->record_exists('local_classroom_users', array(
                    'classroomid' => $stable->classroomid,
                    'userid' => $USER->id
                ));
                $status               = $DB->get_field('local_classroom', 'status', array(
                    'id' => $stable->classroomid
                ));
                $classroom_costcenter = $DB->get_field('local_classroom', 'costcenter', array(
                    'id' => $stable->classroomid
                ));
                if ($status == 1 && !$userenrolstatus && $classroom_costcenter == $USER->open_costcenterid) {
                } //$status == 1 && !$userenrolstatus && $classroom_costcenter == $USER->open_costcenterid
                else {
                    if (!empty($myclassrooms)) {
                        $myclassrooms = implode(', ', $myclassrooms);
                        $concatsql .= " AND c.id IN ( $myclassrooms )";
                        $statusarrays = implode(',', $statusarray);
                        $concatsql .= " AND c.status in ($statusarrays) ";
                    } //!empty($myclassrooms)
                    else {
                        return compact('classrooms', 'classroomscount');
                    }
                }
            } //isset($stable->classroomid) && !empty($stable->classroomid)
            else {
                if (!empty($myclassrooms)) {
                    $myclassrooms = implode(', ', $myclassrooms);
                    $concatsql .= " AND c.id IN ( $myclassrooms )";
                    $statusarrays = implode(',', $statusarray);
                    $concatsql .= " AND c.status in ($statusarrays) ";
                } //!empty($myclassrooms)
                else {
                    return compact('classrooms', 'classroomscount');
                }
            }
        } //!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))
        else {
            $statusarrays = implode(',', $statusarray);
            $concatsql .= " AND c.status in ($statusarrays) ";
        }
        if (isset($stable->classroomid) && $stable->classroomid > 0) {
            $concatsql .= " AND c.id = :classroomid";
            $params['classroomid'] = $stable->classroomid;
        } //isset($stable->classroomid) && $stable->classroomid > 0
        if (isset($stable->classroomstatus) && $stable->classroomstatus != -1) {
            $concatsql .= " AND c.status = :classroomstatus";
            $params['classroomstatus'] = $stable->classroomstatus;
        } //isset($stable->classroomstatus) && $stable->classroomstatus != -1
        $countsql = "SELECT COUNT(c.id) ";
        if ($request == true) {
            $fromsql = "SELECT group_concat(c.id) as classroomids";
        } //$request == true
        else {
            $fromsql = "SELECT c.*, (SELECT COUNT(DISTINCT cu.userid)
                                  FROM {local_classroom_users} AS cu
                                  WHERE cu.classroomid = c.id
                              ) AS enrolled_users";
        }
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $joinon = "cc.id = c.costcenter";
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $joinon = "cc.id = c.department OR cc.id = c.costcenter";
            } //(has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))
        } //(has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))
        else {
            $joinon = "cc.id = c.costcenter";
        }
        $sql = " FROM {local_classroom} AS c
                 JOIN {local_costcenter} AS cc ON $joinon
                WHERE 1 = 1 ";
        $sql .= $concatsql;
        //echo $fromsql . $sql;
        // print_object($params);
        // echo $countsql . $sql;
        // print_object($params);
        if (isset($stable->classroomid) && $stable->classroomid > 0) {
            $classrooms = $DB->get_record_sql($fromsql . $sql, $params);
        } //isset($stable->classroomid) && $stable->classroomid > 0
        else {
            try {
                $classroomscount = $DB->count_records_sql($countsql . $sql, $params);
                if ($stable->thead == false) {
                    $sql .= " ORDER BY c.id DESC";
                    if ($request == true) {
                        $classrooms = $DB->get_record_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    } //$request == true
                    else {
                        $classrooms = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    }
                } //$stable->thead == false
            }
            catch (dml_exception $ex) {
                $classroomscount = 0;
            }
        }
        if (isset($stable->classroomid) && $stable->classroomid > 0) {
            return $classrooms;
        } //isset($stable->classroomid) && $stable->classroomid > 0
        else {
            return compact('classrooms', 'classroomscount');
        }
    }
    /**
     * Classroom sessions
     * @method sessions
     * @param  Integer   $classroomid Classroom ID
     * @param  Object   $stable      Datatable fields
     * @return Array    Sessions and total session count for the perticular classroom
     */
    public function classroomsessions($classroomid, $stable) {
        global $DB, $USER;
        $classroom = $DB->get_record('local_classroom', array(
            'id' => $classroomid
        ));
        if (empty($classroom)) {
            print_error('classroom data missing');
        } //empty($classroom)
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array(
                0 => 'cs.name',
                1 => 'cr.name'
            );
            $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $stable->search . "%' ";
            $concatsql .= " AND ($fields) ";
        } //!empty($stable->search)
        $params     = array();
        $classrooms = array();
        $countsql   = "SELECT COUNT(cs.id) ";
        $fromsql    = "SELECT cs.*, cr.name as room";
        $sql        = " FROM {local_classroom_sessions} AS cs
                LEFT JOIN {user} AS u ON u.id = cs.trainerid
                LEFT JOIN {local_location_room} AS cr ON cr.id = cs.roomid
                WHERE 1 = 1 AND cs.classroomid = $classroomid";
        $sql .= $concatsql;
        try {
            $sessionscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY cs.id ASC";
                $sessions = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            } //$stable->thead == false
        }
        catch (dml_exception $ex) {
            $sessionscount = 0;
        }
        return compact('sessions', 'sessionscount');
    }
    public function sessions_validation($classroomid, $sessiondate, $sessionid = 0) {
        global $DB;
        $return = false;
        if ($classroomid && $sessiondate) {
            $params                      = array();
            $params['classroomid']       = $classroomid;
            $params['sessiondate_start'] = date('Y-m-d H:i', $sessiondate);
            $params['sessiondate_end']   = date('Y-m-d H:i', $sessiondate);
            $sql                         = "SELECT * FROM {local_classroom_sessions} where classroomid=:classroomid and (from_unixtime(timestart,'%Y-%m-%d %H:%i')=:sessiondate_start or from_unixtime(timefinish,'%Y-%m-%d %H:%i')=:sessiondate_end)";
            if ($sessionid > 0) {
                $sql .= " AND id !=:sessionid ";
                $params['sessionid'] = $sessionid;
            } //$sessionid > 0
            // print_object($params);
            // echo $sql;
            $return = $DB->record_exists_sql($sql, $params);
        } //$classroomid && $sessiondate
        return $return;
    }
    /**
     * [add_classroom_signups description]
     * @method add_classroom_signups
     * @param  [type]                $classroomid [description]
     * @param  [type]                $userid      [description]
     * @param  integer               $sessionid   [description]
     */
    public function add_classroom_signups($classroomid, $userid, $sessionid = 0) {
        global $DB, $USER;
        $classroom = $DB->record_exists('local_classroom', array(
            'id' => $classroomid
        ));
        if (!$classroom) {
            print_error("Classroom Not Found!");
        } //!$classroom
        $user = $DB->record_exists('user', array(
            'id' => $userid
        ));
        if (!$user) {
            print_error("User Not Found!");
        } //!$user
        if ($sessionid > 0) {
            $session = $DB->record_exists('local_classroom_sessions', array(
                'id' => $sessionid,
                'classroomid' => $classroomid
            ));
            if (!$session) {
                print_error("Session Not Found!");
            } //!$session
        } //$sessionid > 0
        $sessions = $DB->get_records('local_classroom_sessions', array(
            'classroomid' => $classroomid
        ));
        foreach ($sessions as $session) {
            $checkattendeesignup = $DB->get_record('local_classroom_attendance', array(
                'classroomid' => $classroomid,
                'sessionid' => $session->id,
                'userid' => $userid
            ));
            if (!empty($checkattendeesignup)) {
                continue;
            } //!empty($checkattendeesignup)
            else {
                $attendeesignup              = new stdClass();
                $attendeesignup->classroomid = $classroomid;
                $attendeesignup->sessionid   = $session->id;
                $attendeesignup->userid      = $userid;
                $attendeesignup->status      = 0;
                $attendeesignup->usercreated = $USER->id;
                $attendeesignup->timecreated = time();
                $id                          = $DB->insert_record('local_classroom_attendance', $attendeesignup);
                $params                      = array(
                    'context' => context_system::instance(),
                    'objectid' => $id
                );
                $event                       = \local_classroom\event\classroom_attendance_created_updated::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
            }
        } //$sessions as $session
        return true;
    }
    /**
     * [remove_classroom_signups description]
     * @method remove_classroom_signups
     * @param  [type]                   $classroomid [description]
     * @param  [type]                   $userid      [description]
     * @param  integer                  $sessionid   [description]
     * @return [type]                                [description]
     */
    public function remove_classroom_signups($classroomid, $userid, $sessionid = 0) {
        global $DB, $USER;
        if ($sessionid > 0) {
            $sessions = $DB->get_records('local_classroom_sessions', array(
                'classroomid' => $classroomid,
                'id' => $sessionid
            ));
        } //$sessionid > 0
        else {
            $sessions = $DB->get_records('local_classroom_sessions', array(
                'classroomid' => $classroomid
            ));
        }
        foreach ($sessions as $session) {
            $checkattendeesignup = $DB->get_record('local_classroom_attendance', array(
                'classroomid' => $classroomid,
                'sessionid' => $session->id,
                'userid' => $userid
            ));
            if (!empty($checkattendeesignup)) {
                $DB->delete_records('local_classroom_attendance', array(
                    'classroomid' => $classroomid,
                    'sessionid' => $session->id,
                    'userid' => $userid
                ));
            } //!empty($checkattendeesignup)
        } //$sessions as $session
        return true;
    }
    /**
     * [classroom_get_attendees description]
     * @method classroom_get_attendees
     * @param  [type]                  $sessionid [description]
     * @return [type]                             [description]
     */
    public function classroom_get_attendees($classroomid, $sessionid = 0) {
        global $DB, $OUTPUT;
        $concatsql       = "";
        $selectfileds    = '';
        $whereconditions = '';
        if ($sessionid > 0) {
            $selectfileds = ", ca.id as attendanceid, ca.status";
            $concatsql .= " JOIN {local_classroom_sessions} AS cs ON cs.classroomid = cu.classroomid AND cs.classroomid = $classroomid
            LEFT JOIN {local_classroom_attendance} AS ca ON ca.classroomid = cu.classroomid
              AND ca.sessionid = cs.id AND ca.userid = cu.userid";
            $whereconditions = " AND cs.id = $sessionid";
        } //$sessionid > 0
        $signupssql = "SELECT DISTINCT u.id, u.firstname, u.lastname,
                              u.email, u.picture, u.firstnamephonetic, u.lastnamephonetic,
                              u.middlename, u.alternatename, u.imagealt $selectfileds
                        FROM {user} AS u
                        JOIN {local_classroom_users} AS cu ON
                                (cu.userid = u.id AND cu.classroomid = $classroomid)
                            $concatsql
                       WHERE cu.classroomid = $classroomid $whereconditions";
        $signups    = $DB->get_records_sql($signupssql);
        return $signups;
    }
    /**
     * [classroom_evaluations description]
     * @method classroom_evaluations
     * @param  [type]                $classroomid [description]
     * @return [type]                             [description]
     */
    public function classroom_evaluations($classroomid) {
        global $DB, $USER;
        $params     = array();
        $classrooms = array();
        $concatsql  = '';
        $sql        = "SELECT e.*
                 FROM {local_evaluations} AS e
                WHERE e.visible = 1 AND e.plugin = 'classroom' AND e.instance = $classroomid ";
        $sql .= $concatsql;
        try {
            $sql .= " ORDER BY e.id DESC";
            $evaluations = $DB->get_records_sql($sql, $params);
        }
        catch (dml_exception $ex) {
            $evaluations = array();
        }
        return $evaluations;
    }
    /**
     * [classroom_add_assignusers description]
     * @method classroom_add_assignusers
     * @param  [type]                    $classroomid   [description]
     * @param  [type]                    $userstoassign [description]
     * @return [type]                                   [description]
     */
    public function classroom_add_assignusers($classroomid, $userstoassign) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        } //file_exists($CFG->dirroot . '/local/lib.php')
        $classroomenrol = enrol_get_plugin('classroom');
        //$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $courses        = $DB->get_records_menu('local_classroom_courses', array(
            'classroomid' => $classroomid
        ), 'id', 'id, courseid');
        $allow          = true;
        $type           = 'classroom_enrol';
        $dataobj        = $classroomid;
        $fromuserid     = $USER->id;
        if ($allow) {
            $localclassroom = $DB->get_record_sql("SELECT id,name,status FROM {local_classroom} where id= $classroomid");
            $progress       = 0;
            $progressbar    = new \core\progress\display_if_slow(get_string('enrollusers', 'local_classroom', $localclassroom->name));
            $progressbar->start_html();
            $progressbar->start_progress('', count($userstoassign) - 1);
            foreach ($userstoassign as $key => $adduser) {
                $progressbar->progress($progress);
                $progress++;
                $classroom_capacity_check = $this->classroom_capacity_check($classroomid);
                if (!$classroom_capacity_check) {
                    $classroomuser               = new stdClass();
                    $classroomuser->classroomid  = $classroomid;
                    $classroomuser->courseid     = 0;
                    $classroomuser->userid       = $adduser;
                    $classroomuser->supervisorid = 0;
                    $classroomuser->prefeedback  = 0;
                    $classroomuser->postfeedback = 0;
                    $classroomuser->hours        = 0;
                    $classroomuser->usercreated  = $USER->id;
                    $classroomuser->timecreated  = time();
                    try {
                        $classroomuser->id = $DB->insert_record('local_classroom_users', $classroomuser);
                        $params            = array(
                            'context' => context_system::instance(),
                            'objectid' => $classroomuser->id
                        );
                        $event             = \local_classroom\event\classroom_users_created::create($params);
                        $event->add_record_snapshot('local_classroom', $localclassroom);
                        $event->trigger();
                        if ($localclassroom->status != 0) {
                            $email_logs = emaillogs($type, $dataobj, $classroomuser->userid, $fromuserid);
                            foreach ($courses as $course) {
                                // $instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol'=>'classroom'), '*', MUST_EXIST);
                                if ($classroomuser->id) {
                                    // $classroomenrol->enrol_user($instance, $adduser,$instance->roleid, time());
                                    $enrolclassroomuser = $this->manage_classroom_course_enrolments($course, $adduser, 'employee', 'enrol');
                                    // $DB->execute("UPDATE {local_classroom_users} SET courseid = $course WHERE id = :id ", array('id' => $classroomuser->id));
                                } //$classroomuser->id
                            } //$courses as $course
                        } //$localclassroom->status != 0
                        classroom_evaluations_add_remove_users($classroomid, 0, 'users_to_feedback', $adduser);
                    }
                    catch (dml_exception $ex) {
                        print_error($ex);
                    }
                } //!$classroom_capacity_check
                else {
                    $progress--;
                    break;
                }
            } //$userstoassign as $key => $adduser
            $progressbar->end_html();
            $result              = new stdClass();
            $result->changecount = $progress;
            $result->classroom   = $localclassroom->name;
        } //$allow
        return $result;
    }
    /**
     * [classroom_remove_assignusers description]
     * @method classroom_remove_assignusers
     * @param  [type]                       $classroomid     [description]
     * @param  [type]                       $userstounassign [description]
     * @return [type]                                        [description]
     */
    public function classroom_remove_assignusers($classroomid, $userstounassign) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        } //file_exists($CFG->dirroot . '/local/lib.php')
        $classroomenrol = enrol_get_plugin('classroom');
        //$studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $courses        = $DB->get_records_menu('local_classroom_courses', array(
            'classroomid' => $classroomid
        ), 'id', 'id, courseid');
        $type           = 'classroom_unenroll';
        $dataobj        = $classroomid;
        $fromuserid     = $USER->id;
        try {
            // a large amount of grades.
            $localclassroom = $DB->get_record_sql("SELECT id,name,status FROM {local_classroom} where id= $classroomid");
            $progress       = 0;
            $progressbar    = new \core\progress\display_if_slow(get_string('un_enrollusers', 'local_classroom', $localclassroom->name));
            $progressbar->start_html();
            $progressbar->start_progress('', count($userstounassign) - 1);
            foreach ($userstounassign as $key => $removeuser) {
                $progressbar->progress($progress);
                $progress++;
                if ($localclassroom->status != 0) {
                    if (!empty($courses)) {
                        foreach ($courses as $course) {
                            if ($course > 0) {
                                //$instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol'=>'classroom'), '*', MUST_EXIST);
                                //$classroomenrol->unenrol_user($instance, $removeuser, $instance->roleid, time());
                                $unenrolclassroomuser = $this->manage_classroom_course_enrolments($course, $removeuser, 'employee', 'unenrol');
                            } //$course > 0
                        } //$courses as $course
                    } //!empty($courses)
                } //$localclassroom->status != 0
                classroom_evaluations_add_remove_users($classroomid, 0, 'users_to_feedback', $removeuser, 'update');
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomid
                );
                $event  = \local_classroom\event\classroom_users_deleted::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                $DB->delete_records('local_classroom_users', array(
                    'classroomid' => $classroomid,
                    'userid' => $removeuser
                ));
                if ($localclassroom->status != 0) {
                    $email_logs = emaillogs($type, $dataobj, $removeuser, $fromuserid);
                } //$localclassroom->status != 0
                $DB->delete_records('local_classroom_trainerfb', array(
                    'classroomid' => $classroomid,
                    'userid' => $removeuser
                ));
                $this->remove_classroom_signups($classroomid, $removeuser);
            } //$userstounassign as $key => $removeuser
            $progressbar->end_html();
            $result              = new stdClass();
            $result->changecount = $progress;
            $result->classroom   = $localclassroom->name;
        }
        catch (dml_exception $ex) {
            print_error($ex);
        }
        return $result;
    }
    /**
     * [classroom_manage_evaluations description]
     * @method classroom_manage_evaluations
     * @param  [type]                       $classroomid [description]
     * @param  [type]                       $evaluation  [description]
     * @return [type]                                    [description]
     */
    public function classroom_manage_evaluations($classroomid, $evaluation) {
        global $DB, $USER;
        $plugin_evaluationtypes = plugin_evaluationtypes();
        $params                 = array(
            'classroomid' => $classroomid,
            'evaluationid' => $evaluation->id,
            'timemodified' => time(),
            'usermodified' => $USER->id
        );
        switch ($plugin_evaluationtypes[$evaluation->evaluationtype]) {
            case 'Trainer feedback':
                $return = $DB->execute('UPDATE {local_classroom_trainers} SET feedback_id = :evaluationid, timemodified = :timemodified, usermodified = :usermodified WHERE classroomid = :classroomid AND feedback_id = 0', $params);
                break;
            case 'Training feedback':
                $return = $DB->execute('UPDATE {local_classroom} SET trainingfeedbackid = :evaluationid, timemodified = :timemodified, usermodified = :usermodified WHERE id = :classroomid AND trainingfeedbackid = 0', $params);
                break;
            default:
                $return = false;
                break;
        } //$plugin_evaluationtypes[$evaluation->evaluationtype]
        return $return;
    }
    /**
     * [manage_classroom_trainers description]
     * @method manage_classroom_trainers
     * @param  [type]                    $classroomid [description]
     * @param  [type]                    $action      [description]
     * @param  array                     $trainers    [description]
     * @return [type]                                 [description]
     */
    public function manage_classroom_trainers($classroomid, $action, $trainers = array()) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        } //file_exists($CFG->dirroot . '/local/lib.php')
        $classroom_trainers = $DB->get_records_menu('local_classroom_trainers', array(
            'classroomid' => $classroomid
        ), 'trainerid', 'id, trainerid');
        $enrolclassroom     = enrol_get_plugin('classroom');
        //$teacherroleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $classroomcourses   = $DB->get_records_menu('local_classroom_courses', array(
            'classroomid' => $classroomid
        ), 'id', 'courseid as course, courseid');
        switch ($action) {
            case 'insert':
                if (!empty($trainers)) {
                    $newtrainers = array_diff($trainers, $classroom_trainers);
                } //!empty($trainers)
                else {
                    $newtrainers = $trainers;
                }
                $type       = 'classroom_enrol';
                $dataobj    = $classroomid;
                $fromuserid = $USER->id;
                $string     = 'trainer';
                if (!empty($newtrainers)) {
                    foreach ($newtrainers as $newtrainer) {
                        $trainer              = new stdClass();
                        $trainer->classroomid = $classroomid;
                        $trainer->trainerid   = $newtrainer;
                        $trainer->feedback_id = 0;
                        $trainer->timecreated = time();
                        $trainer->usercreated = $USER->id;
                        $trainer->id          = $DB->insert_record('local_classroom_trainers', $trainer);
                        $classroom_status     = $DB->get_field('local_classroom', 'status', array(
                            'id' => $classroomid
                        ));
                        if ($classroom_status != 0) {
                            $email_logs = emaillogs($type, $dataobj, $trainer->trainerid, $fromuserid, $string);
                            if (!empty($classroomcourses)) {
                                foreach ($classroomcourses as $course) {
                                    //$instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol' => 'classroom'), '*', MUST_EXIST);
                                    //if (!empty($instance)) {
                                    //    $enrolclassroom->enrol_user($instance, $newtrainer,editingteacher, time());
                                    //    
                                    //}
                                    $enrolclassroomuser = $this->manage_classroom_course_enrolments($course, $newtrainer, 'editingteacher', 'enrol');
                                } //$classroomcourses as $course
                            } //!empty($classroomcourses)
                        } //$classroom_status != 0
                    } //$newtrainers as $newtrainer
                } //!empty($newtrainers)
                break;
            case 'update':
                break;
            case 'delete';
                if (!empty($trainers)) {
                    $toremove_trainers = array_diff($classroom_trainers, $trainers);
                } //!empty($trainers)
                else {
                    $toremove_trainers = $classroom_trainers;
                }
                $type       = 'classroom_unenroll';
                $dataobj    = $classroomid;
                $fromuserid = $USER->id;
                $string     = 'trainer';
                if (!empty($toremove_trainers)) {
                    list($remove_trainerscondition, $toremove_trainersparams) = $DB->get_in_or_equal($toremove_trainers);
                    foreach ($toremove_trainers as $toremove_trainer) {
                        $classroom_status = $DB->get_field('local_classroom', 'status', array(
                            'id' => $classroomid
                        ));
                        if (!empty($classroomcourses)) {
                            foreach ($classroomcourses as $course) {
                                //$instance = $DB->get_record('enrol', array('courseid' => $course, 'enrol' => 'classroom'), '*', MUST_EXIST);
                                //$enrolclassroom->unenrol_user($instance, $toremove_trainer, $teacherroleid, time());
                                $enrolclassroomuser = $this->manage_classroom_course_enrolments($course, $toremove_trainer, 'editingteacher', 'unenrol');
                            } //$classroomcourses as $course
                        } //!empty($classroomcourses)
                        $feedbackid              = $DB->get_field('local_classroom_trainers', 'feedback_id', array(
                            'trainerid' => $toremove_trainer,
                            'classroomid' => $classroomid
                        ));
                        // print_object($feedbackid);exit;
                        $core_component          = new core_component();
                        $evaluation_plugin_exist = $core_component::get_plugin_directory('local', 'evaluation');
                        if (!empty($evaluation_plugin_exist) && $feedbackid > 0) {
                            require_once($CFG->dirroot . '/local/evaluation/lib.php');
                            evaluation_delete_instance($feedbackid);
                        } //!empty($evaluation_plugin_exist) && $feedbackid > 0
                        $DB->execute('UPDATE {local_classroom_sessions} SET trainerid =0,timemodified = ' . time() . ',
                            usermodified = ' . $USER->id . ' WHERE classroomid = ' . $classroomid . ' AND trainerid=' . $toremove_trainer . '');
                        if ($classroom_status != 0) {
                            $email_logs = emaillogs($type, $dataobj, $toremove_trainer, $fromuserid, $string);
                        } //$classroom_status != 0
                    } //$toremove_trainers as $toremove_trainer
                    $DB->delete_records_select('local_classroom_trainers', " classroomid = $classroomid AND trainerid $remove_trainerscondition  ", $toremove_trainersparams);
                } //!empty($toremove_trainers)
                break;
            case 'all':
                $this->manage_classroom_trainers($classroomid, 'insert', $trainers);
                $this->manage_classroom_trainers($classroomid, 'update', $trainers);
                $this->manage_classroom_trainers($classroomid, 'delete', $trainers);
                break;
            case 'default':
                break;
        } //$action
        return true;
    }
    /**
     * [classroom_misc description]
     * @method classroom_misc
     * @param  [type]         $classroom [description]
     * @return [type]                    [description]
     */
    public function classroom_misc($classroom) {
        global $DB;
        if ($classroom->id > 0) {
            $systemcontext            = context_system::instance();
            $classroom->description   = $classroom->cr_description['text'];
            $classroom->classroomlogo = $classroom->classroomlogo;
            file_save_draft_area_files($classroom->classroomlogo, $systemcontext->id, 'local_classroom', 'classroomlogo', $classroom->classroomlogo);
            $DB->update_record('local_classroom', $classroom);
            // $params = array(
            //    'context' => context_system::instance(),
            //    'objectid' => $classroom->id
            // );
            //
            //$event = \local_classroom\event\classroom_updated::create($params);
            //$event->add_record_snapshot('local_classroom', $classroom->id);
            //$event->trigger();
        } //$classroom->id > 0
        return $classroom->id;
    }
    // OL-1042 Add Target Audience to Classrooms//
    public function target_audience($classroom) {
        global $DB;
        if ($classroom->id > 0) {
            $classroom->open_group       = (!empty($classroom->open_group)) ? implode(',', array_filter($classroom->open_group)) : NULL;
            $classroom->open_hrmsrole    = (!empty($classroom->open_hrmsrole)) ? implode(',', array_filter($classroom->open_hrmsrole)) : NULL;
            $classroom->open_designation = (!empty($classroom->open_designation)) ? implode(',', array_filter($classroom->open_designation)) : NULL;
            $classroom->open_location    = (!empty($classroom->open_location)) ? implode(',', array_filter($classroom->open_location)) : NULL;
            $classroom->open_grade      =  (!empty($classroom->open_grade)) ? implode(',', array_filter($classroom->open_grade)) : NULL;
            if (is_array($classroom->department)) {
                $classroom->department = !empty($classroom->department) ? implode(',', $classroom->department) : -1;
            } //is_array($classroom->department)
            else {
                $classroom->department = !empty($classroom->department) ? $classroom->department : -1;
            }
            $DB->update_record('local_classroom', $classroom);
            // $params = array(
            //    'context' => context_system::instance(),
            //    'objectid' => $classroom->id
            // );
            //
            //$event = \local_classroom\event\classroom_updated::create($params);
            //$event->add_record_snapshot('local_classroom', $classroom->id);
            //$event->trigger();
        } //$classroom->id > 0
        return $classroom->id;
    }
    // OL-1042 Add Target Audience to Classrooms//
    /**
     * [classroom_logo description]
     * @method classroom_logo
     * @param  integer        $classroomlogo [description]
     * @return [type]                        [description]
     */
    public function classroom_logo($classroomlogo = 0) {
        global $DB;
        $classroomlogourl = false;
        if ($classroomlogo > 0) {
            $sql                 = "SELECT * FROM {files} WHERE itemid = $classroomlogo AND filename != '.' ORDER BY id DESC LIMIT 1";
            $classroomlogorecord = $DB->get_record_sql($sql);
        } //$classroomlogo > 0
        if (!empty($classroomlogorecord)) {
            if ($classroomlogorecord->filearea == "classroomlogo") {
                $classroomlogourl = moodle_url::make_pluginfile_url($classroomlogorecord->contextid, $classroomlogorecord->component, $classroomlogorecord->filearea, $classroomlogorecord->itemid, $classroomlogorecord->filepath, $classroomlogorecord->filename);
            } //$classroomlogorecord->filearea == "classroomlogo"
        } //!empty($classroomlogorecord)
        return $classroomlogourl;
    }
    /**
     * [manage_classroom_courses description]
     * @method manage_classroom_courses
     * @param  [type]                   $courses [description]
     * @return [type]                            [description]
     */
    public function manage_classroom_courses($courses) {
        global $DB, $USER;
        $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
            'classroomid' => $courses->classroomid
        ), 'trainerid', 'id, trainerid');
        $classroomusers    = $DB->get_records_menu('local_classroom_users', array(
            'classroomid' => $courses->classroomid
        ), 'userid', 'id, userid');
        foreach ($courses->course as $course) {
            $classroomcourseexists = $DB->record_exists('local_classroom_courses', array(
                'classroomid' => $courses->classroomid,
                'courseid' => $course
            ));
            if (!empty($classroomcourseexists)) {
                continue;
            } //!empty($classroomcourseexists)
            $classroomcourse              = new stdClass();
            $classroomcourse->classroomid = $courses->classroomid;
            $classroomcourse->courseid    = $course;
            $classroomcourse->timecreated = time();
            $classroomcourse->usercreated = $USER->id;
            $classroomcourse->id          = $DB->insert_record('local_classroom_courses', $classroomcourse);
            $params                       = array(
                'context' => context_system::instance(),
                'objectid' => $classroomcourse->id
            );
            $event                        = \local_classroom\event\classroom_courses_created::create($params);
            $event->add_record_snapshot('local_classroom', $courses->classroomid);
            $event->trigger();
            if ($classroomcourse->id) {
                foreach ($classroomtrainers as $classroomtrainer) {
                    $this->manage_classroom_course_enrolments($course, $classroomtrainer, 'editingteacher', 'enrol');
                } //$classroomtrainers as $classroomtrainer
                foreach ($classroomusers as $classroomuser) {
                    $unenrolclassroomuser = $this->manage_classroom_course_enrolments($course, $classroomuser, 'employee', 'enrol');
                } //$classroomusers as $classroomuser
            } //$classroomcourse->id
        } //$courses->course as $course
        return true;
    }
    /**
     * [manage_classroom_course_enrolments description]
     * @method manage_classroom_course_enrolments
     * @param  [type]                             $cousre        [description]
     * @param  [type]                             $user          [description]
     * @param  string                             $roleshortname [description]
     * @param  string                             $type          [description]
     * @param  string                             $pluginname    [description]
     * @return [type]                                            [description]
     */
    public function manage_classroom_course_enrolments($cousre, $user, $roleshortname = 'employee', $type = 'enrol', $pluginname = 'classroom') {
        global $DB;
        $enrolmethod = enrol_get_plugin($pluginname);
        $roleid      = $DB->get_field('role', 'id', array(
            'shortname' => $roleshortname
        ));
        $instance    = $DB->get_record('enrol', array(
            'courseid' => $cousre,
            'enrol' => $pluginname
        ), '*', MUST_EXIST);
        if (!empty($instance)) {
            if ($type == 'enrol') {
                $enrolmethod->enrol_user($instance, $user, $roleid, time());
            } //$type == 'enrol'
            else if ($type == 'unenrol') {
                $enrolmethod->unenrol_user($instance, $user, $roleid, time());
            } //$type == 'unenrol'
        } //!empty($instance)
        return true;
    }
    /**
     * [classroom_courses description]
     * @method classroom_courses
     * @param  [type]            $classroomid [description]
     * @return [type]                         [description]
     */
    public function classroom_courses($classroomid, $stable) {
        global $DB, $USER;
        $params           = array();
        $classroomcourses = array();
        $concatsql        = '';
        if (!empty($stable->search)) {
            $fields = array(
                0 => 'c.fullname'
            );
            $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $stable->search . "%' ";
            $concatsql .= " AND ($fields) ";
        } //!empty($stable->search)
        $countsql              = "SELECT COUNT(cc.id) ";
        $fromsql               = "SELECT c.*, cc.id as classroomcourseinstance ";
        $sql                   = " FROM {course} AS c
                                     JOIN {local_classroom_courses} AS cc ON cc.courseid = c.id
                                  WHERE cc.classroomid = :classroomid ";
        $params['classroomid'] = $classroomid;
        $sql .= $concatsql;
        try {
            $classroomcoursescount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY cc.id ASC";
                $classroomcourses = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            } //$stable->thead == false
        }
        catch (dml_exception $ex) {
            $classroomcoursescount = 0;
        }
        return compact('classroomcourses', 'classroomcoursescount');
    }
    /**
     * [classroom_status_action description]
     * @method classroom_status_action
     * @param  [type]                  $classroomid     [description]
     * @param  [type]                  $classroomstatus [description]
     * @return [type]                                   [description]
     */
    public function classroom_status_action($classroomid, $classroomstatus) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        } //file_exists($CFG->dirroot . '/local/lib.php')
        switch ($classroomstatus) {
            case CLASSROOM_NEW:
                $this->update_classroom_status($classroomid, CLASSROOM_ACTIVE);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomid
                );
                $event  = \local_classroom\event\classroom_publish::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                break;
            case CLASSROOM_ACTIVE:
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomid
                );
                $event  = \local_classroom\event\classroom_publish::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                $this->update_classroom_status($classroomid, CLASSROOM_ACTIVE);
                // Trigger classroom created event.
                $classroom = $DB->get_record('local_classroom', array(
                    'id' => $classroomid
                ));
                $this->classroom_set_events($classroom); // added by sreenivas
                $classroomusers   = $DB->get_records_menu('local_classroom_users', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, userid');
                $classroomcourses = $DB->get_records_menu('local_classroom_courses', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, courseid');
                $type             = 'classroom_enrol';
                $dataobj          = $classroomid;
                $fromuserid       = $USER->id;
                if (!empty($classroomcourses)) {
                    $i = 0;
                    foreach ($classroomcourses as $classroomcourse) {
                        foreach ($classroomusers as $classroomuser) {
                            $this->manage_classroom_course_enrolments($classroomcourse, $classroomuser, 'employee', 'enrol');
                            if ($i == 0) {
                                $email_logs = emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                            } //$i == 0
                        } //$classroomusers as $classroomuser
                        $i++;
                    } //$classroomcourses as $classroomcourse
                } //!empty($classroomcourses)
                elseif (empty($classroomcourses)) {
                    foreach ($classroomusers as $classroomuser) {
                        $email_logs = emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                    } //$classroomusers as $classroomuser
                } //empty($classroomcourses)
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    $email_logs = emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                } //$classroomtrainers as $classroomtrainer
                break;
            case CLASSROOM_CANCEL:
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomid
                );
                $event  = \local_classroom\event\classroom_cancel::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                $this->update_classroom_status($classroomid, CLASSROOM_CANCEL);
                $classroomusers   = $DB->get_records_menu('local_classroom_users', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, userid');
                $classroomcourses = $DB->get_records_menu('local_classroom_courses', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, courseid');
                $type             = 'classroom_cancel';
                $dataobj          = $classroomid;
                $fromuserid       = $USER->id;
                $localclassroom   = $DB->get_record_sql("SELECT id,status FROM {local_classroom} where id= $classroomid");
                if (!empty($classroomcourses)) {
                    $i = 0;
                    foreach ($classroomcourses as $classroomcourse) {
                        foreach ($classroomusers as $classroomuser) {
                            //$this->manage_classroom_course_enrolments($classroomcourse, $classroomuser,'employee','unenrol');
                            if ($i == 0 && $localclassroom->status != 0) {
                                $email_logs = emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                            } //$i == 0 && $localclassroom->status != 0
                        } //$classroomusers as $classroomuser
                        $i++;
                    } //$classroomcourses as $classroomcourse
                } //!empty($classroomcourses)
                elseif (empty($classroomcourses)) {
                    foreach ($classroomusers as $classroomuser) {
                        if ($localclassroom->status != 0) {
                            $email_logs = emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                        } //$localclassroom->status != 0
                    } //$classroomusers as $classroomuser
                } //empty($classroomcourses)
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    if ($localclassroom->status != 0) {
                        $email_logs = emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                    } //$localclassroom->status != 0
                } //$classroomtrainers as $classroomtrainer
                break;
            case CLASSROOM_HOLD:
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomid
                );
                $event  = \local_classroom\event\classroom_hold::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                $this->update_classroom_status($classroomid, CLASSROOM_HOLD);
                $classroomusers   = $DB->get_records_menu('local_classroom_users', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, userid');
                $classroomcourses = $DB->get_records_menu('local_classroom_courses', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, courseid');
                $type             = 'classroom_hold';
                $dataobj          = $classroomid;
                $fromuserid       = $USER->id;
                $localclassroom   = $DB->get_record_sql("SELECT id,status FROM {local_classroom} where id= $classroomid");
                if (!empty($classroomcourses)) {
                    $i = 0;
                    foreach ($classroomcourses as $classroomcourse) {
                        foreach ($classroomusers as $classroomuser) {
                            $this->manage_classroom_course_enrolments($classroomcourse, $classroomuser, 'employee', 'unenrol');
                            if ($i == 0 && $localclassroom->status != 0) {
                                $email_logs = emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                            } //$i == 0 && $localclassroom->status != 0
                        } //$classroomusers as $classroomuser
                        $i++;
                    } //$classroomcourses as $classroomcourse
                } //!empty($classroomcourses)
                elseif (empty($classroomcourses)) {
                    foreach ($classroomusers as $classroomuser) {
                        if ($localclassroom->status != 0) {
                            $email_logs = emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                        } //$localclassroom->status != 0
                    } //$classroomusers as $classroomuser
                } //empty($classroomcourses)
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    if ($localclassroom->status != 0) {
                        $email_logs = emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                    } //$localclassroom->status != 0
                } //$classroomtrainers as $classroomtrainer
                break;
            case CLASSROOM_COMPLETED:
                $this->classroom_completions($classroomid);
                $this->update_classroom_status($classroomid, CLASSROOM_COMPLETED);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomid
                );
                $event  = \local_classroom\event\classroom_completed::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                $classroomusers = $DB->get_records_menu('local_classroom_users', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, userid');
                // $classroomcourses = $DB->get_records_menu('local_classroom_courses', array('classroomid' => $classroomid), 'id', 'id, courseid');
                $type           = 'classroom_complete';
                $dataobj        = $classroomid;
                $fromuserid     = $USER->id;
                $localclassroom = $DB->get_record_sql("SELECT id,status FROM {local_classroom} where id= $classroomid");
                // foreach($classroomcourses as $classroomcourse) {
                foreach ($classroomusers as $classroomuser) {
                    // $this->manage_classroom_course_enrolments($classroomcourse, $classroomuser);
                    if ($localclassroom->status != 0) {
                        $email_logs = emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                        // echo $email_logs;
                    } //$localclassroom->status != 0
                    // echo $email_logs;
                } //$classroomusers as $classroomuser
                // }
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    if ($localclassroom->status != 0) {
                        $email_logs = emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                    } //$localclassroom->status != 0
                } //$classroomtrainers as $classroomtrainer
                break;
        } //$classroomstatus
        return true;
    }
    /**
     * [update_classroom_status description]
     * @method update_classroom_status
     * @param  [type]                  $classroomid     [description]
     * @param  [type]                  $classroomstatus [description]
     * @return [type]                                   [description]
     */
    public function update_classroom_status($classroomid, $classroomstatus) {
        global $DB, $USER;
        $classroom         = new stdClass();
        $classroom->id     = $classroomid;
        $classroom->status = $classroomstatus;
        if ($classroomstatus == CLASSROOM_COMPLETED) {
            $activeusers               = $DB->count_records('local_classroom_users', array(
                'classroomid' => $classroomid,
                'completion_status' => 1
            ));
            $classroom->activeusers    = $activeusers;
            $totalusers                = $DB->count_records('local_classroom_users', array(
                'classroomid' => $classroomid
            ));
            $classroom->totalusers     = $totalusers;
            $activesessions            = $DB->count_records('local_classroom_sessions', array(
                'classroomid' => $classroomid,
                'attendance_status' => 1
            ));
            $classroom->activesessions = $activesessions;
            $totalsessions             = $DB->count_records('local_classroom_sessions', array(
                'classroomid' => $classroomid
            ));
            $classroom->totalsessions  = $totalsessions;
        } //$classroomstatus == CLASSROOM_COMPLETED
        $classroom->usermodified   = $USER->id;
        $classroom->timemodified   = time();
        $classroom->completiondate = time();
        try {
            $DB->update_record('local_classroom', $classroom);
            //  $params = array(
            //     'context' => context_system::instance(),
            //     'objectid' => $classroom->id
            //  );
            // $event = \local_classroom\event\classroom_updated::create($params);
            // $event->add_record_snapshot('local_classroom', $classroom->id);
            // $event->trigger();
        }
        catch (dml_exception $ex) {
            print_error($ex);
        }
        return true;
    }
    /**
     * [classroomusers description]
     * @method classroomusers
     * @param  [type]         $classroomid [description]
     * @param  [type]         $stable      [description]
     * @return [type]                      [description]
     */
    public function classroomusers($classroomid, $stable) {
        global $DB, $USER;
        $params         = array();
        $classroomusers = array();
        $concatsql      = '';
        if (!empty($stable->search)) {
            $fields = array(
                0 => 'u.firstname',
                1 => 'u.lastname',
                2 => 'u.email',
                3 => 'u.idnumber'
            );
            $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $stable->search . "%' ";
            $concatsql .= " AND ($fields) ";
        } //!empty($stable->search)
        $countsql = "SELECT COUNT(cu.id) ";
        $fromsql  = "SELECT u.*, cu.attended_sessions, cu.hours, cu.completion_status, c.totalsessions, c.activesessions";
        $sql      = " FROM {user} AS u
                 JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                 JOIN {local_classroom} AS c ON c.id = cu.classroomid
                WHERE c.id = $classroomid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        try {
            $classroomuserscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY id ASC";
                $classroomusers = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            } //$stable->thead == false
        }
        catch (dml_exception $ex) {
            $classroomuserscount = 0;
        }
        return compact('classroomusers', 'classroomuserscount');
    }
    /**
     * [classroom_completions description]
     * @method classroom_completions
     * @param  [type]                $classroomid [description]
     * @return [type]                             [description]
     */
    public function classroom_completions($classroomid) {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');
        $classroomuserssql        = "SELECT cu.*
                                FROM {user} AS u
                                JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                                WHERE u.id > 2 AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND cu.classroomid = $classroomid";
        $classroomusers           = $DB->get_records_sql($classroomuserssql);
        $classroom_completiondata = $DB->get_record('local_classroom_completion', array(
            'classroomid' => $classroomid
        ));
        $totalsessionssql         = "SELECT count(id) as total
                                        FROM {local_classroom_sessions}
                                        WHERE classroomid = $classroomid ";
        if (!empty($classroom_completiondata) && $classroom_completiondata->sessiontracking == "OR" && $classroom_completiondata->sessionids != null) {
            $totalsessionssql .= " AND id in ($classroom_completiondata->sessionids)";
        } //!empty($classroom_completiondata) && $classroom_completiondata->sessiontracking == "OR" && $classroom_completiondata->sessionids != null
        $totalsessions       = $DB->count_records_sql($totalsessionssql);
        $classroomcoursessql = "SELECT c.*
                                  FROM {course} AS c
                                 
                                  JOIN {local_classroom_courses} AS cc ON cc.courseid = c.id
                                 WHERE cc.classroomid = $classroomid";
        if (!empty($classroom_completiondata) && $classroom_completiondata->coursetracking == "OR" && $classroom_completiondata->courseids != null) {
            $classroomcoursessql .= " AND cc.courseid in ($classroom_completiondata->courseids)";
        } //!empty($classroom_completiondata) && $classroom_completiondata->coursetracking == "OR" && $classroom_completiondata->courseids != null
        $classroomcourses = $DB->get_records_sql($classroomcoursessql);
        if (!empty($classroomusers)) {
            foreach ($classroomusers as $classroomuser) {
                $usercousrecompletionstatus = array();
                foreach ($classroomcourses as $classroomcourse) {
                    $params                 = array(
                        'userid' => $classroomuser->userid,
                        'course' => $classroomcourse->id
                    );
                    $ccompletion            = new completion_completion($params);
                    $ccompletionis_complete = $ccompletion->is_complete();
                    if ($ccompletionis_complete) {
                        $usercousrecompletionstatus[] = true;
                    } //$ccompletionis_complete
                } //$classroomcourses as $classroomcourse
                // print_object($usercousrecompletionstatus);
                if (empty($classroom_completiondata) || ($classroom_completiondata->sessiontracking == null && $classroom_completiondata->coursetracking == null)) {
                    if (($classroomuser->attended_sessions == $totalsessions) && (count($usercousrecompletionstatus) == count($classroomcourses))) {
                        $classroomuser->completion_status = 1;
                    } //($classroomuser->attended_sessions == $totalsessions) && (count($usercousrecompletionstatus) == count($classroomcourses))
                    else {
                        $classroomuser->completion_status = 0;
                    }
                } //empty($classroom_completiondata) || ($classroom_completiondata->sessiontracking == null && $classroom_completiondata->coursetracking == null)
                else {
                    $classroomuser->completion_status = 0;
                    $attended_sessions_sql            = "SELECT count(id) as total FROM {local_classroom_attendance} where classroomid=$classroomid and userid=$classroomuser->userid and status=1 ";
                    if (!empty($classroom_completiondata) && $classroom_completiondata->sessiontracking == "OR" && $classroom_completiondata->sessionids != null) {
                        $attended_sessions_sql .= " AND sessionid in ($classroom_completiondata->sessionids)";
                    } //!empty($classroom_completiondata) && $classroom_completiondata->sessiontracking == "OR" && $classroom_completiondata->sessionids != null
                    $attended_sessions = $DB->count_records_sql($attended_sessions_sql);
                    if (($attended_sessions == $totalsessions && $classroom_completiondata->sessiontracking == "AND")) {
                        $classroomuser->completion_status = 1;
                    } //($attended_sessions == $totalsessions && $classroom_completiondata->sessiontracking == "AND")
                    if (($attended_sessions <= $totalsessions && $attended_sessions != 0 && $classroom_completiondata->sessiontracking == "OR")) {
                        $classroomuser->completion_status = 1;
                    } //($attended_sessions <= $totalsessions && $attended_sessions != 0 && $classroom_completiondata->sessiontracking == "OR")
                    if (count($usercousrecompletionstatus) == count($classroomcourses) && $classroom_completiondata->coursetracking == "AND") {
                        if (($attended_sessions == $totalsessions && $classroom_completiondata->sessiontracking == "AND")) {
                            $classroomuser->completion_status = 1;
                        } //($attended_sessions == $totalsessions && $classroom_completiondata->sessiontracking == "AND")
                        if (($attended_sessions <= $totalsessions && $attended_sessions != 0 && $classroom_completiondata->sessiontracking == "OR")) {
                            $classroomuser->completion_status = 1;
                        } //($attended_sessions <= $totalsessions && $attended_sessions != 0 && $classroom_completiondata->sessiontracking == "OR")
                        if ($classroom_completiondata->sessiontracking == null) {
                            $classroomuser->completion_status = 1;
                        } //$classroom_completiondata->sessiontracking == null
                    } //count($usercousrecompletionstatus) == count($classroomcourses) && $classroom_completiondata->coursetracking == "AND"
                    elseif ($classroom_completiondata->coursetracking == "AND") {
                        $classroomuser->completion_status = 0;
                    } //$classroom_completiondata->coursetracking == "AND"
                    if (count($usercousrecompletionstatus) <= count($classroomcourses) && count($usercousrecompletionstatus) != 0 && $classroom_completiondata->coursetracking == "OR") {
                        if (($attended_sessions == $totalsessions && $classroom_completiondata->sessiontracking == "AND")) {
                            $classroomuser->completion_status = 1;
                        } //($attended_sessions == $totalsessions && $classroom_completiondata->sessiontracking == "AND")
                        if (($attended_sessions <= $totalsessions && $attended_sessions != 0 && $classroom_completiondata->sessiontracking == "OR")) {
                            $classroomuser->completion_status = 1;
                        } //($attended_sessions <= $totalsessions && $attended_sessions != 0 && $classroom_completiondata->sessiontracking == "OR")
                        if ($classroom_completiondata->sessiontracking == null) {
                            $classroomuser->completion_status = 1;
                        } //$classroom_completiondata->sessiontracking == null
                    } //count($usercousrecompletionstatus) <= count($classroomcourses) && count($usercousrecompletionstatus) != 0 && $classroom_completiondata->coursetracking == "OR"
                    elseif ($classroom_completiondata->coursetracking == "OR") {
                        $classroomuser->completion_status = 0;
                    } //$classroom_completiondata->coursetracking == "OR"
                    //print_object($classroomuser);
                }
                $classroomuser->usermodified   = $USER->id;
                $classroomuser->timemodified   = time();
                $classroomuser->completiondate = time();
                $DB->update_record('local_classroom_users', $classroomuser);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomuser->id
                );
                $event  = \local_classroom\event\classroom_users_updated::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
            } //$classroomusers as $classroomuser
        } //!empty($classroomusers)
        //exit;
        return true;
    }
    public function classroomcategories($formdata) {
        global $DB;
        if ($formdata->id) {
            $DB->update_record('local_classroom_categories', $formdata);
        } //$formdata->id
        else {
            $DB->insert_record('local_classroom_categories', $formdata);
        }
    }
    //******************==================Raju Tummoji=====================================****************//
    /**
     * [select_to_and_from_users description]
     * @param  [type]  $type       [description]
     * @param  integer $clasroomid [description]
     * @param  [type]  $params     [description]
     * @param  integer $total      [description]
     * @param  integer $offset1    [description]
     * @param  integer $perpage    [description]
     * @param  integer $lastitem   [description]
     * @return [type]              [description]
     */
    public function select_to_and_from_users($type = null, $clasroomid = 0, $params, $total = 0, $offset1 = -1, $perpage = -1, $lastitem = 0) {
        global $DB, $USER;
        $classroom           = $DB->get_record('local_classroom', array(
            'id' => $clasroomid
        ));
        $params['suspended'] = 0;
        $params['deleted']   = 0;
        if ($total == 0) {
            $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname";
        } //$total == 0
        else {
            $sql = "SELECT count(u.id) as total";
        }
        $sql .= " FROM {user} AS u
                                WHERE  u.id > 2 AND u.suspended = :suspended
                                     AND u.deleted = :deleted ";
        // OL-1042 Add Target Audience to Classrooms//                         
        //if(!empty($classroom->open_group)){
        //    $group_list = $DB->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$classroom->open_group})");
        //     
        //     $groups_members = implode(',', $group_list);
        //     if (!empty($groups_members))
        //     $sql .=" AND u.id IN ({$groups_members})";
        //     else
        //     $sql .=" AND u.id =0";
        //     
        //}                         
        //if(!empty($classroom->open_hrmsrole)){
        //    $sql .= " AND u.open_hrmsrole IN (:roleinfo)";
        //    $params['roleinfo'] = $classroom->open_hrmsrole;
        //}
        //if(!empty($classroom->open_designation)){
        //    $sql .= " AND u.open_designation IN (:designationinfo)";
        //    $params['designationinfo'] = $classroom->open_designation;
        //}
        //if(!empty($classroom->open_location)){
        //    $sql .= " AND u.city IN (:locationinfo)";
        //    $params['locationinfo'] = $classroom->open_location;
        //}
        // OL-1042 Add Target Audience to Classrooms//
        if ($lastitem != 0) {
            $sql .= " AND u.id > $lastitem";
        } //$lastitem != 0
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $sql .= " AND u.open_costcenterid = :costcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $sql .= " AND u.open_departmentid = :department";
                $params['department'] = $USER->open_departmentid;
            } //(has_capability('local/classroom:manage_owndepartments', context_system::instance()) || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))
        } //(has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))
        $sql .= " AND u.id <> $USER->id ";
        if (!empty($params['email'])) {
            $sql .= " AND u.id IN ({$params['email']})";
        } //!empty($params['email'])
        if (!empty($params['uname'])) {
            $sql .= " AND u.id IN ({$params['uname']})";
        } //!empty($params['uname'])
        if (!empty($params['department'])) {
            $sql .= " AND u.open_departmentid IN ({$params['department']})";
        } //!empty($params['department'])
        if (!empty($params['organization'])) {
            $sql .= " AND u.open_costcenterid IN ({$params['organization']})";
        } //!empty($params['organization'])
        if (!empty($params['idnumber'])) {
            $sql .= " AND u.id IN ({$params['idnumber']})";
        } //!empty($params['idnumber'])
        if (!empty($params['groups'])) {
            $sql .= " AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
        } //!empty($params['groups'])
        if ($type == 'add') {
            $sql .= " AND u.id NOT IN (SELECT lcu.userid as userid
                                       FROM {local_classroom_users} AS lcu
                                       WHERE lcu.classroomid = $clasroomid)";
        } //$type == 'add'
        elseif ($type == 'remove') {
            $sql .= " AND u.id IN (SELECT lcu.userid as userid
                                       FROM {local_classroom_users} AS lcu
                                       WHERE lcu.classroomid = $clasroomid)";
        } //$type == 'remove'
        $sql .= " AND u.id NOT IN (SELECT lcu.trainerid as userid
                                       FROM {local_classroom_trainers} AS lcu
                                       WHERE lcu.classroomid = $clasroomid)";
        $order = ' ORDER BY u.id ASC ';
        if ($perpage != -1) {
            $order .= "LIMIT $perpage";
        } //$perpage != -1
        if ($total == 0) {
            $availableusers = $DB->get_records_sql_menu($sql . $order, $params);
        } //$total == 0
        else {
            $availableusers = $DB->count_records_sql($sql, $params);
        }
        return $availableusers;
    }
    /**
     * [classroom_self_enrolment description]
     * @param  [type] $classroomid   [description]
     * @param  [type] $classroomuser [description]
     * @return [type]                [description]
     */
    public function classroom_self_enrolment($classroomid, $classroomuser, $request=false) {
        global $DB;
        $classroom_capacity_check = $this->classroom_capacity_check($classroomid);
        if (!$classroom_capacity_check) {
            $this->classroom_add_assignusers($classroomid, array(
                $classroomuser
            ), $request);
            // $classroomcourses = $DB->get_records_menu('local_classroom_courses', array('classroomid' => $classroomid), 'id', 'id, courseid');
            // foreach($classroomcourses as $classroomcourse) {
            //         $this->manage_classroom_course_enrolments($classroomcourse, $classroomuser);
            // }
        } //!$classroom_capacity_check
    }
    /**
     * [classroom_capacity_check description]
     * @param  [type] $classroomid [description]
     * @return [type]              [description]
     */
    public function classroom_capacity_check($classroomid) {
        global $DB;
        $return             = false;
        $classroom_capacity = $DB->get_field('local_classroom', 'capacity', array(
            'id' => $classroomid
        ));
        $enrolled_users     = $DB->count_records('local_classroom_users', array(
            'classroomid' => $classroomid
        ));
        //if($classroom_capacity <= $enrolled_users){
        //    $return =true;
        //}
        if ($classroom_capacity <= $enrolled_users && !empty($classroom_capacity) && $classroom_capacity != 0) {
            $return = true;
        } //$classroom_capacity <= $enrolled_users && !empty($classroom_capacity) && $classroom_capacity != 0
        return $return;
    }
    /**
     * [manage_classroom_automatic_sessions description]
     * @param  [type] $classroomid        [description]
     * @param  [type] $classroomstartdate [description]
     * @param  [type] $classroomenddate   [description]
     * @return [type]                     [description]
     */
    public function manage_classroom_automatic_sessions($classroomid, $classroomstartdate, $classroomenddate) {
        global $DB;
        $i                     = 1;
        $start_hours_minuates  = "09:00:00";
        $finish_hours_minuates = "18:00:00";
        $first_time            = date("H:i:s", $classroomstartdate);
        if ($first_time >= $finish_hours_minuates) {
            $classroomstartdate = strtotime('+1 day', strtotime(date("Y-m-d", $classroomstartdate)));
            $classroomstartdate = strtotime(date('Y-m-d', $classroomstartdate) . ' ' . $start_hours_minuates);
        } //$first_time >= $finish_hours_minuates
        $last_time = date("H:i:s", $classroomenddate);
        if ($last_time < $start_hours_minuates) {
            $classroomenddate = strtotime('-1 day', strtotime(date("Y-m-d", $classroomenddate)));
            $classroomenddate = strtotime(date('Y-m-d', $classroomenddate) . ' ' . $finish_hours_minuates);
        } //$last_time < $start_hours_minuates
        $first = strtotime(date("Y-m-d", $classroomstartdate));
        $last  = strtotime(date("Y-m-d", $classroomenddate));
        while ($first <= $last) {
            $session                 = new stdClass();
            $session->id             = 0;
            $session->datetimeknown  = 1;
            $session->classroomid    = $classroomid;
            $session->mincapacity    = 0;
            $session->onlinesession  = 0;
            $session->roomid         = 0;
            $session->trainerid      = $DB->get_field('local_classroom_trainers', 'trainerid', array(
                'classroomid' => $classroomid
            ));
            $session->cs_description = array(
                'text' => "",
                'format' => 1
            );
            $date                    = date('Y-m-d', $first);
            $session->name           = "Session$i";
            $session->timestart      = strtotime($date . ' ' . $start_hours_minuates);
            $session->timefinish     = strtotime($date . ' ' . $finish_hours_minuates);
            if ($first == $last) {
                $session->timefinish = strtotime($date . ' ' . date("H:i:s", $classroomenddate));
            } //$first == $last
            $condition = strtotime('+1 day', $first);
            if ($i == 1) {
                $session->timestart = strtotime($date . ' ' . date("H:i:s", $classroomstartdate));
            } //$i == 1
            elseif ($condition > $last) {
                $session->timefinish = strtotime($date . ' ' . date("H:i:s", $classroomenddate));
            } //$condition > $last
            $this->manage_classroom_sessions($session);
            $first = strtotime('+1 day', $first);
            $i++;
        } //$first <= $last
    }
    /**
     * [function to get user enrolled classrooms count]
     * @param  [INT] $userid [id of the user]
     * @return [INT]         [count of the classrooms enrolled]
     */
    public function enrol_get_users_classrooms_count($userid) {
        global $DB;
        $classroom_sql   = "SELECT count(id) FROM {local_classroom_users} WHERE userid = :userid";
        $classroom_count = $DB->count_records_sql($classroom_sql, array(
            'userid' => $userid
        ));
        return $classroom_count;
    }
    /**
     * [function to get user enrolled classrooms ]
     * @param  [int] $userid [id of the user]
     * @return [object]         [classrooms object]
     */
    public function enrol_get_users_classrooms($userid) {
        global $DB;
        $classroom_sql = "SELECT lc.id,lc.name,lc.description FROM {local_classroom} AS lc 
        JOIN {local_classroom_users} AS lcu ON lcu.classroomid = lc.id WHERE userid = :userid AND lc.status IN (1,4)";
        $classrooms    = $DB->get_records_sql($classroom_sql, array(
            'userid' => $userid
        ));
        return $classrooms;
    }
    public function classroom_status_strip($classroomid, $classroomstatus) {
        global $DB, $USER;
        $return = "";
        $id     = $DB->get_field('local_classroom_users', 'id', array(
            'classroomid' => $classroomid,
            'userid' => $USER->id
        ));
        if (!$id && !is_siteadmin() && (!has_capability('local/classroom:manageclassroom', context_system::instance()))) {
            return $return;
        } //!$id && !is_siteadmin() && (!has_capability('local/classroom:manageclassroom', context_system::instance()))
        switch ($classroomstatus) {
            case CLASSROOM_NEW:
                $return = get_string('new_classroom', 'local_classroom');
                break;
            case CLASSROOM_ACTIVE:
                $return = get_string('active_classroom', 'local_classroom');
                break;
            case CLASSROOM_CANCEL:
                $return = get_string('cancel_classroom', 'local_classroom');
                break;
            case CLASSROOM_HOLD:
                $return = get_string('hold_classroom', 'local_classroom');
                break;
            case CLASSROOM_COMPLETED:
                $return = get_string('completed_classroom', 'local_classroom');
                if (!is_siteadmin() && (!has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                    $completion_status = $DB->get_field('local_classroom_users', 'completion_status', array(
                        'classroomid' => $classroomid,
                        'userid' => $USER->id
                    ));
                    // $status_string=$completion_status == 1 ? 'completed' : 'Not completed';
                    $return            = $completion_status == 1 ? get_string('completed_classroom', 'local_classroom') : get_string('completed_user_classroom', 'local_classroom');
                } //!is_siteadmin() && (!has_capability('local/classroom:manageclassroom', context_system::instance()))
                break;
        } //$classroomstatus
        return $return;
    }
    public function classroom_completion_settings_tab($classroomid) {
        global $DB, $USER;
        $classroom_completiondata = $DB->get_record('local_classroom_completion', array(
            'classroomid' => $classroomid
        ));
        $sessionssql              = "SELECT id,name FROM {local_classroom_sessions}
                                            WHERE classroomid = $classroomid ";
        if (!empty($classroom_completiondata) && $classroom_completiondata->sessiontracking == "OR" && $classroom_completiondata->sessionids != null) {
            $sessionssql .= " AND id in ($classroom_completiondata->sessionids)";
        } //!empty($classroom_completiondata) && $classroom_completiondata->sessiontracking == "OR" && $classroom_completiondata->sessionids != null
        $sessions            = $DB->get_records_sql_menu($sessionssql);
        $classroomcoursessql = "SELECT c.id,fullname
                                  FROM {course} AS c
                                 
                                  JOIN {local_classroom_courses} AS cc ON cc.courseid = c.id
                                 WHERE cc.classroomid = $classroomid";
        if (!empty($classroom_completiondata) && $classroom_completiondata->coursetracking == "OR" && $classroom_completiondata->courseids != null) {
            $classroomcoursessql .= " AND cc.courseid in ($classroom_completiondata->courseids)";
        } //!empty($classroom_completiondata) && $classroom_completiondata->coursetracking == "OR" && $classroom_completiondata->courseids != null
        $classroomcourses = $DB->get_records_sql_menu($classroomcoursessql);
        $return           = "";
        if (!empty($sessions) || !empty($classroomcourses)) {
            $table       = new html_table();
            $table->head = array(
                get_string('courses', 'local_classroom'),
                get_string('sessions', 'local_classroom')
            );
            if (!empty($classroomcourses)) {
                $courses = implode(', ', $classroomcourses);
            } //!empty($classroomcourses)
            else {
                $courses = get_string('noclassroomcourses', 'local_classroom');
            }
            if (!empty($sessions)) {
                $session = implode(', ', $sessions);
            } //!empty($sessions)
            else {
                $session = get_string('nosessions', 'local_classroom');
            }
            $table->data                           = array(
                array(
                    $courses,
                    $session
                )
            );
            $table->id                             = 'viewclassroomcompletion_settings_tab';
            $table->attributes['data-classroomid'] = $classroomid;
            $table->align                          = array(
                'center',
                'center'
            );
            $return                                = html_writer::table($table);
        } //!empty($sessions) || !empty($classroomcourses)
        if (empty($classroom_completiondata) || ($classroom_completiondata->sessiontracking == null && $classroom_completiondata->coursetracking == null)) {
            $sessiontracking = $coursetracking = "";
            if (!empty($sessions)) {
                $sessiontracking = "_allsessions";
            } //!empty($sessions)
            if (!empty($classroomcourses)) {
                $coursetracking = "_allcourses";
            } //!empty($classroomcourses)
            $completion_tab = get_string('classroom_completion_tab_info' . $sessiontracking . $coursetracking . '', 'local_classroom');
        } //empty($classroom_completiondata) || ($classroom_completiondata->sessiontracking == null && $classroom_completiondata->coursetracking == null)
        else {
            $sessiontracking = $coursetracking = "";
            if ($classroom_completiondata->sessiontracking == "AND" && !empty($sessions)) {
                $sessiontracking = "_allsessions";
            } //$classroom_completiondata->sessiontracking == "AND" && !empty($sessions)
            if ($classroom_completiondata->sessiontracking == "OR" && !empty($sessions)) {
                $sessiontracking = "_anysessions";
            } //$classroom_completiondata->sessiontracking == "OR" && !empty($sessions)
            if ($classroom_completiondata->coursetracking == "AND" && !empty($classroomcourses)) {
                $coursetracking = "_allcourses";
            } //$classroom_completiondata->coursetracking == "AND" && !empty($classroomcourses)
            if ($classroom_completiondata->coursetracking == "OR" && !empty($classroomcourses)) {
                $coursetracking = "_anycourses";
            } //$classroom_completiondata->coursetracking == "OR" && !empty($classroomcourses)
            $completion_tab = get_string('classroom_completion_tab_info' . $sessiontracking . $coursetracking . '', 'local_classroom');
        }
        return "<div class='alert alert-info'>" . $completion_tab . "</div>" . $return;
    }
    public function classroomtarget_audience_tab($classroomid) {
        global $DB, $USER;
        $data = $DB->get_record_sql('SELECT id, open_group, open_hrmsrole,
             open_designation, open_location, open_grade, department
             FROM {local_classroom} WHERE id = ' . $classroomid);
        if ($data->department == -1 || $data->department == null) {
            $department = get_string('audience_department', 'local_classroom', 'All');
        } //$data->department == -1 || $data->department == null
        else {
            $departments = $DB->get_field_sql("SELECT GROUP_CONCAT(fullname)  FROM {local_costcenter} WHERE id IN ($data->department)");
            $department  = get_string('audience_department', 'local_classroom', $departments);
        }
        if (empty($data->open_group)) {
            $group = get_string('audience_group', 'local_classroom', 'All');
        } //empty($data->open_group)
        else {
            $groups = $DB->get_field_sql("SELECT GROUP_CONCAT(name) FROM {cohort} WHERE id IN ($data->open_group)");
            $group  = get_string('audience_group', 'local_classroom', $groups);
        }
        $data->open_hrmsrole    = (!empty($data->open_hrmsrole)) ? $hrmsrole = get_string('audience_hrmsrole', 'local_classroom', $data->open_hrmsrole) : $hrmsrole = get_string('audience_hrmsrole', 'local_classroom', 'All');
        $data->open_designation = (!empty($data->open_designation)) ? $designation = get_string('audience_designation', 'local_classroom', $data->open_designation) : $designation = get_string('audience_designation', 'local_classroom', 'All');
        $data->open_location    = (!empty($data->open_location)) ? $location = get_string('audience_location', 'local_classroom', $data->open_location) : $location = get_string('audience_location', 'local_classroom', 'All');
        
        $data->open_grade    = (!empty($data->open_grade)) ? $grade = get_string('audience_grade', 'local_classroom', $data->open_grade) : $grade = get_string('audience_grade', 'local_classroom', 'All');
       
        return $department . $group . $hrmsrole . $designation . $location . $grade;
    }
}
