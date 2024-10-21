<?php
require_once(dirname(__FILE__).'/../../config.php');
global $PAGE,$CFG;
$PAGE->set_url($CFG->wwwroot.'/blocks/empcredits/mycourses_info_notindates.php');
$PAGE->set_title(get_string('completedcourse','block_empcredits'));
$PAGE->set_context(context_system::instance());
require_login();
$PAGE->set_pagelayout('standard');
$renderer = $PAGE->get_renderer('block_empcredits');

//============Js files===============
$PAGE->requires->jQuery();
$PAGE->requires->js('/blocks/empcredits/js/jquery.dataTables.js',true);
$PAGE->requires->js('/blocks/empcredits/js/custom.js');
$PAGE->requires->css($CFG->dirroot.'/blocks/empcredits/css/jquery.dataTables.css');
$PAGE->requires->css($CFG->dirroot.'/blocks/empcredits/styles.css');

echo $OUTPUT->header();
$head=html_writer::tag('h2',"Completed Courses (2016-2017)",array('class'=>'tmhead2'));
echo $OUTPUT->heading($head);

//echo $renderer->get_courses_view_date();
echo $renderer->get_courses_view($info=false);
echo "<div class='more-info'>For more information about credits achieved in 2016-17, please contact <b>faa@fractalanalytics.com</b></div>";
   
echo $OUTPUT->footer();