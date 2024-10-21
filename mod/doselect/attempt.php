<?php
require('../../config.php');
global $DB, $USER, $CFG;
//lini_set('always_populate_raw_post_data', -1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once($CFG->dirroot.'/mod/doselect/classes/doselect.php');
//$result= $HTTP_RAW_POST_DATA;
$result=file_get_contents('php://input');
print_object($result);
$result= utf8_encode($result);
// $result= mb_convert_encoding($result, "UTF-8", mb_detect_encoding($result));
$responsedata=json_decode($result,TRUE);
print_object($result);
//print_object($responsedata);
print_object($responsedata);
echo "test";
$data = new stdClass;
// exit;
  if(!empty($responsedata) && !empty($responsedata['object'])) {
    $fields = 'id,course';
    $doselect = $DB->get_record('doselect',array('doselect_slug'=>$responsedata['object']['test_slug']), $fields);
    // $userid = $DB->get_record('doselect',array('doselect_slug'=>$responsedata['object']['test_slug']), $fields);
    if($doselect){
       $useremail=$responsedata['object']['email'];
      $data->courseid = $doselect->course;
      $data->doselectid = $doselect->id;
      $data->userid = $DB->get_field('user','id',array('email'=>$useremail));
      $data->report_uri = $responsedata['object']['report_uri'];
      $data->test_uri = $responsedata['object']['test_uri'];
      $data->email = $responsedata['object']['email'];
      $data->test_slug = $responsedata['object']['test_slug'];
      $data->action=$responsedata['meta']['action'];
      $data->response= $data->action;

      $data->timecreated = time();
      
      $data->id = $DB->insert_record('doselect_objects', $data);
      print_object($responsedata['meta']['action']);
      if($data->action == 'report'){
        $slugid = $responsedata['object']['test_slug'];
        $doselect = new doselect();
        echo "Start to get Reports";
        $assessmentslist = $doselect->doselect_userreport($slugid,$data->userid);
        
      }
      
    }
 }
 else if(!empty($result)){
       $useremail=1;
      $data->courseid = 1;
      $data->doselectid = 1;
      $data->userid = 1;
      $data->report_uri = $result;
      $data->test_uri = 1;
      $data->email = 1;
      $data->test_slug = 1;
      $data->action=1;

      $data->timecreated = time();
      
      $data->id = $DB->insert_record('doselect_objects', $data);
    
      
    }


