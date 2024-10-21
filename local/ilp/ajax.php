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
 * @subpackage local_ilp
 */

// define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $PAGE,$CFG,$USER;
require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$course = optional_param('course', 0, PARAM_INT);
$plan = optional_param('planid', 0, PARAM_INT);
$value = optional_param('value', '', PARAM_TEXT);
$start =optional_param('start',0,PARAM_INT);
$length=optional_param('length',0,PARAM_INT);
$manage=optional_param('manage',0,PARAM_INT);
$action = optional_param('action','',PARAM_TEXT);

$requestData = $_REQUEST;
$ilp_lib = new local_ilp\lib\lib();
// if($action=='ilptab'){

//     $view_renderer = new local_ilp\render\view();
//     $id = required_param('id',  PARAM_INT);
//     $tab = required_param('tab',PARAM_TEXT);
//     $condition = 'manage';
//     if($tab == 'courses'){
//         $data = $view_renderer->ilps_courses_tab_content($id, $tab,$condition);
//     }else if($tab == 'users'){
//         $data = $view_renderer->ilps_users_tab_content($id, $tab,$condition);
//     }
//     echo json_encode($data);
// }
switch($action){
    case 'ilptab':
        $view_renderer = new local_ilp\render\view();
        $id = required_param('id',  PARAM_INT);
        $tab = required_param('tab',PARAM_TEXT);
        $condition = 'manage';
        if($tab == 'courses'){
            $data = $view_renderer->ilps_courses_tab_content($id, $tab,$condition);
        }else if($tab == 'users'){
            $data = $view_renderer->ilps_users_tab_content($id, $tab,$condition);
        }else if($tab == 'targetaudiences'){
            $data = $view_renderer->ilps_target_audience_content($id, $tab,$condition);
        }else if($tab == 'requestedusers'){
            $data = $view_renderer->ilps_requested_users_content($id, $tab,$condition);
        }
        echo json_encode($data);
    break;

    case 'userselfenrol':
        $userid = required_param('userid',  PARAM_INT);
        $record = new \stdClass();
        $record->planid = $plan;
        $record->userid = $userid;
        $record->timecreated = time();
        $record->usercreated = $userid;
        $record->timemodified = 0;
        $record->usermodified = 0;
        $create_record = $ilp_lib->assign_users_to_ilp($record);
        echo json_encode(true);
    break;

    case 'publishilp':

        $users_info = $ilp_lib->get_enrollable_users_to_ilp($plan);

        foreach($users_info as $userid){
            $data = new \stdClass();
            $data->planid = $plan;
            $data->userid = $userid->id;
            $data->timecreated = time();
            $data->usercreated = $USER->id;
            $data->timemodified = 0;
            $data->usermodified = 0;
            $create_record = $ilp_lib->assign_users_to_ilp($data);
        }
        echo json_encode(true);
    break;
        // $record = new stdClass();
        // $record->planid = $planid;
        // $record->userid = $userid;
        // $record->
        // $id = $DB->insert_record('local_ilp_user', $record); 


}
if($value=="and"){
    
    $id=$DB->get_record('local_ilp_courses',array('planid'=>$plan,'courseid'=>$course));
    // $sql="UPDATE {local_ilp_courses} SET nextsetoperator='and' WHERE id=:id";
    // $DB->execute($sql, array('id' => $id->id));
    $updaterecord = new stdClass();
    $updaterecord->id = $id->id;
    $updaterecord->nextsetoperator = 'and';
    $DB->update_record('local_ilp_courses', $updaterecord);
}elseif($value=="or"){
     $id=$DB->get_record('local_ilp_courses',array('planid'=>$plan,'courseid'=>$course));
     $sql="UPDATE {local_ilp_courses} SET nextsetoperator='or' WHERE id=:id";
    $DB->execute($sql, array('id' => $id->id)); 
}
if($manage>0){
    
    $ilp_renderer = new local_ilp\render\view();
    $dataobj = new stdClass();
    $dataobj->start=$_REQUEST['start'];
    $dataobj->length=$_REQUEST['length'];
    $condition="manage";
    $data=$ilp_renderer->all_ilps($condition,$dataobj,true,$requestData['search']['value']);
    
    echo json_encode($data);
}
// if($action=='publishilp' && $plan!=0){

//     $users_info = $ilp_lib->get_enrollable_users_to_ilp($plan);

//     foreach($users_info as $userid){
//         $data = new \stdClass();
//         $data->planid = $plan;
//         $data->userid = $userid->id;
//         $data->timecreated = time();
//         $data->usercreated = $USER->id;
//         $data->timemodified = 0;
//         $data->usermodified = 0;
//         $create_record = $ilp_lib->assign_users_to_ilp($data);
//     }
//     echo json_encode(true);
// }
