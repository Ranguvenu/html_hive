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
* @package BizLMS
* @subpackage block_program_pathways
*/

global $DB, $OUTPUT, $USER, $CFG, $PAGE;
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
use core_component;
require_once $CFG->dirroot . '/local/includes.php';

class block_learning_progress_renderer extends plugin_renderer_base {
     
     public function learning_progress_track_view_learningplan(){
          global $DB, $PAGE, $USER;
          
          //Start of learning plan inprogress and completed information
          $lp_sql = "SELECT COUNT(llp.id)";
          
          $lp_fromsql = " FROM {local_learningplan} llp ";
          
          $lp_joinsql = " JOIN {local_learningplan_user} lla ON llp.id = lla.planid ";
          
          $lp_wheresql = " WHERE userid = {$USER->id}";
          
          $lp_no_completion_date = " AND lla.completiondate IS NULL AND status IS NULL AND llp.visible = 1 ";
          
          $lp_completion_date = " AND lla.completiondate IS NOT NULL AND status = 1 AND llp.visible = 1 ";
          
          $inprogress_lp = $DB->count_records_sql($lp_sql.$lp_fromsql.$lp_joinsql.$lp_wheresql.$lp_no_completion_date);
          
          $completed_lp = $DB->count_records_sql($lp_sql.$lp_fromsql.$lp_joinsql.$lp_wheresql.$lp_completion_date);          
          //End of learning plan inprogress and completed information
          
          //Start of courses inprogress and completed information          
          $coursetype_sql = "SELECT id,shortname FROM {local_course_types} WHERE shortname NOT IN ( 'learningpath' , 'ilt')";
          $coursetype = $DB->get_fieldset_sql($coursetype_sql);
          $coursetypes = implode(",",$coursetype);
          
          $sql = "SELECT COUNT(DISTINCT(c.id)) F ";
          
          $joinsql = "FROM {course} c 
          JOIN {enrol} e ON c.id = e.courseid AND e.enrol IN('self','manual','auto')
          JOIN {user_enrolments} ue ON e.id = ue.enrolid";
          
          $completed_where_sql = " WHERE ue.userid = {$USER->id} AND c.open_identifiedas IN ( $coursetypes ) AND c.id IN (SELECT course FROM {course_completions} WHERE course = c.id AND userid = {$USER->id} AND timecompleted IS NOT NULL)  AND c.visible = 1 AND c.id > 1 ";
          $inprogresscourse_where_sql = " WHERE ue.userid = {$USER->id} AND c.open_identifiedas IN ( $coursetypes ) AND c.id NOT IN (SELECT course FROM {course_completions} WHERE course = c.id AND userid = {$USER->id} AND timecompleted IS NOT NULL)  AND c.id > 1 AND c.visible = 1 ";
          
          $inprogress_courses = $DB->count_records_sql($sql.$joinsql.$inprogresscourse_where_sql); //inprogress course count
          
          $completed_course = $DB->count_records_sql($sql.$joinsql.$completed_where_sql); //completed course count
          //End of courses inprogress and completed information
          
          //Start of ilt/classroom inprogress and completed information  
          $ilt_selectsql = "SELECT COUNT(lc.id) ";
          $ilt_fromsql = " FROM {local_classroom} AS lc 
                 JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid ";
          $ilt_where_sql = " WHERE lcu.userid = {$USER->id} and lc.visible=1 ";

          $ilt_inprogress_sql = " AND lc.status = :inprogressstatus ";         
          
          $ilt_completed_sql = " AND lc.status=:completedstatus ";
         
          $inprogress_ilts = $DB->count_records_sql($ilt_selectsql.$ilt_fromsql.$ilt_where_sql.$ilt_inprogress_sql,array('inprogressstatus' => 1)); //inprogress ilt count
          
          $completed_ilts = $DB->count_records_sql($ilt_selectsql.$ilt_fromsql.$ilt_where_sql.$ilt_completed_sql,array('completedstatus' => 4)); //completed ilt count
          //End of ilt inprogress and completed information

          $courses_inprogress = $inprogress_lp + $inprogress_courses + $inprogress_ilts;
          $courses_completed = $completed_lp + $completed_course +  $completed_ilts;
          $total_courses = $courses_inprogress + $courses_completed;
          
          $courseurl = new moodle_url('/local/learningsummary/index.php',array());
          $courseurl =$courseurl->out(false);
          if (!empty($total_courses)){
            $learning_progress_count = round(($courses_completed/$total_courses)*100);
          }
          
          $data = array();
          
          $data['course_inprogresscount'] = $courses_inprogress;
          $data['course_completed_count'] = $courses_completed;
          $data['know_more_url'] = $courseurl;
          $data['total_courses'] = $total_courses;
          $data['progress'] = $learning_progress_count;
          
          $return = $this->render_from_template('block_learning_progress/learning_progress',$data);
          return $return;
     }
}
