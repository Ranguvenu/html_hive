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

class report_detailclassroominfo extends report_base{

	function init(){
		$this->components = array('columns','filters','permissions','calcs');
	}
	public function get_all_elements() {
        global $DB;

        $elements = $DB->get_fieldset_select('local_classroom', 'id', null, array());

        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$classroom = optional_param('filter_classrooms',NULL, PARAM_INT);
			$employee = optional_param('filter_users',NULL, PARAM_INT);
			$completionstate = optional_param('filter_completionstate',-1, PARAM_INT);
			$reportarray = array();
			if(!empty($classroom) || !empty($employee)){
				$sql = "SELECT clu.id, cl.name AS classroomname,cl.shortname AS classroomcode, 
						cl.startdate, cl.enddate,cl.capacity,cl.open_location as 'cllocation',u.open_employeeid AS 'employeeid',cl.institute_type,
						(SELECT GROUP_CONCAT(us.firstname,' ',us.lastname) 
							FROM {user} us
							JOIN {local_classroom_trainers} lct ON us.id = lct.trainerid 
                			WHERE lct.classroomid = cl.id) AS trainers,
						concat(u.firstname ,' ',u.lastname) AS employeename,u.open_location as 'emplocation', clu.completion_status,
						clu.completiondate
						FROM {local_classroom} cl
						JOIN {local_classroom_users} clu ON clu.classroomid = cl.id
						JOIN {user} u ON clu.userid = u.id 
						WHERE u.deleted = 0 ";

				if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		            $sql .= "";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
		            $sql .= " AND cl.costcenter = $USER->open_costcenterid ";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
		            $sql .= " AND cl.costcenter = $USER->open_costcenterid 
		            		AND cl.department = $USER->open_departmentid";
		        }else{
		            $sql .= " AND cl.costcenter = $USER->open_costcenterid 
		            		AND lc.department = $USER->open_departmentid";
		        }
		        
				if($classroom > 1){
					$sql .= " AND cl.id = $classroom ";
				}

				if($employee > 1){
					$sql .= " AND clu.userid = $employee ";
				}

				if($completionstate == 0 || $completionstate == 1){
					$sql .= " AND clu.completion_status = $completionstate ";
				}

				if(!is_siteadmin()){
					$user_orgid = $DB->get_field('user','open_costcenterid',array('id'=>$USER->id));
					$sql .= " AND cl.costcenter = $user_orgid ";
				}
				$classroomusers = $DB->get_recordset_sql($sql);
				
				if($classroomusers){
					foreach($classroomusers as $classroomuser){
						$reportdata= new stdClass();
						$reportdata->classroomname = $classroomuser->classroomname;
						$reportdata->classroomcode = $classroomuser->classroomcode;
						$reportdata->startdate = date("d-m-Y", $classroomuser->startdate);
						$reportdata->enddate = date("d-m-Y", $classroomuser->enddate);
						$reportdata->capacity = !empty($classroomuser->capacity) ? $classroomuser->capacity : '--';
						if($classroomuser->trainers){
							$reportdata->trainers = $classroomuser->trainers;
						}else{
							$reportdata->trainers = '--';
						}
						if($classroomuser->institute_type == 1){
							$reportdata->locationtype = get_string('internal','block_configurable_reports');
						}elseif($classroomuser->institute_type == 2){
							$reportdata->locationtype = get_string('external','block_configurable_reports');
						}else{
							$reportdata->locationtype = '--';
						}
						$reportdata->classroomlocation =!empty($classroomuser->cllocation) ? $classroomuser->cllocation : '--';
						$reportdata->employeeid = $classroomuser->employeeid;
						$reportdata->employeename = $classroomuser->employeename;
						$reportdata->employeelocation   = !empty($classroomuser->emplocation) ? $classroomuser->emplocation : '--';
						
						if($classroomuser->completion_status == 0){
							$reportdata->completionstatus = get_string('not_completed','block_configurable_reports');
							$reportdata->completiondate = '--';
						}else{
							$reportdata->completionstatus = get_string('completed','block_configurable_reports');
							$reportdata->completiondate = date('d-m-Y',$classroomuser->completiondate);
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
