<?php
class report_trainernps extends report_base{
	
	function init(){
		$this->components = array('columns','filters','permissions');
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
			
			$filter_classroom = optional_param('filter_classroom', '', PARAM_RAW);
			$filter_programme = optional_param('filter_courses', '', PARAM_RAW);
			$filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
			$filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
			
			$sql="SELECT lct.id, lct.trainerid, lc.id classroomid, lc.name as classroomname,lc.shortname as classroomcode, lc.startdate, lc.enddate,lc.timecreated, lc.timemodified, lc.status,  lc.enrolled_users,  lc.instituteid,
					MONTHNAME(FROM_UNIXTIME(lc.startdate)) monthname,
					(SELECT COUNT(userid) 
						FROM {local_classroom_users}
						WHERE classroomid = lc.id) AS enrolledusers,
					(SELECT COUNT(userid) 
						FROM {local_classroom_users}
						WHERE classroomid = lc.id and completion_status=1 ) AS completedusers
					FROM {local_classroom} as lc
					JOIN {local_classroom_trainers} lct ON lct.classroomid = lc.id
					WHERE 1=1 ";

				if($filter_classroom > 0){
					$sql .= " AND  lc.id = $filter_classroom  ";
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
			$sql.=" order by lc.id DESC";

			$records = $DB->get_records_sql($sql);

			$reportarray = array();
		
			foreach($records as $record){
					
				$columns = new stdClass();
				$columns->iltname = $record->classroomname;
				$columns->iltcode = $record->classroomcode;
				$columns->month  =$record->monthname;
				$columns->year  =date('Y', $record->startdate);
				switch($record->status){
					case 0:
						$columns->iltstatus = get_string('newclasses', 'local_classroom');
						break;
					case 1:
						$columns->iltstatus = get_string('activeclasses', 'local_classroom');
						break;
					case 2:
						$columns->iltstatus = get_string('holdclasses', 'local_classroom');
						break;
					case 3:
						$columns->iltstatus = get_string('cancelledclasses', 'local_classroom');
						break;
					case 4:
						$columns->iltstatus = get_string('completedclasses', 'local_classroom');
						break;
					default:
						$columns->iltstatus = 'None';
				}

				$columns->iltstartenddates = date('d-m-Y', $record->startdate).' to '.date('d-m-Y', $record->enddate);
				$columns->enrolledusers   =   $record->enrolledusers;
				$columns->completedusers   =   $record->completedusers;

				if ($record->trainerid) {
					$trainer_record = $DB->get_record('user', array('id'=>$record->trainerid));
					$columns->trainer    = fullname($trainer_record);
					$columns->trainercode    = $trainer_record->open_employeeid;
					if($record->instituteid){
						$location = $DB->get_field('local_facetoface_institutes', 'fullname', array('id'=>$record->instituteid));
						$columns->location = $location;
					}else{
						$columns->location = 'NA';
					}

					// $sql="SELECT COUNT(lev.id)
					// 		FROM {local_evaluations} le
					// 		JOIN {local_evaluation_completed} lec ON lec.evaluation = le.id AND le.plugin = 'classroom' AND le.evaluationtype = 2
					// 		JOIN {local_evaluation_value} lev ON lev.completed = lec.id
					// 		WHERE le.id = $record->classroomid AND lev.value IN (9,10) ";
					// $promotorssql = ' AND lev.value IN (9,10) ';
					// $detractorssql = ' AND lev.value IN (0,1,2,3,4,5,6) ';
					// $passivesql = ' AND lev.value IN (7,8) ';
					
					// $sql="SELECT count(id) promoters 
					// 		FROM {local_trainerfeedback} 
					// 		where  nps_score in(9,10) and batchid={$record->batchid}
					// 		and trainerid = {$record->trainerid}";
					// $promoters=$DB->get_field_sql($sql);

					
					// $sql="SELECT count(id) passive FROM {local_trainerfeedback} where  nps_score in(7,8) and batchid={$record->batchid} and trainerid={$record->trainerid}";
					// $passive=$DB->get_field_sql($sql);

					

					// $sql="SELECT count(id) detracto FROM {local_trainerfeedback} where nps_score in(0,1,2,3,4,5,6) and batchid={$record->batchid} and trainerid={$record->trainerid}";
					// $detractors =$DB->get_field_sql($sql);
				
					// $sql="SELECT count(id) total FROM {local_trainerfeedback} where batchid={$record->batchid} and trainerid={$record->trainerid} ";
					// $total=$DB->get_field_sql($sql);
					// $total = $promoters + $passive + $detractors;
					// $perpromoters= $promoters/$total;
					// $perdetractors= $detractors/$total;
				}
				// $nps = round((($perpromoters - $perdetractors) * 100), 2);
				// $columns->nps12 = $detractors;
				// $columns->nps3 = $passive;
				// $columns->nps45 = $promoters;
				// $columns->nps = $nps;
				// $all_pro_dem_pass = $detractors + $passive + $promoters;
				// $columns->totalresponses = $all_pro_dem_pass;

				$reportarray[] = $columns;
			}
			
			return $reportarray;
		}		
		return $finalelements;
	}
	
}
