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
class report_course_completion extends report_base{
	
	function init(){
		$this->components = array('columns','filters');
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
	
	function get_rows($elements, $sqlorder = '',$filters_empty ='',$coursemid=0){
		global $DB, $CFG, $USER;
		$finalelements = array();

		if(!empty($elements)){			
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$category = optional_param('filter_categories','', PARAM_INT);
			$courseid = optional_param('filter_courses','', PARAM_INT);
			$completionstatus = optional_param('filter_coursestatus', 0, PARAM_INT);
			$emp_status = optional_param('filter_emp_status', null, PARAM_INT);
			$filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
           			$filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
            			$userid = optional_param('filter_users','', PARAM_INT);
			
			$reportarray = array();
		//	if( !empty($category) || !empty($courseid) || !empty($completionstatus) || !empty($emp_status) || (!empty($filter_starttime) && !empty($filter_endtime)) || !empty($userid)){

				$sql = "SELECT ra.id, u.id as userid, u.open_employeeid AS employeeno,
						concat(u.firstname ,' ',u.lastname) AS employeename,u.email, u.open_costcenterid,u.open_departmentid,
						c.category,u.open_designation, u.open_grade,u.open_careertrack,u.open_location,
						c.id AS courseid,c.fullname AS course,c.shortname,u.suspended,c.open_identifiedas,c.open_courseprovider,
						c.open_points,c.open_ouname,c.open_url,c.autoenrol,c.selfenrol,c.timecreated,
						c.duration,c.visible,cc.timecompleted AS completionstatus, cc.timecompleted AS completiondate,ue.timecreated AS enrolleddate,c.open_skill,c.open_skillcategory,c.open_level,
						(SELECT concat(firstname ,' ',lastname) 
							FROM {user} 
							WHERE id = u.open_supervisorid) AS supervisorname
						FROM {user} u 
						JOIN {role_assignments} as ra ON ra.userid = u.id
						JOIN {context} AS cxt ON cxt.id = ra.contextid 
												AND cxt.contextlevel = 50
						JOIN {course} c ON c.id = cxt.instanceid
						JOIN {course_categories} cat ON cat.id = c.category
                        JOIN {role} as r ON r.id = ra.roleid
                        LEFT JOIN {course_completions} as cc ON cc.course = c.id AND u.id = cc.userid
                         JOIN {enrol} as e ON e.courseid=c.id
                        JOIN {user_enrolments} as ue ON ue.enrolid=e.id AND ue.userid=u.id
                        WHERE u.deleted = 0 AND c.id <> 1 ";			 
					
				if($courseid > 0){
					$sql .= " AND c.id = $courseid ";
				}

				if($userid > 1 ){
					$sql .= " AND u.id = $userid ";
				}

				if($emp_status == 1){
					$sql .= " AND u.suspended = 0 ";
				}elseif($emp_status == 2){
					$sql .= " AND u.suspended = 1 ";
				}

				if($category > 0){
					$sql.= " AND c.category = $category ";
				}

				if ($completionstatus == 1) {
		           		  $sql.= " AND cc.timecompleted IS NOT NULL ";
		        		}

		      		  if ($completionstatus == 2) {
		            		  $sql.= " AND cc.timecompleted IS NULL ";
		       		 }

				if(!empty($filter_starttime)){
					$start_year=$filter_starttime['year'];
					$start_month=$filter_starttime['month'];
					$start_day=$filter_starttime['day'];
					$start_hour=$filter_starttime['hour'];
					$start_minute=$filter_starttime['minute'];
					$start_second=0;
					$filter_starttime_con=mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
					$sql.=" and ue.timecreated >= '$filter_starttime_con' ";
				}
				if(!empty($filter_endtime)){
					$end_year=$filter_endtime['year'];
					$end_month=$filter_endtime['month'];
					$end_day=$filter_endtime['day'];
					$end_hour=$filter_endtime['hour'];
					$end_minute=$filter_endtime['minute'];
					$end_second=0;
					$filter_endtime_con=mktime($end_hour, $end_minute, 0, $end_month, $end_day, $end_year);
					$sql.= " and ue.timecreated <= '$filter_endtime_con' ";
				}
					
				$sql .= " GROUP BY c.id, u.id ";
                                               //    echo $sql;
           				 if($filters_empty == 'exportall'){
                      				$courseusers = $DB->get_records_sql($sql);
             				$count = 0;

                                                      } else {
                      				$courseusers = $DB->get_records_sql($sql);

                                                       }
				//$courseusers = $DB->get_records_sql($sql);
				if($courseusers){
					foreach($courseusers as $enroluser){
						$manger = new stdClass();
				
						if(empty($enroluser->open_grade) || $enroluser->open_grade == -1){
							$manger->grade = get_string('all');
						}else{
							$manger->grade = $enroluser->open_grade;
						}
						
						$department = $DB->get_field('local_costcenter', 'fullname', array('id'=>$enroluser->open_departmentid));
						if(empty($department)){
							$manger->department = 'NA';
						}else{
							$manger->department = $department;
						}
						
						if(empty($enroluser->open_location)){
							$manger->employeelocation = get_string('all');
						}else{
							$manger->employeelocation = $enroluser->open_location;
						}

						if(empty($enroluser->open_identifiedas)){
							$manger->course_type = 'NA';
						}else{
							$manger->course_type = $DB->get_field('local_course_types','course_type',array('active' =>1,'id' => $enroluser->open_identifiedas)); 
						}

						if($enroluser->autoenrol != 0){
							$manger->autoenrol = 'Yes';
						}else{
							$manger->autoenrol = 'No';
						}

						if($enroluser->selfenrol != 0){
							$manger->selfenrol = 'Yes';
						}else{
							$manger->selfenrol = 'No';
						}
						if($enroluser->open_ouname == '-1'){
							$manger->ouname = 'ALL';
						}else if($enroluser->open_ouname != NULL){
							$manger->ouname = $enroluser->open_ouname;
						}else{
							$manger->ouname = 'NA';
						}


						if($enroluser->open_url != 0 || $enroluser->open_url != NULL){
							$manger->url = $enroluser->open_url;
						}else{
							$manger->url = 'NA';
						}
						
						if(empty($enroluser->open_careertrack)){
							$manger->career_track = 'NA';
						}else{
							$manger->career_track = $enroluser->open_careertrack;
						}

						if($enroluser->open_courseprovider != 0){
							$manger->learnerprovider = $DB->get_field('local_course_providers','course_provider',array('id' => $enroluser->open_courseprovider));
						}else{
							$manger->learnerprovider = 'NA';
						}
						
						$manger->courseid = $enroluser->courseid;
						$manger->coursename = $enroluser->course;
						$manger->course_shortname = $enroluser->shortname;
						$manger->employeeid = $enroluser->employeeno;
						$manger->email = $enroluser->email;

						if($enroluser->duration){
							$hours = floor($enroluser->duration/3600);
							$minutes = ($enroluser->duration/60)%60;
							$manger->duration =$hours.':'. $minutes;
						}else{
							$manger->duration = 'NA';
						}
						
						if($enroluser->suspended == 0){
							$manger->employee_status = 'Active';
						}else{
							$manger->employee_status = 'Inactive';
						}
						$manger->employeename = $enroluser->employeename;
						if($enroluser->supervisorname){
							$manger->supervisorname = $enroluser->supervisorname;
						}else{
							$manger->supervisorname = "NA";
						}
						
						if(!empty($enroluser->open_designation)){
							$manger->designation = $enroluser->open_designation;
						}else{
							$manger->designation = 'NA';
						}
						$manger->category = $DB->get_field('course_categories','name',array('id'=>$enroluser->category));
						
						if($enroluser->completionstatus){
							$manger->completionstatus  = 'Completed';
							$manger->completiondate =  date('d-m-Y',$enroluser->completiondate);
							$manger->credits = $enroluser->open_points;
						}else{
							$manger->completionstatus = 'Not Completed';
							$manger->completiondate = "--";
							$manger->credits = "--";
						}
						if($enroluser->enrolleddate){
							
							$manger->enrolleddate =  date('d-m-Y',$enroluser->enrolleddate);
							
						}else{
							
							$manger->enrolleddate = "--";
							
						}
						$open_skillcategory = $DB->get_field('local_skill_categories', 'name', array('id'=>$enroluser->open_skillcategory));
						if(empty($open_skillcategory)){
							$manger->open_skillcategory = 'NA';
						}else{
							$manger->open_skillcategory = $open_skillcategory;
						}
						$open_skill = '';
						if($enroluser->open_skill){
							$sql = "SELECT id,name
				                        FROM {local_skill}
				                        WHERE id IN ($enroluser->open_skill)";
                                                                    // echo $sql;
				        	$open_skill = $DB->get_records_sql_menu($sql);
						}
						
				    	if(empty($open_skill)){
							$manger->open_skill = 'N/A';
						}else{
							$manger->open_skill = implode(', ', $open_skill);
						}
						
						$open_level = $DB->get_field('local_levels', 'name', array('id'=>$enroluser->open_level));
						if(empty($open_level)){
							$manger->open_level = 'N/A';
						}else{
							$manger->open_level = $open_level;
						}

						$course_url = $CFG->wwwroot.'/course/view.php?id='.$enroluser->courseid;
						$manger->course_url = '<a href='.$CFG->wwwroot.'/course/view.php?id='.$enroluser->courseid.' target="_blank">'.$course_url.'</a>';						
						if($enroluser->timecreated){
							$manger->createdon = date('d-m-Y', $enroluser->timecreated);
						}else{
							$manger->createdon = '-';
						}
						$manger->coursetotal = $this->get_course_total($enroluser->courseid, $enroluser->userid);
						if($enroluser->visible == 1){
							$manger->course_status = 'Visible';
						}else{
							$manger->course_status = 'Invisible';
						}
						$reportarray[] = $manger;
					}
				}
			//}
                     //                 print_object($reportarray);
        			return array('data' => $reportarray,'count' => $count);

		}	
		
		return $finalelements;
	}
	
	
	function get_course_total($courseid, $userid){
		global $DB;
		
		$sql = "SELECT gg.finalgrade
				FROM {grade_grades} gg
				JOIN {grade_items} gi ON gg.itemid = gi.id
				WHERE gi.itemtype = 'course' AND gi.courseid = $courseid AND gg.userid = $userid";
		
		$course_grades = $DB->get_record_sql($sql);
		if($course_grades){
			return round($course_grades->finalgrade, 2);
		}else{
			return 'NA';
		}
	}
}
