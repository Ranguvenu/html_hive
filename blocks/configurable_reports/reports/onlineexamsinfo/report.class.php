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

class report_onlineexamsinfo extends report_base{

	function init(){
		$this->components = array('columns','filters','permissions','calcs');
	}
	public function get_all_elements() {
        global $DB;

        $elements = $DB->get_fieldset_select('local_onlinetests', 'id', null, array());

        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$onlinetest = optional_param('filter_onlineexams',NULL, PARAM_INT);
			$employeeid = optional_param('filter_users',NULL, PARAM_INT);
			$completionstate = optional_param('filter_completionstate',-1, PARAM_INT);
			$reportarray = array();
			if(!empty($onlinetest) || !empty($employeeid)){
				$sql = "SELECT otu.id, ot.name AS 'onlinetestname', ot.departmentid,
						u.open_employeeid AS 'employeeid',concat(u.firstname ,' ',u.lastname) AS employeename,
						u.open_location as 'emplocation', otu.status,otu.timemodified as 'completiontime'
						FROM {local_onlinetests} ot
						JOIN {local_onlinetest_users} otu ON otu.onlinetestid = ot.id
						JOIN {user} u ON otu.userid = u.id 
						WHERE 1 = 1 ";

				if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		            $sql .= "";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
		            $sql .= " AND ot.costcenterid = $USER->open_costcenterid ";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
		            $sql .= " AND ot.costcenterid = $USER->open_costcenterid 
		            		AND ot.departmentid = $USER->open_departmentid";
		        }else{
		            $sql .= " AND ot.costcenterid = $USER->open_costcenterid 
		            		AND ot.departmentid = $USER->open_departmentid";
		        }

				if($onlinetest > 1){
					$sql .= " AND ot.id = $onlinetest ";
				}

				if($employeeid > 1){
					$sql .= " AND otu.userid = $employeeid ";
				}

				if($completionstate == 0 || $completionstate == 1){
					$sql .= " AND otu.status = $completionstate ";
				}

				$onlinetestusers = $DB->get_recordset_sql($sql);
				
				if($onlinetestusers){
					foreach($onlinetestusers as $onlinetestuser){
						$reportdata= new stdClass();
						$reportdata->onlinetestname = $onlinetestuser->onlinetestname;
						if($onlinetestuser->departmentid){
							$department = $DB->get_field('local_costcenter','fullname',array('id'=>$USER->id));
							$reportdata->department = $department;
						}else{
							$reportdata->department = get_string('all');
						}						
						$reportdata->employeename = $onlinetestuser->employeename;
						$reportdata->employeeid = $onlinetestuser->employeeid;
						
						if($onlinetestuser->status == 0){
							$reportdata->completionstatus = get_string('not_completed','block_configurable_reports');
							$reportdata->completiondate = '--';
						}else{
							$reportdata->completionstatus = get_string('completed','block_configurable_reports');
							$reportdata->completiondate = date('d-m-Y',$onlinetestuser->completiontime);
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
