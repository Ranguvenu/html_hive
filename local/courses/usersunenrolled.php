<?php

/**
 * Version details
 *
 * @package    local_courses
 * @copyright  @eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/courses/lib.php');

require_login();
global $DB, $PAGE, $CFG;

$PAGE->set_url(new moodle_url('/local/courses/usersunenrolled.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('courses'));
$PAGE->set_heading(get_string('unenrolled_courses','local_courses'));
$PAGE->requires->js_call_amd('local_courses/unenrolCourse', 'Datatable', array());

$result = get_unenrolled_courses_list(); 
/* $courseurl = new moodle_url('/course/view.php', array('id' => $classroomcourse->id));
$courseurl = $courseurl->out(); */
echo $OUTPUT->header();
$templatecontext = (object)[
    'data' => array_values($result),  
    'downloadurl' => new moodle_url('/local/courses/export.php'), 
    'configpath' => $CFG->wwwroot
];

echo $OUTPUT->render_from_template('local_courses/unenrolledusers', $templatecontext);
echo $OUTPUT->footer();
