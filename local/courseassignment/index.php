<?php

/**
 * Version details
 *
 * @package    local_courseassignment
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//use local_courseassignment\form\local_courseassignment as local_courseassignment;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/courseassignment/lib.php');

require_login();
global $DB, $PAGE, $visible;
$PAGE->requires->jquery();
$id = optional_param('id', 0, PARAM_INT);

$PAGE->requires->js_call_amd('local_courseassignment/grader', 'load', array());
$PAGE->requires->js_call_amd('local_costcenter/fragment', 'init', array());
$PAGE->set_url(new moodle_url('/local/courseassignment/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('page_title','local_courseassignment'));
$id = $_GET['id'];
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_courseassignment');
$filterparams = $renderer->get_courseassignments(true);
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);

echo $renderer->get_courseassignments(); 
echo $OUTPUT->footer();






