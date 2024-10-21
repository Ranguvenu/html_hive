<?php
require_once(dirname(__FILE__) . '/../../config.php');

global $DB, $PAGE,$CFG;
require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$action = optional_param('action', '', PARAM_ALPHA);
$costcenter = optional_param('costcenter', 0, PARAM_INT);
//$department = optional_param('department', 0, PARAM_INT);
$department = optional_param('department', '', PARAM_RAW);
$userlib = new local_users\functions\userlibfunctions();
switch ($action) {
	case 'departmentlist':
	$departments = $userlib->find_departments_list($costcenter);
	foreach($departments as $depart){
		$departmentslist[$depart->id]=$depart->fullname;
	}
	echo json_encode(['data' =>$departmentslist]);
	break;
/* 	case 'groupslist':
	if ($costcenter != 0) {
		$groupslist = $DB->get_records_sql_menu("select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.costcenterid = ?", array($costcenter));
	} else {
		if ($department) {
			$gsql = "select lg.id, c.name from {cohort} c, {local_groups} lg where lg.cohortid = c.id AND lg.departmentid IN ( $department)";
		$groupslist = $DB->get_records_sql_menu($gsql);
		}
		
	} */
	
	echo json_encode(['data' =>$groupslist]);
	break;
}
