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
 */
namespace local_courses;
global $CFG;
require_once($CFG->dirroot .'/local/notifications/lib.php');
class notification{
	public $db;
	public $user;
	public function __construct($db=null, $user=null){
		global $DB, $USER;
		$this->db = $db ? $db :$DB;
		$this->user = $user ? $user :$USER;
	}
	public function send_course_completion_notification($course, $user){
		$emailtype = 'course_complete';
		$notification = $this->get_existing_notification($course, $emailtype);
		if($notification){
			$this->send_course_email($course , $user, $emailtype, $notification);
		}
	}

	public function send_course_assignment_gradeaction_notification($course, $user, $emailtype){
		$notification = $this->get_existing_notification($course, $emailtype);
		if($notification){
			$this->send_course_email($course , $user, $emailtype, $notification);
		}
	}

	public function get_existing_notification($course, $emailtype){
		$corecomponent = new \core_component();
		$costcenterexist = $corecomponent::get_plugin_directory('local','costcenter');
		$params = array();
		$notification_typesql = "SELECT lni.* FROM {local_notification_info} AS lni 
			JOIN {local_notification_type} AS lnt ON lnt.id=lni.notificationid
			WHERE concat(',',lni.moduleid,',') LIKE concat('%,',:moduleid,',%') AND lnt.shortname LIKE :emailtype AND lni.active=1 ";
		$params['moduleid'] = $course->id;
		$params['emailtype'] = $emailtype;
		if($costcenterexist){
			$notification_typesql .= " AND lni.costcenterid=:costcenterid";
			$params['costcenterid'] = $course->open_costcenterid;
		}
		$notification = $this->db->get_record_sql($notification_typesql, $params);
		if(empty($notification)){ // sends the default notification for the type.
			$params = array();
			$notification_typesql = "SELECT lni.* FROM {local_notification_info} AS lni 
				JOIN {local_notification_type} AS lnt ON lnt.id=lni.notificationid 
				WHERE (lni.moduleid IS NULL OR lni.moduleid LIKE '0') 
				AND lnt.shortname LIKE :emailtype AND lni.active=1 ";
			$params['emailtype'] = $emailtype;
			if($costcenterexist){
				$notification_typesql .= " AND lni.costcenterid=:costcenterid";
				$params['costcenterid'] = $course->open_costcenterid;
			}
			$notification = $this->db->get_record_sql($notification_typesql, $params);
		}
		if(empty($notification)){
			return false;
		}else{
			return $notification;
		}
	}
	public function send_course_email($course, $user, $emailtype, $notification){
		global $DB;
		$datamailobj = new \stdclass();
        $datamailobj->course_title = $course->fullname;
        $datamailobj->courseid = $course->id;
		$userenrolleddate = $DB->get_field_sql("SELECT MAX(ue.timecreated) as enroldate from {enrol} e 
													JOIN {user_enrolments} ue on ue.enrolid = e.id 
													WHERE e.courseid=$course->id and ue.userid=$user->id ");
        //$datamailobj->course_enrolstartdate = $course->startdate ? date("d-m-Y", $course->startdate) : 'N/A';
		$datamailobj->course_enrolstartdate = $userenrolleddate ? date("d-m-Y", $userenrolleddate) : 'N/A';
        $datamailobj->course_enrolenddate = $course->enddate ? date("d-m-Y", $course->enddate) : 'N/A';
        $datamailobj->course_completiondays = $course->open_coursecompletiondays ? $course->open_coursecompletiondays : 'N/A';
        $datamailobj->notification_infoid = $notification->id;

        // $datamailobj->course_department = $department;
        $datamailobj->course_department = $course->open_departmentid ? 
        	$this->db->get_field('local_costcenter', 'fullname', array('id' => $course->open_departmentid)) : 'N/A' ;
        $datamailobj->course_categoryname = $this->db->get_field('course_categories', 'name', array('id' => $course->category));

        $url = new \moodle_url('/course/view.php?id='.$course->id);
        $datamailobj->course_url = \html_writer::link($url, $url);
        $datamailobj->course_description = $course->summary ? $course->summary : 'N/A' ;
        if($course->open_coursecreator){
            $datamailobj->course_creator = $this->db->get_field_sql("SELECT concat(firstname,' ',lastname) FROM {user} WHERE id=:creatorid", array('creatorid' => $course->open_coursecreator));
        }else{
            $datamailobj->course_creator = 'N/A';
        }
        if($emailtype == 'course_complete'){
        	$datamailobj->course_completiondate = date('d-m-Y', time());	
        }
		if(in_array($emailtype,array('assign_approve','assign_reject','assign_reset'))){
        	$datamailobj->course_assignment_status = $course->status;	
			$datamailobj->reason = $course->reason;	
        }

		if(in_array($emailtype,array('course_completion_reminder'))){
        	$datamailobj->frequencyflag = $notification->frequencyflag;	
			$datamailobj->emailtype = $emailtype;
			$datamailobj->reminderdays = $notification->reminderdays;		
		}
		
        // $includes = new \user_course_details();
        // $courseimage = $includes->course_summary_files($course);
        // $datamailobj->course_image = \html_writer::img($courseimage, $course->fullname,array());
        $datamailobj->enroluser_fullname = $user->firstname;
	    $datamailobj->enroluser_email = $user->email;
	    $datamailobj->adminbody = NULL;
	    $datamailobj->body = $notification->body;
	    $datamailobj->subject = $notification->subject;
	    $datamailobj->touserid = $user->id;

	    $fromuser = \core_user::get_support_user();
	    $datamailobj->fromuserid = $fromuser->id;
	    $datamailobj->teammemberid = 0;
	    if(!empty($notification->adminbody) && !empty($user->open_supervisorid)){
	    	$superuser = \core_user::get_user($user->open_supervisorid);
	    }else{
	    	$superuser = false;
	    }
		// print_r($datamailobj);die;
	 //    if(class_exists('\notifications')){
		//     $notifications_lib = new \notifications();
		//     $notifications_lib->send_email_notification($emailtype, $datamailobj, $user->id, $fromuser->id);
		//     if($superuser){
		//     	$datamailobj->body = null;
		//     	$datamailobj->adminbody = $notification->adminbody;
		//     	$notifications_lib->send_email_notification($emailtype, $datamailobj, $superuser->id, $fromuser->id);
		//     }
		// }else{
			$this->log_email_notification($user, $fromuser, $datamailobj, $emailtype);
			if($superuser){
				$datamailobj->body = $notification->adminbody;
				$datamailobj->touserid = $superuser->id;
				$datamailobj->teammemberid = $user->id;
				$this->log_email_notification($superuser, $fromuser, $datamailobj, $emailtype);
			}
		// }
	}
	public function log_email_notification($user, $fromuser, $datamailobj, $emailtype){
	
		$dataobject = clone $datamailobj;	
		$dataobject->subject = $this->replace_strings($datamailobj, $datamailobj->subject, $emailtype);
		$dataobject->emailbody = $this->replace_strings($datamailobj, $datamailobj->body, $emailtype);
		$dataobject->body = $dataobject->emailbody;
		$dataobject->from_emailid = $fromuser->email;
		$dataobject->from_userid = $fromuser->id;
		$dataobject->ccto = 0;
        $dataobject->to_emailid = $user->email;
        $dataobject->to_userid = $user->id;
        $dataobject->sentdate = 0;
        $dataobject->sent_by = $this->user->id;
        $dataobject->moduleid = $datamailobj->courseid;
        $dataobject->courseid = $datamailobj->courseid;
		
        
        if($logid = $this->check_pending_mail_exists($user, $fromuser, $datamailobj)){
        	$dataobject->id = $logid;
        	$dataobject->timemodified = time();
            $dataobject->usermodified = $this->user->id;
			$logid = $this->db->update_record('local_emaillogs', $dataobject);
        }else{
        	$dataobject->timecreated = time();
        	$dataobject->usercreated = $this->user->id;			
        	$this->db->insert_record('local_emaillogs', $dataobject);
			/* if($dataobject->emailtype == 'course_completion_reminder' && $datamailobj->frequencyflag){
				$nextdate = $dataobject->timecreated+($datamailobj->reminderdays*86400);
				$dataobject->nextdate = $nextdate;
				$this->db->insert_record('local_frequencylogs', $dataobject);        
			} */
		}

	}
	public function check_pending_mail_exists($user, $fromuser, $datamailobj){
		$sql =  " SELECT id FROM {local_emaillogs} WHERE to_userid = :userid AND notification_infoid = :infoid AND from_userid = :fromuserid AND subject = :subject AND status = 0";
		$params['userid'] = $datamailobj->touserid;
		$params['fromuserid'] = $datamailobj->fromuserid;
		$params['subject'] = $datamailobj->subject;
		$params['infoid'] = $datamailobj->notification_infoid;
        if($datamailobj->courseid){
            $sql .= " AND moduleid=:courseid";
            $params['courseid'] = $datamailobj->courseid;
        }
        if($datamailobj->teammemberid){
            $sql .= " AND teammemberid=:teammemberid";
            $params['teammemberid'] = $datamailobj->teammemberid;
        }
		return $this->db->get_field_sql($sql ,$params);
	}
	public function replace_strings($dataobject, $data, $emailtype){       
        // $strings = $this->db->get_records('local_notification_strings', array('module' => 'course'));        
        // if($strings){
        //     foreach($strings as $string){
        //         foreach($dataobject as $key => $dataval){
        //             $key = '['.$key.']';
        //             if("$string->name" == "$key"){
        //                 $data = str_replace("$string->name", "$dataval", $data);
        //             }
        //         }
        //     }
        // }
        $local_notification = new \notifications();
        $strings = $local_notification->get_string_identifiers($emailtype);
        $strings = explode(',', $strings);
        if($strings){
            foreach($strings as $string){
                $string = trim($string);
                foreach($dataobject as $key => $dataval){
                    $key = '['.$key.']';
                    if("$string" == "$key"){
                        $data = str_replace("$string", "$dataval", $data);
                    }
                }
            }
        }
        return $data;
    }
}