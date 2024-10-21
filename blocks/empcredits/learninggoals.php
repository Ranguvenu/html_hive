<?php

require_once(dirname(__FILE__) . '/../../config.php');
global $PAGE,$CFG, $OUTPUT;
$PAGE->set_url('/blocks/empcredits/learninggoals.php');
$PAGE->set_title(get_string('learninggoals','block_empcredits'));
$PAGE->set_context(context_system::instance());
require_login();
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();
$head=html_writer::tag('h2',"Learning Goals",array('class'=>'lgoals_head'));
echo $OUTPUT->heading($head);

$params = [
    'cfgwwwroot' => $CFG->wwwroot,
    'message'=> get_string('learninggoalsmsg','block_empcredits'),
    'lgoalsimg' => $OUTPUT->image_url('learninggoals21_22', 'block_empcredits'),
    'lmsimg' => $OUTPUT->image_url('lmsgoals', 'block_empcredits'),
    'pdfurl'=> '/blocks/empcredits/pix/updated_Learning_Linked_to_Growth.pdf'
];
echo $OUTPUT->render_from_template('block_empcredits/learninggoals', $params);
echo $OUTPUT->footer();