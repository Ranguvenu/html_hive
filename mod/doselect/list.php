<?php
require('../../config.php');

global $DB, $USER, $CFG;

require_once($CFG->dirroot.'/mod/doselect/classes/doselect.php');
require_once($CFG->libdir . '/completionlib.php');

 
  $doselect = new doselect();
  $slugid='k190e';
  $userid=1075;
  $id=64;
  $courseid=212;

  $assessmentslist = $doselect->doselect_userreport($slugid,$userid);


   	/*	$cm= get_coursemodule_from_instance('doselect',$id,$courseid);
    		$doselect = $DB->get_record('doselect', array('id'=>$cm->instance), '*', MUST_EXIST);
   		// Update completion state.
                 $course= $DB->get_record('course',array('id'=>$courseid));

    		$completion = new completion_info($course);
               $enabled=$completion->is_enabled($cm);
    		if ($doselect->completionpass) {
                      
                      
        		$completion->update_state($cm, COMPLETION_COMPLETE, $userid);
    		}
  
      */

?>
