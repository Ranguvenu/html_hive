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
 * @subpackage local_fmsapi
 */


defined('MOODLE_INTERNAL') || die();

function validate_input($skillname,$empid){

    global $DB;
    $isvalid = false;
    $reason   = '';
    if(empty(trim($skillname))){
        $isvalid = true;
        $reason = "Please enter valid skill name";
    }
    if(empty(trim($empid))){
        $isvalid = true;
        $reason = "Please enter valid EmployeeID ";
    }else{
        $usersql = $DB->record_exists('user', array('open_employeeid' => $empid));
        if(!$usersql){
            $isvalid = true;
            $reason = "EmployeeID doesn't exist (not eligible to search)!! ";
        }
    }
    return array($isvalid, $reason);
}


function local_fmsapi_leftmenunode(){
    $systemcontext = context_system::instance();
    $fmsapinode = '';
    if(has_capability('local/fmsapi:view',$systemcontext) || is_siteadmin()){
        $fmsapinode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_fmsapi', 'class'=>'pull-left user_nav_div video'));
        $fmsapi_url = 'https://mis.fractal.ai/fms-3/faa-training-form/';
        $fmsapi = html_writer::link($fmsapi_url, '<span class="fmsapi_icon left_menu_icons"></span><span class="user_navigation_link_text">'.get_string('trainingrequest','local_fmsapi').'</span>',array('class'=>'user_navigation_link','target'=>'__blank'));
        $fmsapinode .= $fmsapi;
        $fmsapinode .= html_writer::end_tag('li');
    }
    return array('15' => $fmsapinode);
}

function custom_fmsapi_log_saving($eventname, $elementid, $transactionid, $params, $response, $status){
    global $DB, $USER;

    $object = new stdClass();
    $object->eventname = $eventname;
    $object->transactionid = $transactionid;
    $object->elementid = $elementid;
    $object->params = $params;
    $object->response = $response;
    if ($status) {
        $object->status = $status;
    }
    $object->usercreated = $USER->id;
    $object->timecreated = time();
    $id = $DB->insert_record('local_fmsapi_logs',$object);
   
}
function send_email_fms($data, $user) {
    global $DB;    
     // To, from.
     $tempuser = new stdClass();    
     $sql = "SELECT u.id, u.email, u.deleted  FROM mdl_user AS u WHERE u.id = 2";
     $userrecord = $DB->get_record_sql($sql);
     $tempuser->email = $userrecord->email;
     $tempuser->id = $userrecord->id;
     $tempuser->deleted = $userrecord->deleted;
     $noreplyuser = core_user::get_noreply_user();

    $messagesubject = 'Error: FMS Assessment for user '.$data->employeeCode.' in course "'.$data->hiveCourseName.'" was not sent successfully.';
    $emailstrings = new stdClass();
    $emailstrings->name = fullname($user, true);
    $emailstrings->empid = $data->employeeCode;
    $emailstrings->course = $data->hiveCourseName;
    $emailstrings->assessmentName = $data->assessmentName;

    $messagetext = get_string('mailbody','local_fmsapi',$emailstrings);
    $messagehtml = text_to_html($messagetext, false, false, true);

    email_to_user($tempuser, $noreplyuser, $messagesubject, $messagetext, $messagehtml);
}
