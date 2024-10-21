<?php
define('AJAX_SCRIPT',true);
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB, $USER;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

//require_once($CFG->dirroot .'/local/includes.php');
// require_once($CFG->libdir.'/enrollib.php');
// require_once($CFG->dirroot.'/local/lib.php');
//$id  = required_param('id', PARAM_INT); // Course id
$crid = optional_param('crid','', PARAM_INT);
$programid = optional_param('programid','', PARAM_INT);
$certificationid = optional_param('certificationid','', PARAM_INT);
$learningplanid = optional_param('learningplanid','', PARAM_INT);
$renderer = $PAGE->get_renderer('local_catalog');
if ($crid){
	echo $renderer->get_classroom_info($crid);
}
if ($programid){
    echo $renderer->get_program_info($programid);
}
if ($certificationid){
    echo $renderer->get_certification_info($certificationid);
}
if ($learningplanid){
    echo $renderer->get_learningplan_info($learningplanid);
}
// }
?>






