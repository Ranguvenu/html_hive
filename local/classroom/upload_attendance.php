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
 * @subpackage local_classroom
 */
require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
$iid = optional_param('iid', '', PARAM_INT);
$cid = optional_param('cid','',PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
@set_time_limit(60 * 60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();

$errorstr = get_string('error');
$stryes = get_string('yes');
$strno = get_string('no');
$stryesnooptions = array(0 => $strno, 1 => $stryes);
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

global $USER, $DB, $OUTPUT;

$returnurl = new moodle_url('/local/classroom/index.php');
$PAGE->set_url('/local/classroom/upload_attendance.php');
$STD_FIELDS = array('session_id','employee_id','employee_email','attendance_status');

$PRF_FIELDS = array();
//-------- if variable $iid equal to zero,it allows enter into the form -------
$PAGE->set_heading(get_string('uploadsessionattendance', 'local_classroom'));

$mform = new local_classroom\form\uploadsessionattendance_form(null,array('cid' => $cid));
 
if ($mform->is_cancelled()) {

    redirect($returnurl);
}
if ($formdata = $mform->get_data()) {
    
    echo $OUTPUT->header();
    $iid = csv_import_reader::get_new_iid('attachment');
    $cir = new csv_import_reader($iid, 'attachment');
    $content = $mform->get_file_content('attachment');
    $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
    $cir->init();
    $linenum = 1; //column header is first line
    // init upload progress tracker------this class used to keeping track of code(each rows and columns)-------------

    $progresslibfunctions = new local_classroom\upload\progresslibfunctions();
    $filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
    
    $hrms = new local_classroom\upload\upload_attendance();
    $hrms->session_attendance_upload($cir, $filecolumns, $formdata);

}
else{
    echo $OUTPUT->header();
   
    $classroom_backurl = new moodle_url('/local/classroom/view.php',array('cid'=>$classroomid));
    $classroom_viewback = '<div class="courseedit course_extended_menu_itemcontainer pull-right">';
    $classroom_viewback .= '<a class="course_extended_menu_itemlink" href="' . $classroom_backurl . '">';
    $classroom_viewback .= '<i class="icon fa fa-reply fa-fw" aria-hidden="true" aria-label="" title ="'.get_string('back_url','local_classroom').'"></i>';
    $classroom_viewback .= '</a>';
    $classroom_viewback .= '</div>';
    
    echo $classroom_viewback;
    echo html_writer::link(new moodle_url('/local/classroom/upload_attendancesample.php?format=csv'),get_string('sample','local_classroom'),array('id'=>'classroom_attendance_upload'));
    echo html_writer::link(new moodle_url('/local/classroom/attendancehelp.php'),get_string('help_manual','local_classroom') ,array('id'=>'classroom_attendance_upload','target'=>'__blank'));
  
    $mform->display();
}
echo $OUTPUT->footer();

