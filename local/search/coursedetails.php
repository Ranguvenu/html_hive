<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG,$USER, $DB, $PAGE, $OUTPUT;
use core_completion\progress;
use local_percipiosync\plugin;
use local_courses\output\search as search;
use local_request\api\requestapi;
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js_call_amd('local_classroom/classroom', 'load');
$PAGE->requires->js_call_amd('local_search/courseinfo', 'load');
$PAGE->requires->js_call_amd('local_request/requestconfirm', 'load', array());
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php';
require_once($CFG->dirroot.'/local/includes.php');

$id  = required_param('id', PARAM_INT); // Course id

$coursecontext =  context_course::instance($id);
$PAGE->set_context($coursecontext);
$PAGE->set_url('/local/search/coursedetails.php', array('id' =>$id));
require_login();
$PAGE->set_pagelayout('course');
$PAGE->requires->event_handler('#usernotcompleted_sessionprereq', 'click', 'M.util.show_confirm_dialog', array('message' => get_string('usernotcompleted_prereq', 'local_catalog'), 'callbacks' => array()));

$course = $DB->get_record('course', array('id'=>$id));
if(!$course){
	print_error('invalidcourseid');
}

$PAGE->set_title($course->fullname);
//$PAGE->set_heading($course->fullname);
$catalogurl = new moodle_url('/local/search/allcourses.php', array());
$PAGE->navbar->add(get_string('e_learning_courses','local_search'), $catalogurl);
$PAGE->navbar->add($course->fullname);
echo $OUTPUT->header();
echo '<div class="content_era_left">';
	if(!empty($course->category)){
		$course_category = $DB->get_field('course_categories', 'name', array('id'=>$course->category));
	}else{
		$course_category = 'NA';
	}
	$level = ($course->open_level) ? $course->open_level : 'NA';

	//$level = ($course->open_level) ? $course->open_level : 'NA';
	if(!empty($course->open_level)){
		$level = $DB->get_field('local_levels','name', array('id' => $course->open_level,), $strictness=IGNORE_MISSING);
	}else{
		$level = 'NA';
	}

	$coursetype = $DB->get_record('local_course_types', array('id' => $course->open_identifiedas));
	if(!empty($course->open_skill)){
		$skillsql = "SELECT GROUP_CONCAT(sk.name) FROM {local_skill} sk WHERE sk.id IN ($course->open_skill) ";
		$skills = $DB->get_field_sql($skillsql);  
	}else{
		$skills = 'N/A';
	}
	
	if(!empty($course->open_skill)){
		$skillcategory = $DB->get_field('local_skill_categories','name', array('id' => $course->open_skillcategory), $strictness=IGNORE_MISSING);
	}else{
		$skillcategory = 'N/A';
	}
	$completion = new \completion_info($course);
	if ($completion->is_enabled()) {
		$percentage = progress::get_course_progress_percentage($course, $USER->id);
		   if (!is_null($percentage)) {
			 $percentage = floor($percentage);
		   }
			 $progress  = $percentage; 
		}
	if (!$progress) {
		$progress = 0;
	} else {
		$progress = round($progress);
	}
	$url = !empty($course->open_url)?$course->open_url:'N/A';
	$url =	($url != 'N/A') ? '<a href = '.$url.' target ="_blank">Click here</a>' : 'N/A';
	if($course->duration){
        $hours = floor($course->duration/3600);
        $minutes = ($course->duration/60)%60;
        $c_duration =$hours.':'. $minutes;
    }else{
        $c_duration = 'NA';
    }
	if(is_null($course->open_grade) || $course->open_grade == '' || $course->open_grade == -1){
		$course_grade = get_string('all');
	}else{
		$course_grade = $course->open_grade;
	}
	if(!empty($course->open_courseprovider)){
		$courseprovider = $DB->get_field('local_course_providers','course_provider', array('id' => $course->open_courseprovider,'active' => 1), $strictness=IGNORE_MISSING);
	}else{
		$courseprovider = 'N/A';
	}

	$enrolmenttypes = 'N/A';
	//$enrolmenttype = $DB->get_field_sql("SELECT e.enrol FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {course} c ON c.id = e.courseid WHERE e.courseid = $course->id AND ue.userid = $USER->id AND e.status = 0");
	$enrolmethods = $DB->get_fieldset_sql("SELECT e.enrol FROM {enrol} e WHERE e.courseid = $course->id AND e.status = 0 AND e.enrol IN ('auto','manual','self')");
	if(!empty($enrolmethods))  {
		$enrolmenttypes = implode(', ', $enrolmethods);
	}

	$Coursefullname = $course->fullname;
  	$includes = new user_course_details();
	//$courseurl = $includes->course_summary_files($course);
	$courseurl = course_thumbimage($course);
	$expirydate = !empty($course->expirydate) ? date('d-M-Y',$course->expirydate): 'N/A';
	
	$coursecontext   = context_course::instance($course->id);
	$enroll = is_enrolled($coursecontext, $USER->id);

	echo '<div class="row  coursedet_row mb-4 p-0">
        <div class="col-md-12">
        <div class=" coursedet_left p-4" style="background-image:url('.$courseurl.')">  
		                          
            <div class="course_description col-md-8 col-12 justify-content-between d-flex flex-column">            
							<h1 class="course_title mb-4">'.$Coursefullname.'</h1>
            	<div class="col-md-7 d-flex mb-4 p-0">
	            		<div class = "progress ">
						<div class = "progress-bar" role = "progressbar" aria-valuenow = "60" aria-valuemin = "0" aria-valuemax = "100" style = "width: '.$progress.'%;">   
						<span class = "sr-only">'.$progress.'% Complete</span>

		   							</div>
									</div>
									<span class="percentage text-white">'.$progress.'%</span>
         			</div>';

				  echo	 '<div class="row mb-4">
				  <div class="col-md-8 ">
					  <div class="content_era_right">
					  <div class="enrol">';
					  $currenttime = time();

					  if (!is_siteadmin()) {

						if ($course->approvalreqd == 1) {
							if ($enroll == 0) {
								$componentid = $courseid;
								//$component = 'elearning';
								$component = 'elearning';
								$sql = "SELECT status FROM {local_request_records} WHERE componentid=:componentid AND compname LIKE :compname AND createdbyid = :createdbyid ORDER BY id desc ";
								$request = $DB->get_field_sql($sql, array('componentid' => $id, 'compname' => $component, 'createdbyid' => $USER->id));
			
								if ($request == 'PENDING') {
									echo '<button class="cat_btn btn-primary viewmore_btn" disabled="true">Processing</button>';
								} else {
									$action = 'add';
									echo '<a href="javascript:void(0);" class="courseselfenrol enrolled' . $id . '" alt = ' . get_string('requestforenroll','local_catalog'). ' title = ' .get_string('enroll','local_catalog'). ' onclick="(function(e){ require(\'local_request/requestconfirm\').init({componentid:'.$id.', component:\''.$component.'\', action:\''.$action.'\', componentname:\''.$course->fullname.'\' }) })(event)" ><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">'.get_string('requestforenroll','local_classroom').'</button></a>'; 
									//echo requestapi::get_requestbutton($componentid, $component, $courseinfo->fullname);
								}
							} else {
								echo '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $id . '" class=""><button class="crs_content btn btn-lg btn-primary ng-binding mb-2">' . get_string('start_now', 'local_search') . '</button></a>';
							}
						} else if ($course->selfenrol == 1) {
							if ($enroll == 0) {
								$currenttime = time();
								$string = get_string('selfenrol', 'local_search');
								if ($course->expirydate != 0) {
									if ($course->expirydate >= $currenttime) {
										$provider_shortname = $DB->get_field('local_course_providers', 'shortname', array('id' => $course->open_courseprovider));
			
										if($provider_shortname == 'percipio'){
											$userlicense = plugin::verify_userlicence($USER->email);
											if($userlicense == false){
												echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick = "(function(e){ require(\'local_learningplan/courseenrol\').percipiosync({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.',enroll:1,coursename:\''. $course->fullname.'\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
											}
									    } else if ($provider_shortname == 'udemy') {
											echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').test({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
										} else if ($provider_shortname == 'coursera') {
											$coursera_programs = $DB->get_records_sql('SELECT * FROM {local_coursera_programs} WHERE courseid =:courseid', array('courseid'=>$id));
											$programcodes = array_column($coursera_programs, 'programcode');
											$numberof_programs = count($coursera_programs);
											if($numberof_programs > 1){
												echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').courseratestprogram({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
											}else{
												$programcode = $programcodes[0];
												echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').courseratest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  , programcode: \'' . $programcode . '\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
											}
										} else {
											echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').coursetest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
										}
									} else if ($course->expirydate < $currenttime) {
										echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').courseexpiry({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
									}
								} else if ($course->expirydate == 0) {
									$courseprovider_shortname = $DB->get_field('local_course_providers', 'shortname', array('id' => $course->open_courseprovider));
			
									if($provider_shortname == 'percipio'){
										$userlicense = plugin::verify_userlicence($USER->email);
										if($userlicense == false){
											echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick = "(function(e){ require(\'local_learningplan/courseenrol\').percipiosync({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.',enroll:1,coursename:`' . $course->fullname . '` }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
										}
									} else if ($courseprovider_shortname == 'udemy') {
										echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').test({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
									} else if ($courseprovider_shortname == 'coursera') {
										$coursera_programs = $DB->get_records_sql('SELECT * FROM {local_coursera_programs} WHERE courseid =:courseid', array('courseid'=>$id));
										$programcodes = array_column($coursera_programs, 'programcode');
										$numberof_programs = count($coursera_programs);
										if($numberof_programs > 1){
											echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').courseratestprogram({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
										}else{
											$programcode = $programcodes[0];
											echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').courseratest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  , programcode: \'' . $programcode . '\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
										}
									} else {
										echo '<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').coursetest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '` }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">' . $string . '</button></a>';
									}
								}
							} //end of $enroll
			
						} else {
							echo '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $id . '" class=""><button class="cat_btn viewmore_btn btn">' . get_string('start_now', 'local_search') . '</button></a>';
						}
					}  
		

			echo			'</div>
					  </div>
				  </div>
				  
			  </div>'; 
				  
			echo '     <div class="row text-white m-0">
			      <div class="col-md-12 col-12">
            	<div class="course_detailscontent  justify-content-between d-flex ">
				        <div class="course_learning d-flex">
							<span class="leraning_name">Learning type</span>
							<span class="colon">:</span>
							<span class="text-white">'.$coursetype->course_type.'</span>
				        </div>
				        
						<div class="course_expiry  d-flex">
							<span class="learning_name">Expiry Date</span>
							<span class="colon">:</span>
							<span class="expiry_date text-white">'.$expirydate.'</span>
						</div>
				  		
						  
				      
            		</div>
            	</div>
        	</div>
        </div>
      </div>';      
    if(isloggedin()){
		$role = $DB->get_record('role_assignments', array('contextid'=>$coursecontext->id, 'userid'=>$USER->id));
		$is_teacher = $is_student = false;
		if($role){
			if($role->roleid==5){
				$is_student = true;
			} else if($role->roleid==3 || $role->roleid==4){
				$is_teacher = true;
			}
		}
	}
	
	$course_options = array();
	$enrolled = $DB->get_records('role_assignments', array('contextid'=>$coursecontext->id, 'userid'=>$USER->id));
	$enrolcount = $DB->count_records('role_assignments', array('contextid'=>$coursecontext->id, 'roleid' => 5)); 
		       
    $share = '<span class="addthis_toolbox addthis_default_style "
                                addthis:url="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'" >
                                <a class="addthis_button_facebook" addthis:title="'.$course->fullname.'"></a>
                                <a class="addthis_button_twitter" addthis:title="'.$course->fullname.'"></a>
                                <a class="addthis_button_linkedin" addthis:title="'.$course->fullname.'"></a>
			    <a class="addthis_button_compact" addthis:title="'.$course->fullname.'"></a>
                            </span>';
	$course_options[] = $share;

	echo html_writer::tag('div', implode(' | ', $course_options), array('class'=>'course_options'));

      	echo '</div>
			</div>
        <div class="col-md-3 coursedet_right d-none">
	        <div class="CourseDetils_container">
		    	 <div class="CourseDetils_content d-none">
		         	<img class="img_summary img-responsive" src="'.$courseurl.'" alt="img" />
		    	 </div>
	    	<div class="Course_content my-3 d-none">';

    		$managecoursecap = has_capability('local/courses:manage', $coursecontext);
        if($enrolled || is_siteadmin() || $managecoursecap){
        	echo '<div class="start_course mb-2">
		    		<a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">
		                <button type="button" class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">
		                   Start Now
		                </button>
		            </a>
        		</div>';
        	echo '<div class="view_gradeslink"><a class="view_links btn btn-block mb-2" href="'.$CFG->wwwroot.'/grade/report/user/index.php?id='.$course->id.'">View Grades</a></div>';
        }else{
		
        	$enrol = $DB->get_record('enrol', array('courseid'=>$id, 'enrol'=>'self'));
        	echo '';
		    
		  $currenttime = time();
		
		  //echo search::get_enrollbutton($enroll, $course);
		 /*  $provider_shortname = $DB->get_field('local_course_providers','shortname',array('id' => $course->open_courseprovider));
		 if($course->expirydate != 0){
		     if($course->expirydate > $currenttime){
		     	//udemy courseprovider sync
		    //  $provider_shortname = $DB->get_field('local_course_providers','shortname',array('id' => $course->open_courseprovider));
		       
		      if($provider_shortname == 'udemy'){
		       echo '<div class="content_era_right">
                         <div class="enrol">
                           <a data-action="courseselfenrol'.$id.'" class="courseselfenrol enrolled'.$id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').test({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.', enroll:1, coursename: \''.$course->fullname.'\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
                         </div>
                    </div>';
            } else if ($udemyprovider_shortname == 'coursera') {
				echo '<div class="content_era_right">
						<div class="enrol">
							<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').courseratest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
						</div>
					</div>';
			} else {
                echo '<div class="content_era_right">
						<div class="enrol">
							<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').coursetest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
                    	</div>
                   	</div>';
            }
		   } else if($course->expirydate < $currenttime){
		   	   echo '<div class="content_era_right">
                         <div class="enrol">
                           <a data-action="courseselfenrol'.$id.'" class="courseselfenrol enrolled'.$id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').courseexpiry({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.', enroll:1, coursename: \''.$course->fullname.'\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
                         </div>
                    </div>';
		   }

		  } else if($course->expirydate == 0){
		  	 // $udemyprovider_shortname = $DB->get_field('local_course_providers','shortname',array('id' => $course->open_courseprovider));
		  	  if($provider_shortname == 'udemy'){
		  	  	    echo '<div class="content_era_right">
                         <div class="enrol">
                           <a data-action="courseselfenrol'.$id.'" class="courseselfenrol enrolled'.$id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').test({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.', enroll:1, coursename: \''.$course->fullname.'\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
                         </div>
                         </div>';
			}else if ($provider_shortname == 'coursera') {
				echo '<div class="content_era_right">
						<div class="enrol">
							<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').courseratest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
						</div>
					</div>';
			} else {
		  	  	   echo '<div class="content_era_right">
						<div class="enrol">
							<a data-action="courseselfenrol' . $id . '" class="courseselfenrol enrolled' . $id . '" onclick ="(function(e){ require(\'local_search/courseinfo\').coursetest({selector:\'courseselfenrol' . $id . '\', courseid:' . $id . ', enroll:1, coursename: `' . $course->fullname . '`  }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
                    	</div>
                   	</div>';
		  	    }
		  	}*/
		}  

     echo '<div class="coursebrieflist col-12 p-0 mt-2">';
    	$careertrack = !empty($course->open_careertrack) ? $course->open_careertrack : "NA";
        $credits = !empty($course->open_points) ? $course->open_points : "NA";
    	  echo'<ul class="crse_details d-none">
		  
	    	        <li class="my-1 incentives__text">'.get_string('career_track_tag','local_users').': <b class="iteminfo">'.$careertrack.'</b>
	    	        </li>
					<li class="my-1 incentives__text">Category: <b class="iteminfo">'.$course_category.'</b>
					</li>
					<li class="my-1 incentives__text">Level: <b class="iteminfo">'.$level.'</b></li>
					<li class="my-1 incentives__text">Grade: <b class="iteminfo">'.$course_grade.'</b>
					</li>
				
					
				</ul>
	        </div>
        </div>
    </div>';
	echo '</div>';
	
    echo '<div class="row p-3 ">';
		if(strtolower($coursetype->shortname) == 'ilt'){
			echo '<div class="col-md-8 ">
			<div id="coursedetails">
				<ul>
					<li><a href="#courseindex">Index</a></li>
					<li><a href="#courseilts">ILT Session</a></li>					
				</ul>
				<div id="courseindex">'.course_sections($course->id).'</div>
				<div id="courseilts">'.course_batchesinfo($course->id).'</div>
			</div>
		</div>';
		}else{
			echo '<div class = "col-md-8 ">
					<h3 class = "col-md-8 p-0 col-12 bold "><strong>Description</strong></h3>
					<span>'.$course->summary.'</span>
			</div>';
	
		}
	echo	'<div class="col-md-4">
	            <div class="course_info">
	            	<h6 class="course_heading">Course Info</h6>
		            <ul class="crse_details ">
		    	        <li class="my-1 incentives__text d-flex align-items-center">
		    	        <span class="track_icon"></span>
		    	        '.get_string('career_track_tag','local_users').': <b class="iteminfo">'.$careertrack.'</b>
		    	        </li>
						<li class="my-1 incentives__text d-flex align-items-center">
						<span class="category_icon"></span>
						Category: <b class="iteminfo">'.$course_category.'</b>
						</li>
						<li class="my-1 incentives__text d-flex align-items-center">
						<span class="level_icon"></span>
						Level: <b class="iteminfo">'.$level.'</b></li>
						<li class="my-1 incentives__text d-flex align-items-center">
						<span class="grade_icon"></span>
						Grade: <b class="iteminfo">'.$course_grade.'</b>
						</li>
						<li class="my-1 incentives__text d-flex align-items-center">
						<span class="courseprovider_icon"></span>
						Course Provider: <b class="iteminfo">'.$courseprovider.'</b>
						</li>
						<li class="my-1 incentives__text url_course d-flex align-items-start">
						<span class="url_icon"></span>
						<span class="mt-1 d-flex">URL:
						<b class="iteminfo url">'.$url.'</b></span>
						</li>
						<li class="my-1 incentives__text duration_course d-flex align-items-start">
						<span class="duration_icon"></span>
						<span class="mt-1 d-flex">Duration:
						<b class="iteminfo url">'.$c_duration.'</b></span>
						</li>
						<li class="my-1 incentives__text skill_course d-flex align-items-center">
							<span class="skillcat_icon"></span>
							<span class="leraning_name">Skill Category:</span>
							<span class="iteminfo">'.$skillcategory.'</span>
						</li>
						<li class="my-1 incentives__text skill_course d-flex align-items-center">
							<span class="skill_icon"></span>
						        <span class="leraning_name">Skill:</span>
							<span class="iteminfo">'.$skills.'</span>
						</li>
						<li class="my-1 incentives__text url_course d-flex align-items-start">
							<span class="enroll_icon"></span>
							<span class="mt-1 d-flex">Enrolment Type:
							<b class="iteminfo url">'.ucwords($enrolmenttypes).'</b></span>
						</li>
					</ul>
				</div>
			</div>
		</div>';

    echo html_writer::script('$("#coursedetails").tabs();');

echo $OUTPUT->footer();
