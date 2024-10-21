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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   local_learningplan
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $PAGE,$CFG,$USER;
require_once(dirname(__FILE__) . '/../../config.php');
require_once ($CFG->libdir . '/csvlib.class.php');
$action = required_param('tableid', PARAM_TEXT);
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$renderer = $PAGE->get_renderer('block_empcredits');
$requestDatacount=array();

$stable = new \stdClass();
$stable->thead = false;
switch ($action) { 
    case 'lastoneyearccdata':
        $output = $renderer->get_courses_info_tabs(true,$stable);
    break;
    case 'allccdata':
        $output = $renderer->get_courses_info_tabs(false,$stable);
   break;   
}

$downloadfilename = clean_filename ( $action."_csv" );
$csvexport = new csv_export_writer ();
$csvexport->set_filename ( $downloadfilename );
$result = (object)$output['results']; 

foreach ($result as $res) {
    
    $array = array();
    $name = $res->fullname;
    if(!empty($res->skill)){
        $skillsql = "SELECT GROUP_CONCAT(sk.name) FROM {local_skill} sk WHERE sk.id IN ($res->skill) ";
        $skill = $DB->get_field_sql($skillsql);                       
    }else{
        $skill = 'N/A';
    }
    if(!empty($res->skillcategory)){ 
        $skillcategory = $DB->get_field('local_skill_categories' , 'name', array('id' => $res->skillcategory));              
    }else{
        $skillcategory = 'N/A';
    }

    if(!empty($res->course_provider)){
        $provider = $DB->get_field('local_course_providers' , 'course_provider', array('id' => $res->course_provider));                    
    }else{
        $provider = 'N/A';
    }
 
    if($res->moduletypeshort == 'learning_path'){
        $finalgrade = '';               
    }else{
        $url = new moodle_url('/course/view.php', array('id'=>$res->id));
        $finalgrade = $DB->get_field_sql("SELECT gd.finalgrade FROM {grade_items} gi
        JOIN {grade_grades} gd ON gi.id = gd.itemid 
        WHERE gi.courseid = :courseid AND gd.userid = :userid",  array('courseid' => $res->id,'userid' => $USER->id));
    }
    if ($res->duration){
        $hours = floor($res->duration/3600);
        $minutes = ($res->duration/60)%60;
        if ($hours < 1) {
            $credits = '0.5';
        } elseif (($hours >= 1 && $hours <= 4) || ($hours == 4 && $minutes <= 59)) {
            $credits = '1';
        } elseif (($hours >= 5 && $hours <= 8) || ($hours == 8 && $minutes <= 59)) {
            $credits = '2';
        } elseif (($hours >= 9 && $hours <= 12) || ($hours == 12 && $minutes <= 59)) {
            $credits = '3';
        } else {
            $credits = '4';
        }
    } else {
        $credits = 'N/A';
    }

    $array[] = $name;
    $array[] = ($res->category) ? $res->category : 'N/A';
    $array[] = $res->moduletype;
    
    $array[] = $provider;
    //$array[] = ($res->open_grade) ? $res->open_grade : 'N/A';
    $array[] = ($res->enroleddate) ? date('d-m-Y ',$res->enroleddate) :'N/A';
    //$array[] = ($finalgrade) ? number_format((float)$finalgrade, 2, '.', '') : 'N/A';
    $array[] = $credits;
    $array[]  = $skillcategory;
    $array[]  = $skill;
    $array[] = date('d-m-Y ',$res->timecompleted);
    $records[]  = $array;	

}

$fieldnames = array('Name of Course/Certificate ', 'Category', 'LearningType', 'CourseProvider' ,'DateofEnrolment', 'Credit', 'SkillCategory', 'SkillsAchieved', 'CompletionDate');
$exporttitle = array ();
foreach ( $fieldnames as $field ) {
	$exporttitle [] = $field;
}

$csvexport->add_data( $exporttitle );
foreach ($records as $rec ) {
	$csvexport->add_data( $rec );
}
$csvexport->download_file();
