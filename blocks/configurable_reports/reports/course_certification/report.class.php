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
 * Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @subpackage Configurable Reports
 * @copyright  2019 Eabyas Info Solutions <www.eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class report_course_certification extends report_base {

	function init(){
		$this->components = array('columns','filters','permissions','calcs');
	}
	public function get_all_elements() {
        global $DB;

        $elements = array();
        $elements = $DB->get_fieldset_select('course', 'id', null, null);
        
        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$category = optional_param('filter_categories','', PARAM_INT);
			$courseid = optional_param('filter_courses',NULL, PARAM_INT);
			$completionstatus = optional_param('filter_coursestatus', 0, PARAM_INT);
			$emp_status = optional_param('filter_emp_status', null, PARAM_INT);
		    $userid = optional_param('filter_users','', PARAM_INT);
		    $learnertype = optional_param('filter_coursetype','',PARAM_INT);
		    $filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
            $filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
			
			$reportarray = array();
		    
		    if(!empty($category) || !empty($courseid) || !empty($completionstatus) || !empty($emp_status) || !empty($userid) || !empty($learnertype) || !empty($filter_starttime) || !empty($filter_endtime)){
		    
		     $sql = "SELECT concat(cm.id,'_',ue.id) AS identifier, cm.id as coursemoduleid,u.id AS userid,c.id as courseid,c.fullname as course, u.open_employeeid AS employeeno,
						concat(u.firstname ,' ',u.lastname) AS employeename,u.email,
						u.open_costcenterid,u.open_departmentid,u.open_designation, u.open_grade,
						  u.suspended,c.open_identifiedas,c.open_points,c.duration,c.visible,c.open_courseprovider,c.open_ouname,c.open_url,c.autoenrol,c.selfenrol,
						  cc.timecompleted AS completionstatus, cc.timecompleted AS completiondate,c.open_skill,c.open_skillcategory,c.open_level,
					  asu.timecreated as assignmentuploadstartdate,asu.timemodified AS assignmentuploaddate, asu.status AS assignmentstatus,gi.itemname AS assignment,
					  (SELECT concat(firstname ,' ',lastname) 
							FROM {user} 
							WHERE id = u.open_supervisorid) AS supervisorname
						FROM {user_enrolments} ue
					    JOIN {enrol} e ON e.id = ue.enrolid  
					    JOIN {course} c ON c.id = e.courseid
					    JOIN {course_categories} cat ON cat.id = c.category
					    JOIN {course_modules} cm ON cm.course = c.id
					    JOIN {modules} AS m ON m.id = cm.module AND m.name LIKE 'assign'
					    JOIN {user} u ON u.id = ue.userid
				        JOIN {grade_items} AS gi ON gi.itemmodule LIKE 'assign' AND gi.itemtype LIKE 'mod' AND gi.iteminstance = cm.instance
				        JOIN {assign} a ON a.id = cm.instance
                        LEFT JOIN {assign_submission} asu ON asu.assignment = a.id AND asu.userid = ue.userid   
					    LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = u.id
					    WHERE u.id > 2 AND u.deleted = 0  ";//AND (c.open_identifiedas = 1 OR c.open_identifiedas = 2 OR c.open_identifiedas = 5 OR c.open_identifiedas = 6 OR c.open_identifiedas = 7)

                if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		            $sql .= "";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
		            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid ";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
		            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid 
		            		AND c.open_departmentid = $USER->open_departmentid";
		        }else{
		            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid 
		            		AND c.open_departmentid = $USER->open_departmentid";
		        }

				if($category > 0){
					$sql .= " AND c.category = $category ";
				}
 
                if($courseid > 0){
					$sql .= " AND c.id = $courseid ";
				}
                 
                if ($completionstatus == 1) {
		           $sql.= " AND cc.timecompleted IS NOT NULL ";
		        }

		        if ($completionstatus == 2) {
		            $sql.= " AND cc.timecompleted IS NULL ";
		        }

		        if($emp_status == 1){
					$sql .= " AND u.suspended = 0 ";
				}else if($emp_status == 2){
					$sql .= " AND u.suspended = 1 ";
				}

                if($userid > 1){
					$sql .= " AND u.id = $userid ";
				}

			    if($learnertype > 0){
			       $sql .= "AND c.open_identifiedas = $learnertype ";
			    }

			    if(!empty($filter_starttime)){
					$start_year=$filter_starttime['year'];
					$start_month=$filter_starttime['month'];
					$start_day=$filter_starttime['day'];
					$start_hour = 0;
					$start_minute = 0;
				    $start_second=0;
					$filter_starttime_assignment = mktime(0, 0, 0, $start_month, $start_day, $start_year);
				    $sql.=" AND asu.timemodified <= '$filter_starttime_assignment' ";
				}
				
				if(!empty($filter_endtime)){
					$end_year=$filter_endtime['year'];
					$end_month=$filter_endtime['month'];
					$end_day=$filter_endtime['day'];
					$end_hour = 0;
					$end_minute = 0;
					$end_second= 0;
					$filter_endtime_assignment = mktime(0, 0, 0, $end_month, $end_day, $end_year);
					$sql.=" AND asu.timemodified  >= '$filter_endtime_assignment' ";
				}
                
                $sql .= " GROUP BY cm.id, u.id ";
				
				$courseusers = $DB->get_records_sql($sql);

                
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
						if($enroluser->supervisorname){
							$manger->supervisorname = $enroluser->supervisorname;
						}else{
							$manger->supervisorname = "NA";
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
						
						$courseurl = new moodle_url('/course/view.php',array('id'=>$enroluser->courseid));             
						$courseurl = $courseurl->out(false); 

						$manger->courseid = $enroluser->courseid;
						$manger->coursename = $enroluser->course;
						$manger->employeeid = $enroluser->employeeno;
						$manger->email = $enroluser->email;
						$manger->assignmentname = $enroluser->assignment;
						$manger->courseurl = $courseurl;
						if($enroluser->duration){
							$hours = floor($enroluser->duration/3600);
							$minutes = ($enroluser->duration/60)%60;
							$manger->duration =$hours.':'. $minutes;
						}else{
							$manger->duration = 'NA';
						}
						
						if($enroluser->suspended == 0){
							$manger->employee_status = get_string('active','block_configurable_reports');
						}else{
							$manger->employee_status = get_string('inactive','block_configurable_reports');
						}
						$manger->employeename = $enroluser->employeename;

						if(!empty($enroluser->open_designation)){
							$manger->designation = $enroluser->open_designation;
						}else{
							$manger->designation = 'NA';
						}
						 
						 $manger->coursecategory = $enroluser->category;

						  
						if($enroluser->open_courseprovider != 0){
						 $manger->learnerprovider = $DB->get_field('local_course_providers','course_provider',array('id' => $enroluser->open_courseprovider));
						}else{
							$manger->learnerprovider = 'NA';
						}
 
						 if($enroluser->assignmentstatus == 'submitted'){
						 	$manger->uploaddate = date('d-m-Y',$enroluser->assignmentuploadstartdate);
						 }else{
						 	$manger->uploaddate = 'NA';
						 }

						 if($enroluser->assignmentstatus == 'submitted'){
							$manger->resubmissiondate = date('d-m-Y',$enroluser->assignmentuploaddate);
						}else{
							$manger->resubmissiondate = 'NA';
						}
						 
						if($enroluser->completionstatus){
							$manger->completionstatus  = get_string('completed','block_configurable_reports');
							$manger->completiondate =  date('d-m-Y',$enroluser->completiondate);
							$manger->credits = $enroluser->open_points;
						}else{
							$manger->completionstatus = get_string('not_completed','block_configurable_reports');
							$manger->completiondate = "--";
							$manger->credits = 'NA';
 
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

						$approverid = $DB->get_field('course_modules_completion', 'overrideby', array('coursemoduleid'=>$enroluser->coursemoduleid, 'userid' => $enroluser->userid));
						if(!empty($approverid )){
							$manger->approvername = $DB->get_field('user', 'concat(firstname ," ",lastname) AS employeename', array('id'=>$approverid));
						}else{
							$manger->approvername = 'N/A';
						}				


						$open_level = $DB->get_field('local_levels', 'name', array('id'=>$enroluser->open_level));
						if(empty($open_level)){
							$manger->open_level = 'N/A';
						}else{
							$manger->open_level = $open_level;
						}
                        if($enroluser->visible == 1){
							$manger->course_status = get_string('visible','block_configurable_reports');
						}else{
							$manger->course_status = get_string('invisible','block_configurable_reports');
						}
						$reportarray[] = $manger;
					}
				}
			}
			return $reportarray;
		}	
		
		return $finalelements;
	}
}
 
			  
			 
		 
