<?php
global $DB, $PAGE,$CFG,$OUTPUT;
require_once('../../config.php');
global $OUTPUT;
require_login();
$PAGE->set_pagelayout('admin');
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/blocks/training_calendar/view.php');
$PAGE->set_title(get_string('calendar', 'block_training_calendar'));
require_capability('block/training_calendar:view',$systemcontext);
$PAGE->navbar->add(get_string("pluginname", 'block_training_calendar'));
$PAGE->set_heading(get_string('calendar', 'block_training_calendar'));
$PAGE->requires->css('/blocks/training_calendar/css/fullcalendar.min.css');
$plugins = \block_training_calendar\calendarlib::trainingcalendar_plugin_details();
if($plugins['program']){
	$PAGE->requires->js_call_amd('local_program/program', 'programDatatable', array(array('programstatus' => -1)));
}
if($plugins['certification']){
	$PAGE->requires->js_call_amd('local_certification/certification', 'certificationDatatable', array(array('certificationstatus' => -1)));
}
$PAGE->requires->js_call_amd('block_training_calendar/showcalendar', 'init');
$PAGE->requires->js_call_amd('block_training_calendar/event_popup', 'load');

$PAGE->requires->js_call_amd('local_classroom/classroom', 'load',array());

echo $OUTPUT->header();
	
	//this is list of all moduletypes
	// $types = array('local_classroom'=>'Classrooms', 'local_onlinetests'=>'Online exams','mod'=>'Course Activities');
$status = array('enrol'=>'Enrolled', 'upcoming'=>'Upcoming','expired'=>'Expired');

	$alltypes = array();

	foreach ($status as $key => $value) {
		$moduletypes = array();
		$moduletypes['key'] = $key;
		$moduletypes['value'] = $value;
		$alltypes[] = $moduletypes;
	}

 	$output = $OUTPUT->render_from_template('block_training_calendar/calendardisplay', array('status' =>$alltypes));
 	echo $output;

echo $OUTPUT->footer();


