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
 * @subpackage local_learningplan
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/learningplan/filters_form.php');

global $DB, $USER, $CFG,$PAGE,$OUTPUT;
use core_component;
$course_enrol=optional_param('courseid', 0, PARAM_INT);
$userid=optional_param('userid', 0, PARAM_INT);
$planid=optional_param('planid', 0, PARAM_INT);

$PAGE->requires->jquery();
$PAGE->requires->css('/local/learningplan/css/jquery.dataTables.css');
$PAGE->requires->js('/local/learningplan/js/jquery.dataTables.min.js', true);
$core_component = new core_component();
$epsilon_plugin_exist = $core_component::get_plugin_directory('theme', 'epsilon');
if(!empty($epsilon_plugin_exist)){
    $PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
}

$return_url = new moodle_url('/local/learningplan/managelearningplan.php');
$systemcontext = context_system::instance();
$PAGE->requires->js_call_amd('local_learningplan/lpcreate', 'load', array());

//check the context level of the user and check whether the user is login to the system or not
$PAGE->set_context($systemcontext);
require_login();
$PAGE->set_url('/local/learningplan/index.php');
$PAGE->set_title(get_string('pluginname', 'local_learningplan'));
// $PAGE->set_pagelayout('admin');
//Header and the navigation bar
$PAGE->set_heading(get_string('pluginname', 'local_learningplan'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string("pluginname", 'local_learningplan'));
$learningplan_renderer = new local_learningplan\render\view();
$learningplan_lib = new local_learningplan\lib\lib();

echo $OUTPUT->header(); 
$enabled = check_learningplanenrol_pluginstatus($value);

if($course_enrol && $planid && $userid){

    $enrol=$learningplan_lib->to_enrol_users($planid,$userid,$course_enrol);
    if($enrol){
        $plan_url = new moodle_url('/course/view.php', array('id' => $course_enrol));
        redirect($plan_url);
    }
}
if(!is_siteadmin()){
    require_capability('local/learningplan:manage', $systemcontext);
}
$out = "<ul class='course_extended_menu_list learning_plan'>";

if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
    $unenrolusers_url    = new moodle_url('/local/learningplan/usersunenrolled.php');

    $out .= '<li>
                                <div class="courseedit course_extended_menu_itemcontainer">
                                    <a href="' . $unenrolusers_url . '" id="extended_menu" class="pull-right course_extended_menu_itemlink" title = "' . get_string('unenrolled_lpaths', 'local_learningplan') . '">
                                        <span class="createicon"><i class="icon fa fa-map"></i><i class="fa fa-close createiconchild" aria-hidden="true"></i></span>
                                    </a>
                                </div>
                            </li>';
    $out .= "<li>    
                    <div class = 'coursebackup course_extended_menu_itemcontainer'>
                        <a href='".$CFG->wwwroot."/local/request/index.php?component=learningplan' class='course_extended_menu_itemlink' title='Request'><i class='icon fa fa-share-square' aria-hidden='true'></i>
                        </a>
                    </div>
                </li>";
}
if (is_siteadmin() || (has_capability('local/learningplan:create', $systemcontext) && has_capability('local/learningplan:manage', $systemcontext))) {
    $titlestring = get_string('addnew_learningplans','local_learningplan');

    $out .= "<li>    
                <div class = 'coursebackup course_extended_menu_itemcontainer'>
                    <a class='course_extended_menu_itemlink' data-action='createlpmodal' title='$titlestring' onclick ='(function(e){ require(\"local_learningplan/lpcreate\").init({selector:\"createlpmodal\", contextid:$systemcontext->id, planid:0, form_status:0}) })(event)'><span class='createicon'><i class='icon fa fa-map' aria-hidden='true' aria-label=''></i><i class='fa fa-plus createiconchild' aria-hidden='true'></i></span>
                    </a>
                </div>
            </li>";
}

$out .= "</ul>";
echo $out;

    if(is_siteadmin()){
        $thisfilters = array('departments', 'level', 'groups');
    }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $thisfilters = array('departments', 'level', 'groups');
    }else if(has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $thisfilters = array('level', 'groups');
    }else {
        $thisfilters = array('level', 'groups');
    }

    $mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams));

    $filterdata = null;
    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/local/learningplan/index.php');
    } else{
        $filterdata =  $mform->get_data();
        if($filterdata){
            $collapse = false;
        } else{
            $collapse = true;
        }
    }
    if(empty($filterdata) && !empty($jsonparam)){
        $filterdata = json_decode($jsonparam);
        foreach($thisfilters AS $filter){
            if(empty($filterdata->$filter)){
                unset($filterdata->$filter);
            }
        }
        $mform->set_data($filterdata);
    }
    if($filterdata){
        $collapse = false;
        $show = 'show';
    } else{
        $collapse = true;
        $show = '';
    }

echo '<a class="btn-link btn-sm" href="javascript:void(0);" data-toggle="collapse" data-target="#local_learningplan-filter_collapse" aria-expanded="false" aria-controls="local_learningplan-filter_collapse">
        <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
      </a>';
echo  '<div class="collapse '.$show.'" id="local_learningplan-filter_collapse">
            <div id="filters_form" class="card card-body p-2">';
                $mform->display();
echo        '</div>
        </div>';

$condition="";
echo $learningplan_renderer->all_learningplans($condition,array(),false,null,$filterdata);
echo $OUTPUT->footer();
