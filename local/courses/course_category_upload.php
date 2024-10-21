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
 * local courses
 *
 * @package    local_courses
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
global $CFG,$PAGE;
require_login();
require_once($CFG->dirroot . '/local/courses/upload/uploadcourse_category_form.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/local/courses/upload/coursecategory_upload.php');

$context = context_system::instance();
if(!has_capability('local/courses:uploadcourse_category', $context)){
    print_error("You don't have permissions to view this page.");
}         
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/courses/course_category_upload.php'));
$PAGE->set_title(get_string('upload_course_category','local_courses'));
$PAGE->requires->jquery();
$PAGE->set_heading(get_string('upload_course_category', 'local_courses'));  
$PAGE->navbar->add(get_string('pluginname', 'local_courses'), new moodle_url('/local/courses/courses.php'));
$PAGE->navbar->add(get_string('upload_course_category', 'local_courses'));
$returnurl = new moodle_url('/local/courses/course_category_upload.php');
echo $OUTPUT->header();

echo html_writer::link(new moodle_url('/local/courses/courses.php'),'Back',array('id'=>'download_coursess'));
echo html_writer::link(new moodle_url('/local/courses/upload/coursecategory_sample.php?format=csv'),'Sample',array('id'=>'download_coursess'));
echo html_writer::link(new moodle_url('/local/courses/upload/coursecategory_help.php'),'Help manual' ,array('id'=>'download_coursess','target'=>'__blank'));
$mform = new local_uploadcourse_category_form();
echo '<div class="panel panel-primary my-6">';
if($mform->is_cancelled()){
    redirect($returnurl);
}
if($data = $mform->get_data()){
raise_memory_limit(MEMORY_EXTRA);
    @set_time_limit(HOURSECS);   
   // $processor = new coursecategory_upload($cir, $options, $defaults);
    $categoryupload = new \local_courses\coursecategory_upload();
    $file = $categoryupload->get_coursecategory_file($data->coursecategoryfile);
    $delimiter_name = $data->delimiter_name;
    $encoding = $data->encoding;
    echo $categoryupload->process_upload_file($file, $context);
} else {
    $mform->display();
}
echo '</div>';
echo $OUTPUT->footer();