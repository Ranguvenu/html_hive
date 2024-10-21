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
 * @subpackage local_skill
 */
require_once('../../config.php');

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
$iid = optional_param('iid', '', PARAM_INT);
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

$returnurl = new moodle_url('/local/skillrepository/index.php');
$PAGE->set_url('/local/skill/bulkuploadform.php');
$STD_FIELDS = array('course_code','skillcategory','skills');

$PRF_FIELDS = array();
//-------- if variable $iid equal to zero,it allows enter into the form -------
$PAGE->set_heading(get_string('uploadcourseskill', 'local_skill'));


$mform = new local_skill\form\bulkuploadform();
if ($mform->is_cancelled()) {

	redirect($returnurl);
}
if ($formdata = $mform->get_data()) {
	
    echo $OUTPUT->header();
	$iid = csv_import_reader::get_new_iid('coursefile');
	$cir = new csv_import_reader($iid, 'coursefile');
	$content = $mform->get_file_content('coursefile');
	$readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
	$cir->init();
	$linenum = 1; //column header is first line
	// init upload progress tracker------this class used to keeping track of code(each rows and columns)-------------

	$progresslibfunctions = new local_skill\upload\progresslibfunctions();
	$filecolumns = $progresslibfunctions->uu_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);

	$hrms = new local_skill\upload\courseskillupload();
	$hrms->course_skill_upload($cir,$filecolumns, $formdata);

}
else{
	echo $OUTPUT->header();
	echo html_writer::link(new moodle_url('/local/skillrepository/index.php'),get_string('back','local_skill'),array('id'=>'download_skill'));
	echo html_writer::link(new moodle_url('/local/skill/courseskillsample.php?format=csv'),get_string('sample','local_skill'),array('id'=>'download_skill'));
	echo html_writer::link(new moodle_url('/local/skill/courseskillhelp.php'),get_string('help_manual','local_skill'),array('id'=>'download_skill','target'=>'__blank'));

	$mform->display();
}
echo $OUTPUT->footer();

