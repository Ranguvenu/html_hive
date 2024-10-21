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
 * @package Bizlms 
 * @subpackage local_classroom
 */
namespace local_classroom;
use moodle_url;
class includes{
	public function get_temp_classes_summary_files(){
    		global $OUTPUT;
			$url = $OUTPUT->image_url('classviewnew', 'local_classroom');
        return $url;
    }
    public function get_classroom_summary_file($classroom){
    	global $DB,$CFG,$OUTPUT;
        $classroomlogourl = '';
        if ($classroom->classroomlogo > 0) {
            $sql = "SELECT * FROM {files} WHERE itemid = :logoid AND filename != '.' AND filearea ='classroomlogo' AND component='local_classroom' ORDER BY id DESC ";
            $classroomlogorecord = $DB->get_record_sql($sql, array('logoid' => $classroom->classroomlogo));
        }
        if (!empty($classroomlogorecord)) {
                $classroomlogourl = \moodle_url::make_pluginfile_url($classroomlogorecord->contextid, $classroomlogorecord->component,
                                        $classroomlogorecord->filearea, $classroomlogorecord->itemid, $classroomlogorecord->filepath,
                                        $classroomlogorecord->filename);
            
        }
        if(empty($classroomlogourl) || $classroom->classroomlogo == 0){	
            $sql = "SELECT id FROM {local_course_types} WHERE shortname = :shortname";
			$open_identifiedas = $DB->get_field_sql($sql, array('shortname' => 'ilt'));
            $coursetypeimage = $DB->get_field('local_course_types','course_image',array('id'=>$open_identifiedas));                 
			$imgurl ='';
			if(!empty($coursetypeimage) && $coursetypeimage !=0){ 
                $sql = "SELECT * FROM {files} WHERE itemid = :course_image AND component = 'local_courses' AND filearea = 'course_image' AND filename != '.' ORDER BY id DESC";
                $imgdata = $DB->get_record_sql($sql, array('course_image' => $coursetypeimage), 1);
              
                if (!empty($imgdata)) {
                    // code...
                    $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);
                } 
                $classroomlogourl = $imgurl;					
            }
		} 
         if(empty($classroomlogourl)){
            $classroomlogourl = $OUTPUT->image_url('classviewnew', 'local_classroom');
        }   
       /*   if(empty($classroomlogourl)){	
            $sql = "SELECT id FROM {local_course_types} WHERE shortname = :shortname";
			$open_identifiedas = $DB->get_field_sql($sql, array('shortname' => 'ilt'));
            $coursetypeimage = $DB->get_field('local_course_types','course_image',array('id'=>$open_identifiedas));                 
			
			if(!empty($coursetypeimage) && $coursetypeimage !=0){ 
                require_once($CFG->dirroot . '/local/courses/lib.php');
                $classroomlogourl = course_img_path($coursetypeimage);					
            }
		} 
         if(!($classroomlogourl)){
            $classroomlogourl = $this->get_temp_classes_summary_files();
        }  */
        return $classroomlogourl;
    }
}