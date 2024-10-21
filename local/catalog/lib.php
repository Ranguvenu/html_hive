<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_catalog
 */
use local_classroom\classroom as clroom;
use local_catalog\output\classroom as cl_output;

    defined('MOODLE_INTERNAL') || die();

    /**
     * @param object $coursedetails 
     */
  
	function disable_course_enroll_msg($coursedetails) {
        global $DB;
        $current_date = strtotime(date("d M Y"));
		
		if($coursedetails->enrollstartdate!=0){
		    $start_date     = strtotime(date('d M Y', $coursedetails->enrollstartdate));
		}else{
			$start_date=0;
		}
		
		if($coursedetails->enrollenddate!=0){
		    $end_date     = strtotime(date('d M Y', $coursedetails->enrollenddate));
		}else{
			$end_date=0;
		}
	
		$result= false;			  
		if(($start_date <= $current_date) && ($end_date >= $current_date)){
			
            $result = true;
        }
		
        return $result;
    }
	function disable_course_enroll_msgwait($coursedetails) {
		
        global $DB;
        $current_date = strtotime(date("d M Y"));
		
		if($coursedetails->enrollstartdate!=0){
		    $start_date     = strtotime(date('d M Y', $coursedetails->enrollstartdate));
		}else{
			$start_date=0;
		}
		
		if($coursedetails->enrollenddate!=0){
		    $end_date     = strtotime(date('d M Y', $coursedetails->enrollenddate));
		}else{
			$end_date=0;
		}
	
				  
		if(($start_date >= $current_date) && ($end_date >= $current_date)){
			
            $result= true;	
        }
		
        return $result;
    }
	function disable_course_enrol_enrol($coursedetails){
		 global $DB,$USER;
			$sql="select enrol,id from {enrol} where courseid=$coursedetails->courseid";
            $get_data=$DB->get_records_sql_menu($sql);
            $data=implode(',',$get_data);
        
        /********This below query is used to check the user already enroled to course with other enrolments methods******/
            $sql="select id from {user_enrolments} where enrolid IN($data) and userid=$USER->id";
            $check=$DB->record_exists_sql($sql) ;
			$data=true;
			if($check){
				$data=false;
			}
			return $data;
	}
	function learning_plan_information($uid) {
        global $CFG, $DB, $PAGE, $OUTPUT, $USER;
        $table = new html_table();
		$table->id = "lp_plan_info";
        $table->head = array('');
        $table->attributes = array('class' => 'lp_display generaltable lp_newclass');
        $table->width = '100%';
        $is_manager = $DB->record_exists_sql("select cp.* from {local_costcenter_permissions} as cp 
                             JOIN {role_assignments} as ra ON ra.userid=cp.userid and cp.userid=$USER->id
                             JOIN {role} as r ON r.id=ra.roleid
                             where r.archetype='manager'");
        $costcenter = new costcenter();
       
        $sql = "SELECT ll.* 
				FROM {learning_learningplan} AS ll
				JOIN {learning_user_learningplan} AS ul ON ul.lp_id = ll.id
				JOIN {user} AS u ON u.id = ul.u_id";
				
			
		
        $sql .= " WHERE u.id =$uid";
        $rs = $DB->get_records_sql($sql, array(), null, null);
		if($rs){
          $data = array();
          foreach ($rs as $log) {
			$completion_status = learning_plan_completions($log->id,$userid = NULL);
			$total_credits = learning_plan_completions_credits($log->id);
			
			if ($completion_status >= $log->credit_points)
			
			 $status = get_string('status_completed','block_learning_plan');
			else
			$status = get_string('status_not_completed','block_learning_plan');
            $row = array();
            $buttons = array();
            $add_training = get_string('add_training', 'block_learning_plan');
            $assign_learningplan_user = get_string('assign_learningplan_user', 'block_learning_plan');
            $courselist = $DB->get_fieldset_sql("select lp.t_id from {learning_plan_training} as lp INNER JOIN {course} as c ON lp.t_id=c.id where lp.lp_id=$log->id group by lp.t_id");
           $courses = implode(',',$courselist);
		  if(!empty($courses))
		  $DB->execute('delete from {learning_plan_training} where  lp_id='.$log->id.' AND t_id not in('.$courses.')');
            $courses_count = $DB->count_records_sql("select count('t_id') from {learning_plan_training} WHERE lp_id=$log->id");
            $users_count = $DB->count_records_sql("select count('u_id') from {learning_user_learningplan} WHERE lp_id=$log->id");
            $completed_lp_count = completed_learningplan_count($log->id);
			$grades = 'Grade:'.$log->grade;
			$career_track = 'Career Track:'.$log->career_track;
            $courses_count_link = 'Courses: ' . $courses_count . '';
            $users_count_link = 'Users: ' . $users_count . '';
			$completed_lp_count_link = html_writer::link('javascript:void(0)', 'Completed employes: ' . count($completed_lp_count) . '', array('id' => 'clpemp' . $log->id . '', 'onclick' => 'assign_manager(' . $log->id . ',"dialogclpemp")'));
            $PAGE->requires->event_handler('#deleteconfirm' . $log->id . '', 'click', 'M.util.tmahendra_show_confirm_dialog', array('message' => get_string('plan_delete', 'block_learning_plan'), 'callbackargs' => array('id' => $log->id, 'extraparams' => '&rem=remove&delete=' . $log->id . '&viewpage=1')));
			
			
				if(!empty($total_credits)){
				$tcredits = $total_credits;
			}else{
				$tcredits = 0;
			}
			
			$lpdates= "<table class = 'lp_batchdatess'>
						<tbody>
						<tr>
						<td><i>Career Track</i><b><i> : ".$log->career_track."</i></b></td>
						<td><i>Grade</i><b><i> : ".$log->grade."</i></b></td>
						</tr>
						<tr>
						<td><i>Total Credits</i><b><i> : ".$tcredits."</i></b>
						<td><i>Require Credit Points</i><b><i> : ".$log->credit_points."</i></b></td>
						</tr>
						<tr>
						<td><i>Type</i><b><i> : ".$log->learning_type."</i></b></td>
						<td><i>Status</i><b><i> : ".$status."</i></b></td>
						</tr>
						</tbody>
						</table>";
						
						
			$innercontent = '<div id="'. $log->id .'" class="toogleplhide"><span class="lp_class_inner" ><div id="demo' . $log->id . '">
                            <ul>
                            <li><a href="' . $CFG->wwwroot . '/blocks/learning_plan/ajax.php?page=5&lp=' . $log->id . '">' . $courses_count_link . '</a></li>
                            </ul>
                           </div></span></div>';
            $costcenter_name = $DB->get_field('local_costcenter','fullname',array('id'=>$log->costcenter));
			$innercontent .= html_writer::script('$(function() {
                                    $( "#demo' . $log->id . '" ).tabs({
                                    beforeLoad: function( event, ui ) {
                                    ui.jqXHR.fail(function() {
                                                ui.panel.html(
                                                            "Couldn\'t load this tab. We\'ll try to fix this as soon as possible. " +
                                                             "If this wouldn\'t be a demo." );
                                                });
                                    ui.panel.html("<center><img src=\"' . $CFG->wwwroot . '/blocks/learning_plan/images/loading.gif\" /></center>")
                                    },
                                    collapsible: true,
                                    active: false
                                    });});');
			if(is_siteadmin())
			$costcenterinfo = '<span class="lp_ccinfo">'.get_string("pluginname","local_costcenter").':<b>'.$costcenter_name.'</b></span>';
            else
			$costcenterinfo ='';
			
			$row[] = '<div id="pl_'. $log->id .'" class="pl_newtoggle"  /*onclick="Show_Div('.$log->id.')"*/><h5 id="lp_heading" class="span12"><span id="arrow'. $log->id .'" class="test lpdownarrow"></span>' . format_string($log->learning_plan, false).'</h5>'.$lpdates.'<span id="lp_actions">'.'</span></div>'.$innercontent;
			
				
			$table->data[] = new html_table_row($row);
		    }
			
			$table = html_writer:: table($table);
			return $table;
        
		}else{
			return false;
		}
    }
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
// function local_catalog_leftmenunode(){
//     $systemcontext = context_system::instance();
//     $catalognode = '';
//     if(has_capability('local/catalog:viewcatalog',$systemcontext) || is_siteadmin()){
//         $catalognode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_calalogue', 'class'=>'pull-left user_nav_div calalogue'));
//             $catalog_url = new moodle_url('/local/catalog/allcourses.php');
//             $catalog = html_writer::link($catalog_url, '<i class="fa fa-search" aria-hidden="true"></i><span class="user_navigation_link_text">'.get_string('leftmenu_catalog','local_catalog').'</span>',array('class'=>'user_navigation_link'));
//             $catalognode .= $catalog;
//         $catalognode .= html_writer::end_tag('li');
//     }

//     return array('22' => $catalognode);
// }

 function course_sections($courseid){
        global $DB;
        
        $sql = "select cs.id, c.id as courseid, c.fullname, c.format, c.startdate,cs.id as sectionid, cs.section, cs.name, cs.summary, cs.sequence
                from {course} c
                join {course_sections} cs
                on c.id = cs.course
                where c.id = $courseid and cs.section != 0";
        
        $course_sections = $DB->get_records_sql($sql);
        
        $course_summary = $DB->get_field('course', 'summary', array('id'=>$courseid));
		$section = '';
        if($course_summary){
            $section .= "<div>".$course_summary."</div>";
        }
		$section .= "<div id='courseallsections'>"; 
		
		foreach($course_sections as $course_section){
			
			if(!empty($course_section->name)){
				$section .= "<h3 class='course_section'>".$course_section->name."</h3>";
				$section .= "<div>";
				if(!empty($course_section->sequence)){
					$c_activities = explode(',', $course_section->sequence);
                    foreach($c_activities as $module){ //In sequence wise modules
                        $module_record = $DB->get_record('course_modules', array('id'=>$module, 'visible'=>1));
                        if(!empty($module_record)){
                            $activity = $DB->get_record('modules', array('id'=>$module_record->module, 'visible'=>1));
                            if(!empty($activity)){
                                $activity_name = $DB->get_record($activity->name, array('id'=>$module_record->instance));
                                $section .= "<p>".$activity_name->name."</p>";
                            }
                        }
                    }
				}else{
					$section .= "<p class='sectioninfo'> -- No activities here --</p>";
				}
				$section .= "</div>";
			}else{
				$section .= "<h3 class='course_section'>Section ".$course_section->section."</h3>";
				$section .= "<div>";
				if(!empty($course_section->sequence)){
					$c_activities = explode(',', $course_section->sequence);
					
                    foreach($c_activities as $module){ //In sequence wise modules
                        $module_record = $DB->get_record('course_modules', array('id'=>$module, 'visible'=>1));
                        if(!empty($module_record)){
                            $activity = $DB->get_record('modules', array('id'=>$module_record->module, 'visible'=>1));
                            if(!empty($activity)){
                                $activity_name = $DB->get_record($activity->name, array('id'=>$module_record->instance));
                                $section .= "<p>".$activity_name->name."</p>";
                            }
                        }
                    }
				}else{
					$section .= "<p class='sectioninfo'> -- No activities here -- </p>";
				}
				$section .= "</div>";
			}
			
		}
		
		$section .= "</div>";
		
		$section .= html_writer::script('
										$(function() {
											$( "#courseallsections" ).accordion();
										});
									');
		
		return $section;
    }
    
    
    function course_batchesinfo($id) {
        global $DB, $USER, $OUTPUT, $PAGE;
        
        $sql = "SELECT lcc.id, lcc.classroomid, lcc.courseid, lc.id as classroomid, 
        		lc.name, lc.shortname, lc.startdate, lc.enddate, lc.nomination_startdate, lc.nomination_enddate, lc.allow_waitinglistusers, lc.status, lc.approvalreqd, lc.open_location, lc.visible, lc.usercreated
        		FROM {local_classroom_courses} lcc
        		JOIN {local_classroom} lc ON lc.id = lcc.classroomid 
        		WHERE lcc.courseid = :courseid ";
        $course_ilts = $DB->get_records_sql($sql, array('courseid'=>$id));

        if($course_ilts){
            $details = '';
            foreach($course_ilts as $course_ilt){        
        
                $sql = "SELECT lcc.id, lcc.classroomid, c.fullname, c.shortname, c.duration
                        FROM {local_classroom_courses} as lcc
                        JOIN {course} as c ON lcc.courseid = c.id
                        WHERE lcc.classroomid = $course_ilt->classroomid";
                $batchcourse = $DB->get_record_sql($sql);
            
                if($batchcourse->duration){
                    $hours = floor($batchcourse->duration/ 60);
                    $minutes = ($batchcourse->duration % 60);
                    if(empty($batchcourse->duration)){
                        $coursename_duration = get_string('not_assigned', 'local_catalog');
                    }else{
                        $coursename_duration=$hours.': '.$minutes." hrs";
                    }
                }else{
                    $coursename_duration = 'NA';
                }

                switch ($course_ilt->status) {
                    case 0:
                        $status = get_string('newclasses', 'local_classroom');
                        break;
                    case 1:
                        $status = get_string('activeclasses', 'local_classroom');
                        break;
                    case 2:
                        $status = get_string('holdclasses', 'local_classroom');
                        break;
                    case 3:
                        $status = get_string('cancelledclasses', 'local_classroom');
                        break;
                    case 4:
                        $status = get_string('completedclasses', 'local_classroom');
                        break;
                    default:
                        $status = get_string('error');
                        break;
                }
                
                $enrolled = $DB->record_exists('local_classroom_users', array('classroomid' => $course_ilt->classroomid, 'userid' => $USER->id));

                if($course_ilt->nomination_startdate == 0 && $course_ilt->nomination_enddate == 0){
                    $can_nominate = true;
                }else{
                    $sql1="SELECT * 
                        FROM {local_classroom} 
                        WHERE id= :classroomid AND
                        CASE WHEN nomination_startdate > 0
                            THEN 
                                CASE WHEN nomination_startdate <= :nomination_startdate
                                THEN 1
                                ELSE 0 END
                            ELSE 1  END = 1 AND 
                            CASE WHEN nomination_enddate > 0
                                THEN CASE WHEN nomination_enddate >= :nomination_enddate
                                    THEN 1
                                    ELSE 0 END
                            ELSE 1 END = 1 ";

                    $params1 = array();
                    $params1['classroomid'] = $list->id;
                    $params1['nomination_startdate'] = time();
                    $params1['nomination_enddate'] = time();

                    $can_nominate = $DB->record_exists_sql($sql1 ,$params1);
                }

                $capacityreached = (new clroom)->classroom_capacity_check($course_ilt->classroomid);

                if($enrolled){
                    if(in_array($course_ilt->status, array(1,3,4))){
                        $url = new \moodle_url('/local/classroom/view.php', array('cid'=>$course_ilt->classroomid));
                        $enrolment = html_writer::link($url, get_string('view'), array());
                    }else{
                        $enrolment = 'You were already enrolled, but ILT is in "'.$status.'" state, so you cant view this ILT';
                    }
                }else if(!$can_nominate){
                    $enrolment = 'Nominations Closed';
                }else if($capacityreached){
                    $enrolment = get_string('capacity_check','local_catalog');
                }else if($course_ilt->status != 1){
                    $enrolment = 'Not in active status';
                }else{
                    $classroominfo = new stdClass();
                    $classroominfo->id = $course_ilt->classroomid;
                    $classroominfo->name = $course_ilt->name;
                    $classroominfo->approvalreqd = $course_ilt->approvalreqd;

                    $enrolment = (new cl_output())->get_enrollbtn($classroominfo);
                }
                
                $grid = '';
                // for self enrolment
                $grid .= html_writer:: tag('div', $enrolment , array('class'=>'enrollbutton')); 
                
                $creator = $DB->get_record('user', array('id'=>$course_ilt->usercreated),'id,firstname,lastname');

                $emplocations = ($course_ilt->open_location) ? $course_ilt->open_location : 'NA';
            
                $details .= "<div class='course_ilts'>
                            <table id='batchinfo1' style='width:100%;'>
                                <tr>
                                    <td style='width:40%;'>
                                        <div class='pt-2 pb-2'><span class='headlabel'>ILT name: </span>".$course_ilt->name."</div>
                                        <div class='pt-2 pb-2'><span class='headlabel'>Course-Duration: </span>".$coursename_duration."</div>
                                        <div class='pt-2 pb-2'><span class='headlabel'>Creator: </span>".$creator->firstname." ".$creator->lastname."</div>
                                        
                                    </td>
                                    <td style='width:40%;'>
                                        <div class='pt-2 pb-2'><span class='headlabel'>Start Date : </span>".date('d M Y H:i a', $course_ilt->startdate)."</div>
                                        <div class='pt-2 pb-2'><span class='headlabel'>End Date: </span>".date('d M Y H:i a', $course_ilt->enddate)."</div>
                                        <div class='pt-2 pb-2'><span class='headlabel'>Status: </span>".$status."</div>
                                    </td>
                                    <div class='pt-2 pb-2'><td style='width:20%;'>".$grid."</td></div>
                                </tr>
                            </table>
                            <div class='emp_locations'>
                                <span class='headlabel'>Employee Locations: </span>".$emplocations."
                            </div>
                        </div>";
            }
        }else{
            $details = html_writer::tag('div', 'No records', array('class'=>'emptymsg'));
        }
        return $details;
    }
