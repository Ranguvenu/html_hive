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
 * @subpackage local_onlinetest
 */

set_time_limit(0);
ini_set('memory_limit', '-1');
require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once ('lib.php');
require_once($CFG->dirroot.'/local/lib.php');
global $DB, $USER;
/// Get params

$id = required_param('id', PARAM_INT);

$onlinetest = $DB->get_record('local_onlinetests', array('id'=>$id));
$context =  context_system::instance();
if (empty($onlinetest)) {
   print_error("Online exam not found");
} elseif (!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))) {
    // $user_detail = $DB->get_record('user', array('id'=>$USER->id));
    if ($onlinetest->costcenterid != $USER->open_costcenterid) {
        print_error("You don't have permission to view this page.");
    }else if(!has_capability('local/costcenter:manage_ownorganization', $context) && $onlinetest->departmentid != $USER->open_departmentid){
        print_error("You don't have permission to view this page.");
    }

}


/// Security and access check

require_login();

if (!has_capability('local/onlinetests:enroll_users', $context)) {
    print_error("You don't have permission to view this page.");
}
 
/// Start making page
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/onlinetests/mass_enroll.php', array('id'=>$id));

$strinscriptions = get_string('mass_enroll', 'local_courses');

$PAGE->set_title($onlinetest->name . ': ' . $strinscriptions);
$PAGE->set_heading($onlinetest->name . ': ' . $strinscriptions);
$PAGE->navbar->add(get_string("pluginname", 'local_onlinetests'), new moodle_url('index.php',array('id'=>$onlinetest->id)));
$PAGE->navbar->add($onlinetest->name);

echo $OUTPUT->header();
$time = time();
if ($onlinetest->timeclose AND ($time >= $onlinetest->timeclose)) {
    $enrol_warning = get_string("warning_enrol", 'local_onlinetests');
    echo html_writer::tag('div', $enrol_warning, array('class' => 'alert alert-danger'));
}
$mform = new local_courses\form\mass_enroll_form($CFG->wwwroot . '/local/onlinetests/mass_enroll.php', array (
	'course' => $onlinetest,
    'context' => $context,
	'type' => 'onlinetest'
));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/onlinetests/index.php'));
} else
if ($data = $mform->get_data(false)) { // no magic quotes
    //echo $OUTPUT->heading($strinscriptions);
    
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
   
    $result = onlinetest_mass_enroll($cir, $onlinetest, $context, $data);
    
    $cir->close();
    $cir->cleanup(false); // only currently uploaded CSV file 
	/** The code has been disbaled to stop sending auto maila and make loading issues **/
    
    echo $OUTPUT->box(nl2br($result), 'center');

    echo $OUTPUT->continue_button(new moodle_url('/local/onlinetests/index.php')); // Back to course page
    echo $OUTPUT->footer($onlinetest);
    die();
}
//echo $OUTPUT->heading($strinscriptions);
echo $OUTPUT->box (get_string('mass_enroll_info', 'local_courses'), 'center');

$nav_links = html_writer::link(new moodle_url('/local/onlinetests/index.php'),get_string('back', 'local_courses'),array('id'=>'back_tp_course'));
$nav_links .= html_writer::link(new moodle_url('/local/courses/sample.php',array('format'=>'csv')),get_string('sample', 'local_courses'),array('id'=>'download_users'));
echo html_writer::tag('div', $nav_links, array('class' => ''));
$mform->display();
echo $OUTPUT->footer($onlinetest);
