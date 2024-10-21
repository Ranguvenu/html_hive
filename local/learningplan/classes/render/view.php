<?php
namespace local_learningplan\render;
use local_learningplan\lib\lib as lib;
use stdClass;
use context_system;
use html_writer;
use html_table;
use moodle_url;

use plugin_renderer_base;
use user_course_details;
use local_percipiosync\plugin;
//use local_coursera\plugin as couseraplugin;
use local_udemysync\udemy_user_verification as udemy_user_verification;
//use open;

require_once($CFG->dirroot . '/local/courses/lib.php');
if(file_exists($CFG->dirroot . '/local/includes.php')){
	require_once($CFG->dirroot . '/local/includes.php');
}
class view extends plugin_renderer_base {
    private $lid;
    function __construct(){
        global $DB, $CFG, $OUTPUT, $USER, $PAGE;
        $this->db=$DB;
		$this->context = context_system::instance();
		$this->output=$OUTPUT;
		$this->page=$PAGE;
		$this->cfg=$CFG;
		$this->user=$USER;
    }
    
    public function all_learningplans($condtion,$dataobj,$tableenable=false,$search=null,$filterdata = null){

        $systemcontext = $this->context;
		if(($tableenable)){
			$start=$dataobj->start;
			$length=$dataobj->length;
		}
		
	if(is_siteadmin()){
		$sql="SELECT l.* 
				FROM {local_learningplan} AS l 
				WHERE 1 = 1 ";
		if(!empty($search)){
			$sql .= " AND name LIKE '%%$search%%'";
		}

		if($filterdata){
			if(isset($filterdata->departments) && !empty($filterdata->departments)){
				$selecteddepts = implode(',', $filterdata->departments);
				$sql .= " AND CONCAT(',',l.department,',') LIKE CONCAT('%,',$selecteddepts,',%') ";
			}

			// if(isset($filterdata->open_level) && !empty($filterdata->open_level)){
			// $selectedlevels = implode(',', $filterdata->open_level);
			// 	$sql .= " AND CONCAT(',',l.level,',') LIKE CONCAT('%,',$selectedlevels,',%') ";
			// }
			if(isset($filterdata->groups) && !empty($filterdata->groups)){
				$selectedgroups = implode(',', $filterdata->groups);
				$sql .= " AND CONCAT(',',l.open_group,',') LIKE CONCAT('%,',$selectedgroups,',%') ";
			}
		}

		$sql .= " ORDER BY l.id DESC ";
		if(($tableenable)){
			$learning_plans = $this->db->get_records_sql($sql, array(), $start,$length);
		}else{
			$learning_plans = $this->db->get_records_sql($sql);
		}
	
		$assign_users_sql = "SELECT id 
								FROM {local_learningplan} l
								WHERE 1 = 1 ";
		if(!empty($search)){
			$assign_users_sql .= " and l.name LIKE '%%$search%%'";
		}

		if($filterdata){
			if(isset($filterdata->departments) && !empty($filterdata->departments)){
				$selecteddepts = implode(',', $filterdata->departments);
				$assign_users_sql .= " AND CONCAT(',',l.department,',') LIKE CONCAT('%,',$selecteddepts,',%') ";
			}

			// if(isset($filterdata->open_level) && !empty($filterdata->open_level)){
			// 	list($levelsql, $levelparams) = $DB->get_in_or_equal($filterdata->open_level);
			// 	$assign_users_sql .= " l.department $levelsql ";
			// }
			if(isset($filterdata->groups) && !empty($filterdata->groups)){
				$selectedgroups = implode(',', $filterdata->groups);
				$assign_users_sql .= " AND CONCAT(',',l.open_group,',') LIKE CONCAT('%,',$selectedgroups,',%') ";
			}
		}
		// echo $assign_users_sql;

		$assigned_users = $this->db->get_records_sql($assign_users_sql);

	}elseif(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
			$data=open::userdetails();
			$sql="SELECT l.* FROM {local_learningplan} AS l 
				WHERE concat(',',l.costcenter,',') LIKE concat('%,',{$this->user->open_costcenterid},',%')";//FIND_IN_SET(".$this->user->open_costcenterid.",l.costcenter)
			if(!empty($search)){
				$sql .= " AND name LIKE '%%$search%%'";
			}

			if($filterdata){
				if(isset($filterdata->departments) && !empty($filterdata->departments)){
					$selecteddepts = implode(',', $filterdata->departments);
					$sql .= " AND CONCAT(',',l.department,',') LIKE CONCAT('%,',$selecteddepts,',%') ";
				}
				// if(isset($filterdata->open_level) && !empty($filterdata->open_level)){
				// $selectedlevels = implode(',', $filterdata->open_level);
				// 	$sql .= " AND CONCAT(',',l.level,',') LIKE CONCAT('%,',$selectedlevels,',%') ";
				// }
				if(isset($filterdata->groups) && !empty($filterdata->groups)){
					$selectedgroups = implode(',', $filterdata->groups);
					$sql .= " AND CONCAT(',',l.open_group,',') LIKE CONCAT('%,',$selectedgroups,',%') ";
				}
			}

			$sql .= " ORDER BY l.id DESC";
			if(($tableenable)){
				// $sql .= " LIMIT $start,$length";
				$learning_plans_depwise = $this->db->get_records_sql($sql, array(), $start,$length);
			}else{
				$learning_plans_depwise = $this->db->get_records_sql($sql);
			}
			$assigned_users_sql = "SELECT l.* 
									FROM {local_learningplan} AS l 
									WHERE concat(',',l.costcenter,',') LIKE concat('%,',{$this->user->open_costcenterid},',%') ";
			if(!empty($search)){
				$assigned_users_sql .= " AND name LIKE '%%$search%%'";
			}

			if($filterdata){
				if(isset($filterdata->departments) && !empty($filterdata->departments)){
					$selecteddepts = implode(',', $filterdata->departments);
					$sql .= " AND CONCAT(',',l.department,',') LIKE CONCAT('%,',$selecteddepts,',%') ";
				}
				// if(isset($filterdata->open_level) && !empty($filterdata->open_level)){
				// $selectedlevels = implode(',', $filterdata->open_level);
				// 	$sql .= " AND CONCAT(',',l.level,',') LIKE CONCAT('%,',$selectedlevels,',%') ";
				// }
				if(isset($filterdata->groups) && !empty($filterdata->groups)){
					$selectedgroups = implode(',', $filterdata->groups);
					$sql .= " AND CONCAT(',',l.open_group,',') LIKE CONCAT('%,',$selectedgroups,',%') ";
				}
			}

			$assigned_users_sql .="ORDER BY l.id DESC";
			$assigned_users = $this->db->get_records_sql($assigned_users_sql);
			$learning_plans=$learning_plans_depwise;
		}elseif(has_capability('local/costcenter:manage_owndepartments',$systemcontext) ){
			$sql="SELECT l.* 
					FROM {local_learningplan} AS l 
					WHERE concat(',',l.costcenter,',') LIKE concat('%,',{$this->user->open_costcenterid},',%') 
					AND CONCAT(',',l.department,',') LIKE CONCAT('%,',{$this->user->open_departmentid},',%') ";
			if(!empty($search)){
				$sql .= " AND name LIKE '%%$search%%' ";
			}

			if($filterdata){
				if(isset($filterdata->departments) && !empty($filterdata->departments)){
					$selecteddepts = implode(',', $filterdata->departments);
					$sql .= " AND CONCAT(',',l.department,',') LIKE CONCAT('%,',$selecteddepts,',%') ";
				}
				// if(isset($filterdata->open_level) && !empty($filterdata->open_level)){
				// $selectedlevels = implode(',', $filterdata->open_level);
				// 	$sql .= " AND CONCAT(',',l.level,',') LIKE CONCAT('%,',$selectedlevels,',%') ";
				// }
				if(isset($filterdata->groups) && !empty($filterdata->groups)){
					$selectedgroups = implode(',', $filterdata->groups);
					$sql .= " AND CONCAT(',',l.open_group,',') LIKE CONCAT('%,',$selectedgroups,',%') ";
				}
			}

			$sql .= "  ORDER BY l.id DESC";
			if(($tableenable)){
				// $sql .= " LIMIT $start,$length";
				$learning_plans_depwise = $this->db->get_records_sql($sql, array(), $start,$length);
			}else{
				$learning_plans_depwise = $this->db->get_records_sql($sql);
			}
			$assigned_users_sql = "SELECT l.* 
								FROM {local_learningplan} AS l
								WHERE l.costcenter={$this->user->open_costcenterid} AND
								 (CONCAT(',',l.department,',') LIKE CONCAT('%,',{$this->user->open_departmentid},',%')) ";
			if(!empty($search)){
				$assigned_users_sql .= " AND name LIKE '%%$search%%'";
			}

			if($filterdata){
				if(isset($filterdata->departments) && !empty($filterdata->departments)){
					$selecteddepts = implode(',', $filterdata->departments);
					$sql .= " AND CONCAT(',',l.department,',') LIKE CONCAT('%,',$selecteddepts,',%') ";
				}
				// if(isset($filterdata->open_level) && !empty($filterdata->open_level)){
				// $selectedlevels = implode(',', $filterdata->open_level);
				// 	$sql .= " AND CONCAT(',',l.level,',') LIKE CONCAT('%,',$selectedlevels,',%') ";
				// }
				if(isset($filterdata->groups) && !empty($filterdata->groups)){
					$selectedgroups = implode(',', $filterdata->groups);
					$sql .= " AND CONCAT(',',l.open_group,',') LIKE CONCAT('%,',$selectedgroups,',%') ";
				}
			}

			$assigned_users_sql .= " ORDER BY l.id DESC ";
			$assigned_users = $this->db->get_records_sql($assigned_users_sql);
			$learning_plans=$learning_plans_depwise;
		}else{
			$data=open::userdetails();
			
			$sql="SELECT * 
					FROM {local_learningplan} AS l 
					WHERE 
					CONCAT(',',l.costcenter,',') LIKE CONCAT('%,',$data->open_costcenterid,',%')
					CONCAT(',',l.open_group,',') LIKE CONCAT('%,',$data->open_group,',%')
					CONCAT(',',l.department,',') LIKE CONCAT('%,',$data->open_departmentid,',%')
					CONCAT(',',l.subdepartment,',') LIKE CONCAT('%,',$data->open_subdepartment,',%')
					AND l.id > 0 " ;
			// FIND_IN_SET('.$data->open_costcenterid.',l.costcenter) AND
			// FIND_IN_SET("'.$data->open_group.'",l.open_group) AND
			// FIND_IN_SET('.$data->open_departmentid.',l.department) AND
			// FIND_IN_SET('.$data->open_subdepartment.',l.subdepartment )
			if(!empty($search)){
				$sql .= " AND name LIKE '%%$search%%'";
			}
			$sql .= ' AND l.visible=1 ORDER BY l.timemodified DESC';
			if(($tableenable)){
				// $sql .= " LIMIT $start,$length";
				$limitstart = $start;
				$limitend = $length;
			}else{
				$limitstart = 0;
				$limitend = 0;
			}
			$learning_plans_depwise = $this->db->get_records_sql($sql, array(), $limitstart, $limitend);
			/* if(!empty($search)){
				$assigned_users_sql .= " AND name LIKE '%%$search%%'";
			}
			$assigned_users_sql .= ' ORDER BY l.timemodified DESC'; */
			$learning_plans=$learning_plans_depwise;
		}
        if(empty($learning_plans)){
        	if($tableenable){
	        	return $output = array(
	                "sEcho" => intval($requestData['sEcho']),
	                "iTotalRecords" => 0,
	                "iTotalDisplayRecords" => 0,
	                "aaData" => array()
	                );
	        }else{
           		return html_writer::tag('div', get_string('nolearningplans', 'local_learningplan'), array('class' => 'alert alert-info text-center pull-left mt-15', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
	        }
        }else{
            $sdata = array();
            $table_data = array();
			
            foreach($learning_plans as $learning_plan){
                $row = array();
                
                $plan_url = new \moodle_url('/local/learningplan/plan_view.php', array('id' => $learning_plan->id));
                if(empty($learning_plan->open_points)){
                    $plan_credits = 'N/A';
                }else{
                    $plan_credits = $learning_plan->open_points;
                }
				if(empty($learning_plan->open_grade)){
                    $plan_grade = 'N/A';
                }else{
                    $plan_grade = $learning_plan->open_grade;
                }
				if(empty($learning_plan->usercreated)){
					$plan_usercreated = 'N/A';
				}else{
					$plan_usercreated = $learning_plan->usercreated;
					$user = $this->db->get_record_sql("SELECT id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename FROM {user} WHERE id = :plan_usercreated", array('plan_usercreated' => $plan_usercreated));
					$created_user = fullname($user);
				}
              /*  if($learning_plan->learning_type == 1){
                    $plan_type = 'Core Courses';
                }elseif($learning_plan->learning_type == 2){
                    $plan_type = 'Elective Courses';
                }else{
                	$plan_type = 'N/A';
                }*/
                if(!empty($learning_plan->location)){
                    $plan_location = $learning_plan->location;
                }else{
                    $plan_location = 'N/A';
                }
				if(!empty($learning_plan->department)){
                    
                    $plan_departments= open::departments($learning_plan->department);
					$plan_department = array();
					foreach($plan_departments as $plan_dep){
						$plan_department[] = $plan_dep->fullname;
					}
					$plan_department = implode(',', $plan_department);
					$plan_department_string = strlen($plan_department) > 23 ? substr($plan_department, 0, 23)."..." : $plan_department;
                }else{
                    $plan_department = 'N/A';
                }
				if(!empty($learning_plan->subdepartment)){
                    $plan_subdepartments=open::departments($learning_plan->subdepartment);
					$plan_subdepartment = array();
					foreach($plan_subdepartments as $plan_subdep) {
						$plan_subdepartment[] = $plan_subdep->fullname;
					}
					$fullname = implode(',', $plan_subdepartment);
					$str_len = strlen($fullname);
					if($str_len > 32){
						$sub_str = substr($fullname,0,32);
					}else{
						$plan_subdepartment = $fullname;
					}
                }else{
                    $plan_subdepartment = 'N/A';
                }
                $action_icons = '';
                if (is_siteadmin() || has_capability('local/learningplan:visible', $systemcontext)) {
					$capability1 = true;
                }
                if (has_capability('local/learningplan:update', $systemcontext)) {
                	$capability2 = true;
                }
                if (has_capability('local/learningplan:delete', $systemcontext)) {
                	$capability3 = true;
                }

				$planlib = new \local_learningplan\lib\lib();
                $lplanassignedcourses = $planlib->get_learningplan_assigned_courses($learning_plan->id);
				$pathcourses = '';
				if(count($lplanassignedcourses)>=2) {
					$i = 1;
					$coursespath_context['pathcourses'] = array();
					foreach($lplanassignedcourses as $assignedcourse){	
							$coursename = $assignedcourse->fullname;
							$coursespath_context['pathcourses'][] = array('coursename'=>$coursename, 'coursename_string'=>'C'.$i);
						$i++;
						if($i>10){
                            break;
                        }
					}
					$pathcourses .= $this->render_from_template('local_learningplan/cousrespath', $coursespath_context);
				}
			 
			    $learningplan_content = array();
                $learning_plan_name = strlen($learning_plan->name) > 34 ? substr($learning_plan->name, 0, 34)."..." : $learning_plan->name;
                $hide_show_icon = $learning_plan->visible ? $this->output->image_url('i/hide') : $this->output->image_url('i/show');
                $title_hide_show = $learning_plan->visible ? 'Make Inactive' : 'Make Active';
                $learningplan_content['plan_url'] = $plan_url;
                $learningplan_content['learning_plan_name'] = $learning_plan_name;
                $learningplan_content['capability1'] = $capability1;
                $learningplan_content['capability2'] = $capability2;
                $learningplan_content['capability3'] = $capability3;
                $learningplan_content['hide'] = $learning_plan->visible ? true : false;
                $learningplan_content['hide_show_icon_url'] = $hide_show_icon;
                $learningplan_content['title_hide_show'] = $title_hide_show;
                $learningplan_content['delete_icon_url'] = $this->output->image_url('i/delete');
                
                $learningplan_content['edit_icon_url'] = $this->output->image_url('i/edit');
                $learningplan_content['learning_planid'] = $learning_plan->id;
                $learningplan_content['plan_credits'] = $plan_credits;
				$learningplan_content['plan_grade'] = $plan_grade;
                $learningplan_content['created_user'] = $created_user;
                $learningplan_content['plan_department'] = ($plan_department=='-1'||empty($plan_department))?'All':$plan_department;
               $learningplan_content['plan_shortname_string'] = $learning_plan->shortname?$learning_plan->shortname:'NA';
                $learningplan_content['plan_department_string'] = ($plan_department_string=='-1'||empty($plan_department_string))?'All':$plan_department_string;
                $learningplan_content['plan_subdepartment'] = $plan_subdepartment;
                $learningplan_content['plan_url'] = $plan_url;
                $learningplan_content['lpcoursespath'] = $pathcourses;
                $learningplan_content['lpcoursescount'] = count($lplanassignedcourses);
                $row[] = $this->render_from_template('local_learningplan/learninngplan_index_view', $learningplan_content);
                $sdata[] = implode('', $row);
				$table_data[] = $row;
				
            }
		
            if($tableenable){
	            $lpchunk = array_chunk($sdata,2);
	            $chunk = array(array(""));
	            if(isset($lpchunk[count($lpchunk)-1]) && count($lpchunk[count($lpchunk)-1])!=2) { 

	                 if(count($lpchunk[count($lpchunk)-1])==1) { 

	                 	$lpchunk[count($lpchunk)-1] = array_merge($lpchunk[count($lpchunk)-1],$chunk,$chunk); 
	                 }else{  
	                    $lpchunk[count($lpchunk)-1]=array_merge($lpchunk[count($lpchunk)-1],$chunk); 
	                } 
	            }

                $iTotal = count($assigned_users); 
                $iFilteredTotal = $iTotal;
                // return $output = array(
                // "sEcho" => intval($requestData['sEcho']),
                // "iTotalRecords" => $iTotal,
                // "iTotalDisplayRecords" => $iFilteredTotal,
                // "aaData" => $lpchunk
                // );
	                return $output = array(
	                "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 0,
	                "recordsTotal" => $iTotal,
	                "recordsFiltered" => $iFilteredTotal,
	                "data" => $lpchunk
	                );
			}
				
            $table = new html_table();
            $table->id = 'all_learning_plans';
            $table->head = array('','');
            $table->data = $table_data;
            $return = html_writer::table($table);
			$return .= html_writer::script('$(document).ready(function(){
										  
												$("#all_learning_plans").DataTable({
												
												    "serverSide": true,
												    "language": {
														paginate: {
															"previous": "<",
															"next": ">"
														},
														  "search": "",
                    									  "searchPlaceholder": "Search",
                    									  "emptyTable":     "<div class=\'w-100 alert alert-info\'>No data available </div>",
													},
													"ajax": "ajax.php?manage=1&depts='.$selecteddepts.'&groups='.$selectedgroups.'",
													"datatype": "json",
													"pageLength": 8,
													
												});
												$("table#all_learning_plans thead").css("display" , "none");
												$("#all_learning_plans_length").css("display" , "none");
										   });');
            $return .= '';
        }
        return $return;
    }
    
    public function single_plan_view($planid){
    	global $CFG,$PAGE;
		$learningplan_lib = new lib();

		$lpimgurl = $learningplan_lib->get_learningplansummaryfile($planid);	
		$plan_record = $this->db->get_record('local_learningplan', array('id' => $planid));
		$plan_description = !empty($plan_record->description) ?  strip_tags(html_entity_decode($plan_record->description),array('overflowdiv' => false, 'noclean' => false, 'para' => false)) : 'No Description available';
		$plan_objective = !empty($plan_record->objective) ? $plan_record->objective : 'No Objective available';
		/*Count of the enrolled users to LEP*/
		$totaluser_sql = "SELECT count(llu.userid)
							FROM {local_learningplan_user} as llu 
							JOIN {user} as u ON u.id=llu.userid 
							WHERE llu.planid = :planid AND u.deleted != :deleted ";
		$total_enroled_users=$this->db->count_records_sql($totaluser_sql, array('planid' => $planid, 'deleted' => 1));
		/*Count of the requested users to LEP*/
		$total_completed_users=$this->db->get_records_sql("SELECT id FROM {local_learningplan_user} WHERE completiondate IS NOT NULL
													 AND status = 1 AND planid = $planid");
		$cmpltd = array();
		foreach($total_completed_users as $completed_users){
			$cmpltd[] = $completed_users->id;
		}
		
		$total_requested_users=$this->db->count_records('local_learningplan_approval',array('planid'=>$planid));
		/*Count of the courses of LEP*/
		$total_assigned_course=$this->db->count_records('local_learningplan_courses',array('planid'=>$planid));
		
		$total_mandatory_course=$this->db->get_records_sql("SELECT id FROM {local_learningplan_courses} WHERE planid = $planid
													 AND nextsetoperator = 'and'");
		$mandatory = array();
		foreach($total_mandatory_course as $total_mandatory){
			$mandatory[] = $total_mandatory->id;
		}
		
		$total_optional_course=$this->db->get_records_sql("SELECT id FROM {local_learningplan_courses} WHERE planid = $planid
													 AND nextsetoperator = 'or'");
		$optional = array();
		foreach($total_optional_course as $total_optional){
			$optional[] = $total_optional->id;
		}
		
		if(!empty($plan_record->startdate)){
			$plan_startdate = date('d/m/Y', $plan_record->startdate);
		}else{
			$plan_startdate = 'N/A';
		}
		if(!empty($plan_record->enddate)){
			$plan_enddate = date('d/m/Y', $plan_record->enddate);
		}else{
			$plan_enddate = 'N/A';
		}
		if(empty($plan_record->usercreated)){
			$plan_usercreated = 'N/A';
		}else{
			$plan_usercreated = $plan_record->usercreated;
			$user = $this->db->get_record_sql("select * from {user} where id = $plan_usercreated");
			$created_user = fullname($user);
		}
		/*if($plan_record->learning_type == 1){
			$plan_type = 'Core Courses';
		}elseif($plan_record->learning_type == 2){
			$plan_type = 'Elective Courses';
		}*/
		if($plan_record->approvalreqd == 1){
			$plan_needapproval = 'Yes';
		}else{
			$plan_needapproval = 'No';
		}
		if(!empty($plan_record->open_group)){
			$plan_location = $plan_record->open_group;
			$str_len = strlen($plan_record->open_group);
			if($str_len > 32){
				$sub_str = substr($plan_record->open_group, 0, 32);
			}
		}else{
			$plan_location = 'N/A';
		}
		if(!empty($plan_record->department)){
            $depart=open::departments($plan_record->department);
			$Dep=array();
			foreach($depart as $dep){
				$Dep[]=$dep->fullname;
			}
			$plan_department=implode(',',$Dep);
		}else{
			$plan_department = 'N/A';
		}
		if(!empty($plan_record->subdepartment)){
            $depart=open::departments($plan_record->subdepartment);
			$Dep=array();
			foreach($depart as $dep){
				$Dep[]=$dep->fullname;
			}
			$plan_subdepartment=implode(',',$Dep);
			$str_len = strlen($plan_subdepartment);
			if($str_len > 32){
				$sub_str = substr($plan_subdepartment, 0, 32);
				$plan_subdepartment = $substr_subdepartment;
			}
		}else{
			$plan_subdepartment = 'N/A';
		}
		$lplanassignedcourses = $learningplan_lib->get_learningplan_assigned_courses($planid);
		$pathcourses = '';
			if($lplanassignedcourses) {
				$i = 1;
				$coursespath_context['pathcourses'] = array();
				foreach($lplanassignedcourses as $assignedcourse){
					if(count($lplanassignedcourses)>=2){
						$coursename = $assignedcourse->fullname;
						$coursespath_context['pathcourses'][] = array('coursename'=>$coursename, 'coursename_string'=>'C'.$i);
					}
					$i++;
					if($i>10){
                        break;
                    }
				}
				$pathcourses .= $this->render_from_template('local_learningplan/cousrespath', $coursespath_context);
			}
		    $plandescription = strip_tags(html_entity_decode(clean_text($plan_record->description)),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
            $description = $plan_record->description;
        	$descount = strlen($plandescription) > 350 ? true : false;

        	$ratings_exist = \core_component::get_plugin_directory('local', 'ratings');
		 /*   if($ratings_exist){
		        require_once($CFG->dirroot.'/local/ratings/lib.php');
		        $display_ratings = display_rating($planid, 'local_learningplan');
		        $display_like = display_like_unlike($planid, 'local_learningplan');
		        // $display_like .= display_comment($planid, 'local_learningplan');
		        $PAGE->requires->jquery();
		        $PAGE->requires->js('/local/ratings/js/jquery.rateyo.js');
		        $PAGE->requires->js('/local/ratings/js/ratings.js');
		    }else{
		        $display_ratings = $display_like = '';
		    }*/
            $planview_context = array();
            $planview_context['lpnameimg'] = $lpimgurl; 
            $planview_context['lpname'] = $plan_record->name;
            $planview_context['lpcoursespath'] = $pathcourses;
            $planview_context['description'] = $description; 
			$planview_context['descount'] = $descount;
			$planview_context['plan_learningplancode'] = $plan_record->shortname?$plan_record->shortname:'NA';
			$planview_context['plan_needapproval'] = $plan_needapproval;
			if($plan_record->open_points > 0){
				$planview_context['plan_credits'] = $plan_record->open_points;	
			}else{
				$planview_context['plan_credits'] = 'N/A';
			}
			if(empty($plan_record->open_grade)){
				$planview_context['plan_grade'] = 'N/A';
			}else{
				$planview_context['plan_grade'] = $plan_record->open_grade;
			}
			$planview_context['created_user'] = $created_user;
			$planview_context['total_assigned_course'] = $total_assigned_course;
			$planview_context['mandatory'] = count($mandatory);
			$planview_context['optional'] = count($optional);
			/*$planview_context['ratings_exist'] = $ratings_exist;
			$planview_context['display_ratings'] = $display_ratings;
			$planview_context['display_like'] = $display_like;*/

			
			
			$planview_context['plan_department_string'] = ($plan_department=='-1'||empty($plan_department))?'All':$plan_department;
			
			$plan_department = strlen($plan_department) > 23 ? substr($plan_department, 0, 23)."..." : $plan_department;
			$planview_context['plan_department'] = ($plan_department=='-1'||empty($plan_department))?'All':$plan_department;
			$planview_context['plan_subdepartment'] = $plan_subdepartment;
			$planview_context['plan_location'] = $plan_location;
			$planview_context['total_enroled_users'] = $total_enroled_users;
			$planview_context['cmpltd'] = count($cmpltd);
		
		return $this->render_from_template('local_learningplan/lp_planview', $planview_context);
	}
  /** Function For The Tabs View In The Learning
	@param $id=LEP id && $curr_tab=tab name
	Plan**/
	public function plan_tabview($id,$curr_tab,$condition){
		global $PAGE;
			
		$courses_active = '';
		$users_active = '';
		$bulk_users_active = '';
		$request_users='';
		if($curr_tab == 'users'){
			$users_active = ' active ';
		}elseif($curr_tab == 'courses'){
			$courses_active = ' active ';
		}
		elseif($curr_tab == 'request_user'){
			$request_users= ' active';
		}
		
		$total_enroled_users=$this->db->get_record_sql('SELECT count(llu.userid) as data  FROM {local_learningplan_user} as llu JOIN {user} as u ON u.id=llu.userid WHERE llu.planid='.$id.' AND u.deleted!=1');
		$total_requested_users=$this->db->count_records('local_learningplan_approval',array('planid'=>$id));
		$total_assigned_course=$this->db->count_records('local_learningplan_courses',array('planid'=>$id));
		$return = '';
		$tabs = '<div id="learningplantabs" class="planview_tabscontainer w-full pull-left mt-3">
					<ul class="nav nav-tabs inner_tabs" role="tablist">
						<li class="nav-item learningplan_tabs" role="presentation"  data-module="courses"  data-id="'.$id.'">
							<a class="active nav-link" data-toggle="tab"  href="javascript:void(0)" aria-controls="plan_courses" role="tab">
								Courses</a>
						</li>
						<li class="nav-item learningplan_tabs" role="presentation" data-module="users" data-id="'.$id.'">
							<a class="nav-link" data-toggle="tab" href="javascript:void(0)" aria-controls="plan_users" role="tab">
								Users
							</a>
						</li>
						<li class="nav-item learningplan_tabs" role="presentation" data-module="targetaudiences" data-id="'.$id.'">
							<a class="nav-link" data-toggle="tab" href="javascript:void(0)" aria-controls="plan_targetaudiences" role="tab">
								'.get_string('target_audience_tab','local_learningplan').'
							</a>
						</li>';
				if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
					$request_renderer = $PAGE->get_renderer('local_request');
					$requestdata = $request_renderer->render_requestview(TRUE, $id, 'learningplan');
					$options = $requestdata['options'];
					$dataoptions = $requestdata['dataoptions'];
					$filterdata =$requestdata['filterdata'];
					$tabs .= "<li class='nav-item learningplan_tabs' role='presentation' data-module='requestedusers' data-id=$id data-options = '".$options."' data-dataoptions='".$dataoptions."' data-filterdata='".$filterdata."'>
							<a class='nav-link' data-toggle='tab' href='javascript:void(0)' aria-controls='requested_users' role='tab'>
								Requested users
							</a>
						</li>
						";
				}
				$tabs .= '</ul>';
			$tabs .= '<div class="tab-content" id="learningplantabscontent">';
			$tabs .= $this->learningplans_courses_tab_content($id, $curr_tab,$condition);
			$tabs .= '</div>';
		$tabs .= '</div>';
		$return .= $tabs;
		return $return;
	}
    
	/**Function to view of course tab
	$planid=LEP_id $curr_tab="tab name"
	**/
	public function learningplans_courses_tab_content($planid, $curr_tab,$condition){
		
        $systemcontext = context_system::instance();
		
		$return ='';
		$return .='<div class="tab-pane active mt-15 ml-15" id="plan_courses" role="tabpanel">';
		if (has_capability('local/learningplan:assigncourses', $systemcontext)) {
			$return .= $this->learningplans_assign_courses_form($planid,$condition);
		}
		$return .='';
		$return .= '<div class="lp_course-wrapper w-100 pull-left">'.$this->assigned_learningplans_courses($planid).'</div>';
		$return .='';
		$return .= '</div>';
		return $return;
	}
    public function learningplans_target_audience_content($planid, $curr_tab,$condition) {
        global $OUTPUT, $CFG, $DB,$USER;
              $data = $DB->get_record_sql('SELECT id, open_group,
             open_designation, open_location, open_grade, department, subdepartment
             FROM {local_learningplan} WHERE id = ' .$planid);
            
            if($data->department==-1||$data->department==NULL){
                $department=get_string('audience_department','local_learningplan','All');
            }else{
                 $departments = $DB->get_field_sql("SELECT GROUP_CONCAT(fullname)  FROM {local_costcenter} WHERE id IN ($data->department)");
                 $department=get_string('audience_department','local_learningplan',$departments);
            }
            if($data->subdepartment == -1 || $data->subdepartment == NULL){
                $subdepartment=get_string('audience_subdepartment','local_learningplan','All');
            }else{
                $sql = "SELECT id,fullname
                 		FROM {local_costcenter} 
                 		WHERE id IN ($data->department)";

                $departments = $DB->get_records_sql_menu($sql);
                $depts = implode(", ", $departments);

                $department = get_string('audience_department','local_learningplan',$depts);
            }
            if(empty($data->open_group)){
                 $group=get_string('audience_group','local_learningplan','All');
            }else{
                $sql = "SELECT id,name
                 		FROM {cohort} 
                 		WHERE id IN ($data->open_group)";

                $groupslist = $DB->get_field_sql($sql);
                $groups = implode(", ", $groupslist);
                $group = get_string('audience_group','local_learningplan',$groups);
            }
            
            // $data->open_hrmsrole =(!empty($data->open_hrmsrole)) ? $hrmsrole=get_string('audience_hrmsrole','local_learningplan',$data->open_hrmsrole) :$hrmsrole=get_string('audience_hrmsrole','local_learningplan','All');
            
            $data->open_designation =(!empty($data->open_designation)) ? $designation=get_string('audience_designation','local_learningplan',$data->open_designation) :$designation=get_string('audience_designation','local_learningplan','All');
            
            $data->open_location =(!empty($data->open_location)) ? $location=get_string('audience_location','local_learningplan',$data->open_location) :$location=get_string('audience_location','local_learningplan','All');

            $data->open_grade =(!empty($data->open_grade)) ? $grade=get_string('audience_grade','local_learningplan',$data->open_grade) :$grade=get_string('audience_grade','local_learningplan','All');
            
             return '<div class="tab-pane active mt-15 ml-15" id="plan_courses" role="tabpanel">'.$department.$subdepartment.$group.$hrmsrole.$designation.$location.$grade.'</div>';
    }
    /**Function to tab view of bulk users uploads
	$planid=LEP_id $curr_tab="tab name"
	**/ 
	public function learningplans_bulk_users_tab_content($planid, $designation, $department,$empnumber,$organization,$email,$band,$subdepartment,$sub_subdepartment){	
		$return ='';
		if(!is_null($designation) || !empty($department) || !empty($organization) || !empty($empnumber) || !empty($email) || !empty($band) || !empty($subdepartment) || !empty($sub_subdepartment)){
			$select_to_users = $this->select_to_users_of_learninplan($planid,$this->user->id,$designation, $department,$empnumber,$organization,$email,$band,$subdepartment,$sub_subdepartment);
			$select_from_users = $this->select_from_users_of_learninplan($planid,$this->user->id,$designation, $department,$empnumber,$organization,$email,$band,$subdepartment,$sub_subdepartment);
		}else{
			$select_to_users = $this->select_to_users_of_learninplan($planid,$this->user->id,$designation,$department,$empnumber,$organization,$email,$band,$subdepartment,$sub_subdepartment);
			$select_from_users = $this->select_from_users_of_learninplan($planid,$this->user->id,$designation, $department,$empnumber,$organization,$email,$band,$subdepartment,$sub_subdepartment);
		}
		
		$return .='<div class="user_batches text-center">
					<form  method="post" name="form_name" id="assign_users_'.$planid.'" action="assign_courses_users.php" class="form_class" >
					<input type="hidden"  name="type" value="bulkusers" >
					<input type="hidden"  name="planid" value='.$planid.' >
					<fieldset>
					<ul class="button_ul">
					
					<li style="padding:18px; display:none"><label>Search</label>
					<input id="textbox" type="text"/>
					</li>
					<li><input type="button" id="select_remove" name="select_all" value="Select All">
					<input type="button" id="remove_select" name="remove_all" value="Remove All">
					</li>
					
					<li>';
					
					$return .='<select name="add_users[]" id="select-from" multiple size="15">';
	
        $return .= '<optgroup label="Selected member list ('.count($select_from_users).') "></optgroup>';
        if(!empty($select_from_users)){
			foreach($select_from_users as $select_from_user){
				if($select_from_user->id == $this->user->id){
					$trainerid_exist=array();
				}else{
					$trainerid_exist="";
				}
				if((empty($trainerid_exist))){
					$symbol="";
					$check=$this->db->get_record('local_learningplan_user',array('userid'=>$select_from_user->id,'status'=>1,'planid'=>$planid));
					if($check){
						$disable="disabled";
						$title="title='User Completed'";
					}else{
						$title="";
						$disable="";
					}
					$data_id=preg_replace("/[^0-9,.]/", "", $select_from_user->idnumber);
					$return .= "<option value=$select_from_user->id $disable $title>$symbol $select_from_user->firstname $select_from_user->lastname ($data_id)</option>";	
				}
			}
			foreach($select_from_users as $select_from_user){
			}
		}else{
			$return .='<optgroup label="None"></optgroup>';
		}
	    
		$return .=	'</select></li>
					</ul>
					<ul class="button_ul">
						
					<li><input type="submit" name="submit_users" value="add users" id="btn_add" style="width:98px;"></li>                    
					<li><input type="submit" name="submit_users" value="remove users" id="btn_remove"></li>
					</ul>
					
					<ul class="button_ul">
					<li><input type="button" id="select_add" name="select_all" value="Select All">
					<input type="button" id="add_select" name="remove_all" value="Remove All">
					</li>
					<li><select name="remove_users[]" id="select-to" multiple size="15">';
						
		$return .= '<optgroup label="Selected member list ('.count($select_to_users).') "></optgroup>';
		if(count($select_to_users) > 100){
			$return .= '<optgroup label="Too many users, use search."></optgroup>';
			$select_to_users = array_slice($select_to_users,0,100);
		}
		if(!empty($select_to_users)){
			foreach($select_to_users as $select_to_user){
				if($select_to_user->id == $this->user->id){
					$trainerid_exist=array();
				}else{
					$trainerid_exist="";
				}
				$data_id=preg_replace("/[^0-9,.]/", "", $select_to_user->idnumber);
				if((empty($trainerid_exist))){
					$symbol="";
					$return .= "<option  value=$select_to_user->id >$symbol $select_to_user->firstname $select_to_user->lastname ($data_id)</option>";
				}
			}
		}else{
			$return .='<optgroup label="None"></optgroup>';
		}
						
		$return .='</select></li>
					</ul>
					</fieldset>
					</form>
					</div>';
						
		$return .="<script>
						$('#btn_add').prop('disabled', true);
						  $('#select-to').on('change', function() {
						  
							 if(this.value!=''){
							  $('#btn_add').prop('disabled', false);
							  $('#btn_remove').prop('disabled', true);
							 }else{
							  $('#btn_add').prop('disabled', true);
							}
						})
						$('#select_add').click(function() {
								 $('#select-to option').prop('selected', true);
								  $('#btn_remove').prop('disabled', true);
								 $('#btn_add').prop('disabled', false);
							});
						$('#add_select').click(function() {
								 $('#select-to option').prop('selected',false);
								 $('#btn_remove').prop('disabled', true);
								 $('#btn_add').prop('disabled', true);
							}); 
						
						$('#btn_remove').prop('disabled', true);
						  $('#select-from').on('change', function() {
							 if(this.value!=''){
							  $('#btn_remove').prop('disabled', false);
							  $('#btn_add').prop('disabled', true);
							 }else{
							  $('#btn_remove').prop('disabled', true);
							}
						})
						$('#select_remove').click(function() {
								 $('#select-from option').prop('selected', true);
								 $('#btn_add').prop('disabled', true);
								 $('#btn_remove').prop('disabled', false);
							});
						$('#remove_select').click(function() {
								 $('#select-from option').prop('selected', false);
								 $('#btn_add').prop('disabled', true);
								 $('#btn_remove').prop('disabled', true);
							});
						
						
					</script>";								
		/*to check courses has the Learning plan enrolment or not*/
		$courses=$this->db->get_records('local_learningplan_courses',array('planid'=>$planid));
		
		if($courses){/*If courses it self not assignes so to check condition*/
			$table = 'local_learningplan_courses';
			$conditions = array('planid'=>$planid);
			$sort = 'id';
			$fields = 'id, courseid'; 
			$result = $this->db->get_records_menu($table,$conditions,$sort,$fields);
            $count=count($result);
			/*finally get the count of records in total courses*/
			$data=implode(',',$result);
			$sql="select * from {enrol} where courseid IN ($data) and enrol='learningplan'";
			$check=$this->db->get_records_sql($sql);
			$check_count=count($check);
			/*get the enrol records according to course*/
			if($check_count==$count){
				return $return;
			}else{
				//$return_msg ='Please apply Learning plan enrolment to all course';
				return $return_msg;
			}
		}
	}
	public function select_from_users_of_learninplan($planid,$userid,$params,$total=0,$offset1=-1,$perpage=-1,$lastitem=0){
		$users = $this->db->get_record('local_learningplan',array('id'=>$planid));
		if($total==0){

			$sql="SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname ";
	    }else{
                $sql = "SELECT count(u.id) as total";
        }  

		$sql.=" FROM {user} u WHERE u.id >1 AND u.deleted=0 AND u.suspended=0 "; 

		if($lastitem!=0){
           $sql.=" AND u.id > $lastitem";
        }
        if (( !is_siteadmin() && ( !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                $sql .= " AND u.open_costcenterid = :costcenter";
                $params['costcenter'] = $this->user->open_costcenterid;
                if ((has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                    $sql .= " AND u.open_departmentid = :department";
                    $params['department'] = $this->user->open_departmentid;
                 }
         }
		// if($users->department !== null && $users->department !== '-1'&& $users->department !== 0){
		// 		$sql.= ' AND u.open_departmentid IN('.$users->department.')';
		// }
		
		$sql .=" AND u.id in(SELECT userid FROM {local_learningplan_user} WHERE planid=$planid)";

		if (!empty($params['email'])) {
			$sql.=" AND u.id IN ({$params['email']})";
		}
		if (!empty($params['uname'])) {
			$sql .=" AND u.id IN ({$params['uname']})";
		}
		if (!empty($params['department'])) {
			$sql .=" AND u.open_departmentid IN ({$params['department']})";
		}
		if (!empty($params['organization'])) {
			$sql .=" AND u.open_costcenterid IN ({$params['organization']})";
		}
		if (!empty($params['idnumber'])) {
			$sql .=" AND u.id IN ({$params['idnumber']})";
		}
		if (!empty($params['groups'])) {
  
           $sql .=" AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
     
        }
        $order = ' ORDER BY u.id ASC ';
        if($perpage!=-1){
            // $order.="LIMIT $perpage";
        }
		
		if($total==0){
			$users=$this->db->get_records_sql_menu($sql .$order,$params, '', $perpage);
		}else{
			$users =$this->db->count_records_sql($sql,$params);
		}
		
		return $users;
	}
	/*End of the function*/
    
	/*Function to called in the bulk users upload*/
	public function select_to_users_of_learninplan($planid, $userid,$params,$total=0,$offset1=-1,$perpage=-1,$lastitem=0){
		
		$users = $this->db->get_record('local_learningplan',array('id'=>$planid));
		$us = $users->open_band;
		$array=explode(',',$us);
		$list=implode("','",$array);
		$loginuser= $this->user;
		$systemcontext = context_system::instance();
		if(!is_siteadmin()){
			$siteadmin_sql=" AND u.open_costcenterid = $users->costcenter ";
		}else{
			$siteadmin_sql="";
		}
		if($total==0){
			$sql = "SELECT  u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname ";
		}else{
			 $sql = "SELECT count(u.id) as total";
		}
		$sql.=" FROM {user} u WHERE u.id >2 AND u.suspended =0
								 AND u.deleted =0  $siteadmin_sql AND u.id not in ($loginuser->id) ";

		if($lastitem!=0){

            $sql.=" AND u.id > $lastitem";
         }
		if (( !is_siteadmin() && ( !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                $sql .= " AND u.open_costcenterid = :costcenter";
                $params['costcenter'] = $this->user->open_costcenterid;
                if (has_capability('local/costcenter:manage_owndepartments', context_system::instance())) {
                    $sql .= " AND u.open_departmentid = :department";
                    $params['department'] = $this->user->open_departmentid;
                 }
         }
		// if($users->department !== null && $users->department !== '-1'&& $users->department !== 0){
		// 		$sql.= ' AND u.open_departmentid IN('.$users->department.')';
		// }
		
		
		if (!empty($params['email'])) {
			$sql.=" AND u.id IN ({$params['email']})";
		}
		if (!empty($params['uname'])) {
			$sql .=" AND u.id IN ({$params['uname']})";
		}
		if (!empty($params['department'])) {
			$sql .=" AND u.open_departmentid IN ({$params['department']})";
		}
		if (!empty($params['organization'])) {
			$sql .=" AND u.open_costcenterid IN ({$params['organization']})";
		}
		if (!empty($params['idnumber'])) {
			$sql .=" AND u.id IN ({$params['idnumber']})";
		}
		if (!empty($params['groups'])) {

            $sql .=" AND u.id IN (select cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']}))";
        }


		$sql .=" AND u.id not in(SELECT userid FROM {local_learningplan_user} WHERE planid=$planid)";

		$order = ' ORDER BY u.id ASC ';
        if($perpage!=-1){
            // $order.="LIMIT $perpage";
            $limit = $perpage;
        }
		if($total==0){
			$users=$this->db->get_records_sql_menu($sql.$order,$params, '', $perpage);
		}else{
			$users =$this->db->count_records_sql($sql,$params);
		}
		return $users;
	}
	/*End of the function*/
	
	/*Function to view the users and assign users*/
	public function learningplans_users_tab_content($planid, $curr_tab,$condition,$ajax){
		global $CFG,$OUTPUT;
		if($ajax==0){
        $systemcontext = context_system::instance();
		
		$return = '';
		$return .= '<div class="tab-pane" id="plan_users" role="tabpanel">';
		if (has_capability('local/learningplan:assignhisusers', $systemcontext)) {
			$table = 'local_learningplan_courses'; 
			$conditions = array('planid'=>$planid);
			$sort = 'id';
			$fields = 'id, courseid'; 
			$result = $this->db->get_records_menu($table,$conditions,$sortid,$fields);
			$count=count($result);
			/*finally get the count of records in total courses*/
			$data=implode(',',$result);
				$return .= "<ul class='course_extended_menu_list learningplan'>
		                 <li>
								<div class='coursebackup course_extended_menu_itemcontainer'>
	                   <a id='extended_menu_syncusers' title='".get_string('le_enrol_users', 'local_learningplan')."' class='course_extended_menu_itemlink' href='" . $CFG->wwwroot ."/local/learningplan/lpusers_enroll.php?lpid=".$planid."'><i class='icon fa fa-user-plus fa-fw' aria-hidden='true' aria-label=''></i></a>
	              	</div>
	              </li></ul>";				
		}
		$return .= $this->assigned_learningplans_users($planid,$ajax);
		$return .= '</div>';
	  }else{
	  	$return= $this->assigned_learningplans_users($planid,$ajax);
	  }
		
		return $return;
	}
	/*End of the function*/

	/*Function to view the requested users in learningplan*/
	public function learningplans_requested_users_content($planid, $curr_tab,$condition){
		global $DB,$CFG,$OUTPUT,$PAGE;
        $systemcontext = context_system::instance();
		
		$return = '';
		if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
		 $learningplan = $DB->get_records('local_request_records', array('compname' =>'learningplan','componentid'=>$planid));
        $output = $PAGE->get_renderer('local_request');
        $component = 'learningplan';
        if($learningplan){
            $return = $output->render_requestview(false , $planid, $component);
            // $return = json_encode($return);
        }else{
        	$return = '<div class="alert alert-info">'.get_string('requestavail', 'local_classroom').'</div>';
        }
    }
		return $return;
	}
	/*End of the function*/
	
	public function learningplans_assign_courses_form($planid,$condition){
		global $DB;
		$systemcontext = context_system::instance();
		$plan_name = $DB->get_field('local_learningplan', 'name', array('id' => $planid));
		$learningplan_lib = new lib;
		$userscount = $learningplan_lib->get_enrollable_users_count_to_learningplan($planid);
		$return = '';
		$add_learningplancourses = '<ul class="course_extended_menu_list learningplan">
							    		<li>
									        <div class="course_extended_menu_itemcontainer">
									            <a title="Assign Courses" class="course_extended_menu_itemlink" href="javascript:void(0);"
									            	onclick="(function(e){ require(\'local_learningplan/courseenrol\').init({selector:\'createcourseenrolmodal\', contextid:'.$systemcontext->id.', planid:'.$planid.', condition:\'manage\'}) })(event)">
									            	<i class="icon fa fa-plus" aria-hidden="true"></i>
									            </a>
									        </div>
									    </li>
									</ul>';

		$return .= $add_learningplancourses;
		$return .= '<div class="assign_courses_container">';
		
		$courses = $learningplan_lib->learningplan_courses_list($planid);
        $return .= '</div>';
		
		return $return;
	}
	public function get_editand_publish_icons($planid){
		global $DB, $CFG, $PAGE;
		$systemcontext = context_system::instance();
		$plan_name = $DB->get_field('local_learningplan', 'name', array('id' => $planid));
		$learningplan_lib = new lib;
		$userscount = $learningplan_lib->get_enrollable_users_count_to_learningplan($planid);


		$learningplaninfo['plan_name'] = $plan_name;
		$learningplaninfo['planid'] = $planid;
		$learningplaninfo['userscount'] = $userscount;
		$learningplaninfo['configpath'] = $CFG->wwwroot;
		$can_manage = has_capability('local/learningplan:manage', $systemcontext);
		$learningplaninfo['can_update'] = (is_siteadmin() || ($can_manage && has_capability('local/learningplan:update', $systemcontext)));
		$learningplaninfo['can_publish'] = (is_siteadmin() || ($can_manage && has_capability('local/learningplan:publishplan', $systemcontext)));
		$learningplaninfo['can_enrolusers'] = (is_siteadmin() || ($can_manage && has_capability('local/learningplan:assignhisusers', $systemcontext)));
		$challenge_exist = \core_component::get_plugin_directory('local', 'challenge');
        if($challenge_exist){
			$challenge_render = $PAGE->get_renderer('local_challenge');
			$element = $challenge_render->render_challenge_object('local_learningplan', $planid);
			$learningplaninfo['challenge_element'] = $element;
		}else{
			$learningplaninfo['challenge_element'] = false;
		}
		$edit_publish_icons = $this->render_from_template('local_learningplan/learningplan_publish_edit', $learningplaninfo);
		return $edit_publish_icons;
	}
	
	private function learningplans_assign_users_form($planid,$condition){
		$sql = "SELECT userid, planid FROM {local_learningplan_user} WHERE planid = $planid";
		$existing_plan_users = $this->db->get_records_sql($sql);
		$return = '';
			$assign_button = '<a class="pull-right assigning " onclick="assign_users_form_toggle('.$planid.')" id="plan_assign_users_'.$planid.'">'.get_string('assign_users', 'local_learningplan').'</a>';
			$return .= $assign_button;
			$return .= '<div class="assign_users_container">';
				$return .= '<form autocomplete="off" id="assign_users_'.$planid.'" action="assign_courses_users.php" method="post" class="mform">';
					$return .= '<fieldset class="hidden">
									<div>
										<div id="fitem_id_t_id[]" class="fitem fitem_fselect ">
											<div class="fitemtitle">
												<label for="id_u_id[]">Select users</label>
											</div>
											<div class="felement ftext">
												<select name="learning_plan_users[]" id="id_lpassignusers" size="10" multiple class="learningplan-assign-users">';
					
									$return .= "</select>
											</div>
										</div>
									</div>
								</fieldset>";
					$return .= '<input type="hidden" name="planid" value=' . $planid . ' />
					            <input type="hidden" name="condtion" value="' . $condition . '" />
								<input type="hidden" name="type" value="assign_users" />';
					$return .= '<fieldset class="hidden">
									<div>
										<div id="fitem_id_submitbutton" class="fitem fitem_actionbuttons fitem_fsubmit">
											<div class="felement fsubmit">
												<input type="submit" class="form-submit" value="Assign" />
											</div>
										</div>';
						$return .= '</div>
								</fieldset>
							</form>';
			$return .= '</div>';
		return $return;
	}
	/**Function to view the  course and functionality with the sortorder @param $planid=LEP_id**/
	public function assigned_learningplans_courses($planid){

        $systemcontext = context_system::instance();
		$learningplan_lib = new lib();

		$includes = new \user_course_details;
		
		$courses = lib::get_learningplan_assigned_courses($planid);
		
		$return = '';
		$return .= '<form class ="l_form" action="assign_courses_users.php" method="post">';

		if(empty($courses)){
			$return .= html_writer::tag('div', get_string('nolearningplancourses', 'local_learningplan'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
		}else{
			
			$table_data = array();
			/**To check the highest sortorder of courses below query written and to compare list of courses**/
			$sql="SELECT id,sortorder FROM {local_learningplan_courses} WHERE planid = :planid ORDER BY sortorder DESC";
		 	$find=$this->db->get_record_sql($sql, array('planid' => $planid));
			/****End of the query****/
			
			/**Below query written to check the users assigned to LEP or NOT and Disable submit button**/
			$userscount=$this->db->get_record('local_learningplan_user',array('planid'=>$planid));
			/*end of query*/
			
			/**The below query has been written taken count if we have submitted condition and later we added new course then submit should open**/
			$courses_zero_count=$this->db->get_records('local_learningplan_courses',array('planid'=>$planid,'nextsetoperator'=>0));
			/*end of query*/
			if($userscount && (count($courses_zero_count)==1 || count($courses_zero_count)==0)){
				$disbaled_button="disabled";
			}else{
				$disbaled_button="";
			}
			/*making list of course*/
			$i=1;
			$lpcourse_data = '';
            foreach($courses as $course){
				
				if($course->next=='and'){
					$select='echo checked="checked"';
					
				}elseif($course->next=='or'){
					$select='';
				}
				
				$startdiv ='<div class="lp_course_sortorder w-full pull-left" id="dat'.$course->id.'">';
				$enddiv='<div>';
				$course_url = new \moodle_url('/course/view.php', array('id'=>$course->id));
				$course_link = strlen($course->fullname) > 25 ? substr($course->fullname, 0, 25)."..." : $course->fullname;
				$course_view_link = html_writer::link($course_url, $course_link, array('title'=>$course->fullname));
				//$course_summary_image_url = $includes->course_summary_files($course);
				$course_summary_image_url = course_thumbimage($course);
				
				$coursesummary = strip_tags(html_entity_decode($course->summary),
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false));
				$course_summary = empty($coursesummary) ? 'Course summary not provided' : $coursesummary;

            	 $course_summary_string = strlen($course_summary) > 125 ? substr($course_summary, 0, 125)."..." : $course_summary;

				$course_total_activities = $includes->total_course_activities($course->id);
				$course_total_activities_link = html_writer::link($course_url, $course_total_activities, array());
				
				$actions = '';/****actions like delete and move up and down****/
				$buttons= ''; /****buttons are select box****/
				
				if (has_capability('local/learningplan:assigncourses', $systemcontext)) {
					
					$unassign_url = new \moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid, 'unassigncourse' => $course->lepid));
					$unassign_link = html_writer::link('javascript:void(0)',
						'<i class="icon fa fa-times fa-fw" aria-hidden="true" title="Un-assign" aria-label="Delete"></i>', array('class' => 'pull-right','id' => 'unassign_course_'.$course->lepid.'', 'onclick' => '(function(e){ require(\'local_learningplan/lpcreate\').unassignCourses({action:\'unassign_course\' , unassigncourseid:'.$course->lepid.', planid:'.$planid.', fullname:"'.$course->fullname.'" }) })(event)'));
					
													
					if($course->sortorder==0){ /**condtion to check the sortorder and make arrows of up and down for the first record ot course**/	
						
						$unassign_url1 = new \moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'down'));
						$unassign_link1 = html_writer::link($unassign_url1,'<i class="icon fas fa-arrow-down" title="Move Down"></i>', array('class' => 'pull-right'));
						
						if($disbaled_button==""){
							$actions .= $unassign_link1; /*Arrows down for first course*/
						}
						/*condition for the select the dropdown if already selected*/
						/*Select box*/
						$buttons .='<span class="switch_type">										
										<label class="switch">
											<input class="switch-input" type="checkbox" id="next_val'.$course->id.'" value="'.$course->id.'" "'.$select.'">
											<span class="switch-label" data-on="Man" data-off="Opt"></span> 
											<span class="switch-handle"></span> 
										</label>
							
										<input type="hidden" value="'.$course->lepid.'" id="courseid'.$course->lepid.'" name="row[]">
										<input type="hidden" value="'.$planid.'" name="plan">
									</span>';
							
							/*End of the select box*/
							$select='';
					}elseif($course->sortorder==$find->sortorder){
						/*condition to check the last course and make the up arrow*/
						
						$unassign_url2 = new \moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'up'));
						$unassign_link_up = html_writer::link($unassign_url2,'<i class="icon fas fa-arrow-up" title="Move Up"></i>', array('class' => 'pull-right'));
						if($disbaled_button==""){
							$actions .=$unassign_link_up;
						}
						$buttons .='<span class="switch_type">										
										<label class="switch">
											<input class="switch-input" type="checkbox" id="next_val'.$course->id.'" value="'.$course->id.'" "'.$select.'">
											<span class="switch-label" data-on="Man" data-off="Opt"></span> 
											<span class="switch-handle"></span> 
										</label>
						
						<input type="hidden" value="'.$course->lepid.'" id="courseid'.$course->lepid.'" name="row[]">
						<input type="hidden" value="'.$planid.'" name="plan">
						</span>';
							
							
					} else { 
					/*Else condition Not for first and last record should have the both arrows*/
						
						$unassign_url2 = new \moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'up'));
						$unassign_link1 = html_writer::link($unassign_url2,'<i class="icon fas fa-arrow-up" title="Move Up"></i>', array('class' => 'pull-right'));
						
						$unassign_url2 = new \moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'down'));
						$unassign_link_down = html_writer::link($unassign_url2,'<i class="icon fas fa-arrow-down" title="Move Down"></i>', array('class' => 'pull-right'));
						if($disbaled_button==""){
							$actions .=$unassign_link_down;
							$actions .= $unassign_link1;
						}
						/*select box*/
						$buttons .='<span class="switch_type">										
										<label class="switch">
											<input class="switch-input" type="checkbox" id="next_val'.$course->id.'" value="'.$course->id.'" "'.$select.'">
											<span class="switch-label" data-on="Man" data-off="Opt"></span> 
											<span class="switch-handle"></span> 
										</label>
						
										<input type="hidden" value="'.$course->lepid.'" id="courseid'.$course->lepid.'" name="row[]">
									</span>';
						/*end of the select box*/
						$courseid_condition[]=$course->lepid;
						$select='';
					}
					
							$confirmationmsg = get_string('unassign_courses_confirm','local_learningplan', $course);
				     
							$actions .= $unassign_link;
				}
				
				 $progress = $includes->user_course_completion_progress($course->id ,$this->user->id);
					if (!$progress) {
						$progress = 0;
						$progress_bar_width = " min-width: 0px;";
					} else {
						$progress = round($progress);
						$progress_bar_width = "min-width: 0px;";
					}
					
				$enrolledusers = $this->db->get_records_menu('local_learningplan_user',  array('planid' =>$planid), 'id', 'id, userid');
				if(!empty($enrolledusers)){
					$course_completions = $this->db->get_records_sql_menu("SELECT id,userid  FROM {course_completions} WHERE course = $course->id AND timecompleted IS NOT NULL");
					
					$result=array_intersect($enrolledusers,$course_completions);
					$user_completions = round((count($result)/count($enrolledusers))*100);
				}else{
					$user_completions=0;
				} 
				// if($progress==100){
				// 	$cmpltd_class = 'course_completed';
				// 	$completedtime = $this->db->get_field('course_completions', 'timecompleted', array('course' => $course->id, 'userid' => $this->user->id));
				// 	if($completedtime){
				// 		$completed_date = date("j M 'Y",$completedtime);
				// 	}else{
				// 		$completed_date = '';
				// 	}

				// }else{
					$cmpltd_class = '';
					$completed_date = '';
				//}			
				if($course->sortorder == 0){/*Condtion to set the enable to first sortorder*/
					$disable_class1 = ' '; /*Empty has been sent to class*/
				}
				$lpcourses_context['disable_class1'] = $disable_class1;
				$lpcourses_context['courseid'] = $course->id;
				$lpcourses_context['course_summary_image_url'] = $course_summary_image_url;
				$lpcourses_context['course_summary_string'] = $course_summary_string;
				$lpcourses_context['course_view_link'] = $course_view_link;
				$lpcourses_context['course_name'] = $course->fullname;
				$lpcourses_context['numbercount'] = $i++;
				$lpcourses_context['buttons'] = $buttons;
				$lpcourses_context['actions'] = $actions;
				$lpcourses_context['submitbuttons'] = $submitbuttons;
				$lpcourses_context['progress'] =  (is_nan($progress)) ? 0 : $progress;
				$lpcourses_context['date'] = $completed_date;
				$lpcourses_context['cmpltd_class'] = $cmpltd_class;
				
				$lpcourse_data .= $this->render_from_template('local_learningplan/courestab_content', $lpcourses_context);
				$lpcourse_data .=html_writer::script("$('#next_val".$course->id."').click(function() {
											var checked = $(this).is(':checked');
											
										if(checked){
											   var checkbox_value = '';
											   var plan=$planid;
											   var value='and';
											  checkbox_value = $(this).val();
											 
										}else{
										    var plan=$planid;
											var checkbox_value = '';
											 var value='or';
											checkbox_value = $(this).val();
										}
											$.ajax({
											type: 'POST',
											url: M.cfg.wwwroot + '/local/learningplan/ajax.php?course='+checkbox_value+'&planid='+plan+'&value='+value,
											data: { checked : checked },
											success: function(data) {
										
											},
											error: function() {
											},
											complete: function() {
										
											}
											});
										});
										");
			}
			$return .= $lpcourse_data;
			$return .= '</form>';
		}
		
		return $return; 
	}
	/******End of the function of the which has sortorder and condition for the courses*******/
	public function assigned_learningplans_users($planid,$ajax){
		global $OUTPUT,$DB;

		$systemcontext = context_system::instance();

		$core_component = new \core_component();
		$certificate_plugin_exist = $core_component::get_plugin_directory('tool', 'certificate');
        if($certificate_plugin_exist){
            $certid = $DB->get_field('local_learningplan', 'certificateid', array('id'=>$planid));
        }else{
            $certid = false;
        }
		if($ajax==0){
			$check=$DB->record_exists('local_learningplan_user', array('planid'=>$planid));
			if($check){
				$table = new html_table();
				$table->id = 'learning_plan_users';
				$head = array(get_string('username', 'local_learningplan'),
			                get_string('employee_id', 'local_learningplan'),
			                get_string('supervisorname', 'local_learningplan'),
							get_string('start_date', 'local_learningplan'),
							get_string('completion_date', 'local_learningplan'),
							get_string('learning_plan_status', 'local_learningplan')
						);
				if($certid){
                    $head[] = get_string('certificate','local_learningplan');
                }
				$table->head = $head;

				if (has_capability('local/learningplan:assignhisusers', $systemcontext)) {
					/*$table->head[] = get_string('learning_plan_actions', 'local_learningplan');*/
				}
				$table->data = array();
				
				$return= html_writer::table($table);
			}else{
				$return= html_writer::tag('div', get_string('nolearningplanusers', 'local_learningplan'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
			}
	    }else{
			$requestData= $_REQUEST;
	        
			$learningplan_lib = new lib();
			$users = $learningplan_lib->get_learningplan_assigned_users($planid,$requestData);
			
			$return = '';
			
				$table_data = array();

	            foreach($users as $user){
					$course_url = new \moodle_url('/local/learningplan/local_learningplan_courses.php', array('planid'=>$planid,'id'=>$user->id));
					$courses_link = html_writer::link($course_url, 'View more', array('id'=>$user->id));
					if($user->status==1){
						$completed="Completed";
					}  
					$user_url = new \moodle_url('/local/users/profile.php', array('id'=>$user->id));
					$user_profile_link = html_writer::link($user_url, fullname($user), array());
					$employee_id = empty($user->open_employeeid) ? 'N/A' : $user->open_employeeid;
					$supervisor = $DB->get_field('user', 'concat(firstname," ",lastname)', array('id' => $user->open_supervisorid));
					$supervisorname = empty($supervisor) ? 'N/A': $supervisor;
					$start_date = empty($user->timecreated) ? 'N/A' : date('d M Y',$user->timecreated);
					$completion_date = empty($user->completiondate) ? 'N/A' : '<i class="fa fa-calendar pr-10" aria-hidden="true"></i>'.date('d M Y',$user->completiondate); 
					$status = empty($user->status) ? 'Not Completed' : $completed;
					
					// if (has_capability('local/learningplan:assignhisusers', $systemcontext)) {
					// 	$unassign_url = new \moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid, 'unassignuser' => $user->id));
					// 	$unassign_link = html_writer::link($unassign_url,
					// 									html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign'))
					// 									, array('id' => 'unassign_user_'.$user->id.''));
					// 	$unassign_link = html_writer::link('javascript:void(0)',html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign')), array('id' => 'unassign_user_'.$user->id.'', 'onclick' => '(function(e){ require(\'local_learningplan/lpcreate\').unassignUsers({action:\'unassign_user\' , unassignuserid:'.$user->id.', planid:'.$planid.', fullname:"'.fullname($user).'" }) })(event)'));
						
					// 	if($completed=="Completed..."." ".$courses_link){
					// 		$unassign_link1 = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/check'), 'class' => 'icon', 'title' => 'Completed'));
					// 		$actions = $unassign_link;
					// 	}
					// 	$confirmationmsg = get_string('unassign_users_confirm','local_learningplan', $user);
								
					// 	$this->page->requires->event_handler("#unassign_user_".$user->id, 'click', 'M.util.moodle_show_user_confirm_dialog',
					// 										array(
					// 										'message' => $confirmationmsg,
					// 										'callbackargs' => array('planid' =>$planid, 'userid' =>$user->id)
					// 									));
					// 	/*This query amd condition is used to check the completed users should not be deleted*/
					// 	$check=$this->db->get_record('local_learningplan_user',array('userid'=>$user->id,'status'=>1,'planid'=>$planid));
					// 	if($check){
					// 	$actions = $unassign_link1;
					// 	}else{
					// 	$actions = $unassign_link;
					// 	}
						
					// 	$table_header = get_string('learning_plan_actions', 'local_learningplan');
					// }else{
					// 	$actions = '';
					// 	$table_header = '';
					// }
			   		
	                $table_row = array();
					$table_row[] = $user_profile_link;
					$table_row[] = $employee_id;
					$table_row[] = $supervisorname;
					$table_row[] = '<i class="fa fa-calendar pr-10" aria-hidden="true"></i>'.$start_date;
					$table_row[] = $completion_date;
					$table_row[] = $status;
					// if (has_capability('local/learningplan:assignhisusers', $systemcontext)) {
					// 	if(empty($actions)){
					// 		$actions="N/A";
					// 	}
					// 	$table_row[] = $actions;
					// }
					$icon = '<i class="icon fa fa-download" aria-hidden="true"></i>';
					if($user->completiondate){
						$certcode = $DB->get_field('tool_certificate_issues', 'code', array('moduleid'=>$planid,'userid'=>$user->id,'templateid'=>$certid,'moduletype'=>'learningplan'));
            			$array = array('code' =>$certcode);$url = new moodle_url('/admin/tool/certificate/view.php', $array);
						$downloadlink = html_writer::link($url, $icon, array('title'=>get_string('download_certificate','local_certificates')));
					}else{
						$downloadlink = get_string('notassigned','local_classroom');
					}
					$table_row[] = $downloadlink;

					$table_data[] = $table_row;
				}
				$sql="SELECT count(lu.id) as total FROM {local_learningplan_user} as lu JOIN {user} u ON u.id = lu.userid WHERE lu.planid = $planid AND u.deleted=0 AND u.suspended=0 ";
				if ( $requestData['search']['value'] != "" )
				{
					$sql .= " and ((CONCAT(u.firstname, ' ',u.lastname) LIKE '%".$requestData['search']['value']."%'))";
				}
				$iTotal = $DB->get_field_sql($sql) ;
				$iFilteredTotal = $iTotal;  // when there is no search parameter then total number rows = total number filtered rows.
				$return = array(
					"sEcho" => intval($requestData['sEcho']),
					"iTotalRecords" => $iTotal,
					"iTotalDisplayRecords" => $iFilteredTotal,
					"aaData" => $table_data);
		}
		return $return;
	}
	
	public function assigned_learningplans_courses_employee_view($planid, $userid,$condition){
		global $CFG,$DB;
		require_once($CFG->dirroot.'/local/learningplan/lib.php');
		if(file_exists($CFG->dirroot.'/local/includes.php')){
			require_once($CFG->dirroot.'/local/includes.php');
		}
		
        $systemcontext = context_system::instance();
		

		$learningplan_lib = new lib();
		$includes = new user_course_details;
		
		$courses = lib::get_learningplan_assigned_courses($planid);
		$return = '';
		if(empty($courses)){
			$return .= html_writer::tag('div', get_string('nolearningplancourses', 'local_learningplan'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
		}else{
			$table_data = array();
            foreach($courses as $course){
				/**************To show course completed or not********/
				$sql="select id from {course_completions} as cc where userid=".$this->user->id." and course=".$course->id." and timecompleted!=''";
			   
				$completed=$this->db->get_record_sql($sql);
			    
				$course_url = new moodle_url('/course/view.php', array('id'=>$course->id));
				$course_view_link = html_writer::link($course_url, $course->fullname, array());
				//$course_summary_image_url = $includes->course_summary_files($course);
				$course_summary_image_url = course_thumbimage($course);
				$course_summary = empty($course->objective) ? 'Course Summary not provided' : $course->summary;
				$course_objective = empty($course->objective) ? 'Course Objective not provided' : $course->objective;
				$course_total_activities = $includes->total_course_activities($course->id);
				$course_total_activities_link = html_writer::link($course_url, $course_total_activities, array());
				$course_completed_activities = $includes->user_course_completed_activities($course->id, $userid);
				$course_completed_activities_link = html_writer::link($course_url, $course_completed_activities, array());
				$course_pending_activities = $course_total_activities - $course_completed_activities;
				$course_pending_activities_link = html_writer::link($course_url, $course_pending_activities, array());
				
				$actions = '';
				$buttons = '';
				/*Select box*/
				if($course->next=='or'){ $select='selected';}else{
								$select='';
				}/*condition for the select the dropdown if already selected*/
				/*Select box*/
				if($course->next=='or' || $course->next=='and'){			
							
					if($course->next=='and'){
						$buttons .='<h4 class="course_sort_status"><span class="label label-default mandatory-course" >Mandatory</span></h4>';
					}
					elseif($course->next=='or'){
						$buttons .='<h4 class="course_sort_status"><span class="label label-default optional-course" >Optional</span></h4>';
					}		
				}
				/*End of the select box*/
				if (has_capability('local/learningplan:assigncourses', $systemcontext)) {
					if($condition=='view'){
						
					}else{
					
					$unassign_url = new moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid, 'unassigncourse' => $course->id));
					$unassign_link = html_writer::link($unassign_url,
													   html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign'))
													   , array(
															   'class' => 'pull-right',
															   'id' => 'unassign_course_'.$course->id.''));
					$confirmationmsg = get_string('unassign_courses_confirm','local_learningplan', $course);
						
					$this->page->requires->event_handler("#unassign_course_".$course->id, 'click', 'M.util.moodle_show_course_confirm_dialog',
														array(
														'message' => $confirmationmsg,
														'callbackargs' => array('planid' =>$planid, 'courseid' =>$course->id)
													));
					$actions = $unassign_link;
					}
				}
				
				
				
				
                $table_row = array();
				$course_data = '';
				if($course->sortorder == 0){/*Condtion to set the enable to first sortorder*/
					$disable_class1 = ' '; /*Empty has been sent to class*/
				}
				
				$course_data .= '<div class="course_complete_info row-fluid pull-left '.$disable_class1.'" id="course_info_'.$course->id.'">';
					$course_data .= '<h4>'.$course_view_link.$actions.''.$buttons.'</h4>';
				if($course->sortorder!==''){/*Condition to check the sortorder and disable the course */
					
					/**** Function to get the all the course details like the nextsetoperator,sortorder
					@param planid,sortorder,courseid of the record
					****/
					$disable_class = $learningplan_lib->get_previous_course_status($planid,$course->sortorder,$course->id);
					$find_completion=$learningplan_lib->get_completed_lep_users($course->id,$planid);
					
						 
					if($disable_class->nextsetoperator!=''){/*condition to check not empty*/
			        
						if($disable_class->nextsetoperator=='and' && $find_completion==''){/*Condition to check the nextsetoperator*/
						$restricted= $DB->get_field('local_learningplan','lpsequence',array('id'=>$planid));
						
						if($restricted) {
							if($course->sortorder>=$disable_class->sortorder){/*Condition to cehck the sortorder and make all the disable*/
								$disable_class1='course_disabled';
							}	
						 }
						}
					}
				}
				/* End of the function and condition By Ravi_369*/
				
					$course_data .= '<div class="course_image_comtainer pull-left span3 desktop-first-column">
										<img class="learningplan_course_image" src="'.$course_summary_image_url.'" title="'.$course->fullname.'"/>
									</div>';
					$course_data .= '<div class="course_data_container pull-left span5 desktop-first-column">';
						$course_data .= '<div class="course_summary">';
							$course_data .= '<div class="clearfix">'.$course_summary.'</div>';
						$course_data .= '</div>';
					$course_data .= '</div>';
					$course_data .= '<div class="course_data_container pull-right col-md-4 desktop-first-column">';
						$course_data .= '<div class="course_activity_details text-right">';
							$course_data .= '<div class="row-fluid"><span style="font-size:18px;line-height:30px;">Activities to Complete : </span><span style="font-size:25px;">'.$course_total_activities_link.'</span></div>';
							$course_data .= '<div class="row-fluid"><span style="font-size:18px;line-height:30px;">Completed Activities : </span><span style="font-size:25px;">'.$course_completed_activities_link.'</span></div>';
							$course_data .= '<div class="row-fluid"><span style="font-size:18px;line-height:30px;">Pending Activities : </span><span style="font-size:25px;">'.$course_pending_activities_link.'</span></div>';
						$course_data .= '</div>';
					
				/********LAUNCH button for every courses to enrol********/
				/*First check the enrolment method*/
				$check_course_enrol=$this->db->get_field('enrol','id',array('courseid'=>$course->id,'enrol'=>'learningplan'));
				/***Then check the userid***/
				$find_user=$this->db->get_field('user_enrolments','id',array('enrolid'=>$check_course_enrol,'userid'=>$this->user->id));
				
				if(!$find_user){/*Condition to check the user enroled or not*/
				$plan_url = new moodle_url('/local/learningplan/index.php', array('courseid' => $course->id,'planid'=>$planid,'userid'=>$this->user->id));
				$detail = html_writer::link($plan_url, 'Launch', array('class'=>'launch'));
				}else{/*if already enroled then show enroled */
				if(!empty($completed)){
					$plan_url = "#";
				    $detail = html_writer::link($plan_url, 'Completed', array('class'=>'launch'));
					}else{	
						$plan_url = "#";
						$detail = html_writer::link($plan_url, 'Enrolled', array('class'=>'launch'));
					}
				}
				$course_data .=$cpmpleted_buttons;
				$course_data .= $detail;	
				$course_data .= '</div>';
				$course_data .= '</div>'; 	
				
				
				$table_row[] = $course_data;
				$table_data[] = $table_row;
			}
			$table = new html_table();
			$table->head = array('');
			$table->id = 'learning_plan_courses';
			$table->data = $table_data;
			$return .= html_writer::table($table);
			$return .= html_writer::script('$(document).ready(function(){
												//$("table#learning_plan_courses").dataTable({
													//language: {
													//	"paginate": {
													//		"next": ">",
													//		"previous": "<"
													//	  }
													//}
												//	"iDisplayLength": 3,
												//	"aLengthMenu": [[3, 10, 25, 50, -1], [3, 10, 25, 50, "All"]]
												//});
												//$("table#learning_plan_courses thead").css("display" , "none");
										   });');
		}
		
		return $return;
	}
public function assigned_learningplans_courses_browse_employee_view($planid, $userid,$condition){
		if(file_exists($CFG->dirroot.'/local/includes.php')){
			require_once($CFG->dirroot.'/local/includes.php');
		}
		
        $systemcontext = context_system::instance();
		
		$learningplan_lib = new local_learningplan\lib\lib();
		$includes = new user_course_details;
		
		$courses = lib::get_learningplan_assigned_courses($planid);
		
		$return = '';
		//$return .= html_writer::tag('h3', get_string('assigned_courses', 'local_learningplan'), array());
		if(empty($courses)){
			$return .= html_writer::tag('div', get_string('nolearningplancourses', 'local_learningplan'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
		}else{
			$table_data = array();
			/**********To disable the links before enrol to plan**********/
			$check=$this->db->get_record('local_learningplan_user',array('userid'=>$this->user->id,'planid'=>$planid));
			/*End of query*/
            foreach($courses as $course){
				
				if($check){
					$course_url = new moodle_url('/course/view.php', array('id'=>$course->id));
				}else{
					$course_url="#";
				}
				
				$course_view_link = html_writer::link($course_url, $course->fullname, array());
				//$course_summary_image_url = $includes->course_summary_files($course);
				$course_summary_image_url = course_thumbimage($course);
				$course_summary = empty($course->objective) ? 'Course Summary not provided' : strip_tags(html_entity_decode($course->summary),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
				$course_objective = empty($course->objective) ? 'Course Objective not provided' : $course->objective;
				$course_total_activities = $includes->total_course_activities($course->id);
				$course_total_activities_link = html_writer::link($course_url, $course_total_activities, array());
				$course_completed_activities = $includes->user_course_completed_activities($course->id, $userid);
				$course_completed_activities_link = html_writer::link($course_url, $course_completed_activities, array());
				$course_pending_activities = $course_total_activities - $course_completed_activities;
				$course_pending_activities_link = html_writer::link($course_url, $course_pending_activities, array());
				
				$actions = '';
				$buttons = '';
				/*Select box*/
				if($course->next=='or'){ $select='selected';}else{
								$select='';
				}/***condition for the select the dropdown if already selected***/
							
				if($course->next=='or' || $course->next=='and'){			
							
					if($course->next=='and'){
						$buttons .='<h4 class="course_sort_status"><span class="label label-default mandatory-course" >Mandatory</span></h4>';
					}
					elseif($course->next=='or'){
						$buttons .='<h4 class="course_sort_status"><span class="label label-default optional-course" >Optional</span></h4>';
					}		
				}
				/*End of the select box*/
				if (has_capability('local/learningplan:assigncourses', $systemcontext)) {
					if($condition=='view'){
						
					}else{
					
					$unassign_url = new moodle_url('/local/learningplan/assign_courses_users.php', array('planid' => $planid, 'unassigncourse' => $course->id));
					$unassign_link = html_writer::link($unassign_url,
													   html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign'))
													   , array(
															   'class' => 'pull-right',
															   'id' => 'unassign_course_'.$course->id.''));
					$confirmationmsg = get_string('unassign_courses_confirm','local_learningplan', $course);
						
					$this->page->requires->event_handler("#unassign_course_".$course->id, 'click', 'M.util.moodle_show_course_confirm_dialog',
														array(
														'message' => $confirmationmsg,
														'callbackargs' => array('planid' =>$planid, 'courseid' =>$course->id)
													));
					$actions = $unassign_link;
					}
				}
				
				
				
				
                $table_row = array();
				$course_data = '';
				if($course->sortorder == 0){/*Condtion to set the enable to first sortorder*/
					$disable_class1 = ' '; /*Empty has been sent to class*/
				}
				
				$course_data .= '<div class="course_complete_info row-fluid pull-left '.$disable_class1.'" id="course_info_'.$course->id.'">';
				$course_data .= '<h4>'.$course_view_link.$actions.''.$buttons.'</h4>';
					
				if($course->sortorder!==''){/*Condition to check the sortorder and disable the course */
					
					/**** Function to get the all the course details like the nextsetoperator,sortorder
					@param planid,sortorder,courseid of the record
					****/
					$disable_class = $learningplan_lib->get_previous_course_status($planid,$course->sortorder,$course->id);
					$find_completion=$learningplan_lib->get_completed_lep_users($course->id,$planid);
					
		           
						 
								if($disable_class->nextsetoperator!=''){/*condition to check not empty*/
						        
									if($disable_class->nextsetoperator=='and' && $find_completion==''){/*Condition to check the nextsetoperator*/
										
									$restricted= $DB->get_field('local_learningplan','lpsequence',array('id'=>$planid));
						          if($restricted) {
									if($course->sortorder>=$disable_class->sortorder){/*Condition to cehck the sortorder and make all the disable*/
										$disable_class1='course_disabled';
									}
								}


									
									}else{
						
									}
								}
				}
				/* End of the function and condition By Ravi_369*/
					
					$course_data .= '<div class="course_image_comtainer pull-left span3 desktop-first-column">
										<img class="learningplan_course_image" src="'.$course_summary_image_url.'" title="'.$course->fullname.'"/>
									</div>';
					$course_data .= '<div class="course_data_container pull-left span5 desktop-first-column">';
					$course_data .= '<div class="course_summary">';
					$course_data .= '<div class="clearfix">'.$course_summary.'</div>';
					$course_data .= '</div>';
					$course_data .= '</div>';
					$course_data .= '<div class="course_data_container pull-right col-md-4 desktop-first-column">';
					$course_data .= '<div class="course_activity_details text-right">';
					$course_data .= '<div class="row-fluid"><span style="font-size:18px;line-height:30px;">Activities to Complete : </span><span style="font-size:25px;">'.$course_total_activities_link.'</span></div>';
					$course_data .= '<div class="row-fluid"><span style="font-size:18px;line-height:30px;">Completed Activities : </span><span style="font-size:25px;">'.$course_completed_activities_link.'</span></div>';
					$course_data .= '<div class="row-fluid"><span style="font-size:18px;line-height:30px;">Pending Activities : </span><span style="font-size:25px;">'.$course_pending_activities_link.'</span></div>';
					$course_data .= '</div>';
				
				    $course_data .= $detail;	
				    $course_data .= '</div>';
				    $course_data .= '</div>';
				
				
				$table_row[] = $course_data;
				$table_data[] = $table_row;
			}
			
			$table = new html_table();
			$table->head = array('');
			$table->id = 'learning_plan_courses';
			$table->data = $table_data;
			$return .= html_writer::table($table);
			$return .= html_writer::script('$(document).ready(function(){
												//$("table#learning_plan_courses").dataTable({
													//language: {
													//	"paginate": {
													//		"next": ">",
													//		"previous": "<"
													//	  }
													//}
												//	"iDisplayLength": 3,
												//	"aLengthMenu": [[3, 10, 25, 50, -1], [3, 10, 25, 50, "All"]]
												//});
												//$("table#learning_plan_courses thead").css("display" , "none");
										   });');
		}
		
		return $return;
	}
	
	public function learningplaninfo_for_employee($planid){
		global $PAGE,$DB, $CFG,$USER;

		$learningplan_lib = new lib();
		$includeslib = new \user_course_details();
		$learningplan_classes_lib = new lib();
		
		$lplan = $this->db->get_record('local_learningplan', array('id'=>$planid));
		
		// $lptype = $lplan->learning_type == 1 ? 'Core Courses' : 'Elective Courses';
		/*if($lplan->learning_type == 1){
			$lptype = 'Core Courses';
		}elseif($lplan->learning_type == 2){
			$lptype = 'Elective Courses';
		}*/
		$lpapproval = $lplan->approvalreqd == 1 ? get_string('yes') : get_string('no');
		
		$lpimgurl = $learningplan_classes_lib->get_learningplansummaryfile($planid);
		
		$mandatarycourses_count = $learningplan_classes_lib->learningplancourses_count($planid, 'and');
		$optionalcourses_count = $learningplan_classes_lib->learningplancourses_count($planid, 'or');
		
		$lplanassignedcourses = lib::get_learningplan_assigned_courses($planid);
		
		$catalogrenderer = $this->page->get_renderer('local_catalog');
		$plandescription = strip_tags(html_entity_decode($lplan->description),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
        //$description_string = strlen($description) > 220 ? substr($description, 0, 220)."..." : $description;
        $description = $lplan->description;
        $descount = strlen($plandescription) > 350 ? true : false;
		$lpinfo = '';
		$condition="view";
		
		/***********The query Check Whether user enrolled to LEP or NOT**********/
		$plan_record = $this->db->get_record('local_learningplan', array('id' => $planid));
		$sql="select id from {local_learningplan_user} where planid=$planid and userid=".$this->user->id."";
		$check=$this->db->get_record_sql($sql);
		/*End of Query*/
		
		/**The Below query is check the approval status for the LOGIN USERS on the his LEP**/
		$check_approvalstatus=$this->db->get_record('local_learningplan_approval',array('planid'=>$plan_record->id,'userid'=>$this->user->id));
		if($check){ /**condition to check user already enrolled to the LEP If Enroled he get option enrolled **/
		
		if($check_approvalstatus->approvestatus==1){
			$back_url = "#";
		
		}else{
			$back_url ="#";
		}
		}else{/****Else he has 4 option like the Send Request or Waiting or Rejected or Enroled****/
		
		if(!is_siteadmin()){
		
		if($condition!='manage'){ /*******condition to check the manage page or browse page******/
		
		if($plan_record->approvalreqd==1  && (!empty($check_approvalstatus))) /***** If user has LEP with approve with 1 means request yes and empty not check approval status means he has sent request******/
		{
		
		$check_users= $learningplan_lib->check_courses_assigned_target_audience($this->user->id,$plan_record->id);
		/****The above Function is to check the user is present in the target audience or not***/
		
		if($check_users==1){/*if there then he will be shown the options*/
		
		$check_approvalstatus=$this->db->get_record('local_learningplan_approval',array('planid'=>$plan_record->id,'userid'=>$this->user->id));
		
		if($check_approvalstatus->approvestatus==0 && !empty($check_approvalstatus)){
		$back_url = "#";
		
		}elseif($check_approvalstatus->approvestatus==2 && !empty($check_approvalstatus)){
		$back_url = "#";
		}

		if(empty($check_approvalstatus)){
		
		$back_url = new moodle_url('/local/learningplan/plan_view.php',array('id'=>$plan_record->id,'enrolid'=>$plan_record->id));
		$notify = new stdClass();
		$notify->name = $plan_record->name;
		// $PAGE->requires->event_handler("#enroll1",
		// 'click', 'M.util.bajaj_show_confirm_dialog', array('message' => get_string('enroll_notify','local_learningplan',$notify),
		// 		 'callbackargs' => array('confirmdelete' =>$plan_record->id)));
		}
		}
		}else if(($plan_record->approvalreqd==1) && (empty($check_approvalstatus))){
			$check_users= $learningplan_lib->check_courses_assigned_target_audience($this->user->id,$plan_record->id);
			
		// if($check_users==1){
		// 	$back_url = new moodle_url('/local/learningplan/index.php', array('approval' => $plan_record->id));	
		// 	$approve=  html_writer::link('Send Request', array('class' => 'pull-right enrol_to_plan nourl','id'=>'request'));
		// 	$notify_info = new stdClass();
		// 	$notify_info->name = $plan_record->name;
		// 	$PAGE->requires->event_handler("#request",
		// 	'click', 'M.util.bajaj_show_confirm_dialog', array('message' => get_string('delete_notify','local_learningplan',$notify_info),
		// 			 'callbackargs' => array('confirmdelete' =>$plan_record->id)));
			
		// }
		}else if($plan_record->approvalreqd==0  && (empty($check_approvalstatus))){
		
		$back_url = new moodle_url('/local/learningplan/plan_view.php',array('id'=>$plan_record->id,'enrolid'=>$plan_record->id));
		$notify = new stdClass();
		$notify->name = $plan_record->name;
		// $PAGE->requires->event_handler("#enroll",
		// 'click', 'M.util.bajaj_show_confirm_dialog', array('message' => get_string('enroll_notify','local_learningplan',$notify),
		// 		 'callbackargs' => array('confirmdelete' =>$plan_record->id)));
		}
		}
		}
		}/** End of condtion **/
		if($lplan->learning_type == 1){
			$plan_type = 'Core Courses';
		}elseif($lplan->learning_type == 2){
			$plan_type = 'Elective Courses';
		}
		if(!empty($lplan->startdate)){
			$plan_startdate = date('d/m/Y', $lplan->startdate);
		}else{
			$plan_startdate = 'N/A';
		}
		if(!empty($lplan->enddate)){
			$plan_enddate = date('d/m/Y', $lplan->enddate);
		}else{
			$plan_enddate = 'N/A';
		}
		$pathcourses = '';
		if(count($lplanassignedcourses)>=2){
			$i = 1;
			$coursespath_context['pathcourses'] = array();
			foreach($lplanassignedcourses as $assignedcourse){
				$coursename = $assignedcourse->fullname;
				$coursespath_context['pathcourses'][] = array('coursename'=>$coursename, 'coursename_string'=>'C'.$i);
			$i++;
			if($i>10){
                    break;
            }

			}
			$pathcourses .= $this->render_from_template('local_learningplan/cousrespath', $coursespath_context);
		}
		$enrolled=$this->db->get_field('local_learningplan_user','id',array('userid'=>$this->user->id,'planid'=>$planid));
		$needenrol = $enrolled? false : true;
		$ratings_exist = \core_component::get_plugin_directory('local', 'ratings');
	    if($ratings_exist){
	        require_once($CFG->dirroot.'/local/ratings/lib.php');
	        $display_ratings .= display_rating($planid, 'local_learningplan');
	        $display_like .= display_like_unlike($planid, 'local_learningplan');
	        $display_like .= display_comment($planid, 'local_learningplan');
	        // $PAGE->requires->jquery();
	        // $PAGE->requires->js('/local/ratings/js/jquery.rateyo.js');
	        // $PAGE->requires->js('/local/ratings/js/ratings.js');
	    }else{
	        $display_ratings = $display_like = '';
	    }

	    if(!is_siteadmin()){
            $switchedrole = $USER->access['rsw']['/1'];
            if($switchedrole){
            	$userrole = $DB->get_field('role', 'shortname', array('id' => $switchedrole));
            }else{
            	$userrole = null;
            }
			if(is_null($userrole) || $userrole == 'user'){
				$core_component = new \core_component();
				$certificate_plugin_exist = $core_component::get_plugin_directory('tool', 'certificate');

			if($certificate_plugin_exist){
				if(!empty($lplan->certificateid)){
					$certificate_exists = true;
					$sql = "SELECT id 
							FROM {local_learningplan_user}
							WHERE planid = :planid AND userid = :userid
							AND status = 1 ";
					$completed = $DB->record_exists_sql($sql, array('userid'=>$USER->id,'planid' => $planid));
					 if($completed){
						$certificate_download= true;
					}else{
						$certificate_download = false;
					}

					$gcertificateid = $DB->get_field('local_learningplan', 'certificateid', array('id'=>$planid));
					$certificateid = $DB->get_field('tool_certificate_issues', 'code', array('moduleid'=>$planid,'userid'=>$USER->id,'templateid'=>$gcertificateid,'moduletype'=>'learningplan'));
}
			 }
		  }
            /* if(is_null($userrole) || $userrole == 'user'){
                	$core_component = new \core_component();
                	$certificate_plugin_exist = $core_component::get_plugin_directory('tool', 'certificate');

                if($certificate_plugin_exist){
	                if(!empty($lplan->certificateid)){
		                $certificate_exists = true;
		                $sql = "SELECT id 
		                        FROM {local_learningplan_user}
		                        WHERE planid = :planid AND userid = :userid
		                        AND status = 1 ";
		                $completed = $DB->record_exists_sql($sql, array('userid'=>$USER->id,'planid' => $planid));
		                 if($completed){
		                    $certificate_download= true;
		                }else{
		                    $certificate_download = false;
		                }
		                $certificateid = $lplan->certificateid;
	                	// $certificate_download['moduletype'] = 'learningplan';
	            	}
         		}
      		} */
  		}

  	   //start of progressbar
  	   $progress = $includeslib->user_course_completion_progress($course->id, $USER->id); 
	   if (!$progress) {
		 $progress = 0;
		 $progress_bar_width = " min-width: 0px;";
	    } else {
		 $progress = round($progress);
		 $progress_bar_width = "min-width: 0px;";
	    }
	    //end of progressbar
		
	    $lp_enrolledusers = $DB->record_exists('local_learningplan_user',array('planid' => $planid,'userid' => $USER->id));
        if($lp_enrolledusers){
            $lparams = array();
        	$lpusercoursecountsql = "SELECT COUNT(llc.courseid) as ccount
                                FROM {local_learningplan_courses} AS llc 
								JOIN {course} As c ON c.id = llc.courseid 
                                WHERE visible = 1 AND  llc.planid = :planid  AND llc.nextsetoperator = 'and' ";//
            $lparams['planid'] = $planid;
          
            $lp_user_coursecount = $DB->count_records_sql($lpusercoursecountsql,$lparams);
 
            $lp_params = array();
            $coursesql = "SELECT  cc.id,cc.course FROM {course_completions} AS cc 
                          JOIN {local_learningplan_courses} AS llc ON llc.courseid = cc.course
                          WHERE cc.userid = :userid AND llc.planid = :lplanid AND cc.timecompleted IS NOT NULL AND llc.nextsetoperator = 'and' ";//
            $lp_params['userid'] = $USER->id;
            $lp_params['lplanid'] = $planid;
            $coursecompletions = $DB->get_records_sql_menu($coursesql,$lp_params);
            $courseresult = count($coursecompletions);
          	if(!empty($lp_user_coursecount)){
		        $lp_user_completedcoursecount = round(($courseresult/$lp_user_coursecount)*100);
		    }
		  
		 }else{
		    $lp_user_completedcoursecount = 0;
		 } 
		 $lpprogress = $this->planpercent($planid);
		 if(!empty($lplan->department)){
            $depart=open::departments($lplan->department);
			$Dep=array();
			foreach($depart as $dep){
				$Dep[]=$dep->fullname;
			}
			$plan_department=implode(',',$Dep);
		}else{
			$plan_department = 'N/A';
		}
        
		$planview_context['plan_department_string'] = ($plan_department=='-1'||empty($plan_department))?'All':$plan_department;
			
		$plan_department = strlen($plan_department) > 23 ? substr($plan_department, 0, 23)."..." : $plan_department;
		if(empty($lplan->open_grade)){
			$plan_grade = 'N/A';
		}else{
			$plan_grade = $lplan->open_grade;
		}

		$totaluser_sql = "SELECT count(llu.userid)
							FROM {local_learningplan_user} as llu 
							JOIN {user} as u ON u.id=llu.userid 
							WHERE llu.planid = :planid AND u.deleted != :deleted ";
		$total_enroled_users=$this->db->count_records_sql($totaluser_sql, array('planid' => $planid, 'deleted' => 1));
		/*Count of the requested users to LEP*/
		$total_completed_users=$this->db->get_records_sql("SELECT id FROM {local_learningplan_user} WHERE completiondate IS NOT NULL
													 AND status = 1 AND planid = $planid");
		$cmpltd = array();
		foreach($total_completed_users as $completed_users){
			$cmpltd[] = $completed_users->id;
		}
		
        $lp_userview = array();
		$lp_userview['planid'] = $planid;
		$lp_userview['userid'] = $this->user->id;
		$lp_userview['needenrol'] = $needenrol;
		$lp_userview['lpname'] = $lplan->name;
		$lp_userview['lpimgurl'] = $lpimgurl;
		//$lp_userview['description_string'] = $description_string;
		$lp_userview['description_string'] = $description; 
		$lp_userview['descount'] = $descount;
		$lp_userview['plan_department'] = ($plan_department=='-1'||empty($plan_department))?'All':$plan_department;
		$lp_userview['lpcoursespath'] = $pathcourses;
		$lp_userview['plan_grade'] = $plan_grade;
		//$lp_userview['lptype'] = $lptype;
		$lp_userview['plan_learningplan_code'] = $lplan ->shortname?$lplan ->shortname:'NA';
		$lp_userview['lpapproval'] = $lpapproval;
		$lp_userview['plan_startdate'] = $plan_startdate;
		$lp_userview['plan_enddate'] = $plan_enddate;
		$lp_userview['lplancredits'] = $lplan->open_points;
		$lp_userview['mandatarycourses_count'] = $mandatarycourses_count;
		$lp_userview['optionalcourses_count'] = $optionalcourses_count;
		$lp_userview['display_ratings'] = $display_ratings;
		$lp_userview['display_like'] = $display_like;
		$lp_userview['certificate_exists'] = $certificate_exists;
		$lp_userview['certificate_download'] = $certificate_download;
		$lp_userview['certificateid'] = $certificateid;
		$lp_userview['progress'] = (is_nan($lpprogress)) ? 0 : $lpprogress;
		$lp_userview['total_lp_courses'] = $lp_user_coursecount;
		$lp_userview['completed_lp_courses'] = $courseresult;
		$lp_userview['total_enroled_users'] = $total_enroled_users;
		$lp_userview['cmpltd'] = count($cmpltd);
		
	    $challenge_exist = \core_component::get_plugin_directory('local', 'challenge');
        if($challenge_exist){
			$challenge_render = $PAGE->get_renderer('local_challenge');
			$element = $challenge_render->render_challenge_object('local_learningplan', $planid);
			$lp_userview['challenge_element'] = $element;
		}else{
			$lp_userview['challenge_element'] = false;
		}
		$lpinfo .= $this->render_from_template('local_learningplan/planview_user', $lp_userview);
	$test = '';
	$test .= '<div class="lp_course-wrapper w-100 pull-left">';
		if($lplanassignedcourses){
			$i=1;
			foreach($lplanassignedcourses as $assignedcourse){
				//$courseimgurl = $includeslib->course_summary_files($assignedcourse);
				$courseimgurl = course_thumbimage($assignedcourse);
				$lp_userviewcoures = array();
				$coursesummary = strip_tags(html_entity_decode($assignedcourse->summary),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
				$course_summary = empty($coursesummary) ? 'Course summary not provided' : $coursesummary;

 				$course_summary_string = strlen($course_summary) > 125 ? substr($course_summary, 0, 125)."..." : $course_summary;
				$c_category = $this->db->get_field('course_categories', 'name', array('id'=>$assignedcourse->category));
				
				$coursetypes = $this->db->get_field('local_coursedetails', 'identifiedas', array('courseid'=>$assignedcourse->id));
				if($coursetypes){
					$types = array();
					$ctypes = explode(',', $coursetypes);
					$identify = array();
					$identify['1'] = get_string('mooc');
					$identify['2'] = get_string('ilt');
					$identify['3'] = get_string('elearning');
					$identify['4'] = get_string('learningplan');
					foreach($ctypes as $ctype){
						$types[] = $identify[$ctype];
					}
				}
			    $coursepageurl = new \moodle_url('/course/view.php', array('id'=>$assignedcourse->id));
				if($assignedcourse->next == 'and'){
					$optional_or_mandtry = "<span class='mandatory' title = 'Mandatory'>M</span>";
				}else{
					$optional_or_mandtry = "<span class='optional' title = 'Optional'>OP</span>";
				}
				/**To make course link enable after the enrolled to lep**/
				$check=$this->db->get_field('local_learningplan_user','id',array('userid'=>$this->user->id,'planid'=>$planid));
				if($check){
                    
					
					$enrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'learningplan'));
					/**The three enrolment added bcos we need to get link in any of enrolment so.There was issues in production**/
					$selfenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'self'));
					$autoenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'auto'));
					$manualenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'manual'));
					$learningplanenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'learningplan'));
					
					$sql="SELECT id FROM {user_enrolments} WHERE userid={$this->user->id} AND enrolid IN ('$enrol','$selfenrol','$autoenrol','$manualenrol','$learningplanenrol')"; 
						
					$enrolledcourse=$this->db->get_field_sql($sql);
					
				$rname = format_string($assignedcourse->fullname);
				if($rname > substr(($rname),0,23)){
					$fullname = substr(($rname),0,23).'...';
				}else{
					$fullname =$rname; 
				}
				if($enrolledcourse){
					
				   $courseprovider = $DB->get_field('local_course_providers','shortname',array('id' => $assignedcourse->open_courseprovider));
				   if($courseprovider == 'percipio'){
				   	  $userlicense = plugin::verify_userlicence($USER->email);
				   	  if($userlicense == true){
				   	  	  $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
				   	  }else if($userlicense == false){
				   	  	   $courselink = html_writer::link('javascript:void(0)',$fullname, array('title' => $fullname, 'alt' => $fullname,'class'=>'coursesubtitle','onclick' =>'(function(e){ require("local_learningplan/courseenrol").percipiosync({selector:\'courseselfenrol'.$assignedcourse->id.'\', courseid:'.$assignedcourse->id.',enroll:1,coursename:\''.$fullname.'\' }) })(event)'));
				   	  }
				   } else if($courseprovider == 'udemy'){
				   	    $verification = new \local_udemysync\udemy_user_verification($USER->email);
				   	    $return = $verification->verify_userlicence(1);
				   	    if($return == true){
				   	       $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));	
				   	    }else if($userlicense == false){
				   	       $courselink = html_writer::link('javascript:void(0)',$fullname, array('title' => $fullname, 'alt' => $fullname,'class'=>'coursesubtitle','onclick' =>'(function(e){ require("local_learningplan/courseenrol").udemysync({selector:\'courseselfenrol'.$assignedcourse->id.'\', courseid:'.$assignedcourse->id.',enroll:1,coursename:\''.$fullname.'\' }) })(event)'));
				   	    }
				   } /*  else if($courseprovider == 'coursera'){
					$usercourseralicense = couseraplugin::verify_userlicence($USER->email);
					 if($usercourseralicense == true){
						   $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
					 } else if($usercourseralicense == false){
						   $courselink = html_writer::link('javascript:void(0)',$fullname, array('title' => $fullname, 'alt' => $fullname,'class'=>'coursesubtitle','onclick' =>'(function(e){ require("local_learningplan/courseenrol").coursetest({selector:\'courseselfenrol'.$assignedcourse->id.'\', courseid:'.$assignedcourse->id.',enroll:1,coursename:\''.$fullname.'\' }) })(event)'));
					 }
			    } */ else {
				   	  $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
				   }

				}else{
				/**Through course Link also user can enroll the course **/
				
				$coursepageurl = new moodle_url('/local/learningplan/index.php', array('courseid' => $assignedcourse->id,'planid'=>$lplan->id,'userid'=>$this->user->id));	

				//percipio + udemy sync
				
				$courseprovider = $DB->get_field('local_course_providers','shortname',array('id' => $assignedcourse->open_courseprovider));
				if($courseprovider == 'percipio'){
					 $userlicense = plugin::verify_userlicence($USER->email);
					   if($userlicense == true){
					   	    $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
					   }else if($userlicense == false){
                             $courselink = html_writer::link('javascript:void(0)',$fullname, array('title' => $fullname, 'alt' => $fullname,'class'=>'coursesubtitle','onclick' =>'(function(e){ require("local_learningplan/courseenrol").percipiosync({selector:\'courseselfenrol'.$assignedcourse->id.'\', courseid:'.$assignedcourse->id.',enroll:1,coursename:\''.$fullname.'\' }) })(event)'));
                        }
				} else if($courseprovider == 'udemy'){
                      $verification = new \local_udemysync\udemy_user_verification($USER->email);  
 					  $return = $verification->verify_userlicence(1);
 					  if($return == true){
 					  	  $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
 					  } else if($return == false){
 					  	  $courselink = html_writer::link('javascript:void(0)',$fullname, array('title' => $fullname, 'alt' => $fullname,'class'=>'coursesubtitle','onclick' =>'(function(e){ require("local_learningplan/courseenrol").udemysync({selector:\'courseselfenrol'.$assignedcourse->id.'\', courseid:'.$assignedcourse->id.',enroll:1,coursename:\''.$fullname.'\' }) })(event)'));
 					  }
 				} /* else if($courseprovider == 'coursera'){
					$usercourseralicense = couseraplugin::verify_userlicence($USER->email);
					 if($usercourseralicense == true){
						   $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
					 } else if($usercourseralicense == false){
						   $courselink = html_writer::link('javascript:void(0)',$fullname, array('title' => $fullname, 'alt' => $fullname,'class'=>'coursesubtitle','onclick' =>'(function(e){ require("local_learningplan/courseenrol").coursetest({selector:\'courseselfenrol'.$assignedcourse->id.'\', courseid:'.$assignedcourse->id.',enroll:1,coursename:\''.$fullname.'\' }) })(event)'));
					 }
			   } */ else {
					 $courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
				}

			    }
				}else{
				$rname = format_string($assignedcourse->fullname);
				if($rname > substr(($rname),0,23)){
				$fullname = substr(($rname),0,23).'...';
				}else{
				$fullname =$rname; 
				}	
				$coursepageurl="#";
				$courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
				}
				
				//$progressbar = $includeslib->user_course_completion_progress($assignedcourse->id,$this->user->id);
				$progressbar = \core_completion\progress::get_course_progress_percentage($assignedcourse, $USER->id);
				if(!$progressbar){
					$progressbarval = 0;
					$progress_bar_width = "min-width: 0px;";
				}else{
					$progressbarval = round($progressbar);
					$progress_bar_width = "min-width: 20px;";
				}
				/**To show course completed or not**/
		$sql="SELECT id,timecompleted FROM {course_completions} as cc WHERE userid=".$this->user->id." and course=".$assignedcourse->id." and timecompleted!=''";

		$completed=$this->db->get_record_sql($sql);
				/**LAUNCH button for every courses to enrol**/
				/*First check the enrolment method*/
				$sql="SELECT id,id AS id_val FROM {enrol} WHERE courseid = $assignedcourse->id";
				$get_data=$this->db->get_records_sql_menu($sql);
				$data=implode(',',$get_data);
				
				/**This below query is used to check the user already enroled to course with other enrolments methods**/
				$sql="SELECT id FROM {user_enrolments} WHERE enrolid IN($data) and userid=".$this->user->id."";
				$find_user=$this->db->record_exists_sql($sql) ;

				/***Then check the userid***/

				if(!$find_user){/*Condition to check the user enroled or not*/
					$plan_url = new \moodle_url('/local/learningplan/index.php', array('courseid' => $assignedcourse->id,'planid'=>$lplan->id,'userid'=>$this->user->id));
					$launch = html_writer::link($plan_url, 'Launch', array('class'=>'btn btn-sm btn-info pull-right btn-enrol btm-btn '));
				}else{/*if already enroled then show enroled */
					if(!empty($completed)){
						$plan_url = new \moodle_url('/course/view.php', array('id' => $assignedcourse->id));
						$launch = html_writer::link($plan_url, 'Launch', array('class'=>'btn btn-sm btn-info pull-right btn-enrol btm-btn'));
					}else{
						$plan_url = new \moodle_url('/course/view.php', array('id' => $assignedcourse->id));
						$launch = html_writer::link($plan_url, 'Launch', array('class'=>'btn btn-sm btn-info pull-right btn-enrol btm-btn'));
					}
				}
				$course_data = '';
				if($assignedcourse->sortorder == 0){/*Condtion to set the enable to first sortorder*/
				$disable_class1 = ' '; /*Empty has been sent to class*/
				}else{
					$disable_class1 = ' ';
				}
	 		if($progressbarval==100){
				$cmpltd_class = 'course_completed';
				$cmpltd_flag = true;
					if($completed->timecompleted){
						$completiondate = date("j M 'Y",$completed->timecompleted);
					}else{
						//$completed_date = '';
						$completiondate = '';
					}
			}else{
				$cmpltd_class = '';
				$completiondate ='';
				$cmpltd_flag = false;
			}
			 if($assignedcourse->next == 'and'){
	    	   $lp_precompletion_msg = get_string('lp_mandatory_course_completion_msg','local_learningplan');
	         }else if($assignedcourse->next == 'or'){
	    	   $lp_precompletion_msg = get_string('lp_optional_course_completion_msg','local_learningplan');
	         }
		if($assignedcourse->sortorder>0&&$assignedcourse->next=='and'){/*Condition to check the sortorder and disable the course */
			/**** Function to get the all the course details like the nextsetoperator,sortorder
			@param planid,sortorder,courseid of the record
			****/
			$disable_class = $learningplan_classes_lib->get_previous_course_status($planid,$assignedcourse->sortorder,$assignedcourse->id);
			if($disable_class){
				$disable_class1="";
			}else{
				$restricted= $DB->get_field('local_learningplan','lpsequence',array('id'=>$planid));
				if($restricted) {
						$disable_class1='course_disabled';
				}
			}

		}else{
			$disable_class1="";
		}
			$enroldisable_class1 = 'enrolled';
			if($needenrol){
				$enroldisable_class1='not_enrolled course_disabled';	
			}
			$lp_userviewcoures['disable_class1'] = $disable_class1;
			$lp_userviewcoures['needenrol'] = $needenrol;
			$lp_userviewcoures['enroldisable_class1'] = $enroldisable_class1;
			$lp_userviewcoures['cmpltd_class'] = $cmpltd_class;
			$lp_userviewcoures['progressbar'] = (is_nan($progressbarval)) ? 0 : $progressbarval;
			$lp_userviewcoures['courseimgurl'] = $courseimgurl;
			$lp_userviewcoures['courselink'] = $courselink;
			$lp_userviewcoures['completiondate'] = $completiondate;
			$lp_userviewcoures['optional_or_mandtry'] = $optional_or_mandtry;
			$lp_userviewcoures['course_summary_string'] = $course_summary_string;
			$lp_userviewcoures['lp_course_completion_msg'] = $lp_precompletion_msg;
			
			/**To disable the The status like Launch || Enrolled || Completed || before enrol to plan**/
			$check=$this->db->get_field('local_learningplan_user','id',array('userid'=>$this->user->id,'planid'=>$planid));
			/*End of query*/
		$test .= $this->render_from_template('local_learningplan/planview_usercourses', $lp_userviewcoures);
			}
		}
		$test .= '</div>';
		$lpinfo .= $test;
		return $lpinfo;
	}

	public function lpathinfo_for_employee($planid){
        global $PAGE,$DB, $CFG,$USER;

        $learningplan_lib = new lib();
        $includeslib = new \user_course_details();
        $learningplan_classes_lib = new lib();

        $lplan = $this->db->get_record('local_learningplan', array('id'=>$planid),'*',MUST_EXIST);

        $lpimgurl = $learningplan_classes_lib->get_learningplansummaryfile($planid);
        $mandatarycourses_count = $learningplan_classes_lib->learningplancourses_count($planid, 'and');
        $optionalcourses_count = $learningplan_classes_lib->learningplancourses_count($planid, 'or');
        $lplanassignedcourses = lib::get_learningplan_assigned_courses($planid);

    
        $description = $lplan->description;
        $lpinfo = '';
        if($lplan->learning_type == 1){
            $plan_type = 'Core Courses';
        }elseif($lplan->learning_type == 2){
            $plan_type = 'Elective Courses';
        }
        if(!empty($lplan->startdate)){
            $plan_startdate = date('d/m/Y', $lplan->startdate);
        }else{
            $plan_startdate = 'N/A';
        }
        if(!empty($lplan->enddate)){
            $plan_enddate = date('d/m/Y', $lplan->enddate);
        }else{
            $plan_enddate = 'N/A';
        }
        $pathcourses = '';
        if(count($lplanassignedcourses)>=2){
            $i = 1;
            $coursespath_context['pathcourses'] = array();
            foreach($lplanassignedcourses as $assignedcourse){
                $coursename = $assignedcourse->fullname;
                $coursespath_context['pathcourses'][] = array('coursename'=>$coursename, 'coursename_string'=>'C'.$i);
                $i++;
                if($i>10){
                    break;
                }
            }
            $pathcourses .= $this->render_from_template('local_learningplan/cousrespath', $coursespath_context);
        }
		$enrolled=$this->db->get_field('local_learningplan_user','id',array('userid'=>$this->user->id,'planid'=>$planid));
		$ratings_exist = \core_component::get_plugin_directory('local', 'ratings');
	    if($ratings_exist){
	        require_once($CFG->dirroot.'/local/ratings/lib.php');
	        $display_ratings .= display_rating($planid, 'local_learningplan');
	        $display_like .= display_like_unlike($planid, 'local_learningplan');
	        $display_like .= display_comment($planid, 'local_learningplan');
	    }else{
	        $display_ratings = $display_like = '';
	    }

	    if(!is_siteadmin()){
            $switchedrole = $USER->access['rsw']['/1'];
            if($switchedrole){
                $userrole = $DB->get_field('role', 'shortname', array('id' => $switchedrole));
            }else{
                $userrole = null;
            }
        }
        $lp_userview = array();
        $lp_userview['planid'] = $planid;
        $lp_userview['userid'] = $this->user->id;
        $enrolled = $DB->record_exists('local_learningplan_user',array('planid'=>$planid, 'userid'=>$USER->id));
        $selfenrol_check =  $DB->get_field('local_learningplan', 'selfenrol', array('id' => $planid));
        if(!is_siteadmin() && !$enrolled && $selfenrol_check){
            $lp_userview['needenroluser'] = true;
        }

		$lp_userview['component'] = $component = 'learningplan';
		$lp_userview['action'] = 'add';
		if($lplan->approvalreqd==1){
			$requestsql = "SELECT status FROM {local_request_records} 
				WHERE componentid = :componentid AND compname LIKE :compname AND 
				createdbyid = :createdbyid ORDER BY id DESC ";
			$request = $DB->get_field_sql($requestsql ,array('componentid' => $planid,'compname' => $component,'createdbyid'=>$USER->id));
			
            if($request=='PENDING'){
            	$lp_userview['pending'] = true;
             }else{
				$lp_userview['requestbtn'] = true;
			}
		}else{
			$lp_userview['requestbtn'] = false;
		}

        $lp_userview['lpname'] = $lplan->name;
        $lp_userview['lpimgurl'] = $lpimgurl;
        $lp_userview['description_string'] = $description;
        $lp_userview['lpcoursespath'] = $pathcourses;
        $lp_userview['plan_learningplan_code'] = $lplan ->shortname ? $lplan ->shortname:'NA';
        $lp_userview['mandatarycourses_count'] = $mandatarycourses_count;
        $lp_userview['optionalcourses_count'] = $optionalcourses_count;
        $lp_userview['display_ratings'] = $display_ratings;
        $lp_userview['display_like'] = $display_like;
        $lp_userview['lplancredits'] = ($lplan->open_points > 0) ? $lplan->open_points : 'N/A';
        $challenge_exist = \core_component::get_plugin_directory('local', 'challenge');
        if($challenge_exist){
            $challenge_render = $PAGE->get_renderer('local_challenge');
            $element = $challenge_render->render_challenge_object('local_learningplan', $planid);
            $lp_userview['challenge_element'] = $element;
        }else{
            $lp_userview['challenge_element'] = false;
        }
        $lpinfo .= $this->render_from_template('local_learningplan/lpathview_user', $lp_userview);
        $test = '';
        $test .= '<div class="lp_course-wrapper w-100 pull-left">';
        if($lplanassignedcourses){
            $i = 1;
            foreach($lplanassignedcourses as $assignedcourse){
                $courseimgurl = $includeslib->course_summary_files($assignedcourse);
                $lp_userviewcoures = array();
                $coursesummary = strip_tags(html_entity_decode($assignedcourse->summary),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
                $course_summary = empty($coursesummary) ? 'Course summary not provided' : $coursesummary;
                $course_summary_string = strlen($course_summary) > 125 ? substr($course_summary, 0, 125)."..." : $course_summary;
                    if($assignedcourse->next == 'and'){
                        $optional_or_mandtry = "<span class='mandatory' title = 'Mandatory'>M</span>";
                    }else{
                        $optional_or_mandtry = "<span class='optional' title = 'Optional'>OP</span>";
                    }
                    
                    $rname = format_string($assignedcourse->fullname);
                    if($rname > substr(($rname),0,23)){
                        $fullname = substr(($rname),0,23).'...';
                    }else{
                        $fullname =$rname; 
                    }
                $course_name_string = strlen($fullname) > 125 ? substr($fullname, 0, 125)."..." : $fullname;
                $enroldisable_class1 = 'enrolled';
                $lp_userviewcoures['enroldisable_class1'] = $enroldisable_class1;
                $lp_userviewcoures['courseimgurl'] = $courseimgurl;
                $lp_userviewcoures['courselink'] = $course_name_string;
                $lp_userviewcoures['optional_or_mandtry'] = $optional_or_mandtry;
                $lp_userviewcoures['course_summary_string'] = $course_summary_string;
                $test .= $this->render_from_template('local_learningplan/lpathcourse', $lp_userviewcoures);
            }
        }
        $test .= '</div>';
        $lpinfo .= $test;

        return $lpinfo;
	}

	private function planpercent($planid){

        global $DB,$USER;

        $sql = "SELECT c.* FROM {local_learningplan_courses} as lpc inner join {course} as c on c.id= lpc.courseid where lpc.planid=:planid AND lpc.nextsetoperator ='and'";

        $params =array("planid"=>$planid);

        $courses = $DB->get_records_sql($sql,$params);
		

        $coursescount= count($courses);

            $lp_params = array();

            $coursesql = "SELECT  cc.id,cc.course FROM {course_completions} AS cc

                          JOIN {local_learningplan_courses} AS llc ON llc.courseid = cc.course

                          WHERE cc.userid = :userid AND llc.planid = :lplanid  AND cc.timecompleted IS NOT NULL AND llc.nextsetoperator ='and' ";

            $lp_params['userid'] = $USER->id;

            $lp_params['lplanid'] = $planid;

            $coursecompletions = $DB->get_records_sql_menu($coursesql,$lp_params);

            $completetedcoursecount = count($coursecompletions);
            if(!empty($coursescount)){
            	$complete_percent = (($completetedcoursecount/$coursescount) * 100);
            }
           

            if($complete_percent > 0){

                return round($complete_percent);

            }else{

                return 0;

            }

        // return $completetedcoursecount/$coursescount * 100;

    }



}
?>
