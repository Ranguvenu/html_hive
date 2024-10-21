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
 */class report_externalcertification extends report_base{
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
		global $DB, $USER;
		$finalelements = array();

		if(!empty($elements)){
			$sql = "SELECT lec.id, u.username, lec.status, lec.timemodified,u.email,u.id AS userid, u.open_grade, lc.fullname AS departmentfullname, lecc.coursename
					FROM {local_external_certificates} lec
					JOIN {user} u ON lec.userid = u.id
					JOIN {local_costcenter} as lc ON u.open_departmentid = lc.id
					JOIN {local_external_certificates_courses} lecc ON lecc.id = lec.coursename";
			if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
				$sql .= "";
			}else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
				$sql .= " AND u.open_costcenterid = $USER->open_costcenterid ";
			}else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
				$sql .= " AND u.open_costcenterid = $USER->open_costcenterid
					AND u.open_departmentid = $USER->open_departmentid";
			}else{
				$sql .= " AND u.open_costcenterid = $USER->open_costcenterid
					AND u.open_departmentid = $USER->open_departmentid";
			}

			$records = $DB->get_records_sql($sql);

			foreach($records as $record){
					
				$columns = new stdClass();
				$columns->status = $record->status == 0 ? get_string('pending', 'block_configurable_reports') : ($record->status == 1 ? get_string('approved', 'block_configurable_reports') : get_string('declined', 'block_configurable_reports'));
				$columns->approveddate =  $record->timemodified == 0 ? "--" : date('d-m-Y', $record->timemodified);
				$columns->employeeemail  = $record->email;
				$columns->employeegrade  = $record->open_grade ? $record->open_grade : "--";
				$columns->employeedepartment  = $record->departmentfullname;
				$columns->coursecredits  = "2";
				$columns->certificationname  = $record->coursename;
				$reportarray[] = $columns;
			}
			return $reportarray;
		}		

		return $finalelements;
	}
	
}
