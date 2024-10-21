<?php
require_once(dirname(__FILE__).'/../../config.php');
global $PAGE,$CFG;

$renderer = $PAGE->get_renderer('block_empcredits');

$data=ilp_startend_dates();
$star_year=date('Y',$data->startdate);
$end_year=date('Y',$data->enddate);
$head=html_writer::tag('h3',"Learner Analytics",array('class'=>'tmhead2','style' => 'font-weight: bold;'));
echo $OUTPUT->heading($head);
$filterparams = $renderer->display_tabcompletedcourses(true);
$fform = date_filters_form($filterparams);
$filterparams['filterform'] = $fform->render();
echo $OUTPUT->render_from_template('block_empcredits/learninganalytics_tabs', $filterparams);
