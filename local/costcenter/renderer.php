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

use core_component;
require_once($CFG->dirroot.'/local/costcenter/lib.php');
if(file_exists($CFG->dirroot.'/local/includes.php')){
    require_once($CFG->dirroot.'/local/includes.php');
}
class local_costcenter_renderer extends plugin_renderer_base {

    /**
     * @method treeview
     * @todo To add action buttons
     */
    public function departments_view() {
        global $DB, $CFG, $OUTPUT, $USER,$PAGE;
        $systemcontext = context_system::instance();

        $costcenter_instance = new costcenter;
        
         if (is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
            $sql = "SELECT distinct(s.id), s.* FROM {local_costcenter} s where parentid=0 ORDER BY s.sortorder";
            $costcenters = $DB->get_records_sql($sql);
        } else if(has_capability('local/costcenter:view', $systemcontext)){
            $sql = "SELECT distinct(s.id), s.* FROM {local_costcenter} s where parentid = 0 AND id = ? ORDER BY s.sortorder";
            $costcenters = $DB->get_records_sql($sql, [$USER->open_costcenterid]);
        } 
        
        if (!is_siteadmin() && empty($costcenters)) {
            print_error('notassignedcostcenter', 'local_costcenter');
        }
        $data = array();
        if(!empty($costcenters)){
            foreach ($costcenters as $costcenter) {
                $line = array();
                $showdepth = 1;
                $line[] = $this->display_department_item($costcenter, $showdepth);
                $data[] = $line;
            }
            $table = new html_table();
            if (has_capability('local/costcenter:manage', $systemcontext)){
                $table->head = array('');
                $table->align = array('left');
                $table->width = '100%';
                $table->data = $data;
                $table->id = 'department-index';
                $output = html_writer::table($table);
            }
        }else{
            $output = html_writer::tag('div', get_string('noorganizationsavailable', 'local_costcenter'), array('class'=>'alert alert-info text-xs-center'));
        }
        return $output;
    }

    /**
     * @method display_department_item
     * @todo To display the all costcenter items
     * @param object $record is costcenter  
     * @param boolean $indicate_depth  depth for the costcenter item
     * @return string
     */
    public function display_department_item($record, $indicate_depth = true) {
        global $OUTPUT, $DB, $CFG, $PAGE;
        require_once($CFG->dirroot.'/local/costcenter/lib.php');
        $core_component = new core_component();
        
        $systemcontext = context_system::instance();

        $sql="SELECT id, id as id_val from {local_costcenter} where parentid=?";
        $orgs = $DB->get_records_sql_menu($sql, [$record->id]);

        $departmentcount = count($orgs);

        if($departmentcount > 0){
            $dept_count_link = new moodle_url("/local/costcenter/costcenterview.php?id=".$record->id."");
        }else{
            $dept_count_link = 'javascript:void(0)';            
        }

        $subdepartmentcount = 0;

        if($departmentcount){
            list($orgsql, $orgparams) = $DB->get_in_or_equal($orgs, SQL_PARAMS_NAMED, 'param', true, false);
            $subsql = "SELECT id, id as id_val from {local_costcenter} where parentid $orgsql";
            $subids = $DB->get_records_sql_menu($subsql, $orgparams);
            $subdepartmentcount = count($subids);
            if($subdepartmentcount > 0){
            $subdepartmentcount = $subdepartmentcount;
            }else{
            $subdepartmentcount = 'N/A';
            }
        } else {
            $subdepartmentcount = 'N/A';
        }

        // //this is for all plugins count
        $pluginnavs = local_costcenter_plugins_count($record->id);
        
        $itemdepth = ($indicate_depth) ? 'depth' . min(10, $record->depth) : 'depth1';
        // @todo get based on item type or better still, don't use inline styles :-(
        $itemicon = $OUTPUT->image_url('/i/item');
        $cssclass = !$record->visible ? 'dimmed' : '';

        if (has_capability('local/costcenter:manage', $systemcontext)) {
            $edit = true;        
            if ($record->visible) {
                $hide = true;
                $show = false;
                $hideurl = 'javascript:void(0)';
                $showurl = 'javascript:void(0)';
            }else{
                $show = true;
                $hide = false;
                $showurl = 'javascript:void(0)';
                $hideurl = 'javascript:void(0)';
            }
            $action_message = get_string('confirmation_to_disable_'.$record->visible, 'local_costcenter', $record->fullname);
            if($departmentcount == 0 && $usercount == 0){
                $delete = true;
                $del_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$record->fullname);
            }else{
                $delete = false;
                $del_confirmationmsg = '';
            }
        }
         $viewdeptContext = [
            "coursefileurl" => $OUTPUT->image_url('/course_images/courseimg', 'local_costcenter'),
            "orgname" => format_string($record->fullname),
            "dept_count_link" => $dept_count_link,
            "deptcount" => $departmentcount,
            "subdeptcount" => $subdepartmentcount,
            "editicon" => $OUTPUT->image_url('t/edit'),
            "hideicon" => $OUTPUT->image_url('t/hide'),
            "showicon" => $OUTPUT->image_url('t/show'),
            "deleteicon" => $OUTPUT->image_url('t/delete'),
            "hideurl" => $hideurl,
            "showurl" => $showurl,
            "edit" => $edit,
            "hide" => $hide,
            "show" => $show,
            "action_message" => $action_message,
            "delete_message" => $del_confirmationmsg,
            "status" => $record->visible,
            "delete" => $delete,
            "recordid" => $record->id,
            "parentid" => $record->parentid,
            "headstring" => 'editcostcen',
            "formtype" => 'organization'
        ];

        $viewdeptContext = $viewdeptContext+$pluginnavs;

        return $this->render_from_template('local_costcenter/costcenter_view', $viewdeptContext);
    }

    /**
     * @method get_dept_view_btns
     * @todo To display create icon
     * @param object $id costcenter  id
     * @return string
     */
    public function get_dept_view_btns($id = false) {
        global $PAGE, $USER, $DB;
        $systemcontext = context_system::instance();
        if ((is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && $PAGE->pagetype == 'local-costcenter-index'){
             $create_organisation = "<a class='course_extended_menu_itemlink' data-action='createcostcentermodal' data-value='0' title = 'Create Organization' onclick ='(function(e){ require(\"local_costcenter/newcostcenter\").init({selector:\"createcostcentermodal\", contextid:$systemcontext->id, id:0, formtype:\"organization\", headstring:\"adnewcostcenter\"}) })(event)'><span class='createicon'><i class='fa fa-sitemap icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span></a>";
            // $create_organisation = false;
        }else{
            $create_organisation = false;
        }
       //  $cap_array = array('local/costcenter:manage_multiorganizations', 'local/costcenter:manage_ownorganization', 'local/costcenter:manage_owndepartments');
    //     if(is_siteadmin() || has_any_capability($cap_array, $systemcontext)){
        $exist_sql = "SELECT id FROM {local_costcenter} WHERE 1=1 ";
            // if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            //     $exist_sql .= " AND "
            // }
        $costcenters_exist = $DB->record_exists_sql($exist_sql);
        if($id){
            $depth = $DB->get_field('local_costcenter', 'depth', array('id' => $id));
        }else{
            $depth = 1;
        }
        if($costcenters_exist && $depth != 2){
            if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext) || has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
                $headstring = 'addnewdept';
                $title = 'Create Departmnet';
                $create_department = "<a class='course_extended_menu_itemlink' data-action='createcostcentermodal' data-value='0' title = '$title' onclick ='(function(e){ require(\"local_costcenter/newcostcenter\").init({selector:\"createcostcentermodal\", contextid:$systemcontext->id, id:0, formtype:\"department\", headstring:\"$headstring\"}) })(event)'>
                    <i class='icon fa fa-plus-square'></i>
                </a>";
                $msettingsurl = new moodle_url('/local/courses/module_settings.php', array());
                $msettingslink = html_writer::link($msettingsurl, '<i class="fa fa-wrench icon" aria-hidden="true"></i>', array('title' => 'Module Settings','class'=>'course_extended_menu_itemlink'));
            }else{
                $create_department = false;
                $msettingslink = false;
            }
        }else{
            $create_department = false;
        }
        $deptexistsql = "SELECT id FROM {local_costcenter} WHERE depth = 2 ";
        if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext))){
            $deptexistsql .= " AND parentid={$USER->open_costcenterid} ";
        }
        $deptexist = $DB->record_exists_sql($deptexistsql);
        if($deptexist){
            $headstring = 'addnewsubdept';
                $title = 'Create Sub Departmnet';
                $create_sub_department = "<a class='course_extended_menu_itemlink' data-action='createcostcentermodal' data-value='0' title = '$title' onclick ='(function(e){ require(\"local_costcenter/newcostcenter\").init({selector:\"createcostcentermodal\", contextid:$systemcontext->id, id:0, formtype:\"subdepartment\", headstring:\"$headstring\"}) })(event)'>
                    <i class='icon fa fa-plus'></i>
                </a>";
        }else{
            $create_sub_department = false;
        }

        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $title = 'Start and Enddates';
            $calendaricon = '<i class="fa fa-calendar icon"></i>';
            $url = new moodle_url('/blocks/empcredits/ilp_startend.php', array());
            $display_startenddates = html_writer::link($url, $calendaricon, array('title'=>$title,'class'=>'course_extended_menu_itemlink'));
        }else{
            $display_startenddates = false;
        }

        // $systemcontext = context_system::instance();
        // if ((is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && 
        //     $PAGE->pagetype == 'local-costcenter-index'){
        //     $createdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createcostcentermodal' data-value='0' title = 'Create Organization/Department' onclick ='(function(e){ require(\"local_costcenter/newcostcenter\").init({selector:\"createcostcentermodal\", contextid:$systemcontext->id, costcenterid:0, parentid:0}) })(event)' ><span class='createicon'><i class='fa fa-sitemap icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span></a>";
        // } else {
        //     $createdeptpopup = '';
        // }
        // if(is_siteadmin() || has_capability('local/costcenter:create',$systemcontext)){
        //     $costcenterrecord = $DB->get_record('local_costcenter', array('id' => $_GET['id']), 'id, visible, parentid');
        //     $visible = $costcenterrecord->visible;
        //     $parent = $costcenterrecord->parentid;
        //     if($visible){
        //         if($parent){
        //             $dept = 0;
        //             $subdept = 1;
        //             $string = get_string('createsubdepartment','local_costcenter');
        //             $iconelement = "<i class='icon fa fa-plus-square' aria-hidden='true'></i>";
        //         }else{
        //             $dept = 1;
        //             $subdept = 0;
        //             $string = get_string('createdepartment','local_costcenter');
        //             $iconelement = "<span class='createicon'><i class='fa fa-sitemap icon' aria-hidden='true'></i><i class='createiconchild fa fa-plus' aria-hidden='true'></i></span>";
        //             $createdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createdeptmodal' data-value='0' title = '".get_string('createsubdepartment','local_costcenter')."' onclick ='(function(e){ require(\"local_costcenter/newsubdept\").init({selector:\"createdeptmodal\", contextid:$systemcontext->id, costcenterid:0, parentid:0,dept:0,subdept:1}) })(event)' ><i class='icon fa fa-plus-square' aria-hidden='true'></i></a>";
        //         }
        //         $createsubdeptpopup = "<a class='course_extended_menu_itemlink' data-action='createdeptmodal' data-value='0' title = '".$string."' onclick ='(function(e){ require(\"local_costcenter/newsubdept\").init({selector:\"createdeptmodal\", contextid:$systemcontext->id, costcenterid:0, parentid:$id,dept:$dept,subdept:$subdept}) })(event)' >$iconelement</a>";
        //     }
        // } else {
        //     $createsubdeptpopup = '';
        // }
        // $buttons = [
        //     "createdeptpopup" => $createdeptpopup,
        //     "createsubdeptpopup" => $createsubdeptpopup
        // ];
        $buttons = array(
            'create_organisation' => $create_organisation,
            'create_department' => $create_department,
            'create_sub_department' => $create_sub_department,
            'modulesettings' => $msettingslink,
            'display_startenddates' => $display_startenddates
        );
    return $this->render_from_template('local_costcenter/viewbuttons', $buttons);
    }


    /**
     * @method get_dept_view_btns
     * @todo To display create icon
     * @param object $id costcenter  id
     * @return string
     */
    public function costcenterview($id, $systemcontext) {
        global $DB, $USER, $OUTPUT, $CFG;
        if (!$depart = $DB->get_record('local_costcenter', array('id' => $id))) {
            print_error('invalidschoolid');
        }
        if (has_capability('local/costcenter:manage', $systemcontext)) {
            $edit = true;
            if ($depart->visible) {
                $hide = true;
                $show = false;
            }else{
                $show = true;
                $hide = false;
            }
            $action_message = get_string('confirmation_to_disable_'.$depart->visible, 'local_costcenter', $depart->fullname);
            
            if(($depart) == 0 && $pluginnavs['totalusers'] == 0){//count removed <revathi>
                $delete = true;
                $del_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$depart->fullname);
            }else{
                $delete = false;
                $del_confirmationmsg = '';
            }
        }
        $dept_count_link = '';
        $subdepartment = '';
        $departments_sql="SELECT id,id AS id_val FROM {local_costcenter} WHERE parentid=:parent";
        $departments =$DB->get_records_sql_menu($departments_sql, array('parent' => $id));
        $department = count($departments);
        $department = ($department > 0 ? $department : 'N/A');
        $dept_id=implode(',',$departments);

        if($dept_id){
             $subdepartments_sql="SELECT id,id AS id_val FROM {local_costcenter} WHERE parentid IN($dept_id);";
             $subdepartments = $DB->get_records_sql_menu($subdepartments_sql);
             $subdepartment = count($subdepartments);
             $subdepartment = ($subdepartment > 0 ? $subdepartment : 'N/A');        
        }   

        $dept_count_link = $department;

        $departments = $DB->get_records('local_costcenter', array('parentid' =>$id));
        $totaldepts = count($departments);
        /*data for organization details ends here*/
        $departments_content = array();
        if($totaldepts % 2 == 0){ 
            $deptclass = '';
        }else{ 
            $deptclass = 'deptsodd';
        } 

        $deptkeys = array_values($departments);
        foreach($deptkeys as $key => $dept){
            $even = false;
            $odd = false;
            if($key % 2 == 0){ 
                $even = true;
            } 
            else{ 
                $odd = true;
            } 
         
            $departments_array = array();
            $subdepartments = $DB->get_records('local_costcenter', array('parentid' =>$dept->id));
            
            $subdept = count($subdepartments);
            if($subdept){
                $subdept_count_link = $CFG->wwwroot.'/local/costcenter/costcenterview.php?id='.$dept->id;
            }else{
                $subdept_count_link = "javascript:void(0)";
            }
            $subdept = ($subdept > 0 ? $subdept : 'N/A');        

            $deparray = local_costcenter_plugins_count($dept->parentid,$dept->id);
            
            if (has_capability('local/costcenter:manage', $systemcontext)) {
                $deptedit = true;
                if ($dept->visible) {
                    $depthide = true;
                    $deptshow = false;
                }else{
                    $deptshow = true;
                    $depthide = false;
                }
                $deptaction_message = get_string('confirmation_to_disable_'.$dept->visible, 'local_costcenter', $dept->fullname);
                $deptdelete = true;
                $deptdel_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$dept->fullname);
            }
          
            $departments_array['subdept'] = $subdept;
            $departments_array['enablesubdepartment_link'] = true;
            $departments_array['subdept_count_link'] = $subdept_count_link;
            $departments_array['departmentparentid'] = $dept->parentid;
            $departments_array['departmentfullname'] = $dept->fullname;
            $departments_array['edit_image_url'] = $OUTPUT->image_url('t/edit');
            $departments_array['subdepartments_content'] = $subdepartments_content;
            $departments_array['even'] = $even;
            $departments_array['odd'] = $odd;
            $departments_array['deptclass'] = $deptclass;
            $departments_array['deptedit'] = $deptedit;
            $departments_array['depthide'] = $depthide;
            $departments_array['deptshow'] = $deptshow;
            $departments_array['deptstatus'] = $dept->visible;
            $departments_array['deptdelete'] = $deptdelete;
            $departments_array['deptid'] = $dept->id;
            $departments_array['deptaction_message'] = $deptaction_message;
            $departments_array['deptdel_confirmationmsg'] = $deptdel_confirmationmsg;
            $departments_array['headstring'] = 'update_costcenter';
            $departments_array['formtype'] = 'department';
            $departments_content[] = $departments_array+$deparray;
        }

        $costcenter_view_content = [
            "deptcount" => $dept_count_link,
            "subdeptcount" => $subdepartment,
            "deptclass" => $deptclass, 
            "coursefileurl" => $OUTPUT->image_url('/course_images/courseimg', 'local_costcenter'),
            "orgname" => $depart->fullname,
            "edit" => $edit,
            "hide" => $hide,
            "show" => $show,
            "status" => $depart->visible,
            "delete" => $delete,
            "recordid" => $depart->id,
            "parentid" => $depart->parentid,
            "action_message" => $action_message,
            "delete_message" => $del_confirmationmsg,
            "departments_content" => $departments_content,
            "headstring" => 'editcostcen',
            "formtype" => 'organization'
        ];
        $pluginnavs = local_costcenter_plugins_count($id);
        $costcenter_view_content = $costcenter_view_content+$pluginnavs;
        return $OUTPUT->render_from_template('local_costcenter/departments_view', $costcenter_view_content);
    }
    public function department_view($id, $systemcontext){
        global $DB, $USER, $OUTPUT;
        if (!$depart = $DB->get_record('local_costcenter', array('id' => $id))) {
            print_error('invalidschoolid');
        }
        if (has_capability('local/costcenter:manage', $systemcontext)) {
            $edit = true;
            if ($depart->visible) {
                $hide = true;
                $show = false;
            }else{
                $show = true;
                $hide = false;
            }
            $action_message = get_string('confirmation_to_disable_department'.$depart->visible, 'local_costcenter', $depart->fullname);
            if(($depart) == 0 && $pluginnavs['totalusers'] == 0){//count <revathi>
                $delete = true;
                $del_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$depart->fullname);
            }else{
                $delete = false;
                $del_confirmationmsg = '';
            }
        }
        $organisationid = $DB->get_field('local_costcenter', 'parentid', array('id' => $id));
        $subdepartment_link = '';
        $subdepartment = '';
        $departments_sql="SELECT id,id AS id_val FROM {local_costcenter} WHERE parentid=:parent";
        $departments =$DB->get_records_sql_menu($departments_sql, array('parent' => $id));
        $department = count($departments);
        $department = ($department > 0 ? $department : 'N/A');
        // $dept_id=implode(',',$departments);
        // if($dept_id){
        //      $subdepartments_sql="SELECT id,id AS id_val FROM {local_costcenter} WHERE parentid IN($dept_id);";
        //      $subdepartments = $DB->get_records_sql_menu($subdepartments_sql);
        //      $subdepartment = count($subdepartments);
        //      $subdepartment = ($subdepartment > 0 ? $subdepartment : 'N/A');        
        // }   

        $subdepartment_link = $department;

        $subdepartments = $DB->get_records('local_costcenter', array('parentid' =>$id));
        $totalsubdepts = count($subdepartments);
        /*data for organization details ends here*/
        $departments_content = array();
        if($totaldepts % 2 == 0){ 
            $deptclass = '';
        }else{ 
            $deptclass = 'deptsodd';
        } 

        $deptkeys = array_values($subdepartments);

        foreach($deptkeys as $key => $dept){
            $even = false;
            $odd = false;
            if($key % 2 == 0){ 
                $even = true;
            } 
            else{ 
                $odd = true;
            } 
         
            $departments_array = array();
            $subdepartments = $DB->get_records('local_costcenter', array('parentid' =>$dept->id));
            
            $subdept = count($subdepartments);
            $subdept = ($subdept > 0 ? $subdept : 'N/A');        

            $deparray = local_costcenter_plugins_count($organisationid, $dept->parentid,$dept->id);
            
            if (has_capability('local/costcenter:manage', $systemcontext)) {
                $deptedit = true;
                if ($dept->visible) {
                    $depthide = true;
                    $deptshow = false;
                }else{
                    $deptshow = true;
                    $depthide = false;
                }
                $deptaction_message = get_string('confirmation_to_disable_'.$dept->visible, 'local_costcenter', $dept->fullname);
                $deptdelete = true;
                $deptdel_confirmationmsg = get_string('confirmationmsgfordel', 'local_costcenter',$dept->fullname);
            }
          
            $departments_array['subdept'] = $subdept;
            $departments_array['enablesubdepartment_link'] = false;
            $departments_array['subdept_count_link'] = $subdept_count_link;
            $departments_array['departmentparentid'] = $dept->parentid;
            $departments_array['departmentfullname'] = $dept->fullname;
            $departments_array['edit_image_url'] = $OUTPUT->image_url('t/edit');
            $departments_array['subdepartments_content'] = $subdepartments_content;
            $departments_array['even'] = $even;
            $departments_array['odd'] = $odd;
            $departments_array['deptclass'] = $deptclass;
            $departments_array['deptedit'] = $deptedit;
            $departments_array['depthide'] = $depthide;
            $departments_array['deptshow'] = $deptshow;
            $departments_array['deptstatus'] = $dept->visible;
            $departments_array['deptdelete'] = $deptdelete;
            $departments_array['deptid'] = $dept->id;
            $departments_array['deptaction_message'] = $deptaction_message;
            $departments_array['hide_users'] = FALSE;
            $departments_array['hide_courses'] = TRUE;
            $departments_array['hide_exams'] = TRUE;
            $departments_array['hide_learninplans'] = TRUE;
            $departments_array['hide_feedbacks'] = TRUE;
            $departments_array['hide_classroom'] = TRUE;
            $departments_array['hide_program'] = TRUE;
            $departments_array['hide_certification'] = TRUE;
            $departments_array['headstring'] = 'update_subdept';
            $departments_array['formtype'] = 'subdepartment';
            $departments_array['deptdel_confirmationmsg'] = $deptdel_confirmationmsg;
            $departments_content[] = $departments_array+$deparray;
        }

        $costcenter_view_content = [
            'showsubdept_content' => true,
            'totalsubdepts' => $totalsubdepts,
            "deptcount" => $dept_count_link,
            "subdeptcount" => $subdepartment,
            "deptclass" => $deptclass, 
            "coursefileurl" => $OUTPUT->image_url('/course_images/courseimg', 'local_costcenter'),
            "orgname" => $depart->fullname,
            "edit" => $edit,
            "hide" => $hide,
            "show" => $show,
            "status" => $depart->visible,
            "delete" => $delete,
            "recordid" => $depart->id,
            "parentid" => $depart->parentid,
            "action_message" => $action_message,
            "delete_message" => $del_confirmationmsg,
            "departments_content" => $departments_content,
            "headstring" => 'update_costcenter',
            "formtype" => 'department'
        ];
        $pluginnavs = local_costcenter_plugins_count($organisationid, $id);
        $costcenter_view_content = $costcenter_view_content+$pluginnavs;
        return $OUTPUT->render_from_template('local_costcenter/departments_view', $costcenter_view_content);
    }
}
