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
namespace local_onlinetests\local;
class user {
	public function user_profile_content($userid, $return = false,$start =0,$limit =0){
		global $OUTPUT;
		$returnobj = new \stdClass();
        $returnobj->onlinetests_exist = 1;
        $useronlinetests = $this->enrol_get_users_onlinetests($userid,false,true,$start,$limit);
        $data = array();
        foreach($useronlinetests['data'] as $onlinetest){
        	$onlinetestsarray = array();
            $onlinetestsarray['id'] = $onlinetest->id;
            $onlinetestsarray['name'] = $onlinetest->name;
            $onlinetestsarray['description'] = '';
            $onlinetestsarray['percentage'] = '';
            $onlinetestsarray['url'] = '';
            $data[] = $onlinetestsarray;
        }

        $returnobj->sequence = 3;
        $returnobj->count = $useronlinetests['count'];
        $returnobj->divid = 'user_onlineexams';
        $returnobj->moduletype = 'onlinetests';
        $returnobj->userid = $userid;
        $returnobj->string = get_string('onlineexams', 'local_onlinetests');
        
        $returnobj->navdata = $data;
        return $returnobj;
	}
	/**
	 * [function to get user enrolled onlinetests]
	 * @param  [INT] $userid [id of the user]
	 * @return [INT]         [count of the onlinetests enrolled]
	 */
	public function enrol_get_users_onlinetest_count($userid){
	    global $DB;
	        $onlinetest_sql = "SELECT count(id) FROM {local_onlinetest_users} WHERE userid = :userid";
	        $onlinetest_count = $DB->count_records_sql($onlinetest_sql, array('userid' => $userid));
	        return $onlinetest_count;
	}
	public function get_enrolled_onlinetest_as_employee($userid){
	    global $DB;
	    $sql = "SELECT lot.* FROM {local_onlinetests} AS lot
	        JOIN {local_onlinetest_users} AS lotu ON lotu.onlinetestid=lot.id
	        -- JOIN {quiz} AS q ON q.id = lot.quizid
	        -- JOIN {enrol} AS e ON e.courseid=q.course
	        -- JOIN {role} AS r ON r.id=e.roleid
	        WHERE /*r.shortname='employee' AND*/ lotu.userid=$userid";
	    $employeeonlinetest = $DB->get_records_sql($sql);
	    return $employeeonlinetest;
	}
	public function user_team_content($user){
		global $OUTPUT;
		$total = $this->get_team_member_onlinetests_status($user->id);
		$completed = $this->get_team_member_onlinetests_status($user->id,1);
		$templatedata = array();
        $teamstatus = new \local_myteam\output\team_status_lib();
        $templatedata['elementcolor'] = $teamstatus->get_colorcode_tm_dashboard($completed,$total);
        $templatedata['completed'] = $completed;
        $templatedata['enrolled'] = $total;
        $templatedata['username'] = fullname($user);
        $templatedata['userid'] = $user->id;
        $templatedata['modulename'] = 'onlinetests';
		// return $OUTPUT->render_from_template('local_users/team_status_element', $templatedata);
		return (object) $templatedata;
	}
	public function get_team_member_onlinetests_status($userid,$completed = false){
		global $DB;
		$sql = "SELECT lou.id,lou.onlinetestid,lou.status,lou.userid as userid
				FROM {local_onlinetest_users} AS lou
				JOIN {local_onlinetests} AS lo ON lou.onlinetestid = lo.id
				WHERE lou.userid = $userid AND lo.visible = 1";
		if($completed){
			$sql .= " AND lou.status=1";
		}
		$onlineteststatus = $DB->get_records_sql($sql);
		return count($onlineteststatus);
	}

	/**
	 * [function to get_user modulecontent]
	 * @param  [INT] $id [id of the user]
	 * @param  [INT] $start [start]
	 * @return [INT] $limit [limit]
	 */
    public function user_modulewise_content($id,$start =0,$limit=5){
      global $OUTPUT,$PAGE,$DB;
      $returnobj = new \stdClass();
      $useronlinetests = $this->enrol_get_users_onlinetests($id,false,true,$start,$limit);
      $data = array();
      foreach($useronlinetests['data'] as $onlinetest){
          $onlinetestsarray = array();
          $onlinetestsarray['name'] = $onlinetest->name;
          $onlinetestsarray['code'] = $onlinetest->name;
          $onlinetestsarray['enrolldate'] = date('d-m-Y H:i:s',$onlinetest->enrolldate);
          if($onlinetest->status == 1){
            $onlinetestsarray['status'] = 'Completed';
            $onlinetestsarray['completiondate'] = date('d-m-Y H:i',$onlinetest->completiontime);
          }else{
            $onlinetestsarray['status'] = 'Not Completed';
            $onlinetestsarray['completiondate'] = 'NA';
          }

          $data[] = $onlinetestsarray;
      }
      $returnobj->navdata = $data;
      return $returnobj;
    }

    /**
	 * [function to get_enrolled onlinetests data and count]
	 * @param  [INT] $userid [id of the user]
	 * @param  [BOOLEAN] $count [true or false]
	 * @param  [BOOLEAN] $limityesorno [true or false]
	 * @param  [INT] $start [start]
	 * @return [INT] $limit [limit]
	 */
	public function enrol_get_users_onlinetests($userid,$count =false,$limityesorno = false,$start =0,$limit=5) {
	    global $DB;
	    $countsql = "SELECT count(lot.id)";
	    $selectsql = "SELECT lot.name,lot.quizid,lot.costcenterid,lot.departmentid,
	    			lot.visible,lot.timeopen,lot.timeclose,lotu.timecreated as enrolldate,lotu.status,
	    			lotu.timemodified as completiontime ";
	    $fromsql = " FROM {local_onlinetests} AS lot
			        JOIN {local_onlinetest_users} AS lotu ON lotu.onlinetestid=lot.id
			        WHERE lotu.userid=:userid";
	    $params = array();
	    $params['userid'] = $userid;
	    if($limityesorno){
	    	$employeeonlinetest = $DB->get_records_sql($selectsql.$fromsql,$params,$start,$limit);
	    }else{
	    	$employeeonlinetest = $DB->get_records_sql($selectsql.$fromsql,$params);
	    }
	    $onlinetestscount = $DB->count_records_sql($countsql.$fromsql,$params);
	    return array('count' => $onlinetestscount,'data' => $employeeonlinetest);
	}
	public function user_team_headers(){
        return array('onlinetests' => get_string('pluginname', 'local_onlinetests'));
    }
}