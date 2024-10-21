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
 * @copyright  2018 hemalatha c arun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_userdashboard\includes;

use renderable;
use renderer_base;
use templatable;
use context_course;
use stdClass;
use core_course_list_element;
use moodle_url;

class generic_content{

    public static function get_coursecount_class($inprogress_elearning){
          
        $courses_view_count = count($inprogress_elearning);
        $course_count_view = '';
        if ($courses_view_count >= 3) {
            $course_count_view = 'view_courses_three';
        } elseif ($courses_view_count == 2) {
            $course_count_view = 'view_courses_two';
        } else {
            $course_count_view = 'view_courses_one';
        }

        return $course_count_view;
    } // end of function

    public static function course_summary_files($courserecord){
        global $DB, $CFG, $OUTPUT;
        if ($courserecord instanceof stdClass) {
            // require_once($CFG->libdir . '/coursecatlib.php');
            $courserecord = new core_course_list_element($courserecord);
        }
        
        // set default course image
        //$url = $OUTPUT->pix_url('/course_images/courseimg', 'local_costcenter');
        foreach ($courserecord->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            if($isimage){
                $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' .
                    $file->get_component() . '/' .$file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
            }else{
               $url = '';//$OUTPUT->image_url('courseimg', 'local_courses');//send_file_not_found();
             //   $url ='';
            }
        }
        if(empty($url)){
            $url = $OUTPUT->image_url('courseimg', 'local_courses');
            if(is_object($url)){
                $url=$url->out();
            }
            //send_file_not_found();
           //$url = '';
        }
        
        return  $url;
    }
    public static function program_logo_files($programid){
        global $DB, $CFG, $OUTPUT;

        
            $programlogo = $DB->get_field('local_program','programlogo',array('id' => $programid));
            if(!empty($programlogo)){
                $sql = "SELECT * FROM {files} WHERE itemid = $programlogo AND filename != '.' AND component = 'local_program' AND filearea = 'programlogo' ORDER BY id DESC "; //LIMIT 1
                $filerecord = $DB->get_record_sql($sql);
            }
            if($filerecord!=''){
        
                $imgurl = $CFG->wwwroot.'/pluginfile.php/1/local_classroom/classroomlogo/'.$filerecord->itemid.'/'.$filerecord->filename;
            }else{
                $imgurl = $CFG->wwwroot.'/local/costcenter/pix/course_images/image3.jpg';
            }
        
        return $imgurl;
    }
    public static function get_classroom_attachment($classroomid){
        global $DB, $CFG;
      
        $fileitemid = $DB->get_field('local_classroom', 'classroomlogo', array('id'=>$classroomid));
        $imgurl = false;
        if(!empty($fileitemid)){
            $sql = "SELECT * FROM {files} WHERE itemid = $fileitemid AND filename != '.' AND component = 'local_classroom' AND filearea = 'classroomlogo' ORDER BY id DESC ";
            $filerecord = $DB->get_record_sql($sql,array(),1);
       
        }     
      
        if($filerecord!=''){ 
            $imgurl = \file_encode_url($CFG->wwwroot."/pluginfile.php", '/' . $filerecord->contextid . '/' . $filerecord->component . '/' .$filerecord->filearea .'/'.$filerecord->itemid. $filerecord->filepath. $filerecord->filename);
        }
        if(empty($imgurl)){	
            $sql = "SELECT id FROM {local_course_types} WHERE shortname = :shortname";
			$open_identifiedas = $DB->get_field_sql($sql, array('shortname' => 'ilt'));
            $coursetypeimage = $DB->get_field('local_course_types','course_image',array('id'=>$open_identifiedas));                 
			
			if(!empty($coursetypeimage) && $coursetypeimage !=0){ 
                $sql = "SELECT * FROM {files} WHERE itemid = :course_image AND component = 'local_courses' AND filearea = 'course_image' AND filename != '.' ORDER BY id DESC";
                $imgdata = $DB->get_record_sql($sql, array('course_image' => $coursetypeimage), 1);
            
                if (!empty($imgdata)) {
                    // code...
                    $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);
                }
                $imgurl = $imgurl->out();                
             }
		}
    
        if(empty($imgurl)){	
            $dir = $CFG->wwwroot.'/local/costcenter/pix/course_images/image3.jpg';
            for($i=1; $i<=10; $i++) {
                $image_name = $dir;
                $imgurl = $image_name;
                break;
            }
        }
        //}
        return $imgurl;
    }



} // end of class
