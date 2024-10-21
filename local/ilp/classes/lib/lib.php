<?php
namespace local_ilp\lib;
use context_module;
use file_encode_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use context_system;
use html_writer;
use html_table;
use moodle_url;
class lib {
    function __construct(){
        global $DB, $USER;
        $this->db = $DB;
        $this->user = $USER;
       
    }
    
    function create_ilp($data){	
		
		$data->description = $data->description['text'];
        $data->usercreated =  $this->user->id;
        $data->userid =  $this->user->id;
		$data->timecreated = time();
		$data->visible = 1;
		$data->department = $this->user->open_departmentid;
		$data->costcenter = $this->user->open_costcenterid;

		if($data->summaryfile){
			$systemcontext = context_system::instance();
			file_save_draft_area_files($data->summaryfile, $systemcontext->id, 'local_ilp', 'summaryfile', $data->summaryfile);
		}

		$data->planid = $this->db->insert_record('local_ilp', $data);
		if($data->planid) {
			$data->userid = $this->user->id;

			//Users creating their own Learning plans, so need to assign them to the plan.
			$this->assign_users_to_ilp($data);
		}
		return $data->planid;
    }
	
	function update_ilp($data){
		if($data->description){
			$data->description = $data->description['text'];
		}
		
		$data->usermodified =  $this->user->id;
		$data->timemodified = time();
		$existingsummaryfile = $this->db->get_field('local_ilp', 'summaryfile', array('id' => $data->id));
		if($data->summaryfile){
			$systemcontext = context_system::instance();
			file_save_draft_area_files($data->summaryfile, $systemcontext->id, 'local_ilp', 'summaryfile', $data->summaryfile);
		}
		if(!empty($data->id)){
			$return = $this->db->update_record('local_ilp', $data);
		}

		return $return;
    }

    /**
     * [get_enrollable_users_to_ilp description]
     * @param  [int] $planid [learningpath id]
     * @return [object]         [object of id's of users]
     */
    public function get_enrollable_users_to_ilp($planid){
    	global $DB,$USER;
		
		if(!is_siteadmin()){
			$siteadmin_sql=" AND u.suspended =0
								 AND u.deleted =0  AND u.open_costcenterid = $USER->open_costcenterid ";
		}else{
			$siteadmin_sql="";
		}
		
    	$plan_info = $DB->get_record('local_ilp',array('id' => $planid));

	    $sql = "SELECT u.id FROM {user} AS u WHERE u.id > 2 $siteadmin_sql AND u.id not in ($USER->id) ";

	         

		if($plan_info->department !== null && $plan_info->department !== '-1'&& $plan_info->department !== 0){
				$sql.= ' AND u.open_departmentid IN('.$plan_info->department.')';
		}
    // OL-1042 Add Target Audience to Classrooms//
	   $params = array();
            if(!empty($plan_info->open_group)){
                $group_list = $DB->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$plan_info->open_group})");
                 
                 $groups_members = implode(',', $group_list);
                 if (!empty($groups_members))
                 $sql .=" AND u.id IN ({$groups_members})";
                 else
                 $sql .=" AND u.id =0";
                 
            }                         
            if(!empty($plan_info->open_hrmsrole)){
				 $implode_result=implode("\",\"",explode(',',$plan_info->open_hrmsrole));
                 $sql .= " AND u.open_hrmsrole IN(\"{$implode_result}\")";
            }
            if(!empty($plan_info->open_designation)){
				$implode_result=implode("\",\"",explode(',',$plan_info->open_designation));
                $sql .= " AND u.open_designation IN(\"{$implode_result}\")";
            }
            if(!empty($plan_info->open_location)){
				$implode_result=implode("\",\"",explode(',',$plan_info->open_location));
                $sql .= " AND u.city IN(\"{$implode_result}\")";
            }
        // OL-1042 Add Target Audience to Classrooms//

	    $users_info = $DB->get_records_sql($sql,$params);

	    return $users_info;
    }

    public function get_enrollable_users_count_to_ilp($planid){
    	global $DB,$USER;
		// if(!is_siteadmin()){
		// 	$siteadmin_sql=" AND u.suspended =0
		// 						 AND u.deleted =0  AND u.open_costcenterid = $USER->open_costcenterid ";
		// }else{
		// 	$siteadmin_sql="";
		// }
  //   	$plan_info = $DB->get_record('local_ilp',array('id' => $planid));

	 //    $sql = "SELECT count(u.id) FROM {user} AS u WHERE u.id > 2 $siteadmin_sql AND u.id not in ($USER->id) ";


		// if($plan_info->department !== null && $plan_info->department !== '-1'&& $plan_info->department !== 0){
		// 		$sql.= ' AND u.open_departmentid IN('.$plan_info->department.')';
		// }
		
  //         // OL-1042 Add Target Audience to Classrooms//
	 //   $params = array();
  //           if(!empty($plan_info->open_group)){
  //               $group_list = $DB->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$plan_info->open_group})");
                 
  //                $groups_members = implode(',', $group_list);
  //                if (!empty($groups_members))
  //                $sql .=" AND u.id IN ({$groups_members})";
  //                else
  //                $sql .=" AND u.id =0";
                 
  //           }                         
  //           if(!empty($plan_info->open_hrmsrole)){
		// 		 $implode_result=implode("\",\"",explode(',',$plan_info->open_hrmsrole));
  //                $sql .= " AND u.open_hrmsrole IN(\"{$implode_result}\")";
  //           }
  //           if(!empty($plan_info->open_designation)){
		// 		$implode_result=implode("\",\"",explode(',',$plan_info->open_designation));
  //               $sql .= " AND u.open_designation IN(\"{$implode_result}\")";
  //           }
  //           if(!empty($plan_info->open_location)){
		// 		$implode_result=implode("\",\"",explode(',',$plan_info->open_location));
  //               $sql .= " AND u.city IN(\"{$implode_result}\")";
  //           }
  //       // OL-1042 Add Target Audience to Classrooms//
	
	 //    $existing_user_sql = " SELECT id,userid FROM {local_ilp_user} WHERE planid=:planid";
	 //    $existing_users = $DB->get_records_sql_menu($existing_user_sql,array('planid' => $planid));
	 //    $existing_userids = implode(',',$existing_users);
	 //    if(!empty($existing_userids)){
	 //    	$sql .= " AND u.id NOT IN ($existing_userids)";
	 //    }
	    $users_info = 0;//$DB->count_records_sql($sql,$params);

	    return $users_info;
    }



    /**
     * for activating or deactivating a ilp.
     * @param  [int] $id [id of the learning plan]
     * @return [bool] true
     */
    function toggleilp($id){
    	$visible = $this->db->get_field('local_ilp', 'visible', array('id' => $id));
    	if($visible){
    		$this->db->execute("UPDATE {local_ilp} SET visible = 0 WHERE id = $id");
    	}else{
    		$this->db->execute("UPDATE {local_ilp} SET visible = 1 WHERE id = $id");
    	}
    	return true;
    }
	
	function delete_ilp($id){
		
		if($id > 0){
			$this->db->delete_records('local_ilp', array('id' => $id));
			$this->db->delete_records('local_ilp_user', array('planid' => $id));
			$this->db->delete_records('local_ilp_courses', array('planid' => $id));
		}
	}
	
	function ilp_courses_list($id){
		global $DB,$USER;
		$systemcontext = context_system::instance();
		
		if(is_siteadmin() /*|| has_capability('local/costcenter:assign_multiple_departments_manage', $systemcontext)*/){
			// $accdcostcenter=$this->db->get_field('local_costcenter','id',array('shortname'=>'ACD'));
			// $costcenters = $DB->get_records('local_costcenter' ,array('parentid' => 0),'','id');
			// $ids = array();
			// foreach($costcenters as $costcenter){
			// 	$ids[] = $costcenter->id;
			// }
			// $costcenterids = implode(',', $ids);
			$costcenterid = $DB->get_field('local_ilp', 'costcenter', array('id' => $id));
			$sql = "SELECT c.id as id, c.fullname FROM {course} as c
					WHERE c.id > 1 AND c.visible = 1 AND CONCAT(',',c.open_identifiedas,',') LIKE '%,4,%' "; //FIND_IN_SET(4,c.open_identifiedas)
			if($costcenterid){		
					$sql.=" AND c.open_costcenterid=$costcenterid";
			}
			$courses = $DB->get_records_sql_menu($sql);
		}else{
			// $costcenter_sql = 'SELECT u.open_costcenterid
			// 						FROM {user} as u
			// 						WHERE u.id='. $USER->id;
									
			// $costcenter = $DB->get_record_sql($costcenter_sql);
			$course_sql = "SELECT c.id as id, c.fullname
							FROM {course} as c
							JOIN {enrol} AS e ON e.courseid=c.id
							JOIN {user_enrolments} as ue on ue.enrolid = e.id
							WHERE c.id > 1 AND c.visible = 1 AND CONCAT(',',c.open_identifiedas,',') LIKE '%,3,%' AND ue.userid={$USER->id}"; //FIND_IN_SET(3,c.open_identifiedas)
	
			$courses = $DB->get_records_sql_menu($course_sql);
		}
		return $courses;
	}
	
	// function ilp_users_list(){
		
	// 	$systemcontext = context_system::instance();
		
	// 	if(is_siteadmin() || has_capability('local/costcenter:assign_multiple_departments_manage', $systemcontext)){
	// 		$sql = 'SELECT u.id, CONCAT(u.firstname," ", u.lastname)
	// 					FROM {users} WHERE id > 2 AND visible = 1';
	// 		$courses = $this->db->get_records_sql_menu($sql);
	// 	}else{
	// 		$costcenter_sql = 'SELECT u.open_costcenterid
	// 							FROM {user} as u
	// 							WHERE u.id='. $this->user->id;
	// 		$costcenter = $this->db->get_record_sql($costcenter_sql);
	// 		$course_sql = 'SELECT c.id as id, c.fullname
	// 								FROM {course} as c
	// 								WHERE c.id > 1 AND c.visible = 1 AND c.open_costcenterid = '.$costcenter->open_costcenterid;
	// 		$courses = $this->db->get_records_sql_menu($course_sql);
	// 	}
	// 	return $courses;
	// }
	
	function assign_courses_to_ilp($data){
		
		$this->db->insert_record('local_ilp_courses', $data);
		return 'courses added to ilp';
	}
   	
	// function update_courses_to_ilp($data){
		
	// 	if($data->id > 0){
	// 		$this->db->update_record('local_ilp_courses', $data);
	// 	}
	// 	return 'courses updated to ilp';
	// }
	
	function delete_courses_to_ilp($data){
		
		
		$get=$this->db->get_records('local_ilp_courses',array('planid'=>$data->planid));
		//print_object($get);exit;
			$this->db->delete_records('local_ilp_courses', array('id' => $data->id, 'planid' => $data->planid, 'courseid' => $data->courseid));
		//}
		$get_coures=$this->db->get_records('local_ilp_courses',array('planid'=>$data->planid));
		$i=0;
		foreach($get_coures as $get){
			
			$data = new stdClass();
			$data->id=$get->id;
			$data->planid = $get->planid;
			$data->courseid = $get->courseid;
			$data->nextsetoperator=$get->nextsetoperator;
			$data->timecreated = time();
			$data->usercreated =  $this->user->id;
			$data->timemodified = 0;
			$data->usermodified = 0;
			$data->sortorder=$i;
            
		
			$this->db->update_record('local_ilp_courses', $data);
			$i++;
		}
		
		
		
		
	}

	function unassign_delete_courses_to_ilps($courseid,$planid){
		$course_record = $this->db->get_record('local_ilp_courses', array('planid' => $planid, 'id' => $courseid));
		if(!empty($course_record)){
			/*If record found then we start for delete the course*/
			$delete_data = new stdClass();
			$delete_data->id = $course_record->id;
			$delete_data->planid = $planid;
			$delete_data->courseid = $course_record->courseid;
			$delete_record = $this->delete_courses_to_ilp($delete_data);
		}
	}
	function unassign_delete_users_to_ilps($userid,$planid){
		//print_object($userid);
		//print_object($planid);
		$user_record = $this->db->get_record('local_ilp_user', array('planid' => $planid, 'userid' => $userid));
		//print_object($user_record);exit;
		if(!empty($user_record)){
			/*If record found then we start for delete the course*/
			$delete_data = new stdClass();
			$delete_data->id = $user_record->id;
			$delete_data->planid = $planid;
			$delete_data->userid = $user_record->userid;
			$delete_record = $this->delete_users_to_ilp($delete_data);
		}
	}
	
	public static function get_ilp_assigned_courses($planid){
		global $DB;
		if($planid){
		$sql = "SELECT c.*,lc.sortorder,lc.id as lepid,lc.nextsetoperator as next
					FROM {local_ilp_courses} lc
					JOIN {course} c ON c.id = lc.courseid
					WHERE lc.planid = ".$planid." ORDER BY lc.sortorder ASC" ;
		$courses = $DB->get_records_sql($sql);
		
		}else{
			$courses=false;
		}
		return $courses;
	}
	
	function assign_users_to_ilp($data){
		global $CFG, $USER;
		$check = $this->db->get_records('local_ilp_user',array('userid'=>$data->userid,'planid'=>$data->planid));
		$type = 'ilp_enrol';
      	$dataobj = $data->planid;
      	$fromuserid = 2;
		if(!$check){
			$user=$this->db->insert_record('local_ilp_user', $data);
			// if(file_exists($CFG->dirroot.'/local/lib.php')){
			// 	require_once($CFG->dirroot.'/local/lib.php');
			// 	$email_logs = emaillogs($type,$dataobj,$data->userid,$fromuserid);
			// }
		}
		
		return $user->id;
	}
	
	
	function delete_users_to_ilp($data){
		
	    if($data->id){
				
			$this->db->delete_records('local_ilp_user', array('id' => $data->id, 'planid' => $data->planid, 'userid' => $data->userid));
		}else{
			
			$id=$this->db->delete_records('local_ilp_user',array('planid' => $data->planid, 'userid' => $data->userid));
			$this->db->delete_records('local_ilp_user', array('id' => $id, 'planid' => $data->planid, 'userid' => $data->userid));
		}

		return 'Users deleted from ilp';
	}
	
	function get_ilp_assigned_users($planid){
		
		
		$sql = "SELECT u.*,lu.completiondate,lu.status,lu.timecreated
					FROM {local_ilp_user} lu
					JOIN {user} u ON u.id = lu.userid
					WHERE lu.planid = ".$planid." and deleted!=1";
		$users = $this->db->get_records_sql($sql);
		
		return $users;
	}
	function notification_for_user_enrol($users,$data){
		
		
		 $type="ilp_enrol";
         $get_ilt=$this->db->get_record('local_notification_type',array('shortname'=>$type));
         
		 //if(count($users)>20){
               //$to_userids=array_chunk($users, 20);
			  // print_object($to_userids);exit;
		       foreach($users as $to_userid){
                    $users=implode(',',$to_userid);
				   	$from = $this->db->get_record('user', array('id'=> $this->user->id));
					$data_infor=$this->db->get_record('local_ilp',array('id'=>$data->planid));
					if($data_infor->learning_type==1){
						$type='core courses';
					}else{
						$type='elective courses';
					}
					//$coursename=$this->db->get_records_menu('local_ilp_courses',array('planid'=>$data->planid),'id','id,courseid');
					//$course=implode(',',$coursename);
					//$sql="select id,fullname from {course} where id IN ($course)";
					//$coursename=$this->db->get_records_sql_menu($sql);
					//$course_names=implode(',',$coursename);
					$coursename=$this->db->get_records_menu('local_ilp_courses',array('planid'=>$data->planid),'id','id,courseid');
					if($coursename){
						$course= implode(',',$coursename);
						$sql="select id,fullname from {course} where id IN ($course)";
						$coursename=$this->db->get_records_sql_menu($sql);
						$course_names=array();
						foreach($coursename as $course){
							$course_names[]="<span>$course</span><br/>";
						}
						$course_names1=implode('',$course_names);
					}else{
						$course_names1 = 'Not Assigned';
					}
					$department=$this->db->get_field('local_costcenter','fullname',array('id'=>$data_infor->costcenter));
					 if($department==''){
                    $department="[ilt_department]";
                    }
					$sql="SELECT id, concat(firstname,' ', lastname) as fullname  from {user} where id=$data_infor->usercreated";   
					$creator=$this->db->get_record_sql($sql);
					
					// $data_details=$this->db->get_record('local_coursedetails',array('courseid'=>$single->id));
					//$department=$this->db->get_field('local_costcenter','fullname',array('id'=>$data_details->costcenterid));
					$dataobj= new stdClass();
					$dataobj->lep_name=$data_infor->name;
					$dataobj->lep_course=$course_names1;
					$dataobj->course_code=$data_infor->shortname;
					$dataobj->lep_startdate=date('d M Y',$data_infor->startdate);
					$dataobj->lep_enddate=date('d M Y',$data_infor->enddate);
					$dataobj->lep_creator=$creator->fullname;
					//$dataobj->lep_department=$department;
					$dataobj->lep_type=$type;
					$dataobj->lep_enroluser_username="[lep_enroluser_username]";
					$dataobj->lep_enroluseremail="[lep_enroluseremail]";
					$url = new moodle_url($CFG->wwwroot.'/local/ilp/view.php',array('id'=>$data->planid,'couid'=>$data->planid));
                    $dataobj->lep_link = html_writer::link($url, $data_infor->name, array());
					$touserid=$to_userid;
					$fromuserid=2;
					$notifications_lib = new notifications();
					$emailtype='ilp_enrol';
					$planid=$data->planid;			
					$notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,$batchid=0,$planid);
				 }
			  return true;
			// }/*else{
				
//				foreach($users as $user){
//				
//					$users=$user;
//						
//					$from = $this->db->get_record('user', array('id'=> $this->user->id));
//					$data_infor=$this->db->get_record('local_ilp',array('id'=>$data->planid));
//					
//					$coursename=$this->db->get_records_menu('local_ilp_courses',array('planid'=>$data->planid),'id','id,courseid');
//					//$course=implode(',',$coursename);
//					//
//					//$sql="select id,fullname from {course} where id IN ($course)";
//					//$coursename=$this->db->get_records_sql_menu($sql);
//					//$course_names=implode(',',$coursename);
//					if($coursename){
//						$course=implode(',',$coursename);
//						$sql="select id,fullname from {course} where id IN ($course)";
//						$coursename=$this->db->get_records_sql_menu($sql);
//						$course_names=implode(',',$coursename);
//					}else{
//						$course_names = "Not Assigned";
//					}
//					$data_details=$this->db->get_record('local_coursedetails',array('courseid'=>$single->id));
//					//$department=$this->db->get_field('local_costcenter','fullname',array('id'=>$data_details->costcenterid));
//					$department=$this->db->get_field('local_costcenter','fullname',array('id'=>$data_infor->costcenter));
//					 if($department==''){
//                    $department="[ilt_department]";
//                    }
//					$sql="select id, concat(firstname,' ', lastname) as fullname  from {user} where id=$data_infor->usercreated";   
//					$creator=$this->db->get_record_sql($sql);
//					if($data_infor->learning_type==1){
//						$type='core courses';
//					}else{
//						$type='elective courses';
//					}
//				    
//					$dataobj= new stdClass();
//					$dataobj->lep_name=$data_infor->name;
//					$dataobj->lep_course=$course_names;
//					$dataobj->course_code=$data_infor->shortname;
//					$dataobj->lep_startdate=date('d M Y',$data_infor->startdate);
//					$dataobj->lep_enddate=date('d M Y',$data_infor->enddate);
//					$dataobj->lep_creator=$creator->fullname;
//					$dataobj->lep_department=$department;
//					$dataobj->lep_enroluser_username="[lep_enroluser_username]";
//					$dataobj->lep_enroluseremail="[lep_enroluseremail]";
//					
//					$dataobj->lep_type=$type;
//					$touserid=$users;
//					$fromuserid=2;
//					
//					$notifications_lib = new notifications();
//						
//					$emailtype='ilp_enrol';
//						
//					$notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid);*/
				
				
				
			 
		//print_object($users);exit;
	}
	function get_previous_course_status($planid, $sortorder,$courseid){
		

		//$sql = "SELECT * FROM {local_ilp_courses} lc WHERE lc.planid=".$planid." AND lc.sortorder < $sortorder ";
		//
		//$records = $this->db->get_records_sql($sql);
		//	
		//if(!empty($records)){
		//
		//     
		//	foreach($records as $rec){
		//			
		//		if($rec->sortorder>1){
		//			$recorder=$rec->sortorder - 1;
		//			
		//			$sql= "SELECT * FROM {local_ilp_courses} WHERE sortorder < {$recorder} and nextsetoperator='and' order by sortorder desc limit 0,1 ";
		//					   
		//			$previous_record = $this->db->get_record_sql($sql);
		//			
		//			if($previous_record) {
		//			 $coursecompleted=$this->get_completed_lep_users($previous_record->courseid,$planid);
		//			 if(empty($coursecompleted))
		//				 return $previous_record;
		//			 else 
		//				return false;
		//			}
		//			//print_object($next_record);
		//		 
		//		}
		//	}
		//}
		$sql = "SELECT GROUP_CONCAT(lc.courseid) as courseids FROM {local_ilp_courses} as lc WHERE lc.planid = $planid and lc.nextsetoperator='and'";
		
		$record = $this->db->get_record_sql($sql);
		if($record->courseids){
			$records=explode(',',$record->courseids);
			$array_search=array_search($courseid,$records);
			if($array_search){
					$coursecompleted=$this->get_completed_lep_users($records[$array_search-1],$planid);
						 if($coursecompleted){
							 return true;
						 }
						 else{ 
							return false;
						}
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
	function get_completed_lep_users($courseid,$planid){
    
	//print_object($courseid);
	//print_object( $this->user->id);
	$sql="SELECT * from {course_completions} where course=$courseid and userid= ".$this->user->id." and timecompleted is not NULL";
	//echo $sql;
	$get_course=$this->db->get_record_sql($sql);
    
	return $get_course;
	
	
	}
	public function check_courses_assigned_target_audience($user,$planid){
	
		   $users=$this->db->get_record('user',array('id'=> $this->user->id));
			 $us=$users->open_band;
			 $array=explode(',',$us);
			 $list=implode("','",$array);
			//print_object($users);
			/*********changed IN to Find_in_set in query for issues 1258********/
			$sql='SELECT ud.* FROM {local_ilp} AS ud WHERE
			ud.id='.$planid.' AND (case when ud.costcenter IS NOT NULL then CONCAT(\',\',ud.costcenter,\',\') LIKE CONCAT(\'%,\','.$users->open_costcenterid.',\',%\') else ud.costcenter is NULL END)
			AND (case when ud.department IS NOT NULL THEN CONCAT(\',\',ud.department,\',\') LIKE CONCAT(\'%,\','.$users->open_departmentid.',\',%\') else ud.department is NULL END)';
			// FIND_IN_SET('.$users->open_costcenterid.',ud.costcenter)
			// FIND_IN_SET('.$users->open_departmentid.',ud.department)
			//if($users->costcenterid){
			//$sql .=' ud.costcenter IN ('.$users->costcenterid.')  ';
			//}else{
			//$sql .=' ud.costcenter!=""  ';
			//}
		
		//if($users->department!=''){
		//	$sql .='ud.department IN ('.$users->department.') AND ';
		//}else{
		//	$sql.='ud.department!="" AND ' ;
		//}
		//if($users->subdepartment!=''){
		//	$sql .='ud.subdepartment IN ('.$users->subdepartment.') AND ';
		//}else{
		//	$sql.='ud.subdepartment!="" AND ';
		//}
		//if($users->sub_sub_department!=''){
		//	$sql .='ud.subsubdepartment IN('.$users->sub_sub_department.') AND ';
		//}else{
		//	$sql.='ud.subsubdepartment!="" AND ';
		//}
		//if($users->band!=''){
		//	
		//	$sql .="ud.band IN('$list')";
		//}else{
		//	$sql .='ud.band!=""  ';
		//}
			//FIND_IN_SET('.$data->costcenterid.',l.costcenter) AND
			//FIND_IN_SET("'.$data->band.'",l.band) AND
			//FIND_IN_SET('.$data->department.',l.department) AND
			//FIND_IN_SET('.$data->sub_sub_department.',l.subsubdepartment) AND
			//FIND_IN_SET('.$data->subdepartment.',l.subdepartment )
			//AND l.id > 0 ORDER BY l.timemodified DESC';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
			//echo $sql;
			$ilps = $this->db->get_records_sql($sql);
			
			if($ilps){
				return true;
			}else{
				return false;
			}
	}
	public function to_enrol_users_check_completion($planid,$users){
		
		//    $planid1=$this->db->get_record('local_ilp_users',array('user'=> $this->user->id));
		//	print_object($planid1);exit;
			$sql="SELECT llc.*,cc.* FROM {local_ilp_courses} AS llc
					JOIN {course_completions} AS cc ON 	cc.course=llc.courseid
					WHERE llc.planid=".$planid." and cc.userid=$users and cc.timecompleted!='NULL' order by llc.id desc limit 1";
			//print_object($planid);
			//print_object($planid);
			$check=$this->db->get_record_sql($sql);
			
			if($check){
						$sort=$check->sortorder+1;	
						$sql="SELECT * from {local_ilp_courses} where planid=".$planid." and sortorder =$sort";
						$record=$this->db->get_record_sql($sql);
						
						if($record){
							
								$enrol_manual = enrol_get_plugin('ilp');
								$sql="SELECT * from {enrol} where courseid=".$record->courseid." and enrol='ilp'";
								
								
								$instance=$this->db->get_record_sql($sql);
							if($instance){ 
								$roleid=$instance->roleid;
								$timestart=0;
								$timeend=0;
								$enrol_manual->enrol_user($instance, $users, $roleid, $timestart, $timeend);
								}
						}
						
						}else{
			
							}
	}

	public static function ilpCompletionProgress($planid, $user){
		global $DB;
		// print_object($planid);
		$lplanassignedcourses = self::get_ilp_assigned_courses($planid);
		$assignedcoursescount = count($lplanassignedcourses);
		// print_object($assignedcoursescount);
		// $optcoursessql = "SELECT cc.id, llu.userid, c.id as courseid from {course_completions} as cc 
		// 	JOIN {course} AS c ON c.id = cc.course 
		// 	JOIN {local_ilp_user} as llu on cc.userid=llu.userid 
		// 	JOIN {local_ilp_courses} as llc ON llc.courseid=c.id WHERE llc.planid=$planid and llc.nextsetoperator='OR' and cc.userid=$user AND cc.timecompleted IS NOT NULL GROUP BY cc.id";
		// $optcourses_completed = $DB->get_records_sql($optcoursessql);
		// $optcourses_completed_count = (count($optcourses_completed));
		$manadtorysql = "SELECT count(id) 
						FROM {local_ilp_courses} 
						WHERE nextsetoperator='AND' AND planid = $planid";

		$manadtorycount = $DB->count_records_sql($manadtorysql);
		
		$mancoursessql = "SELECT cc.course
						FROM {course_completions} AS cc 
						JOIN {course} AS c ON c.id = cc.course 
						JOIN {local_ilp_user} AS llu ON cc.userid = llu.userid 
						JOIN {local_ilp_courses} AS llc ON llc.courseid = c.id 
						WHERE llc.planid=$planid AND llc.nextsetoperator = 'AND' 
						AND cc.userid = $user AND cc.timecompleted IS NOT NULL 
						GROUP BY cc.course ";

		$madcourses_completed = $DB->get_records_sql($mancoursessql);
		$madcourses_completed_count = (count($madcourses_completed));

		if($madcourses_completed_count>0){
			// echo "rizwana";
			$ilp_completion_percent = floor($madcourses_completed_count/($manadtorycount)*100);
			// $lpcount = $madcourses_completed_count;
			// print_object($lpcount);
		}else{
			// echo "rizwana1";
			$optcoursessql = "SELECT cc.course
							FROM {course_completions} as cc 
							JOIN {course} AS c ON c.id = cc.course 
							JOIN {local_ilp_user} as llu on cc.userid=llu.userid 
							JOIN {local_ilp_courses} as llc ON llc.courseid=c.id 
							WHERE llc.planid=$planid and llc.nextsetoperator='OR' 
							and cc.userid=$user AND cc.timecompleted IS NOT NULL GROUP BY cc.course";

			$optcourses_completed = $DB->get_records_sql($optcoursessql);
			$optcourses_completed_count = (count($optcourses_completed));
			if ($optcourses_completed_count>0){
				$ilp_completion_percent = 100;
			}else{
				$ilp_completion_percent = 0;
			}
		}
		return $ilp_completion_percent;
	}

	public function complete_the_lep($planid,$user){
			global $DB;
			if($planid){
			$sql="SELECT llc.courseid as id, llc.courseid from {local_ilp_courses} as llc join {local_ilp_user} as llu
			on llc.planid=llu.planid where llc.planid=$planid and llc.nextsetoperator='and' and llu.userid=$user ";
			$courses = $DB->get_records_sql_menu($sql);

			$check = array();
			$completed = array();
			$optional_completed = array();
			if($courses){
				foreach($courses as $course){
					$sql = "SELECT id from {course_completions} where course=".$course." and userid= $user and timecompleted!='NULL'";
					//echo $sql;
					$check = $DB->get_record_sql($sql);
					//print_object($check);
					if($check){
						$completed[]=1;
					}else{
						$completed[]=0;
					}				
				}
			}else{
				$sql="SELECT llc.courseid as id, llc.courseid from {local_ilp_courses} as llc join {local_ilp_user} as llu
				on llc.planid=llu.planid where llc.planid=$planid  and  llu.userid=$user ";
				$courses=$DB->get_records_sql_menu($sql);
				foreach($courses as $course){
					$sql="SELECT id from {course_completions} where course=".$course." and userid= $user and timecompleted!='NULL'";
					$check=$DB->get_record_sql($sql);
					if($check){
						$optional_completed[]=1;
					}else{
						$optional_completed[]=0;
					}		
				}
			}
		if($completed){
			if (in_array("0", $completed)){
			
			}else{
			
				$date=time();
				$sql="SELECT * from {local_ilp_user} where planid=$planid and userid=$user";
				$id=$DB->get_record_sql($sql);
			
				if($id){
					$condition=$DB->get_field('local_ilp_user','id',array('id'=>$id->id,'status'=>1));
					if(empty($condition)){
						$sql="UPDATE {local_ilp_user} SET completiondate='$date' where id=".$id->id."";
						$data=$DB->execute($sql);
					
						$sql_1="UPDATE {local_ilp_user} SET status='1' where id=".$id->id."";
						$data_1=$DB->execute($sql_1); 
					
						$emailtype="ilp_completion";
						$status="Completed";
						
						$this->to_send_request_notification($id,$emailtype,$status,$planid);
					}
				}
			}
		}

		  if($optional_completed){
				if (in_array("1", $optional_completed)){
					$date=time();
					$sql="SELECT * from {local_ilp_user} where planid=$planid and userid=$user";
					$id=$DB->get_record_sql($sql);
					if($id){
						$condition=$DB->get_field('local_ilp_user','id',array('id'=>$id->id,'status'=>1));
						if(empty($condition)){
							$sql="UPDATE {local_ilp_user} SET completiondate='$date' where id=".$id->id."";
							$data=$DB->execute($sql);
							$sql_1="UPDATE {local_ilp_user} SET status='1' where id=".$id->id."";
							$data_1=$DB->execute($sql_1); 
							$emailtype="ilp_completion";
							$status="Completed";
							
							$this->to_send_request_notification($id,$emailtype,$status,$planid);
						}
					}
				}else{

				}
			}
			
		}
	}
public function to_enrol_users($planid,$userid,$course_enrol){
	
	
	
	    $sql="SELECT * from {local_ilp_courses} where planid=$planid and courseid=$course_enrol";
		$record=$this->db->get_record_sql($sql);
					
		foreach($record as $single){
			
			$enrol_manual = enrol_get_plugin('ilp');
			$sql="SELECT * from {enrol} where courseid=".$course_enrol." and enrol='ilp'";
			$instance=$this->db->get_record_sql($sql);
			if($instance){		
			$roleid=$instance->roleid;
			$timestart=$this->db->get_field('course','startdate',array('id'=>$course_enrol));
			$timeend=0;
			$enrol_manual->enrol_user($instance, $userid, $roleid, $timestart, $timeend);
			}else{
			 echo "Please contact the admin and enrol the course";	
			}
		}
		//return true;
		$plan_url = new \moodle_url('/course/view.php', array('id' => $course_enrol));
        redirect($plan_url);	
}
public function to_send_request_notification($data,$emailtype,$status,$planid){
	 global $DB, $CFG;
	
					
				   	$from = "2";
					$data_infor=$this->db->get_record('local_ilp',array('id'=>$data->planid));
					$completion_date=$this->db->get_field('local_ilp_user','completiondate',array('userid'=>$data->userid,'planid'=>$data->planid));
					
					$coursename=$this->db->get_records_menu('local_ilp_courses',array('planid'=>$data->planid),'id','id,courseid');
					if($coursename){
						$course= implode(',',$coursename);
						$sql="SELECT id,fullname from {course} where id IN ($course)";
						$coursename=$this->db->get_records_sql_menu($sql);
						$course_names=array();
						foreach($coursename as $course){
						$course_names[]="<span>$course</span><br/>";
						}
						$course_names1=implode('',$course_names);
					}else{
						$course_names1="course still not assigned";
					}
					if($data_infor->learning_type==1){
						$type='core courses';
					}else{
						$type='elective courses';
					}
					$department=$this->db->get_field('local_costcenter','fullname',array('id'=>$data_infor->costcenter));
					 if($department==''){
                    //$department="[ilt_department]";
                    }
					$sql="SELECT id, concat(firstname,' ', lastname) as fullname  from {user} where id=$data_infor->usercreated";   
					$creator=$this->db->get_record_sql($sql);
                   
					
					$dataobj= new stdClass();
					$dataobj->lep_name=$data_infor->name;
					$dataobj->lep_course=$course_names1;
					$dataobj->course_code=$data_infor->shortname;
					$dataobj->lep_startdate=date('d M Y',$data_infor->startdate);
					$dataobj->lep_enddate=date('d M Y',$data_infor->enddate);
					
					$dataobj->lep_enroluser_username=$this->db->get_field('user','username',array('id'=>$data->userid));
					$dataobj->lep_enroluseremail=$this->db->get_field('user','email',array('id'=>$data->userid));
					$dataobj->lep_status=$status;
					//$dataobj->lep_department=$department;
					$dataobj->lep_creator=$creator->fullname;
					//print_object($emailtype);
					if($emailtype=='ilp_enrol' || $emailtype=='lep_nomination' || $emailtype=='ilp_completion' || $emailtype=='lep_approvaled'){
					$url = new moodle_url($CFG->wwwroot.'/local/ilp/view.php',array('id'=>$data->planid,'couid'=>$data->planid));
                    $dataobj->lep_link = html_writer::link($url, $data_infor->name, array());
					//print_object($dataobj->course_link);exit;
					}

			
					$dataobj->lep_completiondate=date('d M Y',$completion_date);
					$dataobj->lep_type=$type;
					$touserid=$data->userid;
					$fromuserid=2;
					$notifications_lib = new \notifications();
					$emailtype=$emailtype;
					//print_object($dataobj);exit;
					$planid=$data->planid;
					$notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,$batchid=0,$planid);
}


	
	/**
     * Returns url/path of the ilp summaryfile if exists, else false
     *
	 * @param int $lpanid, local_ilp id
     */
	function get_ilpsummaryfile($lpanid){
		global $CFG, $DB;
       
		
		$imgurl = false;
		
        $fileitemid = $DB->get_field('local_ilp', 'summaryfile', array('id'=>$lpanid));
		
		if(!empty($fileitemid)){
 			$sql = "SELECT * FROM {files} WHERE itemid = $fileitemid AND filename != '.' ORDER BY id DESC ";//LIMIT 1
			$filerecord = $DB->get_record_sql($sql);
		}	
			if($filerecord!=''){
				
			$imgurl = $CFG->wwwroot."/pluginfile.php/" . $filerecord->contextid . '/' . $filerecord->component . '/' .$filerecord->filearea .'/'.$filerecord->itemid. $filerecord->filepath. $filerecord->filename;
			}
		//}else{
			if(empty($imgurl)){
			
			$dir = $CFG->wwwroot.'/local/costcenter/pix/course_images/image3.jpg';
			for($i=1; $i<=10; $i++) {
				$image_name = $dir;
				$imgurl = $image_name;
				break;
			}

		//}
		}
		
		return $imgurl;
	}
	
	/**
     * Returns function for get learnigplan courses count
     *
	 * @param int $planid, local_ilp id
	 * @param text $mandatory optional, and/or
     */
	function ilpcourses_count($planid, $mandatory = null){
	
		global $DB;
		$sql = "SELECT COUNT(lc.id)
					FROM {local_ilp_courses} lc
					JOIN {course} c ON c.id = lc.courseid
					WHERE lc.planid = ".$planid." " ;
					
		if($mandatory == 'and'){
			$sql .= "AND lc.nextsetoperator = 'and' ";
		}elseif($mandatory == 'or'){
			$sql .= "AND lc.nextsetoperator = 'or' ";
		}
		
		$coursescount = $DB->count_records_sql($sql);
		
		return $coursescount;
	}
	public function modal_lpcourse_enrol($new_plan_courses,$planid){
		global $USER;
		$existing_plan_courses_record = $this->db->get_records('local_ilp_courses', array('planid'=> $planid));
	    $existing_plan_timecreated = $this->db->get_record('local_ilp_courses', array('planid'=> $planid));
	    
	   // $return_url = new moodle_url('/local/ilp/plan_view.php', array('id' => $planid, 'tab' => 'courses')); 
	   if(!empty($new_plan_courses)){          
	      	foreach($new_plan_courses as $plan_course){
		        $i=0;
		        $data = new stdClass();
		        $data->planid = $planid;
		        $data->courseid = $plan_course;
		        $data->nextsetoperator='or';
		        $data->timecreated = time();
		        $data->usercreated = $USER->id;
		        $data->timemodified = 0;
		        $data->usermodified = 0;
		         /**Check The sort order max and insert next value**/
		        $sql="select  MAX(sortorder) as sort from {local_ilp_courses} where planid=$planid";
		        $last_order=$this->db->get_record_sql($sql);
		                
		        if($last_order->sort>=0 && $last_order->sort!=''){/**Condition to check sort order and increment the sort value**/              
		            $i=$last_order->sort+1;
		            $data->sortorder=$i;
		        }else{       
		            $data->sortorder=$i;
		            $i++;     
		        }/**end of the conditions By Ravi_369**/       
		        $create_record = $this->assign_courses_to_ilp($data);
	      	}
		}
	}
	/**
	 * function to get the count to ilps of a specific user 
	 * @param  [INT] $userid [user id for whom the count of learning plan is required]
	 * @return [INT]         [count of user enrolled ilp]
	 */
	public function enrol_get_users_ilp_count($userid){
		global $DB;
		$ilp_sql = "SELECT count(id) FROM {local_ilp_user} WHERE userid = :userid";
		$ilp_count = $DB->count_records_sql($ilp_sql, array('userid' => $userid));
		return $ilp_count;
	}
	public function enrol_get_users_ilps($userid){
		global $DB;
		$ilp_sql = "SELECT lp.id,lp.name,lp.description FROM {local_ilp} AS lp
							JOIN {local_ilp_user} AS lpu ON lp.id = lpu.planid
							WHERE lpu.userid = :userid";
		$ilps = $DB->get_records_sql($ilp_sql, array('userid' => $userid));
		return $ilps;

	}
}
?>
