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

/**
 * The survey block
 *
 * @package    block
 * @subpackage  survey
 * @copyright 2017 Shivani M <shivani@eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once dirname(__FILE__) . '/../../config.php';
require_once ($CFG->dirroot . '/blocks/survey/lib.php');
// global $CFG;
class block_survey_renderer extends plugin_renderer_base {
	public function survey_view() {
		global $DB, $PAGE, $USER, $OUTPUT;
		// require_once $CFG->wwwroot . '/blocks/survey/lib.php';
		
		$systemcontext = context_system::instance();
        $class = new survey();
        $view = $class->get_enrolled_surveys();
        //print_object($view);
		$data = array();
        foreach($view as $survey){
			$row = array();

            $surveyname = strlen($survey->name) > 16 ? substr($survey->name, 0, 16)."..." : $survey->name;
            $description = strip_tags($survey->intro, array('overflowdiv' => false, 'noclean' => false, 'para' => false));
			if($survey->intro){
				if(strlen($description) >= 25){
						$description = '<div title="'.$description.'">'.substr($description, 0, 25).'...'.'</div>';
				}else{
					 $description = '<div title="'.$description.'">'.$description.'</div>';
				}
			}else{
				$description = 'No Description added';
			}
			
			$complete = $DB->get_records_sql("SELECT ec.id FROM {local_evaluation_completed} ec
											 JOIN {local_evaluations} e ON e.id = ec.evaluation
											 WHERE ec.userid = $USER->id");
			$array = array();
			foreach($complete as $completed){
				$out = array();
				$out[] = $completed->id;
				$array[] = implode(',',$out);
			}
			$completed_id = implode(',',$array);
			if($completed_id){
				$status = 'Completed';
			}else{
				$status = html_writer::link(new moodle_url('/local/evaluation/complete.php', array('id' =>  $survey->evaluationid, 'courseid' => '')), 'Take Survey', array());
			}
			$row[] = '<div class="col-md-12 p-0 survey_view">
							<div class="col-md-9 pl-0">
								<div class = "name">'.$surveyname.'</div>
								<div class = "descriptn">'.$description.'</div>
							</div>
							<div class="col-md-3 pl-0">
								<div class = "status">'.$status.'</div>
							</div>
						</div>';
			
			$data[] = $row;
			//print_object($data);exit;
		}
		$table = new html_table();
		$table->id = 'survey_view';
		$table->head = array('');
		$table->data = $data;
		if($data){
			$output = html_writer::table($table);
			$output .= html_writer::script("$(document).ready(function() {
                                      $('#survey_view').dataTable(
                                      {
										'pageLength': 2,
										'bLengthChange': false,
                                        'language': {
                                              'emptyTable': 'No Records Found',
											   paginate: {
												'previous': '<',
												'next': '>'
											   }
                                         }
                                      });
 
                       });");
		}else{
			$output = "<div class='alert alert-info' style='float:left;margin-top:10px;width:96%;padding:5px 1.9%;'>
							No Surveys Available
						</div>";
			
		}
		return $output;
    }
}