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

require(__DIR__ . '/../../../config.php');
global $CFG,$PAGE;
require_once($CFG->libdir . '/adminlib.php');
//require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/local/courses/upload/uploadforms.php');
require_once($CFG->dirroot . '/local/courses/upload/processor.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/courses/upload/index.php'));
$PAGE->set_title(get_string('uploadcourses','local_courses'));
$PAGE->requires->jquery();
$PAGE->set_heading(get_string('uploadcourses', 'local_courses'));  
$PAGE->navbar->add(get_string('pluginname', 'local_courses'), new moodle_url('/local/courses/courses.php'));
$PAGE->navbar->add(get_string('uploadcourses', 'local_courses'));
//$PAGE->navbar->add(get_string('manual', 'local_courses'));
if((!has_capability('local/costcenter:create', $context)||!has_capability('local/courses:bulkupload', $context)||!has_capability('local/courses:manage', $context)||!has_capability('moodle/course:create', $context)||!has_capability('moodle/course:update', $context)) && !is_siteadmin()){
    print_error('no access to upload courses');
    exit;
}
$importid = optional_param('importid', 0, PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

$returnurl = new moodle_url('/local/courses/upload/index.php');

if (empty($importid)) {
    $mform1 = new local_uploadcourse_step1_form();
    if ($form1data = $mform1->get_data()) {
        $importid = csv_import_reader::get_new_iid('uploadcourse');
        $cir = new csv_import_reader($importid, 'uploadcourse');

        $content = $mform1->get_file_content('coursefile');
        $readcount = $cir->load_csv_content($content, $form1data->encoding, $form1data->delimiter_name);
        
             
        if ($readcount === false) {
            print_error('csvfileerror', 'tool_uploadcourse', $returnurl, $cir->get_error());
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $returnurl, $cir->get_error());
        }
    } else {
        echo $OUTPUT->header();
        echo html_writer::link(new moodle_url('/local/courses/courses.php'),'Back',array('id'=>'download_courses'));
        echo html_writer::link(new moodle_url('/local/courses/upload/sample.php?format=csv'),'Sample',array('id'=>'download_courses'));
        echo html_writer::link(new moodle_url('/local/courses/upload/coursehelp.php'),'Help manual' ,array('id'=>'download_courses','target'=>'__blank'));
        //echo $OUTPUT->heading(get_string('uploadcourses', 'local_courses'));
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} else {
    $cir = new csv_import_reader($importid, 'uploadcourse');
}

// Data to set in the form.
$data = array('importid' => $importid, 'previewrows' => $previewrows);
if (!empty($form1data)) {
    // Get options from the first form to pass it onto the second.
    foreach ($form1data->options as $key => $value) {
        $data["options[$key]"] = $value;
    }
}
$context = context_system::instance();

$mform2 = new local_uploadcourse_step2_form(null, array('contextid' => $context->id, 'columns' => $cir->get_columns(),
    'data' => $data, 'importid' => $importid,));

// If a file has been uploaded, then process it.
if ($form2data = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);
} else if ($form2data = $mform2->get_data()) {
    $options = (array) $form2data->options;
    $data = (object)$form2data->defaults;
    $data->open_coursecreator = $USER->id;
    $data->format = 'tabtopics';
    $data->enablecompletion  = 1;
    $defaults = (array) $data;

    // Restorefile deserves its own logic because formslib does not really appreciate
    // when the name of a filepicker is an array...
    $options['restorefile'] = '';
    if (!empty($form2data->restorefile)) {
        $options['restorefile'] = $mform2->save_temp_file('restorefile');
    }
    $processor = new local_uploadcourse_processor($cir, $options, $defaults);

    echo $OUTPUT->header();
    if (isset($form2data->showpreview)) {
        echo $OUTPUT->heading(get_string('uploadcoursespreview', 'local_courses'));
        $processor->preview($previewrows, new tool_uploadcourse_tracker(tool_uploadcourse_tracker::OUTPUT_HTML));
        $mform2->display();
    } else {
        echo $OUTPUT->heading(get_string('uploadcoursesresult', 'local_courses'));
        $processor->execute(new tool_uploadcourse_tracker(tool_uploadcourse_tracker::OUTPUT_HTML));
        echo $OUTPUT->continue_button($returnurl);
    }

    // Deleting the file after processing or preview.
    if (!empty($options['restorefile'])) {
        @unlink($options['restorefile']);
    }

} else {
    if (!empty($form1data)) {
        $options = $form1data->options;
    } else if ($submitteddata = $mform2->get_submitted_data()) {
        $options = (array)$submitteddata->options;

    } else {
        // Weird but we still need to provide a value, setting the default step1_form one.
        $options = array('mode' => local_uploadcourse_processor::MODE_CREATE_NEW);
    }
    $processor = new local_uploadcourse_processor($cir, $options, array());
   
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadcoursespreview', 'local_courses'));
    $processor->preview($previewrows, new tool_uploadcourse_tracker(tool_uploadcourse_tracker::OUTPUT_HTML));
    $mform2->display();
}

echo $OUTPUT->footer();
