<?php
require_once(dirname(__FILE__) . '/../../config.php');

global $DB, $USER, $CFG,$PAGE,$OUTPUT;
require_once($CFG->dirroot . '/local/ilp/lib.php');
require_once($CFG->dirroot . '/local/notifications/lib.php');
require_once($CFG->dirroot . '/local/users/lib.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');
$planid = optional_param('id', 0, PARAM_INT);
$users = optional_param('user', 'courses', PARAM_TEXT);


global $DB, $USER, $CFG,$PAGE,$OUTPUT;

$ilp=new local_ilp\lib\lib();
$sql="select llu.id,llu.userid,llu.planid from {local_ilp_user} as llu";

$allusers=$DB->get_records_sql($sql);
// print_object($allusers);
foreach($allusers as $all){
   
//$completed=$ilp->complete_the_lep(170,139);
$completed=$ilp->complete_the_lep($all->planid,$all->userid);

}


