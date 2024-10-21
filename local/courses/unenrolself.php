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
 * Self enrolment plugin - support for user self unenrolment.
 *
 * @package    enrol_self
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();
global $PAGE;
/* $courseid = required_param('id', PARAM_INT);
$instance = $DB->get_record('enrol', array('courseid' => $courseid ,'enrol' => 'self'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
 */
$enrolid = required_param('enrolid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

//$instance = $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'self'), '*', MUST_EXIST);
$instance = $DB->get_record_sql("SELECT * FROM {enrol} WHERE id = :id AND enrol IN ('self','manual') AND status = :status", array('id'=>$enrolid, 'status' => 0));    
$course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);

$context = context_course::instance($course->id, MUST_EXIST);
require_login();
if (!is_enrolled($context)) {
    redirect(new moodle_url('/'));
}
require_login($course);

$plugin = enrol_get_plugin($instance->enrol);
$systemcontext = context_system::instance();
$heading = get_string('unenrolcourse', 'local_courses', format_string($course->fullname));
$PAGE->set_heading($heading);
$pageurl = new moodle_url('/local/courses/unenrolself.php', array());
$PAGE->set_url($pageurl);
$PAGE->set_title($plugin->get_instance_name($instance));
$PAGE->set_context($systemcontext);
$PAGE->navbar->add($heading);
$PAGE->requires->js_call_amd('local_courses/unenrolCourse', 'load', array());

echo $OUTPUT->header();
$yesurl = new moodle_url($PAGE->url, array('confirm'=>1, 'sesskey'=>sesskey()));
$nourl = new moodle_url('/course/view.php', array('id'=>$course->id));
$message = get_string('unenrolselfconfirm', 'enrol_self', format_string($course->fullname));
echo $PAGE->get_renderer('local_courses')->unenrol_confirm($course,$instance,$message);
echo $OUTPUT->footer(); 
