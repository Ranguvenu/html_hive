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
 * @author: Venu <venu.chary@moodle.com>
 * @date: 2020
 */
class report_learning_dashboard extends report_base{
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
	function get_rows($elements){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$user_filter = optional_param('filter_users','', PARAM_INT);
		if(!empty($elements)){

			$technicalcategories = $CFG->local_learningdashboard_technical_categories;
			$leadershipcategories = $CFG->local_learningdashboard_leadership_categories;
			$all = $technicalcategories.', '. $leadershipcategories;

			$sql = "
				SELECT ue.id,
					u.id AS userid,
					c.fullname,
					cc.timecompleted AS coursecompleted,
					ue.timecreated,
					c.open_points AS coursecredits,
					ccat.idnumber AS coursecategoryid,
					u.open_grade,
					lc.fullname AS department,
					u.email,
					u.username,
					ccat.name AS coursecategory,
					lct.course_type AS learningtype,
					CASE
						WHEN ccat.id IN ($leadershipcategories) THEN 'Leadership'
						WHEN ccat.id IN ($technicalcategories) THEN 'Technical'
					END AS creditcategory
			FROM {user} u
			JOIN {user_enrolments} ue ON ue.userid = u.id
			JOIN {enrol} e ON e.id = ue.enrolid AND e.enrol = 'manual'
			JOIN {course} c ON c.id = e.courseid
			JOIN {course_categories} ccat ON ccat.id = c.category
			JOIN {local_course_types} lct ON lct.id = c.open_identifiedas
            JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
			LEFT JOIN {course_completions} cc ON cc.course = c.id AND u.id = cc.userid
			WHERE ccat.id IN ($all)
		";
		if ($user_filter){
			$sql .= " AND u.id = :user_filter" ;
			$params["user_filter"] =  $user_filter;
		}

			$records = $DB->get_records_sql($sql, $params);
			$reportarray = array();
			foreach($records as $record){
				$columns = new stdClass();
				$columns->completionstatus = $record->coursecompleted ? get_string('completed', 'block_configurable_reports') : get_string('inprogress', 'block_configurable_reports');
				if ($record->deleted == 0 && $record->deleted == 0){
					$columns->employeestatus = get_string('active', 'block_configurable_reports');
				}else{
					$columns->employeestatus = get_string('inactive', 'block_configurable_reports');
				}

				$columns->completiondate  = date('d-m-Y', $record->coursecompleted);
				$columns->employeeemail  = $record->email;
				$columns->completiongrade  = $record->grade ? $record->grade : "--";
				$columns->employeedepartment  = $record->department ? $record->department : "--";
				$columns->coursename  = $record->fullname;
				$columns->coursecredits  = $record->coursecredits;
				$columns->username  = $record->username;
				$columns->coursecategory  = $record->coursecategory;
				$columns->enrolmentdate  = date('d-m-Y', $record->timecreated);
				$columns->creditcategory  = $record->creditcategory;
				$columns->learningtype  = $record->learningtype;
				$reportarray[] = $columns;
			}
			return $reportarray;
		}
		return $finalelements;
	}
}
