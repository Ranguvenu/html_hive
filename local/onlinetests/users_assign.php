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
 * @subpackage local_onlinetest
 */

define('NO_OUTPUT_BUFFERING', true);
global $DB, $OUTPUT,$USER,$CFG,$PAGE;
require_once(dirname(__FILE__) . '/../../config.php');
use core_component;
$pagenavurl = new moodle_url('/local/onlinetests/index.php');
require_once($CFG->dirroot.'/local/onlinetests/lib.php');
$corecomponent = new core_component();
$onlinetestid=optional_param('id',0, PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);
$submitvalue = optional_param('submitvalue','', PARAM_RAW);
$add = optional_param('add',array(), PARAM_RAW);
$remove=optional_param('remove',array(), PARAM_RAW);
$view=optional_param('view','page', PARAM_RAW);
$type=optional_param('type','', PARAM_RAW);
$lastitem=optional_param('lastitem',0, PARAM_INT);

$sesskey=sesskey();
require_login();

$context = context_system::instance();

$url = new moodle_url('/local/onlinetests/users_assign.php', array('id' => $onlinetestid));
if (!has_capability('local/onlinetests:enroll_users', $context)) {
	print_error("You don't have permission to view this page.");
}
$onlinetest = $DB->get_record('local_onlinetests', array('id'=>$onlinetestid));
if (empty($onlinetest)) {
   print_error("Online exam not found");
} elseif (!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))) {
	// $user_detail = $DB->get_record('user', array('id'=>$USER->id));
	// if ( !$DB->record_exists_sql(" Select id from {user} where id=? AND  open_costcenterid IN (?) ", [$USER->id, $onlinetest->costcenterid]) ) {
	// 	print_error("You don't have permission to view this page.");
	// }
  if ($onlinetest->costcenterid != $USER->open_costcenterid) {
        print_error("You don't have permission to view this page.");
    }else if(!has_capability('local/costcenter:manage_ownorganization', $context) && $onlinetest->departmentid != $USER->open_departmentid){
        print_error("You don't have permission to view this page.");
  }
}
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
if(!$add&&!$remove){
	$PAGE->set_heading($onlinetest->name ." :". get_string("add_remove_users", 'local_onlinetests'));
}
$PAGE->set_title($onlinetest->name ." :". get_string("add_remove_users", 'local_onlinetests'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string("onlinetests", 'local_onlinetests'), new moodle_url($pagenavurl));
$PAGE->navbar->add($onlinetest->name,new moodle_url('mass_enroll.php',array('id'=>$onlinetestid)));
$PAGE->navbar->add(get_string("enrolusers", 'local_classroom'));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js('/local/classroom/js/jquery.bootstrap-duallistbox.js',true);
$PAGE->requires->css('/local/classroom/css/bootstrap-duallistbox.css');
if($view == 'ajax'){
  $options =(array)json_decode($_GET["options"],false);
  $selectfromusers=onlinetest_enrolled_users($type,$onlinetestid,$options,false,$offset1=-1,$perpage=50,$lastitem);
	echo json_encode($selectfromusers);
	exit;
}


$systemcontext   = context_system::instance();
echo $OUTPUT->header();
$time = time();
if ($onlinetest->timeclose AND ($time >= $onlinetest->timeclose) AND !$add AND !$remove ) {
  echo html_writer::tag('div', get_string("warning_enrol", 'local_onlinetests'), array('class' => 'w-100 pull-left text-center alert alert-danger bold'));
}
//echo $OUTPUT->heading(get_string("add_remove_users", 'local_onlinetests'));
if ($onlinetest) {
  $organization = null;
  $department   = null;
  $email        = null;
  $idnumber     = null;
  $uname        = null;
  $groups        = null;
  if(file_exists($CFG->dirroot.'/local/lib.php')){
      require_once($CFG->dirroot.'/local/lib.php');
      $filterlist = get_filterslist();
  }
  $filterparams = array('options'=>null, 'dataoptions'=>null);
  $pluginexists = $corecomponent::get_plugin_directory('local', 'onlinetests');
  if(!empty($pluginexists)) {
      require_once($CFG->dirroot . '/local/courses/filters_form.php');
      $mform = new filters_form($url, array('filterlist'=>$filterlist,'enrolid'=>0, 'courseid'=>$onlinetestid,'filterparams' => $filterparams, 'action' => 'user_enrolment'));
      $show = '';
      if ($mform->is_cancelled()) {
        redirect($PAGE->url);
      } else {
        $filterdata =  $mform->get_data();
        if($filterdata){
            $collapse = false;
            $show = 'show';
        } else{
            $collapse = true;
            $show = '';
        }
        $organization = !empty($filterdata->organizations) ? implode(',', $filterdata->organizations) : null;
        $department = !empty($filterdata->departments) ? implode(',', $filterdata->departments) : null;
        $email = !empty($filterdata->email) ? implode(',', $filterdata->email) : null;
        $idnumber = !empty($filterdata->idnumber) ? implode(',', $filterdata->idnumber) : null;
        $uname = !empty($filterdata->users) ? implode(',', $filterdata->users) : null;
        $groups = !empty($filterdata->groups) ? implode(',', $filterdata->groups) : null;
      }

    // Create the user selector objects.
    $options = array('context' => $context->id, 'onlinetestid' => $onlinetestid, 'organization' => $organization, 'department' => $department, 'email' => $email, 'idnumber' => $idnumber, 'uname' => $uname, 'groups' => $groups);
    
    if ( $add AND confirm_sesskey()) {
        
        if($submitvalue == "Add_All_Users"){
			     $options =json_decode($_REQUEST["options"],false);
              $userstoassign=array_flip(onlinetest_enrolled_users('add', $onlinetestid, (array)$options, false, $offset1=-1, $perpage=-1));
        }else{
            $userstoassign =$add;
        }
     
        if (!empty($userstoassign)) {
    			$progress = 0;
    			$progressbar = new \core\progress\display_if_slow(get_string('enrollusers', 'local_onlinetests',$onlinetest->name));
    			$progressbar->start_html();
    			$progressbar->start_progress('',count($userstoassign)-1);
  						
    			$submitted = new stdClass();
    			$submitted->timemodified = time();
    			$submitted->timecreated = time();
    			$type = 'onlinetest_enrollment';
    			$dataobj = $onlinetestid;
    			$fromuserid = $USER->id;
          $notification = new \local_onlinetests\notification();
          foreach($userstoassign as $key=>$add_user){
    			  $progressbar->progress($progress);
    			  $progress++;
    			  $submitted->userid = $add_user;
    			  $submitted->onlinetestid = $onlinetestid;
    			  $submitted->creatorid = $USER->id;
    			  $submitted->status = 0;
    			  $quizid = $DB->get_field('local_onlinetests','quizid',array('id'=>$onlinetestid));
    			  $submitted->quizid = $quizid;
    			  $exist = $DB->record_exists('local_onlinetest_users',array('userid'=>$add_user,'onlinetestid'=>$onlinetestid));
    			  
    			  if(empty($exist)){
      				$insert = $DB->insert_record('local_onlinetest_users',$submitted);
              // Trigger onlinetest enrolled event.
              $params = array(
                  'context' => $systemcontext,
                  'relateduserid' => $add_user,
                  'objectid' => $onlinetest->id
              );
              $event = \local_onlinetests\event\onlinetest_enrolled::create($params);
              $event->add_record_snapshot('local_onlinetests', $onlinetest);
              $event->trigger();
              // db transaction for email notification
              $touser = \core_user::get_user($add_user);
              $logmail = $notification->onlinetest_notification($type, $touser, $USER, $onlinetest);
    			  }
    			}
          $progressbar->end_html();
          $result=new stdClass();
          $result->changecount=$progress;
          $result->onlinetest=$onlinetest->name; 

          echo $OUTPUT->notification(get_string('enrolluserssuccess', 'local_onlinetests',$result),'success');
          $button = new single_button($url, get_string('click_continue','local_onlinetests'), 'get', true);
          $button->class = 'continuebutton';
          echo $OUTPUT->render($button);
          echo $OUTPUT->footer();
          die();
      }
    }
    if ( $remove&& confirm_sesskey()) {
        
        if($submitvalue=="Remove_All_Users"){
			$options =json_decode($_REQUEST["options"],false);
             $userstounassign = array_flip(onlinetest_enrolled_users('remove',$onlinetestid,(array)$options,false,$offset1=-1,$perpage=-1));
        }else{
            $userstounassign = $remove;
        }
        if (!empty($userstounassign)) {
			$progress = 0;
			$progressbar = new \core\progress\display_if_slow(get_string('un_enrollusers', 'local_onlinetests',$onlinetest->name));
			$progressbar->start_html();
			$progressbar->start_progress('', count($userstounassign)-1);
			foreach($userstounassign as $key=>$remove_user){
				$progressbar->progress($progress);
				$progress++;
				$getrecord = $DB->get_record('local_onlinetest_users',array('onlinetestid'=>$onlinetestid,'userid'=>$remove_user));
				$deleterecord = $DB->delete_records('local_onlinetest_users',array('id'=>$getrecord->id));
        // Trigger onlinetest unenrolled event.
        $params = array(
            'context' => $systemcontext,
            'relateduserid' => $remove_user,
            'objectid' => $onlinetest->id
        );
        $event = \local_onlinetests\event\onlinetest_unenrolled::create($params);
        $event->add_record_snapshot('local_onlinetests', $onlinetest);
        $event->trigger();


        //Unenrollment Notification//
        $notification = new \local_onlinetests\notification();
        $touser = \core_user::get_user($remove_user);
        $noti_type="onlinetest_unenrollment";
        $logmail = $notification->onlinetest_notification($noti_type, $touser, $USER, $onlinetest);

			}
			$progressbar->end_html();
			$result=new stdClass();
			$result->changecount=$progress;
			$result->onlinetest=$onlinetest->name; 
			
			echo $OUTPUT->notification(get_string('unenrolluserssuccess', 'local_onlinetests',$result),'success');
			$button = new single_button($PAGE->url, get_string('click_continue','local_onlinetests'), 'get', true);
			$button->class = 'continuebutton';
			echo $OUTPUT->render($button);
      echo $OUTPUT->footer();
			die();
        }
        //redirect($url);
    }
   
    $selecttousers = onlinetest_enrolled_users('add', $onlinetestid, $options, false, $offset=-1, $perpage=50);
    $selecttousers_total = onlinetest_enrolled_users('add', $onlinetestid, $options, true, $offset1=-1, $perpage=-1);
 
    $selectfromusers = onlinetest_enrolled_users('remove', $onlinetestid, $options, false, $offset1=-1, $perpage=50);
    $selectfromuserstotal = onlinetest_enrolled_users('remove', $onlinetestid, $options, true, $offset1=-1, $perpage=-1);

    $selectallenrolledusers='&nbsp&nbsp<button type="button" id="select_add" name="select_all" value="Select All" title="Select All"/ class="btn btn-default">'.get_string('select_all', 'local_onlinetests').'</button>';
    $selectallenrolledusers.='&nbsp&nbsp<button type="button" id="add_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>'.get_string('remove_all', 'local_onlinetests').'</button>';
    
    $selectallnotenrolledusers='&nbsp&nbsp<button type="button" id="select_remove" name="select_all" value="Select All" title="Select All" class="btn btn-default"/>'.get_string('select_all', 'local_onlinetests').'</button>';
    $selectallnotenrolledusers.='&nbsp&nbsp<button type="button" id="remove_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>'.get_string('remove_all', 'local_onlinetests').'</button>';
    
    
   $content='<div class="bootstrap-duallistbox-container">
           ';
		   $encodedoptions = json_encode($options);
   $content.='<form  method="post" name="form_name" id="user_assign" class="form_class" ><div class="box2 col-md-5 col-12 pull-left">
    <input type="hidden" name="id" value="'.$onlinetestid.'"/>
    <input type="hidden" name="sesskey" value="'.sesskey().'"/>
	<input type="hidden" name="options"  value='.$encodedoptions.' />
   <label>'.get_string('enrolled_users', 'local_onlinetests',$selectfromuserstotal).$selectallnotenrolledusers.'</label>';
   $content.='<select multiple="multiple" name="remove[]" id="bootstrap-duallistbox-selected-list_duallistbox_onlinetests_users" class="dual_select">';
   foreach($selectfromusers as $key=>$selectfromuser){
          $content.="<option value='$key'>$selectfromuser</option>";
    }

   $content.='</select>';
   $content.='</div><div class="box3 col-md-2 col-12 pull-left actions"><button type="submit" class="custom_btn btn remove btn-default" disabled="disabled" title="Remove Selected Users" name="submitvalue" value="Remove Selected Users" id="user_unassign_all"/>
       '.get_string('remove_selected_users', 'local_onlinetests').'
        </button></form>';
   $content.='<form  method="post" name="form_name" id="user_un_assign" class="form_class" ><button type="submit" class="custom_btn btn move btn-default" disabled="disabled" title="Add Selected Users" name="submitvalue" value="Add Selected Users" id="user_assign_all" />
       '.get_string('add_selected_users', 'local_onlinetests').'
        </button></div><div class="box1 col-md-5 col-12 pull-left">
    <input type="hidden" name="id" value="'.$onlinetestid.'"/>
    <input type="hidden" name="sesskey" value="'.sesskey().'"/>
	<input type="hidden" name="options"  value='.$encodedoptions.' />
   <label> '.get_string('availablelist', 'local_onlinetests',$selecttousers_total).$selectallenrolledusers.'</label>';
    $content.='<select multiple="multiple" name="add[]" id="bootstrap-duallistbox-nonselected-list_duallistbox_onlinetests_users" class="dual_select">';
    foreach($selecttousers as $key=>$select_to_user){
          $content.="<option value='$key'>$select_to_user</option>";
    }
    $content.='</select>';
    $content.='</div></form>';
    $content.='</div>';
}
$collapse = true;
// $show = '';

// print_collapsible_region_start(' ', 'filters_form', ' '.' '.get_string('filters'), false, $collapse);
// $mform->display();
// print_collapsible_region_end();
    echo '<a class="btn-link btn-sm" title="Filter" href="javascript:void(0);" data-toggle="collapse" data-target="#local_onlinetestsenroll-filter_collapse" aria-expanded="false" aria-controls="local_onlinetestsenroll-filter_collapse">
            <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
          </a>';
    echo  '<div class="collapse '.$show.'" id="local_onlinetestsenroll-filter_collapse">
                <div id="filters_form" class="card card-body p-2">';
                    $mform->display();
    echo        '</div>
            </div>';

}
if ($onlinetest) {
  $select_div='<div class="row d-block">
                <div class="w-100 pull-left">'.$content.'</div>
              </div>';
  echo $select_div;
  $myJSON = json_encode($options);
  echo "<script language='javascript'>

  $( document ).ready(function() {
	
	$('#select_remove').click(function() {
        $('#bootstrap-duallistbox-selected-list_duallistbox_onlinetests_users option').prop('selected', true);
        $('.box3 .remove').prop('disabled', false);
        $('#user_unassign_all').val('Remove_All_Users');

        $('.box3 .move').prop('disabled', true);
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_onlinetests_users option').prop('selected', false);
        $('#user_assign_all').val('Add Selected Users');

    });
    $('#remove_select').click(function() {
        $('#bootstrap-duallistbox-selected-list_duallistbox_onlinetests_users option').prop('selected', false);
        $('.box3 .remove').prop('disabled', true);
        $('#user_unassign_all').val('Remove Selected Users');
    });
    $('#select_add').click(function() {
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_onlinetests_users option').prop('selected', true);
        $('.box3 .move').prop('disabled', false);
        $('#user_assign_all').val('Add_All_Users');

        $('.box3 .remove').prop('disabled', true);
        $('#bootstrap-duallistbox-selected-list_duallistbox_onlinetests_users option').prop('selected', false);
        $('#user_unassign_all').val('Remove Selected Users');
        
    });
    $('#add_select').click(function() {
       $('#bootstrap-duallistbox-nonselected-list_duallistbox_onlinetests_users option').prop('selected', false);
        $('.box3 .move').prop('disabled', true);
        $('#user_assign_all').val('Add Selected Users');
    });
    $('#bootstrap-duallistbox-selected-list_duallistbox_onlinetests_users').on('change', function() {
        if(this.value!=''){
            $('.box3 .remove').prop('disabled', false);
            $('.box3 .move').prop('disabled', true);
        }
    });
    $('#bootstrap-duallistbox-nonselected-list_duallistbox_onlinetests_users').on('change', function() {
        if(this.value!=''){
            $('.box3 .move').prop('disabled', false);
            $('.box3 .remove').prop('disabled', true);
        }
    });
    jQuery(
        function($)
        {
          $('.dual_select').bind('scroll', function()
            {
              if($(this).scrollTop() + $(this).innerHeight()>=$(this)[0].scrollHeight)
              {
                var get_id=$(this).attr('id');
                if(get_id=='bootstrap-duallistbox-selected-list_duallistbox_onlinetests_users'){
                    var type='remove';
                    var total_users=$selectfromuserstotal;
                }
                if(get_id=='bootstrap-duallistbox-nonselected-list_duallistbox_onlinetests_users'){
                    var type='add';
                    var total_users=$selecttousers_total;
                   
                }
                var count_selected_list=$('#'+get_id+' option').length;
               
                var lastValue = $('#'+get_id+' option:last-child').val();
             
              if(count_selected_list<total_users){  
                   //alert('end reached');
                    var selected_list_request = $.ajax({
                        method: 'GET',
                        url: M.cfg.wwwroot + '/local/onlinetests/users_assign.php?options=$myJSON',
                        data: {id:'$onlinetestid',sesskey:'$sesskey', type:type,view:'ajax',lastitem:lastValue},
                        dataType: 'html'
                    });  
                    var appending_selected_list = '';
                    selected_list_request.done(function(response){
                    //console.log(response);
                    response = jQuery.parseJSON(response);
                    //console.log(response);
                  
                    $.each(response, function (index, data) {
                   
                        appending_selected_list = appending_selected_list + '<option value=' + index + '>' + data + '</option>';
                    });
                    $('#'+get_id+'').append(appending_selected_list);
                    });
                }
              }
            })
        }
    );
 
  });
    </script>";
}
$backurl = new moodle_url('/local/onlinetests/index.php');
$continue='<div class="w-100 pull-left text-right mt-3">';
$continue.=$OUTPUT->single_button($backurl, get_string('continue'));
$continue.='</div>';
echo $continue;

echo $OUTPUT->footer();
