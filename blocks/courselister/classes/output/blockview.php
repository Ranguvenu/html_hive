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
 * @subpackage block_courselister
 */

namespace block_courselister\output;

use block_courselister\plugin;
use local_search\output\cataloglib;
use local_learningplan\lib\lib as lpn;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use moodle_url;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/courses/lib.php');
/**
 * Class view
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_courselister
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

    /**
     * Generate template
     * @param renderer_base $output
     * @return array
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $CFG, $USER;
        require_once($CFG->libdir.'/enrollib.php');
  
        switch ($this->config->coursetype) {
            case plugin::SELECTEDCOURSES:
                $featuredcourses = plugin::get_selectedcourses($this->stable,$this->filtervalues,$this->config);
                $featuredcourses =$featuredcourses['selectedcourses'];
                break;
          
        }
        $row=array();
        
        $url = new moodle_url('/course/view.php');
        $count = count($featuredcourses);
            //course image
        if(file_exists($CFG->dirroot.'/local/includes.php')){
            require_once($CFG->dirroot.'/local/includes.php');
            $includes = new \user_course_details();              
        }   
       
        foreach ($featuredcourses as $course) {

            $enrolled_user_params = [];
            $enrolled_user_sql = "SELECT COUNT(DISTINCT(ue.id)) as ccount
                                FROM {course} c
                                JOIN {course_categories} cat ON cat.id = c.category
                                JOIN {enrol} e ON e.courseid = c.id AND 
                                            (e.enrol = 'manual' OR e.enrol = 'self') 
                                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                                JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1 
                                                AND u.deleted = 0 AND u.suspended = 0
                                JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
                                JOIN {role_assignments} as ra ON ra.userid = u.id
                                JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
                                WHERE c.id = :courseid";
            $enrolled_user_params['courseid'] = $course->id;
            $enrolled_user_count =  $DB->count_records_sql($enrolled_user_sql, $enrolled_user_params);
            if($course->type == 'LearningPath'){
                $enrolled_user_count=$DB->count_records_sql('SELECT count(llu.id) FROM {local_learningplan_user} as llu JOIN {user} as u ON u.id=llu.userid 
                                        WHERE llu.planid='.$course->id.' AND u.deleted!=1');
            }

            $completed_user_params = [];
            $completed_user_sql = "SELECT COUNT(DISTINCT(cc.id)) as ccount
                                FROM {course} c
                                JOIN {course_categories} cat ON cat.id = c.category
                                JOIN {enrol} e ON e.courseid = c.id AND 
                                            (e.enrol = 'manual' OR e.enrol = 'self') 
                                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                                JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1 
                                                AND u.deleted = 0 AND u.suspended = 0
                                JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
                                JOIN {role_assignments} as ra ON ra.userid = u.id
                                JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
                                JOIN {course_completions} as cc 
                                        ON cc.course = c.id AND u.id = cc.userid
                                WHERE c.id = :courseid AND cc.timecompleted IS NOT NULL ";
            $completed_user_params['courseid'] = $course->id;
            $completed_user_count = $DB->count_records_sql($completed_user_sql,$completed_user_params);
            if($course->type == 'LearningPath'){
                $completed_user_count=$DB->count_records_sql("SELECT count(id) FROM {local_learningplan_user} WHERE completiondate IS NOT NULL
                                                        AND status = 1 AND planid = $course->id");
            }

            // $course = $DB->get_record('course', array('id'=>$res->id));
            $summerylength = strlen($course->summary);
            $coursename = strlen($course->fullname);
          
            if ($summerylength == 0) {
                $coursesummary = get_string('nodesc','local_courses');
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
          
            if($course->type === 'LearningPath'){
              
                $coursetype = $course->type ;
                $learningplanid = $course->id;
                $lpname = $coursefullname;
                $url = new moodle_url('/local/learningplan/view.php',array('id'=>$learningplanid));             
                $url =$url->out(false); 
                $enrolled = $DB->get_field('local_learningplan_user','id', array('planid' => $learningplanid, 'userid' => $USER->id));                
               
                if($course->selfenrol == 1 &&  $enrolled ){              
                    $enrolbutton =' <a href="'.$url.'">
                            <button class="cat_btn viewmore_btn">'.get_string('launch', 'block_userdashboard').'</button>
                    </a>';
                }else{
                    $url = new moodle_url('/local/learningplan/lpathinfo.php',array('id'=>$learningplanid));             
                    $url =$url->out(false); 

                            
                    if($course->selfenrol == 1 &&  $enrolled ){              
                        $enrolbutton =' <a href="'.$url.'">
                                <button class="cat_btn viewmore_btn">'.get_string('launch', 'block_userdashboard').'</button>
                        </a>';
                    }else{
                        $url = new moodle_url('/local/learningplan/lpathinfo.php',array('id'=>$learningplanid));             
                        $url =$url->out(false); 
                       /*  $enrolbutton =' <a href="'.$url.'">
                                                <button class="cat_btn viewmore_btn">'.get_string('launch', 'block_userdashboard').'</button>
                                        </a>'; */
                        $component = 'learningplan';
                        $action = 'add';
                        if($course->approvalreqd==1){
                            $requestsql = "SELECT status FROM {local_request_records} 
                                WHERE componentid = :componentid AND compname LIKE :compname AND 
                                createdbyid = :createdbyid ORDER BY id DESC ";
                            $request = $DB->get_field_sql($requestsql ,array('componentid' => $learningplanid,'compname' => $component,'createdbyid'=>$USER->id));
                            if($request=='PENDING'){
                                $enrolbutton =' <div class="enrol_strip">
                                <button class="cat_btn btn-primary viewmore_btn">Processing</button>
                                </div>';
                            }else{
                                $enrolbutton =' <a class="cat_btn btn-primary viewmore_btn" href="javascript:void(0)" 
                                onclick="(function(e){ require(\'local_request/requestconfirm\').init({componentid:'.$learningplanid.', component:\''.$component.'\', action:\''.$action.'\', componentname:\''.$lpname.'\' }) })(event)" >'
                                .get_string('requestforenroll' , 'local_classroom') .'
                                </a>';
                            }
                        }else{
                            $enrolbutton =' <a class="cat_btn btn-primary viewmore_btn" href="javascript:void(0)" onclick="(function(e){
                                require(\'local_learningplan/courseenrol\').enrolUser({planid:'.$learningplanid.', userid:'.$USER->id.', planname:\''.$lpname.'\' }) })(event)">'
                                .get_string('enroll' , 'local_search').'
                                </a>';
                        }    
                    } 
                } 
                        
            }else {
                $coursetype = $DB->get_field('local_course_types','course_type',array('active' =>1,'id' => $course->type));   
                 
                $url = new moodle_url('/course/view.php',array('id'=>$course->id));             
                $url =$url->out(false);   
                $enroll = is_enrolled(\context_course::instance($course->id), $USER->id);       
                if($course->selfenrol == 1 &&  $enroll ){              
                    $enrolbutton ='<a href="'.$url.'" class=""><button class="cat_btn viewmore_btn">'.get_string('start_now','local_search').'</button></a>';
                }else {                         
                   $enrolbutton = (new \local_courses\output\search())->get_enrollbutton($enroll, $course); 
                }
            }
            if($course->type === 'LearningPath' ){  
                $course->expirydate = '';
            }else{
                $course->expirydate = !empty($course->expirydate) ? date('d-m-Y', $course->expirydate) : '';
            }
            $bookmarks = $DB->get_record_sql("SELECT * FROM {block_custom_userbookmark} WHERE userid = $USER->id AND courseid = $course->id");
            $bookmarkurl = $bookmarks->url;
            $contextitem = (object)[
                'coursenums' => $count,
                'courseid' => $course->id,
                'url' => $url,
                'enrolbutton' => $enrolbutton,
                'title' => $coursefullname,
                'coursename' => $course->coursename,
                'description' => $coursesummary,
                'imageurl'   => $imageurl,
                'modules' => $modules ? $modules : 'N/A',
                'modstr' => $str,
                'enrolled' => false,
                'coursetype' => !empty($coursetype) ? $coursetype :  'N/A',
                'enrolledcount' => $enrolled_user_count,
                'completedcount' => $completed_user_count,
                'bookmarkurl' => $bookmarkurl,
                'expirydate' => $course->expirydate 
            ];

            if($course->type === 'LearningPath' ){
                $coursetype = strtolower('LearningPath');
                $sql = "SELECT id FROM {local_course_types} WHERE shortname = :shortname";
                $course->open_identifiedas = $DB->get_field_sql($sql, array('shortname' => $coursetype));
                //$course->open_identifiedas = $DB->get_field('local_course_types','id', array('shortname' => $coursetype));
            }
            
            if(file_exists($CFG->dirroot.'/local/includes.php')){
                if($course->type === 'LearningPath'){
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
