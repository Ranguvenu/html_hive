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

class report_certificationinfo extends report_base{

	function init(){
		$this->components = array('columns','filters','permissions','calcs');
	}
	public function get_all_elements() {
        global $DB;

        $elements = $DB->get_fieldset_select('local_certification', 'id', null, array());

        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$certificate = optional_param('filter_certifications',NULL, PARAM_INT);
			$employeeid = optional_param('filter_users',NULL, PARAM_INT);
			$completionstate = optional_param('filter_completionstate',-1, PARAM_INT);
			$reportarray = array();
			if(!empty($certificate) || !empty($employeeid)){
				$sql = "SELECT cu.id, c.name, c.shortname,c.startdate,c.enddate,c.capacity, 
						c.institute_type,
						li.fullname as 'location',
						(SELECT GROUP_CONCAT(us.firstname,' ',us.lastname) 
							FROM {user} us
							JOIN {local_certification_trainers} lct ON us.id = lct.trainerid 
                			WHERE lct.certificationid = c.id) AS trainers,
						u.open_employeeid AS 'employeeid',
						concat(u.firstname ,' ',u.lastname) AS employeename,
						cu.completion_status, cu.completiondate
						FROM {local_certification} c
						JOIN {local_certification_users} cu ON cu.certificationid = c.id
						LEFT JOIN {local_location_institutes} li ON li.id = c.instituteid
						JOIN {user} u ON cu.userid = u.id 
						WHERE u.deleted = 0 ";

				if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		            $sql .= "";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
		            $sql .= " AND c.costcenter = $USER->open_costcenterid ";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
		            $sql .= " AND c.costcenter = $USER->open_costcenterid 
		            		AND c.department = $USER->open_departmentid";
		        }else{
		            $sql .= " AND c.costcenter = $USER->open_costcenterid 
		            		AND c.department = $USER->open_departmentid";
		        }

				if($certificate > 1){
					$sql .= " AND c.id = $certificate ";
				}

				if($employeeid > 1){
					$sql .= " AND cu.userid = $employeeid ";
				}

				if($completionstate == 0 || $completionstate == 1){
					$sql .= " AND cu.completion_status =  $completionstate ";
				}
				
				$certificationusers = $DB->get_recordset_sql($sql);
				
				if($certificationusers){
					foreach($certificationusers as $certificationuser){
						$reportdata= new stdClass();
						$reportdata->certificationname = $certificationuser->name;
						$reportdata->shortname = $certificationuser->shortname;
						$reportdata->capacity = $certificationuser->capacity;
						if($certificationuser->institute_type == 1){
							$reportdata->locationtype  = get_string('internal','block_configurable_reports');
						}elseif($certificationuser->institute_type == 2){
							$reportdata->locationtype = get_string('external','block_configurable_reports');
						}else{
							$reportdata->locationtype = '--';
						}
						if($certificationuser->location){
							$reportdata->location = $certificationuser->location;
						}else{
							$reportdata->location = '--';
						}
						if($certificationuser->trainers){
							$reportdata->trainers = $certificationuser->trainers;
						}else{
							$reportdata->trainers = '--';
						}
						$reportdata->employeename = $certificationuser->employeename;
						$reportdata->employeeid = $certificationuser->employeeid;
						if($certificationuser->completion_status == 1){
							$reportdata->completionstatus = get_string('completed','block_configurable_reports');
							$reportdata->completiondate = date('d-m-Y',$certificationuser->completiondate);
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
