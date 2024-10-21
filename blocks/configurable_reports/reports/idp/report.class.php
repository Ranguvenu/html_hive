<?php

require_once($CFG->dirroot.'/local/lib.php');
class report_idp extends report_base{
	
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
	
	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();

		if(!empty($elements)){			
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$userid = optional_param('filter_users','', PARAM_INT);
			$emp_status = optional_param('filter_emp_status', null, PARAM_INT);
			$startdate = optional_param('filter_starttime', '', PARAM_RAW);
			$enddate = optional_param('filter_endtime', '', PARAM_RAW);
			if($userid || $startdate || $emp_status){
				$sql = "SELECT lip.*, u.firstname, u.lastname, u.open_employeeid, u.email, u.open_departmentid, u.suspended,u.open_supervisorid, u.open_designation, u.open_grade, u.open_careertrack, u.open_location, u.suspended, lip.courseid, lip.targetdate, lip.timecreated, lip.timemodified
					FROM {local_ilp} lip
					JOIN {user} u ON lip.userid = u.id
                    WHERE  u.deleted = 0 ";
				if($userid > 0){
					$sql .= " AND lip.userid = $userid ";
				}
				if(!empty($startdate) && !empty($enddate)){
					$day=$startdate[day];
					$month=$startdate[month];
					$year=$startdate[year];
					$date= $day."-".$month."-".$year;
					$filter_starttime_con=strtotime($date);
					
					$end_day=$enddate[day];
					$end_month=$enddate[month];
					$end_year=$enddate[year];
					$end_date= $end_day."-".$end_month."-".$end_year;
					$filter_enddate_con=strtotime($end_date." 23:59:59");
					$sql.=" and lip.timecreated >='$filter_starttime_con' and
							 lip.timecreated <= '$filter_enddate_con'";
				}
			
				if($emp_status == 1){
					$sql.=" AND u.suspended = 0 ";
				}elseif($emp_status == 2){
					$sql.=" AND u.suspended = 1 ";
				}

				$idp_records = $DB->get_records_sql($sql);
				
				$reportarray = array();
			
				if($idp_records){
					foreach($idp_records as $idp_record){
					
						$idpdata = new stdClass();
						
						$idpdata->employeename = $idp_record->firstname.' '.$idp_record->lastname;
						if($idp_record->suspended == 0){
							$idpdata->employee_status = 'Active';
						}else{
							$idpdata->employee_status = 'Inactive';
						}
						
						$targetdate = !empty($idp_record->targetdate) ? date('d-m-Y',$idp_record->targetdate) : 'NA';
						$idpdata->target_date = $targetdate;
						
						if($idp_record->open_employeeid){
							$idpdata->employeeid = $idp_record->open_employeeid;
						}else{
							$idpdata->employeeid = 'NA';
						}
						
						$idpdata->email = $idp_record->email;
						
						$idpdata->created_on = date('d-m-Y', $idp_record->timecreated);
						
						if($idp_record->timemodified){
							$idpdata->modified_on = date('d-m-Y', $idp_record->timemodified);
						}else{
							$idpdata->modified_on = 'N/A';
						}
						
						if(empty($idp_record->open_grade) || $idp_record->open_grade == -1){
							$idpdata->grade = get_string('all');
						}else{
							$idpdata->grade = $idp_record->open_grade;
						}

						if($idp_record->open_departmentid){
							$dept = $DB->get_field('local_costcenter', 'fullname', array('id'=>$idp_record->open_departmentid));
							$idpdata->department = $dept;
						}else{
							$idpdata->department = 'N/A';
						}

						if(empty($idp_record->open_careertrack)){
							$idpdata->career_track = 'N/A';
						}else{
							$idpdata->career_track = $idp_record->open_careertrack;
						}
						
						
						if($idp_record->open_supervisorid){
							$supervisor = $DB->get_record('user', array('id'=>$idp_record->open_supervisorid));
							$idpdata->supervisorname = $supervisor->firstname.' '.$supervisor->lastname; 
						}else{
							$idpdata->supervisorname = "NA";
						}
						
						if(!empty($idp_record->open_designation)){
							$idpdata->designation = $idp_record->open_designation;
						}else{
							$idpdata->designation = 'NA';
						}

						if($idp_record->courseid > 0){
							$fields = 'id,fullname,open_identifiedas, open_points';
							$courseinfo = $DB->get_record('course', array('id'=>$idp_record->courseid),$fields);
							if($courseinfo){
								$idpdata->coursename = $courseinfo->fullname;

								if($courseinfo->open_points >= 0){
									$idpdata->credits = round($courseinfo->open_points,2);
								}else{
									$idpdata->credits = 'NA';
								}

								$sql = "SELECT timecompleted
										FROM {course_completions}
										WHERE course = $idp_record->courseid AND
											userid = $idp_record->userid AND timecompleted IS NOT NULL";
								$completiontime = $DB->get_field_sql($sql);
							
								if($completiontime){
									$idpdata->coursestatus = 'Completed';
									$idpdata->completiondate = date('d-m-Y', $completiontime);
								}else{
									$idpdata->coursestatus = ' Not Completed';
									$idpdata->completiondate = 'NA';
								}
								if(empty($courseinfo->open_identifiedas)){
									$idpdata->course_type = 'NA';
								}else{
									$idpdata->course_type = $DB->get_field('local_course_types','course_type',array('active' =>1,'id' => $courseinfo->open_identifiedas)); 
								}
								
							/* 	if(empty($courseinfo->open_identifiedas)){
									$idpdata->course_type = 'NA';
								}elseif($courseinfo->open_identifiedas == 1){
									$idpdata->course_type = "Mooc";
								}elseif($courseinfo->open_identifiedas == 2){
									$idpdata->course_type = "ILT";
								}elseif($courseinfo->open_identifiedas == 3){
									$idpdata->course_type = "E-Learning";
								}elseif($courseinfo->open_identifiedas == 4){
									$idpdata->course_type = "Learning Path";
								}elseif($courseinfo->open_identifiedas == 5){
									$idpdata->course_type = "Program";
								}elseif($courseinfo->open_identifiedas == 6){
									$idpdata->course_type = "Certification";
								} */
							}else{
								$idpdata->coursename = 'NA';
								$idpdata->coursestatus = 'NA';
								$idpdata->completiondate = 'NA';
								$idpdata->course_type = 'NA';
								$idpdata->credits = 'NA';
							}
						}else{
							$idpdata->coursename = 'NA';
							$idpdata->coursestatus = 'NA';
							$idpdata->completiondate = 'NA';
							$idpdata->course_type = 'NA';
							$idpdata->credits = 'NA';
						}

						$reportarray[] = $idpdata;
					}
				}
			
				return $reportarray;
			}
			return $finalelements;
		}
	}
}
