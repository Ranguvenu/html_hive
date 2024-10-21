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
 * Course list block plugin helper
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_courselister
 */

namespace block_courselister;

use coding_exception;
use ddl_exception;
use dml_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * Class plugin
 * @author eabyas  <info@eabyas.in>
 * @package fractal
 * @subpackage block_courselister
 */
abstract class plugin {
    /** @var string */
    const COMPONENT = 'block_courselister';

    
    /** @var int */
    const SELECTEDCOURSES = 3;

   
    public static function get_selectedcourses($stable,$filtervalues,$data_object) {
        global $DB, $USER, $CFG;

        $systemcontext = \context_system::instance();
        $params=array();
        $allcoursecount = $alllpathscount = 0;

        $featured_courses = $DB->get_record('local_featured_courses',array(), 'featured_course_ids,featured_lpath_ids', $strictness=IGNORE_MISSING);
        
         if($featured_courses){

           if(empty($featured_courses->featured_course_ids) || $featured_courses->featured_course_ids ==0){
                $featured_courses->featured_course_ids = 0;
            }

            if(empty($featured_courses->featured_lpath_ids) || $featured_courses->featured_lpath_ids ==0){
                $featured_courses->featured_lpath_ids = 0;
            }
            $currenttime = time();
            $countsql = "SELECT COUNT(tbl.id) ";

            $coursetypessql = "SELECT id FROM {local_course_types} WHERE shortname IN ('ilt','learningpath')";
            $coursetypes = $DB->get_fieldset_sql($coursetypessql);
            $ctypes = implode(",",$coursetypes );

            $sql="SELECT * ";
            $fromsql = " FROM ( SELECT concat(c.id, c.fullname) as fid, c.id, c.open_grade as grade, c.fullname,c.open_identifiedas as type,c.selfenrol as selfenrol,c.approvalreqd as approvalreqd,c.expirydate as expirydate, c.open_courseprovider as open_courseprovider,c.summary as summary FROM {course} AS c WHERE c.visible = 1 AND c.id IN ($featured_courses->featured_course_ids) 
                                 AND c.open_identifiedas NOT IN ($ctypes) and (c.expirydate =0 OR c.expirydate IS NULL OR c.expirydate >= ".$currenttime.")";
           
            $fromsql .= " UNION ALL ";
          
            $fromsql .= " SELECT concat(lp.id, lp.name) as fid, lp.id, lp.open_grade as grade,lp.name as fullname, 'LearningPath' as type,lp.selfenrol as selfenrol,lp.approvalreqd as approvalreqd, 'Expirydate' as expirydate, 'Provider' as open_courseprovider,lp.description as summary FROM {local_learningplan} AS lp WHERE lp.id IN ($featured_courses->featured_lpath_ids) ";
            $fromsql .= " ) As tbl ";
            $fromsql .= " WHERE tbl.selfenrol = 1 ";

            if (!empty($stable->search)) {
                $fields = array(
                    0 => 'tbl.fullname',
                );
                $fields = implode(" LIKE '%" . $stable->search . "%' OR ", $fields);
                $fields .= " LIKE '%" . $stable->search . "%' ";
                $fromsql .= " AND ($fields) ";
            }

             if(!is_siteadmin() &&  !has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){            
                if(!empty($USER->open_grade) && $USER->open_grade != ""){                 
                   $fromsql .= " AND ( (concat(',',tbl.grade,',') LIKE '%,$USER->open_grade,%' ) OR tbl.grade = '-1' OR tbl.grade IS NULL )";
                } else {
                   $fromsql .= " AND (tbl.grade = '0' OR tbl.grade = '-1' OR tbl.grade IS NULL ) ";
                }
             }
            
            $fromsql .= " ORDER BY tbl.id DESC "; 

            $allcoursecount = $DB->count_records_sql($countsql . $fromsql);
          
            if ($stable->thead == false) {    
                $selectedcourses = $DB->get_records_sql($sql . $fromsql,array(),$stable->start, $stable->length);                
            }     
        }
        try {
            $allcoursecount = $allcoursecount;
        } catch (dml_exception $ex) {
            $allcoursecount = 0;
        }
        return compact('selectedcourses', 'allcoursecount');
    }
}
