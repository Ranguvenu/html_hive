<?php
class report_trainerfeedbackresponses extends report_base{
	
	function init(){
		$this->components = array('columns','filters','permissions');
	}	
	function get_all_elements(){
		global $DB;
		
		$elements = array();
		$rs = $DB->get_recordset('local_classroom', null, '', 'id');
        foreach ($rs as $result) {
			$elements[] = $result->id;
		}
		$rs->close();
		return $elements;
	}
	
	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		
		$filter_classrooms = optional_param('filter_classrooms', '', PARAM_RAW);
		$emp_status = optional_param('filter_emp_status', null, PARAM_INT);
		$filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
		$filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
		if(!empty($elements)){
			if(!empty($filter_classrooms) || !empty($filter_starttime) || !empty($filter_endtime)){
				$sql = "SELECT lct.id, le.id as evalid, lct.trainerid, lc.id as classroomid,
						lc.name as classroomname,lc.startdate, lc.enddate, lc.instituteid, c.fullname,
						u.firstname, u.lastname, u.open_employeeid
						FROM {local_classroom} lc
						JOIN {local_classroom_trainers} lct ON lct.classroomid = lc.id
						JOIN {local_evaluations} le on le.instance = lc.id AND le.plugin = 'classroom'
						JOIN {user} u ON u.id = lct.trainerid
						LEFT JOIN {local_classroom_courses} lcc ON lcc.classroomid = lc.id
						LEFT JOIN {course} c ON c.id = lcc.courseid
						WHERE le.evaluationtype = 2 ";

			    if($filter_classrooms > 0){	
					$sql .= " AND  lc.id = $filter_classrooms  ";
			    }

				if(!empty($filter_starttime)){
					$start_year=$filter_starttime['year'];
					$start_month=$filter_starttime['month'];
					$start_day=$filter_starttime['day'];
					$filter_starttime_con = mktime(0, 0, 0, $start_month, $start_day, $start_year);
					$sql.=" and lc.startdate >= '$filter_starttime_con' ";
				}
				if(!empty($filter_endtime)){
					$end_year=$filter_endtime['year'];
					$end_month=$filter_endtime['month'];
					$end_day=$filter_endtime['day'];
					$filter_endtime_con=mktime(23, 59, 59, $end_month, $end_day, $end_year);
					$sql.=" and lc.enddate <= '$filter_endtime_con' ";
				}
				   
				$sql .= ' ORDER BY lct.trainerid ';
				// echo $sql;
				// exit;
				$records =$DB->get_records_sql($sql, null, $strictness=null);

				$reportarray = array();
				if($records){
					foreach($records as $record) {
						$rsql = "SELECT lec.id, lec.userid, u.firstname, u.lastname,
								u.open_employeeid, u.firstname, u.lastname
								FROM {local_classroom_users} lcu
								JOIN {local_evaluation_completed} lec ON lec.userid = lcu.userid
								JOIN {user} u ON lec.userid = u.id AND u.deleted = 0 
								WHERE lcu.classroomid = $record->classroomid AND
								 lec.evaluation = $record->evalid ";

						if($emp_status == 1){
							$rsql.=" AND u.suspended = 0 ";
						} elseif($emp_status == 2){
							$rsql.=" AND u.suspended = 1 ";
						}
							
						$evaluationusers = $DB->get_records_sql($rsql);
						
						if($evaluationusers){
							foreach ($evaluationusers as $evaluationuser ) {
								$columns = new stdClass();

								$columns->trainername = $record->firstname.' '.$record->lastname;
								$columns->trainerempid = $record->open_employeeid;
								
								if($evaluationuser->open_employeeid){
									$columns->respondedempid = $evaluationuser->open_employeeid;
								}else{
									$columns->respondedempid = 'NA';
								}
								
								if($record->suspended == 0){
									$columns->employee_status = 'Active';
								}else{
									$columns->employee_status = 'Inactive';
								}
								$columns->coursename = !empty($record->fullname) ? $record->fullname : 'NA';
								$columns->iltname = $record->classroomname;
								$columns->iltstartenddates = date('d-m-Y', $record->startdate).' to '.date('d-m-Y', $record->enddate);
								$columns->month = date('F',$record->startdate);
								$columns->year = date('Y',$record->enddate);
								if($record->instituteid){
									$columns->iltlocation = $DB->get_field('local_location_institutes', 'fullname', array('id'=>$record->instituteid));
								}else{
									$columns->iltlocation = 'NA';
								}
								$eachvalues = $DB->get_records('local_evaluation_value', array('completed'=>$evaluationuser->id));
								$arrays = array();
								foreach($eachvalues as $eachvalue) {
									$arrays[] = $eachvalue->value;
								}
								$columns->response1 = ($arrays[0])? $arrays[0]: '-';
								$columns->response2 = ($arrays[1])? $arrays[1]: '-';
								$columns->response3 = ($arrays[2])? $arrays[2]: '-';
								$columns->response4 = ($arrays[3])? $arrays[3]: '-';
								$columns->response5 = ($arrays[4])? $arrays[4]: '-';

								$reportarray[] = $columns;
							}
						}
					}
					return $reportarray;
				}
			}
		}
	}
}

