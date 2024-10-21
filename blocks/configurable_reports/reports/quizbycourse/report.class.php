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

/** Configurable Reports
 * A Moodle block for creating configurable reports
 * @package blocks
 * @author: Madhavi Rajana <madhavi.r@eabyas.com>
 * @date: 2020
 */

global $DB, $CFG, $USER;
require_once($CFG->dirroot.'/mod/quiz/lib.php');
require_once($CFG->dirroot.'/mod/quiz/classes/plugininfo/quiz.php');
require_once($CFG->dirroot.'/local/lib.php');

class report_quizbycourse extends report_base{
	function init(){
		$this->components = array('columns', 'filters');
	}	
	function get_all_elements(){
		global $DB;
		
		$elements = array();
		$rs = $DB->get_recordset('user', null, '', 'id');
            foreach ($rs as $result) {
			$elements[] = $result->id;
		}
		$rs->close();
		return $elements;
	}
	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$syscontext = context_system::instance();
		$finalelements = array();
		if(!empty($elements)){			
			list($usql, $params) = $DB->get_in_or_equal($elements);
			
			$filter_onlinetestid = optional_param('filter_onlinetest', 0, PARAM_INT);
			$filter_department = optional_param('filter_department','',PARAM_RAW);
			$filter_users = optional_param('filter_users', 0, PARAM_INT);
			$filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
            $filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
            $filter_courses = optional_param('filter_courses', 0, PARAM_RAW);
			$emp_status = optional_param('filter_emp_status', null, PARAM_INT);
			//if((!empty($filter_courses) && ($filter_courses!=0)) ||   (!empty($filter_users) && ($filter_users!=null)) ){
				//$filter_onlinetestid = implode(',',$filter_onlinetestid);
			if($filter_onlinetestid || $filter_department || $filter_users || $filter_starttime || $filter_endtime || $filter_courses || $filter_courses || $emp_status ){
				$sql = "SELECT distinct(u.id) as 'uid', u.firstname , u.lastname , u.suspended, u.email,
				u.idnumber, c.fullname AS 'course', c.id as 'cid',
				c.shortname as 'coursecode', c.startdate as 'coursestart', gi.itemmodule, gi.iteminstance,
				CASE 
				WHEN gi.itemtype = 'course' 
				THEN c.fullname + ' Course Total'
                ELSE gi.itemname
                END AS 'activityname',
                from_unixtime(ra.timemodified, '%m-%d-%Y') as timeenrolled, 
                IF(cc.timecompleted is not null, from_unixtime(cc.timecompleted, '%m/%d/%Y'), '-') timecompleted,
                IF((cmc.completionstate= 1 or cmc.completionstate= 2 ), 'Completed', 'Not completed') activitystatus,
				from_unixtime(cmc.timemodified, '%m/%d/%Y') as activity_completed_date,
				ROUND(gi.grademax,2) AS maxgrade,
                ROUND(gg.finalgrade,2) AS grade
                FROM mdl_user AS u 
				LEFT JOIN mdl_role_assignments AS ra ON   u.id = ra.userid
                LEFT JOIN mdl_context AS ctx ON  ra.contextid = ctx.id
				LEFT JOIN mdl_course AS c  ON c.id = ctx.instanceid
				JOIN mdl_grade_items AS gi ON  gi.itemmodule ='quiz' AND gi.courseid = c.id
				LEFT JOIN mdl_grade_grades AS gg ON gi.id = gg.itemid AND gg.userid = u.id
				LEFT JOIN mdl_course_modules cm ON cm.instance = gi.iteminstance AND gi.courseid = cm.course
				LEFT JOIN mdl_course_completions AS cc ON cc.course = c.id AND cc.userid = u.id
				LEFT JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id AND cmc.userid  = u.id
                WHERE u.deleted = 0 AND ra.roleid = 5 ";
				//
				//if(!empty($filter_onlinetestid)){
				//	$sql.=" AND q.id IN( $filter_onlinetestid) ";
				//}
				if(!empty($filter_courses) && $filter_courses != -1){
					$sql.=" AND c.id = $filter_courses ";
				}
				/*if(!empty($filter_department)){
					$sql.=" AND ud.costcenter_custom = '$filter_department' ";
				}*/
				if(!empty($filter_users)){
					$sql.=" AND u.id = $filter_users ";
				}
				if($emp_status == 1){
					$sql.=" AND u.suspended = 0 ";
				}elseif($emp_status == 2){
					$sql.=" AND u.suspended = 1 ";
				}
				if(!empty($filter_starttime)){
					$start_year=$filter_starttime['year'];
					$start_month=$filter_starttime['month'];
					$start_day=$filter_starttime['day'];
					$filter_starttime_con=mktime(0, 0, 0, $start_month, $start_day, $start_year);
					$sql.=" and gg.timemodified >='$filter_starttime_con' ";
			}
			if(!empty($filter_endtime)){
				 $end_year=$filter_endtime['year'];
				 $end_month=$filter_endtime['month'];
				 $end_day=$filter_endtime['day'];
				 $filter_endtime_con=mktime(23, 59, 59, $end_month, $end_day, $end_year);
				 $sql.=" and gg.timemodified <='$filter_endtime_con' ";
			}
				$sql .= " order by gi.iteminstance";
				$records = $DB->get_recordset_sql($sql);
			//}
			$reportarray = array();
			
			foreach($records as $record){
				$manger=new stdClass();
				$manger->testname = $record->activityname;
				$manger->employeeidnumber = $record->idnumber;
				$manger->employeename = $record->firstname.' '.$record->lastname;
				$manger->email = $record->email;
				if($record->suspended == 0){
					$manger->employee_status = 'Active';
				}else{
					$manger->employee_status = 'Inactive';
				}
				
				$manger->maxgrade = $record->maxgrade;
				if ($record->iteminstance)
				$passgrade=$DB->get_field('grade_items','gradepass',array('itemtype'=>'mod','itemmodule'=>'quiz','iteminstance'=>$record->iteminstance));
				if($passgrade){
					$manger->mingrade = $passgrade;
				}else{
					$manger->mingrade = 0;
				}
				$status = $record->activitystatus;
				if ($record->iteminstance)
                $quiz_attempts = $DB->get_record_sql("SELECT * FROM {quiz_attempts} WHERE quiz=$record->iteminstance AND userid=$record->uid");
				
				if($quiz_attempts){
					if($quiz_attempts->timefinish){
						$time_spent = $quiz_attempts->timefinish-$quiz_attempts->timestart;
						//$manger->timespent = ROUND(($time_spent/60),2);
						//$manger->timespent =floor(($time_spent / 60) % 60);
						$manger->timespent = gmdate("i:s",$time_spent);
					}elseif(empty($quiz_attempts->timefinish)){
						$manger->timespent = '-';
					}
				}else{
					$manger->timespent = '-';
				}
				if (empty($record->grade))
				$manger->testscore = 'NA';
				else
				$manger->testscore = $record->grade;
				$manger->course = $DB->get_field('course','fullname',array('id'=>$record->cid));
				$manger->teststatus = $status;
				if (empty($record->activity_completed_date))
				$manger->completiondate = 'NA';
				else
				$manger->completiondate = $record->activity_completed_date;
				
				$reportarray[] = $manger;
			}
			return $reportarray;
		}
	}
		return $finalelements;
	}
}
