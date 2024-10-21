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

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
$iid = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

require_login();

$errorstr = get_string('error');
$stryes = get_string('yes');
$strno = get_string('no');
$stryesnooptions = array(0 => $strno, 1 => $stryes);
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$PAGE->set_pagelayout('admin');
global $USER, $DB , $OUTPUT;

$returnurl = new moodle_url('/local/courses/courses.php');
if (!has_capability('local/courses:manage',$systemcontext) || !has_capability('local/courses:create', $systemcontext) ) {  
    print_error('You dont have permission');
}

$PAGE->set_url('/local/courses/timeupload/timeupload_form.php');
$PAGE->set_heading(get_string('bulkuploadtime', 'local_courses'));
$strheading = get_string('pluginname', 'local_courses') . ' : ' . get_string('pluginnam', 'local_courses');

$PAGE->set_title($strheading);
$PAGE->navbar->add(get_string('manage_course', 'local_courses'), new moodle_url('/local/courses/courses.php'));
$PAGE->navbar->add(get_string('uploadtime', 'local_courses'));
$returnurl = new moodle_url('/local/courses/courses.php');

// array of all valid fields for validation
$STD_FIELDS = array('employeeid','coursecode','completion_date');


$PRF_FIELDS = array();
//-------- if variable $iid equal to zero,it allows enter into the form -------



$mform1 = new local_courses\form\timeupload_form();
if ($mform1->is_cancelled()) {

    redirect($returnurl);
}
if ($formdata = $mform1->get_data()) {
      echo $OUTPUT->header();
    $iid = csv_import_reader::get_new_iid('userfile');
    $cir = new csv_import_reader($iid, 'userfile'); 
    //this class fromcsvlib.php(includes csv methods and classes)
    $content = $mform1->get_file_content('userfile');
    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);     
    $cir->init();
    $linenum = 1; 
    
    $progresslibfunctions = new local_courses\timeupload\progresslibfunctions();
    $filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

    $hrms= new local_courses\timeupload\cronfunctionality();
    $hrms->main_hrms_frontendform_method($cir,$filecolumns, $formdata);
    echo $OUTPUT->footer();
}
else{
    echo $OUTPUT->header();
    
    echo html_writer::link(new moodle_url('/local/courses/courses.php'),'Back',array('id'=>'download_coursess'));
        echo html_writer::link(new moodle_url('/local/courses/timeupload/sample.php?format=csv'),'Sample',array('id'=>'download_coursess'));
        echo html_writer::link(new moodle_url('/local/courses/timeupload/help.php'),'Help manual' ,array('id'=>'download_coursess','target'=>'__blank'));
   
    $mform1->display();
    
    echo $OUTPUT->footer();
    die;
}
