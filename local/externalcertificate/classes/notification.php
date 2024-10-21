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
namespace local_externalcertificate;
use \Exception;
global $CFG;
require_once($CFG->dirroot .'/local/notifications/lib.php');

Class notification {
	public $db;
	public $user;
	public function __construct($db=null, $user=null){
		global $DB, $USER;
		$this->db = $db ? $db :$DB;
		$this->user = $user ? $user :$USER;
	}

	public function send_extcertificate_notification($certificate, $user, $emailtype){
		
		$notification = $this->get_existing_notification($certificate, $emailtype);
		if($notification){
			$this->send_certificate_email($certificate , $user, $emailtype, $notification);
		}
	}  

	public function get_existing_notification($certificate, $emailtype){
		$corecomponent = new \core_component();
		$costcenterexist = $corecomponent::get_plugin_directory('local','costcenter');
		$params = array();
		
		$notification_typesql = "SELECT lni.* FROM {local_notification_info} AS lni 
			JOIN {local_notification_type} AS lnt ON lnt.id=lni.notificationid
			WHERE  lnt.shortname LIKE :emailtype AND lni.active=1 ";
		$params['emailtype'] = $emailtype;
		if($costcenterexist){
			$notification_typesql .= " AND lni.costcenterid=:costcenterid";
			$params['costcenterid'] = $this->user->open_costcenterid;
		}
		$notification = $this->db->get_record_sql($notification_typesql, $params);
		if(empty($notification)){ // sends the default notification for the type.
			$params = array();
			$notification_typesql = "SELECT lni.* FROM {local_notification_info} AS lni 
				JOIN {local_notification_type} AS lnt ON lnt.id=lni.notificationid 
				WHERE lnt.shortname LIKE :emailtype AND lni.active=1 ";
			$params['emailtype'] = $emailtype;
			if($costcenterexist){
				$notification_typesql .= " AND lni.costcenterid=:costcenterid";
				$params['costcenterid'] = $certificate->open_costcenterid;
			}
			$notification = $this->db->get_record_sql($notification_typesql, $params);
		}
		if(empty($notification)){
			return false;
		}else{
			return $notification;
		}
	}

	public function send_certificate_email($certificate, $user, $emailtype, $notification){
		$datamailobj = new \stdclass();	
		
		$datamailobj->courseid = 0;
		$datamailobj->notification_infoid = $notification->id;
		if(in_array($emailtype,array('certificate_uploaded'))){        
            $datamailobj->user_name = $certificate->username;	
            $datamailobj->course_name = $certificate->coursename;	
			if($certificate->certificate_issuing_authority == 'Other'){
				$certificate->issuing_authority = $certificate->authority_type .' ('. $certificate->certificate_issuing_authority .') ';
			}
            $datamailobj->issuing_authority = $certificate->certificate_issuing_authority;	
            $datamailobj->provider = $certificate->institute_provider;	
			$datamailobj->skill = $certificate->skill;	
            $datamailobj->uploaded_date = date('d-M-Y' ,$certificate->timecreated);	
        }      
        if(in_array($emailtype,array('certificate_approved','certificate_declined'))){   
            $a = array('0' => 'Pending', '1' => 'Approved', '2' => 'Decline');
			$datamailobj->user_name = $certificate->username;	
            $datamailobj->course_name = $certificate->coursename;	
			if($certificate->certificate_issuing_authority == 'Other'){
				$certificate->issuing_authority = $certificate->authority_type .' ('. $certificate->certificate_issuing_authority .') ';
			}
            $datamailobj->issuing_authority = $certificate->certificate_issuing_authority;	
            $datamailobj->provider = $certificate->institute_provider;	
			$datamailobj->skill = $certificate->skill;
			$datamailobj->uploaded_date = date('d-M-Y' ,$certificate->timecreated);		
            $datamailobj->aprroved_date = date('d-M-Y' ,$certificate->timemodified);
            $datamailobj->cert_status = $a[$certificate->status];
            $datamailobj->reason = ($certificate->status == 2) ? $certificate->reason : 'N/A';
        }
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
	
        $this->log_email_notification($user, $fromuser, $datamailobj, $emailtype);
        if($superuser){
            $datamailobj->body = $notification->adminbody;
            $datamailobj->touserid = $superuser->id;
            $datamailobj->teammemberid = $user->id;
            $this->log_email_notification($superuser, $fromuser, $datamailobj, $emailtype);
        }
	
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

		$dataobject->timecreated = time();
		$dataobject->usercreated = $this->user->id;			
		$this->db->insert_record('local_emaillogs', $dataobject);
        
    /*     if($logid = $this->check_pending_mail_exists($user, $fromuser, $datamailobj)){
        	$dataobject->id = $logid;
        	$dataobject->timemodified = time();
            $dataobject->usermodified = $this->user->id;
			$logid = $this->db->update_record('local_emaillogs', $dataobject);
        }else{
        	$dataobject->timecreated = time();
        	$dataobject->usercreated = $this->user->id;			
        	$this->db->insert_record('local_emaillogs', $dataobject);
		
		} */

	}
	public function check_pending_mail_exists($user, $fromuser, $datamailobj){
		$sql =  " SELECT id FROM {local_emaillogs} WHERE to_userid = :userid AND notification_infoid = :infoid AND from_userid = :fromuserid AND subject = :subject 
					AND status = 0 AND ";
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