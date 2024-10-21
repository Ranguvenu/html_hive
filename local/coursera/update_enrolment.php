<?php

require_once(dirname(__FILE__) . '/../../config.php');

global $SITE, $PAGE, $OUTPUT,$DB,$CFG,$USER;

$sql="select * from {course} where id>2 AND open_courseprovider=5 AND category=33";

$newcourse= $DB->get_records_sql($sql);
foreach($newcourse as $course){
 $existing_method = $DB->get_record('enrol',array('courseid'=> $course->id  ,'enrol' => 'self'));
 if($existing_method){
 	 $existing_method->status = 0;
                $existing_method->customint6 = 1;
                $DB->update_record('enrol', $existing_method);
 }
               
}

?>