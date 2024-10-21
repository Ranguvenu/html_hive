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
 * @subpackage block_suggested_courses
 */

namespace block_suggested_courses\output;

use block_suggested_courses\plugin;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use moodle_url;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');

/**
 * Class view
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_suggested_courses
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
       
        global $DB, $CFG, $USER,$PAGE, $OUTPUT;
        require_once($CFG->libdir.'/enrollib.php');
        $courses = plugin::get_suggestedcourses($this->stable,$this->filtervalues,$this->config);
        $courses =$courses['suggestedcourses'];
       // print_r($courses);die;
        $row=array();
        $suggestedcourses = array();
        
        $courseurl = new moodle_url('/course/view.php');
        $numco = $courses['allcoursecount'];
          
        if(file_exists($CFG->dirroot.'/local/includes.php')){
            require_once($CFG->dirroot.'/local/includes.php');
            $includes = new \user_course_details();              
        }   
      
        foreach ($courses as $course) {
          
            $summerylength = strlen($course->summary);
            $coursename = strlen($course->fullname);
            $courseid =  $course->id;
            
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
          
            $courseurl = new moodle_url('/course/view.php',array('id'=>$course->id));
             
            $courseurl =$courseurl->out(false);
            
            $modules = 0;
           
            $enroll = is_enrolled(\context_course::instance($course->id), $USER->id);
            $course->coursename = $course->fullname;
            
            $enrolbutton = (new \local_courses\output\search())->get_enrollbutton($enroll, $course); 
            $bookmarks = $DB->get_record_sql("SELECT * FROM {block_custom_userbookmark} WHERE userid = $USER->id AND courseid = $course->id");
            $bookmarkurl = $bookmarks->url;
            $contextitem = (object)[
                'coursenums' => $numco,
                'courseid' => $courseid,
                'url' => $courseurl,
                'enrolbutton' => $enrolbutton,
                'title' => $coursefullname,
                'coursename' => $course->fullname,
                'description' => $coursesummary,
                'modules' => $modules ? $modules : 'N/A',
                'enrolled' => false,
                'coursetype' => !empty($course->course_type) ? $course->course_type :  'N/A',
                'bookmarkurl' => $bookmarkurl,
                'expirydate' => !empty($course->expirydate) ? date('d-m-Y', $course->expirydate) : '',
               ];
            
      
            $contextitem->imageurl = course_thumbimage($course);
            $row[]=(array)$contextitem;
        }

        
        return $row;
    }
}
