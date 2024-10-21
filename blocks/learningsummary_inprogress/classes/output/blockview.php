<?php
/**
 * Renderable for course list view.
 *
 * @author eabyas  <info@eabyas.in>

 * @package fractal
 * @subpackage block_learningsummary_inprogress
 */

namespace block_learningsummary_inprogress\output;

use block_learningsummary_inprogress\plugin;
use core_completion\progress;
use local_learningplan\lib\lib as lpn;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use moodle_url;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');

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

    public function export_for_template(renderer_base $output) {
        global $DB, $CFG, $USER;
        require_once($CFG->libdir.'/enrollib.php');
        $inprogresscourses = plugin::get_inprogress_content($this->stable,$this->filtervalues,$this->config);
        $allcourses =$inprogresscourses['allcourses'];
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
            
            if ($summerylength == 0) {
               $coursesummary = get_string('no_data_available','block_learningsummary_inprogress');
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
               
            }else if($course->open_identifiedas == 'learningpath'){
                $progressflag = true;
                $course_type = 'Learning Path';
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
                $ctypes = $DB->get_record('local_course_types', array('id' => $course->open_identifiedas,'active' => 1),'id,course_type,shortname', $strictness=IGNORE_MISSING);
                $course_type  = $ctypes->course_type ;
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
                'coursetype' => $course_type ,
                'progress' => (is_nan($progress)) ? 0 : $progress,
                'progressflag' => $progressflag,
                'expirydate' => !empty($course->expirydate) ? date('d-m-Y', $course->expirydate) : 'N/A',
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
