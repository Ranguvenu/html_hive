<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @package   local
 * @subpackage  courses
 * @author eabyas  <info@eabyas.in>
**/
namespace local_courses\local;
class general_lib{
	public function get_custom_data($fields = '*', $params){
		global $DB;
		$sql = "SELECT {$fields} FROM {course} WHERE 1=1 ";
		foreach($params AS $key => $value){
			if($key == 'unique_module')
				continue;
			$sql .= " AND {$key} =:{$key} ";
		}
		if((isset($params['unique_module']) && $params['unique_module'] ==  true) || (isset($params['id']) && $params['id'] > 0) ){
			$data = $DB->get_record_sql($sql, $params);
		}else{
			$data = $DB->get_records_sql($sql, $params);
		}
		return $data;
	}
	public function get_module_logo_url($courseid){
		global $CFG;
		if(file_exists($CFG->dirroot.'/local/includes.php')){
			require_once($CFG->dirroot.'/local/includes.php');
			$courseobject = get_course($courseid);
			$includes = new \user_course_details();
			$url_object = $includes->course_summary_files($courseobject);
			return $url_object;
		}
	}
	public function enable_enrollment_to_module($courseid, $user){
		$coursecontext = \context_course::instance($courseid);
		if(is_enrolled($coursecontext, $user, '', $onlyactive = true)){
			return true;
		}
		$params = array('id' => $courseid);
		$coursedata = $this->get_custom_data('*', $params);
		if(($coursedata->open_costcenterid == $user->open_costcenterid) && 
			($coursedata->open_departmentid == $user->open_departmentid 
				|| $coursedata->open_departmentid == 0) && 
			($coursedata->open_subdepartment == $user->open_subdepartment 
				|| $coursedata->open_subdepartment == 0)
		){
			$classname = "\\local_request\\api\\requestapi";
			if(class_exists($classname)){
				$class = new $classname();
				if($coursedata->selfenrol == 1 && $coursedata->approvalreqd == 1){
					if(method_exists($class, 'create')){
						$class->create('local_courses', $courseid);
					}
				}else if($coursedata->selfenrol == 1){
					if(method_exists($class, 'enroll_to_component')){
						$class->enroll_to_component('local_courses', $courseid, $user->id);
					}
				}
			}
		}
	}
	public function get_completion_count_from($moduleid, $userstatus, $date = NULL){
		global $DB;
		$params = array('moduleid' => $moduleid);
		switch($userstatus){
			case 'enrolled':
				$count_sql = "SELECT count(ue.id) FROM {user_enrolments} AS ue 
					JOIN {enrol} AS e ON e.id = ue.enrolid 
					WHERE e.enrol IN ('self', 'manual', 'auto') AND e.courseid = :moduleid ";
				if(!is_null($date)){
					$count_sql .= " AND ue.timecreated > :fromtime ";
					$params['fromtime'] = $date;
				}	
			break;
			case 'completed':
				$count_sql = "SELECT count(cc.id) FROM {course_completions} AS cc 
					JOIN {enrol} AS e ON e.courseid = cc.course AND e.enrol IN ('self', 'manual', 'auto')
					JOIN {user_enrolments} AS ue ON ue.enrolid = e.id AND ue.userid = cc.userid
					WHERE cc.course = :moduleid AND cc.timecompleted IS NOT NULL ";
				if(!is_null($date)){
					$count_sql .= " AND cc.timecompleted > :fromtime ";
					$params['fromtime'] = $date;
				}
			break;
		}
		$count = $DB->count_records_sql($count_sql, $params);
		return $count;
	}
	public function get_custom_icon_details(){
		return ['componenticonclass' => 'fa fa-book', 'customimage_required' => False];
	}

	/******Function to the show the inprogress course names in the E-learning Tab********/
    public static function inprogress_coursenames($filter_text='', $offset = 0, $limit = 10, $coursestype) {
        global $DB, $USER;    
        
        $sqlquery = "SELECT course.id,course.fullname,course.summary,course.expirydate";
        
        $sql = " FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN ('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid ";
        
        $sql .= " WHERE ue.userid = {$USER->id} AND course.id <> 1 AND course.visible=1 ";

        if($coursestype == 'mooc'){
            $sql .= " AND CONCAT(',',course.open_identifiedas,',') LIKE CONCAT('%,',1,',%') ";
        }else if($coursestype == 'ilt'){
            $sql .= " AND CONCAT(',',course.open_identifiedas,',') LIKE CONCAT('%,',2,',%') ";
        }else{
            $sql .= " AND CONCAT(',',course.open_identifiedas,',') LIKE CONCAT('%,',3,',%') ";
        }

        if(!empty($filter_text)){
            $sql .= " AND course.fullname LIKE '%%{$filter_text}%%'";
        }
        
        $sql .= " AND course.id NOT IN(SELECT course FROM {course_completions} WHERE course = course.id AND userid = {$USER->id} AND timecompleted IS NOT NULL) ";
        
        $sql .= ' ORDER BY ue.timecreated desc';

        $inprogress_courses = $DB->get_records_sql($sqlquery . $sql, array(), $offset, $limit);
        
        return $inprogress_courses;
        
    }

    public static function inprogress_coursenames_count($filter_text = '', $coursestype){
        global $USER, $DB;
        $sql = "SELECT COUNT(course.id) 
                FROM {course} AS course
                JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid 
                WHERE course.visible = 1 AND ue.userid = {$USER->id} AND course.id <> 1 ";
        
        if($coursestype == 'mooc'){
            $sql .= " AND CONCAT(',',course.open_identifiedas,',') LIKE CONCAT('%,',1,',%') ";
        }else if($coursestype == 'ilt'){
            $sql .= " AND CONCAT(',',course.open_identifiedas,',') LIKE CONCAT('%,',2,',%') ";
        }else{
            $sql .= " AND CONCAT(',',course.open_identifiedas,',') LIKE CONCAT('%,',3,',%') ";
        }
        $sql .= " AND course.id NOT IN ( SELECT course 
                                        FROM {course_completions} 
                                        WHERE course = course.id AND userid = {$USER->id} 
                                        AND timecompleted IS NOT NULL) ";

        // if(!empty($filter_text)){
        //    $sql .= " AND course.fullname LIKE '%%{$filter_text}%%'";
        // }
        
        $inprogress_count = $DB->count_records_sql($sql);


        return $inprogress_count;
    }
    /*********End of the Function*********/

    /******Function to the show the Completed course names in the E-learning Tab********/
    public static function completed_coursenames($filter_text='', $offset = 0, $limit = 10, $coursestype) {
        global $DB, $USER;

        $sqlquery = "SELECT cc.id as completionid,c.id,c.fullname,c.summary";

        $sql .= " FROM {course_completions} cc
                JOIN {course} c ON c.id = cc.course AND cc.userid = $USER->id
                JOIN {enrol} e ON c.id = e.courseid AND e.enrol IN('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                WHERE ue.userid = {$USER->id} AND c.open_costcenterid = $USER->open_costcenterid
                AND cc.timecompleted IS NOT NULL AND c.visible = 1 AND c.id > 1 ";

        if($coursestype == 'mooc'){
            $sql .= " AND CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',1,',%') ";
        }else if($coursestype == 'ilt'){
            $sql .= " AND CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',2,',%') ";
        }else{
            $sql .= " AND CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',3,',%') ";
        }

        if(!empty($filter_text)){
           $sql .= " AND c.fullname LIKE '%%{$filter_text}%%'";
        }
        $sql .= " ORDER BY cc.timecompleted DESC ";

        $coursenames = $DB->get_records_sql($sqlquery . $sql, array(), $offset, $limit);
        return $coursenames;
    }
    /********end of the Function****/

    public static function completed_coursenames_count($filter_text = '', $coursestype){
    	global $DB, $USER;
    	$sql = "SELECT COUNT(c.id) 
    			FROM {course_completions} cc
                JOIN {course} c ON c.id = cc.course AND cc.userid = {$USER->id}
                JOIN {enrol} e ON c.id = e.courseid AND e.enrol IN('self','manual','auto')
                JOIN {user_enrolments} ue ON e.id = ue.enrolid
                WHERE ue.userid = {$USER->id} AND c.visible = 1 AND c.id > 1 
                AND cc.timecompleted IS NOT NULL";
        if($coursestype == 'mooc'){
            $sql .= " AND CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',1,',%') ";
        }else if($coursestype == 'ilt'){
            $sql .= " AND CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',2,',%') ";
        }else{
            $sql .= " AND CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',3,',%') ";
        }
        $completed_count = $DB->count_records_sql($sql);
        return $completed_count;
    }
    public function get_courses_having_completion_criteria($courseid, $query = '', $offset = 0, $limit = 0){
    	global $DB, $CFG;
    	require_once($CFG->libdir.'/completionlib.php');
        $coursesSql = "SELECT DISTINCT c.id, c.fullname
            FROM {course} c
            LEFT JOIN {course_completion_criteria} cc ON cc.courseinstance = c.id AND cc.course = {$courseid}
            INNER JOIN {course_completion_criteria} ccc ON ccc.course = c.id
            WHERE c.enablecompletion = ".COMPLETION_ENABLED."  AND c.id <> :courseid 
            
            AND c.open_costcenterid = (SELECT open_costcenterid FROM {course} WHERE id = :thiscourseid) ";
            // AND c.id NOT IN (SELECT course.id 
            // 			FROM {course_completion_criteria} innercc 
            //    			JOIN {course} AS course ON innercc.courseinstance = course.id 
            //     		WHERE innercc.course = {$courseid})
        $params = array('courseid' => $courseid, 'thiscourseid' => $courseid);
        if($query != ''){
            $coursesSql .= " AND ".$DB->sql_like('fullname', ":search", false);
            $params['search'] = "%$query%";
        }
        $courses = $DB->get_records_sql($coursesSql, $params, $offset, $limit);
        return $courses;
    } 
}
