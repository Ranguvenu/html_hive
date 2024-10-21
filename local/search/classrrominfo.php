<?php
define('AJAX_SCRIPT',true);
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB, $USER;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
require_login();

$crid = optional_param('crid','', PARAM_INT);
$learningplanid = optional_param('learningplanid','', PARAM_INT);
$ccourseid = optional_param('ccourseid','', PARAM_INT);
$renderer = $PAGE->get_renderer('local_search');
if ($crid){
	echo $renderer->get_classroom_info($crid);
}
if ($learningplanid){
    echo $renderer->get_learningplan_info($learningplanid);
}
if ($ccourseid){
    echo $renderer->get_coursera_programs($ccourseid);
}
// }
?>






