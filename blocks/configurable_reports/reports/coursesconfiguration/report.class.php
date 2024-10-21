<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
 * A Moodle block for creating configurable reports
 * @package blocks
 * @author: Madhavi Rajana <madhavi.r@eabyas.com>
 * @date: 2020
 */
global $DB, $CFG, $USER;
class report_coursesconfiguration extends report_base{
	function init(){
		$this->components = array('columns', 'filters');
	}	
	function get_all_elements(){
		global $DB;
		
		$elements = array();
		$rs = $DB->get_recordset('user', null, '', 'id');
            foreach ($rs as $result) {
			$elements[] = $result->id;
		}
		$rs->close();
		return $elements;
	}
	function get_rows($elements, $sqlorder = ''){
		global $DB, $CFG, $USER;
		$syscontext = context_system::instance();
		$finalelements = array();
		if(!empty($elements)){			
				list($usql, $params) = $DB->get_in_or_equal($elements);
				$filter_courses = optional_param('filter_courses', 0, PARAM_RAW);
				if($filter_courses || $filter_courses==-1){
					$sql="SELECT c.id,c.fullname,c.shortname,c.category,c.open_grade,(cc.name) as category,
					c.open_level,c.open_skill,c.open_points,c.open_identifiedas,c.open_facilitatorcredits,
					c.summary,c.enablecompletion,c.duration,c.visible,c.open_careertrack,c.timecreated,c.open_courseprovider,
					c.open_ouname,c.open_url,c.autoenrol,c.selfenrol
					FROM {course} c
					JOIN {course_categories} cc ON cc.id=c.category
					WHERE 1 = 1 ";
					if($filter_courses == -1){
						$sql;
					}elseif(!empty($filter_courses)){
						$sql.=" AND c.id = $filter_courses ";
					}
					 
					$records = $DB->get_records_sql($sql);
					$reportarray = array();
				
					foreach($records as $record){
						$course_data=new stdClass();
						$course_data->courseid = $record->id;
					    $course_data->fullname = $record->fullname;
						$course_data->shortname = $record->shortname;
						$course_data->coursecategory = $record->category;
						if(empty($record->open_identifiedas)){
							$course_data->type = 'NA';
						}else{
							$course_data->type = $DB->get_field('local_course_types','course_type',array('active' =>1,'id' => $record->open_identifiedas)); 
						}
						
						if($record->timecreated){
							$course_data->createdon = date('d-m-Y', $record->timecreated);
						}else{
							$course_data->createdon = '-';
						}

						if(empty($record->open_grade) || $record->open_grade == -1){
							$course_data->grade = get_string('all');
						}else{
							$course_data->grade = $record->open_grade;
						}

						$course_data->skills = 'N/A';
						$course_data->skill_category = 'N/A';
						if($record->open_skill){
			                $sql = "SELECT GROUP_CONCAT(name separator ', ')
			                        FROM {local_skill}
			                        WHERE id IN ($record->open_skill) ";
			                $skills = $DB->get_field_sql($sql);
			                if($skills){
			                    $course_data->skills = $skills;
			                }
			                $skillslist = explode(',', $record->open_skill);
			                if($skillslist[0]){
			                	$skillrecord = $DB->get_record('local_skill',array('id'=>$skillslist[0]),'id,category,name');
			                	$course_data->skill_category = $DB->get_field('local_skill_categories','name',array('id'=>$skillrecord->category));
			                }
			            }

						if(!empty($record->open_level)){
							$levelname =  $DB->get_field('local_levels','name',array('id'=>$record->open_level));
							$course_data->level = $levelname;
					    }else{
					    	$course_data->level = 'NA';
					    }
						$course_data->credits = !empty($record->open_points) ? $record->open_points : 0;
						if(!empty($record->open_facilitatorcredits)){
                        	$course_data->facilitatorcredits = round($record->open_facilitatorcredits,1);
						}else{
							$course_data->facilitatorcredits = 0;
						}

						if($record->open_courseprovider != 0){
							$course_data->courseprovider = $DB->get_field('local_course_providers','course_provider',array('id' => $record->open_courseprovider));
						}else{
							$course_data->courseprovider = 'NA';
						}

						if($record->autoenrol != 0){
							$course_data->autoenrol = 'Yes';
						}else{
							$course_data->autoenrol = 'No';
						}

						if($record->selfenrol != 0){
							$course_data->selfenrol = 'Yes';
						}else{
							$course_data->selfenrol = 'No';
						}
						
						if($record->open_ouname == '-1'){
							$course_data->ouname = 'ALL';
						}else if($record->open_ouname != NULL){
							$course_data->ouname = $record->open_ouname;
						}else{
							$course_data->ouname = 'NA';
						}

						if($record->open_url != 0 || $record->open_url != NULL){
							$course_data->url = $record->open_url;
						}else{
							$course_data->url = 'NA';
						}

						if($record->duration){
							$hours = floor($record->duration/3600);
							$minutes = ($record->duration/60)%60;
							$course_data->duration = $hours.':'. $minutes;
						}else{
							$course_data->duration = '0:0';
						}

						if($record->summary){
							$summary = str_replace('&nbsp;', ' ',$record->summary);
							$summary = str_replace('nbsp;', ' ',$summary);
							$summary = str_replace('&amp;', ' ',$summary);
							$course_data->summary = $summary;
						
						}else{
							$course_data->summary = "--";
						}
						if($record->enablecompletion==1){
							$course_data->enablecompletion = "Yes";
							$activities=activity_name($record->id);
							$criteria_type=$DB->record_exists('course_completion_criteria',array('criteriatype'=>4,'course'=>$record->id));
							if($criteria_type){
								$course_data->activitycompletion = implode(',',$activities);
							}else{
								$course_data->activitycompletion = "--";
							}
					    }else{
							$course_data->enablecompletion = "No";
							$course_data->activitycompletion = "--";
						}
						/* $selfenrol = $DB->record_exists('enrol',array('courseid'=>$record->id,'enrol'=>'self','status'=>0));
						if($selfenrol){
							$course_data->selfenrol = get_string('yes');
						}else{
							$course_data->selfenrol =  get_string('no');
						} */

						if($record->visible == 1){
						$course_data->course_status = 'Visible';
						}else{
							$course_data->course_status = 'Invisible';
						}
						if(empty($record->open_careertrack) || $record->open_careertrack == 'All'){
							$course_data->career_track = get_string('all');
						}else{
							$course_data->career_track = $record->open_careertrack;
						}
						$course_url = $CFG->wwwroot.'/course/view.php?id='.$record->id;
						$course_data->course_url = '<a href='.$CFG->wwwroot.'/course/view.php?id='.$record->id.' target="_blank">'.$course_url.'</a>';
						$reportarray[] = $course_data;
					}
					return $reportarray;
				}
				return $finalelements;
			}
		}
	}
	
	
	function activity_name($course_id){
		global $DB;
		$sql = "SELECT cc.id,cc.module,cc.moduleinstance FROM {course_completion_criteria} cc
		        WHERE cc.course=$course_id and cc.criteriatype=4";
		$data= $DB->get_records_sql($sql);
		$activities = array();
		foreach($data as $data_records){
			$moduleid = $DB->get_field('course_modules', 'instance', array('id'=>$data_records->moduleinstance));
			$activities[] = $DB->get_field($data_records->module, 'name', array('id'=>$moduleid));
		}         
		return $activities;
	}
	
	
	
