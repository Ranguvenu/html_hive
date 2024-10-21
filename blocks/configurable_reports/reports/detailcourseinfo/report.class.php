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

class report_detailcourseinfo extends report_base{

	function init(){
		$this->components = array('columns','filters','permissions','calcs');
	}
	public function get_all_elements() {
        global $DB;

        $elements = array();
        $elements = $DB->get_fieldset_select('course', 'id', null, null);
        
        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$courseid = optional_param('filter_courses',NULL, PARAM_INT);
			$userid = optional_param('filter_users','', PARAM_INT);
			$completionstate = optional_param('filter_completionstate',-1, PARAM_INT);
			$reportarray = array();
			if(!empty($courseid) || !empty($userid)){
				$sql = "SELECT ue.id , u.open_employeeid AS 'employeeid', e.enrol,
						concat(u.firstname ,' ',u.lastname) AS employeename,u.email,
						u.open_costcenterid,u.open_departmentid,c.id as courseid,
						c.fullname AS coursename,c.shortname, c.open_departmentid as 'coursedepartment',
						ue.timecreated as enrolleddate,
						cc.name as category, c.visible, cp.timecompleted
						FROM {user} u
						JOIN {user_enrolments} ue ON ue.userid = u.id
						JOIN {enrol} e ON e.id = ue.enrolid
						JOIN {course} c ON c.id = e.courseid
						JOIN {course_categories} cc ON cc.id = c.category
						LEFT JOIN {course_completions} cp ON cp.course = c.id AND cp.userid = u.id
						WHERE  u.id > 2
						AND u.deleted = 0 ";

				$identifiedas = array(1,3); // for get only E-Learning courses
				list($insql, $inparams) = $DB->get_in_or_equal($identifiedas);
				$sql .= "AND c.open_identifiedas $insql ";

				if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		            $sql .= "";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
		            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid ";
		        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
		            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid 
		            		AND c.open_departmentid = $USER->open_departmentid";
		        }else{
		            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid 
		            		AND c.open_departmentid = $USER->open_departmentid";
		        }

				if($courseid > 1){
					$sql.=" AND c.id = $courseid ";
				}

				if($completionstate == 0){
					$sql .= " AND cp.timecompleted IS NULL";
				}elseif($completionstate == 1){
					$sql .= " AND cp.timecompleted IS NOT NULL";
				}

				if($userid > 1 ){
					$sql .=" AND u.id = $userid ";
				}
                
				$courseenrollmentinfo = $DB->get_records_sql($sql,$inparams);
				
				if($courseenrollmentinfo){
					foreach($courseenrollmentinfo as $enrolinfo){

						$reportdata = new stdClass();
						$reportdata->employeename   = $enrolinfo->employeename;
						$reportdata->employeeid   = $enrolinfo->employeeid;
						$reportdata->email   = $enrolinfo->email;
						$reportdata->enrolment_type=$enrolinfo->enrol;
						$userdepart =  $DB->get_field('local_costcenter','fullname',array('id' =>$enrolinfo->open_departmentid));
				        $reportdata->employee_department = !empty($userdepart) ? $userdepart : '--';
				        $empsubdept =  $DB->get_field('local_costcenter','fullname',array('id' =>$enrolinfo->open_subdepartment));
				        $reportdata->employee_subdepartment = !empty($empsubdept) ? $empsubdept : '-';
						$reportdata->band = !empty($enrolinfo->open_band) ? $enrolinfo->open_band : '-';
						$reportdata->courseid   = $enrolinfo->courseid;
						$reportdata->coursename   = $enrolinfo->coursename;
						$reportdata->course_shortname   = $enrolinfo->shortname;
						if($enrolinfo->coursedepartment){
							$cdept = $DB->get_field('local_costcenter','fullname',array('id' =>$enrolinfo->coursedepartment));
							$reportdata->coursedepartment = $cdept;
						}else{
							$reportdata->coursedepartment = '--';
						}
						$reportdata->category   = $enrolinfo->category;
						$reportdata->visible   = $enrolinfo->visible == 1 ? get_string('visible') : get_string('invisible');
						$reportdata->enrolmenttype = $enrolinfo->enrol;
						$reportdata->enrolleddate = date('d-m-Y', $enrolinfo->enrolleddate);
						
						if(!empty($enrolinfo->timecompleted)){
							$reportdata->completionstatus = get_string('completed','block_configurable_reports');
							$reportdata->completiondate = date('d-m-Y', $enrolinfo->timecompleted);
						}else{
							$reportdata->completionstatus = get_string('not_completed','block_configurable_reports');
							$reportdata->completiondate = 'N/A';
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
