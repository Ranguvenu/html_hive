<?php
require_once(dirname(__FILE__).'/../../config.php');
global $PAGE,$CFG;
$PAGE->set_url($CFG->wwwroot.'/blocks/empcredits/mycourses_info.php');
$PAGE->set_title(get_string('completedcourse','block_empcredits'));
$PAGE->set_context(context_system::instance());
require_login();
$PAGE->set_pagelayout('standard');
$renderer = $PAGE->get_renderer('block_empcredits');

//============Js files===============
$PAGE->requires->jQuery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js('/blocks/empcredits/js/jquery.dataTables.js',true);
$PAGE->requires->js('/blocks/empcredits/js/custom.js');
$PAGE->requires->css($CFG->dirroot.'/blocks/empcredits/css/jquery.dataTables.css');
$PAGE->requires->css($CFG->dirroot.'/blocks/empcredits/styles.css');

echo $OUTPUT->header();
$data=ilp_startend_dates();
$star_year=date('Y',$data->startdate);
$end_year=date('Y',$data->enddate);
$head=html_writer::tag('h2',"Completed Courses",array('class'=>'tmhead2'));
echo $OUTPUT->heading($head);


echo '<div id="tabs">
		<ul>
	      		<li><a href="#tabs-1" > '.$star_year.'-'.$end_year.'</a>
	      		</li>
	      		<li><a href="'.$CFG->wwwroot.'/blocks/empcredits/ajax.php?action=allccdata" > All Completed Courses</a>
	      		</li>
				 <li><a href="'.$CFG->wwwroot.'/blocks/empcredits/ajax.php?action=certdata" > Download Certificates</a>
	      		</li>
		</ul>

		<div id="tabs-1">'. $renderer->get_courses_view(true).'</div>
	</div>';


echo $OUTPUT->footer();