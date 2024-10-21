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
 * @subpackage local_costcenter
 */

defined('MOODLE_INTERNAL') or die;
define('ACTIVE',0);
define('IN_ACTIVE',1);
define('TOTAL',2);
//use core_component; // Comment <Revathi>
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->dirroot . '/message/lib.php');

class costcenter {
    
    /*
     * @method get_costcenter_parent Get parent of the costcenter
     * @param object $costcenters costcenter data object
     * @param array $selected Costcenter position
     * @param boolean $inctop Include default value/not
     * @param boolean $all All option to select all values/not
     * @return array List of values
     */
    function get_costcenter_parent($costcenters, $selected = array(), $inctop = true, $all = false) {
        $out = array();

        //if an integer has been sent, convert to an array
        if (!is_array($selected)) {
            $selected = ($selected) ? array(intval($selected)) : array();
        }
        if ($inctop) {
            $out[null] = '---Select---';
        }
        if ($all) {
            $out[0] = get_string('all');
        }
        if (is_array($costcenters)) {
            foreach ($costcenters as $parent) {
                // An item cannot be its own parent and cannot be moved inside itself or one of its own children
                // what we have in $selected is an array of the ids of the parent nodes of selected branches
                // so we must exclude these parents and all their children
                //add using same spacing style as the bulkitems->move available & selected multiselects
                foreach ($selected as $key => $selectedid) {
                    if (preg_match("@/$selectedid(/|$)@", $parent->path)) {
                        continue 2;
                    }
                }
                if ($parent->id != null) {
                    $out[$parent->id] = format_string($parent->fullname);
                }
            }
        }

        return $out;
    }


    /*
     * @method get_costcenter_items Get costcenter list
     * @param boolean $fromcostcenter used to indicate called from costcenter plugin,using while error handling
     * @return list of costcenters
     * */
    function get_costcenter_items($fromcostcenter = NULL) {

        global $DB, $USER;
        $activecostcenterlist = $DB->get_records('local_costcenter', array('visible' => 1), 'sortorder, fullname');

        if (empty($fromcostcenter)) {
            if (empty($activecostcenterlist))
                print_error('notassignedcostcenter', 'local_costcenter');
        }
        
        $assigned_costcenters = costcenter_items();
        
        if (empty($fromcostcenter)) {
            if (empty($assigned_costcenters)) {
                print_error('notassignedcostcenter', 'local_costcenter');
            } else
                return $assigned_costcenters;
        } else
            return $assigned_costcenters;
    }
    /*
     * @method get_next_child_sortthread Get costcenter child list
     * @param  int $parentid which is id of a parent costcenter
     * @param  [string] $table is a table name 
     * @return list of costcenter children
     * */
    function get_next_child_sortthread($parentid, $table) {
        global $DB, $CFG;
        $maxthread = $DB->get_record_sql("SELECT MAX(sortorder) AS sortorder FROM {$CFG->prefix}{$table} WHERE parentid = :parentid", array('parentid' => $parentid));
        
        if (!$maxthread || strlen($maxthread->sortorder) == 0) {
            if ($parentid == 0) {
                // first top level item
                return $this->inttovancode(1);
            } else {
                // parent has no children yet
                return $DB->get_field('local_costcenter', 'sortorder', array('id' => $parentid)) . '.' . $this->inttovancode(1);
            }
        }
        return $this->increment_sortorder($maxthread->sortorder);
    }

    /**
     * Convert an integer to a vancode
     * @param int $int integer to convert.
     * @return vancode The vancode representation of the specified integer
     */
    function inttovancode($int = 0) {
        $num = base_convert((int) $int, 10, 36);
        $length = strlen($num);
        return chr($length + ord('0') - 1) . $num;
    }

    /**
     * Convert a vancode to an integer
     * @param string $char Vancode to convert. Must be <= '9zzzzzzzzzz'
     * @return integer The integer representation of the specified vancode
     */
    function vancodetoint($char = '00') {
        return base_convert(substr($char, 1), 36, 10);
    }

    /**
     * Increment a vancode by N (or decrement if negative)
     *
     */
    function increment_vancode($char, $inc = 1) {
        return $this->inttovancode($this->vancodetoint($char) + (int) $inc);
    }
    /**
     * Increment a sortorder by N (or decrement if negative)
     *
     */
    function increment_sortorder($sortorder, $inc = 1) {
        if (!$lastdot = strrpos($sortorder, '.')) {
            // root level, just increment the whole thing
            return $this->increment_vancode($sortorder, $inc);
        }
        $start = substr($sortorder, 0, $lastdot + 1);
        $last = substr($sortorder, $lastdot + 1);
        // increment the last vancode in the sequence
        return $start . $this->increment_vancode($last, $inc);
    }
    
    /*Get uploaded course summary uploaded file
     * @param $course is an obj Moodle course
     * @return course summary file(img) src url if exists else return default course img url
     * */
    function get_course_summary_file($course){  
        global $DB, $CFG, $OUTPUT;
        if ($course instanceof stdClass) {
            require_once($CFG->libdir . '/coursecatlib.php');
            $course = new course_in_list($course);
        }
        
        // set default course image
        $url = $OUTPUT->image_url('/course_images/courseimg', 'local_costcenter');
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            if($isimage)
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
            $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
        }
        return $url;
    }
    function get_costcenter_icons(){
        global $USER, $DB;

        if(!empty($costcentershell = $DB->get_field('local_costcenter', 'shell', array('id' => $USER->open_costcenterid, 'visible' => 1)))){
            return $costcentershell;
        }else{
            return false;
        }
    }
    function get_costcenter_theme(){
        global $USER, $DB;

        if(!empty($costcentertheme = $DB->get_field('local_costcenter', 'theme', array('id' => $USER->open_costcenterid, 'visible' => 1)))){
            return $costcentertheme;
        }else{
            return false;
        }
    }

}
/**
 * Description: local_costcenter_pluginfile for fetching images in costcenter plugin
 * @param  [INT] $course        [course id]
 * @param  [INT] $cm            [course module id]
 * @param  [context] $context       [context of the file]
 * @param  [string] $filearea      [description]
 * @param  [array] $args          [array of ]
 * @param  [boolean] $forcedownload [to download or only view]
 * @param  array  $options       [description]
 * @return [file]                [description]
 */
function local_costcenter_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
        // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.

        // Make sure the filearea is one of those used by the plugin.
        if ($filearea !== 'costcenter_logo') {
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
        $file = $fs->get_file($context->id, 'local_costcenter', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return false;
        }
        send_file($file, $filename, 0, $forcedownload, $options);
    }
/**
 * Description: get the logo specified to the organization.
 * @param  [INT] $costcenter_logo [item id of the logo]
 * @return [URL]                  [path of the logo]
 */
function costcenter_logo($costcenter_logo) {
    global $DB;
    $costcenter_logourl = false;

    $sql = "SELECT * FROM {files} WHERE itemid = :logo  AND filename != '.' ORDER BY id DESC";
    $costcenterlogorecord = $DB->get_record_sql($sql,array('logo' => $costcenter_logo),1);

    if (!empty($costcenterlogorecord)){
        if($costcenterlogorecord->filearea=="costcenter_logo"){
            $costcenter_logourl = moodle_url::make_pluginfile_url($costcenterlogorecord->contextid, $costcenterlogorecord->component, $costcenterlogorecord->filearea, $costcenterlogorecord->itemid, $costcenterlogorecord->filepath, $costcenterlogorecord->filename);
        }
    }
    return $costcenter_logourl;
}
/**
     * @method local_costcenter_output_fragment_new_costcenterform
     * @param  $args is an array   
     */
function local_costcenter_output_fragment_new_costcenterform($args){
 global $CFG,$DB;
 
    $args = (object) $args;
    $context = $args->context;
    // $costcenterid = $args->costcenterid;
    // $parentid = $args->parentid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }
    if($args->id){
        $data = $DB->get_record('local_costcenter', array('id'=>$args->id));
    }

    $mform = new local_costcenter\form\organization_form(null, array(/*'editoroptions' => $editoroptions,'subdept'=>$subdept,'dept'=>$dept,'parentid' => $parentid ,*/ 'id' => $args->id, 'formtype' => $args->formtype), 'post', '', null, true, $formdata);    
    $mform->set_data($data);
 
    if (!empty($args->jsonformdata)&& strlen($args->jsonformdata) > 2) {       
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
 * Description: [organizations_filter code]
 * @param  [mform]  $mform          [form where the filetr is initiated]
 * @param  string  $query          [description]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [description]
 * @param  integer $perpage        [description]
 * @return [type]                  [description]
 */
function organizations_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $organizationlist = array();
    $data = data_submitted();

    $userparam = array();
    $organizationparam = array();
    $params = array();
    
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $organizationlist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth =1";
    }else{
        $organizationlist_sql="SELECT id, fullname FROM {local_costcenter} WHERE depth =1 AND id = :usercostcenter ";
        $userparam['usercostcenter'] = $USER->open_costcenterid;
    }
    if(!empty($query)){ 
        if ($searchanywhere) {
            $organizationlist_sql.=" AND fullname LIKE '%$query%' ";
        } else {
            $organizationlist_sql.=" AND fullname LIKE '$query%' ";
        }
    }
    if(isset($data->organizations)&&!empty(($data->organizations))){
        list($organizationparamsql, $organizationparam) = $DB->get_in_or_equal($data->organizations, SQL_PARAMS_NAMED, 'param', true, false);        
        $organizationlist_sql.=" AND id $organizationparamsql";
    }

    $params = array_merge($userparam, $organizationparam);

    if(!empty($query)||empty($mform)){
        $organizationlist = $DB->get_records_sql($organizationlist_sql, $params, $page, $perpage);
        return $organizationlist;
    }
    if((isset($data->organizations)&&!empty($data->organizations))){
        $organizationlist = $DB->get_records_sql_menu($organizationlist_sql, $params, $page, $perpage);
    }
    
    $options = array(
        'ajax' => 'local_courses/form-options-selector',
        'multiple' => true,
        'data-action' => 'organizations',
        'data-options' => json_encode(array('id' => 0)),
        'placeholder' => get_string('organisations','local_costcenter')
    );
    $select = $mform->addElement('autocomplete', 'organizations', '', $organizationlist,$options);
    $mform->setType('organizations', PARAM_RAW);
}
/**
  * Description: [departments_filter code]
 * @param  [mform]  $mform          [form where the filetr is initiated]
 * @param  string  $query          [description]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [description]
 * @param  integer $perpage        [description]
 * @return [type]                  [description]
 */
function departments_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $departmentslist=array();
    $data=data_submitted();

    $userparam = array();
    $organizationparam = array();
    $params = array();
    
    $orgid = $DB->get_field('local_costcenter', 'id', array('shortname'=>'Fractal'));
    
    $departmentslist_sql="SELECT id, fullname 
                            FROM {local_costcenter} 
                            WHERE parentid = :usercostcenter ";
    $userparam['usercostcenter'] = $orgid;
    if(!empty($query)){ 
        if ($searchanywhere) {
            $departmentslist_sql.=" AND fullname LIKE '%$query%' ";
        } else {
            $departmentslist_sql.=" AND fullname LIKE '$query%' ";
        }
    }
    if(isset($data->departments)&&!empty(($data->departments))){
        list($organizationparamsql, $organizationparam) = $DB->get_in_or_equal($data->departments, SQL_PARAMS_NAMED, 'param', true, false);
        if($organizationparamsql){
            $departmentslist_sql.=" AND id {$organizationparamsql} ";
        }
    }
    $params = array_merge($userparam, $organizationparam);

    if(!empty($query)||empty($mform)){ 
        $departmentslist = $DB->get_records_sql($departmentslist_sql, $params, $page, $perpage);
        return $departmentslist;
    }
    if((isset($data->departments)&&!empty($data->departments))){ 
        $departmentslist = $DB->get_records_sql_menu($departmentslist_sql, $params, $page, $perpage);
    }
    
    $options = array(
            'ajax' => 'local_courses/form-options-selector',
            'multiple' => true,
            'data-action' => 'departments',
            'data-options' => json_encode(array('id' => 0)),
            'placeholder' => get_string('department','local_costcenter')
    );
        
    $select = $mform->addElement('autocomplete', 'departments', '', $departmentslist,$options);
    $mform->setType('departments', PARAM_RAW);
}

/**
  * Description: [subdepartment_filter code]
 * @param  [mform]  $mform          [form where the filetr is initiated]
 * @param  string  $query          [description]
 * @param  boolean $searchanywhere [description]
 * @param  integer $page           [description]
 * @param  integer $perpage        [description]
 * @return [type]                  [description]
 */
function subdepartment_filter($mform,$query='',$searchanywhere=false, $page=0, $perpage=25){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $subdepartmentslist=array();
    $data=data_submitted();

    $userparam = array();
    $departmentparam = array();
    $params = array();
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $subdepartmentslist_sql = "SELECT id, fullname FROM {local_costcenter} WHERE depth = 3 ";
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $subdepartmentslist_sql = "SELECT id, fullname FROM {local_costcenter} WHERE depth = 3 AND parentid IN (SELECT id FROM {local_costcenter} WHERE parentid = :usercostcenter) ";
        $userparam['usercostcenter'] = $USER->open_costcenterid;
    }else{
        $subdepartmentslist_sql = "SELECT id, fullname FROM {local_costcenter} WHERE depth = 3 AND parentid = :userdepartment ";
        $userparam['userdepartment'] = $USER->open_departmentid;
    }
    if(!empty($query)){ 
        if ($searchanywhere) {
            $subdepartmentslist_sql .= " AND fullname LIKE '%$query%' ";
        } else {
            $subdepartmentslist_sql .= " AND fullname LIKE '$query%' ";
        }
    }
    if(isset($data->subdepartment)&&!empty(($data->subdepartment))){
        list($departmentparamsql, $departmentparam) = $DB->get_in_or_equal($data->subdepartment, SQL_PARAMS_NAMED, 'param', true, false);        
        $subdepartmentslist_sql.=" AND id $departmentparamsql";
    }
    $params = array_merge($userparam, $departmentparam);

    if(!empty($query)||empty($mform)){ 
        $subdepartmentslist = $DB->get_records_sql($subdepartmentslist_sql, $params, $page, $perpage);
        return $subdepartmentslist;
    }
    if((isset($data->subdepartment) && !empty($data->subdepartment))){ 
        $subdepartmentslist = $DB->get_records_sql_menu($subdepartmentslist_sql, $params, $page, $perpage);
    }
    
    $options = array(
            'ajax' => 'local_courses/form-options-selector',
            'multiple' => true,
            'data-action' => 'subdepartment',
            'data-options' => json_encode(array('id' => 0)),
            'placeholder' => get_string('subdepartment','local_costcenter'),
            'id' => 'subdepartment_filter_element'
    );
        
    $select = $mform->addElement('autocomplete', 'subdepartment', '', $subdepartmentslist, $options);
    $mform->setType('subdepartment', PARAM_RAW);
}
/**
 * Description: [insert costcenter instance ]
 * @param  [OBJECT] $costcenter [costcenter object]
 * @return [INT]             [created costcenter id]
 */
function costcenter_insert_instance($costcenter){
        global $DB, $CFG, $USER;
        $systemcontext = context_system::instance();
        if ($costcenter->parentid == 0) {
            $costcenter->depth = 1;
            $costcenter->path = '';
        } else {
            /* ---parent item must exist--- */
            $parent = $DB->get_record('local_costcenter', array('id' => $costcenter->parentid));
            $costcenter->depth = $parent->depth + 1;
            $costcenter->path = $parent->path;
        }
        /* ---get next child item that need to provide--- */
        $custom = new costcenter();
        if (!$sortorder = $custom->get_next_child_sortthread($costcenter->parentid, 'local_costcenter')) {
            return false;
        }
        
        $costcenter->sortorder = $sortorder;
        $parentid = $costcenter->parentid ?  $costcenter->parentid:0;
        $costcenter->costcenter_logo = $costcenter->costcenter_logo;
        $costcenter->shell = $costcenter->shell;

            file_save_draft_area_files($costcenter->costcenter_logo, $systemcontext->id, 'local_costcenter', 'costcenter_logo', $costcenter->costcenter_logo);
        $costcenter->id = $DB->insert_record('local_costcenter', $costcenter);
        
        if($costcenter->id) {
            $parentpath = $DB->get_field('local_costcenter', 'path', array('id'=>$parentid));
            $path = $parentpath.'/'.$costcenter->id;
            $datarecord = new stdClass();
            $datarecord->id = $costcenter->id;
            $datarecord->path = $path;
            $DB->update_record('local_costcenter',  $datarecord);
            
            $record = new stdClass();
            $record->name = $costcenter->fullname;
            $record->parent = $DB->get_field('local_costcenter', 'category', array('id'=>$parentid));
            $record->idnumber = $costcenter->shortname;
            $category = core_course_category::create($record);
            
            if($category ){
                $DB->execute("UPDATE {local_costcenter} SET multipleorg = ? WHERE id = ?", [$costcenter->id, $costcenter->id]);
                $DB->execute("UPDATE {local_costcenter} SET category= ? WHERE id = ? ", [$category->id, $costcenter->id]);
            }
        }
        return $costcenter->id;
    }
/**
 * Description: [edit costcenter instance ]
 * @param  [INT] $costcenterid  [id of the costcenter]
 * @param  [object] $newcostcenter [update content]
 * @return [BOOLEAN]                [true if updated ]
 */
function costcenter_edit_instance($costcenterid, $newcostcenter){
    global $DB,$CFG;
    $systemcontext = context_system::instance();
    $oldcostcenter = $DB->get_record('local_costcenter', array('id' => $costcenterid));
    $category = $DB->get_field('local_costcenter','category',array('id' => $newcostcenter->id));
    /* ---check if the parentid is the same as that of new parentid--- */
    if ($newcostcenter->parentid != $oldcostcenter->parentid) {
        $newparentid = $newcostcenter->parentid;
        $newcostcenter->parentid = $oldcostcenter->parentid;
    }
    $today = time();
    $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
    $newcostcenter->timemodified = $today;
    $newcostcenter->costcenter_logo = $newcostcenter->costcenter_logo;
        file_save_draft_area_files($newcostcenter->costcenter_logo, $systemcontext->id, 'local_costcenter', 'costcenter_logo', $newcostcenter->costcenter_logo);

    $costercenter = $DB->update_record('local_costcenter', $newcostcenter);
    $course_categories=$DB->record_exists('course_categories',array('id'=>$category));
    if($costercenter && $course_categories){
        $record = new stdClass();
        $record->id = $category;
        $record->name = $newcostcenter->fullname;
        $record->idnumber = $newcostcenter->shortname;
        $DB->update_record('course_categories', $record);
    }
    return true;
}
/**
 * [costcenter_items description]
 * @return [type] [description]
 */
function costcenter_items(){
    global $DB, $USER;
    $assigned_costcenters = '';
    $systemcontext = context_system::instance();
    if (is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
                   $sql="SELECT * from {local_costcenter} where visible=1 AND depth <3 ORDER by sortorder,fullname ";
        $assigned_costcenters = $DB->get_records_sql($sql);
    } else {
         $sql="SELECT * from {local_costcenter} where visible = 1 and (id = ? or parentid = ?) ORDER by sortorder,fullname";
        $assigned_costcenters = $DB->get_records_sql($sql, [$USER->open_costcenterid, $USER->open_costcenterid]);
    }
    return $assigned_costcenters;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_costcenter_leftmenunode(){
    global $USER;
    $systemcontext = context_system::instance();
    $costcenternode = '';
    if(has_capability('local/costcenter:view', $systemcontext) || is_siteadmin()) {     
        $costcenternode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_departments', 'class'=>'pull-left user_nav_div departments'));
        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $organization_url = new moodle_url('/local/costcenter/index.php');
            $organization_string = get_string('orgStructure','local_costcenter');
        }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $organization_url = new moodle_url('/local/costcenter/costcenterview.php',array('id' => $USER->open_costcenterid));
            $organization_string = get_string('orgStructure','local_costcenter');
        }else{
            $organization_url = new moodle_url('/local/costcenter/costcenterview.php',array('id' => $USER->open_departmentid));
            $organization_string = get_string('orgStructure','local_costcenter');
        }
        $department = html_writer::link($organization_url, '<span class="org_structure_icon left_menu_icons"></span><span class="user_navigation_link_text">'.$organization_string.'</span>',array('class'=>'user_navigation_link'));
        $costcenternode .= $department;
        $costcenternode .= html_writer::end_tag('li');
    }

    return array('2' => $costcenternode);
}

/*
* Author sarath
* @return  plugins count with all modules
*/
function local_costcenter_plugins_count($costcenterid, $departmentid=false, $subdepartmentid=false){
    global $CFG;
    $core_component = new core_component();
    $local_pluginlist = $core_component::get_plugin_list('local');
    $deparray = array();
    foreach($local_pluginlist as $key => $local_pluginname){
        if(file_exists($CFG->dirroot.'/local/'.$key.'/lib.php')){
            require_once($CFG->dirroot.'/local/'.$key.'/lib.php');
            $functionname = 'costcenterwise_'.$key.'_count';
            if(function_exists($functionname)){
                // if($subdepartmentid){
                    // if($key === 'users')
                $data = $functionname($costcenterid,$departmentid, $subdepartmentid);
                // }else{
                //     $data = $functionname($costcenterid,$departmentid, $subdepartmentid);
                // }
                foreach($data as  $key => $val){
                    $deparray[$key] = $val;
                }
            }
        }
    }
    return $deparray;
}

/*
* Author sarath
* @return true for reports under category
*/
function learnerscript_costcenter_list(){
    return 'Costcenter';
}


function local_costcenter_output_fragment_licence_form($args){
    global $CFG,$DB;
    $args = (object) $args;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    // print_object($formdata);
    // print_object($args);
    // exit;
    $mform = new local_costcenter\form\licence_form(null, array(), 'post', '', null, true, $formdata);
 
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
