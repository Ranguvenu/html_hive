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
 *
 * @author eabyas  <info@eabyas.in>
 */
namespace local_learningplan;

define('LEARNINGPLAN_NOT_ENROLLED', 0);
define('LEARNINGPLAN_ENROLLED', 1);
define('LEARNINGPLAN_ENROLMENT_REQUEST', 2);
define('LEARNINGPLAN_ENROLMENT_PENDING', 3);

class learningplan {
    /**
     * [userlearningplans description]
     * @param  string $status [description]
     * @param  string $search [description]
     * @return [type]         [description]
     */
    public function userlearningplans($status = 'inprogress', $search = '', $limit = '', $mobile = false, $page=0, $perpage=10) {
        global $DB, $USER, $CFG;
        $coursesinfo = self::learningplancoursestypeinfo(true);
        $sqlquery = "SELECT llp.id, llp.name, llp.description, llp.learning_type, IF(llp.learning_type = 1, 'Core Courses', 'Elective Courses') AS learningplantype, llp.open_points $coursesinfo ";
        $sqlcount = "SELECT COUNT(llp.id)";
        $userlearningplanssql = " FROM {local_learningplan} llp
                                   JOIN {local_learningplan_user} lla ON llp.id = lla.planid
                                  WHERE userid = :userid AND llp.visible = :visible ";
        if ($status == 'inprogress') {
            $userlearningplanssql .= ' AND lla.completiondate is NULL AND status is NULL';
        } else if ($status == 'completed') {
            $userlearningplanssql .= ' AND lla.completiondate is NOT NULL AND status = 1';
        }
        if (!empty($search)) {
            $userlearningplanssql .= " AND llp.name LIKE '%%$search%%'";
        }
        if ($status == 'inprogress' && $limit) {
            $userlearningplanssql .= " ORDER BY lla.id desc ";
            $limit = 5;
        }
        else {
            $userlearningplanssql .= " ORDER BY lla.id desc";
            $limit = 0;
        }
        $params = array();
        $params['userid'] = $USER->id;
        $params['visible'] = 1;
        // $params['userid'] = $USER->id;
        if ($mobile) {
             $userlearningplans = $DB->get_records_sql($sqlquery . $userlearningplanssql, $params, $page * $perpage, $perpage);
             $count = $DB->count_records_sql($sqlcount . $userlearningplanssql, $params);
             return array($userlearningplans, $count);

        } else {
            $userlearningplans = $DB->get_records_sql($sqlquery . $userlearningplanssql, $params, 0, $limit);
            return $userlearningplans;

        }
    }
    /**
     * [userlearningplansData description]
     * @return [type] [description]
     */
    public function userlearningplansData($userlearningplans){
        $mylearningplans = array();
        foreach ($userlearningplans as $userlearningplan) {
            $mylearningplan = (array)$userlearningplan;
            $mylearningplan['courses'] = self::userlearningplancourses($userlearningplan->id, '');
            $mylearningplans[] = $mylearningplan;
        }
        return $mylearningplans;
    }
    /**
     * [userlearningplancourses description]
     * @param  string $status [description]
     * @param  string $search [description]
     * @return [type]         [description]
     */
    public function userlearningplancourses($lpid, $search = '', $page=0, $perpage=10) {
        global $DB, $USER, $CFG;
        $query = "SELECT c.id, c.fullname, c.enablecompletion, c.summary, lc.sortorder, lc.id AS lepid, lc.nextsetoperator AS next, IF(lc.nextsetoperator = 'and', 'Mandatory', 'Optional') AS coursetype";
        $sqlcount = "SELECT COUNT(c.id)";
        $userlearningplancoursessql = " FROM {local_learningplan_courses} lc
                    JOIN {course} c ON c.id = lc.courseid";

        $params = array();
        $params['lpid'] = $lpid;
        $params['visible'] = 1;
        if(!empty($search)) {
            $userlearningplancoursessql .= " AND c.fullname LIKE '%%$search%%'";
        }
        $userlearningplancoursessql .= " WHERE lc.planid = $lpid ORDER BY lc.sortorder ASC";
        $userlearningplancourses = $DB->get_records_sql($query . $userlearningplancoursessql, $params, $page * $perpage, $perpage);
        $count = $DB->count_records_sql($sqlcount . $userlearningplancoursessql);
        return array($userlearningplancourses, $count);
    }
    public function learningplancoursestypecount($lpid, $type = ''){
        global $DB;
        $params = array();
        $learningplancoursestypecountsql = "SELECT COUNT(lc.id)
                    FROM {local_learningplan_courses} lc
                    JOIN {course} c ON c.id = lc.courseid
                    WHERE lc.planid = :planid " ;
        if ($type == 'and') {
            $learningplancoursestypecountsql .= " AND lc.nextsetoperator = :type ";
            $params['type'] = $type;
        } else if ($type == 'or') {
            $learningplancoursestypecountsql .= " AND lc.nextsetoperator = :type ";
            $params['type'] = $type;
        }
        $params['planid'] = $lpid;

        $learningplancoursestypecount = $DB->count_records_sql($learningplancoursestypecountsql);
        return $learningplancoursestypecount;
    }

    public function learningplancoursestypeinfo($subquery = true, $selectedtype = ''){
        global $DB;
        if (empty($selectedtype)) {
            $types = array('and', 'or');
        } else {
            $types = array($selectedtype);
        }
        $seperatedquery = ' ';
        if ($subquery) {
            $seperatedquery = ' , ';
        }
        $i = 0;
        $seperatedquery = ' ';
        $learningplancoursestypecountsql = '';
        foreach ($types as $type) {
            if ($subquery || $i == 1) {
                $seperatedquery = ' , ';
            }
            $i++;
            $learningplancoursestypecountsql .= " $seperatedquery (SELECT COUNT(lc.id)
                        FROM {local_learningplan_courses} lc
                        JOIN {course} c ON c.id = lc.courseid
                        WHERE lc.planid = llp.id " ;
            if ($type == 'and') {
                $columntype = 'mandatory';
                $learningplancoursestypecountsql .= " AND lc.nextsetoperator = '$type' ";
            } else if ($type == 'or') {
                $columntype = 'optional';
                $learningplancoursestypecountsql .= " AND lc.nextsetoperator = '$type' ";
            }
            $learningplancoursestypecountsql .= " ) AS " . $columntype ;
        }
        return $learningplancoursestypecountsql;
    }
    public function userlearningplancoursesInfo($lpid, $search = '', $page=0, $perpage=10) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot.'/local/ratings/lib.php');
        $data = array();
        $learningplan_classes_lib = new \local_learningplan\lib\lib ();
        list($userlearningplancourses, $count) = self::userlearningplancourses($lpid, $search, $page, $perpage);
        foreach ($userlearningplancourses as $userlearningplancourse) {
            $lpcourses = array();
            if ($userlearningplancourse->sortorder > 0 && $userlearningplancourse->next == 'and') {
                $coursestatus = $learningplan_classes_lib->get_previous_course_status($lpid,$userlearningplancourse->sortorder,$userlearningplancourse->id);
                if($coursestatus){
                    $disable_class1=1;
                }else{
                    $restricted= $DB->get_field('local_learningplan','lpsequence',array('id'=>$lpid));
                    if($restricted) {
                            $disable_class1=0;
                    }
                }
            }
            else{
                $disable_class1=1;
            }
            if ($userlearningplancourse->enablecompletion) {
                $progress = \core_completion\progress::get_course_progress_percentage($userlearningplancourse, $USER->id);
            }
            $modulerating = $DB->get_field('local_ratings_likes', 'module_rating', array('module_id' => $userlearningplancourse->id, 'module_area' => 'local_learningplan'));
            if(!$modulerating){
                 $modulerating = 0;
            }
            $likes = $DB->count_records('local_like', array('likearea'=> 'local_learningplan', 'itemid'=>$userlearningplancourse->id, 'likestatus'=>'1'));
            $dislikes = $DB->count_records('local_like', array('likearea'=> 'local_learningplan', 'itemid'=>$userlearningplancourse->id, 'likestatus'=>'2'));
            $lpcourses['id'] = $userlearningplancourse->id;
            $lpcourses['fullname'] = $userlearningplancourse->fullname;
            $lpcourses['visible'] = $disable_class1;
            $lpcourses['sortorder'] = $userlearningplancourse->sortorder;
            $lpcourses['lepid'] = $userlearningplancourse->lepid;
            $lpcourses['next'] = $userlearningplancourse->next;
            $lpcourses['coursetype'] = $userlearningplancourse->coursetype;
            $lpcourses['progress'] = $progress;
            $lpcourses['summary'] = $userlearningplancourse->summary;
            $lpcourses['rating'] = $modulerating;
            $lpcourses['likes'] = $likes;
            $lpcourses['dislikes'] = $dislikes;
            $avgratings = get_rating($userlearningplancourse->id, 'local_courses');
            $avgrating = $avgratings->avg;
            $ratingusers = $avgratings->count;
            $lpcourses['avgrating'] = $avgrating;
            $lpcourses['ratingusers'] = $ratingusers;
            $data[] = $lpcourses;
        }
        return array($data,$count);
    }

    public function enrol_status($enrol, $learningplan, $userid = 0){
        global $DB, $USER;
        $enrolled = $DB->get_field('local_learningplan_user', 'id', array('planid' => $learningplanid, 'userid' => $USER->id));
        $return = $enrolled ? LEARNINGPLAN_ENROLLED : LEARNINGPLAN_NOT_ENROLLED;
        $component = 'learningplan';
        if ($learningplan->approvalreqd == 1) {
            $requestsql = "SELECT status FROM {local_request_records}
                WHERE componentid = :componentid AND compname LIKE :compname AND
                createdbyid = :createdbyid ORDER BY id DESC ";
            $request = $DB->get_field_sql($requestsql ,array('componentid' => $learningplan->id,'compname' => $component, 'createdbyid' => $USER->id));
            if ($request == 'PENDING') {
                $return = LEARNINGPLAN_ENROLMENT_PENDING;
             } else {
                $return = LEARNINGPLAN_ENROLMENT_REQUEST;
            }
        } else {
            $return = LEARNINGPLAN_NOT_ENROLLED;
        }
        return $return;
    }

}
