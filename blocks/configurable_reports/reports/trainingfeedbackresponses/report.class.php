<?php
class report_trainingfeedbackresponses extends report_base{
	
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

		$filter_classrooms = optional_param('filter_classrooms', NULL, PARAM_INT);
		$filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
		$filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
		if(!empty($elements)){

			if(!empty($filter_classrooms) || !empty($filter_starttime) || !empty($filter_endtime)){

				$sql = "SELECT lec.id as lecid, lec.userid as lecuserid, le.id as evalid, lc.id as classroomid,lc.name as classroomname, lc.startdate, lc.enddate, lc.instituteid,
					u.firstname, u.lastname, u.open_employeeid, c.fullname
					FROM {local_evaluation_completed} lec
					JOIN {local_classroom} lc ON lec.evaluation = lc.id
					JOIN {local_evaluations} le ON le.instance = lc.id AND le.plugin = 'classroom'
					JOIN {user} u ON u.id = lec.userid
					LEFT JOIN {local_classroom_courses} lcc ON lcc.classroomid = lc.id
					LEFT JOIN {course} c ON c.id = lcc.courseid 
					WHERE le.evaluationtype = 1 ";

				if($filter_classrooms > 0){
					$sql.=" AND  lc.id = $filter_classrooms  ";
				}
				if(!empty($filter_starttime)){
					$start_year=$filter_starttime['year'];
					$start_month=$filter_starttime['month'];
					$start_day=$filter_starttime['day'];
					$filter_starttime_con=mktime(0, 0, 0, $start_month, $start_day, $start_year);
					$sql.=" and lc.startdate >= '$filter_starttime_con' ";
				}
				if(!empty($filter_endtime)){
					$end_year=$filter_endtime['year'];
					$end_month=$filter_endtime['month'];
					$end_day=$filter_endtime['day'];
					$filter_endtime_con=mktime(23, 59, 59, $end_month, $end_day, $end_year);
					$sql.=" and lc.enddate <= '$filter_endtime_con' ";
				}
			   
				$sql .= ' ORDER BY lc.id DESC ';

				$records =$DB->get_records_sql($sql, null, $strictness=null);
				
				$reportarray = array();
				if($records){
					foreach($records as $record) {
						$columns = new stdClass();
						
						$columns->respondedempid = !empty($record->open_employeeid) ? $record->open_employeeid : 'NA';
						$columns->coursename = !empty($record->fullname) ? $record->fullname : 'NA';
						$columns->iltname = $record->classroomname;
						$columns->iltstartenddates = date('d-m-Y', $record->startdate).' to '.date('d-m-Y', $record->enddate);
						$columns->month = date('F', $record->startdate);
						$columns->year = date('Y', $record->enddate);

						$sql = "SELECT clt.id, CONCAT(u.firstname,' ',u.lastname) as trainer
								FROM {local_classroom_trainers} clt
								JOIN {user} u ON u.id = clt.trainerid
								WHERE u.deleted = 0 AND clt.classroomid = $record->classroomid";

						$ptrianers = $DB->get_records_sql_menu($sql);
						if($ptrianers){
							$columns->trainers = implode(',', $ptrianers);
						}else{
							$columns->trainers = 'NA';
						}
						
						if($record->instituteid){
							$iltlocation = $DB->get_field('local_classroom_institutes', 'fullname', array('id'=>$record->instituteid));
							$columns->iltlocation = $iltlocation;
						}else{
							$columns->iltlocation = 'NA';
						}

						$arrays = array();
						$sql = "SELECT lev.id, lev.value 
								FROM {local_evaluation_value} lev 
								WHERE lev.id = $record->lecid ";
						$evaluationusers = $DB->get_records_sql($sql);
						foreach ($evaluationusers as $evaluationuser) {
							$arrays[] = $evaluationuser->value;
						}
						if($arrays){
							$columns->response1 = ($arrays[0])? $arrays[0]: '-';
							$columns->response2 = ($arrays[1])? $arrays[1]: '-';
							$columns->response3 = ($arrays[2])? $arrays[2]: '-';
							$columns->response4 = ($arrays[3])? $arrays[3]: '-';
							$columns->response5 = ($arrays[4])? $arrays[4]: '-';
							$columns->response6 = ($arrays[5])? $arrays[5]: '-';
							$columns->comment1 =  ($arrays[6])? $arrays[6]: '-';
							$columns->comment2 =  ($arrays[7])? $arrays[7]: '-';
						}else{
							$columns->response1 = '-';
							$columns->response2 = '-';
							$columns->response3 = '-';
							$columns->response4 = '-';
							$columns->response5 = '-';
							$columns->response6 = '-';
							$columns->comment1 = '-';
							$columns->comment2 = '-';
						}
						$reportarray[] = $columns;
					}
					return $reportarray;
				}
			}
		}
	}

	function get_training_feedbackanswer($answer){
		switch ($answer) {
			case 1:
				$ans = '1/ Strongly Disagree';
				break;
			case 2:
				$ans = '2/ Disagree';
				break;
			case 3:
				$ans = '3/ Neutral';
				break;
			case 4:
				$ans = '4/ Agree';
				break;
			case 5:
				$ans = '5/ Strongly Agree';
				break;
			default:
				$ans = '-';
				break;
		}
		return $ans;
	}
}

