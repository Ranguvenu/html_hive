<?php

/**
 * *************************************************************************
 * *                 OOHOO Tab topics Course format                       **
 * *************************************************************************
 * @package     format                                                    **
 * @subpackage  tabtopics                                                 **
 * @name        tabtopics                                                 **
 * @copyright   oohoo.biz                                                 **
 * @link        http://oohoo.biz                                          **
 * @author      Nicolas Bretin                                            **
 * @author      Braedan Jongerius                                         **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later  **
 * *************************************************************************
 * ************************************************************************ */
defined('MOODLE_INTERNAL') || die();
require_once $CFG->dirroot . '/course/format/renderer.php';
use core_completion\progress;
use core_component;
use core_courseformat\output\section_renderer; //Add for section <revathi>
class format_tabtopics_renderer extends section_renderer {

	/**
	 * Generate the starting container html for a list of sections
	 * @return string HTML to output.
	 */
	protected function start_section_list() {
		return html_writer::start_tag('ul', array('class' => 'tabtopics'));
	}

	/**
	 * Generate the closing container html for a list of sections
	 * @return string HTML to output.
	 */
	protected function end_section_list() {
		return html_writer::end_tag('ul');
	}

	/**
	 * Generate the title for this section page
	 * @return string the page title
	 */
	protected function page_title() {
		return get_string('topicoutline');
	}

	/**
	 * Generate the edit controls of a section
	 *
	 * @param stdClass $course The course entry from DB
	 * @param stdClass $section The course_section entry from DB
	 * @param bool $onsectionpage true if being printed on a section page
	 * @return array of links with edit controls
	 */
	//added section_edit_control_items  prevous section_edit_controls() <Revathi>
	protected function section_edit_control_items($course, $section, $onsectionpage = false) {
		global $PAGE;

		if (!$PAGE->user_is_editing()) {
			return array();
		}

		$coursecontext = context_course::instance($course->id);

		if ($onsectionpage) {
			$url = course_get_url($course, $section->section);
		} else {
			$url = course_get_url($course);
		}
		$url->param('sesskey', sesskey());

		$controls = array();
		if (has_capability('moodle/course:setcurrentsection', $coursecontext)) {
			if ($course->marker == $section->section) {
				// Show the "light globe" on/off.
				$url->param('marker', 0);
				$controls[] = html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marked'),
					'class' => 'icon ', 'alt' => get_string('markedthistopic'))), array('title' => get_string('markedthistopic'), 'class' => 'editing_highlight'));
			} else {
				$url->param('marker', $section->section);
				$controls[] = html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marker'),
					'class' => 'icon', 'alt' => get_string('markthistopic'))), array('title' => get_string('markthistopic'), 'class' => 'editing_highlight'));
			}
		}
		//section_edit_controls deprecated instead of used section_edit_control_items <revathi>
		return array_merge($controls, parent::section_edit_control_items($course, $section, $onsectionpage));
	}

	/**
	 * Displays the avaliability message if not visible
	 */
	public function section_availability_message($section, $canViewHidden) {
		echo parent::section_availability_message($section, $canViewHidden);
	}

	/**
	 * Display a hidden section message
	 *
	 * @param type $section
	 */
	public function section_hidden($section, $courseorid = NULL) {
		echo parent::section_hidden($section, $courseorid);
	}

    public function course_info_details($course) {
        global $CFG, $COURSE, $PAGE, $OUTPUT, $DB, $USER;
        require_once($CFG->dirroot.'/local/includes.php');

        $includes = new user_course_details();
        $return = '';

		// $courseimageurl = $includes->course_summary_files($course);
        $courseimageurl = course_thumbimage($course);
            

        $coursename = $course->fullname;

        // $course_duration = $course->open_coursecompletiondays != NULL ? $course->open_coursecompletiondays: 'N/A';
        $course_points = $course->open_points != NULL ? $course->open_points: 'N/A';
        $course_instructor = $course->open_coursecreator != NULL ? $course->open_coursecreator: 'N/A';

        $return .= html_writer::start_tag('div', array('class'=>'course_page_info_section'));

        $return .= html_writer::start_tag('div', array('class'=>'course_image_container d-none d-md-block'));
        $return .= html_writer::tag('div', '', array('style'=>'background-image: url("'.$courseimageurl.'");', 'class'=>'courseimage format_course_image'));
        $return .= html_writer::end_tag('div');

        // $return .= html_writer::tag('div', html_writer::tag('h3', $coursename, array('class'=>'course_name')), array('class'=>'course_name_container'));

        $course_detail = '';

        // Ratings for courses
      //   $ratings_plugin_exist = core_component::get_plugin_directory('local', 'ratings');
      //   if($ratings_plugin_exist){
      //   	require_once($CFG->dirroot . '/local/ratings/lib.php');
      //   	/*$PAGE->requires->jquery();
	    	// $PAGE->requires->js('/local/ratings/js/jquery.rateyo.js');
	    	// $PAGE->requires->js('/local/ratings/js/ratings.js');*/
      //   	$course_duration = display_rating($COURSE->id,'local_courses');
      //   	$course_like = display_like_unlike($COURSE->id,'local_courses');
      //   	$course_review = display_comment($COURSE->id,'local_courses');

      //   	$course_detail .= html_writer::start_tag('p', array('class'=>'course_duration m-0'));
      //   	$course_detail .= html_writer::tag('span', $course_duration, array('class'=>'ml-15 course_detail_value'));
	     //    $course_detail .= html_writer::end_tag('p');

	     //    $course_detail .= html_writer::start_tag('span', array('class'=>'course_like'));
	     //    $course_detail .= html_writer::tag('span', '<span class="course_detail_labelname"></span>', array('class'=>'course_detail_label'));
	     //    $course_detail .= html_writer::tag('span', $course_like, array('class'=>'ml-15 course_detail_value'));
	     //    $course_detail .= html_writer::end_tag('span');
	     //    $course_detail .= html_writer::tag('span', '<span class="course_detail_labelname"></span>', array('class'=>'course_detail_label'));
	     //    $course_detail .= html_writer::tag('span', $course_review, array('class'=>'ml-15 course_detail_value'));
	     //    $course_detail .= html_writer::end_tag('span');
      //   }
        
        $catfa = html_writer::tag('i','', array('class' => 'fa fa-list-alt Cfaicon', 'aria-hidden' => 'true'));
        $catlable = html_writer::tag('label','Catergory', array('class' => 'course_flexlab w-full mb-2'));
        $catName = $DB->get_field('course_categories', 'name', array('id' => $COURSE->category));
        // $displayCatName = strlen($catName) > 12 ? substr($catName, 0, 10).'...' : $catName;
        $catspan = html_writer::tag('span',$catName, array('class' => 'c_y_n w-full bgcat bg_Ccourse text-truncate d-inline-block', 'title' => $catName));
        $catcontainer = html_writer::tag('div',$catfa . $catlable . $catspan, array('class'=>'col-md-4 text-center'));

        $Cskillfa = html_writer::tag('i','', array('class' => 'fa fa-cogs Cfaicon', 'aria-hidden' => 'true'));
        $Cskilllable = html_writer::tag('label','Type', array('class' => 'course_flexlab w-full mb-2'));

        if($COURSE->open_identifiedas == 1){
        	$coursetype = 'MOOC';
        }else if($COURSE->open_identifiedas == 2){
        	$coursetype = 'ILT';
        }else {
        	$coursetype = 'E-Learning';
        }

        // $skillname = $COURSE->open_identifiedas ? $DB->get_field('local_skill', 'name', array('id' => $COURSE->open_skill)) : 'N/A';

        $Cskillspan = html_writer::tag('span', $coursetype, array('class' => 'c_y_n w-full bgskill bg_Ccourse text-truncate d-inline-block', 'title' => $skillname));
        $skillcontainer = html_writer::tag('div',$Cskillfa . $Cskilllable . $Cskillspan, array('class'=>'col-md-4 text-center'));

        $Ccoursfa = html_writer::tag('i','', array('class' => 'fa fa-language Cfaicon', 'aria-hidden' => 'true'));
        $Ccourslable = html_writer::tag('label','Credits', array('class' => 'course_flexlab w-full mb-2'));
        $points = !empty($COURSE->open_points) ? $COURSE->open_points : 0;
        $Ccoursspan = html_writer::tag('span', $points, array('class' => 'c_y_n w-full bglang text-truncate d-inline-block'));

        $Ccoursecontainer = html_writer::tag('div',$Ccoursfa . $Ccourslable . $Ccoursspan, array('class'=>'col-md-4 text-center'));
        
        $courselistContainer = html_writer::tag('div',$catcontainer . $skillcontainer . $Ccoursecontainer, array('class'=>'col-md-12 d-flex flex-row flex-wrap align-items-center m-y-1 pl-0 pr-0 pb-2'));
        $return .= html_writer::tag('div', $courselistContainer, array('class' => 'course_detail_container'));

        // $abtinstr = html_writer::tag('h6','About Instructor',array('class' => 'abt_instr'));
        
        $userrecord = $DB->get_record('user', array('id' => $USER->id));
        // $user_image = $OUTPUT->user_picture($userrecord, array('size' => 50, 'link' => false));
        // $user_name = html_writer::tag('span',$USER->firstname . ' ' . $USER->lastname, array('class'=>'user_name mr-2'));
        $user_email = $USER->email;
        // $instr_img_container = html_writer::tag('span',$user_image, array('class'=>'instructor_profile d-inline-block mr-2'));

        // $instr_envelope = html_writer::tag('i','',array('class' =>'fa fa-envelope mr-2'));
        // $instr_user = html_writer::tag('i','',array('class' =>'fa fa-user mr-2'));
        // $instr_email = html_writer::tag('span',$user_email, array('class'=>'instructor_email w-full'));
        // $instr_profile_container = html_writer::tag('span',$user_name . $instr_envelope . $instr_user, array('class'=>'instructor_profile_info w-full mb-1'));
        // $instructur = html_writer::tag('span',$instr_profile_container . $instr_email,array('class'=>'instructor_Content d-flex flex-column flex-wrap'));

        // $instrcontent = html_writer::tag('div', $instr_img_container . $instructur, array('class'=>'CourseprfileContainer w-full d-flex flex-row flex-wrap align-items-center'));
        // $return .= html_writer::tag('hr', '' , array('class'=>'Chr'));
        // $return .= $abtinstr;
        // $return .= html_writer::tag('div', $instrcontent , array('class'=>'Csubprofile pt-2 p-b-1'));
        $return .= html_writer::end_tag('div');

        $return .= $this->course_progress_details($course);
        
  //       $local_gamification_exist = $core_component::get_plugin_directory('local', 'gamification');
  //       if($local_gamification_exist){
	 //        $weeklylb = new \gamificationboards_leaderboard\view();
	 //        $return .= $weeklylb->view_course_leaderboard();
		// }

        return $return;
    }

    public function course_progress_details($course){
        global $DB, $USER, $CFG,$PAGE, $COURSE;

        // require_once($CFG->dirroot.'/local/includes.php');
        
        // $includes = new user_course_details();
	    

		$rolesql = "SELECT r.archetype  FROM {role_assignments} as ra
                          JOIN {context} as c on ra.contextid = c.id 
                         JOIN {role} as r on ra.roleid = r.id
                     WHERE c.contextlevel = 50 AND c.instanceid = $course->id
                      AND ra.userid = $USER->id";
        $role = $DB->get_record_sql($rolesql);

        if($role || is_siteadmin()){
	        if($role->archetype == 'student'){
	        	$table = new html_table();
				$table->align=array('center');
		    	$percent = $this->percentage_completed($course);
			    $progress_bar_width = "min-width: 0px;";
			    $progress_class = " progress-bar-success";
				if(!$percent){
				    // $progress = 0;
				    $progress_bar_width = " min-width: 20px;";
				    $progress_class = " progress-bar-danger";
				}else{
				    // $progress = round($percent)."%";
				    $progress_bar_width = "min-width: 20px;";
				    $progress_class = " progress-bar-success";
				}
				$coursename = $DB->get_field('course','fullname',array('id'=>$course->id));
				// print_object($coursename);
			    $progress .= '<div id = "progressbardisplay_course" class="c100 p'.$percent.' large green center" data-name="'.$coursename.'" onclick="(function(e){ require(\'local_courses/coursestatus\').statuspopup({selector:\'showcoursestatus\', context:1, courseid:'.$course->id.' }) })(event)">
			                        <span>'.$percent."%".'</span>
			                            <div class="slice">
			                                <div class="bar"></div>
			                                <div class="fill"></div>
			                            </div>
			                    </div>';
			 //    $progress .= html_writer::start_tag('div',array('id' => 'dialog','style'=>' display : none'));
				// $display = $this->get_data($course);
				// $progress .=  '<div id=scrolled>';
				// $progress .= html_writer::table($display);
				// $progress .= '</div>';
		  //       $progress .= html_writer::end_tag('div');
		       
		        //$link = "<div class='text-center' style = 'margin-top:10px'><button type='button' class='center' onclick=popup('".$course->shortname."');>View more</button></div>";
		        $link = "<div class='text-center d-block clear' style = 'margin-top:10px'><button type='button' class='viewInfo center' onclick='(function(e){ require(\"local_courses/coursestatus\").statuspopup({selector:\"showcoursestatus\", context:1, courseid:".$course->id." }) })(event)'>View info</button></div>";
		        $progressContainer = html_writer::tag('div', $progress . $link, array('class'=>'progressContainer'));
		      	$completion = new \completion_info($course);
   				$modules = $completion->get_activities();
			    $totalactivitiescount = count($modules);
			    $completed = 0;
			    $data_inprogress = array();
			    $data_completed = array();
				foreach ($modules as $module) {
				    $data = $completion->get_data($module, false, $userid);
				    if($data->completionstate == 0){
					$data_inprogress[] = $data->completionstate;
				    }else{
					$data_completed[] = $data->completionstate;
				    }
				}
   				$totalactivities = $totalactivitiescount;
   				$completedactivities = count($data_completed);
   				$inprogressactivities = count($data_inprogress);
		        
		        $totalactivitiesdata = html_writer::tag('span', $totalactivities, array('class'=>'course_stat_value'));

		        $totalactivitiesdata .= html_writer::tag('span', get_string('totalactivities','format_tabtopics'), array('class'=>'course_stat_label')); 

		        $inprogressactivitiesdata = html_writer::tag('span', $inprogressactivities, array('class'=>'course_stat_value'));

		        $inprogressactivitiesdata .= html_writer::tag('span',get_string('inprogress','format_tabtopics') , array('class'=>'course_stat_label')); 
		        $completedactivitiesdata = html_writer::tag('span', $completedactivities, array('class'=>'course_stat_value'));

		        $completedactivitiesdata .= html_writer::tag('span', get_string('activitiescompleted','format_tabtopics'), array('class'=>'course_stat_label'));

		        $activitieshdr = html_writer::tag('h5',get_string('activities','format_tabtopics'),array('class'=>'textclass'));
		        $course_progress_stat = html_writer::tag('div', $totalactivitiesdata, array('class'=>'course_progress_stat text-center'));
		        $inprogressstat = html_writer::tag('div', $inprogressactivitiesdata, array('class'=>'course_progress_stat text-center'));
		        $completestat = html_writer::tag('div', $completedactivitiesdata, array('class'=>'course_progress_stat text-center'));
		        $actdetailscontent = html_writer::tag('div',$activitieshdr . $course_progress_stat . $inprogressstat . $completestat, array('class'=>'actvitiinfo'));
		        $details .= $actdetailscontent;

		        $output = '';
			    if($percent){
			        // $output .= $progress;
			        // $output .= $link;
			        $output .= $progressContainer;
			        $output .= $details;
			    }else{
				    $output .= '<div class="text-center alert alert-info w-full">'.get_string('notyetstarted','format_tabtopics').'</div>';
		        }

	        	$return = '';
	        	
	        	// code added by Raghuvaran code review
	        	$Creview = html_writer::tag('h6','Course Review',array('class' => 'abt_instr'));
	        	$raitingvalue = $DB->get_field('local_ratings_likes', 'module_rating', array('module_area'=>'local_courses', 'module_id'=>$COURSE->id));
	        	$starnum = $raitingvalue ? round($raitingvalue,2) : 'N/A';
				$ratingCount = html_writer::tag('span',$starnum,array('class'=>'ratingCount w-full text-center'));
				$ratinfa = display_rating($COURSE->id,'local_courses');
				$ratinfacontent = html_writer::tag('span',$ratinfa,array('class'=>'ratinfacontent w-full'));
				
				$ratingnum = html_writer::tag('span',$starnum . ' ratings',array('class'=>'ratingnum'));
				$ratingstars = html_writer::tag('span',$ratinfacontent . $ratingnum,array('class'=>'ratingstars text-center'));

				$ratingList = html_writer::tag('div','',array('class'=>'ratingList', 'id'=>'ratingList', 'data-itemid' => $COURSE->id, 'data-ratearea' => 'local_courses'));

				$ratingsubContainer = html_writer::tag('div',$ratingCount . $ratingstars,array('class'=>'ratingsubContainer'));

				$ratingContainer = html_writer::tag('div',$ratingsubContainer . $ratingList,array('class'=>'ratingContainer'));
				$ratingamainContent = html_writer::tag('div',$Creview . $ratingContainer,array('class'=>'ratingamainContent'));
				$return .= $ratingamainContent;
				// code Course review ends here

		        $return .= html_writer::start_tag('div', array('class'=>'course_page_info_progress'));
		        $return .= html_writer::tag('h5',get_string('coursestatus','format_tabtopics') , array('class'=>'course_progress_stats'));

		        $return .= html_writer::start_tag('div', array('class'=>'course_progress_stats_container'));

		        $return .= $output;
		        $return .= html_writer::end_tag('div');

		        $return .= html_writer::end_tag('div');

		        // code added by Raghuvaran code ends
		        
		        $circlecss = "@import url('".$CFG->wwwroot."/course/format/tabtopics/css/circle.css');";
		        $return .= html_writer::tag('style', $circlecss, array());
	        }else{
	        	$coursecontext = context_course::instance($course->id);
		        $ccsql = "SELECT count(id) as ccount from {course_completions} where course = $course->id AND timecompleted IS NOT NULL";

		        $enrolled_count = count(get_enrolled_users($coursecontext));
		        $completed_count = $DB->get_record_sql($ccsql);
		        $inprogess_count = $enrolled_count - $completed_count->ccount;

		        $enrolled_details = html_writer::tag('span', $enrolled_count, array('class'=>'course_stat_value'));
		        $enrolled_details .= html_writer::tag('span', get_string('enrolled','format_tabtopics'), array('class'=>'course_stat_label'));

		        $inprogress_details = html_writer::tag('span', $inprogess_count, array('class'=>'course_stat_value'));
		        $inprogress_details .= html_writer::tag('span', get_string('inprogress','format_tabtopics'), array('class'=>'course_stat_label'));

		        $completed_details = html_writer::tag('span', $completed_count->ccount, array('class'=>'course_stat_value'));
		        $completed_details .= html_writer::tag('span', get_string('completed','format_tabtopics'), array('class'=>'course_stat_label'));

		        $return = '';

		        $return .= html_writer::start_tag('div', array('class'=>'course_page_info_progress'));
		        $return .= html_writer::tag('h5', get_string('coursestatus','format_tabtopics'), array('class'=>'course_progress_stats'));

		        $return .= html_writer::start_tag('div', array('class'=>'course_progress_stats_container'));

		        $return .= html_writer::tag('div', $enrolled_details, array('class'=>'course_progress_stat text-center'));
		        $return .= html_writer::tag('div', $inprogress_details, array('class'=>'course_progress_stat text-center'));
		        $return .= html_writer::tag('div', $completed_details, array('class'=>'course_progress_stat text-center'));

		        $return .= html_writer::end_tag('div');

		        $return .= html_writer::end_tag('div');
	        }
	    }else{
	    	return false;
	    }

	    if($COURSE->open_skill){
	    	$sql = "SELECT GROUP_CONCAT(name SEPARATOR ', ')
	    			FROM {local_skill}
	    			WHERE id IN ($COURSE->open_skill) ";

	    	$courseskills = $DB->get_field_sql($sql);
	    }else{
	    	$courseskills = 'N/A';
	    }

	    $return .= html_writer::start_tag('div', array('class'=>'course_page_info_progress'));
        $return .= html_writer::tag('h5', get_string('skills','format_tabtopics'), array('class'=>'course_progress_stats'));
        $return .= html_writer::tag('div', $courseskills, array('class'=>'course_progress_stat text-center'));

        $return .= html_writer::end_tag('div');
		
        return $return;
    }

	public function get_data($course){
		global $DB,$USER;
		$info = new completion_info($course);
		
		// Is course complete?
		$coursecomplete = $info->is_course_complete($USER->id);

		// Has this user completed any criteria?
		$criteriacomplete = $info->count_course_user_data($USER->id);
		// Load course completion.
			$params = array(
			    'userid' => $USER->id,
			    'course' => $course->id,
			);
			$completions = $info->get_completions($USER->id);
			$ccompletion = new completion_completion($params);

			if ($coursecomplete) {
			    echo get_string('complete');
			} else if (!$criteriacomplete && !$ccompletion->timestarted) {
			    echo html_writer::tag('i', get_string('notyetstarted', 'completion'));
			} else {
			    echo html_writer::tag('i', get_string('inprogress', 'completion'));
			}

		
		// return $completions;
		$rows = array();
	    // Loop through course criteria.
	    foreach ($completions as $completion) {
	        $criteria = $completion->get_criteria();
	        $row = array();
		        $row['type'] = $criteria->criteriatype;
		        $row['title'] = $criteria->get_title();
		        $row['status'] = $completion->get_status();
		        $row['complete'] = $completion->is_complete();
		        $row['timecompleted'] = $completion->timecompleted;
		        $row['details'] = $criteria->get_details($completion);
		        $rows[] = $row;

	    	}
	    // Print table.
	    $last_type = '';
	    $agg_type = false;
	    $oddeven = 0;
	    $table = new html_table();
	    $table->head = array(get_string('criteriagroup','format_tabtopics'),get_string('criteria','format_tabtopics'),get_string('requirement','format_tabtopics'),get_string('status','format_tabtopics'),get_string('complete','format_tabtopics'),get_string('completiondate','format_tabtopics'));
	    // $table->head=array('Criteriagroup','Criteria','Requirement','Status','Complete','Completion date');
	    $table->size=array('20%','20%','25%','5%','5%','25%');
	    $table->align=array('left','left','left','center','center','center');
	    $table->id = 'scrolltable';
	    foreach ($rows as $row) {
	    	if ($last_type !== $row['details']['type']) {
            $last_type = $row['details']['type'];
            $agg_type = true;
            }else {
            // Display aggregation type.
            if ($agg_type) {
                $agg = $info->get_aggregation_method($row['type']);
                // print_object($agg);
                $last_type .= '('. html_writer::start_tag('i');
                if ($agg == COMPLETION_AGGREGATION_ALL) {
                    $last_type .= core_text::strtolower(get_string('all', 'completion'));
                } else {
                    $last_type .= core_text::strtolower(get_string('any', 'completion'));
                }
                $last_type .= html_writer::end_tag('i') .core_text::strtolower(get_string('required')).')';
                $agg_type = false;
            }
        }
        if ($row['timecompleted']) {
            $timecompleted=userdate($row['timecompleted'], get_string('strftimedate', 'langconfig'));
        } else {
            $timecompleted = '-';
        }
	    	$table->data[] = new html_table_row(array($last_type,$row['details']['criteria'],$row['details']['requirement'],$row['details']['status'],$row['complete'] ? get_string('yes') : get_string('no'),$timecompleted));
	    	$oddeven = $oddeven ? 0 : 1;
	    }

	    return $table;
	}
	public function percentage_completed($course){// to know the percentage of completed activities in a particular course
	global $DB,$USER;
	// $tcountsql = 'SELECT count(id) as total from {course_modules} where course ='.$id;
 //    $tcount = $DB->get_record_sql($tcountsql);

 //    $ccountsql= 'SELECT  COUNT( cm.id ) as completed from {course_modules} as cm JOIN {course_modules_completion} as cmc ON cmc.coursemoduleid = cm.id WHERE cm.course ='.$id.' AND cmc.userid ='.$USER->id;
 //    $ccount = $DB->get_record_sql($ccountsql);  
 //    if($ccount->completed)  {
 //        $percent = round(($ccount->completed/($tcount->total-1))*100);
 //    }
 //    else{
 //        $percent = 0;
 //    }
	 $completion = new \completion_info($course);

            // First, let's make sure completion is enabled.
            if ($completion->is_enabled()) {
                $percent = progress::get_course_progress_percentage($course, $USER->id);

                if (!is_null($percent)) {
                    $percent = floor($percent);
                }

                // add completion data in course object
               // $course->completed = $completion->is_course_complete($userobject->id);
               $percent  = $percent;
            }
    return $percent;
}

    public function formatted_coursesummary($course){
        global $OUTPUT;

        $summary = $course->summary;

        $return = '';
        $return .= '<div class="course_summary_heading">Summary</div>';
        if(empty($summary)){
            $return .= html_writer::tag('div', get_string('nodescriptionprovided','format_tabtopics'), array('class'=>'alert alert-info text-center'));
        }else{
            // $summary_without_tags = strip_tags($course->summary);
            // $summary_length = strlen($summary_without_tags);
            // if($summary_length > 200){
                // $summary = '<div>';
            // }else{
            $return .= '<div class="course_summary">'.$summary.'</div>';
            // }
        }
        return $return;
    }
}
