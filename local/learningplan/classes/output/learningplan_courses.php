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
 * elearning  courses
 *
 * @package    block_userdashboard
 * @copyright  2018 Maheshchandra <maheshchandra@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_learningplan\output;

use renderer_base;
use context_course;
use stdClass;
use local_learningplan\local\userdashboard_content  as DashboardLearningplan;
use block_userdashboard\includes\generic_content;
use local_learningplan\lib\lib as learninngplan_lib;

class learningplan_courses implements \renderable, \templatable {

    //-----hold the courselist inprogress or completed 
    private $courseslist;

    private $subtab='';

    private $learningplantemplate=0;

    private $filter_text='';
    
    private $filter='';
    
    public function __construct($filter, $filter_text='', $offset, $limit){
       $this->inprogressCount = DashboardLearningplan::inprogress_lepnames_count($filter_text);
       $this->completedCount = DashboardLearningplan::completed_lepnames_count($filter_text);
        switch ($filter){
            case 'inprogress':
                    $this->courseslist = DashboardLearningplan::inprogress_lepnames($filter_text, $offset, $limit);
                    $this->coursesViewCount = $this->inprogressCount;
                    $this->subtab='elearning_inprogress'; 
                break;

            case 'completed' :                           
                    $this->courseslist = DashboardLearningplan::completed_lepnames($filter_text, $offset, $limit);
                    $this->coursesViewCount = $this->completedCount;
                    $this->subtab='elearning_completed';
                break; 
            
        }
        $this->filter = $filter;  
        $this->filter_text = $filter_text;
        $this->learningplantemplate=1;

    } // end of the function
    

    


    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $USER, $CFG, $PAGE;
       
        $data = new stdClass(); 
        $courses_active = ''; 
        $tabs = '';  
        $data->inprogress_elearning = array();    

        $inprogresscount = $this->inprogressCount;
        $completedcount = $this->completedCount;
        $total = $inprogresscount+$completedcount;
        // $total = DashboardLearningplan::gettotal_programs();

        $data->course_count_view =0;
        $data->total =$total;
        $data->index = $this->coursesViewCount > 10 ? 9 : $this->coursesViewCount-1;
        $data->viewMoreCard = $this->coursesViewCount > 10 ? true : false;
        $data->filter = $this->filter;
        $data->inprogresscount= $this->inprogressCount;
        $data->completedcount = $this->completedCount;
        $data->functionname ='learningplan_courses';
        $data->subtab= $this->subtab;
        $data->view_more_url = $CFG->wwwroot.'/local/learningplan/userdashboard.php?tab='.explode('_',$this->subtab)[1];
        $data->learningplantemplate= $this->learningplantemplate;
        $data->filter_text = $this->filter_text;
        $plan_view_count = $this->coursesViewCount;
        $data->plan_view_count = $plan_view_count;
        if($plan_view_count > 2)
            $data->enableslider = 1;
        else    
            $data->enableslider = 0;
        
        if (!empty($this->courseslist)) 
            $data->inprogress_elearning_available = 1;
        else
            $data->inprogress_elearning_available = 0;

        
        if (!empty($this->courseslist)) {

            $data->course_count_view = generic_content::get_coursecount_class($this->courseslist);
            $i = 0;
            foreach ($this->courseslist as $inprogress_coursename) {
                $onerow=array();
                $onerow['index'] = $i++;
                // $i++;
                $onerow['inprogress_coursename'] = $inprogress_coursename;
                $lastaccessstime= $DB->get_field('user_lastaccess', 'timeaccess', array('userid'=>$USER->id, 'courseid' => $inprogress_coursename->id));

                /* get the courses of learning path added by rizwana */
                $lplanassignedcourses = learninngplan_lib::get_learningplan_assigned_courses($inprogress_coursename->id);
                $coursescount = count($lplanassignedcourses);
                $onerow ['pathcourses'] = array();
                if(count($lplanassignedcourses)>=2) {
                    $i = 1;
                    foreach($lplanassignedcourses as $assignedcourse){ 
                            $onerow ['pathcourses'][] = array('coursename'=>$assignedcourse->fullname, 'coursename_string'=>'C'.$i);
                        $i++;

                        if($i>10){
                            break;
                        }
                    }
                    
                }
                $onerow['lastaccessstime']= $lastaccessstime;
                $onerow['lastaccessdate'] = date('d/m/y', $lastaccessstime);
               
                $course_record = $DB->get_record('course', array('id' => $inprogress_coursename->id));

             
                // $onerow['plan_image_url'] = learninngplan_lib::get_learningplansummaryfile($inprogress_coursename->id);    

                //-------- get the course summary------------------------ 
                $onerow['planSummary']= $this->get_coursesummary($inprogress_coursename);

                //---------get course fullname-----
                $onerow['planFullname'] = $inprogress_coursename->fullname;
                $onerow['displayPlanFullname'] = $this->get_coursefullname($inprogress_coursename);
                $onerow['planUrl'] = $CFG->wwwroot.'/local/learningplan/view.php?id='.$inprogress_coursename->id;
                if(class_exists('local_ratings\output\renderer')){
                    $rating_render = $PAGE->get_renderer('local_ratings');
                    $onerow['rating_element'] = $rating_render->render_ratings_data('local_learningplan', $inprogress_coursename->id);
                }else{
                    $onerow['rating_element'] = '';
                }
             
                array_push($data->inprogress_elearning, $onerow);
                
            } // end of foreach 

        } // end of if condition     
        // print_object($data);
        $data->moduledetails = $data->inprogress_elearning;
        $data->menu_heading = get_string('learningpaths','block_userdashboard');
        $data->nodata_string = get_string('nolearningplansavailable','block_userdashboard');
        return $data;
    } // end of export_for_template function.


    private function get_coursesummary($course_record){   
        $coursesummary = strip_tags($course_record->description);
        $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
        $coursesummary = $summarystring;
        if(empty($coursesummary)){
            $coursesummary = '<span class="w-full alert alert-info pull-left text-center">'.get_string('nodecscriptionprovided','block_userdashboard').'</span>';
        }

        return $coursesummary;
    } // end of get_coursesummary function


    private function get_coursefullname($inprogress_coursename){

        $course_fullname = $inprogress_coursename->fullname;
        if (strlen($course_fullname) >= 38) {
            $inprogress_coursename_fullname = substr($inprogress_coursename->fullname, 0, 38) . '...';
        } else {
            $inprogress_coursename_fullname = $inprogress_coursename->fullname;
        }

        return $inprogress_coursename_fullname;
    } // end of  get_coursesummary function



} // end of class
