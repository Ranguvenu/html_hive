<?php
require_once(dirname(__FILE__).'/../../config.php');
global $PAGE,$CFG;
$PAGE->set_url($CFG->wwwroot.'/blocks/empcredits/myfacilitator_info.php');
$PAGE->set_title(get_string('facilitator','block_empcredits'));

$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
require_login();
$renderer = $PAGE->get_renderer('block_empcredits');

//============Js files===============
$PAGE->requires->jQuery();
$PAGE->requires->js('/blocks/empcredits/js/jquery.dataTables.js',true);
$PAGE->requires->js('/blocks/empcredits/js/custom.js');
$PAGE->requires->css($CFG->dirroot.'/blocks/empcredits/css/jquery.dataTables.css');
$PAGE->requires->css($CFG->dirroot.'/blocks/empcredits/styles.css');

echo $OUTPUT->header();
$data=ilp_startend_dates();
$star_year=date('Y',$data->startdate);
$end_year=date('Y',$data->enddate);
$head=html_writer::tag('h2',"Facilitator credits ($star_year-$end_year)",array('class'=>'tmhead2'));
echo $OUTPUT->heading($head);
echo $renderer->get_facilitator_view($info=true);

   
echo $OUTPUT->footer();