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
 * @subpackage blocks_configurable_reports
 */
class report_classroom_summary extends report_base{
	
	function init(){
		$this->components = array('columns','filters','permissions');
	}	
	function get_all_elements(){
		global $DB;
		
		$elements = array();
		$sql = "SELECT id
				FROM {local_classroom}";
		$elements = $DB->get_fieldset_sql($sql);

		return $elements;
	}
	
	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		
		$finalelements = array();

		if(!empty($elements)){			
			list($usql, $params) = $DB->get_in_or_equal($elements);
			
			$filter_classroom = optional_param('filter_classrooms', '', PARAM_RAW);
			// $filter_course = optional_param('filter_courses', '', PARAM_RAW);
			$filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
			$filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);

			if(!empty($filter_classroom) || (!empty($filter_starttime)&&!empty($filter_endtime))){
				$sql="SELECT lc.id as classroomid, lc.name as classroomname, lc.shortname as classroomcode, lc.startdate, lc.enddate, lc.timecreated createdon, lc.timemodified modifiedon, lc.status, lc.instituteid,lc.total_hours, lc.totalsessions, c.fullname, c.shortname, c.open_points,
					(SELECT count(lcu.userid)
					FROM {local_classroom_users} lcu
					WHERE lcu.classroomid = lc.id)  as enrolledusers,
					(SELECT count(lcu.userid)
					FROM {local_classroom_users} lcu
					WHERE lcu.classroomid = lc.id AND lcu.completion_status=1 )  as completedusers
					FROM {local_classroom} as lc
					LEFT JOIN {local_classroom_courses} lcc ON lcc.classroomid = lc.id
					LEFT JOIN {course} c ON c.id = lcc.courseid
					WHERE 1 = 1 ";

				if($filter_classroom > 0){
					$sql.=" AND lc.id = $filter_classroom  ";
				}
				
				// if(!empty($filter_programme) and $filter_programme!=0){
				// 	 $test = implode(",",$filter_programme);
				// 	$sql.=" AND  bc.courseid =$filter_programme ";
				// }	
					
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
				$sql.= " ORDER BY lc.id DESC";

				$records = $DB->get_records_sql($sql);
			
				$reportarray = array();
		
				foreach($records as $record){
						
					$columns = new stdClass();
					$columns->iltname = $record->classroomname;
					$columns->iltcode = $record->classroomcode;

					$columns->coursename  = !empty($record->fullname) ? $record->fullname : 'NA';
					$columns->coursecode  = !empty($record->shortname) ? $record->shortname : 'NA';
					$columns->credits = !empty($record->open_points) ? $record->open_points : 'NA';
					
					$columns->month  = date('M', $record->startdate);
					$columns->year  =date('Y',$record->startdate);
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

					$sql = "SELECT count(id) detracto 
							FROM {local_trainingfeedback} 
							WHERE score IN (0,1,2,3,4,5,6) and batchid={$record->classroomid} ";
					$detractors = $DB->get_field_sql($sql);

					$sql = "SELECT count(id) passive 
							FROM {local_trainingfeedback} 
							WHERE  score IN (7,8) and batchid={$record->classroomid} ";
					$passive = $DB->get_field_sql($sql);

					$sql = "SELECT count(id) promoters 
							FROM {local_trainingfeedback} 
							WHERE  score IN (9,10) and batchid={$record->classroomid} ";
					$promoters = $DB->get_field_sql($sql);					
					
					$total = $promoters + $passive + $detractors;
					$promotersavg = ($total > 0) ? ($promoters/$total) : 0;
					$detractorsavg = ($total > 0) ? ($detractors/$total) : 0;

					$columns->noofresponses_0to6 = $detractors;
					$columns->noofresponses_7to8 = $passive;
					$columns->noofresponses_9to10 = $promoters;
					$columns->totalresponses = $promoters + $passive + $detractors;

					$columns->trainingnps = round( (($promotersavg - $detractorsavg) * 100), 2);
					
					$location = $DB->get_field('local_location_institutes', 'fullname', array('id'=>$record->instituteid));
					$columns->location = $location ? $location : 'NA';		

					$sql = "SELECT lct.id, CONCAT(u.firstname,' ',u.lastname) as trainer
							FROM {local_classroom_trainers} lct
							JOIN {user} u ON u.id = lct.trainerid
							WHERE lct.classroomid = $record->classroomid AND u.deleted = 0";
					$trainers = $DB->get_records_sql_menu($sql);
					if($trainers){
						$columns->trainers = implode(',', $trainers);
					}else{
						$columns->trainers = 'NA';
					}
					$columns->traininghours = $this->ilt_training_hours($record->classroomid);
					
					$reportarray[] = $columns;			
					}
					return $reportarray;
				}		
				return $finalelements;
		}
	}

	function ilt_training_hours($classroomid){
		global $DB;
		$sql = "SELECT cls.id,cls.timestart,cls.timefinish 
				FROM {local_classroom_sessions} cls 
				WHERE cls.classroomid = $classroomid";
		$sessiondetails = $DB->get_records_sql($sql);
		$hours = 0;
		if($sessiondetails){
			$time = 0;
			foreach($sessiondetails as $session){
				$diff = $session->timefinish - $session->timestart;
				$time = $time + $diff;	 
			}
			$hours = gmdate("H:i", $time);	
		}
		return $hours;
	}
	
}
