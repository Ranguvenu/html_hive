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
 * Doselect module version information
 *
 * @package mod_doselect
 * @copyright  2019 Anilkumar Cheguri (anil@eabyas.in)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/doselect/lib.php');
require_once($CFG->dirroot.'/mod/doselect/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

global $PAGE, $USER, $DB, $COURSE;
$PAGE->requires->jquery();
// $api_salt='06a1d6b1792c7ab576d5893bad6480667d1960a9c6e9fc6d4119d8958ed912d1';
// $userhash_old = "ed0e6447e46a15446a9ce9bec4eb6c8513f522ffbbc2c56c7ae486369407d2ae";
$api_salt = $DB->get_field('config_plugins','value',array('plugin'=>'doselect','name'=>'api_secret'));
$api_key = $DB->get_field('config_plugins','value',array('plugin'=>'doselect','name'=>'api_key'));


// 16 character salt starting with $5$. The default number of rounds is 5000.



	
$userhash = hash_hmac('sha256', $USER->email, $api_salt);
/*if($USER->id==2){
echo $api_salt;	
echo "System Generated Code".$userhash
}*/
//$userhash ='69f86b56d1a14509aaaee82215b3b61aa7b5aa7a0c37c2975d8c13adc59ef274';

echo html_writer::script('
		window.doselect = {
		    "api_key": "'.$api_key.'",
		    "email": "'.$USER->email.'",
		    "full_name": "'.fullname($USER).'",
		    "timezone": "Asia/Kolkata",
		    "user_hash": "'.$userhash.'"
		}
	');
//$PAGE->requires->js('/mod/doselect/js/doselect-embed.min.js');
echo '<script src="https://assets.doselect.com/embed/v3/doselect-embed.min.js"></script>';


	$id = optional_param('id', 0, PARAM_INT); // Course Module ID

     if($id) {
   	    if (!$cm = get_coursemodule_from_id('doselect', $id)) {
       			 print_error('invalidcoursemodule');
    	    }
   	    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        		print_error('coursemisconf');
    	   }
    		$doselect = $DB->get_record('doselect', array('id'=>$cm->instance), '*', MUST_EXIST);


     }


$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/doselect:view', $context);
$PAGE->set_title(get_string('pluginname','doselect'));

// Completion and trigger events.
doselect_view($doselect, $course, $cm, $context);

$PAGE->set_url('/mod/doselect/view.php', array('id' => $cm->id));

//displaying doselect in full page view for only Employees.

$context = get_context_instance(CONTEXT_COURSE,$cm->course,true);
$roles = get_user_roles($context, $USER->id);
foreach ($roles as $role) {
    $rolestr[] = role_get_name($role, $context);
}
if (count((array)$rolestr) == 1 && in_array("Employee",(array)$rolestr) )  {
	$PAGE->set_pagelayout('fullscreen');
}

echo $OUTPUT->header();
$canadd = has_capability('mod/doselect:addinstance', $context);
$canview = has_capability('mod/doselect:view', $context);
$coursecontext = context_course::instance($course->id);
$is_enrolled = is_enrolled($coursecontext,  $USER);
if (empty($is_enrolled)) {
	$youneedtoenrol = html_writer::tag('p', get_string('youneedtoenrol', 'doselect'));
    $button = html_writer::tag('p',
            $OUTPUT->continue_button($CFG->wwwroot . '/course/view.php?id=' . $course->id));
    $output .= $OUTPUT->box($youneedtoenrol."\n\n".$button."\n", 'generalbox', 'notice');
    echo $output;
} else {
	echo '<div style="float:right";> <a href="/mod/doselect/attempts.php?id='.$id.'">View Attempts </a></div>';
	echo '<div class="doselect-embed" data-category="test" data-slug="'.$doselect->doselect_slug.'" data-config=\'{"allow_test_retake": true,"custom_body_class": "custom_class1 custom_class2"}\'></div>';
}
echo $OUTPUT->footer();
