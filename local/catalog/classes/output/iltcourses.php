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
 * Class containing data for course competencies page
 *
 * @package    local_catalog
 * @copyright  2018 hemalathacarun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_catalog\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;
use context_system;
use context_course;
use context_user;
use html_writer;
use local_catalog\output\cataloglib;
use local_request\api\requestapi;
use local_udemysync\plugin;


/**
 * Class containing data for course competencies page
 *
 * @copyright  2019 eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iltcourses implements renderable{

    public function get_iltcourses( $perpage, $startlimit, $return_noofrecords=false,$returnobjectlist=false){
        global $DB,$USER;

        $search = cataloglib::$search;
        $sortid = cataloglib::$sortid;
        $csql = " SELECT c.* ";

        $cfromsql = " from {course} c 
                        JOIN {enrol} en on en.courseid=c.id ";
        $usql = " UNION ";
        $tsql = " SELECT c.* ";

        $tfromsql = " from {course} c 
                        JOIN {enrol} en on en.courseid=c.id ";

        $tjoinsql = " JOIN {tag_instance} tgi ON tgi.itemid = en.courseid AND tgi.itemtype = 'courses' 
                                                AND tgi.component = 'local_courses' 
                        JOIN {tag} t ON t.id = tgi.tagid ";

        $leftjoinsql = $groupby = $orderby = $avgsql = '';
        if (!empty($sortid)) {
          switch($sortid) {
            case 'highrate':
            if ($DB->get_manager()->table_exists('local_rating')) {
                $avgsql .= " , AVG(r.rating) as rates ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = c.id AND r.ratearea = 'local_courses' ";
                $groupby .= " group by c.id ";
                $orderby .= " order by rates desc ";
            }
            break;
            case 'lowrate':  
            if ($DB->get_manager()->table_exists('local_rating')) {  
                $avgsql .= " , AVG(r.rating) as rates  ";
                $leftjoinsql .= " LEFT JOIN {local_rating} as r ON r.itemid = c.id AND r.ratearea = 'local_courses' ";
                $groupby .= " group by c.id ";
                $orderby .= " order by rates asc ";
            }
            break;
            case 'latest':
            $orderby .= " order by a.timecreated desc ";
            break;
            case 'oldest':
            $orderby .= " order by a.timecreated asc ";
            break;
            default:
            $orderby .= " order by a.timecreated desc ";
            break;
            }
        }

        $wheresql = " WHERE c.id > 1 and (en.enrol='self' and en.status=0) AND CONCAT(',',c.open_identifiedas,',') LIKE '%,2,%'";
        
        
        $systemcontext = context_system::instance();
      
        if(!is_siteadmin()){                
            $wheresql .=" AND c.open_costcenterid IN ($USER->open_costcenterid)  ";  
            if($USER->open_departmentid){
                $wheresql .=" AND (c.open_departmentid = $USER->open_departmentid OR c.open_departmentid = 0) ";
            }
        }
        
        if(cataloglib::$search && cataloglib::$search != 'null'){
            $cwrsql = " AND c.fullname LIKE '%$search%'";
            $twrsql = " AND t.name LIKE '%$search%'";
        }
        
        $category=cataloglib::$category;
        if(cataloglib::$category && cataloglib::$category>0){
            $wheresql .= " AND c.category=$category";             
        }
        
        if(cataloglib::$enrolltype && cataloglib::$enrolltype>0){
            if(cataloglib::$enrolltype==1){
                $coursecondition= "c.id in";
            }
            else{
                $coursecondition = "c.id not in";
            }
            
            $wheresql .=" AND $coursecondition (select 
                distinct e.courseid  
                from {enrol} e
                JOIN {user_enrolments} ue on ue.enrolid = e.id 
                where e.courseid=c.id and ue.userid=$USER->id) ";    
        }
        if(!empty($USER->open_subdepartment) && $USER->open_subdepartment != ""){
            $subdepartmentcond = " OR c.open_subdepartment = $USER->open_subdepartment ";
        }else{
            $subdepartmentcond = " ";
        }

        if(!is_siteadmin() || !has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
            $params = array();
            if(!empty($USER->open_grade) && $USER->open_grade != ""){
                $open_gradelike = "'%,$USER->open_grade,%'";
            }else{
                $open_gradelike = "''";
            }
            $params[]= " 1 = CASE WHEN c.open_grade IS NOT NULL
                THEN 
                    CASE WHEN CONCAT(',',c.open_grade,',') LIKE {$open_gradelike}
                    THEN 1
                    ELSE 0 END 
                ELSE 1 END ";
          
            if(!empty($USER->open_ouname) && $USER->open_ouname != ""){
                $open_ounamelike = "'%,$USER->open_ouname,%'";
            }else{
                $open_ounamelike = "''";
            }
            $params[]= " 1 = CASE WHEN c.open_ouname IS NOT NULL
                THEN 
                    CASE WHEN CONCAT(',',c.open_ouname,',') LIKE {$open_ounamelike}
                    THEN 1
                    ELSE 0 END 
                ELSE 1 END ";
            

            if(!empty($params)){
                $finalparams=implode('AND',$params);
            }else{
                $finalparams = '1=1';
            }

            $wheresql .= " AND ($finalparams OR ((c.open_grade IS NULL OR c.open_grade = '0' OR c.open_grade = '-1') AND (c.open_ouname IS NULL OR c.open_ouname = '0' OR c.open_ouname = '-1'))) ";
        }
        
        $wheresql .= " AND c.visible=1 AND (c.open_subdepartment = 0 $subdepartmentcond )";
        
        $finalsql = "SELECT * from ( ".$csql.$avgsql.$cfromsql.$leftjoinsql.$wheresql.$cwrsql.$groupby.$usql.$tsql.$avgsql.$tfromsql.$tjoinsql.$leftjoinsql.$wheresql.$twrsql.$groupby." ) as a ";
        $numofcourses= $DB->get_records_sql($finalsql);
        $numberofrecords = sizeof($numofcourses);
        $checkingfloat = ($numberofrecords/3);

        if (empty($sortid)) {
            $finalsql .= " order by a.id desc ";
        } else {
            $finalsql .= $orderby;
        }

        $courseslist=$DB->get_records_sql($finalsql, array(), $startlimit, $perpage);

        if($return_noofrecords && !$returnobjectlist){
            return  array('numberofrecords'=>$numberofrecords);
        }
        else if($returnobjectlist && !$return_noofrecords){
            return  array('list'=>$courseslist);
        }
        else{
            if($return_noofrecords && $returnobjectlist){
                return  array('numberofrecords'=>$numberofrecords,'list'=>$courseslist);
            }
        }
        
    } // end of get_elearning_courselist_query


    public function export_for_template($perpage,$startlimit){
        global $DB, $USER,$CFG, $OUTPUT,$PAGE;

        $courseslist_ar= $this->get_iltcourses($perpage,$startlimit, true, true);
        $courseslist=$courseslist_ar['list'];
        $tagsplugin = \core_component::get_plugin_directory('local', 'tags');
        if($tagsplugin){
            $localtags = new \local_tags\tags();
        }
        $finalresponse= array();
        foreach ($courseslist as $course){         
            $grid="";
            $courserecord = $DB->get_record('course', array('id'=>$course->id));
            $course_category = $DB->get_field('course_categories', 'name', array('id'=>$courserecord->category));                  
            $course->fileurl = cataloglib::convert_urlobject_intoplainurl($course);
            if(file_exists($CFG->dirroot.'/local/includes.php')){
                require_once($CFG->dirroot.'/local/includes.php');
                $progressbarpercent=cataloglib::$includesobj->user_course_completion_progress($course->id, $USER->id);
            }
            if(empty($progressbarpercent)){
                $course->progressbarpercent = 0;
                $course->progressbarpercent_width = 0;
            }else{
                $course->progressbarpercent = $progressbarpercent;
                $course->progressbarpercent_width = 1;
            } 
     
            $course->id = $course->id;
            $course->coursename =$courserecord->fullname;
            $course->course_fullname = cataloglib::format_thestring($courserecord->fullname);       
            $course->categoryname = $course_category;            
            $course->formattedcategoryname = cataloglib::format_thestring($categoryname);           
            $course->summary = cataloglib::format_thesummary($courserecord->summary);
           
            $courseurl = new moodle_url('/local/catalog/courseinfo.php', array('id'=>$course->id));
               
            $courselink = html_writer::link($courseurl, $course_fullname, array('style'=>'color:#000;font-weight: 300;cursor:pointer;', 'title'=>$courserecord->fullname, 'class'=>'available_course_link'));
          
                
            $course->course_url = $CFG->wwwroot.'/course/view.php?id='.$courserecord->id;
            $course->coursegrade =$this->get_coursegrade($coursedetails->grade);
            $course->courselink = $courselink;
                 
            if(!empty($coursedetails->credits)){
                $coursecredits = $coursedetails->credits;
            }else{
                $coursecredits = 'N/A';
            }
              
            $course->coursecredits = $coursecredits;
            $course->enrollstartdate =cataloglib::get_thedateformat($coursedetails->enrollstartdate); 
            $course->enrollenddate = cataloglib::get_thedateformat($coursedetails->enrollenddate);
            
            $course->coursecompletiondays=$this->get_coursecompletiondays_format($courserecord->duration);

            $coursecontext   = context_course::instance($course->id);
            $enroll=is_enrolled($coursecontext, $USER->id);
            $course->enroll = $enroll;

            // $course->selfenrol = $this->get_enrollbutton($enroll, $course);
            $course->selfenrol = null;
            if(class_exists('local_ratings\output\renderer')){
                $rating_render = $PAGE->get_renderer('local_ratings');
                $course->rating_element = $rating_render->render_ratings_data('local_courses', $courserecord->id ,null, 14);
            }else{
                $course->rating_element = '';
            }

            
            $url = new moodle_url('/local/catalog/coursedetails.php', array('id'=>$course->id));
            $course->redirect = html_writer::link($url, get_string('viewmore','local_catalog'), array('class'=>'cat_btn viewmore_btn','target'=>'_blank'));

            // $course->redirect='<a data-action="courseinfo'.$course->id.'" class="courseinfo" onclick ="(function(e){ require(\'local_catalog/courseinfo\').init({selector:\'courseinfo'.$course->id.'\', courseid:'.$course->id.'}) })(event)"><button class="cat_btn viewmore_btn">'.get_string('viewmore','local_catalog').'</button></a>';  
            
            $course->type = ELE;
            $coursecontext = context_course::instance($course->id);


            
            if($tagsplugin){
                $tags = $localtags->get_item_tags('local_courses', 'courses', $course->id, $coursecontext->id, $arrayflag = 0,$more = 0);
                $course->tags_title = $tags;
                $tags = strlen($tags) > 25 ? substr($tags, 0, 25)."..." : $tags;
                $course->tags = (!empty($tags) ) ? '<span title="Tags"><i class="fa fa-tags" aria-hidden="true"></i></span> '.$tags: '';
            }
            $finalresponse[]= $course;
        }
        
        $finalresponse['numberofrecords']=$courseslist_ar['numberofrecords'];
         
        return $finalresponse;
    }

    public function get_enrollbutton($enroll, $courseinfo){
        global $DB,$CFG,$USER;
        $courseid = $courseinfo->id;
        $coursename = $courseinfo->coursename;
        if(!is_siteadmin()){
            if($courseinfo->approvalreqd==1){
                if($enroll == 0){ 
                    $componentid =$courseid;
                    $component = 'elearning';
                    $sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
                    $request = $DB->get_field_sql($sql,array('componentid' => $courseid,'compname' => $component,'createdbyid'=>$USER->id));
                    if($request=='PENDING'){
                        $selfenrolbutton = '<button class="cat_btn btn-primary viewmore_btn">Processing</button>';
                    }else{
                        $selfenrolbutton =requestapi::get_requestbutton($componentid, $component, $coursename);
                    }
                }else{
                    $selfenrolbutton = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'" class=""><button class="cat_btn btn-primary viewmore_btn">'.get_string('start_now','local_catalog').'</button></a>';
                }
            }else{
                if($enroll == 0){
                   
                    $selfenrolbutton = '<a data-action="courseselfenrol'.$courseid.'" class="courseselfenrol enrolled'.$courseid.'" onclick ="(function(e){ require(\'local_catalog/courseinfo\').test({selector:\'courseselfenrol'.$courseid.'\', courseid:'.$courseid.', enroll:1, coursename: \''.$coursename.'\' }) })(event)"><button class="cat_btn viewmore_btn"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('enroll','local_catalog').'</button></a>';
                
                 }else{
                    
                    $selfenrolbutton = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'" class=""><button class="cat_btn btn-primary viewmore_btn">'.get_string('start_now','local_catalog').'</button></a>';
                }
            }
        }
        return $selfenrolbutton;
    }

    private function get_coursegrade($grade){
        if(!empty($grade)){
            if($grade == -1){
                $coursegrade = get_string('all');
            }else{
                $coursegrade = $grade;
            }
        }else{
            $coursegrade = get_string('all');
        }
        return $coursegrade;
    }

    private function get_coursecompletiondays_format($duration){
        if(!empty($duration)){
            if($duration >= 60 ){
                $hours = floor($duration / 60);
                $minutes = ($duration % 60);
                $hformat = $hours > 1 ? $hformat = '%01shrs': $hformat = '%01shr';
                if($minutes == NULL){
                    $mformat = '';
                }else{
                    $mformat = $minutes > 1 ? $mformat = '%01smins': $mformat = '%01smin';
                }
                $format = $hformat . ' ' . $mformat;
                $coursecompletiondays = sprintf($format, $hours, $minutes);
            }else{
                $minutes = $duration;
                $coursecompletiondays = $duration > 1 ? $duration.'mins' : $duration.'min';
            }
        }else{
            $coursecompletiondays = 'N/A';
        }
        return $coursecompletiondays;
    }

} // end of class






