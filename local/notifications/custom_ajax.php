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
 * @subpackage local_notifications
 */


if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require(__DIR__.'/../../config.php');
require_once('lib.php');
global $CFG,$DB,$USER, $PAGE;
$notificationid = required_param('notificationid', PARAM_INT);
$costcenterid = optional_param('costcenterid', 0, PARAM_INT);
$moduleid = optional_param('moduleid', 0, PARAM_INT);
$page = required_param('page', PARAM_INT);

$PAGE->set_context(context_system::instance());
require_login();
$lib = new \notifications();
$notif_type = $DB->get_field('local_notification_type', 'shortname', array('id'=>$notificationid));

switch($page){
	case 1:		
		$strings = $lib->get_string_identifiers($notif_type);
        /* $sql = "SELECT c.id, c.fullname as name FROM {course} c                           
                    WHERE  c.visible = 1 AND c.open_costcenterid ={$costcenterid} 
                    AND (c.open_coursecompletiondays != NULL AND c.open_coursecompletiondays != 0)";
		
		$datamoduleids = $DB->get_records_sql($sql);  */
	 	if($notif_type == 'course_reminder' || $notif_type = 'course_completion_reminder'){
            $sql = "SELECT c.id, c.fullname as name FROM {course} c                           
                    WHERE  c.visible = 1 AND c.open_costcenterid ={$costcenterid} 
                     AND (c.open_coursecompletiondays != NULL OR c.open_coursecompletiondays != 0)";
			$datamoduleids = $DB->get_records_sql($sql);
			//$datamoduleids = array(0 => get_string('selectcourses', 'local_notifications')) + $datamoduleids;
		}else{
			$datamoduleids = array();
		} 
        $remindernotiftype = array('course_reminder','course_completion_reminder');
       
        $notif_type_find=explode('_',$notif_type);
        switch(strtolower($notif_type_find[0])){
            case 'course':	
                if(!in_array($notif_type,$remindernotiftype)){
                    $sql = "SELECT c.id, c.fullname as name FROM {course} c                           
                                    WHERE  c.visible = 1 AND c.open_costcenterid = {$costcenterid} ";                    
                    $datamoduleids = $DB->get_records_sql($sql);
                }
                if(in_array($notif_type,$remindernotiftype)){
                    $datamodule_label='Courses<abbr class="initialism text-danger" title="Required"><img src='.$OUTPUT->image_url("new_req").'></abbr>';
		        }else{
                    $datamodule_label="Courses";
                }
    
                break;	

            case 'assign':
                $sql = "SELECT c.id, c.fullname as name FROM {course} c                           
                                    WHERE  c.visible = 1 AND c.open_costcenterid = {$costcenterid} ";                    
                $datamoduleids = $DB->get_records_sql($sql);
                $datamodule_label="Courses";

                break;

            case 'classroom':	
                $sql = "SELECT c.id, c.name FROM {local_classroom} c                           
                                WHERE  c.costcenter = {$costcenterid} ";                    
                $datamoduleids = $DB->get_records_sql($sql);
    
                $datamodule_label="Classrooms";
    
                break;
            case 'onlinetest':	
                $sql = "SELECT c.id, c.name FROM {local_onlinetests} c                           
                                WHERE  c.visible = 1 AND c.costcenterid	= {$costcenterid} ";                    
                $datamoduleids = $DB->get_records_sql($sql);
    
                $datamodule_label="Onlinetests";
    
                break;
            case 'feedback':	
                $sql = "SELECT c.id, c.name FROM {local_evaluations} c                           
                    WHERE  c.visible = 1 AND c.costcenterid = {$costcenterid} AND deleted != 1 ";                    
                $datamoduleids = $DB->get_records_sql($sql);
    
                $datamodule_label="Feedbacks";
    
                break;	
            case 'program':	
                $sql = "SELECT c.id, c.name FROM {local_program} c                           
                                WHERE  c.visible = 1 AND c.costcenter = {$costcenterid} ";                 
                $datamoduleids = $DB->get_records_sql($sql);
    
                $datamodule_label="Programs";
    
                break;
            case 'learningplan':	
                $sql = "SELECT c.id, c.name FROM {local_learningplan} c                           
                                WHERE  c.visible = 1 AND c.costcenter = {$costcenterid} ";                    
                $datamoduleids = $DB->get_records_sql($sql);
    
                $datamodule_label="Learning Paths";
    
                break;	
                
            case 'certification':	
                $sql = "SELECT c.id, c.name FROM {local_certification} c                           
                                WHERE  c.visible = 1 AND c.costcenter = {$costcenterid} ";                
                $datamoduleids = $DB->get_records_sql($sql);
    
                $datamodule_label="Certifications";
    
                break;
        }
       
		echo json_encode(['datamodule_label'=>$datamodule_label,'datamoduleids' =>$datamoduleids,'datastrings'=>$strings]);	
	break;
	case 2:
		$sql = "SELECT c.id, c.fullname FROM {course} c                           
                            WHERE  c.visible = 1 AND c.open_costcenterid = {$costcenterid} ";                    
        $courses = $DB->get_records_sql($sql);
		echo json_encode(['data' =>$courses]);
		break;
	
	case 3:
		$sql = "SELECT id, name FROM {local_classroom} WHERE costcenter = {$data->costcenterid} AND status=1 ";
        $courses = $DB->get_records_sql($sql);
		echo json_encode(['data' =>$courses]);
		break;
	case 4:
        if($notif_type == 'course_reminder' || $notif_type = 'course_completion_reminder'){
			$completiondays_sql = "SELECT open_coursecompletiondays AS value, open_coursecompletiondays AS completiondays 
            	FROM {course} WHERE id > 1 AND open_coursecompletiondays IS NOT NULL 
            	AND open_costcenterid={$costcenterid} ";
            if($moduleid){
                $completiondays_sql .= " AND id IN ({$moduleid})";
            }
            $completiondays_sql .= " GROUP BY open_coursecompletiondays ";
            $completiondays = $DB->get_records_sql_menu($completiondays_sql);
			$completiondays = array(0 => get_string('selectcompletiondays', 'local_notifications')) + $completiondays;
		}else{
			$completiondays = array();
		}
	
		echo json_encode(['completiondays' => $completiondays]);
	break;
}
