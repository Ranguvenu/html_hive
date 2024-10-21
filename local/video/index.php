<?php

require_once('../../config.php');
require_login();
global $CFG, $PAGE, $OUTPUT, $DB;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/video/index.php');
$PAGE->set_title(get_string('video', 'local_video'));
$PAGE->set_heading(get_string('video', 'local_video'));
$PAGE->navbar->add(get_string('video', 'local_video'), new moodle_url('/local/video/index.php'));
$PAGE->requires->js_call_amd('local_video/demoVideo', 'load', array());

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('local_video');
$renderer->action_btn();
echo $OUTPUT->footer();
