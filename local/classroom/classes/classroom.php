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
 * @package Bizlms
 * @subpackage local_classroom
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
use user_picture;
// use \local_classroom\notificationemails as classroomnotifications_emails;
require_once($CFG->dirroot . '/local/classroom/lib.php');
require_once($CFG->dirroot . '/local/courses/lib.php');
define('CLASSROOM_NEW', 0);
define('CLASSROOM_ACTIVE', 1);
define('CLASSROOM_HOLD', 2);
define('CLASSROOM_CANCEL', 3);
define('CLASSROOM_COMPLETED', 4);
define('SESSION_PRESENT', 1);
define('SESSION_ABSENT', 2);
define('CLASSROOM', 1);
define('LEARNINGPLAN', 2);
define('CERTIFICATE', 3);

define('CLASSROOM_NOT_ENROLLED', 0);
define('CLASSROOM_ENROLLED', 1);
define('CLASSROOM_ENROLMENT_REQUEST', 2);
define('CLASSROOM_ENROLMENT_PENDING', 3);

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
    public static function classroomtypes() {
        return array(
            1 => get_string('classroom', 'local_classroom'),
            2 => get_string('learningplan', 'local_classroom'),
            3 => get_string('certificate', 'local_classroom')
        );
    }
    public function manage_classroom($classroom) {
        global $DB, $USER;
        $classroom->shortname = $classroom->name;
        if (empty($classroom->trainers)) {
            $classroom->trainers = null;
        }
        if (empty($classroom->capacity) || $classroom->capacity == 0) {
            $classroom->capacity = null;
        }
        try {
            if ($classroom->id > 0) {
                $classroom->timemodified = time();
                $classroom->usermodified = $USER->id;
                $localclassroom          = $DB->get_record_sql("SELECT id,startdate,enddate,capacity,
                    allow_multi_session,instituteid FROM {local_classroom}
                    where id= :classroomid",array('classroomid' => $classroom->id));
                $allowmultisession       = $localclassroom->allow_multi_session;

                if($classroom->map_certificate == 1){
                    $classroom->certificateid = $classroom->certificateid;
                }else{
                    $classroom->certificateid = null;
                }
                $DB->update_record('local_classroom', $classroom);
                $this->classroom_set_events($classroom);
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroom->id,
                    'other' => 'classroom',
                    'url' => 'view.php',
                    ''
                );
                $event  = \local_classroom\event\classroom_updated::create($params);
                $event->add_record_snapshot('local_classroom', $classroom->id);
                $event->trigger();
                // Update classroom tags.
                // if (isset($classroom->tags)) {
                //     \local_tags_tag::set_item_tags('local_classroom', 'classroom', $classroom->id, context_system::instance(), $classroom->tags, 0, $classroom->costcenter, $classroom->department);
                // }
                if($classroom->capacity > $localclassroom->capacity && $classroom->allow_waitinglistusers==1){
                        $stable = new \stdClass();
                        $stable->search = false;
                        $stable->thead = false;
                        $stable->start = $offset;
                        $stable->length = $limit;
                        $users = $this->classroomwaitinglistusers($classroom->id,$stable,$forenrollment=true);
                        $this->classroom_add_assignusers($classroom->id,$users['classroomusers'], $request=0,$waitinglist=true);

                }
            } else {
                $classroom->status      = 0;
                $classroom->timecreated = time();
                $classroom->usercreated = $USER->id;
                if (has_capability('local/classroom:manageclassroom', context_system::instance())) {
                    $classroom->department = -1;
                    $classroom->subdepartment = -1;
                    if (!is_siteadmin() && (has_capability('local/classroom:manage_owndepartments', context_system::instance())
                         || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                        $classroom->department = $USER->open_departmentid;
                    }
                    // $capability_array = array('local/classroom:manage_owndepartments', 'local/costcenter:manage_owndepartments');
                    // has_any_capabilities($capability_array);
                    // if (!(has_capability('local/classroom:manage_owndepartments', context_system::instance())
                    //      || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                    //     $classroom->subdepartment = $USER->open_subdepartment;
                    // }
                }
                $classroom->id = $DB->insert_record('local_classroom', $classroom);
                $params        = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroom->id
                );
                $event         = \local_classroom\event\classroom_created::create($params);
                $event->add_record_snapshot('local_classroom', $classroom->id);
                $event->trigger();
                // Update classroom tags.
                // if (isset($classroom->tags)) {
                //     \local_tags_tag::set_item_tags('local_classroom', 'classroom', $classroom->id, context_system::instance(), $classroom->tags, 0, $classroom->costcenter, $classroom->department);
                // }
                $classroom->shortname = 'class' . $classroom->id;
                $DB->update_record('local_classroom', $classroom);
            }
            if ($classroom->id) {
                $this->manage_classroom_trainers($classroom->id, 'all', $classroom->trainers);
                $sessionscount = $DB->count_records('local_classroom_sessions', array(
                    'classroomid' => $classroom->id
                ));
                if (($classroom->id == 0 && $classroom->allow_multi_session == 1) ||
                    (($classroom->allow_multi_session != $allowmultisession || $sessionscount == 0)
                     && $classroom->id > 0 && $classroom->allow_multi_session == 1)) {
                    $this->manage_classroom_automatic_sessions($classroom->id, $classroom->startdate, $classroom->enddate);
                }else if($classroom->id > 0 && $classroom->allow_multi_session == 0){
                    $this->manage_classroom_induction_automatic_sessions($classroom->id, $classroom->startdate, $classroom->enddate);
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $classroom->id;
    }
    public function classroom_set_events($classroom) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/calendar/lib.php');
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
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } else {
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                $event->eventtype  = 'open';
                \calendar_event::create($event);
            }
        } else if ($eventid) {
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        }
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
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } else {
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                \calendar_event::create($event);
            }
        } else if ($eventid) {
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        }
    }
    public function manage_classroom_sessions($session) {
        global $DB, $USER,$CFG;
       // require_once($CFG->dirroot . '/local/o365/classes/feature/calsync/main.php');
        $session->description = $session->cs_description['text'];
        try {
            $sessionsvalidationstart = $this->sessions_validation($session->classroomid, $session->timestart, $session->id);
            $session->duration       = ($session->timefinish - $session->timestart) / 60;
            if ($sessionsvalidationstart) {
                return true;
            }
            $sessionsvalidationend = $this->sessions_validation($session->classroomid, $session->timefinish, $session->id);
            if ($sessionsvalidationend) {
                return true;
            }
           
            if ($session->id > 0) {
                $session->timemodified = time();
                $session->usermodified = $USER->id;
                $DB->update_record('local_classroom_sessions', $session);
               
                $this->classroom_calendar_update_event($session);
                $this->session_set_events($session);
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
                }
                $classroom                = new stdClass();
                $classroom->id            = $session->classroomid;
                $classroom->totalsessions = $DB->count_records('local_classroom_sessions', array(
                    'classroomid' => $session->classroomid
                ));
                $DB->update_record('local_classroom', $classroom);
            } else {
                
                $session->timecreated = time();
                $session->usercreated = $USER->id;
                $session->id = $DB->insert_record('local_classroom_sessions', $session);
                $this->classroom_calendar_create_event($session);
                $this->session_set_events($session);
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
                    }
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
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $session->id;
    }

    public function manage_classroom_sessions_induction_technical($session) {
        global $DB, $USER,$CFG;
        $session->description = $session->cs_description['text'];
        try {
            $sessionsvalidationstart = $this->sessions_validation($session->classroomid, $session->timestart, $session->id);
            $session->duration       = ($session->timefinish - $session->timestart) / 60;
            if ($sessionsvalidationstart) {
                return true;
            }
            $sessionsvalidationend = $this->sessions_validation($session->classroomid, $session->timefinish, $session->id);
            if ($sessionsvalidationend) {
                return true;
            }
           
            if ($session->id > 0) {
                $session->timemodified = time();
                $session->usermodified = $USER->id;
                $DB->update_record('local_classroom_sessions', $session);
                 $this->classroom_calendar_update_event($session);
               
                $this->session_set_events($session);
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
                }
                $classroom                = new stdClass();
                $classroom->id            = $session->classroomid;
                $classroom->totalsessions = $DB->count_records('local_classroom_sessions', array(
                    'classroomid' => $session->classroomid
                ));
                $DB->update_record('local_classroom', $classroom);
            } else {
                
                $session->timecreated = time();
                $session->usercreated = $USER->id;
                $session->id = $DB->insert_record('local_classroom_sessions', $session);
                $this->classroom_calendar_create_event($session);
                $this->session_set_events($session);
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
                    }
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
                }
            }
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $session->id;
    }

    public function classroom_calendar_create_event($session){
        global $DB;    
        $main = new \local_o365\feature\calsync\main();
        $muserid = get_config('local_classroom', 'outlook_event_credential');
        $apiclient = $main->construct_calendar_api($muserid,true);
        $attendees_sql = "SELECT u.id,u.email,u.firstname,u.lastname FROM {user} u 
                                  JOIN {local_classroom_users} lcu ON lcu.userid = u.id AND lcu.classroomid = $session->classroomid ";
        $attendees  = $DB->get_records_sql($attendees_sql);
        $classroomname = $DB->get_field('local_classroom','name',array('id' => $session->classroomid));
        $strings = new stdClass();
        $strings->classroomname = $classroomname;
        
        if($session->description){
                $body = $session->description;
        }else{
               $body = '';
        }
        $timestart = $session->timestart;
        $timefinish = $session->timefinish;
        $response = $apiclient->create_event(get_string('calander_subject_create','local_classroom',$strings),$body,$timestart, $timefinish, $attendees,[],null);
        $outlookeventid = $response['id'];
        if($session->id)
         $sessioninfo = new stdClass();
         $sessioninfo->id = $session->id;
         $sessioninfo->outlookeventid = $outlookeventid;
         $DB->update_record('local_classroom_sessions', $sessioninfo);
          
    }
  
  public function classroom_release_calendar_create_event($classroomid){
       global $DB,$USER;
       $main = new \local_o365\feature\calsync\main();
       $muserid = get_config('local_classroom', 'outlook_event_credential');;
       $apiclient = $main->construct_calendar_api($muserid,true);
       $outlookeventid_sql = "SELECT lcs.id as sessionid,lcs.name,lcs.outlookeventid FROM {local_classroom_sessions} lcs WHERE lcs.classroomid = $classroomid AND lcs.outlookeventid <> '0' AND lcs.outlookeventid != '' ";
       $outlookeventdata = $DB->get_records_sql($outlookeventid_sql);
       foreach($outlookeventdata AS $outlookevent){
           $attendees_sql = "SELECT u.id,u.email,u.firstname,u.lastname FROM {user} u 
                                  JOIN {local_classroom_users} lcu ON lcu.userid = u.id AND lcu.classroomid = $classroomid ";
           $attendees  = $DB->get_records_sql($attendees_sql);
           $classroomname = $DB->get_field('local_classroom','name',array('id' => $classroomid));
           $sessionname = $DB->get_field('local_classroom_sessions','name',array('id' => $outlookevent->sessionid));
           $sessiondescription = $DB->get_field('local_classroom_sessions','description',array('id' => $outlookevent->sessionid));
           $strings = new stdClass();
           $strings->classroomname = $classroomname;
           $strings->sessionname = $sessionname;
           if($sessiondescription){
                $body = $sessiondescription;
            }else{
                   $body = '';
            }
            $timestart = $DB->get_field('local_classroom_sessions','timestart',array('id' => $outlookevent->sessionid));
            $timefinish = $DB->get_field('local_classroom_sessions','timefinish',array('id' => $outlookevent->sessionid));
           $response = $apiclient->create_event(get_string('calander_subject','local_classroom',$strings),$body,time()+86400, time()+90000, $attendees,[],null);
           $outlookeventid = $response['id'];
           if($outlookevent->sessionid)
             $sessioninfo = new stdClass();
             $sessioninfo->id = $outlookevent->sessionid;
             $sessioninfo->outlookeventid = $outlookeventid;
             $sessioninfo->usermodified = $USER->id;
             $sessioninfo->timemodified = time();
             $DB->update_record('local_classroom_sessions',$sessioninfo);
       }
   } 

  public function classroom_calendar_update_event($session){ 
        global $DB;
          $main = new \local_o365\feature\calsync\main();
          $muserid = get_config('local_classroom', 'outlook_event_credential');;
          $apiclient = $main->construct_calendar_api($muserid,true);
          $outlookeventid = $DB->get_field('local_classroom_sessions','outlookeventid',array('id' => $session->id));
          $classroomname = $DB->get_field('local_classroom','name',array('id' => $session->classroomid));
          $sessionname = $DB->get_field('local_classroom_sessions','name',array('id' => $session->id));
          $calendarstrings = new stdClass();
          $calendarstrings->classroomname = $classroomname;
          $calendarstrings->sessionname = $sessionname;
          $updated['subject'] = get_string('calander_subject','local_classroom',$calendarstrings);
          if($session->description){
            $body = $session->description;
          }else{
            $body = '';
          }
          $updated['body'] = $body;
          $updated['starttime'] = $session->timestart;
          $updated['endtime'] = $session->timefinish;
          $attendees_sql = "SELECT u.* FROM {user} u 
                                  JOIN {local_classroom_users} lcu ON lcu.userid = u.id AND lcu.classroomid = $session->classroomid ";
          $attendees  = $DB->get_records_sql($attendees_sql);
          $updated['attendees'] = $attendees;
          $apiclient->update_event($outlookeventid, $updated);
         
    }
    
    public function classroom_user_calendar_update_event($classroomid){
        global $DB,$USER;
        try{
              $main = new \local_o365\feature\calsync\main();
              $muserid = get_config('local_classroom', 'outlook_event_credential');;
              $apiclient = $main->construct_calendar_api($muserid,true);
              $outlookeventid_sql = "SELECT lcs.id as sessionid,lcs.name,lcs.outlookeventid,lcs.timestart as timestart,lcs.timefinish as timefinish FROM {local_classroom_sessions} lcs WHERE lcs.classroomid = $classroomid AND lcs.outlookeventid <> '0' AND lcs.outlookeventid != '' ";
              $outlookeventdata = $DB->get_records_sql($outlookeventid_sql);
                if($outlookeventdata){
                    $attendees_sql = "SELECT u.* FROM {user} u
                                              JOIN {local_classroom_users} lcu ON lcu.userid = u.id AND lcu.classroomid = $classroomid ";
                    $attendees = $DB->get_records_sql($attendees_sql);
                    foreach($outlookeventdata AS $outlookevent) {   
                      $outlookeventid = $outlookevent->outlookeventid;
                      $classroomname = $DB->get_field('local_classroom','name',array('id' => $classroomid));
                      $sessionname = $DB->get_field('local_classroom_sessions','name',array('id' => $outlookevent->sessionid));
                      $sessiondescription = $DB->get_field('local_classroom_sessions','description',array('id' => $outlookevent->sessionid));
                      $strings = new stdClass();
                      $strings->classroomname = $classroomname;
                      $strings->sessionname = $sessionname;
                      $updated['subject'] = get_string('calander_subject','local_classroom',$strings);
                      if($sessiondescription){
                        $body = $sessiondescription;
                      }else{
                        $body = '';
                      }
                      $updated['body'] = $body;
                      $updated['starttime'] = $outlookevent->timestart;
                      $updated['endtime'] = $outlookevent->timefinish;
                      $updated['attendees'] = $attendees;
                      $apiclient->update_event($outlookeventid, $updated); 
                   } 
                }
           
       } catch(\Exception $ex){
            $calendarerror = new stdClass();
            $calendarerror->classroomid = $classroomid;
            $calendarerror->errormessage = $ex->getMessage(); 
            $calendarerror->usercreated = $USER->id;
            $calendarerror->timecreated = time();
            $DB->insert_record('local_classroom_calendarlogs',$calendarerror); 
        }
    }

   public static function classroom_calendar_delete_event($classroomid){
          global $DB;
          $main = new \local_o365\feature\calsync\main();
          $muserid = get_config('local_classroom', 'outlook_event_credential');;
          $apiclient = $main->construct_calendar_api($muserid,true);
          $outlookeventid_sql = "SELECT lcs.id as sessionid,lcs.outlookeventid FROM {local_classroom_sessions} lcs WHERE lcs.classroomid = $classroomid AND lcs.outlookeventid <> '0' AND lcs.outlookeventid != '' ";
          $outlookeventdata = $DB->get_records_sql($outlookeventid_sql);
          if($outlookeventdata){
            foreach($outlookeventdata AS $outlookevent){
               $outlookeventid = $outlookevent->outlookeventid;
               $apiclient->delete_event($outlookeventid); 
            }
          }
      }

 public static function classroom_calendar_session_delete_event($classroomid,$sessionid){
      global $DB;
      $main = new \local_o365\feature\calsync\main();
      $muserid = get_config('local_classroom', 'outlook_event_credential');;
      $apiclient = $main->construct_calendar_api($muserid,true);
      $outlookeventid = $DB->get_field('local_classroom_sessions','outlookeventid',array('id' => $sessionid, 'classroomid' => $classroomid));
      $apiclient->delete_event($outlookeventid);
}

 public function session_set_events($session) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/calendar/lib.php');
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
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } else {
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                $event->eventtype  = 'open';
                \calendar_event::create($event);
            }
        } else if ($eventid) {
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        }
        $eventid = $DB->get_field('event', 'id', array(
            'modulename' => '0',
            'instance' => 0,
            'plugin' => 'local_classroom',
            'plugin_instance' => $session->classroomid,
            'plugin_itemid' => $session->id,
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
                $event->id     = $eventid;
                $calendarevent = \calendar_event::load($event->id);
                $calendarevent->update($event);
            } else {
                $event->courseid   = 0;
                $event->groupid    = 0;
                $event->userid     = 0;
                $event->modulename = 0;
                $event->instance   = 0;
                \calendar_event::create($event);
            }
        } else if ($eventid) {
            $calendarevent = \calendar_event::load($eventid);
            $calendarevent->delete();
        }
    }
    public function manage_classroom_completions($completions) {
        global $DB, $USER;
        if (!empty($completions->sessionids) && is_array($completions->sessionids)) {
            $completions->sessionids = implode(',', $completions->sessionids);
        } else {
            $completions->sessionids = null;
        }
        if (!empty($completions->courseids) && is_array($completions->courseids)) {
            $completions->courseids = implode(',', $completions->courseids);
        } else {
            $completions->courseids = null;
        }
        if (empty($completions->sessiontracking)) {
            $completions->sessiontracking = null;
        }
        if (empty($completions->coursetracking)) {
            $completions->coursetracking = null;
        }
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
            } else {
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
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $completions->id;
    }
    public function classroom_sessions_delete($classroomid) {
        global $DB, $USER;
        $classroomsessions = $DB->get_records_sql_menu("SELECT id,id as sessionid FROM {local_classroom_sessions}
                                                where classroomid = :classroomid",array('classroomid' => $classroomid));
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
        }
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
                                                WHERE  lcs.classroomid = :classroomid
                                                and lcs.id in(SELECT sessionid  FROM {local_classroom_attendance}
                                                where classroomid = $classroomid and userid = $classroomuser
                                                and status = 1)",array('classroomid' => $classroomid));
            if (empty($attendedsessionshours)) {
                $attendedsessionshours = 0;
            }
            $DB->execute('UPDATE {local_classroom_users} SET attended_sessions = ' . $attendedsessions . ',hours = ' . $attendedsessionshours . ', timemodified = ' . time() . ',
                        usermodified = ' . $USER->id . ' WHERE classroomid = ' . $classroomid . ' AND userid = ' . $classroomuser);
        }
    }
    public function location_date($data) {
        global $DB, $USER;
        $location                       = new stdClass();
        $location->institute_type       = $data->institute_type;
        $location->instituteid          = $data->instituteid;
        $location->nomination_startdate = $data->nomination_startdate;
        $location->nomination_enddate   = $data->nomination_enddate;
        try {
            $localclassroom = $DB->get_record_sql("SELECT id,instituteid FROM {local_classroom} where id = :id ",array('id' => $data->id));
            if (isset($location->instituteid) && ($location->instituteid != $localclassroom->instituteid) && ($localclassroom->instituteid != 0)) {
                $DB->execute('UPDATE {local_classroom_sessions} SET roomid =0,timemodified = ' . time() . ',
                   usermodified = ' . $USER->id . ' WHERE classroomid = ' . $data->id . '');
            }
            $location->id           = $data->id;
            $location->timemodified = time();
            $location->usermodified = $USER->id;
            $DB->update_record('local_classroom', $location);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $data->id;
    }

    public function get_classrooms($status, $search, $start, $perpage) {
        global $DB, $USER, $OUTPUT, $PAGE;
        $PAGE->set_context(1);

        $params          = array();
        $classrooms      = array();
        $classroomscount = 0;
        // $concatsql       = '';
        $condition       = '';

        if($status >= 0){
            $condition .= " AND c.status = :status ";
        }
        $params['status'] = $status;
        if (!empty($search)) {
            $condition .= " AND (c.name LIKE :search )";
            $params['search'] = '%' . $search . '%';
        }
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin()
            && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $params['costcenter'] = $USER->open_costcenterid;
            $condition .= " AND (cc.id = :costcenter)";
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())
                 || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $params['department'] = $USER->open_departmentid;
                $condition .= " AND (c.department = :department )";
            }
            if (has_capability('local/classroom:trainer_viewclassroom', context_system::instance())) {
                $myclassrooms = $DB->get_records_menu('local_classroom_trainers', array(
                    'trainerid' => $USER->id
                ), 'id', 'id, classroomid');
                if (!empty($myclassrooms)) {
                    list($relatedclassromsql, $relatedclassroomparams) = $DB->get_in_or_equal($myclassrooms, SQL_PARAMS_NAMED, 'myclassrooms');
                    $params = array_merge($params,$relatedclassroomparams);
                    $condition .= " AND c.id $relatedclassromsql";
                }else{
                    return array('classrooms' => array(), 'classroomscount' =>0);
                }
            }
        //end user condition
        } else if (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) {
            $myclassrooms = $DB->get_records_menu('local_classroom_users', array(
                'userid' => $USER->id
            ), 'id', 'id, classroomid');

            if (!empty($myclassrooms)) {
                    list($relatedclassromsql, $relatedclassroomparams) = $DB->get_in_or_equal($myclassrooms, SQL_PARAMS_NAMED, 'myclassrooms');
                    $params = array_merge($params,$relatedclassroomparams);
                    $condition .= " AND c.id $relatedclassromsql";
                }else{
                    return array('classrooms' => array(), 'classroomscount' =>0);
                }
        } else {
            // $statusarrays = implode(',', $status);//implode(',', $statusarray);
            // $concatsql .= " AND c.status in ($statusarrays) ";
        }
        // if (isset($stable->classroomid) && $stable->classroomid > 0) {
        //     $concatsql .= " AND c.id = :classroomid";
        //     $params['classroomid'] = $stable->classroomid;
        // }
        // if (isset($stable->classroomstatus) && $stable->classroomstatus != -1) {
        //     $concatsql .= " AND c.status = :classroomstatus";
        //     $params['classroomstatus'] = $stable->classroomstatus;
        // }
        $countsql = "SELECT COUNT(c.id) ";
        // if ($request == true) {
        //     $fromsql = "SELECT group_concat(c.id) as classroomids";
        // } else {
            $fromsql = "SELECT c.*, (SELECT COUNT(DISTINCT cu.userid)
                                  FROM {local_classroom_users} AS cu
                                  WHERE cu.classroomid = c.id
                              ) AS enrolled_users";
        // }
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin()
            && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $joinon = "cc.id = c.costcenter";
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())
                || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $joinon = "cc.id = c.department OR cc.id = c.costcenter";
            }
        } else {
            $joinon = "cc.id = c.costcenter";
        }
        $sql = " FROM {local_classroom} AS c
                 JOIN {local_costcenter} AS cc ON $joinon
                WHERE 1 = 1 ";
        //added by sarath for ticket 2751
        if(!is_siteadmin() && !has_capability('local/classroom:view_holdclassroomtab', context_system::instance())){
            $sql .= " AND c.status != 2";
        }

        if(!is_siteadmin() && !has_capability('local/classroom:view_newclassroomtab', context_system::instance())){
            $sql .= " AND c.status != 0";
        }
        //ended here by sarath

        $sql .= $condition;
        // print_object($countsql . $sql. $params);
        // print_r($params);
        // print_object($fromsql . $sql. $params);
        // print_r($params);
        // exit;
        // if (isset($stable->classroomid) && $stable->classroomid > 0) {
        //     $classrooms = $DB->get_record_sql($fromsql . $sql, $params);
        // } else {
            try {
                $classroomscount = $DB->count_records_sql($countsql . $sql, $params);
                // if ($stable->thead == false) {
                    $sql .= " ORDER BY c.id DESC";
                    // if ($request == true) {
                        // $classrooms = $DB->get_record_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    // } else {
                        $classrooms = $DB->get_records_sql($fromsql . $sql, $params, $start, $perpage);
                        $row = array();
                        foreach ($classrooms as $sdata) {
                            $line = array();
                            //-----class room summary image
                            $classesimg = $this->classroom_logo($sdata->classroomlogo);
                            if($classesimg == false){
                                $classesimg = $OUTPUT->image_url('classviewnew', 'local_classroom');
                            } 
                            $classesimg = $classesimg->out(); 
                        /*     if ($sdata->classroomlogo > 0) {
                                $classroominclude = new \local_classroom\includes();
                                 $classesimg = $this->classroom_logo($sdata->classroomlogo);
                                if($classesimg == false){
                                    $classesimg = $OUTPUT->image_url('classviewnew', 'local_classroom');
                                } 
                            }else{
                                $classesimg = $OUTPUT->image_url('classviewnew', 'local_classroom');
                            }
                            $classesimg = $classesimg->out(); */

                            //-------data variables
                            $classname = $sdata->name;
                            $classname_string = strlen($classname) > 40 ? substr($classname, 0, 40)."..." : $classname;
                            $usercreated = $sdata->usercreated;
                            //$user = $DB->get_record('user', array('id' => $usercreated));
                            //$createdBy = $user->firstname.'&nbsp;'.$user->lastname;
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
                                $classroomdepartment = $DB->get_fieldset_select('local_costcenter', 'fullname', " CONCAT(',',$sdata->department,',') LIKE CONCAT('%,',id,',%') ", array());//FIND_IN_SET(id, '$sdata->department')
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
                            // $classroom_actionstatus = $this->classroom_actionstatus($sdata);
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
                            // $line ['classroom_actionstatus'] = array_values(($classroom_actionstatus));
                            $classroomcoursessql = "SELECT c.id, c.fullname
                                                      FROM {course} AS c
                                                     /* JOIN {enrol} AS en on en.courseid=c.id and en.enrol='classroom' and en.status=0 */
                                                      JOIN {local_classroom_courses} AS cc ON cc.courseid = c.id
                                                     WHERE c.visible = 1 AND cc.classroomid = :id";

                            $classroomcourses = $DB->get_records_sql($classroomcoursessql,array('id' => $sdata->id),0,2);
                            $line ['courses'] = array();
                            if (!empty($classroomcourses)) {
                                foreach($classroomcourses as $classroomcourse) {
                                    $courseslimit = true;
                                    $coursename = strlen($classroomcourse->fullname) > 15 ? substr($classroomcourse->fullname, 0, 15)."..." : $classroomcourse->fullname;
                                    $courseurl = new moodle_url('/course/view.php', array('id' => $classroomcourse->id));
                                    $courseurl = $courseurl->out();
                                    $line ['courses'][] = array('coursetitle' => $classroomcourse->fullname, 'coursename' => $coursename,'courseurl' => $courseurl);

                                }
                            }
                            $line ['enrolled_users'] = $enrolled_users;
                            $line ['departmentname'] = $departmentname;
                            $line['departmenttitle'] = $departmenttitle;
                            $line ['classroomid'] = $sdata->id;
                            $classroomurl = new moodle_url('/local/classroom/view.php', array('cid' => $sdata->id));
                            $classroomurl = $classroomurl->out();
                            $line ['classroomurl'] = $classroomurl;
                            $classroomtrainerssql = "SELECT u.*
                                                      FROM {user} AS u
                                                      JOIN {local_classroom_trainers} AS ct ON ct.trainerid = u.id
                                                      WHERE u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND ct.classroomid = :id";

                            $classroomtrainers = $DB->get_records_sql($classroomtrainerssql,array('id' => $sdata->id),0,2);
                            $line['trainers']  = array();
                            if(!empty($classroomtrainers)) {
                                $trainerslimit = false;
                                foreach($classroomtrainers as $classroomtrainer) {
                                    $trainerslimit = true;
                                    $trainername = strlen(fullname($classroomtrainer)) > 8 ? substr(fullname($classroomtrainer), 0, 8)."..." : fullname($classroomtrainer);

                                    $user_picture = new user_picture($classroomtrainer);
                                    $classroomtrainerpic = $user_picture->get_url($PAGE);
                                    $classroomtrainerpic = $classroomtrainerpic->out();
                                    $classroomtrainerprofileurl = new moodle_url('/user/profile.php', array('id' => $classroomtrainer->id));
                                    $classroomtrainerprofileurl = $classroomtrainerprofileurl->out();
                                    $line['trainers'][] = array('trainerpic' => $classroomtrainerpic, 'trainername' => $trainername, 'trainerdesignation' => '', 'trainerprofileurl' => $classroomtrainerprofileurl);
                                }
                            }
                            if(count($classroomtrainers) > 2){
                                $trainerslimit = false;
                                $line['moretrainers'] = array_slice($line['trainers'], 0, 2);
                            }else{
                                $line['moretrainers'] = array();
                            }

                            $line ['trainerslimit'] = $trainerslimit;
                            // $line ['editicon'] = $OUTPUT->image_url('t/edit');
                            // $line ['deleteicon'] = $OUTPUT->image_url('t/delete');
                            // $line ['assignusersicon'] = $OUTPUT->image_url('t/assignroles');
                             $classroomcompletion_id=$DB->get_field('local_classroom_completion','id',array('classroomid'=>$sdata->id));
                                if(!$classroomcompletion_id){
                                    $classroomcompletion_id=0;
                                }

                            $line['classroomcompletion'] = false;
                            
                            $line['action'] = false;
                            $line['edit'] = false;
                            $line['delete'] = false;
                            $line['assignusers'] = false;
                            $line['assignusersurl'] = false;

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
                                    $assignusersurl = new moodle_url("/local/classroom/enrollusers.php?cid=".$sdata->id."");
                                    $line ['assignusersurl'] = $assignusersurl->out();
                                    $mouse_overicon=true;
                            }
                             if ((has_capability('local/classroom:classroomcompletion', context_system::instance()) || is_siteadmin())) {
                                $line['classroomcompletion'] =  true;
                            }
                            $line['classroomcompletion_id'] = $classroomcompletion_id;
                            $line['mouse_overicon'] = $mouse_overicon;
                            // $row[] = $this->render_from_template('local_classroom/browseclassroom', $line);
                            $row[] = $line;

                        }

            } catch (dml_exception $ex) {
                $classroomscount = 0;
            }
        // }
        // if (isset($stable->classroomid) && $stable->classroomid > 0) {
        //     return $classrooms;
        // } else {
            return array('classrooms' => $row, 'classroomscount' => $classroomscount);
        // }
    }

    public function classrooms($stable, $request = false) {
        global $DB, $USER;
        $params          = array();
        $classrooms      = array();
        $classroomscount = 0;
        $concatsql       = '';
        $statusarray     = array();
        if (has_capability('local/classroom:view_newclassroomtab', context_system::instance())) {
            $statusarray[] = 0;
        }
        $statusarray[] = 1;
        if (has_capability('local/classroom:view_holdclassroomtab', context_system::instance())) {
            $statusarray[] = 2;
        }
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
        }
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin()
            && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $condition            = " AND (cc.id = :costcenter)";
            $params['costcenter'] = $USER->open_costcenterid;
            $statusarrays         = implode(',', $statusarray);
            $concatsql .= " AND c.status in ($statusarrays) ";
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())
                 || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $condition .= " AND (c.department = :department )";
                $params['department'] = $USER->open_departmentid;
            }
            $concatsql .= $condition;
            if (has_capability('local/classroom:trainer_viewclassroom', context_system::instance())) {
                $myclassrooms = $DB->get_records_menu('local_classroom_trainers', array(
                    'trainerid' => $USER->id
                ), 'id', 'id, classroomid');
                if (!empty($myclassrooms)) {
                    list($relatedclassromsql, $relatedclassroomparams) = $DB->get_in_or_equal($myclassrooms, SQL_PARAMS_NAMED, 'myclassrooms');
                    $params = array_merge($params,$relatedclassroomparams);
                    $concatsql .= " AND c.id $relatedclassromsql";
                } else {
                    return compact('classrooms', 'classroomscount');
                }
            }
        } else if (!is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) {
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
                $classroomcostcenter = $DB->get_field('local_classroom', 'costcenter', array(
                    'id' => $stable->classroomid
                ));
                if ($status == 1 && !$userenrolstatus && $classroomcostcenter == $USER->open_costcenterid) {
                    $empty = 1;
                } else {
                    if (!empty($myclassrooms)) {

                        list($relatedclassromsql, $relatedclassroomparams) = $DB->get_in_or_equal($myclassrooms, SQL_PARAMS_NAMED, 'myclassrooms');
                        $params = array_merge($params,$relatedclassroomparams);
                        $concatsql .= " AND c.id $relatedclassromsql";      

                        list($relatedstatussql, $relatedstatusparams) = $DB->get_in_or_equal($statusarray, SQL_PARAMS_NAMED, 'status');
                        $params = array_merge($params,$relatedstatusparams);
                        $concatsql .= " AND c.status $relatedstatussql ";
                    } else {
                        return compact('classrooms', 'classroomscount');
                    }
                }
            } else {
                if (!empty($myclassrooms)) {
                        list($relatedclassromsql, $relatedclassroomparams) = $DB->get_in_or_equal($myclassrooms, SQL_PARAMS_NAMED, 'myclassrooms');
                        $params = array_merge($params,$relatedclassroomparams);
                        $concatsql .= " AND c.id $relatedclassromsql";      

                        list($relatedstatussql, $relatedstatusparams) = $DB->get_in_or_equal($statusarray, SQL_PARAMS_NAMED, 'status');
                        $params = array_merge($params,$relatedstatusparams);
                        $concatsql .= " AND c.status $relatedstatussql ";
                } else {
                    return compact('classrooms', 'classroomscount');
                }
            }
        } else {
            list($relatedstatussql, $relatedstatusparams) = $DB->get_in_or_equal($statusarray, SQL_PARAMS_NAMED, 'status');
            $params = array_merge($params,$relatedstatusparams);
            $concatsql .= " AND c.status $relatedstatussql ";
        }
        if (isset($stable->classroomid) && $stable->classroomid > 0) {
            $concatsql .= " AND c.id = :classroomid";
            $params['classroomid'] = $stable->classroomid;
        }
        if (isset($stable->classroomstatus) && $stable->classroomstatus != -1) {
            $concatsql .= " AND c.status = :classroomstatus";
            $params['classroomstatus'] = $stable->classroomstatus;
        }
        $countsql = "SELECT COUNT(c.id) ";
        // if ($request == true) {
        //     $fromsql = "SELECT group_concat(c.id) as classroomids";
        // } else {
            $fromsql = "SELECT c.*, (SELECT COUNT(DISTINCT cu.userid)
                                  FROM {local_classroom_users} AS cu
                                  WHERE cu.classroomid = c.id
                              ) AS enrolled_users";
        // }
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin()
            && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $joinon = "cc.id = c.costcenter";
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())
                || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $joinon = "cc.id = c.department OR cc.id = c.costcenter";
            }
        } else {
            $joinon = "cc.id = c.costcenter";
        }
        $sql = " FROM {local_classroom} AS c
                 JOIN {local_costcenter} AS cc ON $joinon
                WHERE 1 = 1 ";
        $sql .= $concatsql;
        if (isset($stable->classroomid) && $stable->classroomid > 0) {
            $classrooms = $DB->get_record_sql($fromsql . $sql, $params);
        } else {
            try {
                $classroomscount = $DB->count_records_sql($countsql . $sql, $params);
                if ($stable->thead == false) {
                    $sql .= " ORDER BY c.id DESC";
                    if ($request == true) {
                        $classrooms = $DB->get_record_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    } else {
                        $classrooms = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                    }
                }
            } catch (dml_exception $ex) {
                $classroomscount = 0;
            }
        }
        if (isset($stable->classroomid) && $stable->classroomid > 0) {
            return $classrooms;
        } else {
            return compact('classrooms', 'classroomscount');
        }
    }
    public function classroomsessions($classroomid, $stable) {
        global $DB, $USER;
        $classroom = $DB->get_record('local_classroom', array(
            'id' => $classroomid
        ));
        if (empty($classroom)) {
            print_error('classroom data missing');
        }
        $concatsql = '';
        if (!empty($stable->search)) {
            $fields = array(
                0 => 'cs.name',
                1 => 'cr.name'
            );
            $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
            $fields .= " LIKE '%" . $stable->search . "%' ";
            $concatsql .= " AND ($fields) ";
        }
        $params     = array();
        $classrooms = array();
        $countsql   = "SELECT COUNT(cs.id) ";
        $fromsql    = "SELECT cs.*, cr.name as room";
        $sql        = " FROM {local_classroom_sessions} AS cs
                LEFT JOIN {user} AS u ON u.id = cs.trainerid
                LEFT JOIN {local_location_room} AS cr ON cr.id = cs.roomid
                WHERE 1 = 1 AND cs.classroomid = :classroomid";
        $sql .= $concatsql;
        $params['classroomid'] = $classroomid;
        try {
            $sessionscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY cs.id DESC";
                $sessions = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            }
        } catch (dml_exception $ex) {
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
            
            $params['start']   = strtotime($params['sessiondate_end'].':00');
            $params['ednd']   = strtotime($params['sessiondate_end'].':59');

            $params['estart']   = strtotime($params['sessiondate_end'].':00');
            $params['eend']   = strtotime($params['sessiondate_end'].':59');

            $sql  = "SELECT id FROM {local_classroom_sessions} where classroomid=:classroomid
            and ((timestart >=:start and timestart <=:ednd) or (timefinish >=:estart and timefinish <=:eend))";
            if ($sessionid > 0) {
                $sql .= " AND id !=:sessionid ";
                $params['sessionid'] = $sessionid;
            }
            $return = $DB->record_exists_sql($sql, $params);
        }
        return $return;
    }
    public function add_classroom_signups($classroomid, $userid, $sessionid = 0) {
        global $DB, $USER;
        $classroom = $DB->record_exists('local_classroom', array(
            'id' => $classroomid
        ));
        if (!$classroom) {
            print_error("Classroom Not Found!");
        }
        $user = $DB->record_exists('user', array(
            'id' => $userid
        ));
        if (!$user) {
            print_error("User Not Found!");
        }
        if ($sessionid > 0) {
            $session = $DB->record_exists('local_classroom_sessions', array(
                'id' => $sessionid,
                'classroomid' => $classroomid
            ));
            if (!$session) {
                print_error("Session Not Found!");
            }
        }
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
            } else {
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
        }
        return true;
    }
    public function remove_classroom_signups($classroomid, $userid, $sessionid = 0) {
        global $DB, $USER;
        if ($sessionid > 0) {
            $sessions = $DB->get_records('local_classroom_sessions', array(
                'classroomid' => $classroomid,
                'id' => $sessionid
            ));
        } else {
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
            }
        }
        return true;
    }
    public function classroom_get_attendees($classroomid, $sessionid = 0) {
        global $DB, $OUTPUT,$USER;
        $concatsql       = "";
        $selectfileds    = '';
        $whereconditions = '';

        $params = array();

        if ($sessionid > 0) {
            $selectfileds = ", ca.id as attendanceid, ca.status";
            $concatsql .= " JOIN {local_classroom_sessions} AS cs ON cs.classroomid = cu.classroomid AND cs.classroomid = $classroomid
            LEFT JOIN {local_classroom_attendance} AS ca ON ca.classroomid = cu.classroomid
              AND ca.sessionid = cs.id AND ca.userid = cu.userid";
            $whereconditions = " AND cs.id = :sessionid";
            $params['sessionid'] = $sessionid;
        }
        $signupssql = "SELECT DISTINCT u.id, u.firstname, u.lastname,
                              u.email, u.picture, u.firstnamephonetic, u.lastnamephonetic,
                              u.middlename, u.alternatename, u.imagealt $selectfileds
                        FROM {user} AS u
                        JOIN {local_classroom_users} AS cu ON
                                (cu.userid = u.id AND cu.classroomid = $classroomid)
                            $concatsql
                       WHERE cu.classroomid = :classroomid $whereconditions";
                       $params['classroomid'] = $classroomid;

        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin()
            && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $condition            = " AND (u.open_costcenterid = :costcenter)";
            $params['costcenter'] = $USER->open_costcenterid;
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())
                 || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $condition .= " AND (u.open_departmentid = :department )";
                $params['department'] = $USER->open_departmentid;
            }
            if (has_capability('local/classroom:trainer_viewclassroom', context_system::instance())) {
                 $condition="";
            }
            $signupssql .= $condition;
        }
        $signups    = $DB->get_records_sql($signupssql,$params);
        return $signups;
    }
    public function classroom_evaluations($classroomid,$stable) {
        global $DB, $USER;
        $params     = array();
        $selectsql = "SELECT e.* ";
        $countsql = "SELECT count(e.id) ";
        $sql      = " FROM {local_evaluations} AS e
                    WHERE e.plugin = 'classroom' AND e.instance = :classroomid AND e.deleted = 0 ";
        if ((has_capability('local/classroom:editfeedback', context_system::instance()) || has_capability('local/classroom:deletefeedback', context_system::instance()))&&(has_capability('local/classroom:manageclassroom', context_system::instance()) )) {
            $sql .=" AND e.visible <> 2 ";
        }else{
            $sql .=" AND e.visible =1 ";
        }                 
        $params['classroomid'] = $classroomid;
        $evaluationscount = $DB->count_records_sql($countsql.$sql,$params);
        try {
            // $sql .= " ORDER BY e.id DESC";
            $evaluations = $DB->get_records_sql($selectsql.$sql, $params,$stable->start,$stable->length);
        } catch (dml_exception $ex) {
            $evaluations = array();
        }
        return compact('evaluationscount','evaluations');
    }
    public function classroom_add_assignusers($classroomid, $userstoassign, $request,$waitinglist=false) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/classroom/notifications_emails.php');
        // $class_emaillogs = new classroomnotifications_emails();
        $notification = new \local_classroom\notification();
        $classroomenrol = enrol_get_plugin('classroom');
        $courses        = $DB->get_records_menu('local_classroom_courses', array(
            'classroomid' => $classroomid
        ), 'id', 'id, courseid');
        $allow          = true;
        $type           = 'classroom_enrol';
        $dataobj        = $classroomid;
        $fromuserid     = $USER->id;
        if ($allow) {
            // $localclassroom = $DB->get_record_sql("SELECT id,name,status FROM {local_classroom} where id= $classroomid");
            $localclassroom = $DB->get_record_sql("SELECT * FROM {local_classroom} where id= $classroomid");
            if($request != 1) {
                $progress       = 0;
                // $progressbar    = new \core\progress\display_if_slow(get_string('enrollusers', 'local_classroom', $localclassroom->name));
                // $progressbar->start_html();
                // $progressbar->start_progress('', count($userstoassign) - 1);
                foreach ($userstoassign as $key => $adduser) {
                    // $progressbar->progress($progress);
                    $progress++;
                    $classroomcapacitycheck = $this->classroom_capacity_check($classroomid,$checking=true);
                    if (!$classroomcapacitycheck) {
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
                            //Outlook calendar event
                        
                            $params            = array(
                                'context' => context_system::instance(),
                                'objectid' => $classroomuser->id
                            );
                            $event             = \local_classroom\event\classroom_users_created::create($params);
                            $event->add_record_snapshot('local_classroom', $localclassroom);
                            $event->trigger();
                            if ($localclassroom->status != 0) {
                                // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser->userid, $fromuserid);
                                $touser = \core_user::get_user($classroomuser->userid);
                                $emaillogs = $notification->classroom_notification($type, $touser, $USER, $localclassroom);
                                foreach ($courses as $course) {
                                    if ($classroomuser->id) {
                                        $enrolclassroomuser = $this->manage_classroom_course_enrolments($course, $adduser, 'employee', 'enrol');
                                    }
                                }
                            }
                            classroom_evaluations_add_remove_users($classroomid, 0, 'users_to_feedback', $adduser);
                            if($waitinglist==true){
                                $DB->execute('UPDATE {local_classroom_waitlist} SET enrolstatus =1,timemodified = ' . time() . ',
                                    usermodified = ' . $USER->id . ' WHERE classroomid = ' .$classroomid. ' AND id='.$key.'');
                            }
                        } catch (dml_exception $ex) {
                            print_error($ex);
                        }
                    } else {
                        $progress--;
                        break;
                    }
                }
                //Outlook calendar event
                $this->classroom_user_calendar_update_event($classroomid);
                // $progressbar->end_html();
            } else {
                $progress=0;
                foreach ($userstoassign as $key => $adduser) {
                    $progress++;
                    $classroomcapacitycheck = $this->classroom_capacity_check($classroomid,$checking=true);
                    if (!$classroomcapacitycheck) {
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
                                // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser->userid, $fromuserid);
                                $touser = \core_user::get_user($classroomuser->userid);
                                $emaillogs = $notification->classroom_notification($type, $touser, $USER, $localclassroom);
                                foreach ($courses as $course) {
                                    if ($classroomuser->id) {
                                        $enrolclassroomuser = $this->manage_classroom_course_enrolments($course, $adduser, 'employee', 'enrol');
                                    }
                                }
                            }
                            classroom_evaluations_add_remove_users($classroomid, 0, 'users_to_feedback', $adduser);
                            if($waitinglist==true){
                                $DB->execute('UPDATE {local_classroom_waitlist} SET enrolstatus =1,timemodified = ' . time() . ',
                                    usermodified = ' . $USER->id . ' WHERE classroomid = ' .$classroomid. ' AND id='.$key.'');
                            }
                        } catch (dml_exception $ex) {
                            print_error($ex);
                        }
                    } else {
                        $progress--;
                        break;
                    }
                }
            }

            $result              = new stdClass();
            $result->changecount = $progress;
            $result->classroom   = $localclassroom->name;
        }
        return $result;
    }
 

    public function classroom_add_waitingusers($classroomid, $userstoassign, $enroltype) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        $return=0;
        $notification = new \local_classroom\notification();
        $classroomenrol = enrol_get_plugin('classroom');
        $type           = 'classroom_enrolwaiting';
        $dataobj        = $classroomid;
        $fromuserid     = $USER->id;
        $localclassroom = $DB->get_record_sql("SELECT * FROM {local_classroom} where id= $classroomid");

            foreach ($userstoassign as $key => $adduser) {
                $sortorder= $DB->get_field_sql("SELECT max(sortorder) FROM {local_classroom_waitlist} where classroomid=$classroomid");
                $classroomuser               = new stdClass();
                $classroomuser->classroomid  = $classroomid;
                $classroomuser->sortorder     = $sortorder+1;
                $classroomuser->userid       = $adduser;
                if($enroltype=='request'){
                    $classroomuser->enroltype = 1;
                }if($enroltype=='myteam'){
                    $classroomuser->enroltype = 2;
                }
                $classroomuser->usercreated  = $USER->id;
                $classroomuser->timecreated  = time();
                try {
                    $existcheck= $DB->get_field_sql("SELECT id FROM {local_classroom_waitlist} where classroomid=:classroomid AND userid=:userid AND enrolstatus=:enrolstatus",array('classroomid'=>$classroomid,'userid'=>$adduser,'enrolstatus'=>0));
                    if(!$existcheck){

                        $return= $DB->insert_record('local_classroom_waitlist', $classroomuser);
                        $params            = array(
                            'context' => context_system::instance(),
                            'objectid' => $return
                        );
                        $event             = \local_classroom\event\classroom_users_waitingcreated::create($params);
                        $event->add_record_snapshot('local_classroom', $localclassroom);
                        $event->trigger();
                        if ($return) {
                            $touser = \core_user::get_user($classroomuser->userid);
                            $emaillogs = $notification->classroom_notification($type, $touser, $USER, $localclassroom,$return);
                        }
                    }else{
                        $return=$existcheck;
                    }
                } catch (dml_exception $ex) {
                    print_error($ex);
                }
            }

        return $return;
    }
    public function classroom_remove_assignusers($classroomid, $userstounassign,$request=false) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/classroom/notifications_emails.php');
        // $class_emaillogs = new classroomnotifications_emails();
        $classroom_notification = new \local_classroom\notification();
        $classroomenrol = enrol_get_plugin('classroom');
        $courses        = $DB->get_records_menu('local_classroom_courses', array(
            'classroomid' => $classroomid
        ), 'id', 'id, courseid');
        $type           = 'classroom_unenroll';
        $dataobj        = $classroomid;
        $fromuserid     = $USER->id;
        try {
            $localclassroom = $DB->get_record_sql("SELECT id,name,status,allow_waitinglistusers FROM {local_classroom} where id= $classroomid");
            $classroominstance = $DB->get_record('local_classroom', array('id' => $classroomid));
            if($request != 1) {
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
                                    $unenrolclassroomuser = $this->manage_classroom_course_enrolments($course, $removeuser, 'employee', 'unenrol');
                                }
                            }
                        }
                    }
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
                        // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $removeuser, $fromuserid);
                        $touser = \core_user::get_user($removeuser);
                        $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER, $classroominstance);
                    }
                    $DB->delete_records('local_classroom_trainerfb', array(
                        'classroomid' => $classroomid,
                        'userid' => $removeuser
                    ));
                    $this->remove_classroom_signups($classroomid, $removeuser);
                    if($localclassroom->allow_waitinglistusers==1){
                        $stable = new \stdClass();
                        $stable->search = false;
                        $stable->thead = false;
                        $stable->start = $offset;
                        $stable->length = $limit;
                        $users = $this->classroomwaitinglistusers($classroomid,$stable,$forenrollment=true);
                        $this->classroom_add_assignusers($classroomid,$users['classroomusers'], $request=0,$waitinglist=true);
                    }
                }
                //Outlook calendar event
                $this->classroom_user_calendar_update_event($classroomid);
                $progressbar->end_html();
            }   else {
                $progress= 0;
                foreach ($userstounassign as $key => $removeuser) {
                    $progress++;
                    if ($localclassroom->status != 0) {
                        if (!empty($courses)) {
                            foreach ($courses as $course) {
                                if ($course > 0) {
                                    $unenrolclassroomuser = $this->manage_classroom_course_enrolments($course, $removeuser, 'employee', 'unenrol');
                                }
                            }
                        }
                    }
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
                        // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $removeuser, $fromuserid);
                        $touser = \core_user::get_user($removeuser);
                        $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER, $classroominstance);
                    }
                    $DB->delete_records('local_classroom_trainerfb', array(
                        'classroomid' => $classroomid,
                        'userid' => $removeuser
                    ));
                    $this->remove_classroom_signups($classroomid, $removeuser);
                    if($localclassroom->allow_waitinglistusers==1){
                        $stable = new \stdClass();
                        $stable->search = false;
                        $stable->thead = false;
                        $stable->start = $offset;
                        $stable->length = $limit;
                        $users = $this->classroomwaitinglistusers($classroomid,$stable,$forenrollment=true);
                        $this->classroom_add_assignusers($classroomid,$users['classroomusers'], $request=0,$waitinglist=true);
                    }
                }
            }

          
            $result              = new stdClass();
            $result->changecount = $progress;
            $result->classroom   = $localclassroom->name;
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return $result;
    }
    public function classroom_manage_evaluations($classroomid, $evaluation) {
        global $DB, $USER;
        $pluginevaluationtypes = plugin_evaluationtypes();
        $params                 = array(
            'classroomid' => $classroomid,
            'evaluationid' => $evaluation->id,
            'timemodified' => time(),
            'usermodified' => $USER->id
        );
        switch ($pluginevaluationtypes[$evaluation->evaluationtype]) {
            case 'Trainer feedback':
                $return = $DB->execute('UPDATE {local_classroom_trainers} SET feedback_id = :evaluationid,
                                       timemodified = :timemodified, usermodified = :usermodified WHERE classroomid = :classroomid
                                       AND feedback_id = 0', $params);
                break;
            case 'Training feedback':
                $return = $DB->execute('UPDATE {local_classroom} SET trainingfeedbackid = :evaluationid, timemodified = :timemodified,
                                       usermodified = :usermodified WHERE id = :classroomid AND trainingfeedbackid = 0', $params);
                break;
            default:
                $return = false;
                break;
        }
        return $return;
    }
    public function manage_classroom_trainers($classroomid, $action, $trainers = array()) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/classroom/notifications_emails.php');
        // $class_emaillogs = new classroomnotifications_emails();
        $classroom_notification = new \local_classroom\notification();
        $classroominstance = $DB->get_record('local_classroom', array('id' => $classroomid));
        $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
            'classroomid' => $classroomid
        ), 'trainerid', 'id, trainerid');
        $enrolclassroom     = enrol_get_plugin('classroom');
        $classroomcourses   = $DB->get_records_menu('local_classroom_courses', array(
            'classroomid' => $classroomid
        ), 'id', 'courseid as course, courseid');
        switch ($action) {
            case 'insert':
                if (!empty($trainers)) {
                    $newtrainers = array_diff($trainers, $classroomtrainers);
                } else {
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
                        $classroomstatus     = $DB->get_field('local_classroom', 'status', array(
                            'id' => $classroomid
                        ));
                        if ($classroomstatus != 0) {
                            // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $trainer->trainerid, $fromuserid, $string);
                            $touser = \core_user::get_user($trainer->trainerid);
                            $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER, $classroominstance);
                            if (!empty($classroomcourses)) {
                                foreach ($classroomcourses as $course) {
                                    $enrolclassroomuser = $this->manage_classroom_course_enrolments($course, $newtrainer, 'editingteacher', 'enrol');
                                }
                            }
                        }
                    }
                }
                break;
            case 'update':
                break;
            case 'delete';
                if (!empty($trainers)) {
                    $toremovetrainers = array_diff($classroomtrainers, $trainers);
                } else {
                    $toremovetrainers = $classroomtrainers;
                }
                $type       = 'classroom_unenroll';
                $dataobj    = $classroomid;
                $fromuserid = $USER->id;
                $string     = 'trainer';
                if (!empty($toremovetrainers)) {
                    list($removetrainerscondition, $toremovetrainersparams) = $DB->get_in_or_equal($toremovetrainers);
                    foreach ($toremovetrainers as $toremovetrainer) {
                        $classroomstatus = $DB->get_field('local_classroom', 'status', array(
                            'id' => $classroomid
                        ));
                        if (!empty($classroomcourses)) {
                            foreach ($classroomcourses as $course) {
                                $enrolclassroomuser = $this->manage_classroom_course_enrolments($course, $toremovetrainer, 'editingteacher', 'unenrol');
                            }
                        }
                        $feedbackid              = $DB->get_field('local_classroom_trainers', 'feedback_id', array(
                            'trainerid' => $toremovetrainer,
                            'classroomid' => $classroomid
                        ));
                        $corecomponent          = new core_component();
                        $evaluationpluginexist = $corecomponent::get_plugin_directory('local', 'evaluation');
                        if (!empty($evaluationpluginexist) && $feedbackid > 0) {
                            require_once($CFG->dirroot . '/local/evaluation/lib.php');
                            evaluation_delete_instance($feedbackid);
                        }
                        $DB->execute('UPDATE {local_classroom_sessions} SET trainerid =0,timemodified = ' . time() . ',
                            usermodified = ' . $USER->id . ' WHERE classroomid = ' . $classroomid . ' AND trainerid=' . $toremovetrainer . '');
                        if ($classroomstatus != 0) {
                            // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $toremovetrainer, $fromuserid, $string);
                            $touser = \core_user::get_user($toremovetrainer);
                            $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER, $classroominstance);
                        }
                    }
                    $DB->delete_records_select('local_classroom_trainers', " classroomid = $classroomid AND trainerid $removetrainerscondition  ", $toremovetrainersparams);
                }
                break;
            case 'all':
                $this->manage_classroom_trainers($classroomid, 'insert', $trainers);
                $this->manage_classroom_trainers($classroomid, 'update', $trainers);
                $this->manage_classroom_trainers($classroomid, 'delete', $trainers);
                break;
            case 'default':
                break;
        }
        return true;
    }
    public function classroom_misc($classroom) {
        global $DB;
        if ($classroom->id > 0) {
            $systemcontext            = context_system::instance();
            $classroom->description   = $classroom->cr_description['text'];
            $classroom->classroomlogo = $classroom->classroomlogo;
            file_save_draft_area_files($classroom->classroomlogo, $systemcontext->id, 'local_classroom', 'classroomlogo', $classroom->classroomlogo);
            $DB->update_record('local_classroom', $classroom);
        }
        return $classroom->id;
    }
     public function prerequisites($classroom) {
        global $DB;
        if ($classroom->id > 0) {

            $systemcontext            = context_system::instance();
            // $classroom->prerequisites   = $classroom->prerequisites;
             if ($classroom->open_prerequisites){
                $classroom->open_prerequisites =implode(',', array_filter($classroom->open_prerequisites));
            } else {
                $classroom->open_prerequisites= implode(',', array_filter($classroom->open_prerequisites));
            }
            
            $DB->update_record('local_classroom', $classroom);
        }
        return $classroom->id;
    }
    public function target_audience($classroom) {
        global $DB;
        if ($classroom->id > 0) {
            $classroom->open_group = !empty($classroom->open_group) ? implode(',', array_filter($classroom->open_group)) : NULL;
            if(!empty($classroom->open_group)) {
                $classroom->open_group = $classroom->open_group;
            } else {
                $classroom->open_group = NULL;
            }
            $classroom->open_hrmsrole    = (!empty($classroom->open_hrmsrole)) ? implode(',', array_filter($classroom->open_hrmsrole)) : null;
            if(!empty($classroom->open_hrmsrole)) {
                $classroom->open_hrmsrole = $classroom->open_hrmsrole;
            } else {
                $classroom->open_hrmsrole = NULL;
            }
            $classroom->open_designation = (!empty($classroom->open_designation)) ? implode(',', array_filter($classroom->open_designation)) : null;
            if(!empty($classroom->open_designation)) {
                $classroom->open_designation = $classroom->open_designation;
            } else {
                $classroom->open_designation = NULL;
            }
            if (in_array(NULL, $classroom->open_location)){
                $classroom->open_location = NULL;
            } else {
                $classroom->open_location= implode(',', array_filter($classroom->open_location));
            }

            if (in_array(NULL, $classroom->open_grade)){
                $classroom->open_grade = NULL;
            } else {
                $classroom->open_grade = implode(',', array_filter($classroom->open_grade));
            }
          
            if (is_array($classroom->department)) {
                $classroom->department = !empty($classroom->department) ? implode(',', $classroom->department) : -1;
            } else {
                $classroom->department = !empty($classroom->department) ? $classroom->department : -1;
            }
          
            if (is_array($classroom->subdepartment)) {
                $classroom->subdepartment = !empty($classroom->subdepartment) ? implode(',', $classroom->subdepartment) : -1;
            } else {
                $classroom->subdepartment = !empty($classroom->subdepartment) ? $classroom->subdepartment : -1;
            }
            $DB->update_record('local_classroom', $classroom);
        }
        return $classroom->id;
    }
    public function classroom_logo($classroomlogo = 0) {
        global $DB, $OUTPUT;
        $classroomlogourl = false;
        if ($classroomlogo > 0) {
            $sql                 = "SELECT * FROM {files} WHERE itemid = :logo AND filename != '.' ORDER BY id DESC";
            $classroomlogorecord = $DB->get_record_sql($sql,array('logo' => $classroomlogo),1);
        }
        if (!empty($classroomlogorecord)) {
            if ($classroomlogorecord->filearea == "classroomlogo") {
                $classroomlogourl = moodle_url::make_pluginfile_url($classroomlogorecord->contextid, $classroomlogorecord->component,
                                        $classroomlogorecord->filearea, $classroomlogorecord->itemid, $classroomlogorecord->filepath,
                                        $classroomlogorecord->filename);
            }
        }
         if(empty($classroomlogourl)){	
            $sql = "SELECT id FROM {local_course_types} WHERE shortname = :shortname";
			$open_identifiedas = $DB->get_field_sql($sql, array('shortname' => 'ilt'));
            $coursetypeimage = $DB->get_field('local_course_types','course_image',array('id'=>$open_identifiedas));                 
			
			if(!empty($coursetypeimage) && $coursetypeimage !=0){ 
                $sql = "SELECT * FROM {files} WHERE itemid = :course_image AND component = 'local_courses' AND filearea = 'course_image' AND filename != '.' ORDER BY id DESC";
                $imgdata = $DB->get_record_sql($sql, array('course_image' => $coursetypeimage), 1);
              
                if (!empty($imgdata)) {
                    // code...
                    $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);
                } 
                $classroomlogourl = $imgurl;					
            }
		} 
         if(empty($classroomlogourl)){
            $classroomlogourl = $OUTPUT->image_url('classviewnew', 'local_classroom');
        }   
      
        return $classroomlogourl;
    }
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
            }
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
                }
                foreach ($classroomusers as $classroomuser) {
                    $unenrolclassroomuser = $this->manage_classroom_course_enrolments($course, $classroomuser, 'employee', 'enrol');
                }
            }
        }
        return true;
    }
    public function manage_classroom_course_enrolments($cousre, $user, $roleshortname = 'employee', $type = 'enrol', $pluginname = 'classroom') {
        global $DB;
        $courseexist=$DB->record_exists('enrol', array('courseid' => $cousre, 'enrol' => $pluginname));
        if($courseexist){ 
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
                } else if ($type == 'unenrol') {
                    $enrolmethod->unenrol_user($instance, $user, $roleid, time());
                }
            }
        }
        return true;
    }
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
        }
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
            }
        } catch (dml_exception $ex) {
            $classroomcoursescount = 0;
        }
        return compact('classroomcourses', 'classroomcoursescount');
    }
    public function classroom_status_action($classroomid, $classroomstatus) {
        global $DB, $USER, $CFG;
        if (file_exists($CFG->dirroot . '/local/lib.php')) {
            require_once($CFG->dirroot . '/local/lib.php');
        }
        // require_once($CFG->dirroot . '/local/classroom/notifications_emails.php');
        // $class_emaillogs = new classroomnotifications_emails();
        $classroom_notification = new \local_classroom\notification();

        switch ($classroomstatus) {
            case CLASSROOM_NEW:
                $this->update_classroom_status($classroomid, CLASSROOM_ACTIVE);
                $this->classroom_release_calendar_create_event($classroomid);
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
                $classroom = $DB->get_record('local_classroom', array(
                    'id' => $classroomid
                ));
                $this->classroom_set_events($classroom);
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
                                $touser = \core_user::get_user($classroomuser);
                                // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                                $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);

                            }
                        }
                        $i++;
                    }
                } else if (empty($classroomcourses)) {
                    foreach ($classroomusers as $classroomuser) {
                        // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                        $touser = \core_user::get_user($classroomuser);
                        $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                    }
                }
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    $touser = \core_user::get_user($classroomtrainer);
                    $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                    // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                }
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
                $this->classroom_calendar_delete_event($classroomid);
                $classroomusers   = $DB->get_records_menu('local_classroom_users', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, userid');
                $classroomcourses = $DB->get_records_menu('local_classroom_courses', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, courseid');
                $type             = 'classroom_cancel';
                $dataobj          = $classroomid;
                $fromuserid       = $USER->id;
                $localclassroom   = $DB->get_record_sql("SELECT id,status FROM {local_classroom} where id= :classroomid",array('classroomid' => $classroomid));
                $classroom = $DB->get_record('local_classroom', array('id' => $dataobj));
                if (!empty($classroomcourses)) {
                    $i = 0;
                    foreach ($classroomcourses as $classroomcourse) {
                        foreach ($classroomusers as $classroomuser) {
                            if ($i == 0 && $localclassroom->status != 0) {
                                // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                                $touser = \core_user::get_user($classroomuser);
                                $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                            }
                        }
                        $i++;
                    }
                } else if (empty($classroomcourses)) {
                    foreach ($classroomusers as $classroomuser) {
                        if ($localclassroom->status != 0) {
                            // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                            $touser = \core_user::get_user($classroomuser);
                            $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                        }
                    }
                }
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    if ($localclassroom->status != 0) {
                        // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                        $touser = \core_user::get_user($classroomtrainer);
                        $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                    }
                }
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
                $this->classroom_calendar_delete_event($classroomid);
                $classroomusers   = $DB->get_records_menu('local_classroom_users', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, userid');
                $classroomcourses = $DB->get_records_menu('local_classroom_courses', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, courseid');
                $type             = 'classroom_hold';
                $dataobj          = $classroomid;
                $fromuserid       = $USER->id;
                $localclassroom   = $DB->get_record_sql("SELECT id,status FROM {local_classroom} where id = :classroomid ",array('classroomid' => $classroomid));
                $classroom = $DB->get_record('local_classroom', array('id' => $classroomid));
                if (!empty($classroomcourses)) {
                    $i = 0;
                    foreach ($classroomcourses as $classroomcourse) {
                        foreach ($classroomusers as $classroomuser) {
                            $this->manage_classroom_course_enrolments($classroomcourse, $classroomuser, 'employee', 'unenrol');
                            if ($i == 0 && $localclassroom->status != 0) {
                                // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                                $touser = \core_user::get_user($classroomuser);
                                $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                            }
                        }
                        $i++;
                    }
                } else if (empty($classroomcourses)) {
                    foreach ($classroomusers as $classroomuser) {
                        if ($localclassroom->status != 0) {
                            // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                            $touser = \core_user::get_user($classroomuser);
                            $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                        }
                    }
                }
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    if ($localclassroom->status != 0) {
                        // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                        $touser = \core_user::get_user($classroomtrainer);
                        $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                    }
                }
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
                $type           = 'classroom_complete';
                $dataobj        = $classroomid;
                $fromuserid     = $USER->id;
                $localclassroom = $DB->get_record_sql("SELECT id,status FROM {local_classroom} where id= :classroomid",array('classroomid' => $classroomid));
                $classroom = $DB->get_record('local_classroom', array('id' => $classroomid));
                foreach ($classroomusers as $classroomuser) {
                    if ($localclassroom->status != 0) {
                        // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomuser, $fromuserid);
                        $touser = \core_user::get_user($classroomuser);
                        $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                    }
                }
                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers', array(
                    'classroomid' => $classroomid
                ), 'id', 'id, trainerid');
                foreach ($classroomtrainers as $classroomtrainer) {
                    if ($localclassroom->status != 0) {                        
                        // $class_emaillogs = new classroomnotifications_emails();
                        // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $classroomtrainer, $fromuserid);
                        $touser = \core_user::get_user($classroomtrainer);
                        $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER ,$classroom);
                    }
                }
                break;
        }
        return true;
    }
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
        }
        $classroom->usermodified   = $USER->id;
        $classroom->timemodified   = time();
        $classroom->completiondate = time();
        try {
            $DB->update_record('local_classroom', $classroom);
        } catch (dml_exception $ex) {
            print_error($ex);
        }
        return true;
    }
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
        }
        $countsql = "SELECT COUNT(cu.id) ";
        $fromsql  = "SELECT u.*, cu.attended_sessions, cu.hours, cu.completion_status, c.totalsessions, c.activesessions";
        $sql      = " FROM {user} AS u
                 JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                 JOIN {local_classroom} AS c ON c.id = cu.classroomid
                WHERE c.id = :classroomid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        $params['classroomid'] = $classroomid;
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin()
            && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $condition            = " AND (u.open_costcenterid = :costcenter)";
            $params['costcenter'] = $USER->open_costcenterid;
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())
                 || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $condition .= " AND (u.open_departmentid = :department )";
                $params['department'] = $USER->open_departmentid;
            }
            if (has_capability('local/classroom:trainer_viewclassroom', context_system::instance())) {
                 $condition="";
            }
            $sql .= $condition;
        }
        try {
            $classroomuserscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY id ASC";
                $classroomusers = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
            }
        } catch (dml_exception $ex) {
            $classroomuserscount = 0;
        }
        return compact('classroomusers', 'classroomuserscount');
    }
    public function get_specific_costcenter_requests_classroom($component,$sorting,$componentid,$stable) {
        global $USER, $DB;
        $systemcontext = context_system::instance();
        $fields = " req.id, req.createdbyid, req.compname, req.compcode, req.compkey, req.componentid, req.status, req.responder, req.respondeddate, req.usermodified, req.timecreated, req.timemodified ";
        $params = array();
        if(has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || is_siteadmin()){
           $selectsql = "SELECT $fields ";
           $countsql = "SELECT count(req.id) ";
           $sql = " FROM {local_request_records} AS req WHERE 1=1";
        }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
            $costcenterid=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id));
            if($costcenterid){
                $selectsql = "SELECT $fields ";
                $countsql = "SELECT count(req.id) ";
                $sql = " FROM {local_request_records} AS req 
                    JOIN {user} AS u ON u.id=req.createdbyid 
                    WHERE u.open_costcenterid= :costcenterid ";
                $params['costcenterid'] = $costcenterid;
            }

        }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
            $selectsql = "SELECT $fields ";
            $countsql = "SELECT count(req.id) ";
            $sql = " FROM {local_request_records} AS req 
                JOIN {user} AS u ON u.id=req.createdbyid 
                WHERE u.open_costcenterid= :costcenterid AND u.open_departmentid = :departmentid ";
                $params['costcenterid'] = $USER->open_costcenterid;
                $params['departmentid'] = $USER->open_departmentid;
        }else if(has_capability('local/classroom:manageclassroom',$systemcontext)||
                has_capability('local/program:manageprogram',$systemcontext)||
                has_capability('local/classroom:manageclassroom',$systemcontext)){
            $trainerclassrooms = $DB->get_records_menu('local_classroom_trainers',array('trainerid' => $USER->id),'','id,classroomid');
            array_push($trainerclassrooms,0);
            $classroomids = implode(',', $trainerclassrooms);

            $trainerprograms = $DB->get_records_menu('local_program_trainers',array('trainerid' => $USER->id),'','id,programid');
            array_push($trainerprograms,0);
            $programids = implode(',', $trainerprograms);

            $trainerclassrooms = $DB->get_records_menu('local_classroom_trainers',array('trainerid' => $USER->id),'','id,classroomid');
            array_push($trainerclassrooms,0);
            $classroomids = implode(',', $trainerclassrooms);
            $selectsql = "SELECT $fields ";
            $countsql = "SELECT count(req.id) ";
            $sql = " FROM {local_request_records} AS req
                JOIN {user} AS u ON u.id=req.createdbyid
                WHERE ((req.compname='classroom' AND req.componentid IN($classroomids)) OR
                (req.compname='program' AND req.componentid IN($programids)) OR
                (req.compname='classroom' AND req.componentid IN($programids))) AND req.compname!='elearning'";
        }
        if($sql){
          if($component){
            if(is_array($component)){
              $listid =  "'".implode("','", $component)."'";
              
            }else{
              $listid = ''.$component.'';
            }
            $sql .=" and req.compname = :compname ";
            $params['compname'] = $listid;
          }
          if($componentid){
            $sql .=" and req.componentid = :componentid ";
            $params['componentid'] = $componentid;
          }
          $requestscount = $DB->count_records_sql($countsql.$sql,$params);
          $requestlist = $DB->get_records_sql($selectsql.$sql,$params,$stable->start,$stable->length);
          return array('requestscount' =>$requestscount, 'requestlist' => $requestlist);
        }
    }
    public function classroomrequestedusers($list=null, $component=null,$sorting=false,$tab=false,$componentid=false, $stable) {
        global $DB, $USER;
        $systemcontext = context_system::instance();
        
        if(has_capability('local/request:viewrecord',$systemcontext) && !has_capability('local/request:approverecord',$systemcontext)){                                       
            $requestscount = $DB->count_records('local_request_records', array('createdbyid' =>$USER->id));
            $requestlist = $DB->get_records('local_request_records', array('createdbyid' =>$USER->id),$stable->start,$stable->length);
      
        }
        else{
            if(is_siteadmin() || (has_capability('local/request:viewrecord',$systemcontext) && has_capability('local/request:approverecord',$systemcontext))){
                $requestdata = $this->get_specific_costcenter_requests_classroom($component,$sorting,$componentid,$stable);
                $requestlist = $requestdata['requestlist'];
                $requestscount = $requestdata['requestscount'];
            }
        }
        return compact('requestlist', 'requestscount');
    }


    public function requestsdata($requestlist){
      global $DB, $USER;

        $data = array();
        foreach ($requestlist as  $request) {
            $onerow = array();
            $onerow['status']=  $request->status;

            if($request->status =='APPROVED'){
              $onerow['approvestatus'] =1; 
            }
            else{
               $onerow['approvestatus'] =0;
            }

            if($request->status =='REJECTED'){
              $onerow['rejectstatus'] =1; 
            }
            else{
               $onerow['rejectstatus'] =0; 
            }

            $onerow['compname'] = get_string($request->compname,'local_request');
            $onerow['requestedby'] = $DB->get_field('user','firstname',array('id' => $request->createdbyid,'deleted' => 0,'suspended' => 0));
            $onerow['requesteddate'] = date("j M 'y",$request->timecreated); 
            $onerow['componentid'] = $request->componentid; 
            $onerow['id'] = $request->id;
            if($request->createdbyid){
              $user =$DB->get_record('user', array('id'=>$request->createdbyid));
              $onerow['requesteduser'] = fullname($user); 
            }
            if($request->responder){
                $responderinfo=$DB->get_record('user', array('id'=>$request->responder));
                $name = $responderinfo->firstname.' '.$responderinfo->lastname;
            }
            else{
                $name = "---------";
            }
            $onerow['responder'] = $name;
            if($request->respondeddate){
              $onerow['respondeddate'] = date("j M 'y",$request->respondeddate); 
            }
            else
              $onerow['respondeddate']='-------';

            $componentname='-------';
            $componentid = $request->componentid;
            $component = $request->compname;
            if($componentid){

                switch($component){     
                    case 'classroom' :
                        $componentname = $DB->get_field('local_classroom', 'name', array('id'=>$componentid));                                             
                    break;

                    case 'program' :    
                        $componentname = $DB->get_field('local_program', 'name', array('id'=>$componentid));                                             
                    break;

                    case 'learningplan' : 
                        $componentname = $DB->get_field('local_learningplan', 'name', array('id'=>$componentid));                                             
                    break;


                    case 'elearning' :   
                        $componentname = $DB->get_field('course', 'fullname', array('id'=>$componentid));                                             
                    break;

                    case 'certification' :   
                        $componentname = $DB->get_field('local_certification', 'name', array('id'=>$componentid));                                             
                    break;
                                      
                } // end of switch statement
            }
            $onerow['componentname'] = $componentname;
            $onerow['capability'] = $this->get_capabilitycheck_list();
           $data[]= $onerow;
        } // end of foreach
        return $data;


    } // return data function 


    public  function get_capabilitycheck_list(){
        global $USER;
        $systemcontext = context_system::instance();
        $viewrecord_capability=0;
            if(has_capability('local/request:viewrecord',$systemcontext)){
              $viewrecord_capability=1;
            }    

              
            $approve_capability=0;
            if(has_capability('local/request:approverecord',$systemcontext)){
              $approve_capability=1;
            }
            
            $deny_capability=0;
            if(has_capability('local/request:denyrecord',$systemcontext)){
              $deny_capability=1;
            }
          
            $addrecord_capability=0;
            if(has_capability('local/request:addrecord',$systemcontext)){
              $addrecord_capability=1;
            }

            $deleterecord_capability=0;
            if(has_capability('local/request:deleterecord',$systemcontext)){
              $deleterecord_capability=1;
            }
           
            $addcomment_capability=0;
            if(has_capability('local/request:addcomment',$systemcontext)){
              $addcomment_capability=1;
            }

        return $list= array('viewrecord_capability'=>$viewrecord_capability,
                            'approve_capability'=> $approve_capability, 
                            'deny_capability'=>  $deny_capability,
                            'addrecord_capability'=>$addrecord_capability, 
                            'deleterecord_capability'=>$deleterecord_capability,
                             'addcomment_capability' =>$addcomment_capability ); 
    } // end of function

    public function classroom_completions($classroomid) {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');
        $classroomuserssql        = "SELECT cu.*
                                FROM {user} AS u
                                JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                                WHERE u.id > 2 AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND cu.classroomid = :classroomid";
        $classroomusers           = $DB->get_records_sql($classroomuserssql,array('classroomid' => $classroomid));
        $classroomcompletiondata = $DB->get_record('local_classroom_completion', array(
            'classroomid' => $classroomid
        ));
        $totalsessionssql         = "SELECT count(id) as total
                                        FROM {local_classroom_sessions}
                                        WHERE classroomid = :classroomid";
        $sqlparams = array();
        $sqlparams['classroomid'] =  $classroomid;

        if (!empty($classroomcompletiondata) && $classroomcompletiondata->sessiontracking == "OR" && $classroomcompletiondata->sessionids != null) {
            $sessionidsda = explode(',',$classroomcompletiondata->sessionids);
            list($relatedsessionidsql, $relatedsessionidparams) = $DB->get_in_or_equal($sessionidsda, SQL_PARAMS_NAMED, 'sessionids');
            $sqlparams = array_merge($sqlparams,$relatedsessionidparams);
            $totalsessionssql .= " AND id $relatedsessionidsql";
        }
        $totalsessions       = $DB->count_records_sql($totalsessionssql,$sqlparams);

        $params = array();
        $params['classroomid'] = $classroomid;
        $classroomcoursessql = "SELECT c.*
                                  FROM {course} AS c
                       /*           JOIN {enrol} AS en on en.courseid=c.id and en.enrol='classroom' and en.status=0 */
                                  JOIN {local_classroom_courses} AS cc ON cc.courseid = c.id
                                 WHERE cc.classroomid = :classroomid";
        if (!empty($classroomcompletiondata) && $classroomcompletiondata->coursetracking == "OR" && $classroomcompletiondata->courseids != null) {

            $courseidsda = explode(',',$classroomcompletiondata->courseids);
            list($relatedcourseidsql, $relatedcourseidparams) = $DB->get_in_or_equal($courseidsda, SQL_PARAMS_NAMED, 'courseids');
            $params = array_merge($params,$relatedcourseidparams);
            $classroomcoursessql .= " AND cc.courseid $relatedcourseidsql";
        }
        $classroomcourses = $DB->get_records_sql($classroomcoursessql,$params);
        if (!empty($classroomusers)) {
            foreach ($classroomusers as $classroomuser) {
                $usercousrecompletionstatus = array();
                foreach ($classroomcourses as $classroomcourse) {
                    $params                 = array(
                        'userid' => $classroomuser->userid,
                        'course' => $classroomcourse->id
                    );
                    $ccompletion            = new completion_completion($params);
                    $ccompletioniscomplete = $ccompletion->is_complete();
                    if ($ccompletioniscomplete) {
                        $usercousrecompletionstatus[] = true;
                    }
                }
                if (empty($classroomcompletiondata) || ($classroomcompletiondata->sessiontracking == null && $classroomcompletiondata->coursetracking == null)) {
                    if (($classroomuser->attended_sessions == $totalsessions) && (count($usercousrecompletionstatus) == count($classroomcourses))) {
                        $classroomuser->completion_status = 1;
                    } else {
                        $classroomuser->completion_status = 0;
                    }
                } else {
                    $classroomuser->completion_status = 0;
                    $attendedsessionssql            = "SELECT count(id) as total FROM {local_classroom_attendance} where
                    classroomid= :classroomid and userid= :userid and status=1 ";
                    $params = array();
                    $params['classroomid'] = $classroomid;
                    $params['userid'] = $classroomuser->userid;

                    if (!empty($classroomcompletiondata) && $classroomcompletiondata->sessiontracking == "OR" &&
                        $classroomcompletiondata->sessionids != null) {

                        $sessionidsda = explode(',',$classroomcompletiondata->sessionids);
                        list($relatedsessionidsql, $relatedsessionidparams) = $DB->get_in_or_equal($sessionidsda, SQL_PARAMS_NAMED, 'sessionids');
                        $params = array_merge($params,$relatedsessionidparams);
                        $attendedsessionssql .= " AND sessionid $relatedsessionidsql";
                    }
                    $attendedsessions = $DB->count_records_sql($attendedsessionssql,$params);
                    if (($attendedsessions == $totalsessions && $classroomcompletiondata->sessiontracking == "AND")) {
                        $classroomuser->completion_status = 1;
                    }
                    if (($attendedsessions <= $totalsessions && $attendedsessions != 0 && $classroomcompletiondata->sessiontracking == "OR")) {
                        $classroomuser->completion_status = 1;
                    }
                    if (count($usercousrecompletionstatus) == count($classroomcourses) && $classroomcompletiondata->coursetracking == "AND") {
                        if (($attendedsessions == $totalsessions && $classroomcompletiondata->sessiontracking == "AND")) {
                            $classroomuser->completion_status = 1;
                        }
                        if (($attendedsessions <= $totalsessions && $attendedsessions != 0 && $classroomcompletiondata->sessiontracking == "OR")) {
                            $classroomuser->completion_status = 1;
                        }
                        if ($classroomcompletiondata->sessiontracking == null) {
                            $classroomuser->completion_status = 1;
                        }
                    } else if ($classroomcompletiondata->coursetracking == "AND") {
                        $classroomuser->completion_status = 0;
                    }
                    if (count($usercousrecompletionstatus) <= count($classroomcourses) && count($usercousrecompletionstatus) != 0
                        && $classroomcompletiondata->coursetracking == "OR") {
                        if (($attendedsessions == $totalsessions && $classroomcompletiondata->sessiontracking == "AND")) {
                            $classroomuser->completion_status = 1;
                        }
                        if (($attendedsessions <= $totalsessions && $attendedsessions != 0 && $classroomcompletiondata->sessiontracking == "OR")) {
                            $classroomuser->completion_status = 1;
                        }
                        if ($classroomcompletiondata->sessiontracking == null) {
                            $classroomuser->completion_status = 1;
                        }
                    } else if ($classroomcompletiondata->coursetracking == "OR") {
                        $classroomuser->completion_status = 0;
                    }
                }
                $classroomuser->usermodified   = $USER->id;
                $classroomuser->timemodified   = time();
                $classroomuser->completiondate = time();
                $DB->update_record('local_classroom_users', $classroomuser);
                if($classroomuser->completion_status){
                    $params = array(
                        'context' => \context_system::instance(),
                        'objectid' => $classroomid,
                        'courseid' => 1,
                        'userid' => $classroomuser->userid,
                        'relateduserid' => $classroomuser->userid,
                    );
                    // $event  = \local_classroom\event\classroom_users_updated::create($params);
                    $event = \local_classroom\event\classroom_user_completed::create($params);
                    $event->add_record_snapshot('local_classroom', $classroomid);
                    $event->trigger();
                }
            }
        }
        return true;
    }
    public function classroomcategories($formdata) {
        global $DB;
        if ($formdata->id) {
            $DB->update_record('local_classroom_categories', $formdata);
        } else {
            $DB->insert_record('local_classroom_categories', $formdata);
        }
    }
    public function select_to_and_from_users($type = null, $clasroomid = 0, $params, $total = 0, $offset1 = -1, $perpage = -1, $lastitem = 0) {
        global $DB, $USER;
        $classroom           = $DB->get_record('local_classroom', array(
            'id' => $clasroomid
        ));
        $params['suspended'] = 0;
        $params['deleted']   = 0;
        if ($total == 0) {
            $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname";
        } else {
            $sql = "SELECT count(u.id) as total";
        }
        $sql .= " FROM {user} AS u
                                WHERE  u.id > 2 AND u.suspended = :suspended
                                     AND u.deleted = :deleted ";
        if ($lastitem != 0) {
            $sql .= " AND u.id > $lastitem";
        }
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin() &&
                        (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                        && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $sql .= " AND u.open_costcenterid = :costcenter";
            $params['costcenter'] = $USER->open_costcenterid;
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance()) ||
                 has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $sql .= " AND u.open_departmentid = :department";
                $params['department'] = $USER->open_departmentid;
            }
        }
        $sql .= " AND u.id <> $USER->id ";


        if (!empty($params['email'])) {

            $emails = explode(',',$params['email']);
            list($relatedemailsql, $relatedemailparams) = $DB->get_in_or_equal($emails, SQL_PARAMS_NAMED, 'email');
            $params = array_merge($params,$relatedemailparams);            
            $sql .= " AND u.id $relatedemailsql";
        }
        if (!empty($params['uname'])) {

            $unames = explode(',',$params['uname']);
            list($relatedunamesql, $relatedunameparams) = $DB->get_in_or_equal($unames, SQL_PARAMS_NAMED, 'uname');
            $params = array_merge($params,$relatedunameparams);            
            $sql .= " AND u.id $relatedunamesql";
        }
        if (!empty($params['department'])) {

            $departments = explode(',',$params['department']);
            list($relateddepartmentsql, $relateddepparams) = $DB->get_in_or_equal($departments, SQL_PARAMS_NAMED, 'department');
            $params = array_merge($params,$relateddepparams);            
            $sql .= " AND u.open_departmentid $relateddepartmentsql";
        }
        if (!empty($params['organization'])) {

            $organizations = explode(',',$params['organization']);
            list($relatedorgsql, $relatedorgparams) = $DB->get_in_or_equal($organizations, SQL_PARAMS_NAMED, 'organization');
            $params = array_merge($params,$relatedorgparams);            
            $sql .= " AND u.open_costcenterid $relatedorgsql";
        }
        if (!empty($params['idnumber'])) {

            $idnumbers = explode(',',$params['idnumber']);
            list($relatedidnumbersql, $relatedidnumberparams) = $DB->get_in_or_equal($idnumbers, SQL_PARAMS_NAMED, 'idnumber');
            $params = array_merge($params,$relatedidnumberparams);            
            $sql .= " AND u.id $relatedidnumbersql";
        }
        if (!empty($params['groups'])) {
            $sql .= " AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0
            AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
        }
        if ($type == 'add') {
            $sql .= " AND u.id NOT IN (SELECT lcu.userid as userid
                                       FROM {local_classroom_users} AS lcu
                                       WHERE lcu.classroomid = $clasroomid)";
        } else if ($type == 'remove') {
            $sql .= " AND u.id IN (SELECT lcu.userid as userid
                                       FROM {local_classroom_users} AS lcu
                                       WHERE lcu.classroomid = $clasroomid)";
        }
        $sql .= " AND u.id NOT IN (SELECT lcu.trainerid as userid
                                       FROM {local_classroom_trainers} AS lcu
                                       WHERE lcu.classroomid = $clasroomid)";
        $order = ' ORDER BY u.id ASC ';
        // if ($perpage != -1) {
        //     $order .= "LIMIT $perpage";
        // }
        if ($total == 0) {
            $availableusers = $DB->get_records_sql_menu($sql . $order, $params);
        } else {
            $availableusers = $DB->count_records_sql($sql, $params);
        }
        return $availableusers;
    }
    public function classroom_self_enrolment($classroomid, $classroomuser, $request=false,$enroltype) {
        global $DB;
        $return=-2;
       
        $classroom = $DB->get_record_select('local_classroom', 'id = :id', array('id' => $classroomid),'id,capacity,allow_waitinglistusers');

        $classroomcapacitycheck = $this->classroom_capacity_check($classroomid,$checking=true);
        if (!$classroomcapacitycheck) {
            $this->classroom_add_assignusers($classroomid, array(
                $classroomuser
            ), 0);
        }
        elseif($classroom->allow_waitinglistusers==1){
           $return=$this->classroom_add_waitingusers($classroomid, array(
               $classroomuser
           ),$enroltype);
        }else{
            $return=-1;
        }
       return $return; 
    }
    public function classroom_capacity_check($classroomid,$checking=false) {
        global $DB;
        $return             = false;
        // $classroomcapacity = $DB->get_field('local_classroom', 'capacity', array(
        //     'id' => $classroomid
        // ));
        $classroom = $DB->get_record_select('local_classroom', 'id = :id', array('id' => $classroomid),'id,capacity,allow_waitinglistusers');
        
        $countfields = "SELECT COUNT(DISTINCT u.id) ";
        $params['classroomid'] = $classroomid;
        $params['confirmed'] = 1;
        $params['suspended'] = 0;
        $params['deleted'] = 0;
        $sql = " FROM {user} AS u
                JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                 WHERE u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                    AND u.deleted = :deleted AND cu.classroomid = :classroomid";
        $enrolledusers     =$DB->count_records_sql($countfields . $sql, $params);

        //$enrolledusers     = $DB->count_records('local_classroom_users', array(
        //    'classroomid' => $classroomid
        //));
   
        if($classroom->capacity <= $enrolledusers && !empty($classroom->capacity) && $classroom->capacity != 0) {
            $return = true;
        }
   
        return $return;
    }
    public function manage_classroom_automatic_sessions($classroomid, $classroomstartdate, $classroomenddate) {
        global $DB;
        $i                     = 1;
        $starthoursminuates  = "10:00:00";
        $finishhoursminuates = "13:00:00";
        $firsttime            = date("H:i:s", $classroomstartdate);
        if ($firsttime >= $finishhoursminuates) {
            $classroomstartdate = strtotime('+1 day', strtotime(date("Y-m-d", $classroomstartdate)));
            $classroomstartdate = strtotime(date('Y-m-d', $classroomstartdate) . ' ' . $starthoursminuates);
        }
        $lasttime = date("H:i:s", $classroomenddate);
        if ($lasttime < $starthoursminuates) {
            $classroomenddate = strtotime('-1 day', strtotime(date("Y-m-d", $classroomenddate)));
            $classroomenddate = strtotime(date('Y-m-d', $classroomenddate) . ' ' . $finishhoursminuates);
        }
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
            $session->timestart      = strtotime($date . ' ' . $starthoursminuates);
            $session->timefinish     = strtotime($date . ' ' . $finishhoursminuates);
            if ($first == $last) {
                $session->timefinish = strtotime($date . ' ' . date("H:i:s", $classroomenddate));
            }
            $condition = strtotime('+1 day', $first);
            if ($i == 1) {
                $session->timestart = strtotime($date . ' ' . date("H:i:s", $classroomstartdate));
            } else if ($condition > $last) {
                $session->timefinish = strtotime($date . ' ' . date("H:i:s", $classroomenddate));
            }
            
            $this->manage_classroom_sessions_induction_technical($session);
            $first = strtotime('+1 day', $first);
            $i++;
        }
    }
    public function manage_classroom_induction_automatic_sessions($classroomid, $classroomstartdate, $classroomenddate) {
        global $DB;
        $i = 1;
        $timiningarray = array(
            array('starthoursminuates' => '10:00:00', 'finishhoursminuates' => '11:00:00'),
            array('starthoursminuates' => '11:00:00', 'finishhoursminuates' => '12:00:00'),
            array('starthoursminuates' => '12:00:00', 'finishhoursminuates' => '13:00:00'),
            array('starthoursminuates' => '14:00:00', 'finishhoursminuates' => '16:00:00'),
            array('starthoursminuates' => '16:00:00', 'finishhoursminuates' => '18:00:00')
        );
       //$startDate = date("w", $classroomstartdate);
       //$endDate =  date("w", $classroomenddate);


        foreach ($timiningarray as  $timinings) {

            $firsttime            = date("H:i:s", $classroomstartdate);
            if ($firsttime >= $timinings['finishhoursminuates']) {
                $classroomstartdate = strtotime('+1 day', strtotime(date("Y-m-d", $classroomstartdate)));
                $classroomstartdate = strtotime(date('Y-m-d', $classroomstartdate) . ' ' . $timinings['starthoursminuates']);
            }
            $lasttime = date("H:i:s", $classroomenddate);
            if ($lasttime < $timinings['starthoursminuates']) {
                $classroomenddate = strtotime('-1 day', strtotime(date("Y-m-d", $classroomenddate)));
                $classroomenddate = strtotime(date('Y-m-d', $classroomenddate) . ' ' . $timinings['finishhoursminuates']);
            }
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
                    $session->timestart      = strtotime($date . ' ' . $timinings['starthoursminuates']);
                    $session->timefinish     = strtotime($date . ' ' . $timinings['finishhoursminuates']);
                    if ($first == $last) {
                        $session->timefinish = strtotime($date . ' ' . date("H:i:s", $classroomenddate));
                    }
                    $condition = strtotime('+1 day', $first);
                    if ($i == 1) {
                        $session->timestart = strtotime($date . ' ' . date("H:i:s", $classroomstartdate));
                    } else if ($condition > $last) {
                        $session->timefinish = strtotime($date . ' ' . date("H:i:s", $classroomenddate));
                    }
                    
                    $this->manage_classroom_sessions_induction_technical($session);
                    $first = strtotime('+1 day', $first);

                    $i++;
               }
              
       }

     }
    public function enrol_get_users_classrooms_count($userid) {
        global $DB;
        $classroomsql   = "SELECT count(id) FROM {local_classroom_users} WHERE userid = :userid";
        $classroomcount = $DB->count_records_sql($classroomsql, array(
            'userid' => $userid
        ));
        return $classroomcount;
    }
    public function enrol_get_users_classroom($userid,$limityesorno = false,$start =0,$limit=5) {
        global $DB;
        $countsql = "SELECT distinct(count(lc.id))";
        $selectsql = "SELECT lc.id,lc.name,lc.description";

        $classroomsql = " FROM {local_classroom} AS lc 
        JOIN {local_classroom_users} AS lcu ON lcu.classroomid = lc.id WHERE userid = :userid AND lc.status IN (1,4)";

        

        if($limityesorno){
            $classrooms    = $DB->get_records_sql($selectsql.$classroomsql, array(
                'userid' => $userid
            ),$start,$limit);
        }else{
            $classrooms    = $DB->get_records_sql($selectsql.$classroomsql, array(
                'userid' => $userid
            ));
        }
        $classroomscount    = $DB->count_records_sql($countsql.$classroomsql, array(
            'userid' => $userid
        ));
        return array('count' => $classroomscount,'data' => $classrooms);
    }
    public function classroom_status_strip($classroomid, $classroomstatus) {
        global $DB, $USER;
        $return = "";
        $id     = $DB->get_field('local_classroom_users', 'id', array(
            'classroomid' => $classroomid,
            'userid' => $USER->id
        ));
       /*  if (!$id && !is_siteadmin() && (!has_capability('local/classroom:manageclassroom', context_system::instance()))) {
            return $return;
        } */
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
                    $completionstatus = $DB->get_field('local_classroom_users', 'completion_status', array(
                        'classroomid' => $classroomid,
                        'userid' => $USER->id
                    ));
                    $return            = $completionstatus == 1 ? get_string('completed_classroom', 'local_classroom') : get_string('completed_user_classroom', 'local_classroom');
                }
                break;
        }
        return $return;
    }

    public function classroom_completion_settings_tab($classroomid) {
        global $DB, $USER;
        $classroomcompletiondata = $DB->get_record('local_classroom_completion', array(
            'classroomid' => $classroomid
        ));
        $sessionssql = "SELECT id,name FROM {local_classroom_sessions}
                                            WHERE classroomid = $classroomid ";
        if (!empty($classroomcompletiondata) && $classroomcompletiondata->sessiontracking == "OR" && $classroomcompletiondata->sessionids != null) {
            $sessionssql .= " AND id in ($classroomcompletiondata->sessionids)";
        }
        $sessions            = $DB->get_records_sql_menu($sessionssql);
        $classroomcoursessql = "SELECT c.id,fullname
                                  FROM {course} AS c
                             /*     JOIN {enrol} AS en on en.courseid=c.id and en.enrol='classroom' and en.status=0 */
                                  JOIN {local_classroom_courses} AS cc ON cc.courseid = c.id
                                 WHERE cc.classroomid = :classroomid";
        $params = array();
        $params['classroomid'] = $classroomid;


        if (!empty($classroomcompletiondata) && $classroomcompletiondata->coursetracking == "OR" && $classroomcompletiondata->courseids != null) {
            $courseidsda = explode(',',$classroomcompletiondata->courseids);
            list($relatedcourseidsql, $relatedcourseidparams) = $DB->get_in_or_equal($courseidsda, SQL_PARAMS_NAMED, 'courseids');
            $params = array_merge($params,$relatedcourseidparams);            
            $classroomcoursessql .= " AND cc.courseid $relatedcourseidsql";
        }
        $classroomcourses = $DB->get_records_sql_menu($classroomcoursessql,$params);
        $return           = "";
        $data = array();
        
        $courses = '';
        $session = '';

        if (!empty($sessions) || !empty($classroomcourses)) {
            $list = array();
            
            if (!empty($classroomcourses)) {
                $courses = implode(', ', $classroomcourses);
            } 

            if (!empty($sessions)) {
                $session = implode(', ', $sessions);
            }
        }

        $list['classroomid'] = $classroomid;

        $list['sessions'] = $session;

        $list['courses'] = $courses;
        
        if (empty($classroomcompletiondata) || ($classroomcompletiondata->sessiontracking == null && $classroomcompletiondata->coursetracking == null)) {
            $sessiontracking = $coursetracking = "";
            if (!empty($sessions)) {
                $sessiontracking = "_allsessions";
            }
            if (!empty($classroomcourses)) {
                $coursetracking = "_allcourses";
            }
        } else {
            $sessiontracking = $coursetracking = "";
            if ($classroomcompletiondata->sessiontracking == "AND" && !empty($sessions)) {
                $sessiontracking = "_allsessions";
            }
            if ($classroomcompletiondata->sessiontracking == "OR" && !empty($sessions)) {
                $sessiontracking = "_anysessions";
            }
            if ($classroomcompletiondata->coursetracking == "AND" && !empty($classroomcourses)) {
                $coursetracking = "_allcourses";
            }
            if ($classroomcompletiondata->coursetracking == "OR" && !empty($classroomcourses)) {
                $coursetracking = "_anycourses";
            }
        }
        
        $list['tracking'] = get_string('classroom_completion_tab_info' . $sessiontracking . $coursetracking . '', 'local_classroom');
        $data[] = $list;

        return $data;
    }

    public function classroomtarget_audience_tab($classroomid) {
        global $DB, $USER;
        $array = array();

        $list = array();

        $data = $DB->get_record_sql('SELECT id, open_group, open_hrmsrole,
             open_designation, open_location, open_grade, department,subdepartment
             FROM {local_classroom} WHERE id = :classroomid',array('classroomid' => $classroomid));
        if ($data->department == -1 || $data->department == null) {
            $department = 'All';
        } else {
            $departments = $DB->get_fieldset_sql("SELECT fullname FROM {local_costcenter} WHERE id IN ($data->department) ");
            $department  = implode(',', $departments);
        }
        if ($data->subdepartment == -1 || $data->subdepartment == null) {
            $subdepartment = 'All';
        } else {
            $subdepartments = $DB->get_fieldset_sql("SELECT fullname  FROM {local_costcenter} WHERE id IN ($data->subdepartment)");
            $subdepartment  = implode(',', $subdepartments);
        }
        
        
        $list['department'] = $department;
        $list['subdepartment'] = $subdepartment;

        if (empty($data->open_group)) {
            $group = 'All';
        } else {
            $groups = $DB->get_fieldset_sql("SELECT name FROM {cohort} WHERE id IN ($data->open_group)");
            $group  = implode(',', $groups);
        }

        $list['group'] = $group;

        $list['hrmsrole'] = (!empty($data->open_hrmsrole)) ? $hrmsrole = $data->open_hrmsrole : $hrmsrole = 'All';

        $list['designation'] = (!empty($data->open_designation)) ? /*$designation =*/ $data->open_designation : $designation = 'All';

        $list['location'] = (!empty($data->open_location)) ? /*$location =*/ $data->open_location : $location = 'All';

        $list['grade'] = (!empty($data->open_grade)) ? $data->open_grade : $grade = 'All';

        $array[] = $list;
        return $array;
    }
    public function classroomwaitinglistusers($classroomid, $stable,$forenrollment=false) {
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
        }
        $countsql = "SELECT COUNT(DISTINCT(cu.userid)) ";
        if($forenrollment==true){
            $fromsql  = "SELECT cu.id,u.id as userid";
        }else{
            $fromsql  = "SELECT u.*, cu.sortorder, cu.timecreated,cu.enroltype";
        }
        
        $sql      = " FROM {user} AS u
                 JOIN {local_classroom_waitlist} AS cu ON cu.userid = u.id
                 JOIN {local_classroom} AS c ON c.id = cu.classroomid
                WHERE c.id = :classroomid AND cu.enrolstatus=0 AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
        $sql .= $concatsql;
        $params['classroomid'] = $classroomid;
        if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && (!is_siteadmin()
            && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance())
                && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
            $condition            = " AND (u.open_costcenterid = :costcenter)";
            $params['costcenter'] = $USER->open_costcenterid;
            if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())
                 || has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                $condition .= " AND (u.open_departmentid = :department )";
                $params['department'] = $USER->open_departmentid;
            }
            if (has_capability('local/classroom:trainer_viewclassroom', context_system::instance())) {
                 $condition="";
            }
            $sql .= $condition;
        }
        try {
            $classroomuserscount = $DB->count_records_sql($countsql . $sql, $params);
            if ($stable->thead == false) {
                $sql .= " ORDER BY cu.sortorder ASC";
                if($forenrollment==true){
                    $classroomusers = $DB->get_records_sql_menu($fromsql . $sql, $params);
                }else{
                    $classroomusers = $DB->get_records_sql($fromsql . $sql, $params, $stable->start, $stable->length);
                }
            }
        } catch (dml_exception $ex) {
            $classroomuserscount = 0;
        }
        return compact('classroomusers', 'classroomuserscount');
    }

    public function delete_suspend_user_remove_classrooms($userid){
        global $DB, $USER, $OUTPUT, $PAGE;
        $PAGE->set_context(1);
         $sql="SELECT c.id ,c.name FROM {local_classroom} AS c 
                        JOIN {local_classroom_users} AS cu ON cu.classroomid = c.id
                                  WHERE cu.userid = :classroomuserid and c.status <> :status AND c.startdate > :startdate ";

         $params=array('classroomuserid' =>$userid,'status'=>4,'startdate'=>time()); 
        $classrooms = $DB->get_records_sql($sql, $params);
      
        foreach ($classrooms as $key => $classroom) {
            $this->classroom_remove_assignusers($classroom->id, array($userid), $request=1);
        }                    
    }

    public function enrol_status($enrol, $classroom, $userid = 0){
        global $DB, $USER;

        if (!has_capability('local/classroom:manageclassroom', context_system::instance())) {
            $userenrolstatus = $DB->record_exists('local_classroom_users', array('classroomid' => $classroom->id, 'userid' => $USER->id));
            $return=false;
            if ($classroom->id > 0 && $classroom->nomination_startdate != 0 && $classroom->nomination_enddate != 0 ) {
                $params1 = array();
                $params1['classroomid'] = $classroom->id;
                $params1['nomination_startdate'] = time();
                $params1['nomination_enddate'] = time();

                $sql1 = " SELECT id FROM {local_classroom} WHERE id = :classroomid AND
                    nomination_startdate <= :nomination_startdate AND
                    nomination_enddate >= :nomination_enddate ";

                $return = $DB->record_exists_sql($sql1, $params1);

            } else if ($classroom->id > 0 && $classroom->nomination_startdate == 0 && $classroom->nomination_enddate == 0){
                $return = true;
                $nominationselfenrolmentcap = false;
            }

            if ($classroom->status == 1 && !$userenrolstatus && $return) {
                $classroom->selfenrolmentcap = true;
                $url = new moodle_url('/local/classroom/view.php', array('cid' => $classroom->id, 'action' => 'selfenrol'));

                $classroom->selfenrolmentcap = '<a href="javascript:void(0);" class="btn btn-primary pull-right mr-15" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('enroll','local_classroom').'</a>';
            }

            if ((($classroom_capacity_check && $classroom->allow_waitinglistusers == 0))) {
                    $classroom->selfenrolmentcap = get_string('capacity_check', 'local_classroom');
            } else if ( $classroom->allow_waitinglistusers == 1) {
                $waitlist = $DB->get_field('local_classroom_waitlist', 'id', array('classroomid' => $classroom->id, 'userid' => $USER->id, 'enrolstatus' => 0));
                if ($waitlist > 0) {
                    $classroom->selfenrolmentcap ='<button class="cat_btn btn-primary viewmore_btn">Waiting List</button>';
                }
            }
        }

        $totalseats = $classroom->capacity;
        $allocatedseats = $classroom->enrolled_users;

        $component = 'classroom';
        $action = 'add';
        if ($classroom->approvalreqd == 1) {
            $waitlist = $DB->get_field('local_classroom_waitlist', 'id', array('classroomid' => $classroom->id, 'userid' => $USER->id, 'enrolstatus' => 0));
            if ($waitlist > 0) {
                $return = CLASSROOM_NOT_ENROLLED;
            } else {
                $requestsql = "SELECT status FROM {local_request_records}
                        WHERE componentid = :componentid AND compname LIKE :compname AND
                        createdbyid = :createdbyid ORDER BY id DESC ";
                $request = $DB->get_field_sql($requestsql, array('componentid' => $classroom->id, 'compname' => $component, 'createdbyid'=>$USER->id));
                if ($request == 'PENDING') {
                    $return = CLASSROOM_ENROLMENT_PENDING;
                } else {
                    if (((!$classroom_capacity_check && $classroom->allow_waitinglistusers==0) || ($classroom->allow_waitinglistusers==1))) {
                        $return = CLASSROOM_ENROLMENT_REQUEST;
                    }
                }
            }
        } else {
            $return = CLASSROOM_NOT_ENROLLED;
        }


        // if ($classroom->approvalreqd == 1) {
        //     if ($enrol->enrolled == 0) {
        //         $componentid = $classroom->id;
        //         $component = 'classroom';
        //         $sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
        //         $request = $DB->get_field_sql($sql, array('componentid' => $courseid, 'compname' => $component, 'createdbyid' => $USER->id));

        //         if ($request == 'PENDING') {
        //             $return = CLASSROOM_ENROLMENT_PENDING;
        //         } else {
        //             $return = CLASSROOM_ENROLMENT_REQUEST;
        //         }
        //     } else {
        //         $return = CLASSROOM_ENROLLED;
        //     }
        // }
        return $return;
    }

}
