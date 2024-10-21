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

class report_programinfo extends report_base{

	function init(){
		$this->components = array('columns','filters','permissions','calcs');
	}
	public function get_all_elements() {
        global $DB;

        $elements = $DB->get_fieldset_select('local_program', 'id', null, array());

        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$program = optional_param('filter_programs',NULL, PARAM_INT);
			$employeeid = optional_param('filter_users',NULL, PARAM_INT);
			$completionstate = optional_param('filter_completionstate',-1, PARAM_INT);
			$reportarray = array();
			if(!empty($program) || !empty($employeeid)){
				$sql = "SELECT pu.id, p.name AS 'programname',p.shortname, pc.stream,
						u.open_employeeid AS 'employeeid',concat(u.firstname ,' ',u.lastname) AS employeename,
						pu.completion_status, pu.completiondate
						FROM {local_program} p
						JOIN {local_program_users} pu ON pu.programid = p.id
						JOIN {local_program_stream} pc ON pc.id = p.stream
						JOIN {user} u ON pu.userid = u.id 
						WHERE u.deleted = 0 ";

				if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		            $sql .= "";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
		            $sql .= " AND p.costcenter = $USER->open_costcenterid ";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
		            $sql .= " AND p.costcenter = $USER->open_costcenterid 
		            		AND p.department = $USER->open_departmentid";
		        }else{
		            $sql .= " AND p.costcenter = $USER->open_costcenterid 
		            		AND p.department = $USER->open_departmentid";
		        }

				if($program > 1){
					$sql .= " AND p.id = $program ";
				}

				if($employeeid > 1){
					$sql .= " AND pu.userid = $employeeid ";
				}

				if($completionstate == 0 || $completionstate == 1){
					$sql .= " AND pu.completion_status =  $completionstate ";
				}

				$programusers = $DB->get_recordset_sql($sql);
				
				if($programusers){
					foreach($programusers as $programuser){
						$reportdata= new stdClass();
						$reportdata->programname = $programuser->programname;
						$reportdata->shortname = $programuser->shortname;
						$reportdata->stream = $programuser->stream;
						$reportdata->employeename = $programuser->employeename;
						$reportdata->employeeid = $programuser->employeeid;
						if($programuser->completion_status == 1){
							$reportdata->completionstatus = get_string('completed','block_configurable_reports');
							$reportdata->completiondate = date('d-m-Y',$programuser->completiondate);
						}else{
							$reportdata->completionstatus = get_string('not_completed','block_configurable_reports');
							$reportdata->completiondate = '--';
						}
						$reportarray[] = $reportdata;
					}
				}
			}
			return $reportarray;
		}
		return $finalelements;
	}
}
