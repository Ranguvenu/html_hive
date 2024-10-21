<?php

/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_evaluation
 */


require_once("../../config.php");
require_once("lib.php");
require_once('evaluation_form.php');

global $DB, $OUTPUT,$USER,$CFG;
require_once($CFG->dirroot . '/local/courses/filters_form.php');

$id = optional_param('id', -1, PARAM_INT); // evalauation id
$plugin = optional_param('plugin','site',PARAM_RAW);
$instance = optional_param('instance', 0, PARAM_INT); // instance id from other pluign
$delete = optional_param('delete', 0, PARAM_INT);
$tab = optional_param('tab',0,PARAM_RAW);
$userid = optional_param('userid','',PARAM_INT);
$sessiontype = optional_param('sessiontype','all',PARAM_RAW);
require_login();

$context = context_system::instance();
if (!has_capability('local/evaluation:view', $context) ) {
    print_error("You dont have permission to view this page.");
}
$PAGE->set_url('/local/evaluation/index.php');
$PAGE->set_context($context);
// $PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('my_feedbacks', 'local_evaluation'));
$PAGE->set_heading(get_string('my_feedbacks', 'local_evaluation'));
if (is_siteadmin() OR has_capability('local/evaluation:edititems', $context)) {
	$PAGE->set_title(get_string('browse_feedback', 'local_evaluation'));
	$PAGE->set_heading(get_string('browse_feedback', 'local_evaluation'));
}
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_costcenter/fragment', 'init', array());
$PAGE->requires->js_call_amd('local_evaluation/evaluation', 'load', array());
$PAGE->requires->css('/local/evaluation/css/jquery.dataTables.css');

$core_component = new core_component();
$epsilon_plugin_exist = $core_component::get_plugin_directory('theme', 'epsilon');
if(!empty($epsilon_plugin_exist)){
	$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
}

$current_langcode = current_language();  /* $SESSION->lang;*/
$stringman = get_string_manager();
$strings = $stringman->load_component_strings('local_evaluation', $current_langcode);   /*'en'*/
$PAGE->requires->strings_for_js(array_keys($strings), 'local_evaluation');
$PAGE->navbar->add(get_string("pluginname", 'local_evaluation'));
$renderer = $PAGE->get_renderer('local_evaluation');
$filterparams = $renderer->get_evaluations(true);
if(is_siteadmin()){
    $thisfilters = array('organizations', 'departments', 'evaluation', 'evaluation_type', 'status');
}else if(has_capability('local/costcenter:manage_ownorganization',$context) || has_capability('local/evaluation:manage_ownorganization',$context)){
    $thisfilters = array('departments', 'evaluation', 'evaluation_type', 'status');
}else if(has_capability('local/costcenter:manage_owndepartments',$context) || has_capability('local/evaluation:manage_owndepartments',$context)){
    $thisfilters = array('evaluation', 'evaluation_type', 'status');
}else {
    $thisfilters = array('evaluation', 'evaluation_type');
}
$mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams));
     
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/courses/courses.php');
} else{
    $filterdata =  $mform->get_data();
    if($filterdata){
        $collapse = false;
    } else{
        $collapse = true;
    }
}
if(empty($filterdata) && !empty($jsonparam)){
    $filterdata = json_decode($jsonparam);
    foreach($thisfilters AS $filter){
        if(empty($filterdata->$filter)){
            unset($filterdata->$filter);
        }
    }
    $mform->set_data($filterdata);
}
if($filterdata){
    $collapse = false;
    $show = 'show';
} else{
    $collapse = true;
    $show = '';
}
$PAGE->requires->js_call_amd('local_evaluation/newevaluation', 'load', array());
echo $OUTPUT->header();

if (is_siteadmin() OR has_capability('local/evaluation:edititems', $context)) {	
	if ($delete) {
		$evaluation = $DB->get_record('local_evaluations', array('id'=>$id));
		evaluation_delete_instance($id);		
		$params = array(
			'context' => $context,
			'objectid' => $id
		);

		$event = \local_evaluation\event\evaluation_deleted::create($params);
		$event->add_record_snapshot('local_evaluations', $evaluation);
		$event->trigger();
		redirect('index.php');
	}
	$PAGE->requires->js_call_amd('local_evaluation/newevaluation', 'init', array('[data-action=createevaluationmodal]', $context->id, $id, $instance, $plugin));
	$PAGE->requires->js_call_amd('local_evaluation/newevaluation', 'getdepartmentlist');
}
if (is_siteadmin() OR has_capability('local/evaluation:addinstance', $context)) {
	echo '<ul class="course_extended_menu_list">
		<li>	
			<div class = "coursebackup course_extended_menu_itemcontainer">
				<a class="course_extended_menu_itemlink createeval" data-value="0" data-action="createevaluationmodal" title="'.get_string("createevaluation", "local_evaluation").'">
					<i class="icon fa fa-clipboard" aria-hidden="true"></i>
					<i class="fa fa-plus createiconchild" aria-hidden="true"></i>
				</a>
			</div>
		</li>
	</ul>';
}
echo '<a class="btn-link btn-sm" href="javascript:void(0);" data-toggle="collapse" data-target="#local_courses-filter_collapse" aria-expanded="false" aria-controls="local_courses-filter_collapse" title="Filters">
        <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
      </a>';
echo  '<div class="collapse '.$show.' local_filter_collapse" id="local_courses-filter_collapse">
            <div id="filters_form" class="card card-body p-2">';
                $mform->display();
echo        '</div>
        </div>';
$filterparams['submitid'] = 'form#filteringform';
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);
echo $renderer->get_evaluations();
echo $OUTPUT->footer();
