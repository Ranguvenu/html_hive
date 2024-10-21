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
 * Renderable for course list view.
 *
 * @author eabyas  <info@eabyas.in>

 * @package Bizlms
 * @subpackage block_learningsummary_complete
 */

namespace block_learningsummary_completed\output;

use block_learningsummary_completed\plugin;
use core_completion\progress;
use local_learningplan\lib\lib as lpn;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use moodle_url;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');

/**
 * Class view
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_completed
 */
final class blockview implements renderable, templatable {

    /** @var stdClass|null */
    private $config;
   
    private $stable;

    private $filtervalues;

    /**
     * blockview constructor.
     * @param stdClass|null $config
     */
    public function __construct($config,$stable,$filtervalues) {
        $this->config = $config;
        $this->stable = $stable;
        $this->filtervalues = $filtervalues;
    }

    // /**
    //  * Generate template
    //  * @param renderer_base $output
    //  * @return array
    //  * @throws moodle_exception
    //  */
  
   public function export_for_template(renderer_base $output) {
        global $DB, $CFG, $USER;
        require_once($CFG->libdir.'/enrollib.php');
        $completedcourses = plugin::get_completed_content($this->stable,$this->filtervalues,$this->config);
        $allcourses =$completedcourses['allcourses'];
        $row=array();
       
        $numco = count($courses);
          
        if(file_exists($CFG->dirroot.'/local/includes.php')){
            require_once($CFG->dirroot.'/local/includes.php');
            $includes = new \user_course_details();              
        }   
       
        foreach($allcourses as $course) {
            $summerylength = strlen($course->summary);
            $coursename = strlen($course->fullname);
            $courseid =  $course->id;
            
            if ($summerylength == 0) {
               $coursesummary = get_string('no_data_available','block_learningsummary_completed');
            } else if ($summerylength >= 100) {
                $coursesummary = substr(strip_tags(clean_text($course->summary)), 0, 100).'...';
            } else {
                $coursesummary = clean_text($course->summary);
            }

            if ($coursename >= 62) {
                $coursefullname = substr($course->fullname, 0, 62).'...';
            } else {
                $coursefullname = $course->fullname;
            }
         
            $modules = 0;
            $course->coursename = $course->fullname;            
            $coursetype = '';
            $progressflag = false;
            if($course->open_identifiedas == 'ilt'){
                $course_type = 'ILT';
                $courseurl = new moodle_url('/local/classroom/view.php',array('cid'=>$course->id));
                $courseurl =$courseurl->out(false);   
            }else if($course->open_identifiedas == 'learningpath'){ 
                $progressflag = true;
                $coursetype = 'Learning Path';
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
                     $progress =0;
                     $progress = round(($courseresult/$lp_user_coursecount)*100);
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
                $ctypes =  $DB->get_record('local_course_types', array('id' => $course->open_identifiedas,'active' => 1),'course_type', $strictness=IGNORE_MISSING);
                $coursetype = $ctypes->course_type;
                $courseurl = new moodle_url('/course/view.php',array('id'=>$course->id));
                $courseurl =$courseurl->out(false);
                $completion = new \completion_info($course);
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
           

            $contextitem = (object)[
                'coursenums' => $numco,
                'courseid' => $courseid,
                'url' => $courseurl,
                'title' => $coursefullname,
                'description' => $coursesummary,
                'modules' => $modules ? $modules : 'N/A',
                'enrolled' => false,
                'coursetype' => $coursetype ,
                'progress' => (is_nan($progress)) ? 0 : $progress,
                'progressflag' => $progressflag,
           ];

           if(file_exists($CFG->dirroot.'/local/includes.php')){
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
            }
           $row[]=(array)$contextitem;
        }
        return $row;
    }
}
