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
 * @subpackage local_learningsummary
 */

namespace local_learningsummary\output;
defined('MOODLE_INTERNAL') || die;
use renderable;
use renderer_base;
use templatable;
use context_system;
use moodle_url;
use core_completion\progress;
use local_learningplan\lib\lib as lpn;
require_once($CFG->dirroot . '/local/learningsummary/lib.php');
require_once($CFG->dirroot . '/local/courses/lib.php');
require_once($CFG->libdir . '/completionlib.php');

class summary implements renderable, templatable {
    /**
     * [__construct description]
     * @method __construct
     */
    public function __construct($courses) {
        $this->context = context_system::instance();
        $this->allcourses = $courses;
      
    }
    /**
     * [export_for_template description]
     * @method export_for_template
     * @param  renderer_base       $output [description]
     * @return [type]                      [description]
     */
    public function export_for_template(renderer_base $output) {
        
        global $DB, $CFG, $USER,$OUTPUT;        
        require_once($CFG->libdir.'/enrollib.php');
        
        $allcourses =$this->allcourses;
        $row=array();
       
        $numco = count($allcourses);
          
        if(file_exists($CFG->dirroot.'/local/includes.php')){
            require_once($CFG->dirroot.'/local/includes.php');
            $includes = new \user_course_details();              
        }   
       
        foreach($allcourses as $course) {
            $summerylength = strlen($course->summary);
            $coursename = strlen($course->fullname);
            $courseid =  $course->id;
            $expirydate = $course->expirydate;
            if ($summerylength == 0) {
                $coursesummary = get_string('nodesc','local_courses');
            } else if ($summerylength >= 100) {
                $coursesummary = clean_text(substr(strip_tags($course->summary), 0, 100)).'...';
            } else {
                $coursesummary = clean_text($course->summary);
            }

            if ($coursename >= 62) {
                $coursefullname = clean_text(substr(strip_tags($course->fullname), 0, 62)).'...';
            } else {
                $coursefullname = strip_tags(clean_text($course->fullname));
            }
         
            $modules = 0;
            $course->coursename = strip_tags(clean_text($course->fullname));           
            $progressflag = false;
            if($course->open_identifiedas == 'ilt'){
                $course_type = 'ILT';
                $courseurl = new moodle_url('/local/classroom/view.php',array('cid'=>$course->id));
                $courseurl =$courseurl->out(false);               
                $expirydate = '';
            }else if($course->open_identifiedas == 'learningpath'){
                $progressflag = true;
                $course_type = 'Learning Path';
                $expirydate = '';
                $courseurl = new moodle_url('/local/learningplan/view.php',array('id'=>$course->id));
                $courseurl =$courseurl->out(false);  
                $lp_enrolledusers = $DB->record_exists('local_learningplan_user',array('planid' => $courseid,'userid' => $USER->id));
                if($lp_enrolledusers){
                     $lparams = array();
                     $lpusercoursecountsql = "SELECT COUNT(llc.courseid) as ccount
                                 FROM {local_learningplan_courses} AS llc 
                                 WHERE llc.planid = :planid AND llc.nextsetoperator = 'and'";
                     $lparams['planid'] = $course->id;
                     $lp_user_coursecount = $DB->count_records_sql($lpusercoursecountsql,$lparams);
  
                     $lp_params = array();
                     $coursesql = "SELECT cc.id,cc.course FROM {course_completions} AS cc 
                           JOIN {local_learningplan_courses} AS llc ON llc.courseid = cc.course
                           WHERE cc.userid = :userid AND llc.planid = :lplanid AND llc.nextsetoperator = 'and' AND cc.timecompleted IS NOT NULL ";
                     $lp_params['userid'] = $USER->id;
                     $lp_params['lplanid'] = $courseid;
                     $coursecompletions = $DB->get_records_sql_menu($coursesql,$lp_params);
                     $courseresult = count($coursecompletions);
                     if(!empty($lp_user_coursecount)){                        
                         $progress = round(($courseresult/$lp_user_coursecount)*100);
                     }
                }else{
                    $progress = 0;
                }
                if (!$progress) {
                    $progress = 0;
                    $progress_bar_width = "min-width: 0px;";
                } else {
                    $progress = round($progress);
                    $progress_bar_width = "min-width: 0px;";
                }
            }else{
                $progressflag = true;
                $ctypes = $DB->get_record('local_course_types', array('id' => $course->open_identifiedas,'active' => 1),'id,course_type,shortname', $strictness=IGNORE_MISSING);
                $course_type  = $ctypes->course_type ;
                $courseurl = new moodle_url('/course/view.php',array('id'=>$course->id));
                $courseurl =$courseurl->out(false);
                $completion = new \completion_info($course);
                $expirydate = ($course->expirydate)?$course->expirydate :'';
                if ($completion->is_enabled()) {
                    $percentage = progress::get_course_progress_percentage($course, $USER->id);
                       if (!is_null($percentage)) {
                         $percentage = floor($percentage);
                       }
                         $progress  = $percentage; 
                    }
                if (!$progress) {
                    $progress = 0;
                } else {
                    $progress = round($progress);
                }
            }           
            $bookmarks = $DB->get_record_sql("SELECT * FROM {block_custom_userbookmark} WHERE userid = $USER->id AND courseid = $courseid");
            $bookmarkurl = $bookmarks->url;
            $contextitem = (object)[
                'coursenums' => $numco,
                'courseid' => $courseid,
                'url' => $courseurl,
                'title' => $coursefullname,
                'coursename' => $course->coursename,
                'description' => $coursesummary,
                'modules' => $modules ? $modules : 'N/A',
                'enrolled' => false,
                'coursetype' => $course_type ,
                'progress' => (is_nan($progress)) ? 0 : $progress,
                'progressflag' => $progressflag,
                'bookmarkurl' => $bookmarkurl,
                'expirydate' => !empty($expirydate) ? date('d-m-Y', $expirydate) : '',
           ];

         /*   if(file_exists($CFG->dirroot.'/local/includes.php')){
                if($course->open_identifiedas == 'learningpath'){
                    $courseimage = (new lpn)->get_learningplansummaryfile($learningplanid);      
                }else{
                    $courseimage = $includes->course_summary_files($course);    
                }            
                if(is_object($courseimage)){
                    $contextitem->imageurl = $courseimage->out();                    
                }else{
                    $contextitem->imageurl = $courseimage;
                } 
            } */


           if($course->open_identifiedas == 'learningpath' ){

                $contextitem->imageurl = (new lpn)->get_learningplansummaryfile($course->id);      
                     
            }else  if($course->open_identifiedas == 'ilt' ){
                 $classroominclude = new \local_classroom\includes();
                 $courseimage = $classroominclude->get_classroom_summary_file($course); 
                //$classroominclude = new \local_classroom\classroom();
                //$contextitem->imageurl = $classroominclude->classroom_logo($sdata->classroomlogo);
                if(is_object($courseimage)){
                    $contextitem->imageurl = $courseimage->out();                    
                }
                if($contextitem->imageurl == false){
                    $contextitem->imageurl = $OUTPUT->image_url('classviewnew', 'local_classroom');
                } 
                       
             }else{
                 $contextitem->imageurl = course_thumbimage($course);
             }
           
            $row[]=(array)$contextitem;
        }
        return $row;
    }
}
