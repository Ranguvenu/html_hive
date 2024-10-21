<?php

/**
 * Version details
 *
 * @package    local_classroom
 * @copyright  @eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/classroom/lib.php');

require_login();
global $DB, $PAGE, $CFG;

$PAGE->set_url(new moodle_url('/local/classroom/usersunenrolled.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_classroom'));
$PAGE->set_heading(get_string('pluginname', 'local_classroom'));
$PAGE->requires->js_call_amd('local_classroom/classroomunenrol', 'Datatable', array());

$result = get_unenrolled_classroom_list(); 

echo $OUTPUT->header();
$templatecontext = (object)[
    'data' => array_values($result),  
    'downloadurl' => new moodle_url('/local/classroom/export.php'),
    'configpath' => $CFG->wwwroot
];

echo $OUTPUT->render_from_template('local_classroom/unenrolledusers', $templatecontext);
echo $OUTPUT->footer();
