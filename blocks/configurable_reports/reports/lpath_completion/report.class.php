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
 * @date: 2020
 */

require_once($CFG->dirroot.'/local/lib.php');
class report_lpath_completion extends report_base{
	
	function init(){
		$this->components = array('columns','filters','permissions');
	}	
	function get_all_elements(){
		global $DB;
		
        $elements = $DB->get_fieldset_select('local_learningplan', 'id', null, null);;
		return $elements;
	}
	
	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();

		if(!empty($elements)){			
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$lpathid = optional_param('filter_learningpath','', PARAM_INT);
            $userid = optional_param('filter_users','', PARAM_INT);
            $completionstatus = optional_param('filter_completionstate',-1, PARAM_INT);
			
			$reportarray = array();

			$sql = "SELECT llu.id,u.firstname, u.lastname, u.email, u.open_employeeid, lp.name, lp.shortname,
					FROM_UNIXTIME(llu.timecreated,'%d %b %Y') as enrolledon,
					u.id as userid,llu.status,llu.completiondate
                    FROM {local_learningplan} lp
                    JOIN {local_learningplan_user} as llu ON lp.id = llu.planid
                    JOIN {user} as u ON u.id = llu.userid
                    WHERE u.deleted = 0  ";			 
					
			if($lpathid > 0){
				$sql .= " AND lp.id = $lpathid ";
			}

			if($userid > 1 ){
				$sql .= " AND u.id = $userid ";
			}

			if ($completionstatus == 0) {
	           $sql .= " AND llu.status IS NULL ";
	        }else if($completionstatus == 1){
	        	$sql .= " AND llu.status = 1 ";
	        }

			$lpathusers = $DB->get_records_sql($sql);
			if($lpathusers){
				foreach($lpathusers as $enroluser){
					$rowdata = new \stdClass();
					$rowdata->lpathname = $enroluser->name;
					$rowdata->lpathcode = $enroluser->shortname;
					$rowdata->employeename = $enroluser->firstname.' '.$enroluser->lastname;
					$rowdata->employeeid = $enroluser->open_employeeid;
					$rowdata->email = $enroluser->email;
					$rowdata->enrolledon = $enroluser->enrolledon;
					if($enroluser->status){
						$rowdata->completionstatus = 'Completed';
						$rowdata->completiondate = date('d-m-Y',$enroluser->completiondate);
					}else{
						$rowdata->completionstatus = 'Not Completed';
						$rowdata->completiondate = 'NA';
					}
					$reportarray[] = $rowdata;
				}
			}
			return $reportarray;
		}	
		
		return $finalelements;
	}
	
}
