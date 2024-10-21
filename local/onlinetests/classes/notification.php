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
namespace local_onlinetests;
class notification{
	public $db;
	public $user;
	public function __construct($db=null, $user=null){
		global $DB, $USER;
		$this->db = $db ? $db :$DB;
		$this->user = $user ? $user :$USER;
	}
	public function get_notification_strings($emailtype){
		switch($emailtype){
			case 'onlinetest_enrollment':
                $strings = "[test_name],[test_schedule], 
                            [test_enroldate],[test_username],
                            [test_email],[test_url]";
                break;
            case 'onlinetest_due':
                $strings = "[test_name],[test_schedule], 
                            [test_enroldate],[test_username],
                            [test_email],[test_url]";
                break;
            case 'onlinetest_completed':
                $strings = "[test_name],[test_schedule], 
                            [test_enroldate],[test_username],
                            [test_email],[test_url],[test_completeddate]";
                break;
		}
		return $strings;
	}
	public function onlinetest_notification($emailtype, $touser, $fromuser, $onlinetestinstance){
        if($notification = $this->get_existing_notification($onlinetestinstance, $emailtype)){
            $this->send_onlinetest_notification($onlinetestinstance, $touser, $fromuser, $emailtype, $notification);
        }
    }
    public function get_existing_notification($onlinetestinstance, $emailtype){
    	$corecomponent = new \core_component();
        $costcenterexist = $corecomponent::get_plugin_directory('local','costcenter');
        $params = array();
        $notification_typesql = "SELECT lni.* FROM {local_notification_info} AS lni 
            JOIN {local_notification_type} AS lnt ON lnt.id=lni.notificationid
            WHERE concat(',',lni.moduleid,',') LIKE concat('%,',:moduleid,',%') AND lnt.shortname LIKE :emailtype AND lni.active=1 ";
        $params['moduleid'] = $onlinetestinstance->id;
        $params['emailtype'] = $emailtype;
        if($costcenterexist){
            $notification_typesql .= " AND lni.costcenterid=:costcenterid";
            $params['costcenterid'] = $onlinetestinstance->costcenterid;
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
                $params['costcenterid'] = $onlinetestinstance->costcenterid;
            }
            $notification = $this->db->get_record_sql($notification_typesql, $params);
        }
        if(empty($notification)){
            return false;
        }else{
            return $notification;
        }
    }
    public function send_onlinetest_notification($onlinetestinstance, $touser, $fromuser, $emailtype, $notification){
    	$datamailobject = new \stdClass();
    	$datamailobject->test_name = $onlinetestinstance->name;
    	$datamailobject->notification_infoid = $notification->id;
    	if($onlinetestinstance->timeopen && $onlinetestinstance->timeclose){
            $datamailobject->test_schedule = date('d-m-Y', $onlinetestinstance->timeopen).' To '.date('d-m-Y', $onlinetestinstance->timeclose);
        }else{
            $datamailobject->test_schedule = 'Open Test';
        }
        $enrolleddate = $this->db->get_field('local_onlinetest_users', 'timecreated', array('onlinetestid' => $onlinetestinstance->id, 'userid' => $touser->id));
        $datamailobject->test_enroldate = date('d-m-Y', $enrolleddate);
        if($emailtype == "onlinetest_unenrollment"){
            $datamailobject->test_unenroldate = date('d-m-Y');
        }
    	$datamailobject->test_username = $touser->firstname;
    	$datamailobject->test_email = $touser->email;
    	$cm = get_coursemodule_from_instance("quiz", $onlinetestinstance->quizid, 0, false, MUST_EXIST);
    	$url = new \moodle_url($CFG->wwwroot.'/mod/quiz/view.php?id='.$cm->id);
        $datamailobject->test_url = '<a href='.$url.'>'.$url.'</a>';
    	if($emailtype == "onlinetest_completed"){
    		$gradeitem = $this->db->get_record('grade_items', array('iteminstance'=>$onlinetestinstance->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
            $usergrade = $this->db->get_record_sql("SELECT id, finalgrade, timemodified FROM {grade_grades} WHERE itemid = {$gradeitem->id} AND userid = {$this->user->id}");
            if ($usergrade) {
                $gradepass = ($gradeitem->gradepass) ? round($gradeitem->gradepass): '-';
                if ($usergrade->finalgrade >= $gradepass) {
                    $completedon = date("d-m-Y", $usergrade->timemodified);
                } else {
                    $completedon = 'N/A';
                }                   
            } else {
                $completedon = 'N/A';
            }
    		$datamailobject->test_completeddate = $completedon;
    	}
    	$datamailobject->adminbody = NULL;
        $datamailobject->body = $notification->body;
        $datamailobject->subject = $notification->subject;
        $datamailobject->onlinetestid = $onlinetestinstance->id;
        $datamailobject->touserid = $touser->id;
        $datamailobject->fromuserid = $fromuser->id;
        $datamailobject->teammemberid = 0;
        if(!empty($notification->adminbody) && !empty($touser->open_supervisorid)){
            $superuser = \core_user::get_user($touser->open_supervisorid);
        }else{
            $superuser = false;
        }

        $this->log_email_notification($touser, $fromuser, $datamailobject);
        if($superuser){
            $datamailobject->body = $notification->adminbody;
            $datamailobject->touserid = $superuser->id;
            $datamailobject->teammemberid = $touser->id;
            $this->log_email_notification($superuser, $fromuser, $datamailobject);
        }
    }
    public function log_email_notification($touser, $fromuser, $datamailobj){
    	$dataobject = clone $datamailobj;
        $dataobject->subject = $this->replace_strings($datamailobj, $datamailobj->subject);
        $dataobject->emailbody = $this->replace_strings($datamailobj, $datamailobj->body);
        $dataobject->from_emailid = $fromuser->email;
        $dataobject->from_userid = $fromuser->id;
        $dataobject->to_emailid = $touser->email;
        $dataobject->to_userid = $touser->id;
        $dataobject->ccto = 0;
        $dataobject->sentdate = 0;
        $dataobject->sent_by = $fromuser->id;
        $dataobject->moduleid = $datamailobj->onlinetestid;
        
        if($logid = $this->check_pending_mail_exists($touser, $fromuser, $datamailobj)){
            $dataobject->id = $logid;
            $dataobject->timemodified = time();
            $dataobject->usermodified = $this->user->id;
            $logid = $this->db->update_record('local_emaillogs', $dataobject);
        }else{
            $dataobject->timecreated = time();
            $dataobject->usercreated = $this->user->id;
            $this->db->insert_record('local_emaillogs', $dataobject);
        }
    }
    public function replace_strings($dataobject, $data){       
        $strings = $this->db->get_records('local_notification_strings', array('module' => 'onlinetest'));        
        if($strings){
            foreach($strings as $string){
                foreach($dataobject as $key => $dataval){
                    $key = '['.$key.']';
                    if("$string->name" == "$key"){
                        $data = str_replace("$string->name", "$dataval", $data);
                    }
                }
            }
        }
        return $data;
    }
    public function check_pending_mail_exists($user, $fromuser, $datamailobj){
        $sql =  " SELECT id FROM {local_emaillogs} WHERE to_userid = :userid AND notification_infoid = :infoid AND from_userid = :fromuserid AND subject = :subject AND status = 0 ";
        $params['userid'] = $datamailobj->touserid;
        $params['fromuserid'] = $datamailobj->fromuserid;
        $params['infoid'] = $datamailobj->notification_infoid;
        $params['subject'] = $datamailobj->subject;
        if($datamailobj->onlinetestid){
            $sql .= " AND moduleid=:onlinetestid";
            $params['onlinetestid'] = $datamailobj->onlinetestid;
        }
        if($datamailobj->teammemberid){
            $sql .= " AND teammemberid=:teammemberid";
            $params['teammemberid'] = $datamailobj->teammemberid;
        }
        return $this->db->get_field_sql($sql ,$params);
    }
}