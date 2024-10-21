<?php

require_once(dirname(__FILE__) . '/../../config.php');
global $PAGE,$CFG, $OUTPUT;
$PAGE->set_url('/blocks/empcredits/learninggoals.php');
$PAGE->set_title(get_string('learninggoals','block_empcredits'));
$PAGE->set_context(context_system::instance());
require_login();
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
$head=html_writer::tag('h2',"Learning Requirement",array('class'=>'lgoals_head'));
echo $OUTPUT->heading($head);

$params = [
    'cfgwwwroot' => $CFG->wwwroot,
    'message'=> get_string('learninggoalsmsg','block_empcredits'),
    'lgoalsimg' => $OUTPUT->image_url('learninggoals21_22', 'block_empcredits'),
    'lmsimg' => $OUTPUT->image_url('lmsgoals', 'block_empcredits'),
    'pdfurl'=> '/blocks/empcredits/pix/Learning_goals_publishing_20th_Augest_24.pdf'
];
// echo '<div style="text-align: center; font-size: 20px; font-weight: bold; padding-top: 70px;">New learning goal framework will be announced soon for FY 24-25</div>';

echo $OUTPUT->render_from_template('block_empcredits/learninggoals', $params);
echo $OUTPUT->footer();
