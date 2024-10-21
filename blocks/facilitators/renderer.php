<?php

global $CFG;
//require_once($CFG->dirroot . '/blocks/learning_plan/renderer.php');

class block_facilitators_renderer extends plugin_renderer_base {   
	
	 
	 
	function facilitators_info(){
        global $DB,$OUTPUT,$CFG,$COURSE;
		$id=$COURSE->id;
		
			$sql = "SELECT * FROM {course_facilitator} WHERE courseid = $id GROUP BY employeeid";
			$teachers = $DB->get_records_sql($sql);
			if($teachers){
				
				$facil_res='';
				$teach = array();
				foreach($teachers as $teacher){
					$out = '';
					$tuser = $DB->get_record('user', array('id'=>$teacher->employeeid));
					
					$contact = get_string('name').": ". fullname($tuser).", ";
					if($tuser->phone1){
						$ph = $tuser->phone1;
					}else{
						$ph = '-';
					}
					//$ph = $tuser->phone1 ? $tuser->phone1 : ' - ' ;
						$contact .= get_string('phone').": ". $ph.", ";
					//if($tuesr->email)
					$em = $tuser->email ? $tuser->email : ' - ' ;
						$contact .= get_string('email').": ". $em." ";
						
					$profile_pic = html_writer::tag('li', $OUTPUT->user_picture($tuser, array('size'=>100)), array('class'=>'auth_img'));
					
					$out = html_writer::tag('ul', $profile_pic, array('class'=>'auth_details', 'title'=>$contact));
					if($tuser->description){
						$out .= html_writer::tag('li', $tuser->description, array('class'=>'auth_summary'));
					}
					$teach[] = $out;
				}
				$facil_res .=  implode(' ', $teach);
			}else{
				$facil_res='';
				$facil_res .= html_writer::tag('p',get_string('nofacilitator_assigned','block_facilitators'),array());
			}
		return $facil_res;	  
	}
	
}
