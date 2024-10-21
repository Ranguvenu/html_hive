<?php

global $CFG;
use local_classroom\classroom;
use local_learningplan\lib\lib as lib;
use local_search\output\elearning as elearning;
require_once($CFG->dirroot . '/local/catalog/lib.php');
require_once($CFG->dirroot . '/local/search/lib.php');
//
// namespace local_classroom\output;

class local_search_renderer extends plugin_renderer_base {   
	
	 function display_tabs_and_its_content(){
		  global $DB, $USER, $OUTPUT, $PAGE;

		  $data = '';
		  $data = html_writer:: start_tag('div', array('id'=>'mytabs', 'class'=>'span12'));
		    
                $systemcontext = context_system::instance();
					
				 if(!is_siteadmin()){
                        $tab3url = new moodle_url('#completedcourse', array());
                        $tab3link = html_writer:: link($tab3url, get_string('completed_courses', 'local_search'), array());
                   
                        $data .= html_writer:: tag('li', $tab3link ,array());
					}
					
                $data .= html_writer:: end_tag('ul');
			   
               $grid = $this->mycourses_tabcontent($USER->id);
			   $completed_course_tabcontent = $this->completedcourses_tabcontent($USER->id);
			   
			   $data .= html_writer:: tag('div', $grid, array('id'=>'mycourses', 'class'=>'coursesgrid_search')); // first tab content
               
                if(has_capability('block/learning_plan:viewpages',$systemcontext)){
                    $mylearnignplan_tabcontent = learning_plan_information($USER->id);
                    if($mylearnignplan_tabcontent){
                        $mylearnignplan_tabcontent = $mylearnignplan_tabcontent;
                    }else{
                        $mylearnignplan_tabcontent = html_writer:: tag('p',get_string('norecords', 'local_search'),array('class'=>'norecords_msg'));
                    }
                    
                    $data .= html_writer:: tag('div', $mylearnignplan_tabcontent, array('id'=>'mylearningplans'));
                }
			   
			   if(!is_siteadmin()){
					$data .= html_writer:: tag('div', $completed_course_tabcontent, array('id'=>'completedcourse', 'class'=>'coursesgrid_search'));
			   }

			   
		  
		  $data .= html_writer:: end_tag('div');
		  
		  return $data;
		  
	}
	 
	/*Get uploaded course summary uploaded file
     * @param $course is an obj Moodle course
     * @return course summary file(img) src url if exists else return default course img url
     * */
    function get_course_summary_file($course){  
        global $DB, $CFG, $OUTPUT;
        if ($course instanceof stdClass) {
            require_once($CFG->libdir . '/coursecatlib.php');
            $course = new course_in_list($course);
        }
        
        // set default course image
        $url = $OUTPUT->pix_url('/course_images/courseimg', 'local_costcenter');
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            if($isimage){
                $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                                        $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
            }else{
                $url = $OUTPUT->pix_url('/course_images/courseimg', 'local_costcenter');
            }
        }
        return $url;
    }
    
    function course_sections($courseid){
        global $DB;
        
        $sql = "SELECT cs.id, c.id as courseid, c.fullname, c.format, c.startdate,cs.id as sectionid, cs.section, cs.name, cs.summary, cs.sequence, cs.visible as section_visible
                from {course} c
                join {course_sections} cs
                on c.id = cs.course
                where c.id = $courseid and cs.section != 0";
        
        $course_sections = $DB->get_records_sql($sql);
        
		$sql .= " AND cs.visible = 1 ";
		$visible_course_sections = $DB->get_records_sql($sql);
		
		$visible_course_sections = count($visible_course_sections);
		$section = '';
        $section_names = '';
        $section_content = '';
        $sec_num = 1;
        
        foreach($course_sections as $course_section){
            
            $section_icon = 'fa-folder';
            
			if($course_section->section_visible && !empty($course_section->sequence)){
				$section_names .= '<li>
										<a href="#section-'.$sec_num.'" role="tab" tabindex="0">
												<i class="fa '.$section_icon.' course-icon"></i><br>
												Module '.$sec_num.'
										</a>
									</li>';
			
				$section_content .= '<div id="section-'.$sec_num.'" role="tabpanel" >';
				$section_content .= '<div class="content">
										<h3 class="sectionname">'.$course_section->name.'</h3>';
				if(!empty($course_section->sequence)){
					$c_activities = explode(',', $course_section->sequence);
					if(!empty($c_activities)){
						$section_content .= '<ul class="section">';
						foreach($c_activities as $module){ //In sequence wise modules
							$module_record = $DB->get_record('course_modules', array('id'=>$module, 'visible'=>1));
							if(!empty($module_record)){
								$activity = $DB->get_record('modules', array('id'=>$module_record->module, 'visible'=>1));
								switch($activity->name){
									case 'book':
										$activity_icon_class = 'fa-book';
										break;
									case 'file':
										$activity_icon_class = 'fa-file';
										break;
									case 'folder':
										$activity_icon_class = 'fa-folder';
										break;
									case 'imscp':
										$activity_icon_class = 'fa-cubes';
										break;
									case 'kpoint':
										$activity_icon_class = 'fa-play';
										break;
									case 'label':
										$activity_icon_class = 'fa-tag';
										break;
									case 'page':
										$activity_icon_class = 'fa-file-text-o';
										break;
									case 'url':
										$activity_icon_class = 'fa-globe';
										break;
									case 'assign':
										$activity_icon_class = 'fa-arrows-h';
										break;
									case 'bigbluebuttonbn':
										$activity_icon_class = 'fa-btc';
										break;
									case 'certificate':
										$activity_icon_class = 'fa-file-picture-o';
										break;
									case 'chat':
										$activity_icon_class = 'fa-comments';
										break;
									case 'choice':
										$activity_icon_class = 'fa-question';
										break;
									case 'data':
										$activity_icon_class = 'fa-database';
										break;
									case 'external tool':
										$activity_icon_class = 'fa-puzzle-piece';
										break;
									case 'feedback':
										$activity_icon_class = 'fa-bullhorn';
										break;
									case 'forum':
										$activity_icon_class = 'fa-comment';
										break;
									case 'glossary':
										$activity_icon_class = 'fa-file-word-o';
										break;
									case 'lti':
										$activity_icon_class = 'fa-user';
										break;
									case 'lesson':
										$activity_icon_class = 'fa-file-text-o';
										break;
									case 'quiz':
										$activity_icon_class = 'fa-check-square';
										break;
									case 'scorm':
										$activity_icon_class = 'fa-inbox';
										break;
									case 'secured pdf':
										$activity_icon_class = 'fa-file-pdf-o';
										break;
									case 'survey':
										$activity_icon_class = 'fa-bar-chart-o';
										break;
									case 'wiki':
										$activity_icon_class = 'fa-wikipedia-w';
										break;
									case 'workshop':
										$activity_icon_class = 'fa-users';
										break;
									default:
										$activity_icon_class = 'fa-book';
								}
								if(!empty($activity)){
									$activity_name = $DB->get_record($activity->name, array('id'=>$module_record->instance));
									$activity_icon = '<i class="iconlarge activityicon fa-2x fa '.$activity_icon_class.' iconcourse success" title="'.$activity_name->name.'" role="presentation"></i>';
									$section_content .= "<li class='activity'>".$activity_icon.$activity_name->name."</li>";
								}
							}
						}
						$section_content .= '</ul>';
					}
					
				}else{
					$section_content .= "<p class='sectioninfo'>".get_string('noactivitieshere','local_search')."</p>";
				}
				$section_content .= '</div>';
				$section_content .= '</div>';
			}else{
				//hidden and no activities
			}
            
            $sec_num++;
        }
        
		$section .= "<div id='courseallsections'>"; 
		
		if($visible_course_sections > 5){
			$section .= '<span class="leftArrow_container"><i id="leftArrow2" class="fa fa-angle-left leftArrow"></i></span>';
		}
        $section .= "<ul>";
        $section .= $section_names;
        $section .= "</ul>";
		if($visible_course_sections > 5){
			$section .= '<span class="rightArrow_container"><i id="rightArrow2" class="fa fa-angle-right rightArrow"></i></span>';
		}
		$section .= '<div>'.$section_content.'</div>';
        
		$section .= "</div>";
				
		return $section;
    }
    
    public function get_course_info($id) {
		//echo "hiiirender";exit;
		global $USER, $OUTPUT, $DB, $CFG;
		$coursecontext   = context_course::instance($id);
		$course = $DB->get_record('course', array('id'=>$id));
		if(file_exists($CFG->dirroot . '/local/ratings/lib.php')){
			require_once($CFG->dirroot . '/local/ratings/lib.php');
        	$course_like = display_like_unlike($id,0,0,'course',$id);
        }
        $course_points = $course->open_points != NULL ? $course->open_points: 'N/A';

		$enrolled_count = count(get_enrolled_users($coursecontext));
		// $completed_count = $DB->get_record_sql($ccsql);
		// $inprogess_count = $enrolled_count - $completed_count->ccount;
		if(!$course){
			print_error('invalidcourseid');
		}
		if(file_exists($CFG->dirroot .'/local/includes.php')){
			require_once($CFG->dirroot .'/local/includes.php');
			$includes = new user_course_details;
		}
		//$url = $includes->course_summary_files($course);
		$url = course_thumbimage($course);
		$course_summary = strip_tags(html_entity_decode(clean_text($course->summary)),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
		if(!empty($course_summary)){
			$course_summary = $course_summary;
			$course_summary_string = strlen($course_summary) > 585 ? substr($course_summary, 0, 585)."..." : $course_summary;
		}
		$component = 'elearning';
		$action = 'add';
		$enroll=is_enrolled($coursecontext, $USER->id);
		$is_admin = false;
        $selfenrol = (new \local_courses\output\search())->get_enrollbutton($enroll, $course);
		if(is_siteadmin()){
			$is_admin = true;
		}
		$dur_min_sql = "SELECT cd.charvalue 
                        FROM {customfield_data} cd 
                        JOIN {customfield_field} cff ON cff.id = cd.fieldid
                        WHERE instanceid = $course->id AND cff.shortname = 'duration_in_minutes'
                            ";
        $dur_min = $DB->get_field_sql($dur_min_sql);
        if($dur_min){
            $hours = floor($dur_min / 60);
            if($hours > 1){
                $hours = floor($dur_min / 60).' Hrs ';
            }elseif($hours == 1){
                $hours = floor($dur_min / 60).' Hr ';
            }elseif($hours == 0){
                $hours = '';
            }
            $minutes = ($dur_min % 60).' Mins.';
            $durationinmin  = $hours.$minutes;
        }else{
            $min = 0;
            $durationinmin = $min.' Min.';
        }

        $displaymodules = false;
        $activities_count = 0;
		if($course->open_contentvendor){
			$cprovider = $DB->get_field('local_courses_contentvendors','shortname',array('id'=>$course->open_contentvendor));
			if($cprovider == 'Microsoft Learn'){
				if($course->open_noofchildren > 0){
					$displaymodules = true;
					$activities_count = $course->open_noofchildren;
				}
			}else{
				$activitiescount = $this->get_modulescount($course->id);
				if($activitiescount > 0){
					$displaymodules = true;
					$activities_count = $activitiescount;
				}
			}
		}else{
			$activitiescount = $this->get_modulescount($course->id);
			if($activitiescount > 0){
				$displaymodules = true;
				$activities_count = $activitiescount;
			}
		}

		$coursesContext = [
			"courseid" => $course->id,
			"course_summary" => $course_summary_string,
			"course_name" => $course->fullname,
			"course_imageurl" => $url,
			"is_admin" => $is_admin,
			"selfenrol" => $selfenrol,
			"durationinmin" => $durationinmin,
			"displaymodules" => $displaymodules,
			"activities_count" => $activities_count,
			//"is_user" => $is_user,
			//"is_enrolled" => $is_enrolled,
			"component" => $component,
			"action" => $action,
			"requestbtn" => $requestbtn,
			"pending" => $pending,
			// "course_like" => $course_like,
			"course_points" => $course_points,
			"enrolled_count" => $enrolled_count,
			"courseviewurl" => $CFG->wwwroot.'/course/view.php?id='.$course->id.'',
			"enrolurl" => $CFG->wwwroot.'/enrol/index.php?id='.$course->id.'',
		];
		//print_object($coursesContext);
		return  $this->render_from_template('local_search/courseinfo', $coursesContext);
	}

	public function get_modulescount($courseid){
		global $DB;

		$count_sql = "SELECT count(id) 
                        FROM {course_modules}
                        WHERE course = $courseid
                        AND deletioninprogress = 0 AND visibleoncoursepage =1 AND visible = 1 ";

        $activities_count = $DB->count_records_sql($count_sql);

        $count = $activities_count ? $activities_count : 0 ;

        return $count;
	}

	public function get_classroom_info($crid) {
		global $OUTPUT, $CFG, $DB, $USER, $PAGE;
		//echo "huii";
		//require_once($CFG->dirroot . '/local/classroom/classes/classroom.php');

        $stable = new stdClass();
        $stable->classroomid = $crid;
        $stable->thead = false;
        $stable->start = 0;
        $stable->length = 1;
		$fromsql = "SELECT c.*, (SELECT COUNT(DISTINCT cu.userid)
                                  FROM {local_classroom_users} AS cu
                                  WHERE cu.classroomid = c.id
                              ) AS enrolled_users FROM {local_classroom} AS c
					WHERE c.id=$crid";  
        $classroom = $DB->get_record_sql($fromsql);

        $context = context_system::instance();
        $classroom_status = $DB->get_field('local_classroom','status',array('id' => $crid));
        if(!has_capability('local/classroom:view_newclassroomtab', context_system::instance()) && $classroom_status==0){
            print_error("You don't have permissions to view this page.");
        }
        elseif(!has_capability('local/classroom:view_holdclassroomtab', context_system::instance())&& $classroom_status==2){
            print_error("You don't have permissions to view this page.");
        }
        if(empty($classroom)) {
            print_error("Classroom Not Found!");
        }
        if(file_exists($CFG->dirroot.'/local/includes.php')){
        	require_once($CFG->dirroot.'/local/includes.php');
        	$includes = new user_course_details();
    	}
        if ($classroom->classroomlogo > 0){
            $classroom->classroomlogoimg = (new classroom)->classroom_logo($classroom->classroomlogo);
            if($classroom->classroomlogoimg == false){
                $classroom->classroomlogoimg = $includes->get_classes_summary_files($sdata); 
            }
        } else {
            $classroom->classroomlogoimg = $includes->get_classes_summary_files($classroom);
        }
        //if ($classroom->category > 0) {
        //    $classroom->category = $DB->get_field('local_location_institutes', 'category', array('id' => $classroom->instituteid));
        //} else {
        //    $classroom->category = 'N/A';
        //}
        if ($classroom->instituteid > 0) {
            $classroom->classroomlocation = $DB->get_field('local_location_institutes', 'fullname', array('id' => $classroom->instituteid));
        } else {
            $classroom->classroomlocation = 'N/A';
        }


        if ($classroom->department == -1) {
            $classroom->classroomdepartment = 'All';
        } else {
            $classroomdepartment = $DB->get_fieldset_select('local_costcenter', 'fullname', " CONCAT(',',{$classroom->department},',') LIKE CONCAT('%,',id,',%') ", array());//FIND_IN_SET(id, '$classroom->department')
            $classroom->classroomdepartment = implode(', ', $classroomdepartment);
        }
        
        $classroom_capacity_check=(new classroom)->classroom_capacity_check($classroom->id);
        $return="";
        $classroom->userenrolmentcap = (has_capability('local/classroom:manageusers', context_system::instance()) &&has_capability('local/classroom:manageclassroom', context_system::instance()) && $classroom->status == 0) ? true : false;
    	$nominationselfenrolmentcap=$classroom->selfenrolmentcap = false;
    	if (!has_capability('local/classroom:manageclassroom', context_system::instance())) {
            $userenrolstatus = $DB->record_exists('local_classroom_users', array('classroomid' => $classroom->id, 'userid' => $USER->id));

            $return=false;
            if($classroom->id > 0 && $classroom->nomination_startdate!=0 && $classroom->nomination_enddate!=0){
                $params1 = array();
                $params1['classroomid'] = $classroom->id;
                // $params1['nomination_startdate'] = date('Y-m-d H:i',time());
                // $params1['nomination_enddate'] = date('Y-m-d H:i',time());
                $params1['nomination_startdate'] = time();
                $params1['nomination_enddate'] = time();

                $sql1=" SELECT id FROM {local_classroom} WHERE id = :classroomid AND 
                	nomination_startdate <= :nomination_startdate AND 
                	nomination_enddate >= :nomination_enddate ";
               
                $return=$DB->record_exists_sql($sql1,$params1); 

            }elseif($classroom->id > 0 && $classroom->nomination_startdate==0 && $classroom->nomination_enddate==0){
                $return=true;
				$nominationselfenrolmentcap=false;
            }

           
            if ($classroom->status == 1 && !$userenrolstatus && $return) {
                $classroom->selfenrolmentcap = true;
                $url = new moodle_url('/local/classroom/view.php', array('cid' =>$classroom->id,'action' => 'selfenrol'));
                    //$btn = new single_button($url,get_string('enroll','local_search'), 'POST');
                    //$btn->add_confirm_action(get_string('classroom_self_enrolment', 'local_classroom'));
                    //
                    //$cbutton=str_replace("Enroll",''.get_string('enroll','local_search'),$OUTPUT->render($btn));
                    // $cbutton=str_replace('title=""','title="'.get_string('enroll','local_search').'"',$cbutton);

                     $classroom->selfenrolmentcap='<a href="javascript:void(0);" class="btn btn-primary pull-right mr-15" alt = ' . get_string('enroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_classroom/classroom\').ManageclassroomStatus({action:\'selfenrol\', id: '.$classroom->id.', classroomid:'.$classroom->id.',actionstatusmsg:\'classroom_self_enrolment\',classroomname:\''.$classroom->name.'\'}) })(event)" ><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('enroll','local_classroom').'</a>';
                     //$classroom->selfenrolmentcap= array_values(array($cbutton));
            }
                
				if((($classroom_capacity_check && $classroom->allow_waitinglistusers==0) )){
                //if($classroom_capacity_check&&$classroom->status == 1 && !$userenrolstatus){

                        $classroom->selfenrolmentcap=get_string('capacity_check', 'local_classroom');
                }elseif( $classroom->allow_waitinglistusers==1){
					    $waitlist = $DB->get_field('local_classroom_waitlist','id',array('classroomid' => $classroom->id,'userid'=>$USER->id,'enrolstatus'=>0));
						if($waitlist > 0){
								$classroom->selfenrolmentcap='<button class="cat_btn btn-primary viewmore_btn">Waiting List</button>';
						}
				}

        }
        // $stable = new stdClass();
        // $stable->thead = true;
        // $stable->start = 0;
        // $stable->length = -1;
        // $stable->search = '';

        $totalseats=$DB->get_field('local_classroom','capacity',array('id'=>$crid)) ;
        $allocatedseats=$DB->count_records('local_classroom_users',array('classroomid'=>$crid)) ;
        //$coursesummary = strip_tags($course->summary,
        //            array('overflowdiv' => false, 'noclean' => false, 'para' => false));
        $description =strip_tags(html_entity_decode(clean_text($classroom->description)),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
        $isdescription = '';
        if (empty($description)) {
           $isdescription = false;
        } else {
            $isdescription = true;
            if (strlen($description) > 500) {
                $decsriptionCut = substr($description, 0, 500);
                $decsriptionstring =  strip_tags(html_entity_decode(clean_text($decsriptionCut)),array('overflowdiv' => false, 'noclean' => false, 'para' => false));;
            }
        }

        if (empty($totalseats)||$totalseats==0) {
            $seats_progress = 0;
        } else {
            $seats_progress = round(($allocatedseats/$totalseats)*100);
        }
        //print_object($classroom);exit;
        $component = 'classroom';
		$action = 'add';
        if($classroom->approvalreqd==1){
			   $waitlist = $DB->get_field('local_classroom_waitlist','id',array('classroomid' => $classroom->id,'userid'=>$USER->id,'enrolstatus'=>0));
                if($waitlist > 0){
                        $requestbtn = false;
                }else{
					// $request = $DB->get_field('local_request_records','status',array('componentid' => $classroom->id,'compname' => $component,'createdbyid'=>$USER->id));
					$requestsql = "SELECT status FROM {local_request_records} 
						WHERE componentid = :componentid AND compname LIKE :compname AND 
						createdbyid = :createdbyid ORDER BY id DESC ";
					$request = $DB->get_field_sql($requestsql,array('componentid' => $classroom->id, 'compname' => $component, 'createdbyid'=>$USER->id));
					if($request=='PENDING'){
						$pending = true;
					 }else{
						 if(((!$classroom_capacity_check && $classroom->allow_waitinglistusers==0) || ($classroom->allow_waitinglistusers==1))){
							$requestbtn = true;
						 }
						
					}
			}
		}else{
			$requestbtn = false;
		}
		$classroom_url = new moodle_url('/local/classroom/view.php', array('cid' =>$classroom->id));
		$nomination_enddate = $classroom->nomination_enddate ? date('d-m-Y', $classroom->nomination_enddate) : '--';
        $classroomcontext = [
            'classroomname' => $classroom->name,
            'classroomid' => $crid,
            'totalseats'=>$totalseats,
            'allocatedseats'=>$allocatedseats,
            'description'=>strip_tags(html_entity_decode(clean_text($description)),array('overflowdiv' => false, 'noclean' => false, 'para' => false)),
            'descriptionstring'=>strip_tags(html_entity_decode(clean_text($descriptionstring)),array('overflowdiv' => false, 'noclean' => false, 'para' => false)),
            'isdescription'=>$isdescription,
            'startdate' => date("j M 'y", $classroom->startdate),
        	'enddate' => date("j M 'y", $classroom->enddate),
        	'selfenrolmentcap' => $classroom->selfenrolmentcap,
			'nominationselfenrolmentcap' => $nominationselfenrolmentcap,
        	'component' => $component,
        	'action' => $action,
        	'requestbtn' => $requestbtn,
        	'pending' => $pending,
            //'seats_progress'=>$seats_progress,
            'contextid' => $context->id,
            'classroomlogoimg' => $classroom->classroomlogoimg,
            'classroomlocation' => $classroom->classroomlocation,
            'classroomdepartment' => $classroom->classroomdepartment,
            'linkpath'=> $classroom_url,
            'userenrolstatus' => $userenrolstatus,
            'nomination_expired_string' => $classroom->nomination_startdate > time() ? get_string('nomination_notyet_started', 'local_search', date('d-m-Y', $classroom->nomination_startdate)) : get_string('nomination_expired', 'local_search', $nomination_enddate),
        ];
        //print_object($classroomcontext);exit;
       
        return $this->render_from_template('local_search/classroominfo', $classroomcontext);
	}
    
    public function get_learningplan_info($learningplanid){
    	global $DB,$USER;

    	$lplan = $DB->get_record('local_learningplan', array('id'=>$learningplanid));
        
        $description = strip_tags(html_entity_decode(clean_text($lplan->description)),array('overflowdiv' => false, 'noclean' => false, 'para' => false));
        $description_string = strlen($description) > 220 ? substr($description, 0, 220)."..." : $description;
        $lpimgurl = lib::get_learningplansummaryfile($learningplanid);
		
		$mandatarycourses_count = lib::learningplancourses_count($learningplanid, 'and');
		//echo "hiii";exit;
		$optionalcourses_count = lib::learningplancourses_count($learningplanid, 'or');
		$lplanassignedcourses = lib::get_learningplan_assigned_courses($learningplanid);
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
			$pathcourses .= $this->render_from_template('local_learningplan/cousrespath', $coursespath_context);
		}
		$enrolled = $DB->get_field('local_learningplan_user', 'id', array('planid' => $learningplanid, 'userid' => $USER->id));
		$selfenrolmentenable = $enrolled ? false : true;
		$component = 'learningplan';
		$action = 'add';
		if($lplan->approvalreqd==1){
			// $request = $DB->get_field('local_request_records','status',array('componentid' => $lplan->id,'compname' => $component,'createdbyid'=>$USER->id));
			$requestsql = "SELECT status FROM {local_request_records} 
				WHERE componentid = :componentid AND compname LIKE :compname AND 
				createdbyid = :createdbyid ORDER BY id DESC ";
			$request = $DB->get_field_sql($requestsql ,array('componentid' => $lplan->id,'compname' => $component,'createdbyid'=>$USER->id));
            if($request=='PENDING'){
            	$pending = true;
             }else{
				$requestbtn = true;
			}
		}else{
			$requestbtn = false;
		}
		$lp_url = new moodle_url('/local/learningplan/view.php', array('id' =>$lplan->id));
		$lp_userview = array();
		$lp_userview['lpname'] = $lplan->name;
		$lp_userview['lpimgurl'] = $lpimgurl;
		$lp_userview['is_admin'] = is_siteadmin();
		$lp_userview['description_string'] = $description_string;
		$lp_userview['lpcoursespath'] = $pathcourses;
		$lp_userview['lptype'] = $plan_type;
		$lp_userview['selfenrolmentenable'] = $selfenrolmentenable;
		$lp_userview['userid'] = $USER->id;
		$lp_userview['planid'] = $learningplanid;
		$lp_userview['component'] = $component;
		$lp_userview['action'] = $action;
		$lp_userview['requestbtn'] = $requestbtn;
		$lp_userview['pending'] = $pending;
		//$lp_userview['lpapproval'] = $lpapproval;
		$lp_userview['plan_startdate'] = $plan_startdate;
		$lp_userview['plan_enddate'] = $plan_enddate;
		$lp_userview['lplancredits'] = $lplan->credits;
		$lp_userview['mandatarycourses_count'] = $mandatarycourses_count;
		$lp_userview['optionalcourses_count'] = $optionalcourses_count;
		$lp_userview['linkpath'] = $lp_url;
		return $this->render_from_template('local_search/learningplaninfo', $lp_userview);
    }

    public function get_coursera_programs($courseid){
    	global $DB,$USER;
        $coursera_programs = $DB->get_records('local_coursera_programs', array('courseid'=>$courseid));
		foreach($coursera_programs as $programs){
			// print_r($programs);exit;
			$rowdata['courseid'] = $programs->courseid;
			$rowdata['programid'] = $programs->programcode;
			$rowdata['courseraid'] = $programs->id;
			$rowdata['coursename'] = $DB->get_field('course','fullname',array('id'=>$courseid)) ;
			$templatedata['programrow'][] = $rowdata;
		}
		$templatedata['contextid'] = $systemcontext->id;
		if(count($templatedata)>0){
			$templatedata['enable'] = true;
		}else{
			$templatedata['enable'] = false;
			$templatedata['emptycontent'] = get_string('noprograms', 'local_search');
		}
		return $this->render_from_template('local_search/courserapgcontent', $templatedata);
    }

}
