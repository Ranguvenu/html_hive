<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG,$USER, $DB, $PAGE, $OUTPUT;

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js_call_amd('local_classroom/classroom', 'load');
$PAGE->requires->js_call_amd('local_search/courseinfo', 'load');

require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php';
require_once($CFG->dirroot.'/local/includes.php');

$id  = required_param('id', PARAM_INT); // Course id

$coursecontext = get_context_instance(CONTEXT_COURSE, $id);
$PAGE->set_context($coursecontext);
$PAGE->set_url('/local/search/coursedetails.php', array('id' =>$id));
require_login();
$PAGE->set_pagelayout('course');
$PAGE->requires->event_handler('#usernotcompleted_sessionprereq', 'click', 'M.util.show_confirm_dialog', array('message' => get_string('usernotcompleted_prereq', 'local_catalog'), 'callbacks' => array()));

$course = $DB->get_record('course', array('id'=>$id));
if(!$course){
	print_error('invalidcourseid');
}

// The URL of the search course details page for the enrol.
$searchurl = new moodle_url('/local/search/coursedetails.php', array('id' => $id));
redirect($searchurl);

$PAGE->set_title($course->fullname);
//$PAGE->set_heading($course->fullname);
$catalogurl = new moodle_url('/local/search/allcourses.php', array());
$PAGE->navbar->add(get_string('e_learning_courses','local_search'), $catalogurl);
$PAGE->navbar->add($course->fullname);
echo $OUTPUT->header();
echo '<div class="content_era_left">';

	$course_category = $DB->get_field('course_categories', 'name', array('id'=>$course->category));
	$level = ($course->open_level) ? $course->open_level : 'NA';

	if(is_null($course->open_grade) || $course->open_grade == '' || $course->open_grade == -1){
		$course_grade = get_string('all');
	}else{
		$course_grade = $course->open_grade;
	}
	$Courseullnfame = $course->fullname;
  	$includes = new user_course_details();
	$courseurl = $includes->course_summary_files($course);

	echo '<div class="row  coursedet_row mb-4 p-0">
        <div class="col-md-12">
        <div class=" coursedet_left p-4" style="background-image:url('.$courseurl.')">  
		                    
            <div class="course_description col-md-8 col-12 justify-content-between d-flex flex-column">            
							<h1 class="course_title mb-4">'.$Courseullnfame.'</h1>
            	<div class="col-md-7 d-flex mb-4 p-0">
	            		<div class = "progress ">
		   							<div class = "progress-bar" role = "progressbar" aria-valuenow = "60" aria-valuemin = "0" aria-valuemax = "100" style = "width: 73%;">   
		      							<span class = "sr-only">73% Complete</span>
		   							</div>
									</div>
									<span class="percentage text-white">73%</span>
         			</div>
            <div class="row mb-4">
            	<div class="col-md-8 ">
		            <div class="content_era_right">
		              <div class="enrol">
		                <a data-action="courseselfenrol'.$id.'" class="courseselfenrol enrolled'.$id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').test({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.', enroll:1, coursename: \''.$course->fullname.'\' }) })(event)"><button class="crs_content btn btn-lg btn-primary  ng-binding mb-2">Enrol</button></a>
		              </div>
		            </div>
            	</div>
			      	<div class="col-md-4 text-white mt-2">
					      <div class="course_expiry  d-flex">
							   	<span class="expiry_name">Expiry Date</span>
							 		<span class="colon">:</span>
							 		<span class="expiry_date">20 Nov 2022</span>
								</div>
			      	</div>
			      </div>
			      <div class="row text-white m-0">
			      <div class="col-md-12 col-12">
            	<div class="course_detailscontent  justify-content-between d-flex ">
				          <div class="course_learning d-flex">
							        <span class="leraning_name">Learning type</span>
							        <span class="colon">:</span>
							        <span class="text-white">MOOC,E-Learning,ILT,Embedded</span>
				          </div>
				          <div class="skill_course d-flex">
						        <span class="leraning_name">Skill</span>
						        <span class="colon">:</span>
						        <span class="text-white">SQL,Python,Mysql</span>
				          </div>
				          <div class="url_course d-flex">
							      <span class="leraning_name">URL</span>
							      <span class="colon">:</span>
							      <span class="text-white">https://xd.adobe.com/view/456f6f05</span>
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
           
		 if($course->expirydate != 0){
		     if($course->expirydate > $currenttime){
		     	//udemy courseprovider sync
		      $provider_shortname = $DB->get_field('local_course_providers','shortname',array('id' => $course->open_courseprovider));
		       
		      if($provider_shortname == 'udemy'){
		       echo '<div class="content_era_right">
                         <div class="enrol">
                           <a data-action="courseselfenrol'.$id.'" class="courseselfenrol enrolled'.$id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').test({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.', enroll:1, coursename: \''.$course->fullname.'\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
                         </div>
                    </div>';
            } else {
               echo '<div class="content_era_right">
					<div class="enrol">
						<form action="'.$CFG->wwwroot.'/enrol/index.php" method="post" id="mform1" class="mform" accept-charset="utf-8" autocomplete="off">
	            		<input type="hidden" value="'.$id.'" name="id">
	                    <input name="instance" value="'.$enrol->id.'" type="hidden">
	                    <input name="sesskey" value="'.sesskey().'" type="hidden">
	                    <input name="_qf__'.$enrol->id.'_enrol_self_enrol_form" value="1" type="hidden">
	                    <input name="mform_isexpanded_id_selfheader" value="1" type="hidden">
	                    <input type="submit" id="id_submitbutton" class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2" value="Enrol" name="submitbutton">
	                    </form>
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
		  	  $udemyprovider_shortname = $DB->get_field('local_course_providers','shortname',array('id' => $course->open_courseprovider));
		  	  if($udemyprovider_shortname == 'udemy'){
		  	  	    echo '<div class="content_era_right">
                         <div class="enrol">
                           <a data-action="courseselfenrol'.$id.'" class="courseselfenrol enrolled'.$id.'" onclick ="(function(e){ require(\'local_search/courseinfo\').test({selector:\'courseselfenrol'.$id.'\', courseid:'.$id.', enroll:1, coursename: \''.$course->fullname.'\' }) })(event)"><button class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2">Enrol</button></a>
                         </div>
                         </div>';
		  	  } else {
		  	  	   echo '<div class="content_era_right">
					<div class="enrol">
						<form action="'.$CFG->wwwroot.'/enrol/index.php" method="post" id="mform1" class="mform" accept-charset="utf-8" autocomplete="off">
	            		<input type="hidden" value="'.$id.'" name="id">
	                    <input name="instance" value="'.$enrol->id.'" type="hidden">
	                    <input name="sesskey" value="'.sesskey().'" type="hidden">
	                    <input name="_qf__'.$enrol->id.'_enrol_self_enrol_form" value="1" type="hidden">
	                    <input name="mform_isexpanded_id_selfheader" value="1" type="hidden">
	                    <input type="submit" id="id_submitbutton" class="crs_content btn btn-lg btn-primary w-full ng-binding mb-2" value="Enrol" name="submitbutton">
	                    </form>
                   	</div>
                   	</div>';
		  	  }
		  	}
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
					<li class="my-1 incentives__text">Credits: <b class="iteminfo">'.$credits.'</b>
					</li>
				</ul>
	        </div>
        </div>
    </div>';
	echo '</div>';

    echo '<div class="row p-3 ">
			<div class="col-md-8 ">
				<div id="coursedetails">
			        <ul>
			            <li><a href="#courseindex">Index</a></li>
			            <li><a href="#courseilts">ILT Session</a></li>
			        </ul>
			        <div id="courseindex">'.course_sections($course->id).'</div>
			        <div id="courseilts">'.course_batchesinfo($course->id).'</div>
			    </div>
			</div>
			<div class="col-md-4">
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
						Credits: <b class="iteminfo">'.$credits.'</b>
						</li>
					</ul>
				</div>
			</div>
		</div>';

    echo html_writer::script('$("#coursedetails").tabs();');

echo $OUTPUT->footer();
