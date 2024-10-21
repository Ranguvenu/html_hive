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
 * @subpackage local_courses
 */


function get_listof_usersbookmarks($stable, $filterdata, $data_object) {
    global $CFG,$DB,$OUTPUT,$USER;
    $countsql = "SELECT count(id) ";
    $selectsql =  "SELECT * ";
    $fromsql = " FROM {block_custom_userbookmark} WHERE userid = $USER->id";
    if(!empty($data_object->search_query)){ 
        $fromsql .= " AND ( title LIKE '%". $data_object->search_query."%')";
    }
    $bookmarkcourses = $DB->get_records_sql($selectsql.$fromsql, array(), $stable->start, $stable->length);
    $bookmarkcount = $DB->count_records_sql($countsql.$fromsql, array());
    if(file_exists($CFG->dirroot.'/local/includes.php')){
        require_once($CFG->dirroot.'/local/includes.php');
        $includes = new \user_course_details();              
     }  
    foreach ($bookmarkcourses as $key) { 
        $key->url = $CFG->wwwroot .$key->url;
        $key->coursename =  $key->tilte;
        if (strlen(strip_tags($key->description))>23) {
            $key->description = substr(strip_tags($key->description), 0, 47)."...";
        }
        $coursename = strlen($key->tilte);       
        if ($coursename >= 62) {
            $key->tilte = substr($key->tilte, 0, 62).'...';
        } 
        
        if(strtolower($key->learningtype) == 'learningpath' ){

            $lpinclude = new \local_learningplan\lib\lib;
            $image = $lpinclude->get_learningplansummaryfile($course->id);      
                 
        }else  if(strtolower($key->learningtype) == 'ilt' ){
            $classroominclude = new \local_classroom\includes();
            $ilt = $DB->get_record('local_classroom' , array('id' => $key->courseid));
            $image = $classroominclude->get_classroom_summary_file($ilt); 
          
        }else{
            require_once($CFG->dirroot . '/local/courses/lib.php');
            $course = $DB->get_record('course' , array('id' => $key->courseid));
            $image = course_thumbimage($course);
        } 
        if(is_object($image)){
            $key->imageurl = $image->out();                    
        }else{
            $key->imageurl = $image;
        }        
     /*    if($key->learningtype === 'LearningPath'){           
            $image = (new local_learningplan\lib\lib)->get_learningplansummaryfile($key->courseid);      
        }else{
            $course = $DB->get_records('course', array('id' => $key->courseid));
            $image = $includes->course_summary_files((object)$course);    
        }            
        if(is_object($image)){
            $key->imageurl = $image->out();                    
        }else{
            $key->imageurl = $image;
        }   */
       
     /*     if($key->learningtype == 'LearningPath'){
            $key->imageurl = $CFG->wwwroot.'/local/costcenter/pix/course_images/image3.jpg';    
        }else{
            $key->imageurl = $CFG->wwwroot.'/local/courses/pix/courseimage.jpg';        
        }   */    
     }
  
   
   
    try {
        $bookmarkcount = $bookmarkcount;
    } catch (dml_exception $ex) {
        $bookmarkcount = 0;
    }
    return compact('bookmarkcourses', 'bookmarkcount');
   // return $result;
}