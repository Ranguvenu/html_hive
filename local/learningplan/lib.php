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
 * @subpackage local_learning
 */

 define('learningplan', 4);
 
function local_learningplan_output_fragment_new_learningplan($args){
	global $CFG,$DB, $PAGE;
	$args = (object) $args;
    $contextid = $args->contextid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = ($args->jsonformdata);//json_decode
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
		if(isset($args->id) && $args->id > 0){
			$data = $DB->get_record('local_learningplan', array('id'=>$args->id));
		}else if(isset($args->planid)&&$args->planid>0){
			$data = $DB->get_record('local_learningplan', array('id'=>$args->planid));
		}
		if($data){
			$description = $data->description;
			unset($data->description);
			$data->description['text'] = $description;
			$data->open_band = (!empty($data->open_band)) ? array_diff(explode(',',$data->open_band), array('')) :NULL;
			// $data->open_hrmsrole = (!empty($data->open_hrmsrole)) ? array_diff(explode(',',$data->open_hrmsrole), array('')) :array(NULL=>NULL);
			$data->open_branch =(!empty($data->open_branch)) ? array_diff(explode(',',$data->open_branch), array('')) :NULL;
			$data->open_group =(!empty($data->open_group)) ? array_diff(explode(',',$data->open_group), array('')) :array(NULL=>NULL);
			$data->open_designation = (!empty($data->open_designation)) ? array_diff(explode(',',$data->open_designation), array('')) :array(NULL=>NULL);
            $data->open_location = (!empty($data->open_location)) ? array_diff(explode(',',$data->open_location), array('')) :array(NULL=>NULL);
            $data->open_grade = (!empty($data->open_grade)) ? array_diff(explode(',',$data->open_grade), array('')) :array(NULL=>NULL);
			$data->department =(!empty($data->department)) ? (count(explode(',',$data->department))>1)? array_diff(explode(',',$data->department), array('')):$data->department :NULL;
      
            $mform = new local_learningplan\forms\learningplan(null, array('editoroptions' => $editoroptions, 'id'=>$data->id, 'form_status' => $args->form_status), 'post', '', null, true, $formdata);
            // Populate tags.
            // $data->tags = local_tags_tag::get_item_tags_array('local_learningplan', 'learningplan', $data->id);
            if(!empty($data->certificateid)){
                $data->map_certificate = 1;
            }else{
                $data->map_certificate = null;
            }
            $description = $data->description;
			$mform->set_data($data);
		}
    }
    else{
    	$mform = new local_learningplan\forms\learningplan(null, array('editoroptions' => $editoroptions, 'form_status' => $args->form_status), 'post', '', null, true, $formdata);
    }

    if (!empty($args->jsonformdata) && strlen($args->jsonformdata) >2) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    $renderer = $PAGE->get_renderer('local_users');
	
	ob_start();
	$formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_users\output\form_status($formstatus);
    $o .= $renderer->render($formstatusview);
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
function local_learningplan_output_fragment_lpcourse_enrol($args){
	global $CFG,$DB, $PAGE;
	$args = (object) $args;
    $contextid = $args->contextid;
    $planid = $args->planid;
    $o = '';
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata =($args->jsonformdata);// json_decode
        parse_str($serialiseddata, $formdata);
    }
    $mform = new local_learningplan\forms\courseenrolform(null,array('planid' => $planid, 'condition' => 'manage'));
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}
function local_learningplan_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
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
    $file = $fs->get_file($context->id, 'local_learningplan', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }
    send_file($file, $filename, 0, $forcedownload, $options);
}
function learningplan_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    // $sql = "SELECT id, name FROM {local_learningplan} WHERE id > 1";
    $learningplan_params = array();
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $sql = " SELECT id, name FROM {local_learningplan} WHERE 1 = 1 ";
    }else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $sql = " SELECT id, name FROM {local_learningplan} WHERE  costcenter = :costcenter ";
        $learningplan_params['costcenter'] = $USER->open_costcenterid;
    }else{
        $sql = " SELECT id, name FROM {local_learningplan} WHERE  costcenter = :costcenter AND (department = :department OR department = -1) ";
        $learningplan_params['costcenter'] = $USER->open_costcenterid;
        $learningplan_params['department'] = $USER->open_departmentid;
    }
    if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
        $learningplanlist = $DB->get_records_sql_menu($sql, $learningplan_params);
    }
    $select = $mform->addElement('autocomplete', 'learningplan', '', $learningplanlist, array('placeholder' => get_string('learning_path_name', 'local_learningplan')));
    $mform->setType('learningplan', PARAM_RAW);
    $select->setMultiple(true);
}
function get_user_learningplan($userid) {
    global $DB, $CFG;
    $query = "SELECT lp.* 
                FROM {local_learningplan_user} AS ulp
                JOIN {local_learningplan} AS lp ON lp.id = ulp.planid
                WHERE lp.visible = ? AND ulp.userid = ?";
    $params = [1, $userid];
    $lps = $DB->get_records_sql($query, $params);
    return $lps;
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_learningplan_leftmenunode(){
    $systemcontext = context_system::instance();
    $learningplannode = '';
    if(has_capability('local/learningplan:manage', $systemcontext) || is_siteadmin()) {
        $learningplannode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_learningplans', 'class'=>'pull-left user_nav_div learningplans'));
            $learningplan_url = new moodle_url('/local/learningplan/index.php');
            $learningplan = html_writer::link($learningplan_url, '<span class="manage_learningpath_icon left_menu_icons"></span><span class="user_navigation_link_text">'.get_string('managelep','local_learningplan').'</span>',array('class'=>'user_navigation_link'));
            $learningplannode .= $learningplan;
        $learningplannode .= html_writer::end_tag('li');
    }

    return array('9' => $learningplannode);
}
function local_learningplan_quicklink_node(){
    global $CFG, $PAGE, $OUTPUT;
    $systemcontext = context_system::instance();
    if (is_siteadmin() || has_capability('local/learningplan:view',$systemcontext)){
            //local learningplans content
        $PAGE->requires->js_call_amd('local_learningplan/lpcreate', 'load', array());
        // $local_learningplans_content = $PAGE->requires->js_call_amd('local_learningplan/lpcreate', 'load', array());
        // $local_learningplans_content .= "<span class='anch_span'><i class='fa fa-map' aria-hidden='true'></i></span>";
        // $local_learningplans_content .= "<div class='quick_navigation_detail'>
        //                                     <div class='span_str'>".get_string('manage_br_learningplan', 'local_learningplan')."</div>";
        //         $display_line = false;
        //     if(is_siteadmin() || (has_capability('local/learningplan:manage', $systemcontext) && has_capability('local/learningplan:create', $systemcontext))){
        //     $local_learningplans_content .= "<span class='span_createlink'>
        //                                         <a href='javascript:void(0);' class='quick_nav_link goto_local_learningplan' title='".get_string('create_learningplan', 'local_learningplan')."' data-action='createlpmodal' onclick ='(function(e){ require(\"local_learningplan/lpcreate\").init({selector:\"createlpmodal\", contextid:".$systemcontext->id.", planid:0,form_status:0}) })(event)'>".get_string('create')."</a>";
        //         $display_line = true;
        //     }  
            
        //     if($display_line) {
        //         $local_learningplans_content .= " | ";
        //     }
        //         $local_learningplans_content .= " <a href='".$CFG->wwwroot."/local/learningplan/index.php' class='viewlink' title= '".get_string('view_learningplan', 'local_learningplan')." '>".get_string('view')."</a>
        //                                     </span>";
        // $local_learningplans_content .= "</div>";
        // $local_learningplans = '<div class="quick_nav_list manage_learningplans one_of_three_columns" >'.$local_learningplans_content.'</div>';
        $learningplan = array();
        $learningplan['node_header_string'] = get_string('manage_br_learningplan', 'local_learningplan');
        $learningplan['pluginname'] = 'learningplans';
        $learningplan['plugin_icon_class'] = 'fa fa-map';
        if(is_siteadmin() || (has_capability('local/learningplan:manage', $systemcontext) && has_capability('local/learningplan:create', $systemcontext))){
            $learningplan['create'] = TRUE;
            $learningplan['create_element'] = html_writer::link('javascript:void(0)', get_string('create'), array('class' => 'quick_nav_link goto_local_learningplan', 'title' => get_string('create_learningplan', 'local_learningplan'), 'data-action' => 'createlpmodal', 'onclick' => '(function(e){ require("local_learningplan/lpcreate").init({selector:"createlpmodal", contextid:'.$systemcontext->id.', planid:0,form_status:0}) })(event)'));
        }
        // if(has_capability('local/courses:view', $systemcontext) || has_capability('local/courses:manage', $systemcontext)){
        $learningplan['viewlink_url'] = $CFG->wwwroot.'/local/learningplan/index.php';
        $learningplan['view'] = TRUE;
        $learningplan['viewlink_title'] = get_string("view_learningplan", "local_learningplan");
        // }
        $learningplan['space_count'] = 'one';
        $content = $OUTPUT->render_from_template('block_quick_navigation/quicklink_node', $learningplan);
    }
    
    return array('5' => $content);
}

/*
* Author Sarath
* return count of learningplans under selected costcenter
* @return  [type] int count of learningplans
*/
function costcenterwise_learningplan_count($costcenter,$department = false){
    global $USER, $DB;
        $params = array();
        $params['costcenter'] = $costcenter;
        $countlpql = "SELECT count(id) FROM {local_learningplan} WHERE costcenter = :costcenter";
        if($department){
            $countlpql .= " AND department = :department ";
            $params['department'] = $department;
        }
        $activesql = " AND visible = 1 ";
        $inactivesql = " AND visible = 0 ";

        $countlps = $DB->count_records_sql($countlpql, $params);
        $activelps = $DB->count_records_sql($countlpql.$activesql, $params);
        $inactivelps = $DB->count_records_sql($countlpql.$inactivesql, $params);

    return array('lp_plugin_exist' => true,'alllearningplans' => $countlps,'activelearningplans' => $activelps,'inactivelearningplans' => $inactivelps);
}

/*
* Author sarath
* @return true for reports under category
*/
function learnerscript_learningplan_list(){
    return 'Learningpath';
}
function local_learningplan_request_dependent_query($aliasname){
    $returnquery = " WHEN ({$aliasname}.compname LIKE 'learningplan') THEN (SELECT name from {local_learningplan} WHERE id = {$aliasname}.componentid) ";
    return $returnquery;
}
/**
 * Returns learningplans tagged with a specified tag.
 *
 * @param local_tags_tag $tag
 * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
 *             are displayed on the page and the per-page limit may be bigger
 * @param int $fromctx context id where the link was displayed, may be used by callbacks
 *            to display items in the same context first
 * @param int $ctx context id where to search for records
 * @param bool $rec search in subcontexts as well
 * @param int $page 0-based number of page being displayed
 * @return \local_tags\output\tagindex
 */
function local_learningplan_get_tagged_learningplans($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0, $sort = '') {
    global $CFG, $PAGE;
    // prepare for display of tags related to tests
    $perpage = $exclusivemode ? 10 : 5;
    $displayoptions = array(
        'limit' => $perpage,
        'offset' => $page * $perpage,
        'viewmoreurl' => null,
    );
    $renderer = $PAGE->get_renderer('local_learningplan');
    $totalcount = $renderer->tagged_learningplans($tag->id, $exclusivemode, $ctx, $rec, $displayoptions, $count = 1, $sort);
    $content = $renderer->tagged_learningplans($tag->id, $exclusivemode, $ctx, $rec, $displayoptions, 0, $sort);
    $totalpages = ceil($totalcount / $perpage);
    if ($totalcount)
    return new local_tags\output\tagindex($tag, 'local_learningplan', 'learningplan', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
    else
    return '';
}

/**
* todo sql query departmentwise
* @param  $systemcontext object
* @return array
**/
function orgsql($systemcontext){
    global $DB, $USER;
    $sql = '';
    $params =array();
    if (has_capability('local/learningplan:manage', $systemcontext) && 
        has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $sql = " AND  c.costcenter = :costcenter";
        $params['costcenter'] = $USER->open_costcenterid;
    } else if (has_capability('local/costcenter:manage_owndepartments', $systemcontext)) {
        $sql = " AND  c.department = :department";
        $params['department'] = $USER->open_departmentid;
    } else {
        $sql .= " AND  c.costcenter = :costcenter";
        $params['costcenter'] = $USER->open_costcenterid;
        $sql .= " AND  ( c.department = :department OR c.department = '-1' ) ";
        $params['department'] = $USER->open_departmentid;
        // target audience
        $gparams = array();
        $group_list = $DB->get_records_sql_menu("select cm.id,cm.cohortid as groupid from {cohort_members} cm where cm.userid IN ({$USER->id})");
        if (!empty($group_list)){
             $groups_members = implode(',', $group_list);
             if(!empty($group_list)){
                $grouquery = array();
                foreach ($group_list as $key => $group) {
                    $grouquery[] = " CONCAT(',',c.open_group,',') LIKE CONCAT('%,',$group,',%') "; 
                }
                $groupqueeryparams =implode('OR',$grouquery);
                $gparams[]= '('.$groupqueeryparams.')';
             }
        }

        if(!empty($gparams))
          $opengroup=implode('AND',$gparams);
        else
          $opengroup = '1 != 1';
        $fparams = array();
        $fparams[]= " 1 = CASE WHEN (c.open_group!='-1' AND c.open_group <> '')
                THEN
                  CASE WHEN $opengroup
                    THEN 1
                    ELSE 0 END 
                ELSE 1 END ";
        if(!empty($USER->open_departmentid) && $USER->open_departmentid != ""){
          $departmentlike = "'%,$USER->open_departmentid,%'";
        }else{
          $departmentlike = "''";
        }
        $fparams[]= " 1 = CASE WHEN c.department!='-1'
          THEN 
            CASE WHEN CONCAT(',',c.department,',') LIKE {$departmentlike}
            THEN 1
            ELSE 0 END
          ELSE 1 END ";
        if(!empty($USER->open_subdepartment) && $USER->open_subdepartment != ""){
          $subdepartmentlike = "'%,$USER->open_subdepartment,%'";
        }else{
          $subdepartmentlike = "''";
        }
        $fparams[]= " 1 = CASE WHEN c.subdepartment!='-1'
          THEN 
            CASE WHEN CONCAT(',',c.subdepartment,',') LIKE {$subdepartmentlike}
            THEN 1
            ELSE 0 END
          ELSE 1 END ";
        // if(!empty($USER->open_hrmsrole) && $USER->open_hrmsrole != ""){
        //   $hrmsrolelike = "'%,$USER->open_hrmsrole,%'";
        // }else{
        //   $hrmsrolelike = "''";
        // }
          // $fparams[]= " 1 = CASE WHEN c.open_hrmsrole IS NOT NULL
          // THEN 
          //   CASE WHEN CONCAT(',',c.open_hrmsrole,',') LIKE {$hrmsrolelike}
          //   THEN 1
          //   ELSE 0 END
          // ELSE 1 END ";
        if(!empty($USER->open_designation) && $USER->open_designation != ""){
          $designationlike = "'%,$USER->open_designation,%'";
        }else{
          $designationlike = "''";
        }
          $fparams[]= " 1 = CASE WHEN c.open_designation IS NOT NULL
            THEN 
              CASE WHEN CONCAT(',',c.open_designation,',') LIKE {$designationlike}
                THEN 1
                ELSE 0 END
            ELSE 1 END  ";
        if(!empty($USER->open_location) && $USER->open_location != ""){
          $citylike = "'%,$USER->open_location,%'";
        }else{
          $citylike = "''";
        }
        $fparams[]= " 1 = CASE WHEN c.open_location IS NOT NULL
          THEN 
            CASE WHEN CONCAT(',',c.open_location,',') LIKE {$citylike}
              THEN 1
              ELSE 0 END
          ELSE 1 END  ";

        if(!empty($USER->open_grade) && $USER->open_grade != ""){
          $gradelike = "'%,$USER->open_grade,%'";
        }else{
          $gradelike = "''";
        }
        $fparams[]= " 1 = CASE WHEN c.open_grade IS NOT NULL
          THEN 
            CASE WHEN CONCAT(',',c.open_grade,',') LIKE {$gradelike}
              THEN 1
              ELSE 0 END
          ELSE 1 END  ";

        if(!empty($params)){
          $finalparams=implode('AND',$fparams);
        }else{
          $finalparams= '1=1' ;
        }

        $sql .= " AND ($finalparams OR (c.open_hrmsrole IS NULL AND c.open_designation IS NULL AND c.open_location IS NULL AND c.open_grade IS NULL AND c.open_group IS NULL AND c.department='-1' ) )  ";
    }
    return compact('sql', 'params'); 
}

/**
* todo sql query departmentwise
* @param  $systemcontext object 
* @return array
**/

function get_learningplan_details($lpid) {
    global $USER, $DB, $PAGE;
    $context = context_system::instance();
    $PAGE->requires->js_call_amd('local_learningplan/learningplan','load', array());
    $PAGE->requires->js_call_amd('local_request/requestconfirm','load', array());
    $details = array();
    $time = date('d-m-Y');
    $joinsql = '';
    if(is_siteadmin() OR has_capability('local/costcenter:manage_ownorganization',$context) OR 
        has_capability('local/costcenter:manage_owndepartments',$context) OR has_capability('local/learningplan:manage', $context)) {

        $selectsql = "select c.*  ";
        $fromsql = " from  {local_learningplan} c ";
        if ($DB->get_manager()->table_exists('local_rating')) {
            $selectsql .= " , AVG(rating) as avg ";
            $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_learningplan' ";
        }
        $wheresql = " where c.id = ? ";

        $adminrecord = $DB->get_record_sql($selectsql.$fromsql.$joinsql.$wheresql, [$lpid]);
        $details['manage'] = 1;
        $completedcount = $DB->count_records_sql("select count(cu.id) from {local_learningplan_user} cu, {user} u where u.id = cu.userid AND u.deleted = 0 AND u.suspended = 0 AND cu.planid=? AND cu.status=?", array($lpid, 1));
        $enrolledcount = $DB->count_records_sql("select count(cu.id) from {local_learningplan_user} cu, {user} u where u.id = cu.userid AND u.deleted = 0 AND u.suspended = 0 AND cu.planid=? ", array($lpid));
        $sessioncount = $DB->count_records_sql("select count(cu.id) from {local_learningplan_courses} cu, {local_learningplan} c where c.id = cu.planid AND cu.planid=? ", array($lpid));
        $details['completed'] = $completedcount;
        $details['enrolled'] = $enrolledcount;
        $details['noofsessions'] = $sessioncount;
    } else {
        $selectsql = "select cu.*, c.id as cid, c.startdate, c.enddate ";

        $fromsql = "from {local_learningplan_user} cu 
        JOIN {local_learningplan} c ON c.id = cu.planid ";
        if ($DB->get_manager()->table_exists('local_rating')) {
            $selectsql .= " , AVG(rating) as avg ";
            $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_learningplan' ";
        }
        $wheresql = " where 1 = 1 AND cu.userid = ? AND c.id = ? ";

        $record = $DB->get_record_sql($selectsql.$fromsql.$joinsql.$wheresql, [$USER->id, $lpid], IGNORE_MULTIPLE);
        
        $sessioncount = $DB->count_records_sql("select count(cu.id) from {local_learningplan_courses} cu, {local_learningplan} c where c.id = cu.planid AND cu.planid=? ", array($lpid));
        $details['manage'] = 0;
        $details['status'] = ($record->status == 1) ? get_string('learningplancompleted', 'local_learningplan'):get_string('learningplanpending', 'local_learningplan');

        $classsql = "select c.* from {local_learningplan} c where c.id = ?";
        $lpinfo = $DB->get_record_sql($classsql, [$lpid]);
        
        if (!empty($record)) {
            if ($lpinfo->approvalreqd == 0) {
                $enrollmentbtn = '<a href="javascript:void(0);" class="cat_btn" alt = ' . get_string('enroll','local_learningplan'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_learningplan/courseenrol\').enrolUser({ planid: '.$lpinfo->id.', userid:'.$USER->id.'}) })(event)" ><button class="cat_btn viewmore_btn"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('enroll','local_classroom').'</button></a>';
            } else {
                $enrollmentbtn = '<a href="javascript:void(0);" class="cat_btn" alt = ' . get_string('requestforenroll','local_classroom'). ' title = ' .get_string('enroll','local_classroom'). ' onclick="(function(e){ require(\'local_request/requestconfirm\').init({action:\'add\', componentid: '.$lpinfo->id.', component:\'learningplan\',componentname:\''.$lpinfo->name.'\'}) })(event)" ><button class="cat_btn viewmore_btn"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('requestforenroll','local_classroom').'</button></a>';
            }
        } else {
            $enrollmentbtn ='-';
        }
        $details['noofsessions'] = $sessioncount;
        $details['enrolled'] = ($record->timecreated) ? date('d-m-Y', $record->timecreated): $enrollmentbtn;
        $details['completed'] = ($record->completiondate) ? date('d-m-Y', $record->completiondate): '-';
    }
    return $details;
}
function check_learningplanenrol_pluginstatus($value){
 global $DB ,$OUTPUT ,$CFG;
$enabled_plugins = $DB->get_field('config', 'value', array('name' => 'enrol_plugins_enabled'));
$enabled_plugins =  explode(',',$enabled_plugins);
$enabled_plugins = in_array('learningplan',$enabled_plugins);

if(!$enabled_plugins){

    if(is_siteadmin()){
        $url = $CFG->wwwroot.'/admin/settings.php?section=manageenrols';
        $enable = get_string('enableplugin','local_learningplan',$url);
        echo $OUTPUT->notification($enable,'notifyerror');
    }
    else{
        $enable = get_string('manageplugincapability','local_learningplan');
        echo $OUTPUT->notification($enable,'notifyerror');
     }
   }    
}

function local_learningplan_output_fragment_learningplan_unenrol($args){
    global $PAGE;
    $args = (object) $args;
    $userid = $args->userid;
    $pathid = $args->pathid;
    $renderer = $PAGE->get_renderer('local_learningplan');	
	ob_start();
	$o = $renderer->unenrol_confirm($userid,$pathid );
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

/** 
 * 
 */
function get_unenrolled_lpaths_list(){
    global $DB; 
    $sql = 'SELECT MAX(lpu.id) as id,lp.id as lpid,MAX(from_unixtime(lpu.time )) as time, lpu.userid ,
             CONCAT(u.firstname,u.lastname) as username,lp.name as name, lpu.unenrol_reason ,u.email
            FROM {local_lp_unenrolments_log} As lpu 
            JOIN {user} AS u ON u.id = lpu.userid 
            JOIN {local_learningplan} AS lp ON lp.id = lpu.lpathid 
            GROUP BY lpu.id,lp.id,lp.name,lpu.unenrol_reason,lpu.userid,u.email 
            ORDER BY lpu.id desc';

    $unenroldata = $DB->get_records_sql($sql); 

    return $unenroldata;
}

function local_learningplan_search_page_js(){
    global $PAGE;
    $PAGE->requires->js_call_amd('local_learningplan/courseenrol','load');
}
function local_learningplan_search_page_filter_element(&$filterelements){
    global $CFG;
    if(file_exists($CFG->dirroot.'/local/search/lib.php')){
        require_once($CFG->dirroot.'/local/search/lib.php');
        $filterelements['learningpath'] = ['tagitemid' => 'moduletype_learningplan', 'tagitemname' => 'LP', 'tagitemshortname' => 'learningplan', 'coursecount' => local_search_get_coursecount_for_modules(['moduletype_learningplan'])];
    }
}
function local_learningplan_enabled_search(){
    return ['pluginname' => 'local_learningplan', 'templatename' => 'local_learningplan/searchpagecontent', 'type' => learningplan];
}
function  local_learningplan_applicable_filters_for_search_page(&$filterapplicable){
    $filterapplicable[learningplan] = [/*'learningtype',*/ 'status', /*'categories', 'level', 'skill'*/];
}
