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
 * Version details.
 *
 * @package    local_video
 * @copyright  akshat.c@eabyas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot.'/local/video/lib.php');
require_once($CFG->dirroot . '/local/video/classes/form/edit.php');
// use context_system;
global $DB, $CFG;
require_login();

$PAGE->set_url(new moodle_url('/local/video/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('upload','local_video'));
$PAGE->set_heading(get_string('upload','local_video'));
$PAGE->navbar->add('Index', new moodle_url('/local/video/index.php'));


// form block code for display form
$mform = new edit();
$formid = optional_param('id', null, PARAM_INT);

if($formid) {
    // print_r($formid);
    // die;
    $dbdata = $DB->get_record('local_video', ['id' => $formid]);
    $mform->set_data($dbdata);

    if ($mform->is_cancelled()) {
        // Go back to index.php page
        redirect($CFG->wwwroot.'/local/video/index.php', 'You cancelled the video uploading.', null, \core\output\notification::NOTIFY_ERROR);

    } else if ($formdata = $mform->get_data()) {
        $systemcontext = context_system::instance();
        $recordtoinsert->video = $formdata->video;
        file_save_draft_area_files($formdata->video, $systemcontext->id, 'local_video', 'video', $formdata->video);

        // update the data into our database table.
        $recordtoinsert         = new stdClass();
        $recordtoinsert->id     = $formid;
        $recordtoinsert->title  = $formdata->title;
        $recordtoinsert->video  = $formdata->video;

        $update = $DB->update_record('local_video', $recordtoinsert);

        // Go back to index.php page
        redirect($CFG->wwwroot.'/local/video/index.php', 'Your video successfully modified.', null, \core\output\notification::NOTIFY_SUCCESS);
    }

} else {
    if ($mform->is_cancelled()) {
        // Go back to index.php page
        redirect($CFG->wwwroot.'/local/video/index.php', 'You cancelled the video uploading.', null, \core\output\notification::NOTIFY_ERROR);
    } else if ($fromform = $mform->get_data()) {
        $systemcontext = context_system::instance();
        $createrec->video = $fromform->video;
        file_save_draft_area_files($fromform->video, $systemcontext->id, 'local_video', 'video', $fromform->video);

        $createrec          = new \stdClass();
        $createrec->title   = $fromform->title;
        $createrec->video   = $fromform->video;
        $count = $DB->count_records('local_video', array());
        if($count < 1){
            $createrec->status   = 1;
        }
        $insertrecord = $DB->insert_record('local_video', $createrec);

        // Go back to index.php page
        redirect($CFG->wwwroot.'/local/video/index.php', 'Your video successfully created.', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
