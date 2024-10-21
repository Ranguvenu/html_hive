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
require_once($CFG->dirroot.'/local/lib.php');
class report_sme_credit_points extends report_base{
	function init(){
		$this->components = array('columns','filters');
	}	
	function get_all_elements(){
		global $DB;
		$elements = array();
		$rs = $DB->get_recordset('local_course_facilitators', null, '', 'id');
        foreach ($rs as $result) {
			$elements[] = $result->id;
		}
		$rs->close();
		return $elements;
	}
	
	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$courseid = optional_param('filter_courses', null, PARAM_RAW);
			$emp_status = optional_param('filter_emp_status', null, PARAM_INT);
			
			$filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
			$filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
			
			if(!empty($courseid)||!empty($emp_status)||(!empty($filter_starttime)&&!empty($filter_endtime)) ){

				$sql = "SELECT cf.id, cf.credits, cf.courseid, cf.contenttype, cf.timecreated, cf.usercreated, cf.classroomid, c.fullname, u.firstname, u.lastname, u.email, u.open_employeeid, u.suspended, u.open_supervisorid, u.open_designation, u.open_location, u.open_costcenterid, u.open_departmentid
					FROM {local_course_facilitators} cf
					JOIN {user} u ON cf.userid = u.id
					JOIN {course} c ON cf.courseid = c.id
					WHERE u.deleted = 0  AND u.id > 2 ";
				
				if($courseid > 0){
					$sql .= " AND courseid = $courseid ";
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
					$sql.=" and cf.timecreated >='$filter_starttime_con' ";
				}
				if(!empty($filter_endtime)){
					$end_year=$filter_endtime['year'];
					$end_month=$filter_endtime['month'];
					$end_day=$filter_endtime['day'];
					$filter_endtime_con=mktime(23, 59, 59, $end_month, $end_day, $end_year);
					$sql.=" and cf.timecreated <='$filter_endtime_con' ";
				}

				$classrooms = $DB->get_records_sql($sql);
				$reportarray = array();
				
				foreach($classrooms as $classroom){
					$manger = new stdClass();

					if(!empty($classroom->open_employeeid)){
						$manger->employeeid = $classroom->open_employeeid;
					}else{
						$manger->employeeid = 'NA';
					}
					if($classroom->suspended == 0){
						$manger->employee_status = 'Active';
					}else{
						$manger->employee_status = 'Inactive';
					}
					$manger->smename = $classroom->firstname.' '.$classroom->lastname;
					
					if(!empty($classroom->credits)){
						$manger->credits = round($classroom->credits, 2);
					}else{
						$manger->credits = 'NA';
					}
					$manger->coursename = $classroom->fullname;
					$manger->email = $classroom->email;
					if($classroom->open_location){
						$manger->location = $classroom->open_location;
					}else{
						$manger->location = 'NA';
					}
					$manger->designation = ($classroom->open_designation) ? $classroom->open_designation : 'NA';
					if($classroom->open_departmentid){
						$department = $DB->get_field('local_costcenter', 'fullname', array('id'=>$classroom->open_departmentid));
						$manger->department = $department;
					}else{
						$manger->department = 'NA';
					}
					$supervisor = $DB->get_record('user', array('id'=>$classroom->open_supervisorid));
					if(!empty($supervisor)){
						$manger->supervisor = $supervisor->firstname. ' '. $supervisor->lastname;
					}else{
						$manger->supervisor = 'NA';
					}
					if($classroom->contenttype){
						$contenttype = $this->get_contetntype($classroom->contenttype);
						$manger->contenttype = $contenttype;
					}else{
						$manger->contenttype = 'NA';
					}
					
					if($classroom->classroomid){
						$iltrecord = $DB->get_record('local_classroom', array('id'=>$classroom->classroomid));
						$manger->iltstartenddate = date('d-m-Y', $iltrecord->startdate).' to '.date('d-m-Y', $iltrecord->enddate);
						$manger->month= date('F',$iltrecord->startdate);
						$manger->year= date('Y',$iltrecord->startdate);
						$manger->iltname = $iltrecord->name;
						if($iltrecord->completion_time){
							$manger->completiondate = date('d-m-Y', $iltrecord->completion_time);
						}else{
							$manger->completiondate = 'NA';
						}
					}else{
						$manger->iltname = 'NA';
						$manger->completiondate = 'NA';
						$manger->iltstartenddate = 'NA';
						$manger->month= 'NA';
						$manger->year= 'NA';
					}
					$manger->assigneddate = date('d-m-Y', $classroom->timecreated);
					$assignedby = $DB->get_record_sql("Select * from {user} where id = $classroom->usercreated");
					$manger->assignedby = $assignedby->firstname. ' '.   $assignedby->lastname;
					$reportarray[] = $manger;
				}
			}
			return $reportarray;
		}
		return $finalelements;
	}
	
	function get_contetntype($type){
		
		if($type == 1){
			$contenttype = get_string('project_review_viva', 'block_configurable_reports');
		}elseif($type == 2){
			$contenttype = get_string('classroom_content_development', 'block_configurable_reports');
		}elseif($type == 3){
			$contenttype = get_string('eLearning_content_development', 'block_configurable_reports');
		}elseif($type == 4){
			$contenttype = get_string('others', 'block_configurable_reports');
		}else{
			$contenttype = get_string('classroom_delivery', 'block_configurable_reports');
		}
		return $contenttype;
	}
}
