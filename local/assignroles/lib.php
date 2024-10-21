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
 * @subpackage local_assignroles
 */

require_once(__DIR__ . '/../../config.php');
/**
 * Function to display the assign role form in popup
 * returns data of the popup 
 */
function local_assignroles_output_fragment_new_assignrole($args) {
    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $roleid = $args->roleid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }
    $mform = new local_assignroles\form\assignrole(null, array('editoroptions' => $editoroptions,'roleid'=>$roleid), 'post', '', null, true, $formdata);
    $mform->set_data($data);
    if (!empty($formdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
/**
 * Function to display the role users in popup
 * returns data of the popup 
 */
function local_assignroles_output_fragment_roleusers_display($args){
    global $DB,$CFG,$PAGE,$OUTPUT,$USER;

    $args = (object) $args;
    $context = $args->context;
    $roleid = $args->roleid;
    $rolename = $DB->get_field('role', 'shortname', array('id' => $roleid));
    $rolefullname = $DB->get_field('role', 'name', array('id' => $roleid));
    $systemcontext = context_system::instance();


    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
       
        $sql="SELECT ra.* FROM {role_assignments} AS ra JOIN {user} AS u on u.id=ra.userid 
            JOIN {local_costcenter} AS c on c.id=u.open_costcenterid
            WHERE ra.roleid=:roleid AND ra.contextid=:contextid";

        $users= $DB->get_records_sql($sql, array('roleid' => $roleid, 'contextid' => $context->id, 'costcenter' => $USER->open_costcenterid)); 
         
    }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $sql = "SELECT ra.* FROM {role_assignments} AS ra JOIN {user} AS u on u.id=ra.userid 
            WHERE ra.roleid=:roleid AND ra.contextid=:contextid AND u.open_costcenterid=:costcenter";
        $users= $DB->get_records_sql($sql, array('roleid' => $roleid, 'contextid' => $context->id, 'costcenter' => $USER->open_costcenterid));
    }else{
        $sql = "SELECT ra.* FROM {role_assignments} AS ra JOIN {user} AS u on u.id=ra.userid 
            WHERE ra.roleid=:roleid AND ra.contextid=:contextid AND u.open_costcenterid=:costcenter AND u.open_departmentid=:department";
        $users= $DB->get_records_sql($sql, array('roleid' => $roleid, 'contextid' => $context->id, 'costcenter' => $USER->open_costcenterid, 'department' => $USER->open_departmentid));  
    }
    $templatedata = array();
    $templatedata['roleid'] = $roleid;
    $templatedata['rolename'] = $rolename;
    $templatedata['rolefullname'] = $rolefullname;
    
    if($users){
        $templatedata['enabletable'] = true;
        foreach($users as $user){
            $rowdata = array();
            $user_data_sql = "SELECT u.id,u.firstname,u.lastname,u.email,u.open_employeeid,lc.fullname FROM {user} AS u JOIN {local_costcenter} AS lc ON lc.id=u.open_costcenterid WHERE u.id = :id";
           
            $userdata = $DB->get_record_sql($user_data_sql,array('id' => $user->userid));
            $fullname = $userdata->firstname.' '.$userdata->lastname;
            $rowdata['fullname'] = $fullname;
            $rowdata['employeeid'] = $userdata->open_employeeid;
            $rowdata['email'] = $userdata->email;
            $rowdata['orgname'] = $userdata->fullname;
            $rowdata['userid'] = $user->userid;
            $rowdata['username'] = $fullname;
            $templatedata['rowdata'][] = $rowdata;
        }
    }else{
        $templatedata['enabletable'] = false;
    }
    $output = $OUTPUT->render_from_template('local_assignroles/popupcontent', $templatedata);
    
    return $output;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_assignroles_leftmenunode(){
    $systemcontext = context_system::instance();
    $assignrolesnode = '';
    if(has_capability('local/assignroles:manageassignroles',$systemcontext) || is_siteadmin()) {
        $assignrolesnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_assign_roles', 'class'=>'pull-left user_nav_div assign_roles'));
            $users_url = new moodle_url('/local/assignroles/index.php');
            $users = html_writer::link($users_url, '<span class="assign_role_icon left_menu_icons"></span><span class="user_navigation_link_text">'.get_string('pluginname', 'local_assignroles').'</span>',array('class'=>'user_navigation_link'));
            $assignrolesnode .= $users;
        $assignrolesnode .= html_writer::end_tag('li');
    }
    return array('4' => $assignrolesnode);
}
