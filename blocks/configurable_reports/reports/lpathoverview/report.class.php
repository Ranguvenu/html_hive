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
 * @copyright  2020 eAbyas Info Solutions <www.eabyas.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class report_lpathoverview extends report_base{

	function init(){
		$this->components = array('columns','filters','permissions');
	}
	public function get_all_elements() {
        global $DB;

        $elements = array();
        $elements = $DB->get_fieldset_select('local_learningplan', 'id', null, null);
        
        return $elements;
    }

	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$finalelements = array();
		$systemcontext = context_system::instance();
		if(!empty($elements)){
			list($usql, $params) = $DB->get_in_or_equal($elements);
			$lpathid = optional_param('filter_learningpath',NULL, PARAM_INT);
				$sql = "SELECT lp.id as learningpathid,lp.name,lp.shortname,
                    (SELECT count(llu.id) 
                        FROM {local_learningplan_user} as llu 
                        JOIN {user} u ON u.id = llu.userid AND u.deleted = 0 AND u.suspended = 0
                        WHERE llu.planid = lp.id) as enrolledcount,
                    (SELECT count(llu.id) 
                        FROM {local_learningplan_user} as llu 
                        JOIN {user} u ON u.id = llu.userid AND u.deleted = 0 AND u.suspended = 0
                        WHERE llu.planid = lp.id AND llu.status = 1) as completedcount 
                        FROM {local_learningplan} lp
                        WHERE 1 = 1 ";

            $params = array();
            if ($lpathid) {
	            $sql .= " AND lp.id = :lpathid ";
	            $params['lpathid'] = $lpathid;
	        }
                
			$lpathsinfo = $DB->get_records_sql($sql,$params);
			if($lpathsinfo){
				$reportarray = array();
				foreach($lpathsinfo as $singlepath){
					$reportdata = new \stdClass();
					$reportdata->lpathname = $singlepath->name;
					$reportdata->lpathcode = $singlepath->shortname;

					$sql = "SELECT c.id, c.fullname
	                        FROM {local_learningplan_courses} AS llc 
	                        JOIN {course} c ON c.id = llc.courseid 
	                        WHERE llc.planid = :planid AND nextsetoperator = :optval";

	                $params = array('planid'=>$singlepath->learningpathid, 'optval'=>'or');
	                $optinalcourses = $DB->get_records_sql_menu($sql, $params);
	                if($optinalcourses){
	                    $reportdata->optionalcourses = implode(', ', $optinalcourses);
	                }else{
	                    $reportdata->optionalcourses = 'NA';
	                }

	                $sql = "SELECT c.id, c.fullname
	                        FROM {local_learningplan_courses} AS llc 
	                        JOIN {course} c ON c.id = llc.courseid 
	                        WHERE llc.planid = :planid AND nextsetoperator = :manval";

	                $params = array('planid'=>$singlepath->learningpathid,'manval'=>'and');
	                $mandatorycourses = $DB->get_records_sql_menu($sql, $params);
	                if($mandatorycourses){
	                    $reportdata->mandatorycourses = implode(', ', $mandatorycourses);
	                }else{
	                    $reportdata->mandatorycourses = 'NA';
	                }

					$reportdata->enrolledcount = $singlepath->enrolledcount;
					$reportdata->completedcount = $singlepath->completedcount;

					$reportarray[] = $reportdata;
				}
			}
			return $reportarray;
		}
		return $finalelements;
	}
}
