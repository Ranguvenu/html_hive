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
class report_mooc_courses extends report_base{
	
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
		$courseid = optional_param('filter_mooc_courses', null, PARAM_INT);
		if(!empty($elements)){			
			list($usql, $params) = $DB->get_in_or_equal($elements);

			if($courseid){
				$sql = "SELECT c.id, c.fullname, c.open_identifiedas, c.open_points
						FROM {course} as c
						WHERE c.visible = 1 AND
						(CONCAT(',',open_identifiedas,',') LIKE CONCAT('%,',3,',%') OR CONCAT(',',open_identifiedas,',') LIKE CONCAT('%,',1,',%')) ";
						
				if($courseid > 0){
					$sql .= " AND c.id = $courseid ";
				}
				
				$records =$DB->get_records_sql($sql);
				
				$reportarray = array();
				foreach($records as $record){
					$manger=new stdClass();
					
					$manger->coursename =$record->fullname;
					$manger->credits = $record->open_points;
					$noofenrolled = $this->get_enrolled_users_count($record->id);
					$manger->noofenrolled = $noofenrolled;
					$noofcompleted = $this->get_completed_users_count($record->id);
					$manger->noofcompleted = $noofcompleted;

					if($record->open_identifiedas == 1){
						$manger->type = "Mooc";
					}else{
						$manger->type = "E-Learning";
					}
					
					$reportarray[] = $manger;
				}
				return $reportarray;
			}
		}
	}

	function get_enrolled_users_count($courseid){
		global $DB;

			$sql= "SELECT count(ra.id) as enrolled
					FROM {role_assignments} ra
					JOIN {context} cxt ON cxt.id = ra.contextid
					JOIN {user} u ON u.id = ra.userid
					WHERE ra.roleid = 5 AND cxt.contextlevel = 50 AND u.deleted = 0
					AND cxt.instanceid = $courseid";

			$enrolledusers = $DB->count_records_sql($sql);

			return $enrolledusers;
	}
	
	function get_completed_users_count($courseid){
		global $DB;

		$contextid = $DB->get_field('context', 'id', array('instanceid'=>$courseid,'contextlevel'=>50));

		$sql = "SELECT COUNT(cc.id) 
                FROM {role_assignments} as ra
                JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee' 
                	AND ra.contextid = $contextid
                JOIN {user} u ON u.id = ra.userid
                JOIN {course_completions} as cc ON cc.course = $courseid AND u.id = cc.userid
                WHERE u.deleted = 0 AND cc.course = $courseid
                AND cc.timecompleted IS NOT NULL";

		$completedusers = $DB->count_records_sql($sql);
		
		return $completedusers;
	}
}

