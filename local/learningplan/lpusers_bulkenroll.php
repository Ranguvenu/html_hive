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
 * TODO describe file upload
 *
 * @package    local_learningplan
 * @copyright  2023 Moodle India Information Solutions Pvt Ltd
 * @author     Narendra Patel (narendra.patel@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

// use local_trainingcourses\permission;

require_login();
$systemcontext = \context_system::instance();
$lpid = optional_param('lpid', 0, PARAM_INT);

@set_time_limit(60 * 60); // 1 hour.
raise_memory_limit(MEMORY_HUGE);

$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/local/learningplan/lpusers_bulkenroll.php'));
$plugin = get_string('enrollusers', 'local_learningplan');
$title = get_string('enrollusers', 'local_learningplan');
$PAGE->set_title($title);
// $heading = get_string('bulkuploadorg', 'enrollusers');
// $PAGE->set_heading($heading);

echo $OUTPUT->header();

$bulkenroll = new \local_learningplan\forms\bulkenroll_form();
if ($bulkenroll->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/learningplan/plan_view.php?id='.$lpid);
}

if ($data = $bulkenroll->get_data()) {
    // print_r($data); exit;
    raise_memory_limit(MEMORY_EXTRA);
    @set_time_limit(HOURSECS);
    $userenroll = new local_learningplan\local\bulkenrollusers();
    $file = $userenroll->get_users_file($data->userfiles);
    echo '<div class="critera_error1"><h3 style="text-decoration: underline;">'
            . get_string('usersfile_syncstatus', 'local_learningplan') . '</h3>';
    echo $userenroll->process_upload_file($file, $systemcontext,$data->lpid);
} else {
    echo html_writer::tag('a', get_string('back', 'local_learningplan'), ['href' => $CFG->wwwroot . '/local/learningplan/plan_view.php?id='.$lpid, 'class' => "btn btn-secondary ml-2 float-right"]);
    echo html_writer::tag('a', get_string('sample', 'local_learningplan'), ['href' => $CFG->wwwroot . '/local/learningplan/sample.php', 'class' => "btn btn-secondary float-right"]);

    $bulkenroll->display();
}

echo $OUTPUT->footer();
