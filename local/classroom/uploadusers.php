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
use \local_classroom\form\uploadusers_form as uploadusers_form;
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/csvlib.class.php');

set_time_limit(0);
ini_set('memory_limit', '-1');

global $DB, $OUTPUT;
$classroomid = required_param('id', PARAM_INT);
$PAGE->set_url('/local/classroom/uploadusers.php', array());
$PAGE->set_title(get_string("uploadusers", 'local_classroom'));
require_login();
$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);
$fields = 'id,name,shortname,costcenter, capacity';
$classroominfo = $DB->get_record('local_classroom' , array('id'=>$classroomid), $fields);
$classroomurl = new moodle_url('local/classroom/view.php',array('cid'=>$classroomid));
$PAGE->navbar->add($classroominfo->shortname, $classroomurl);
$PAGE->navbar->add(get_string("uploadusers", 'local_classroom'));
echo $OUTPUT->header();

$bulk_enrolment = get_string('bulk_enrolment','local_classroom');

$mform = new uploadusers_form(null, array('id'=>$classroomid));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/classroom/view.php',array('id'=>$id)));
} else if ($data = $mform->get_data(false)) {
    echo $OUTPUT->heading($bulk_enrolment);

    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    $content = $mform->get_file_content('attachment');

    $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);
    unset($content);

    if ($readcount === false) {
        print_error('csvloaderror', '', $returnurl);
    } else if ($readcount == 0) {
        print_error('csvemptyfile', 'error', $returnurl);
    }
   
    $result = classroom_bulkenrol($cir, $classroominfo, $data);

    $cir->close();
    $cir->cleanup(false);
    
    echo $OUTPUT->box(nl2br($result), 'center');

    echo $OUTPUT->continue_button(new moodle_url('/local/classroom/view.php',array('cid'=>$classroomid)));
}else{
	echo $OUTPUT->heading($bulk_enrolment);
	$url = new moodle_url('/local/classroom/sample.php',array());
	echo $OUTPUT->single_button($url, get_string('sample','local_courses'));

	$mform->display();
}

echo $OUTPUT->footer();
