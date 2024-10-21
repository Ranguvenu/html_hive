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

class report_feedbackinfo extends report_base{

	function init(){
		$this->components = array('columns','filters','permissions','calcs');
	}
	public function get_all_elements() {
        global $DB;

        $elements = $DB->get_fieldset_select('local_evaluations', 'id', null, array());

        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$feedback = optional_param('filter_feedbacks',NULL, PARAM_INT);
			$employeeid = optional_param('filter_users',NULL, PARAM_INT);
			$completionstate = optional_param('filter_completionstate',-1, PARAM_INT);
			$reportarray = array();
			if(!empty($feedback) || !empty($employeeid)){
				$sql = "SELECT eu.id, le.name AS 'feedbackname', le.departmentid,le.timeopen,le.timeclose,le.type,
						u.open_employeeid AS 'employeeid',concat(u.firstname ,' ',u.lastname) AS employeename,
						ec.timemodified as 'completiontime'
						FROM {local_evaluations} le
						JOIN {local_evaluation_users} eu ON eu.evaluationid = le.id
						LEFT JOIN {local_evaluation_completed} ec ON ec.evaluation = eu.evaluationid 
																	AND ec.userid = eu.userid
						JOIN {user} u ON eu.userid = u.id ";

				// if($completionstate == 1){
				// 	$sql .= " JOIN {local_evaluation_completed} ec ON ec.evaluation = eu.evaluationid 
				// 													AND ec.userid = eu.userid ";
				// }elseif($completionstate == 0){
				// 	$sql .= "  {local_evaluation_completed} ec ON ec.evaluation = eu.evaluationid 
				// 													AND ec.userid = eu.userid ";
				// }

				$sql .= " WHERE u.deleted = 0  ";

				if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		            $sql .= "";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
		            $sql .= " AND le.costcenterid = $USER->open_costcenterid ";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
		            $sql .= " AND le.costcenterid = $USER->open_costcenterid 
		            		AND le.departmentid = $USER->open_departmentid";
		        }else{
		            $sql .= " AND le.costcenterid = $USER->open_costcenterid 
		            		AND le.departmentid = $USER->open_departmentid";
		        }

				if($feedback > 1){
					$sql .= " AND le.id = $feedback ";
				}

				if($employeeid > 1){
					$sql .= " AND eu.userid = $employeeid ";
				}
				
				$feedbackusers = $DB->get_recordset_sql($sql);
				
				if($feedbackusers){
					foreach($feedbackusers as $feedbackuser){
						$reportdata= new stdClass();
						$reportdata->feedbackname = $feedbackuser->feedbackname;
						if($feedbackuser->departmentid){
							$department = $DB->get_field('local_costcenter','fullname',array('id'=>$feedbackuser->departmentid));
							$reportdata->department = $department;
						}else{
							$reportdata->department = get_string('all');
						}
						if(!empty($feedbackuser->timeopen)){
							$reportdata->startdate = date('d-m-Y H:i',$feedbackuser->timeopen);
						}else{
							$reportdata->startdate = '--';
						}
						if(!empty($feedbackuser->timeclose)){
							$reportdata->enddate = date('d-m-Y H:i',$feedbackuser->timeclose);
						}else{
							$reportdata->enddate = '--';
						}
						if($feedbackuser->type == 1){
							$reportdata->type = get_string('feedback','local_evaluation');
						}else{
							$reportdata->type =get_string('survey','local_evaluation');
						}						
						$reportdata->employeename = $feedbackuser->employeename;
						$reportdata->employeeid = $feedbackuser->employeeid;
						
						if(!empty($feedbackuser->completiontime)){
							$reportdata->completionstatus = get_string('completed','block_configurable_reports');
							$reportdata->completiondate = date('d-m-Y',$feedbackuser->completiontime);
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
