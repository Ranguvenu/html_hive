<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB, $USER;
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






