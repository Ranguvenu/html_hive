<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This courselister is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This courselister is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this courselister.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Suggested Courses list block plugin helper
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_suggested_courses
 */

namespace block_suggested_courses;

use dml_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Class plugin
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_suggested_courses
 */
abstract class plugin {
    /** @var string */
    const COMPONENT = 'block_suggested_courses';

    
    /** @var int */
    const SUGGESTEDCOURSES = 1;

    public static function get_suggestedcourses($stable,$filtervalues,$data_object) {
        global $DB, $USER, $CFG;
        
        $systemcontext = \context_system::instance();
        $params=array();
      
           $interested_skill_ids = $DB->get_record('local_interested_skills',array('usercreated'=>$USER->id), 'interested_skill_ids', $strictness=IGNORE_MISSING);

            if($interested_skill_ids){
                $intskills = $interested_skill_ids->interested_skill_ids;
    
                $coursetypessql = "SELECT id FROM {local_course_types} WHERE shortname NOT IN ('ilt','learningpath')";
                $coursetypes = $DB->get_fieldset_sql($coursetypessql);
                $ctypes = implode(",",$coursetypes );
                
                $currenttime = time();

                $countsql = "SELECT COUNT(c.id) ";
                $coursesql = "SELECT c.id,c.fullname,c.visible,c.summary,c.open_identifiedas,ct.course_type,c.selfenrol,c.expirydate,c.open_courseprovider";
                $fromsql = " FROM {local_skill} As sk
                                JOIN {course} As c ON  c.open_skill IN  ($intskills) 
                                JOIN {enrol} en on en.courseid=c.id
                                JOIN {local_course_types} As ct ON c.open_identifiedas = ct.id
                                JOIN {local_costcenter} lc ON lc.id = c.open_costcenterid ";           
    
                $wheresql = " WHERE sk.id >1 AND sk.id IN ($intskills) AND c.selfenrol = 1 AND c.open_identifiedas IN ( $ctypes )";
                $wheresql .= " AND c.id > 1 and (en.enrol='self' and en.status=0)  ";   
                $wheresql .= " and (c.expirydate =0 OR c.expirydate IS NULL OR c.expirydate >= ".$currenttime.") ";     
    
                $wheresql .=" AND c.id NOT IN (select 
                                distinct e.courseid  from {enrol} e
                                JOIN {user_enrolments} ue on ue.enrolid = e.id 
                                where e.courseid=c.id and ue.userid=$USER->id) ";  
              
             
                if(!is_siteadmin() || !has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
                    if(!empty($USER->open_grade) && $USER->open_grade != ""){                 
                    $wheresql .= " AND ( (concat(',',c.open_grade,',') LIKE '%,$USER->open_grade,%' ) OR c.open_grade = '-1' OR c.open_grade IS NULL)";
                    } else {
                    $wheresql .= " AND (c.open_grade = '0' OR c.open_grade = '-1' OR c.open_grade IS NULL ) ";
                    }
                }

                if(!is_siteadmin() || !has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
                    if(!empty($USER->open_ouname) && $USER->open_ouname != ""){
                        $wheresql .= " AND ( (concat(',',c.open_ouname,',') LIKE '%,$USER->open_ouname,%' ) OR c.open_ouname = '-1' OR c.open_ouname IS NULL)";
                    }else {
                        $wheresql .= " AND (c.open_ouname = '0' OR c.open_ouname = '-1' OR c.open_ouname IS NULL ) ";
                    }
                } 

                if(!is_siteadmin() || !has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
                    if(!empty($USER->open_subdepartment) && $USER->open_subdepartment != ""){
                        $wheresql .= " AND (c.open_subdepartment = 0 OR c.open_subdepartment IS NULL OR (c.open_subdepartment = $USER->open_subdepartment) ) ";
                    }else{
                        $wheresql .= " AND (c.open_subdepartment = 0 OR c.open_subdepartment IS NULL ) ";
                    }
                } 
                if(!empty($data_object->search_query)){
                    $wheresql .= " AND c.fullname LIKE '%$data_object->search_query%'";
                }
                
                $wheresql .= " AND c.visible = 1 ";     
                $groupby = "  GROUP BY c.id,c.fullname,c.visible,c.summary,c.open_identifiedas,ct.course_type";
                $coursecountsql = $DB->get_records_sql($coursesql . $fromsql .$wheresql.$groupby, array('userid'=>$USER->id,'enroltype'=> 'self','status'=>0 )); 

                $suggestedcourses = $DB->get_records_sql($coursesql.$fromsql .$wheresql.$groupby, array('userid'=>$USER->id,'enroltype'=> 'self','status'=>0 ),$stable->start, $stable->length);
        }else{
            $allcoursecount = 0;
        }
        try {
            if(!empty($coursecountsql)){
               $allcoursecount = count($coursecountsql);
            }
          // $allcoursecount = count($suggestedcourses );
          
        } catch (dml_exception $ex) {
            $allcoursecount = 0;
        }
     
        return compact('suggestedcourses', 'allcoursecount');

    }

}
