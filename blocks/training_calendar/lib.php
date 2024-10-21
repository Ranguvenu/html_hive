<?php
/**
 * event access to the user.
 *
 * todo check user access to event
 * @param object $event
 * @return array
 */
function calendar_check_event_access($event) {
	global $DB, $USER, $CFG, $COURSE;
	$context = context_system::instance();
	$dbman = $DB->get_manager();
	if (!empty($event->plugin)) {
		if (!$dbman->table_exists($event->plugin)) {
			$rowid = $event->plugin_instance;
			$table = $CFG->prefix.''.$event->plugin.'s';
		} else {
			if ($event->local_eventtype === "session_open" /* OR $event->local_eventtype === "session_close" */) {
	            $rowid = $event->plugin_itemid;
	            if($event->plugin == 'local_program'){
	            	$table = $CFG->prefix.'local_bc_course_sessions';
	            }else{
	            	$table = $CFG->prefix.''.$event->plugin.'_sessions';
	            }
			} else {
	            $rowid = $event->plugin_instance;
				$table = $CFG->prefix.''.$event->plugin;
			}
		}
	}else{
    	$rowid = $event->instance;
    	if($event->modulename){
    		$table = $CFG->prefix.''.$event->modulename;
    	}else{
    		$table = false;
    	}
    	$event->plugin = 'mod';
    }	
    if (!empty($event->plugin)) {
    	if($table){
    		$itemsql = "SELECT * from {$table} where id = ? ";
			$get_item = $DB->get_record_sql($itemsql, array($rowid));
    	}

     
      $enrolled = false;
		$self_enrol = false;
		if ($get_item) {
			// check user is enrolled to event or not
			switch($event->plugin) {	
				case 'local_onlinetests':
					if ((is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$context) || has_capability('local/costcenter:manage_ownorganization',$context) || has_capability('local/costcenter:manage_owndepartments',$context)) || $DB->record_exists_sql("SELECT ou.id from {local_onlinetest_users} ou, {local_onlinetests} o where o.id = ou.onlinetestid AND ou.userid = {$USER->id} AND o.id = {$get_item->id}"))
					$enrolled = true;
					else
					$enrolled = false;
					break;
				case 'local_evaluation':
					if ((( is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations',$context) OR (has_capability('local/costcenter:manage_ownorganization',$context)) OR has_capability('local/costcenter:manage_owndepartments',$context))) || 
						$DB->record_exists_sql("SELECT eu.id from {local_evaluation_users} AS eu  
						JOIN {local_evaluations} AS e ON e.id = eu.evaluationid 
						JOIN {user} AS u ON u.id=eu.userid
						WHERE e.id = {$get_item->id} AND (eu.userid = $USER->id AND e.evaluationmode LIKE 'SE') OR (u.open_supervisorid = {$USER->id} AND e.evaluationmode LIKE 'SP')") /*|| 
						$DB->record_exists_sql("SELECT eu.id from {local_evaluation_users} AS eu 
							JOIN {local_evaluations} AS e ON e.id=eu.evaluationid
							JOIN {user} AS u ON u.id=eu.userid
							WHERE u.open_supervisorid = {$USER->id} AND e.id = {$get_item->id} AND e.evaluationmode LIKE 'SP'")*/)
					$enrolled = true;
					else
					$enrolled = false;
					break;
				case 'local_classroom':
                    
          if ($event->local_eventtype === "session_open"/* OR $event->local_eventtype === "session_close"*/) {
						$itemid = $get_item->classroomid;
						$training_or_session = 'session';
					} else {
						$itemid = $get_item->id;
						$training_or_session = 'training';
					}
					if (( is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations',$context) OR (has_capability('local/costcenter:manage_ownorganization',$context)) OR has_capability('local/costcenter:manage_owndepartments',$context)) || $DB->record_exists_sql("SELECT cu.id from {local_classroom_users} cu, {local_classroom} c where c.id = cu.classroomid AND cu.userid = {$USER->id} AND c.id = {$itemid}")) {					
						$enrolled = true;
					} else {
						if ($DB->record_exists_sql("SELECT ct.id from {local_classroom_trainers} ct, {local_classroom} c where c.id = ct.classroomid AND ct.trainerid = {$USER->id} AND c.id = {$itemid}") && has_capability('local/classroom:trainer_viewclassroom',$context))
						$enrolled = true;
						else
						$enrolled = false;
					}
					// get sesion / classroom info
					if ($training_or_session == 'session') {
						$training_info = new stdclass();
						
						if ($get_item->trainerid)
							$training_info->trianers = $DB->get_field_sql("SELECT concat(u.firstname,' ', u.lastname) as trainer from {user} u where u.id = {$get_item->trainerid} ");
						else
							$training_info->trianers = 'N/A';
					 
			            $classroom_prerequisite = $DB->get_records('local_classroom',array('id' => $itemid));
                     foreach($classroom_prerequisite AS $classroom) {
                  	     $open_prerequisite = $classroom->open_prerequisites;
                        
                          if($open_prerequisite){
                          	
                          if ($prerequisitecourse_list = $DB->get_records_sql_menu("SELECT c.id, c.fullname as prerequisite FROM {course} c where c.id in ($classroom->open_prerequisites)") ) {
                        	$courses = [];
                        	foreach($prerequisitecourse_list AS $courseid => $coursename){
                        		$userid = $USER->id;
                        	    $course_completions_sql = "SELECT id FROM {course_completions} WHERE course = {$courseid} AND userid = {$userid} ";
                        	    $course_completions = $DB->get_record_sql($course_completions_sql);

                                 if($course_completions){
                        		    $courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-check text-success icon','title' => get_string('completed','block_training_calendar')));
                        		 } else {
                        		 	$courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-times text-danger icon','title' => get_string('notcompleted','block_training_calendar')));
                        		 }  

                        	 }
                            $prerequisitecourse = implode('<br>',$courses);
														$training_info->prerequisite = $prerequisitecourse;
                            
                       } else { 
                         $training_info->prerequisite = 'N/A';
                     }

                    } else if($open_prerequisites == NULL || $open_prerequisites == ''){
                    	$training_info->prerequisite = 'N/A';
                    }
                      
                  }
 
                   $prerequisite_completionchecksql = $DB->get_field('local_classroom','open_prerequisites',array('id' => $itemid));
                   if($prerequisite_completionchecksql){
                    	$pre_courses = explode(',',$prerequisite_completionchecksql);
                    	$completed=array();
                    	$userid = $USER->id;
                     
                     foreach($pre_courses as $course){
                      
                      $sql = "SELECT * FROM {course_completions} WHERE course = {$course} AND userid = {$userid} AND timecompleted IS NOT NULL ";
                       
                      $check=$DB->get_record_sql($sql);
                      if($check){
                        $completed[]=1;
                      }else{
                        $completed[]=0;
                      }
                    }
                   
                   if (in_array("0", $completed)){
                      $training_info->prerequisite_completion = false;
                   }else{
                      $training_info->prerequisite_completion = true;
                   }
 
               }else if($prerequisite_completionchecksql == '' || $prerequisite_completionchecksql == null || empty($prerequisite_completionchecksql)){
               	  $training_info->prerequisite_completion = true;
               }else{
                   $training_info->prerequisite_completion = false;
               }
 
               $training_info_url = $DB->get_field('local_classroom','open_url',array('id' => $itemid));
               
               if(!empty($training_info_url)){
               	  $training_url = html_writer::tag('a',$training_info_url,array('href' => ''));
               } else {
               	  $training_url = 'N/A';
               }
							 
						   $training_info->training_url = $training_url; 

						   $self_enrol = $DB->get_field('local_classroom','status',array('id' => $itemid, 'visible' => 1));
						   if($self_enrol == 1){
						   	 $training_info->selfenrol = get_string('yes','block_training_calendar');
						   }else{
						   	 $training_info->selfenrol = get_string('no','block_training_calendar');
						   }
							
							if ($get_item->roommid){
							  $training_info->location = $DB->get_field_sql("SELECT concat(r.name, ' / ',  r.building, ' / ', r.address) as location from {local_location_room} r where r.id = {$get_item->roommid} " );
						   }
						  else
							  $training_info->location = 'N/A';
					  
						
						$training_info->type = ($get_item->onlinesession) ? 'Online': 'Offline';
						$training_info->endtime = date('M-d-Y H:i', $get_item->timefinish);
						$training_info->starttime = (date('d-M-Y',$get_item->startdate).'&'.(date('H-i',$get_item->startdate)));
					} else {
						$training_info = new stdclass();
	                    if ($trainers_list = $DB->get_records_sql_menu("SELECT u.id, concat( u.firstname, ' ', u.lastname) as trainers FROM mdl_user u where u.id in (select trainerid from {local_classroom_trainers} where classroomid = {$get_item->id} )") )
							$training_info->trianers = implode(',', $trainers_list);
						  
						else
							$training_info->trianers = 'N/A';
						
                    
                  $classroom_prerequisite = $DB->get_records('local_classroom',array('id' => $itemid));
                  foreach($classroom_prerequisite AS $classroom) {
                  	 $open_prerequisite = $classroom->open_prerequisites;
											
											if($open_prerequisite){
                          	
                            if ($prerequisitecourse_list = $DB->get_records_sql_menu("SELECT c.id, c.fullname as prerequisite FROM {course} c where c.id in ($classroom->open_prerequisites)") ) {
                        	$courses = [];
                        	foreach($prerequisitecourse_list AS $courseid => $coursename){
                        		$userid = $USER->id;
                        	    $course_completions_sql = "SELECT id FROM {course_completions} WHERE course = {$courseid} AND userid = {$userid} ";
                        	    $course_completions = $DB->get_record_sql($course_completions_sql);

                                 if($course_completions){
                        		    $courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-check text-success icon', 'title' => get_string('completed','block_training_calendar')));
                        		 } else {
                        		 	$courses[] = html_writer::tag('a',$coursename,array('href' => $CFG->wwwroot.'/course/view.php?id='.$courseid)).' '.html_writer::tag('i', '', array('class' => 'fa fa-times text-danger icon','title' => get_string('notcompleted','block_training_calendar')));
                        		 }  

                        	 }
                            $prerequisitecourse = implode('<br>',$courses);

                           
                           $training_info->prerequisite = $prerequisitecourse;
                            
                       } else { 
                         $training_info->prerequisite = 'N/A';
                     }

                    } else if($open_prerequisites == NULL || $open_prerequisites == ''){
                    	 $training_info->prerequisite = 'N/A';
                    }

                  }

                  
                   $prerequisite_completionchecksql = $DB->get_field('local_classroom','open_prerequisites',array('id' => $itemid));
                   
                   if($prerequisite_completionchecksql){
                     $pre_courses = explode(',',$prerequisite_completionchecksql);

                     $completed=array();
                      $userid = $USER->id;
                      
                      foreach($pre_courses as $course){
                       
                         $sql = "SELECT * FROM {course_completions} WHERE course = {$course} AND userid = {$userid} AND timecompleted IS NOT NULL ";
                       
                         $check=$DB->get_record_sql($sql);
                         if($check){
                          $completed[]=1;
                         }else{
                          $completed[]=0;
                        }
                  }
                  
                  if (in_array("0", $completed)){
                      $training_info->prerequisite_completion = false;
                  }else{
                      $training_info->prerequisite_completion = true;
                  }
 
               }else if($prerequisite_completionchecksql == '' || $prerequisite_completionchecksql == null || empty($prerequisite_completionchecksql)){
                    $training_info->prerequisite_completion = true;
               } else {
               	   $training_info->prerequisite_completion = false;
               }

			   $training_info_url = $DB->get_field('local_classroom','open_url',array('id' => $itemid));
               
               if(!empty($training_info_url)){
               	  $training_url = html_writer::tag('a',$training_info_url,array('href' => ''));
               } else {
               	  $training_url = 'N/A';
               }
							 
				 $training_info->training_url = $training_url; 

				 $self_enrol = $DB->get_field('local_classroom','status',array('id' => $itemid, 'visible' => 1));
				  if($self_enrol == 1){
					  $training_info->selfenrol = get_string('yes','block_training_calendar');
				  }else{
				      $training_info->selfenrol = get_string('no','block_training_calendar');
				  }

					 if ($get_item->instituteid){
						$training_info->location =  $DB->get_field_sql("SELECT concat( fullname,' / ', address) as location from {local_location_institutes}  where id = {$get_item->instituteid} " );
					  }
					  else
					   	$training_info->location = 'N/A';
						
						  $training_info->type ='Classroom';
						  $training_info->endtime =date('M-d-Y H:i', $get_item->enddate);	
					 	$training_info->starttime = (date('d-M-Y',$get_item->startdate).'&'.(date('H-i',$get_item->startdate)));

					  }
					break; 

				case 'local_program':
					if ($event->local_eventtype === "session_open"/* OR $event->local_eventtype === "session_close" */) {
						$itemid = $get_item->programid;
						$training_or_session = 'session';
					} else {
						$itemid = $get_item->id;
						$training_or_session = 'training';
					}
					if ($DB->record_exists_sql("SELECT pu.id from {local_program_users} pu, {local_program} p where p.id = pu.programid AND pu.userid = $USER->id AND p.id = $itemid") OR (( is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations',$context) OR (has_capability('local/costcenter:manage_ownorganization',$context)) OR has_capability('local/costcenter:manage_owndepartments',$context)))) {					
						$enrolled = true;
					} else {
						// check if the user is trainer to session
						// if ($DB->record_exists_sql("select pu.id from {local_program_trainers} pu, {local_program} p where p.id = pu.programid AND pu.trainerid = $USER->id AND p.id = $itemid"))
						if($DB->record_exists_sql("SELECT lbcs.id from {local_bc_course_sessions} AS lbcs, {local_program} AS p where p.id = lbcs.programid AND lbcs.trainerid = {$USER->id} AND p.id = $itemid AND lbcs.id={$event->plugin_itemid}") && has_capability('local/program:trainer_viewprogram',$context))
						$enrolled = true;
						else
						$enrolled = false;
					}
					// get sesion / classroom info
					if ($training_or_session == 'session') {
						$training_info = new stdclass();
						
						if ($get_item->trainerid)
						$training_info->trianers = $DB->get_field_sql("SELECT concat(u.firstname,' ', u.lastname) AS trainer FROM {user} u where u.id = {$get_item->trainerid}" );
						else
						$training_info->trianers = 'N/A';
						
						// if ($get_item->roomid)
						// $training_info->location = $DB->get_field_sql("select group_concat(r.name, ' / ',  r.building, ' / ', r.address) as location from {local_location_room} r where r.id = $get_item->roomid " );
						// else
						// $training_info->location = 'N/A';
					
                  if ($get_item->instituteid){
							$training_info->location = $DB->get_field_sql("SELECT concat( fullname,' / ', address) as location from {local_location_institutes}  where id = {$get_item->instituteid} " );
						}
						else
							$training_info->location = 'N/A';
						
						$training_info->type = ($get_item->onlinesession) ? 'Online': 'Classroom';
						$training_info->endtime = date('M-d-Y H:i', $get_item->timefinish);
					   
					} else {
						$training_info = new stdclass();
						// if ($trainers_list = $DB->get_field_sql("SELECT group_concat( u.firstname, ' ', u.lastname) as trainers FROM {user} u where u.id in (SELECT trainerid from {local_program_trainers} where programid = $get_item->id )"))
						// $training_info->trianers = $trainers_list;
						if ($trainers_list = $DB->get_field_sql("SELECT concat( u.firstname, ' ', u.lastname) as trainers FROM {user} u WHERE u.id = (SELECT trainerid from {local_bc_course_sessions} WHERE id = {$get_item->id} ) "))
							$training_info->trianers = $trainers_list;
	                    else
							$training_info->trianers = 'N/A';
	                    
					    if ($get_item->instituteid){
							$training_info->location = $DB->get_field_sql("SELECT concat( fullname,' / ', address) as location from {local_location_institutes}  WHERE id = {$get_item->instituteid} " );
						}
						else
							$training_info->location = 'N/A';
						
						$training_info->type ='Classroom';
						$training_info->endtime =date('M-d-Y H:i', $get_item->enddate);	
					    				
					}
					break;
				case 'local_certification':
					if ($event->local_eventtype === "session_open" /* OR $event->local_eventtype === "session_close" */) {
						$itemid = $get_item->certificationid;
						$training_or_session = 'session';
					} else {
						$itemid = $get_item->id;
						$training_or_session = 'training';
					}
					if (( is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$context) || (has_capability('local/costcenter:manage_ownorganization',$context)) || has_capability('local/costcenter:manage_owndepartments',$context)) || $DB->record_exists_sql("SELECT cu.id from {local_certification_users} cu, {local_certification} c where c.id = cu.certificationid AND cu.userid = $USER->id AND c.id = $itemid")) {
						$enrolled = true;
					} else {
						if ($DB->record_exists_sql("SELECT ct.id from {local_certification_trainers} ct, {local_certification} c where c.id = ct.certificationid AND ct.trainerid = $USER->id AND c.id = $itemid") && has_capability('local/certification:trainer_viewcertification',$context))
						$enrolled = true;
						else
						$enrolled = false;
					}
					// get sesion / classroom info
					if ($training_or_session == 'session') {
						$training_info = new stdclass();
						
						if ($get_item->trainerid)
							$training_info->trianers = $DB->get_field_sql("SELECT concat(u.firstname,' ', u.lastname) as trainer from {user} u where u.id = {$get_item->trainerid} " );
						else
							$training_info->trianers = 'N/A';
						
						if ($get_item->roommid){
							$sql = "SELECT id, name, building, address
									FROM {local_location_room}
									WHERE id = {$get_item->roommid} ";
							$trlocation = $DB->get_record_sql($sql);

							$training_info->location = $trlocation->name.' / '.$trlocation->building.' / '.$trlocation->address;
						}else{
							$training_info->location = 'N/A';
						}

					    $training_info->type = ($get_item->onlinesession) ? 'Online': 'Offline';
						$training_info->endtime = date('M-d-Y H:i', $get_item->timefinish);
					 
					} else {
						$training_info = new stdclass();
	                    if ($trainers_list = $DB->get_records_sql_menu("SELECT id, concat( u.firstname, ' ', u.lastname) as trainers FROM mdl_user u where u.id in (select trainerid from {local_certification_trainers} where certificationid ={$get_item->id} )"))
						$training_info->trianers = implode(',', $trainers_list);
						else
	                    $training_info->trianers = 'N/A';
	                    
						if ($get_item->instituteid){
							$sql = "SELECT id, fullname, address
									FROM {local_location_institutes}
									WHERE id = {$get_item->instituteid} ";
							$trlocation = $DB->get_record_sql($sql);
							$training_info->location = $trlocation->fullname.' @ '.$trlocation->address;
						}else{
							$training_info->location = 'N/A';
						}
						
						$training_info->type ='Classroom';
						$training_info->endtime =date('M-d-Y H:i', $get_item->enddate);	
						 
					}
					break;

					//this is for all course module view in my calendar
					case 'mod':
						$training_info = new stdclass();

			            if ($DB->record_exists_sql("select ue.id from {user_enrolments} as ue JOIN {enrol} as enrol ON enrol.id = ue.enrolid where (enrol.enrol='manual' OR enrol.enrol = 'self') AND enrol.courseid = $event->courseid AND ue.userid = $USER->id")){
			            	$moduleid = $DB->get_field('modules','id',array('name' => $event->modulename));

			            	$coursemoduleid = $DB->get_field('course_modules','id',array('course' => $event->courseid,'instance' => $event->instance,'module' => $moduleid));

			            	$enrolled = true;
			            	$training_info->instance = $coursemoduleid;
			            }else{
			            	$enrolled = false;
			            }
					break;

					case 'user':
			            $enrolled = false;
					break;

					default:
						$enrolled = false;
			}
			if (!$enrolled) { // check if the plugin allows self enrollment			
				if ($event->plugin ==='local_program' OR $event->plugin ==='local_classroom' OR $event->plugin ==='local_certification') {
					if ($get_item->costcenter == $USER->open_costcenterid) { // user can enroll self to the item
						if ($get_item->department > 0) {
							$explode_department = explode(',', $get_item->department);
							if (in_array($USER->open_departmentid, $explode_department)) {
								$self_enrol = true;
							} else {
								$self_enrol = false;
							}
						} else {
							$self_enrol = true;
						}
					} else {
						$self_enrol = false;
					}
					if($self_enrol && !empty($get_item->open_group)){
						$self_enrol = in_array($USER->open_group, explode(',', $get_item->open_group)) ? true : false;
					}
					if($self_enrol && !empty($get_item->open_hrmsrole)){
						$self_enrol = in_array($USER->open_hrmsrole, explode(',', $get_item->open_hrmsrole)) ? true : false;
					}
					if($self_enrol && !empty($get_item->open_designation)){
						$self_enrol = in_array($USER->open_designation, explode(',', $get_item->open_designation)) ? true : false;
					}
					if($self_enrol && !empty($get_item->open_location)){
						$self_enrol = in_array($USER->open_location, explode(',', $get_item->open_location)) ? true : false;
					}
				}
			}
			$return = array('enrolled'=>$enrolled, 'self_enrol'=>$self_enrol, 'training_info'=>$training_info);
		} else {
			$return = array('enrolled'=>$enrolled, 'self_enrol'=>$self_enrol, 'training_info'=>$training_info);
		}
	} else {
		$return = array('enrolled'=>true, 'self_enrol'=>false, 'training_info'=>$training_info);
	}
	return $return;
}


function plugins_access_sql($table) {
    global $DB, $USER;
	$context = context_system::instance();
    if(has_capability('local/costcenter:manage_ownorganization',$context)) {
        $costcenter = $DB->get_record_sql("SELECT cc.id, cc.parentid FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");
        if ($costcenter->parentid == 0) {
			if ($table == "local_onlinetests" OR $table == "local_evaluations" )
            $sql =" and costcenterid IN( {$costcenter->id} )";
			else
			$sql =" and costcenter IN( {$costcenter->id} )";
        } else {
			if ($table == "local_onlinetests" OR $table == "local_evaluations" )
            // $sql =" and ( find_in_set($costcenter->id, departmentid) <> 0)  ";
            $sql =" and ( concat(',',departmentid,',') LIKE '%,{$costcenter->id},%' )  ";
			else
			// $sql =" and ( find_in_set($costcenter->id, department) <> 0)  ";
			$sql =" and ( concat(',',department,',') LIKE '%,{$costcenter->id},%' )  ";
        }
    } else {
		if ($table == "local_onlinetests" OR $table == "local_evaluations" )
        // $sql =" and ( find_in_set($USER->open_departmentid, departmentid) <> 0)  ";
		$sql =" and ( concat(',', departmentid, ',') LIKE '%,{$USER->open_departmentid},%')  ";
		else
		// $sql =" and ( find_in_set($USER->open_departmentid, department) <> 0)  ";
		$sql =" and ( concat(',', department, ',') LIKE '%,{$USER->open_departmentid},%')  ";
    }
    return $sql;
}

function users_plugin_access_sql() {
    global $DB, $USER;
	$context = context_system::instance();
	if($USER->open_costcenterid){
		$sql .= " AND (( lc.costcenter !=0 AND $USER->open_costcenterid in (lc.costcenter)))";      
	}
    return $sql;
}

//this is for moduletype cases
function commonmodule_plugin_access_sql($onlinetests_sql,$evaluations_sql,$classrooms_sql,$programs_sql,$certifications_sql) {
	global $DB, $USER;
	$plugins = \block_training_calendar\calendarlib::trainingcalendar_plugin_details();
	$sql_array = array();
	if($plugins['onlinetest']){
		$sql_array[] = " when e.plugin = 'local_onlinetests' then (select o.id from {local_onlinetests} o where o.id = e.plugin_instance $onlinetests_sql) ";
	}
	if($plugins['feedback']){
		$sql_array[] = " when e.plugin = 'local_evaluation' then (select o.id from {local_evaluations} o where o.id = e.plugin_instance $evaluations_sql) ";
	}
	if($plugins['classroom']){
	   $sql_array[] = " when e.plugin = 'local_classroom' then (select o.id from {local_classroom} o where o.id = e.plugin_instance AND o.status = 1 $classrooms_sql) ";
	  
    }
    
    if($plugins['program']){
		$sql_array[] = " when e.plugin = 'local_program' then (select o.id from {local_program} o where o.id = e.plugin_instance $programs_sql) ";
	}
	if($plugins['program']){
		$sql_array[] = " when e.plugin = 'local_certification' then (select o.id from {local_certification} o where o.id = e.plugin_instance AND o.status = 1 $certifications_sql) ";
	}
	$final_concatsql = implode('', $sql_array);

	$sql = " 0 < case $final_concatsql end ";
	return $sql;
}

//this is for enduser cases
function commonuser_plugin_access_sql($user_access_sql) {
	global $USER,$DB;
	$plugins = \block_training_calendar\calendarlib::trainingcalendar_plugin_details();
	$sql_array = array();
	if($plugins['onlinetest']){
		$sql_array[] = " when e.plugin = 'local_onlinetests' then (select o.id from {local_onlinetest_users} o where o.onlinetestid = e.plugin_instance AND userid = $USER->id) ";
	}
	if($plugins['feedback']){
		$sql_array[] = " when e.plugin = 'local_evaluation' then (select o.id from {local_evaluation_users} o where o.evaluationid = e.plugin_instance AND userid = $USER->id) ";
	}
	if($plugins['classroom']){
	
       $usergrade = $DB->get_field('user','open_grade',array('id' => $USER->id));
       $userlocation = $DB->get_field('user','city',array('id' => $USER->id));
      
            
       if($usergrade) {
        	$sql_array[] = " when e.plugin = 'local_classroom' then (select lc.id from {local_classroom} lc where lc.id = e.plugin_instance AND lc.status = 1 AND (FIND_IN_SET('$usergrade', lc.open_grade) OR lc.open_grade IS NULL OR lc.open_grade = '') $user_access_sql) ";
        
        } else if($userlocation){
        	$sql_array[] = " when e.plugin = 'local_classroom' then (select lc.id from {local_classroom} lc where lc.id = e.plugin_instance AND lc.status = 1 AND (FIND_IN_SET('$userlocation', lc.open_location) OR lc.open_location IS NULL OR lc.open_location = '') $user_access_sql) ";

        } else {
        	$sql_array[] = " when e.plugin = 'local_classroom' then (select lc.id from {local_classroom} lc where lc.id = e.plugin_instance AND lc.status = 1 $user_access_sql) ";
        } 

     }

    if($plugins['program']){
		$sql_array[] = " when e.plugin = 'local_program' then (select lc.id from {local_program} lc where lc.id = e.plugin_instance $user_access_sql) ";
	}
	if($plugins['certification']){
		$sql_array[] = " when e.plugin = 'local_certification' then (select lc.id from {local_certification} lc where lc.id = e.plugin_instance AND lc.status = 1 $user_access_sql) ";
	}
	$final_concatsql = implode('', $sql_array);
	// $sql = " case
	// 	when e.plugin = 'local_onlinetests' then (select o.id from {local_onlinetest_users} o where o.onlinetestid = e.plugin_instance AND userid = $USER->id)
	// 	when e.plugin = 'local_evaluation' then (select o.id from {local_evaluation_users} o where o.evaluationid = e.plugin_instance AND userid = $USER->id)
	// 	when e.plugin = 'local_classroom' then (select lc.id from {local_classroom} lc where lc.id = e.plugin_instance AND lc.status = 1 $user_access_sql)
	// 	when e.plugin = 'local_program' then (select lc.id from {local_program} lc where lc.id = e.plugin_instance $user_access_sql)
	// 	when e.plugin = 'local_certification' then (select lc.id from {local_certification} lc where lc.id = e.plugin_instance AND lc.status = 1 $user_access_sql)
	// end ";
	$sql = " 0 < case $final_concatsql end ";
	return $sql;
}

/*
* Author Sarath
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function block_training_calendar_leftmenunode(){
    
    $trainingcalendarnode = '';
    
    $systemcontext = context_system::instance();
    if(has_capability('block/training_calendar:view',$systemcontext) || is_siteadmin()){
        $trainingcalendarnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_trainingcalendar', 'class'=>'pull-left user_nav_div trainingcalendar'));
        
        $trainingcalendar_url = new moodle_url('/blocks/training_calendar/view.php');
        $trainingcalendar_label = get_string('pluginname','block_training_calendar');

        $trainingcalender = html_writer::link($trainingcalendar_url, '<span class="calendar_icon left_menu_icons"></span><span class="user_navigation_link_text">'.$trainingcalendar_label.'</span>',array('class'=>'user_navigation_link'));

        $trainingcalendarnode .= $trainingcalender;
        $trainingcalendarnode .= html_writer::end_tag('li');
    }
    // return $trainingcalendarnode;
     return array('23' => $trainingcalendarnode);
}

/**
 * Get default calendar events with plugin column add by sarath.
 *
 * @param int $tstart Start time of time range for events
 * @param int $tend End time of time range for events
 * @param array|int|boolean $users array of users, user id or boolean for all/no user events
 * @param array|int|boolean $groups array of groups, group id or boolean for all/no group events
 * @param array|int|boolean $courses array of courses, course id or boolean for all/no course events
 * @param boolean $withduration whether only events starting within time range selected
 *                              or events in progress/already started selected as well
 * @param boolean $ignorehidden whether to select only visible events or all events
 * @param array|int|boolean $categories array of categories, category id or boolean for all/no course events
 * @return array $events of selected events or an empty array if there aren't any (or there was an error)
 */
function default_calendar_get_events($tstart, $tend, $users, $groups, $courses,
    $withduration = true, $ignorehidden = true, $categories = []) {
    global $DB;

    $whereclause = '';
    $params = array();
    // Quick test.
    if (empty($users) && empty($groups) && empty($courses) && empty($categories)) {
        return array();
    }

    if ((is_array($users) && !empty($users)) or is_numeric($users)) {
        // Events from a number of users
        if(!empty($whereclause)) $whereclause .= ' OR';
        list($insqlusers, $inparamsusers) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED);
        $whereclause .= " (e.userid $insqlusers AND e.courseid = 0 AND e.groupid = 0 AND e.categoryid = 0)";
        $params = array_merge($params, $inparamsusers);
    } else if($users === true) {
        // Events from ALL users
        if(!empty($whereclause)) $whereclause .= ' OR';
        $whereclause .= ' (e.userid != 0 AND e.courseid = 0 AND e.groupid = 0 AND e.categoryid = 0)';
    } else if($users === false) {
        // No user at all, do nothing
    }

    if ((is_array($groups) && !empty($groups)) or is_numeric($groups)) {
        // Events from a number of groups
        if(!empty($whereclause)) $whereclause .= ' OR';
        list($insqlgroups, $inparamsgroups) = $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED);
        $whereclause .= " e.groupid $insqlgroups ";
        $params = array_merge($params, $inparamsgroups);
    } else if($groups === true) {
        // Events from ALL groups
        if(!empty($whereclause)) $whereclause .= ' OR ';
        $whereclause .= ' e.groupid != 0';
    }
    // boolean false (no groups at all): we don't need to do anything

    if ((is_array($courses) && !empty($courses)) or is_numeric($courses)) {
        if(!empty($whereclause)) $whereclause .= ' OR';
        list($insqlcourses, $inparamscourses) = $DB->get_in_or_equal($courses, SQL_PARAMS_NAMED);
        $whereclause .= " (e.groupid = 0 AND e.courseid $insqlcourses)";
        $params = array_merge($params, $inparamscourses);
    } else if ($courses === true) {
        // Events from ALL courses
        if(!empty($whereclause)) $whereclause .= ' OR';
        $whereclause .= ' (e.groupid = 0 AND e.courseid != 0)';
    }

    if ((is_array($categories) && !empty($categories)) || is_numeric($categories)) {
        if (!empty($whereclause)) {
            $whereclause .= ' OR';
        }
        list($insqlcategories, $inparamscategories) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED);
        $whereclause .= " (e.groupid = 0 AND e.courseid = 0 AND e.categoryid $insqlcategories)";
        $params = array_merge($params, $inparamscategories);
    } else if ($categories === true) {
        // Events from ALL categories.
        if (!empty($whereclause)) {
            $whereclause .= ' OR';
        }
        $whereclause .= ' (e.groupid = 0 AND e.courseid = 0 AND e.categoryid != 0)';
    }

    // Security check: if, by now, we have NOTHING in $whereclause, then it means
    // that NO event-selecting clauses were defined. Thus, we won't be returning ANY
    // events no matter what. Allowing the code to proceed might return a completely
    // valid query with only time constraints, thus selecting ALL events in that time frame!
    if(empty($whereclause)) {
        return array();
    }

    if($withduration) {
        $timeclause = '(e.timestart >= '.$tstart.' OR e.timestart + e.timeduration > '.$tstart.') AND e.timestart <= '.$tend;
    }
    else {
        $timeclause = 'e.timestart >= '.$tstart.' AND e.timestart <= '.$tend;
    }
    if(!empty($whereclause)) {
        // We have additional constraints
        $whereclause = $timeclause.' AND ('.$whereclause.')';
    }
    else {
        // Just basic time filtering
        $whereclause = $timeclause;
    }

    if ($ignorehidden) {
        $whereclause .= ' AND e.visible = 1';
    }

    $sql = "SELECT e.*
              FROM {event} e
         LEFT JOIN {modules} m ON e.modulename = m.name
                -- Non visible modules will have a value of 0.
             WHERE e.plugin is null AND (m.visible = 1 OR m.visible IS NULL) AND $whereclause
          ORDER BY e.timestart";
    $events = $DB->get_records_sql($sql, $params);

    if ($events === false) {
        $events = array();
    }
    return $events;
}
//ended by sarath
