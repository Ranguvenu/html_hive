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


function local_ilp_output_fragment_new_ilp($args){
    global $CFG,$DB, $PAGE;
    $args = (object) $args;
    $contextid = $args->contextid;
    $o = '';
    $formdata = [];
    
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $editoroptions = [
        'maxfiles' => EDITOR_UNLIMITED_FILES,
        'maxbytes' => $course->maxbytes,
        'trust' => false,
        'context' => $contextid,
        'noclean' => true,
        'subdirs' => false,
    ];
    
    if($args->id>0||$args->planid>0){
        if(isset($args->id)&&$args->id>0){
            $data = $DB->get_record('local_ilp', array('id'=>$args->id));
        }
        if(isset($args->planid)&&$args->planid>0){
            $data = $DB->get_record('local_ilp', array('id'=>$args->planid));
        }
        if($data){
            $description = $data->description;
            unset($data->description);
            $data->description['text'] = $description;
            $data->open_band = (!empty($data->open_band)) ? array_diff(explode(',',$data->open_band), array('')) :NULL;
            $data->open_hrmsrole = (!empty($data->open_hrmsrole)) ? array_diff(explode(',',$data->open_hrmsrole), array('')) :NULL;
            $data->open_branch =(!empty($data->open_branch)) ? array_diff(explode(',',$data->open_branch), array('')) :NULL;
            $data->open_group =(!empty($data->open_group)) ? array_diff(explode(',',$data->open_group), array('')) :NULL;
            $data->open_designation = (!empty($data->open_designation)) ? array_diff(explode(',',$data->open_designation), array('')) :NULL;
            $data->department =(!empty($data->department)) ? array_diff(explode(',',$data->department), array('')) :NULL;
            
            $mform = new local_ilp\forms\ilp(null, array('editoroptions' => $editoroptions, 'id'=>$data->id), 'post', '', null, true, $formdata);
            $mform->set_data($data);
        }
    }
    else{
        $mform = new local_ilp\forms\ilp(null, array('editoroptions' => $editoroptions), 'post', '', null, true, $formdata);
    }

    if (!empty($args->jsonformdata) && strlen($args->jsonformdata) >2) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    // $formheaders = array_keys($mform->formstatus);
    // $nextform = array_key_exists($args->form_status, $formheaders);
    // if ($nextform === false) {
    //     return false;
    // }
    
    ob_start();
    // $formstatus = array();
 //    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
 //        $activeclass = $k == $args->form_status ? 'active' : '';
 //        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
 //    }
 //    $formstatusview = new \local_users\output\form_status($formstatus);
 //    $o .= $renderer->render($formstatusview);
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
function local_ilp_output_fragment_lpcourse_enrol($args){
    global $CFG,$DB, $PAGE;
    $args = (object) $args;
    // print_object($args);
    $contextid = $args->contextid;
    $planid = $args->planid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $mform = new local_ilp\forms\courseenrolform(null,array('planid' => $planid, 'condition' => 'manage'));
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
function local_ilp_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'summaryfile') {
        return false;
    }

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_ilp', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    send_file($file, $filename, 0, $forcedownload, $options);
}
function ilp_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $sql = "SELECT id, name FROM {local_ilp} WHERE id > 1";
if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
        $lists = $DB->get_fieldset_sql("SELECT componentid  FROM {local_request_records} WHERE compname = 'ilp'");
        $list = implode(',',$lists);
        $courseslist = $DB->get_records_sql_menu("SELECT id, name FROM {local_ilp} WHERE id IN ($list)");
        
    }
    $select = $mform->addElement('autocomplete', 'ilp', '', $courseslist, array('placeholder' => get_string('componentname', 'local_request')));
    $mform->setType('ilp', PARAM_RAW);
    $select->setMultiple(true);
}

function get_ilp_by_id($id) {
    global $DB;
    return $DB->get_record('local_ilp', ['id'=>$id]);
}

function is_user() {
    if(has_any_capability(
        array('local/costcenter:manage_ownorganization', //This is for OH to manage LEP
            'local/costcenter:manage_owndepartments'),   //This is for academy head to manage LEP
        context_system::instance()) 
        OR is_siteadmin()) {
        return false;
    }
    return true;
}

function get_user_ilp($userid) {
    global $DB, $CFG;
    $query = "SELECT lp.* 
                FROM {local_ilp_user} AS ulp
                JOIN {local_ilp} AS lp ON lp.id = ulp.planid
                WHERE lp.visible = ? AND ulp.userid = ?";
    $params = [1, $userid];
    $ilps = $DB->get_records_sql($query, $params);
    return $ilps;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
/*function local_ilp_leftmenunode(){
    $systemcontext = context_system::instance();
    $ilpnode = '';
    if(is_user()) {
        $ilpnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_ilp', 'class'=>'pull-left user_nav_div ilps'));
            $ilp_url = new moodle_url('/local/ilp/index.php');
            $ilp_icon = '<span class="ilp_icon_wrap"></span>';
            $learningplan = html_writer::link($ilp_url, $ilp_icon.'<span class="user_navigation_link_text">'.get_string('my_ilps','local_ilp').'</span>',array('class'=>'user_navigation_link'));
            $ilpnode .= $learningplan;
        $ilpnode .= html_writer::end_tag('li');
    }

    return array('3' => $ilpnode);
}
*/
