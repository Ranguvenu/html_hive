<?php
require('../../config.php');
global $DB, $USER, $CFG;
//lini_set('always_populate_raw_post_data', -1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once($CFG->dirroot.'/mod/doselect/classes/doselect.php');
 $doselect = new doselect();

$userid= 3318;
$slugid="nm00o";

$assessmentslist = $doselect->doselect_userreport($slugid,$userid);
        
 


?>
