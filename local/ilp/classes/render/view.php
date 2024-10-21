<?php
namespace local_ilp\render;
use context_module;
use local_ilp\lib\lib as lib;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use context_system;
use html_writer;
use html_table;
use moodle_url;
use ilp;
use plugin_renderer_base;
use user_course_details;
//use open;
if(file_exists($CFG->dirroot . '/local/includes.php')){
	require_once($CFG->dirroot . '/local/includes.php');
}
class view extends plugin_renderer_base {
    private $lid; /*Learning Plan id*/
    
   // private $userid;
    
    function __construct(){
        global $DB, $CFG, $OUTPUT, $USER, $PAGE;
        $this->db=$DB;
		$this->context = context_system::instance();
		$this->output=$OUTPUT;
		$this->page=$PAGE;
		$this->cfg=$CFG;
		$this->user=$USER;
		
    }
    
    public function all_ilps($condtion,$dataobj,$tableenable=false,$search=null){
        $systemcontext = $this->context;
        $userid = $this->user->id;
		if(($tableenable)){
			$start=$dataobj->start;
			$length=$dataobj->length;			
		}else{
			$start = 0;
			$length = 0;
		}
	
		$data=open::userdetails();
		
		$sql="SELECT l.* 
				FROM {local_ilp} AS l
				WHERE l.userid = {$this->user->id}";
		// if($data->open_costcenterid) {
		// 	$sql .= ' AND FIND_IN_SET('.$data->open_costcenterid.',l.costcenter) ';
		// }
		// if($data->open_group) {
		// 	$sql .= ' AND FIND_IN_SET('.$data->open_group.',l.open_group) ';
		// }
		// if($data->open_departmentid) {
		// 	$sql .= ' AND FIND_IN_SET('.$data->open_departmentid.',l.department) ';
		// }
		// if($data->open_subdepartment) {
		// 	$sql .= ' AND FIND_IN_SET('.$data->open_subdepartment.',l.subdepartment) ';
		// }

		if(!empty($search)){
			$sql .= " AND l.name LIKE '%%$search%%'";
		}
		$sql .= ' ORDER BY l.timemodified DESC';
		// if(($tableenable)){
		// 	$sql .= " LIMIT $start,$length";
		// }
		
		$ilps_depwise = $this->db->get_records_sql($sql, array(), $start, $length);

		$ilps = $ilps_depwise;

        if(empty($ilps)){
           return html_writer::tag('div', get_string('noilps', 'local_ilp'), array('class' => 'alert alert-info text-center w-100 pull-left mt-15'));
        }else{
            $sdata = array();
            $table_data = array();
            
            foreach($ilps as $ilp){
                $row = array();
                $ilp_url = new \moodle_url('/local/ilp/plan_view.php', array('id' => $ilp->id));
                if(empty($ilp->credits)){
                    $plan_credits = 'N/A';
                }else{
                    $plan_credits = $ilp->credits;
                }
				if(empty($ilp->usercreated)){
					$plan_usercreated = 'N/A';
				}else{
					$plan_usercreated = $ilp->usercreated;
					$user = $this->db->get_record_sql("SELECT id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename FROM {user} WHERE id = :plan_usercreated", array('plan_usercreated' => $plan_usercreated));
					$created_user = fullname($user);
				}
                if($ilp->learning_type == 1){
                    $plan_type = 'Core Courses';
                }elseif($ilp->learning_type == 2){
                    $plan_type = 'Elective Courses';
                }else{
                	$plan_type = 'N/A';
                }
                if(!empty($ilp->location)){
                    $plan_location = $ilp->location;
                }else{
                    $plan_location = 'N/A';
                }
				if(!empty($ilp->department)){
                    
                    $plan_departments= open::departments($ilp->department);
					$plan_department = array();
					foreach($plan_departments as $plan_dep){
						$plan_department[] = $plan_dep->fullname;
					}
					$plan_department = implode(',', $plan_department);
					// $str_len = strlen($fullname);
					$plan_department_string = strlen($plan_department) > 23 ? substr($plan_department, 0, 23)."..." : $plan_department;
                }else{
                    $plan_department = 'N/A';
                }
				if(!empty($ilp->subdepartment)){
                    $plan_subdepartments=open::departments($ilp->subdepartment);
					$plan_subdepartment = array();
					foreach($plan_subdepartments as $plan_subdep) {
						$plan_subdepartment[] = $plan_subdep->fullname;
					}
					$fullname = implode(',', $plan_subdepartment);
					$str_len = strlen($fullname);
					if($str_len > 32){
						$sub_str = substr($fullname, 0, 32);
						$plan_subdepartment = $sub_str.'<a class="toggle_subdepartment_'.$ilp->id.' view_more_toggle" onclick="target_audience_toggle(\'subdepartment\','.$ilp->id.')" ><span class="view_more">&nbsp...View more</span><span class="hidden view_less">&nbsp...View less</span></a>';
						$plan_subdepartment .= '<div class="toggle_subdepartment_content_'.$ilp->id.' view_more_toggle_content" style="display:none;right:0px;"><span onclick="target_audience_toggle(\'subdepartment\','.$ilp->id.')" class="view_more_close">x</span><span class="view_more_content">'.$fullname.'</span></div>';
					}else{
						$plan_subdepartment = $fullname;
					}
                }else{
                    $plan_subdepartment = 'N/A';
                }
                $action_icons = '';
				$capability1 = true;
            	$capability2 = true;
            	$capability3 = true;

                if(is_siteadmin()){
					
				}else{
					
				}
                $lplanassignedcourses = lib::get_ilp_assigned_courses($ilp->id);
      			// print_object($lplanassignedcourses);
                // $completedcoursescount = array();
                $courses = array();
                foreach($lplanassignedcourses as $assignedcourse){
                	$courses[] = $assignedcourse->id;
                }
                $test = lib::ilpCompletionProgress($ilp->id, $this->user->id);
                // print_object($courses);
                if(empty($courses)){
                	$courses = array(1);
                }
            	$assignedcourses = implode(',', $courses);
        	
                $sql = "SELECT count(id) from {course_completions} WHERE course in ($assignedcourses) AND userid = $userid AND timecompleted IS NOT NULL "; 
                $totalassignedcourses =  count($lplanassignedcourses);	
                $completedcourses = $this->db->count_records_sql($sql);
				$ilp_completion_percent = lib::ilpCompletionProgress($ilp->id, $this->user->id);
					
				if($ilp_completion_percent == 0){
					$ilpstatus = get_string('yettostart',  'local_ilp');
					$ilpstatusclass = '';
				}else if($ilp_completion_percent == 100){
					$ilpstatus = get_string('completed',  'local_ilp');
					$ilpstatusclass = 'completed';
				}else{
					$ilpstatus = get_string('inprogress',  'local_ilp');
					$ilpstatusclass = 'inprogress';
				}
                $ilp_content = array();
                if($ilp->courseid == -1){
                	$name = $ilp->comment;
                }else{
                	$name = $$DB->get_field('course', 'fullname',array('id'=> $ilp->courseid));
                }

                $ilp_name = strlen($ilp->name) > 30 ? substr($ilp->name, 0, 30)."..." : $ilp->name;
                $description = strip_tags(html_entity_decode($ilp->description));
                $ilpdescription = strlen($description) > 150 ? substr($description, 0, 150)."..." : $description;
                $hide_show_icon = $ilp->visible ? $this->output->image_url('i/hide') : $this->output->image_url('i/show');
                $title_hide_show = $ilp->visible ? 'hide' : 'show';
                $visibleilp = $ilp->visible ? '' : 'usersuspended';
                $ilp_content['ilp_url'] = $ilp_url;
                $ilp_content['ilp_name'] = $name;
                $ilp_content['description'] = $description;
                $ilp_content['ilpdescription'] = $ilpdescription;
                $ilp_content['capability1'] = $capability1;
                $ilp_content['capability2'] = $capability2;
                $ilp_content['capability3'] = $capability3;
                $ilp_content['hide'] = $ilp->visible ? true : false;
                $ilp_content['hide_show_icon_url'] = $hide_show_icon;
                $ilp_content['title_hide_show'] = $title_hide_show;
                $ilp_content['visibleilp'] = $visibleilp;
                $ilp_content['delete_icon_url'] = $this->output->image_url('i/delete');
                
                $ilp_content['edit_icon_url'] = $this->output->image_url('i/edit');
                $ilp_content['ilpid'] = $ilp->id;
                $ilp_content['ilpstatus'] = $ilpstatus;
                $ilp_content['ilpstatusclass'] = $ilpstatusclass;
                $ilp_content['lpcoursescount'] = count($lplanassignedcourses);
                $ilp_content['completedcourses'] = $completedcourses;
                $ilp_content['ilp_completion_percent'] = $ilp_completion_percent;
                $ilp_content['ilpincompleteclass'] = $ilpincompleteclass;
                $row[] = $this->render_from_template('local_ilp/learninngplan_index_view', $ilp_content);
                $sdata[] = implode('', $row);
				// $table_data[] = $row;
				
            }

            $lpchunk = array_chunk($sdata,2);
	            $chunk = array(""); 
	 	
	            if(isset($lpchunk[count($lpchunk)-1]) && count($lpchunk[count($lpchunk)-1])!=2) { 

	                 if(count($lpchunk[count($lpchunk)-1])==1) { 

	                 	$lpchunk[count($lpchunk)-1] = array_merge($lpchunk[count($lpchunk)-1],$chunk,$chunk); 
	                 }else{  
	                    $lpchunk[count($lpchunk)-1]=array_merge($lpchunk[count($lpchunk)-1],$chunk); 
	                } 
	            }

            if($tableenable){
                $iTotal = count($assigned_users); 
                $iFilteredTotal = $iTotal;
                             
                return $output = array(
                "sEcho" => intval($requestData['sEcho']),
                "iTotalRecords" => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData" => $lpchunk
                );
			}

				
            $table = new html_table();
            $table->id = 'all_ilps';
            $table->head = array('','');
            $table->data = $lpchunk;
            $return = html_writer::table($table);
			$return .= html_writer::script('$(document).ready(function(){
												$("#all_ilps").DataTable({
												    "serverSide": true,
												    "language": {
														paginate: {
															"previous": "<",
															"next": ">"
														},
														  "search": "",
                    									  "searchPlaceholder": "Search"
													},
													"ajax": "ajax.php?manage=1",
													"datatype": "json",
													"pageLength": 8,
													
												});
												$("table#all_ilps thead").css("display" , "none");
												$("#all_ilps_length").css("display" , "none");
										   });');
            $return .= '';
        }
        return $return;
    }

    public function ilpBannerContent() {
    	global $OUTPUT;
    	$sql="SELECT count(l.id) 
				FROM {local_ilp} AS l
				WHERE l.userid = {$this->user->id}";
		$ilps = $this->db->count_records_sql($sql);

		$sql = "SELECT count(llp.id) 
				FROM {local_ilp} AS llp
				WHERE llp.userid = {$this->user->id} AND completiondate IS NULL
				AND llp.visible = 1 AND 0 < (SELECT count(cc.id) 
												FROM {course_completions} AS cc 
												JOIN {course} AS c ON c.id = cc.course 
												JOIN {local_ilp_courses} AS ilc ON ilc.courseid = c.id 
												WHERE ilc.planid = llp.id)";

		$inprogressilps = $this->db->count_records_sql($sql);		

		$completedilps = $this->db->count_records_sql("SELECT count(id) from {local_ilp} 
														WHERE userid={$this->user->id} 
														AND completiondate is NOT NULL AND visible=1");


		if ($completedilps > 0){
			$ilp_completion_percent = floor($completedilps/($ilps)*100);
		}else{
			$ilp_completion_percent = 0;
		}

		if($ilp_completion_percent == 0){
			$ilpstatus = get_string('yettostart',  'local_ilp');
			$ilpstatusclass = '';
		}else if($ilp_completion_percent == 100){
			$ilpstatus = get_string('completed',  'local_ilp');
			$ilpstatusclass = 'completed';
		}else{
			$ilpstatus = get_string('inprogress',  'local_ilp');
			$ilpstatusclass = 'inprogress';
		}
		$total_comp_inprogress = $inprogressilps+$completedilps;
		$yettostart = $ilps-$total_comp_inprogress;
		$bannercontent['inprogresscount'] = $inprogressilps;
		$bannercontent['completedcount'] = $completedilps;
		$bannercontent['yettostart'] = $yettostart;
		$bannercontent['ilpstatus'] = $ilpstatus;
		$bannercontent['ilpstatusclass'] = $ilpstatusclass; 
		$bannercontent['ilp_completion_percent'] = $ilp_completion_percent; 

		return  $this->render_from_template('local_ilp/bannercontent', $bannercontent);
    }
    public function single_plan_view($planid){
		$ilp_lib = new lib();

		$lpimgurl = $ilp_lib->get_ilpsummaryfile($planid);	
		$plan_record = $this->db->get_record('local_ilp', array('id' => $planid));
		$plan_description = !empty($plan_record->description) ?  strip_tags(html_entity_decode($plan_record->description),array('overflowdiv' => false, 'noclean' => false, 'para' => false)) : 'No Description available';
		$plan_objective = !empty($plan_record->objective) ? $plan_record->objective : 'No Objective available';
		/*Count of the enrolled users to LEP*/	
		// $total_enroled_users=$this->db->get_record_sql('SELECT u.id,count(llu.userid) as data 
		// 												FROM {local_ilp_user} as llu 
		// 												JOIN {user} as u ON u.id=llu.userid 
		// 												WHERE llu.planid='.$planid.' AND 
		// 												u.deleted != 1' );
		/*Count of the requested users to LEP*/
		$total_completed_users=$this->db->get_records_sql("SELECT id FROM {local_ilp_user} WHERE completiondate IS NOT NULL
													 AND status = 1 AND planid = $planid");
		$cmpltd = array();
		foreach($total_completed_users as $completed_users){
			$cmpltd[] = $completed_users->id;
		}
		
		/*Count of the courses of LEP*/
		$total_assigned_course=$this->db->count_records('local_ilp_courses',array('planid'=>$planid));
		
		$total_mandatory_course=$this->db->get_records_sql("SELECT id FROM {local_ilp_courses} WHERE planid = $planid
													 AND nextsetoperator = 'and'");
		$mandatory = array();
		foreach($total_mandatory_course as $total_mandatory){
			$mandatory[] = $total_mandatory->id;
		}
		
		$total_optional_course=$this->db->get_records_sql("SELECT id FROM {local_ilp_courses} WHERE planid = $planid
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
		if(empty($plan_record->credits)){
			$plan_credits = 'N/A';
		}else{
			$plan_credits = $plan_record->credits;
		}
		if(empty($plan_record->usercreated)){
			$plan_usercreated = 'N/A';
		}else{
			$plan_usercreated = $plan_record->usercreated;
			$user = $this->db->get_record_sql("select * from {user} where id = $plan_usercreated");
			$created_user = fullname($user);
		}
		if($plan_record->learning_type == 1){
			$plan_type = 'Core Courses';
		}elseif($plan_record->learning_type == 2){
			$plan_type = 'Elective Courses';
		}
		$plan_needapproval = '';
		if(!empty($plan_record->open_group)){
			$plan_location = $plan_record->open_group;
			$str_len = strlen($plan_record->open_group);
			if($str_len > 32){
				$sub_str = substr($plan_record->open_group, 0, 32);
				$plan_location = $sub_str.'<a class="emp_band_'.$plan_record->id.' view_more_toggle" onclick="target_audience_toggle(\'emp_band\','.$plan_record->id.')" ><span class="view_more">&nbsp...View more</span><span class="hidden view_less">&nbsp...View less</span></a>';
				$plan_location .= '<div class="emp_band_content_'.$plan_record->id.' view_more_toggle_content" style="display:none;"><span onclick="target_audience_toggle(\'emp_band\','.$plan_record->id.')" class="view_more_close">x</span><span class="view_more_content">'.$plan_record->open_group.'</span></div>';
			}
		}else{
			$plan_location = 'N/A';
		}
		/*code reverted -- starts here*/
		if(!empty($plan_record->department)){
			//$sql="select fullname from {local_costcenter} where id IN ($plan_record->department)";
			//$depart=$this->db->get_records_sql($sql);
			//print_object($depart);
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
			//$sql="select fullname from {local_costcenter} where id IN ($plan_record->subdepartment)";
			//$depart=$this->db->get_records_sql($sql);
			//print_object($depart);
            $depart=open::departments($plan_record->subdepartment);
			$Dep='';
			foreach($depart as $dep){
				$Dep[]=$dep->fullname;
			}
			$plan_subdepartment=implode(',',$Dep);
			//$plan_subdepartment = $this->db->get_field('local_costcenter', 'fullname', array('id' => $plan_record->subdepartment));
			$str_len = strlen($plan_subdepartment);
			if($str_len > 32){
				$sub_str = substr($plan_subdepartment, 0, 32);
				$substr_subdepartment = $sub_str.'<a class="toggle_subdepartment_'.$plan_record->id.' view_more_toggle" onclick="target_audience_toggle(\'subdepartment\','.$plan_record->id.')" ><span class="view_more">&nbsp...View more</span><span class="hidden view_less">&nbsp...View less</span></a>';
				$substr_subdepartment .= '<div class="toggle_subdepartment_content_'.$plan_record->id.' view_more_toggle_content" style="display:none;"><span onclick="target_audience_toggle(\'subdepartment\','.$plan_record->id.')" class="view_more_close">x</span><span class="view_more_content">'.$plan_subdepartment.'</span></div>';
				$plan_subdepartment = $substr_subdepartment;
			}
		}else{
			$plan_subdepartment = 'N/A';
		}
		$lplanassignedcourses = $ilp_lib->get_ilp_assigned_courses($planid);
		$pathcourses = '';
			if($lplanassignedcourses) {
				$i = 1;
				$coursespath_context['pathcourses'] = array();
				foreach($lplanassignedcourses as $assignedcourse){
					if(count($lplanassignedcourses)>=2){
						$coursename = $assignedcourse->fullname;
						// $coursename_string = strlen($coursename) > 6 ? substr($coursename, 0, 6)."..." : $coursename;

						$coursespath_context['pathcourses'][] = array('coursename'=>$coursename, 'coursename_string'=>'C'.$i);
					}
					$i++;
				}
				$pathcourses .= $this->render_from_template('local_ilp/cousrespath', $coursespath_context);
			}
			$description = strip_tags(html_entity_decode($plan_record->description));
        	$description_string = strlen($description) > 220 ? substr($description, 0, 220)."..." : $description;
            $planview_context = array();
            //print_object($plan_record);exit;
            $planview_context['lpnameimg'] = $lpimgurl; 
            $planview_context['lpname'] = $plan_record->name;
            $planview_context['lpcoursespath'] = $pathcourses;
            $planview_context['description'] = $description_string; 
			$planview_context['plan_type'] = $plan_type;
			$planview_context['plan_needapproval'] = $plan_needapproval;
			$planview_context['plan_credits'] = $plan_credits;
			$planview_context['created_user'] = $created_user;
			$planview_context['total_assigned_course'] = $total_assigned_course;
			$planview_context['mandatory'] = count($mandatory);
			$planview_context['optional'] = count($optional);
			
			
			$planview_context['plan_department_string'] = ($plan_department=='-1'||empty($plan_department))?'All':$plan_department;
			
			$plan_department = strlen($plan_department) > 23 ? substr($plan_department, 0, 23)."..." : $plan_department;
			$planview_context['plan_department'] = ($plan_department=='-1'||empty($plan_department))?'All':$plan_department;
			$planview_context['enddate'] = date('d M, Y', $plan_record->enddate);
			$planview_context['plan_subdepartment'] = $plan_subdepartment;
			$planview_context['plan_location'] = $plan_location;
			// $planview_context['total_enroled_users'] = $total_enroled_users->data;
			// $planview_context['cmpltd'] = count($cmpltd);
		
		return $this->render_from_template('local_ilp/lp_planview', $planview_context);
	}
  /***************Function For The Tabs View In The Learning
	@param $id=LEP id && $curr_tab=tab name
	Plan*****************/
	public function plan_tabview($id,$curr_tab,$condition){
		
			
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
		
		$total_enroled_users=$this->db->get_record_sql('SELECT count(llu.userid) as data  FROM {local_ilp_user} as llu JOIN {user} as u ON u.id=llu.userid WHERE llu.planid='.$id.' AND u.deleted!=1');
		$total_assigned_course=$this->db->count_records('local_ilp_courses',array('planid'=>$id));
		$return = '';
		// $tabs = '<div id = "ilptabs">
		// 			<ul class="nav nav-tabs nav-justified w-full pull-left" role="tablist">
		// 				';
						
		// 		$tabs .= '</ul>
		// 		</div>';
		//not displaying tabs here
		$tabs .= '<div class="tab-content" id="ilptabscontent">';
		$tabs .= $this->ilps_courses_tab_content($id, $curr_tab,$condition);
		$tabs .= '</div>';
		return $tabs;
	}
    
	/***********Function to view of course tab
	$planid=LEP_id $curr_tab="tab name"
	***************/
	public function ilps_courses_tab_content($planid, $curr_tab,$condition){
		
        $systemcontext = context_system::instance();
		
		$return ='';
		
		// $active_courses = ' ';
		// if($curr_tab == 'courses'){
		// 	$active_courses = 'active in';
		// }
		$return .='<div class="tab-pane active mt-15 ml-15" id="plan_courses" role="tabpanel">';
		// if (has_capability('local/ilp:assigncourses', $systemcontext)) {
			$return .= $this->ilps_assign_courses_form($planid,$condition);
		// }
		$return .='';
		$return .= '<div class="ilp_courses_list col-md-11 m-auto">'.$this->assigned_ilps_courses($planid).'</div>';
		$return .='';
		$return .= '</div>';
		return $return;
	}
    public function ilps_target_audience_content($planid, $curr_tab,$condition) {
        global $OUTPUT, $CFG, $DB,$USER;
              $data = $DB->get_record_sql('SELECT id, open_group, open_hrmsrole,
             open_designation, open_location,department
             FROM {local_ilp} WHERE id = ' .$planid);
            
            if($data->department==-1||$data->department==NULL){
                $department=get_string('audience_department','local_ilp','All');
            }else{
                 $departments = $DB->get_field_sql("SELECT GROUP_CONCAT(fullname)  FROM {local_costcenter} WHERE id IN ($data->department)");
                 $department=get_string('audience_department','local_ilp',$departments);
            }
            if(empty($data->open_group)){
                 $group=get_string('audience_group','local_ilp','All');
            }else{
                 $groups = $DB->get_field_sql("SELECT GROUP_CONCAT(name) FROM {cohort} WHERE id IN ($data->open_group)");
                 $group=get_string('audience_group','local_ilp',$groups);
            }
            
            $data->open_hrmsrole =(!empty($data->open_hrmsrole)) ? $hrmsrole=get_string('audience_hrmsrole','local_ilp',$data->open_hrmsrole) :$hrmsrole=get_string('audience_hrmsrole','local_ilp','All');
            
            $data->open_designation =(!empty($data->open_designation)) ? $designation=get_string('audience_designation','local_ilp',$data->open_designation) :$designation=get_string('audience_designation','local_ilp','All');
            
            $data->open_location =(!empty($data->open_location)) ? $location=get_string('audience_location','local_ilp',$data->open_location) :$location=get_string('audience_location','local_ilp','All');
            
             return '<div class="tab-pane active mt-15 ml-15" id="plan_courses" role="tabpanel">'.$department.$group.$hrmsrole.$designation.$location.'</div>';
    }
    /******************Function to tab view of bulk users uploads
	$planid=LEP_id $curr_tab="tab name"
	***************/ 
	public function ilps_bulk_users_tab_content($planid, $designation, $department,$empnumber,$organization,$email,$band,$subdepartment,$sub_subdepartment){
		
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
					//$trainerid_exist=$this->db->get_record_sql("SELECT * FROM {local_ilp_user} where userid=$select_from_user->id and planid=$planid");	
				}
				if((empty($trainerid_exist))){
					$symbol="";
					$check=$this->db->get_record('local_ilp_user',array('userid'=>$select_from_user->id,'status'=>1,'planid'=>$planid));
					if($check){
						$disable="disabled";
						$title="title='User Completed'";
					}else{
						$title="";
						$disable="";
					}
					$data_id=preg_replace("/[^0-9,.]/", "", $select_from_user->idnumber);
					$return .= "<option value=$select_from_user->id $disable $title>$symbol $select_from_user->firstname $select_from_user->lastname ($data_id)</option>";		
					//$PAGE->requires->event_handler('#eventconfirm'.$select_from_user->id .'', 'click', 'tmahendra_show_confirm_dialog', array('message' => get_string('deleteconfirm','local_custom_repository'), 'callbackargs' => array('confirmid' =>$select_from_user->id))); 
				}
			}
			foreach($select_from_users as $select_from_user){
				//$return .= '<input type="hidden" name="planidss" value=' . $select_from_user->id . ' />';
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
					//$trainerid_exist=$this->db->get_record_sql("SELECT * FROM {local_ilp_user} where userid=$select_from_user->id and planid=$planid");	
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
		$courses=$this->db->get_records('local_ilp_courses',array('planid'=>$planid));
		
		if($courses){/*If courses it self not assignes so to check condition*/
			$table = 'local_ilp_courses'; ///name of table
			$conditions = array('planid'=>$planid); ///the name of the field (key) and the desired value
			$sort = 'id';
			$fields = 'id, courseid'; 
			$result = $this->db->get_records_menu($table,$conditions,$sort,$fields);
            $count=count($result);
			/*finally get the count of records in total courses*/
			$data=implode(',',$result);
			$sql="select * from {enrol} where courseid IN ($data) and enrol='ilp'";
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
	/*End of the function commented by Ravi_369*/
	
	/******Function to called in the bulk users upload
	$planid=LEP_id 
	*******/
	public function select_from_users_of_learninplan($planid,$userid,$params){

		$sql="SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')',' ','(',u.open_employeeid,')') as fullname FROM {user} u WHERE u.id >1 AND u.deleted=0 AND u.suspended=0 ";
		if($planid!=0){
			$batch_users=$this->db->get_fieldset_sql("SELECT userid FROM {local_ilp_user} WHERE planid=$planid");
		}else{
			$batch_users=$this->db->get_fieldset_sql("SELECT userid FROM {local_ilp_user}");  
		}
		array_push($batch_users, 1);
		$batch_userss = implode(',',$batch_users);
		if(!empty($batch_userss)){
			$sql .=' AND u.id in(' . $batch_userss . ')';
		}
		
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
                 $group_list = $this->db->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']})");
                 
                
                 if (!empty($group_list)){
                     $groups_members = implode(',', $group_list);
                     $sql .=" AND u.id IN ({$groups_members})";
                 }else{
                    $sql .=" AND u.id =0";
                 }
        }

		$users=$this->db->get_records_sql_menu($sql);
		
		return $users;
	}
	/*End of the function*/
    
	/*Function to called in the bulk users upload*/
	public function select_to_users_of_learninplan($planid, $userid,$params){
		
		$users = $this->db->get_record('local_ilp',array('id'=>$planid));
		$us = $users->open_band;
		$array=explode(',',$us);
		$list=implode("','",$array);
		$loginuser= $this->user;
		$systemcontext = context_system::instance();
		if(!is_siteadmin()){
			$siteadmin_sql=" AND u.suspended =0
								 AND u.deleted =0  AND u.open_costcenterid = $users->costcenter ";
		}else{
			$siteadmin_sql="";
		}
		
		$sql = "SELECT  u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')',' ','(',u.open_employeeid,')') as fullname FROM {user} u WHERE u.id >2 $siteadmin_sql AND u.id not in ($loginuser->id) ";
		
		if(!empty($empnumber)){
			if($empnumber !=='null' && $empnumber !=='-1'){
				$sql.= " AND u.id IN({$empnumber})"; 
			}
		}
		if (( !is_siteadmin() && ( !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                $sql .= " AND u.open_costcenterid = :costcenter";
                $params['costcenter'] = $this->user->open_costcenterid;
                if ($users->department !== null && $users->department !== '-1'&& $users->department !== 0 &&(has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
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
                 $group_list = $this->db->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']})");
                 
                
                 if (!empty($group_list)){
                     $groups_members = implode(',', $group_list);
                     $sql .=" AND u.id IN ({$groups_members})";
                 }else{
                    $sql .=" AND u.id =0";
                 }
        }

		if($planid!=0){
			$batch_users=$this->db->get_fieldset_sql("SELECT userid FROM {local_ilp_user} WHERE planid=$planid");
		}else{
			$batch_users=$this->db->get_fieldset_sql("SELECT userid FROM {local_ilp_user}");  
		}
		array_push($batch_users, 1);
		$batch_userss = implode(',',$batch_users);
		if(!empty($batch_userss)){
			$sql .=' AND u.id not in(' . $batch_userss . ')';
		}
	
		$users=$this->db->get_records_sql_menu($sql,$params);
		return $users;
	}
	/*End of the function*/
	
	
    
    
	/*Function to view the users and assign users*/
	public function ilps_users_tab_content($planid, $curr_tab,$condition){
		global $CFG,$OUTPUT;
        $systemcontext = context_system::instance();
		
		$return = '';
		
		$return .= '<div class="tab-pane" id="plan_users" role="tabpanel">';
		if (has_capability('local/ilp:assignhisusers', $systemcontext)) {
			/*to check courses has the Learning plan enrolment or not*/
			// $courses=$this->db->get_records('local_ilp_courses',array('planid'=>$planid));
			// if($courses){
				$table = 'local_ilp_courses'; ///name of table
				$conditions = array('planid'=>$planid); ///the name of the field (key) and the desired value
				$sort = 'id';
				$fields = 'id, courseid'; 
				$result = $this->db->get_records_menu($table,$conditions,$sortid,$fields);
				$count=count($result);
				/*finally get the count of records in total courses*/
				$data=implode(',',$result);
				// $sql="SELECT id from {enrol} where courseid IN ($data) and enrol='ilp'";
				// $check=$this->db->get_records_sql($sql);
				// $check_count=count($check);
				/*get the enrol records according to course*/
				// if($check_count == $count){
					/********The Below query written to check the all coures have the condition if two courses has condition 0
					then while completion cron runs gets error to avoid we should make them to submit*******/
					// $courses_zero_count=$this->db->get_records('local_ilp_courses',array('planid'=>$planid,'nextsetoperator'=>0));
					// if(count($courses_zero_count)==1 || count($courses_zero_count)==0){
						// $return.='<a href="'.$CFG->wwwroot.'/local/ilp/lpusers_enroll.php?lpid='.$planid.'" class="show">
						// 				<span class="pull-right knowmore assigning button">Assign Users</span></a>';
						$return .= "<ul class='course_extended_menu_list ilp'>
			                 <li>
  								<div class='coursebackup course_extended_menu_itemcontainer'>
                           <a id='extended_menu_syncusers' title='".get_string('sync_users', 'local_users')."' class='course_extended_menu_itemlink' href='" . $CFG->wwwroot ."/local/ilp/lpusers_enroll.php?lpid=".$planid."'><i class='icon fa fa-users fa-fw' aria-hidden='true' aria-label=''></i></a>
                      	</div>
                      </li></ul>";				
						//$return .= $this->ilps_assign_users_form($planid,$condition);
					// }else{
					// 	$return .='Please apply Learning plan Condtion to all course please submit button in the coures tab';	
					// }
				// }else{
				// 	$return .='Please apply Learning plan enrollment to all courses';
				// }
			// }
		}
		$return .= $this->assigned_ilps_users($planid);
		$return .= '</div>';
		
		return $return;
	}
	/*End of the function*/

	/*Function to view the requested users in ilp*/
	public function ilps_requested_users_content($planid, $curr_tab,$condition){
		global $DB,$CFG,$OUTPUT,$PAGE;
        $systemcontext = context_system::instance();
		
		$return = '';
		if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
		 $ilp = $DB->get_records('local_request_records', array('compname' =>'ilp','componentid'=>$planid));
        $output = $PAGE->get_renderer('local_request');
        $component = 'ilp';
        if($ilp){
            $return = $output->render_requestview(new \local_request\output\requestview($ilp, $component));
        }else{
        	$return = '<div class="alert alert-info">'.get_string('requestavail', 'local_classroom').'</div>';
        }
    }
		
		return $return;
	}
	/*End of the function*/
	
	public function ilps_assign_courses_form($planid,$condition){
		global $DB;
		$systemcontext = context_system::instance();
		$plan_name = $DB->get_field('local_ilp', 'name', array('id' => $planid));
		$ilp_lib = new lib;
		$userscount = $ilp_lib->get_enrollable_users_count_to_ilp($planid);
		$return = '';
		$add_ilpcourses = '<ul class="course_extended_menu_list ilp">
							    		<li>
									        <div class="course_extended_menu_itemcontainer">
									            <a title="" class="course_extended_menu_itemlink" href="javascript:void(0);"
									            	onclick="(function(e){ require(\'local_ilp/courseenrol\').init({selector:\'createcourseenrolmodal\', contextid:'.$systemcontext->id.', planid:'.$planid.', condition:\'manage\'}) })(event)">
									            	<i class="icon fa fa-plus" aria-hidden="true"></i>
									            </a>
									        </div>
									    </li>
									</ul>';

		$return .= $add_ilpcourses;
		$return .= '<div class="assign_courses_container">';
		
		$courses = $ilp_lib->ilp_courses_list($planid);

		$return .= '</div>';
		
		return $return;
	}
	public function get_editand_publish_icons($planid){
		global $DB, $CFG;
		$plan_name = $DB->get_field('local_ilp', 'name', array('id' => $planid));
		$ilp_lib = new lib;
		$userscount = $ilp_lib->get_enrollable_users_count_to_ilp($planid);


		$ilpinfo['plan_name'] = $plan_name;
		$ilpinfo['planid'] = $planid;
		$ilpinfo['userscount'] = $userscount;
		$ilpinfo['configpath'] = $CFG->wwwroot;
		$edit_publish_icons = $this->render_from_template('local_ilp/ilp_publish_edit', $ilpinfo);
		return $edit_publish_icons;
	}
	
	private function ilps_assign_users_form($planid,$condition){
		
		//require_once($CFG->dirroot.'/local/ilp/lib.php');
		//require_once($CFG->dirroot.'/local/filterclass.php');
		
		//$filter_class = new \custom_filter(); 
		//$users = $filter_class->get_all_users_id_fullname($planid);/*Filter page added this function*/
		
		$sql = "SELECT userid, planid FROM {local_ilp_user} WHERE planid = $planid";
		$existing_plan_users = $this->db->get_records_sql($sql);
		$return = '';
			$assign_button = '<a class="pull-right assigning " onclick="assign_users_form_toggle('.$planid.')" id="plan_assign_users_'.$planid.'">'.get_string('assign_users', 'local_ilp').'</a>';
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
												<select name="ilp_users[]" id="id_lpassignusers" size="10" multiple class="ilp-assign-users">';
					
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
	/****Function to view the  course and functionality with the sortorder @param $planid=LEP_id****/
	public function assigned_ilps_courses($planid){

        $systemcontext = context_system::instance();
		$ilp_lib = new lib();

		$includes = new \user_course_details;
		
		$courses = lib::get_ilp_assigned_courses($planid);
		
		$return = '';
		$return .= '<form action="assign_courses_users.php" method="post">';

		if(empty($courses)){
			$return .= html_writer::tag('div', get_string('noilpcourses', 'local_ilp'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
		}else{
			
			$table_data = array();
			/*******To check the highest sortorder of courses below query written and to compare list of courses*******/
			$sql = "Select id,sortorder 
					FROM {local_ilp_courses} 
					WHERE planid = $planid 
					ORDER BY sortorder DESC ";
					
		 	$find= $this->db->get_record_sql($sql);
			/****End of the query****/
			
			/*************Below query written to check the users assigned to LEP or NOT and Disable submit button************************/
			$userscount=$this->db->get_record('local_ilp_user',array('planid'=>$planid));
			/*end of query*/
			
			/******* The below query has been written taken count if we have submitted condition and later we added new course then submit should open************/
			$courses_zero_count=$this->db->get_records('local_ilp_courses',array('planid'=>$planid,'nextsetoperator'=>0));
			/*end of query*/
			
			
			
			if($userscount && (count($courses_zero_count)==1 || count($courses_zero_count)==0)){
				$disbaled_button="disabled";
			}else{
				$disbaled_button="";
			}

			/*List of courses making list of course*/
			$i=1;
			$lpcourse_data = '';
            foreach($courses as $course){
				
				if($course->next=='and'){
					//echo "checked";
					$select='echo checked="checked"';
					
				}elseif($course->next=='or'){
					$select='';
				}
				
				$startdiv ='<div class="lp_course_sortorder w-full pull-left" id="dat'.$course->id.'">';
				$enddiv='<div>';
				$course_url = new \moodle_url('/course/view.php', array('id'=>$course->id));
				$course_link = strlen($course->fullname) > 28 ? substr($course->fullname, 0, 28)."..." : $course->fullname;
				$course_view_link = html_writer::link($course_url, $course_link, array());
				$course_summary_image_url = $includes->course_summary_files($course);
				
				$coursesummary = strip_tags(html_entity_decode($course->summary));
				$course_summary = empty($coursesummary) ? 'Course summary not provided' : $coursesummary;

            	 $course_summary_string = strlen($course_summary) > 125 ? substr($course_summary, 0, 125)."..." : $course_summary;

				$course_total_activities = $includes->total_course_activities($course->id);
				$course_total_activities_link = html_writer::link($course_url, $course_total_activities, array());
				
				$actions = '';/****actions like delete and move up and down****/
				$buttons= ''; /****buttons are select box****/
				
				// if (has_capability('local/ilp:assigncourses', $systemcontext)) {
					
					$unassign_url = new \moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid, 'unassigncourse' => $course->lepid));
					$unassign_link = html_writer::link('javascript:void(0)',
						'<i class="icon fa fa-trash fa-fw" aria-hidden="true" title="Un-assign" aria-label="Delete"></i>', array('class' => 'pull-right','id' => 'unassign_course_'.$course->lepid.'', 'onclick' => '(function(e){ require(\'local_ilp/lpcreate\').unassignCourses({action:\'unassign_course\' , unassigncourseid:'.$course->lepid.', planid:'.$planid.', fullname:"'.$course->fullname.'" }) })(event)'));
					
													
					if($course->sortorder==0){ /****condtion to check the sortorder and make arrows of up and down
								    for the first record ot course*****/	
						
						$unassign_url1 = new \moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'down'));
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
						
						$unassign_url2 = new \moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'up'));
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
						
						$unassign_url2 = new \moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'up'));
						$unassign_link1 = html_writer::link($unassign_url2,'<i class="icon fas fa-arrow-up" title="Move Up"></i>', array('class' => 'pull-right'));
						
						$unassign_url2 = new \moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid,'instance' => $course->lepid, 'order' => 'down'));
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
					
							$confirmationmsg = get_string('unassign_courses_confirm','local_ilp', $course);
				     
							$actions .= $unassign_link;
				// }
				$progress = $includes->user_course_completion_progress($course->id ,$this->user->id);
					if (!$progress) {
						$progress = 0;
						$progress_bar_width = " min-width: 0px;";
					} else {
						$progress = round($progress);
						$progress_bar_width = "min-width: 0px;";
					}
                
				if($progress==100){
					$cmpltd_class = 'course_completed';
					$completedtime = $this->db->get_field('course_completions', 'timecompleted', array('course' => $course->id, 'userid' => $this->user->id));
					if($completedtime){
						$completed_date = date("j M 'Y",$completedtime);
					}else{
						$completed_date = '';
					}
					
				}else{
					$cmpltd_class = '';
					$completed_date = '';
				}

				//$lpcourse_data[] = array();
				// $lpcourse_data .= $startdiv;
			
				if($course->sortorder == 0){/*Condtion to set the enable to first sortorder*/
					$disable_class1 = ' '; /*Empty has been sent to class*/
				}
				// if($course->startdate){
				// 	$startdate = date('d/m/Y',$course->startdate);
				// }else{
				// 	$startdate = '-';
				// }
				$lpcourses_context['disable_class1'] = $disable_class1;
				$lpcourses_context['courseid'] = $course->id;
				$lpcourses_context['course_summary_image_url'] = $course_summary_image_url;
				$lpcourses_context['course_summary_string'] = $course_summary_string;
				$lpcourses_context['course_view_link'] = $course_view_link;
				$lpcourses_context['course_name'] = $course->fullname;
				$lpcourses_context['numbercount'] = $i++;
				$lpcourses_context['buttons'] = $buttons;
				$lpcourses_context['actions'] = $actions;
				// $lpcourses_context['submitbuttons'] = $submitbuttons;
				$lpcourses_context['progress'] = $progress;
				$lpcourses_context['date'] = $completed_date;
				$lpcourses_context['cmpltd_class'] = $cmpltd_class;



				$lpcourse_data .= $this->render_from_template('local_ilp/courestab_content', $lpcourses_context);
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
											url: M.cfg.wwwroot + '/local/ilp/ajax.php?course='+checkbox_value+'&planid='+plan+'&value='+value,
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
	public function assigned_ilps_users($planid){
		global $OUTPUT;
        $systemcontext = context_system::instance();
		$ilp_lib = new lib();
		$users = $ilp_lib->get_ilp_assigned_users($planid);
		
		$return = '';
		
		if(empty($users)){
			$return .= html_writer::tag('div', get_string('noilpusers', 'local_ilp'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
		}else{
			$table_data = array();
            foreach($users as $user){
				$course_url = new \moodle_url('/local/ilp/local_ilp_courses.php', array('planid'=>$planid,'id'=>$user->id));
				$courses_link = html_writer::link($course_url, 'View more', array('id'=>$user->id));
				if($user->status==1){
					$completed="Completed";
				}  
				$user_url = new \moodle_url('/local/users/profile.php', array('id'=>$user->id));
				$user_profile_link = html_writer::link($user_url, fullname($user), array());
				$start_date = empty($user->timecreated) ? 'N/A' : date('d M Y',$user->timecreated);
				$completion_date = empty($user->completiondate) ? 'N/A' : '<i class="fa fa-calendar pr-10" aria-hidden="true"></i>'.date('d M Y',$user->completiondate); 
				$status = empty($user->status) ? 'Not Completed' : $completed;
				
				if (has_capability('local/ilp:assignhisusers', $systemcontext)) {
					$unassign_url = new \moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid, 'unassignuser' => $user->id));
					$unassign_link = html_writer::link($unassign_url,
													html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign'))
													, array('id' => 'unassign_user_'.$user->id.''));
					$unassign_link = html_writer::link('javascript:void(0)',html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign')), array('id' => 'unassign_user_'.$user->id.'', 'onclick' => '(function(e){ require(\'local_ilp/lpcreate\').unassignUsers({action:\'unassign_user\' , unassignuserid:'.$user->id.', planid:'.$planid.', fullname:"'.fullname($user).'" }) })(event)'));
					
					if($completed=="Completed..."." ".$courses_link){
						$unassign_link1 = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/check'), 'class' => 'icon', 'title' => 'Completed'));
						$actions = $unassign_link;
					}
					$confirmationmsg = get_string('unassign_users_confirm','local_ilp', $user);
							
					$this->page->requires->event_handler("#unassign_user_".$user->id, 'click', 'M.util.moodle_show_user_confirm_dialog',
														array(
														'message' => $confirmationmsg,
														'callbackargs' => array('planid' =>$planid, 'userid' =>$user->id)
													));
					/*This query amd condition is used to check the completed users should not be deleted*/
					$check=$this->db->get_record('local_ilp_user',array('userid'=>$user->id,'status'=>1,'planid'=>$planid));
					if($check){
					$actions = $unassign_link1;
					}else{
					$actions = $unassign_link;
					}
					
					$table_header = get_string('ilp_actions', 'local_ilp');
				}else{
					$actions = '';
					$table_header = '';
				}
		   		
                $table_row = array();
				$table_row[] = $user_profile_link;
				$table_row[] = '<i class="fa fa-calendar pr-10" aria-hidden="true"></i>'.$start_date;
				$table_row[] = $completion_date;
				$table_row[] = $status;
				if (has_capability('local/ilp:assignhisusers', $systemcontext)) {
					$table_row[] = $actions;
				}
				
				$table_data[] = $table_row;
			}
			$table = new html_table();
			$table->id = 'ilp_users';
			$table->head = array(get_string('username', 'local_ilp'),
								 get_string('start_date', 'local_ilp'),
								 get_string('completion_date', 'local_ilp'),
								 get_string('ilp_status', 'local_ilp')
								 );
			if (has_capability('local/ilp:assignhisusers', $systemcontext)) {
				$table->head[] = get_string('ilp_actions', 'local_ilp');
			}
			$table->align = array('left', 'center', 'center', 'center', 'center');
			$table->data = $table_data;
			
			$return .= html_writer::table($table);
			// $return .= html_writer::script('$(document).ready(function(){
			// 									$("table#ilp_users").dataTable({
			// 										language: {
			// 											"paginate": {
			// 												"next": ">",
			// 												"previous": "<"
			// 											  },
			// 											  "search": "",
   //                  									  "searchPlaceholder": "Search"
			// 										}
			// 									});
			// 									//$("table#ilp_users thead").css("display" , "none");
			// 							   });');
		}
		
		return $return;
	}
	
	public function assigned_ilps_courses_employee_view($planid, $userid,$condition){
		global $CFG;
		require_once($CFG->dirroot.'/local/ilp/lib.php');
		require_once($CFG->dirroot.'/local/includes.php');
		
        $systemcontext = context_system::instance();
		

		$ilp_lib = new lib();
		$includes = new user_course_details;
		
		$courses = lib::get_ilp_assigned_courses($planid);
		$return = '';
		if(empty($courses)){
			$return .= html_writer::tag('div', get_string('noilpcourses', 'local_ilp'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
		}else{
			$table_data = array();
            foreach($courses as $course){
				/**************To show course completed or not********/
				$sql="select id from {course_completions} as cc where userid=".$this->user->id." and course=".$course->id." and timecompleted!=''";
			   
				$completed=$this->db->get_record_sql($sql);
			    
				$course_url = new moodle_url('/course/view.php', array('id'=>$course->id));
				$course_view_link = html_writer::link($course_url, $course->fullname, array());
				$course_summary_image_url = $includes->course_summary_files($course);
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
				if (has_capability('local/ilp:assigncourses', $systemcontext)) {
					if($condition=='view'){
						
					}else{
					
					$unassign_url = new moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid, 'unassigncourse' => $course->id));
					$unassign_link = html_writer::link($unassign_url,
													   html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign'))
													   , array(
															   'class' => 'pull-right',
															   'id' => 'unassign_course_'.$course->id.''));
					$confirmationmsg = get_string('unassign_courses_confirm','local_ilp', $course);
						
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
					//$course_data .=	$buttons;
				if($course->sortorder!==''){/*Condition to check the sortorder and disable the course */
					
					/**** Function to get the all the course details like the nextsetoperator,sortorder
					@param planid,sortorder,courseid of the record
					****/
					$disable_class = $ilp_lib->get_previous_course_status($planid,$course->sortorder,$course->id);
					$find_completion=$ilp_lib->get_completed_lep_users($course->id,$planid);
					
						 
					if($disable_class->nextsetoperator!=''){/*condition to check not empty*/
			        
						if($disable_class->nextsetoperator=='and' && $find_completion==''){/*Condition to check the nextsetoperator*/
						
							if($course->sortorder>=$disable_class->sortorder){/*Condition to cehck the sortorder and make all the disable*/
								$disable_class1='course_disabled';
							}	
						}
					}
				}
				/* End of the function and condition By Ravi_369*/
				
					$course_data .= '<div class="course_image_comtainer pull-left span3 desktop-first-column">
										<img class="ilp_course_image" src="'.$course_summary_image_url.'" title="'.$course->fullname.'"/>
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
				$check_course_enrol=$this->db->get_field('enrol','id',array('courseid'=>$course->id,'enrol'=>'ilp'));
				/***Then check the userid***/
				$find_user=$this->db->get_field('user_enrolments','id',array('enrolid'=>$check_course_enrol,'userid'=>$this->user->id));
				
				if(!$find_user){/*Condition to check the user enroled or not*/
				$plan_url = new moodle_url('/local/ilp/index.php', array('courseid' => $course->id,'planid'=>$planid,'userid'=>$this->user->id));
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
			$table->id = 'ilp_courses';
			$table->data = $table_data;
			$return .= html_writer::table($table);
			$return .= html_writer::script('$(document).ready(function(){
												//$("table#ilp_courses").dataTable({
													//language: {
													//	"paginate": {
													//		"next": ">",
													//		"previous": "<"
													//	  }
													//}
												//	"iDisplayLength": 3,
												//	"aLengthMenu": [[3, 10, 25, 50, -1], [3, 10, 25, 50, "All"]]
												//});
												//$("table#ilp_courses thead").css("display" , "none");
										   });');
		}
		
		return $return;
	}
	// public function all_enroled_ilps(){
        
 //        $systemcontext = context_system::instance();
        
	
	// 	$sql="SELECT llp.* FROM {local_ilp} llp JOIN {local_ilp_user} AS lla ON llp.id=lla.planid where userid=:userid and llp.visible=1";
	// 	$ilps = $this->db->get_records_sql($sql,array('userid' => $this->user->id));
 //        if(empty($ilps)){
 //           return html_writer::tag('div', get_string('noilps', 'local_ilp'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
 //        }else{
 //            $data = array();
 //            foreach($ilps as $ilp){
 //                $row = array();
 //                $plan_url = new moodle_url('/local/ilp/plan_view.php', array('id' => $ilp->id));
 //                $plan_edit_url = new moodle_url('/local/ilp/index.php', array('id' => $ilp->id));
	// 			$plan_visible_url = new moodle_url('/local/ilp/index.php', array('visible' => $ilp->id,'show'=>$ilp->id));
 //                if(!empty($ilp->startdate)){
 //                    $plan_startdate = date('d/m/Y', $ilp->startdate);
 //                }else{
 //                    $plan_startdate = 'N/A';
 //                }
 //                if(!empty($ilp->enddate)){
 //                    $plan_enddate = date('d/m/Y', $ilp->enddate);
 //                }else{
 //                    $plan_enddate = 'N/A';
 //                }
 //                if(empty($ilp->credits)){
 //                    $plan_credits = 'N/A';
 //                }else{
 //                    $plan_credits = $ilp->credits;
 //                }
 //                if($ilp->learning_type == 1){
 //                    $plan_type = 'Core Courses';
 //                }elseif($ilp->learning_type == 2){
 //                    $plan_type = 'Elective Courses';
 //                }
 //                if(!empty($ilp->location)){
 //                    $plan_location = $ilp->location;
 //                }else{
 //                    $plan_location = 'N/A';
 //                }
                
 //                $action_icons = '';
 //                if (is_siteadmin() || has_capability('local/ilp:visible', $systemcontext)) {
	// 				if($ilp->visible == 0){
	// 					$action_icons .= html_writer::link($plan_visible_url,
 //                                                       html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/show'), 'title'=>'Show', 'class'=>'icon')));
	// 				}elseif($ilp->visible == 1){
	// 					$action_icons .= html_writer::link($plan_visible_url,
 //                                                       html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('i/hide'), 'title'=>'Hide', 'class'=>'icon')));
	// 				}
 //                }
				
 //                //Learning Plan
 //                $detail = html_writer::start_tag('div', array('class' => 'ilp_view'));
 //                $detail .= html_writer::tag('h4', html_writer::link($plan_url, $ilp->name), array('class'=>'pull-left'));
 //                $detail .= html_writer::start_tag('div', array('class'=>'action_icons pull-right'));
 //                $detail .= $action_icons;
	// 			$detail .= html_writer::end_tag('div');
 //                $detail .= html_writer::start_tag('div', array('class' => 'ilp_info pull-left col-md-12 desktop-first-column'));
 //                //Learning Plan Detailed info
 //                $detail .= html_writer::start_tag('div', array('class' => 'ilp_detail_info'));
 //                $detail .= html_writer::start_tag('div');
 //                $detail .= html_writer::start_tag('div');
                    
 //            	$detail .= '<div><span>Type : </span><span>'.$plan_type.'</span></div>';
 //                $detail .= '<div><span>Credits : </span><span>'.$plan_credits.'</span></div>';
 //                $detail .= '<div><span>Location : </span><span>'.$plan_location.'</span></div>';
 //                $detail .= html_writer::end_tag('div');
                
 //                $detail .= html_writer::start_tag('div');
 //                if(!is_siteadmin()){
                      
	// 				$detail .= html_writer::link($plan_url, 'Launch', array('class'=>'launch'));
						
	// 			}else{
	// 				$detail .= html_writer::link($plan_url, 'Launch', array('class'=>'launch'));
							
	// 			}
 //                $detail .= html_writer::end_tag('div');
 //                $detail .= html_writer::end_tag('div');
 //                $detail .= html_writer::start_tag('div');
 //                $detail .= html_writer::end_tag('div');
 //                $detail .= html_writer::end_tag('div');
 //                //Learning Plan Image
 //                $detail .= html_writer::start_tag('div', array('class' => 'ilp_image'));
 //                $detail .= html_writer::end_tag('div');
 //                $detail .= html_writer::end_tag('div');
 //                $detail .= html_writer::end_tag('div');
 //                $row[] = $detail;
 //                $data[] = $row;
 //            }
 //            $table = new html_table();
 //            $table->id = 'all_ilps_mylep';
 //            $table->head = array('');
 //            $table->data = $data;
 //            $return = html_writer::table($table);
	// 		$return .= html_writer::script('$(document).ready(function(){
	// 											$("table#all_ilps_mylep").dataTable({
	// 												language: {
	// 													"paginate": {
	// 														"next": ">",
	// 														"previous": "<"
	// 													  }
	// 												},
	// 												"iDisplayLength": 3,
	// 												"aLengthMenu": [[3, 10, 25, 50, -1], [3, 10, 25, 50, "All"]]
	// 											});
	// 											$("table#all_ilps_mylep thead").css("display" , "none");
	// 											$("#all_ilps_length").css("display" , "none");
	// 									   });');
 //            $return .= '';
 //        }
 //        return $return;
 //    }
public function assigned_ilps_courses_browse_employee_view($planid, $userid,$condition){
		
		// require_once($CFG->dirroot.'/local/ilp/lib.php');
		require_once($CFG->dirroot.'/local/includes.php');
		
        $systemcontext = context_system::instance();
		
		$ilp_lib = new local_ilp\lib\lib();
		$includes = new user_course_details;
		
		$courses = lib::get_ilp_assigned_courses($planid);
		
		$return = '';
		//$return .= html_writer::tag('h3', get_string('assigned_courses', 'local_ilp'), array());
		if(empty($courses)){
			$return .= html_writer::tag('div', get_string('noilpcourses', 'local_ilp'), array('class' => 'alert alert-info text-center pull-left', 'style' => 'width:96%;padding-left:2%;padding-right:1%;'));
		}else{
			$table_data = array();
			/**********To disable the links before enrol to plan**********/
			$check=$this->db->get_record('local_ilp_user',array('userid'=>$this->user->id,'planid'=>$planid));
			/*End of query*/
            foreach($courses as $course){
				
				if($check){
					$course_url = new moodle_url('/course/view.php', array('id'=>$course->id));
				}else{
					$course_url="#";
				}
				
				$course_view_link = html_writer::link($course_url, $course->fullname, array());
				$course_summary_image_url = $includes->course_summary_files($course);
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
				if (has_capability('local/ilp:assigncourses', $systemcontext)) {
					if($condition=='view'){
						
					}else{
					
					$unassign_url = new moodle_url('/local/ilp/assign_courses_users.php', array('planid' => $planid, 'unassigncourse' => $course->id));
					$unassign_link = html_writer::link($unassign_url,
													   html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/delete'), 'class' => 'icon', 'title' => 'Unassign'))
													   , array(
															   'class' => 'pull-right',
															   'id' => 'unassign_course_'.$course->id.''));
					$confirmationmsg = get_string('unassign_courses_confirm','local_ilp', $course);
						
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
					$disable_class = $ilp_lib->get_previous_course_status($planid,$course->sortorder,$course->id);
					$find_completion=$ilp_lib->get_completed_lep_users($course->id,$planid);
					
		           
						 
								if($disable_class->nextsetoperator!=''){/*condition to check not empty*/
						        
									if($disable_class->nextsetoperator=='and' && $find_completion==''){/*Condition to check the nextsetoperator*/
									
									if($course->sortorder>=$disable_class->sortorder){/*Condition to cehck the sortorder and make all the disable*/
										$disable_class1='course_disabled';
									}
									
									}else{
						
									}
								}
					//}
				}
				/* End of the function and condition By Ravi_369*/
					
					$course_data .= '<div class="course_image_comtainer pull-left span3 desktop-first-column">
										<img class="ilp_course_image" src="'.$course_summary_image_url.'" title="'.$course->fullname.'"/>
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
			$table->id = 'ilp_courses';
			$table->data = $table_data;
			$return .= html_writer::table($table);
			$return .= html_writer::script('$(document).ready(function(){
												//$("table#ilp_courses").dataTable({
													//language: {
													//	"paginate": {
													//		"next": ">",
													//		"previous": "<"
													//	  }
													//}
												//	"iDisplayLength": 3,
												//	"aLengthMenu": [[3, 10, 25, 50, -1], [3, 10, 25, 50, "All"]]
												//});
												//$("table#ilp_courses thead").css("display" , "none");
										   });');
		}
		
		return $return;
	}
	
public function ilpinfo_for_employee($planid){
		global $PAGE;

		$ilp_lib = new lib();
		$includeslib = new \user_course_details();
		$ilp_classes_lib = new lib();
		
		$lplan = $this->db->get_record('local_ilp', array('id'=>$planid));
		
		$lptype = $lplan->approvalreqd == 1 ? 'Core Courses' : 'Elective Courses';
		$lpapproval = $lplan->approvalreqd == 1 ? get_string('yes') : get_string('no');
		
		$lpimgurl = $ilp_classes_lib->get_ilpsummaryfile($planid);
		
		$mandatarycourses_count = $ilp_classes_lib->ilpcourses_count($planid, 'and');
		$optionalcourses_count = $ilp_classes_lib->ilpcourses_count($planid, 'or');
		
		$lplanassignedcourses = lib::get_ilp_assigned_courses($planid);
		
		$catalogrenderer = $this->page->get_renderer('local_catalog');
		$description = strip_tags(html_entity_decode($lplan->description),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
        $description_string = strlen($description) > 220 ? substr($description, 0, 220)."..." : $description;

		$lpinfo = '';
		$condition="view";
		
		/***********The query Check Whether user enrolled to LEP or NOT**********/
		$plan_record = $this->db->get_record('local_ilp', array('id' => $planid));
		$sql="select id from {local_ilp_user} where planid=$planid and userid=".$this->user->id."";
		$check=$this->db->get_record_sql($sql);
		/*End of Query*/
		
		/*******The Below query is check the approval status for the LOGIN USERS on the his LEP************/
		$check_approvalstatus=$this->db->get_record('local_ilp_approval',array('planid'=>$plan_record->id,'userid'=>$this->user->id));
		
		/*End of Query*/
		//print_object($check);
		if($check){ /****condition to check user already enrolled to the LEP If Enroled he get option enrolled ********/
		
		if($check_approvalstatus->approvestatus==1){
			$back_url = "#";
			//$lpinfo .= "<a href=$back_url> <button class='btn enroll'>Enrolled</button></a>";
		
		}else{
			$back_url ="#";
			//$lpinfo .= "<a href='$back_url' id='enrolle'> <button class='btn enroll'></button></a>";
		}
		}else{/****Else he has 4 option like the Send Request or Waiting or Rejected or Enroled****/
		
		if(!is_siteadmin()){
		
		if($condition!='manage'){ /*******condition to check the manage page or browse page******/
		
		if($plan_record->approvalreqd==1  && (!empty($check_approvalstatus))) /***** If user has LEP with approve with 1 means request yes and
											empty not check approval status means he has sent request******/
		{
		

		$check_users= $ilp_lib->check_courses_assigned_target_audience($this->user->id,$plan_record->id);
		/****The above Function is to check the user is present in the target audience or not***/
		
		if($check_users==1){/*if there then he will be shown the options*/
		
		$check_approvalstatus=$this->db->get_record('local_ilp_approval',array('planid'=>$plan_record->id,'userid'=>$this->user->id));
		
		if($check_approvalstatus->approvestatus==0 && !empty($check_approvalstatus)){
		$back_url = "#";
		//$lpinfo .= "<a href='$back_url' id='request'> <button class='btn enroll'>Waiting</button></a>";

		}elseif($check_approvalstatus->approvestatus==2 && !empty($check_approvalstatus)){
		$back_url = "#";
		//$lpinfo .= "<a href='$back_url' id='request'> <button class='btn enroll'>Rejected</button></a>";
		}

		if(empty($check_approvalstatus)){
		
		$back_url = new moodle_url('/local/ilp/plan_view.php',array('id'=>$plan_record->id,'enrolid'=>$plan_record->id));
		//$lpinfo .= "<a href='$back_url' id='enroll'> <button class='btn enroll'>Enroll to Plan</button></a>";
		//echo html_writer::link($back_url, 'Enroll to Plan', array('class' => 'btn enroll','id'=>'enroll1'));
		$notify = new stdClass();
		$notify->name = $plan_record->name;
		$PAGE->requires->event_handler("#enroll1",
		'click', 'M.util.bajaj_show_confirm_dialog', array('message' => get_string('enroll_notify','local_ilp',$notify),
				 'callbackargs' => array('confirmdelete' =>$plan_record->id)));
		}
		}
		}else if(($plan_record->approvalreqd==1) && (empty($check_approvalstatus))){
			$check_users= $ilp_lib->check_courses_assigned_target_audience($this->user->id,$plan_record->id);
			
		if($check_users==1){
			$back_url = new moodle_url('/local/ilp/index.php', array('approval' => $plan_record->id));	
			//$lpinfo .= "<a href='$back_url' id='request'> <button class='btn enroll'>SEND REQUEST</button></a>";
			$approve=  html_writer::link('Send Request', array('class' => 'pull-right enrol_to_plan nourl','id'=>'request'));
			$notify_info = new stdClass();
			$notify_info->name = $plan_record->name;
			$PAGE->requires->event_handler("#request",
			'click', 'M.util.bajaj_show_confirm_dialog', array('message' => get_string('delete_notify','local_ilp',$notify_info),
					 'callbackargs' => array('confirmdelete' =>$plan_record->id)));
			
		}
		}else if($plan_record->approvalreqd==0  && (empty($check_approvalstatus))){
		
		$back_url = new moodle_url('/local/ilp/plan_view.php',array('id'=>$plan_record->id,'enrolid'=>$plan_record->id));
		//$lpinfo .= "<a href='$back_url' id='enroll'> <button class='btn enroll'>Enroll to Plan</button></a>";
		$notify = new stdClass();
		$notify->name = $plan_record->name;
		$PAGE->requires->event_handler("#enroll",
		'click', 'M.util.bajaj_show_confirm_dialog', array('message' => get_string('enroll_notify','local_ilp',$notify),
				 'callbackargs' => array('confirmdelete' =>$plan_record->id)));
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
				//$coursename_string = strlen($coursename) > 6 ? substr($coursename, 0, 6)."..." : $coursename;

				$coursespath_context['pathcourses'][] = array('coursename'=>$coursename, 'coursename_string'=>'C'.$i);
			$i++;			
			}
			$pathcourses .= $this->render_from_template('local_ilp/cousrespath', $coursespath_context);
		}
		$enrolled=$this->db->get_field('local_ilp_user','id',array('userid'=>$this->user->id,'planid'=>$planid));
		$needenrol = $enrolled? false : true;
		$lp_userview = array();
		$lp_userview['planid'] = $planid;
		$lp_userview['userid'] = $this->user->id;
		$lp_userview['needenrol'] = $needenrol;
		$lp_userview['lpname'] = $lplan->name;
		$lp_userview['lpimgurl'] = $lpimgurl;
		$lp_userview['description_string'] = $description_string;
		$lp_userview['lpcoursespath'] = $pathcourses;
		$lp_userview['lptype'] = $lptype;
		$lp_userview['lpapproval'] = $lpapproval;
		$lp_userview['plan_startdate'] = $plan_startdate;
		$lp_userview['plan_enddate'] = $plan_enddate;
		$lp_userview['lplancredits'] = $lplan->credits;
		$lp_userview['mandatarycourses_count'] = $mandatarycourses_count;
		$lp_userview['optionalcourses_count'] = $optionalcourses_count;
		$lpinfo .= $this->render_from_template('local_ilp/planview_user', $lp_userview);
	$test = '';
		if($lplanassignedcourses){
			$i=1;
			foreach($lplanassignedcourses as $assignedcourse){
				$courseimgurl = $includeslib->course_summary_files($assignedcourse);
				
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
					$identify['4'] = get_string('ilp');
					foreach($ctypes as $ctype){
						$types[] = $identify[$ctype];
					}
				}
				
				
				$coursepageurl = new \moodle_url('/course/view.php', array('id'=>$assignedcourse->id));
				if($assignedcourse->next == 'and'){
					$optional_or_mandtry = "<span class='mandatory'>M</span>";
				}else{
					$optional_or_mandtry = "<span class='optional'>OP</span>";
				}
				/******To make course link enable after the enrolled to lep******/
				$check=$this->db->get_field('local_ilp_user','id',array('userid'=>$this->user->id,'planid'=>$planid));
				if($check){
					$enrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'ilp'));
					/*******The three enrolment added bcos we need to get link in any of enrolment so.There was issues in production***/
					$selfenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'self'));
					$autoenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'auto'));
					$manualenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'manual'));
					$ilpenrol=$this->db->get_field('enrol','id',array('courseid'=>$assignedcourse->id,'enrol'=>'ilp'));
					
					$sql="SELECT id FROM {user_enrolments} WHERE userid={$this->user->id} AND enrolid IN ('$enrol','$selfenrol','$autoenrol','$manualenrol','$ilpenrol')"; 
						
					$enrolledcourse=$this->db->get_field_sql($sql);
					
				$rname = format_string($assignedcourse->fullname);
				if($rname > substr(($rname),0,23)){
					$fullname = substr(($rname),0,23).'...';
				}else{
					$fullname =$rname; 
				}
				if($enrolledcourse){
					
				$courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
				}else{
					/*******Through course Link also user can enroll the course **********/
				$coursepageurl = new moodle_url('/local/ilp/index.php', array('courseid' => $assignedcourse->id,'planid'=>$lplan->id,'userid'=>$this->user->id));	
				$courselink = html_writer::link($coursepageurl, $fullname, array('class'=>'coursesubtitle','title'=>$assignedcourse->fullname));
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
				
				$progressbar = $includeslib->user_course_completion_progress($assignedcourse->id,$this->user->id);
				if(!$progressbar){
					$progressbarval = 0;
					$progress_bar_width = "min-width: 0px;";
				}else{
					$progressbarval = round($progressbar);
					$progress_bar_width = "min-width: 20px;";
				}
					/**************To show course completed or not********/
		$sql="SELECT id,timecompleted FROM {course_completions} as cc WHERE userid=".$this->user->id." and course=".$assignedcourse->id." and timecompleted!=''";

		$completed=$this->db->get_record_sql($sql);
				/********LAUNCH button for every courses to enrol********/
				/*First check the enrolment method*/
				$sql="SELECT id,id AS id_val FROM {enrol} WHERE courseid = $assignedcourse->id";
				$get_data=$this->db->get_records_sql_menu($sql);
				$data=implode(',',$get_data);
				
				/********This below query is used to check the user already enroled to course with other enrolments methods******/
				$sql="SELECT id FROM {user_enrolments} WHERE enrolid IN($data) and userid=".$this->user->id."";
				$find_user=$this->db->record_exists_sql($sql) ;

				/***Then check the userid***/

				if(!$find_user){/*Condition to check the user enroled or not*/
					$plan_url = new \moodle_url('/local/ilp/index.php', array('courseid' => $assignedcourse->id,'planid'=>$lplan->id,'userid'=>$this->user->id));
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
					if($completed->timecompleted){
						$completiondate = date("j M 'Y",$completed->timecompleted);
					}else{
						$completed_date = '';
					}
			}else{
				$cmpltd_class = '';
				$completiondate ='';
			}
			//print_object($assignedcourse);
			//echo $assignedcourse->fullname."<br/>";
		if($assignedcourse->sortorder>0&&$assignedcourse->next=='and'){/*Condition to check the sortorder and disable the course */
			//echo $assignedcourse->fullname."<br/>";
			/**** Function to get the all the course details like the nextsetoperator,sortorder
			@param planid,sortorder,courseid of the record
			****/
			$disable_class = $ilp_classes_lib->get_previous_course_status($planid,$assignedcourse->sortorder,$assignedcourse->id);
			//print_object($disable_class);
			//$find_completion= $ilp_classes_lib->get_completed_lep_users($assignedcourse->id,$planid);
					//if($disable_class->nextsetoperator!=''){/*condition to check not empty*/
					//      
					//	if($disable_class->nextsetoperator=='and'){/*Condition to check the nextsetoperator*/
					//	
					//	if($assignedcourse->sortorder>=$disable_class->sortorder){/*Condition to cehck the sortorder and make all the disable*/
					//		$disable_class1='course_disabled';
					//	}
					//	
					//	
					//	}else{
					//
					//	}
					//}
					//else 
					//	$disable_class1='';
			if($disable_class){
				$disable_class1="";
			}else{
				$disable_class1='course_disabled';
			}

		}else{
			$disable_class1="";
		}
			$enroldisable_class1 = 'enrolled';
			if($needenrol){
				$enroldisable_class1='not_enrolled course_disabled';	
			}
			// $completiondate = '28/02/18';
			$lp_userviewcoures['disable_class1'] = $disable_class1;
			$lp_userviewcoures['needenrol'] = $needenrol;
			$lp_userviewcoures['enroldisable_class1'] = $enroldisable_class1;
			$lp_userviewcoures['cmpltd_class'] = $cmpltd_class;
			$lp_userviewcoures['progressbar'] = $progressbarval;
			$lp_userviewcoures['courseimgurl'] = $courseimgurl;
			$lp_userviewcoures['courselink'] = $courselink;
			$lp_userviewcoures['completiondate'] = $completiondate;
			$lp_userviewcoures['optional_or_mandtry'] = $optional_or_mandtry;
			$lp_userviewcoures['course_summary_string'] = $course_summary_string;
			
			/**********To disable the The status like Launch || Enrolled || Completed || before enrol to plan**********/
			$check=$this->db->get_field('local_ilp_user','id',array('userid'=>$this->user->id,'planid'=>$planid));
			/*End of query*/
		$test .= $this->render_from_template('local_ilp/planview_usercourses', $lp_userviewcoures);
			}
		}
	$lpinfo .= $test;
		return $lpinfo;
	}
}
?>