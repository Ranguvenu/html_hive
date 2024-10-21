
<?php

/**
 * Version details
 *
 * @package    local_courseassignment
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/courseassignment/lib.php');
require_once($CFG->dirroot . '/local/courseassignment/filters_form.php');

require_login();
global $DB, $PAGE, $visible,$OUTPUT;
$PAGE->requires->jquery();

$PAGE->requires->js_call_amd('local_courseassignment/grader', 'load', array());

$PAGE->set_url(new moodle_url('/local/courseassignment/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('page_title','local_courseassignment'));

$PAGE->set_heading(get_string('page_title','local_courseassignment'));

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_courseassignment');
$filterparams = $renderer->get_courseassignments(true);

$thisfilters = array('assignmentcourse', 'approvalstatus','fromdate','todate');

$mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams));
     
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/courseassignment/index.php');
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
    $show = 'show';
} else{ 
    $show = '';
} 

echo  '<div class="'.$show.'" id="local_courses-filter_collapse">
        <div id="filters_form" class="card card-body p-2">';
         $mform->display();
echo        '</div>';
$filterparams['submitid'] = 'form#filteringform';
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);

echo $renderer->get_courseassignments(); 
echo $OUTPUT->footer();



