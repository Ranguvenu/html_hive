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
 * @subpackage local_courses
 */


require_once('../../config.php');
require_once($CFG->dirroot . '/local/courses/filters_form.php');

$id        = optional_param('id', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$jsonparam    = optional_param('jsonparam', '', PARAM_RAW);

require_login();

$systemcontext = context_system::instance();
if(!has_capability('local/courses:view', $systemcontext) && !has_capability('local/courses:manage', $systemcontext) ){
    print_error("You don't have permissions to view this page.");
}
//$PAGE->set_pagelayout('admin');

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/courses/courses.php');
$PAGE->set_title(get_string('courses'));
$PAGE->set_heading(get_string('manage_courses','local_courses'));
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'load');
$PAGE->requires->js_call_amd('local_courses/featuredCourseform', 'load');
$PAGE->requires->js_call_amd('local_courses/createCoursetype', 'load');
$PAGE->requires->js_call_amd('local_courses/createCourseProviders', 'load');

$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js_call_amd('local_costcenter/fragment', 'init', array());
$PAGE->requires->js_call_amd('local_courses/courses', 'load', array());
$PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'getskills', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('manage_courses','local_courses'));

if($deleteid && $confirm && confirm_sesskey()){
    $course=$DB->get_record('course',array('id'=>$deleteid));
    delete_course($course, false);
    if($course){
        $custom_delete = new local_courses\action\delete();
        $delete = $custom_delete->delete_coursedetails($deleteid);
     }

    $course_detail = new stdClass();
    $sql = $DB->get_field('user','firstname', array('id' =>$USER->id));
    $course_detail->userid = $sql;
    $course_detail->courseid = $deleteid;
    $description = get_string('descptn','local_courses',$course_detail);
    $logs = new local_courses\action\insert();
    $insert_logs = $logs->local_custom_logs('delete', 'course', $description, $deleteid);
    redirect($CFG->wwwroot . '/local/courses/courses.php'); 
}
$renderer = $PAGE->get_renderer('local_courses');

$extended_menu_links = '';  
$extended_menu_links = '<div class="course_contextmenu_extended">
            <ul class="course_extended_menu_list">';


if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
    $extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
                            <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "Request" href = '.$CFG->wwwroot.'/local/request/index.php?component=elearning>
                                <i class="icon fa fa-share-square" aria-hidden="true"></i>
                            </a>
                        </div></li>';
} 
if(((has_capability('local/costcenter:create', $systemcontext)&&has_capability('local/courses:bulkupload', $systemcontext)&&has_capability('local/courses:manage', $systemcontext)&&has_capability('moodle/course:create', $systemcontext)&&has_capability('moodle/course:update', $systemcontext)))|| is_siteadmin()){

    $extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
                                <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('uploaduploadtime','local_courses').'" href = '.$CFG->wwwroot.'/local/courses/timeupload/timeupload_form.php>
                                    <i class="icon fa fa-calendar" aria-hidden="true"></i>
                                </a>
                            </div></li>';
}
if(((has_capability('local/costcenter:create', $systemcontext)&&has_capability('local/courses:bulkupload', $systemcontext)&&has_capability('local/courses:manage', $systemcontext)&&has_capability('moodle/course:create', $systemcontext)&&has_capability('moodle/course:update', $systemcontext)))|| is_siteadmin()){

    $extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
                                <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('uploaduploadcredits','local_courses').'" href = '.$CFG->wwwroot.'/local/courses/creditsupload/credits_form.php>
                                    <i class="icon fa fa-language Cfaicon" aria-hidden="true"></i>
                                </a>
                            </div></li>';
}              
/*if(((has_capability('local/costcenter:create', $systemcontext)&&has_capability('local/courses:bulkupload', $systemcontext)&&has_capability('local/courses:manage', $systemcontext)&&has_capability('moodle/course:create', $systemcontext)&&has_capability('moodle/course:update', $systemcontext)))|| is_siteadmin()){

    $extended_menu_links .= '<li><div class="courseedit course_extended_menu_itemcontainer">
                                <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('uploadcourses','local_courses').'" href = '.$CFG->wwwroot.'/local/courses/upload/index.php>
                                    <i class="icon fa fa-upload" aria-hidden="true"></i>
                                </a>
                            </div></li>';
}*/
if(is_siteadmin() ||(has_capability('moodle/course:create', $systemcontext)&& has_capability('moodle/course:update', $systemcontext)&&has_capability('local/courses:manage', $systemcontext))){
        $featured_courses = $DB->get_record('local_featured_courses', array(), $fields = 'id,count(*)', $strictness = IGNORE_MISSING);

        if ((!empty($featured_courses)) && $featured_courses->id > 0) {
            $featured_id = $featured_courses->id;
        } else {
            $featured_id = 0;
        }
    
        $coursetype_url = new moodle_url('/local/courses/coursestypes.php');
        $courseprov_url = new moodle_url('/local/courses/coursesproviders.php');
        $courseassign_url = new moodle_url('/local/courseassignment/assignments.php');
        $unenroluser_url    = new moodle_url('/local/courses/usersunenrolled.php');
        $levels_url    = new moodle_url('/local/courses/levels.php');
        $course_category_upload_url = new moodle_url('/local/courses/course_category_upload.php');
        $course_completion_upload_url = new moodle_url('/local/courses/course_completion_upload.php');

        $extended_menu_links .= '<li>
                                <div class="courseedit course_extended_menu_itemcontainer">
                                    <a href="' . $levels_url . '" id="extended_menu_createcourseprov" class="pull-right course_extended_menu_itemlink" title = "' . get_string('levels', 'local_courses') . '">
                                        <i class="icon fa fa-th-list"></i>
                                    </a>
                                </div>
                            </li>';

        $extended_menu_links .= '<li>
                                    <div class="courseedit course_extended_menu_itemcontainer">
                                        <a href="' . $unenroluser_url . '" id="extended_menu_createcourseprov" class="pull-right course_extended_menu_itemlink" title = "' . get_string('unenrolled_courses', 'local_courses') . '">
                                            <span class="createicon"><i class="icon fa fa-book"></i><i class="fa fa-close createiconchild" aria-hidden="true"></i></span>
                                        </a>
                                    </div>
                                </li>';
        $extended_menu_links .= '<li>
                                    <div class="courseedit course_extended_menu_itemcontainer">
                                        <a  href="'. $courseassign_url .'" title="' . get_string('page_title', 'local_courseassignment') . '" class="course_extended_menu_itemlink">
                                            <i class="icon fa fa-check-square-o" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="courseedit course_extended_menu_itemcontainer">
                                        <a href="' . $courseprov_url . '" id="extended_menu_createcourseprov" class="pull-right course_extended_menu_itemlink" title = "' . get_string('viewcourse_prov', 'local_courses') . '">
                                            <span class="createicon"><i class="icon fa fa-book"></i><i class="fa fa-hand-paper-o createiconchild" aria-hidden="true"></i></span>
                                        </a>
                                    </div>
                                </li>';

        $extended_menu_links .= '<li>
                                    <div class="courseedit course_extended_menu_itemcontainer">
                                        <a href="' . $coursetype_url . '" id="extended_menu_createcoursetype" class="pull-right course_extended_menu_itemlink" title = "' . get_string('viewcourse_type', 'local_courses') . '">
                                            <i class="icon fa fa-list-alt"></i>
                                        </a>
                                    </div>
                                </li>';

        $extended_menu_links .= '<li>
                                    <div class="courseedit course_extended_menu_itemcontainer">
                                        <a id="extended_menu_createfeaturedcourses" class="pull-right course_extended_menu_itemlink" title = "' . get_string('add_featuredcourse', 'local_courses') . '" data-action="featuredcoursemodal" onclick="(function(e){ require(\'local_courses/featuredCourseform\').init({contextid:' . $systemcontext->id . ', featured_id :' . $featured_id . ' }) })(event)">
                                            <i class="icon fa fa-star" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </li>';

if (is_siteadmin() || (has_capability('local/courses:uploadcourse_category', $systemcontext))){

        $extended_menu_links .= '<li>
                                <div class="courseedit course_extended_menu_itemcontainer">
                                    <a href="' . $course_completion_upload_url . '" id="extended_menu_createcourseprov" class="pull-right course_extended_menu_itemlink" title = "' . get_string('upload_course_completion', 'local_courses') . '">
                                        <i class="icon fa"><img src="pix/Course_completions.png" alt="" style="width:23px;"></i>
                                    </a>
                                </div>
                            </li>';

        $extended_menu_links .= '<li>
                                        <div class="courseedit course_extended_menu_itemcontainer">
                                            <a href="' . $course_category_upload_url . '" id="extended_menu_createcourseprov" class="pull-right course_extended_menu_itemlink" title = "' . get_string('upload_course_category', 'local_courses') . '">
                                                <i class="icon fa"><img src="pix/Course_category.png" alt="" style="width:23px;"></i>
                                            </a>
                                        </div>
                                    </li>';
                                    
}

        $uploadurl = new moodle_url('/local/courses/upload/index.php',array());
       
        $extended_menu_links .= '<li>
                                <div class="courseedit course_extended_menu_itemcontainer">
                                    <a class="pull-right course_extended_menu_itemlink" title = "'.get_string('uploadcourses','local_courses').'" href="'.$uploadurl.'">
                                        <i class="icon fa fa-upload" aria-hidden="true"></i>
                                    </a>
                                </div>
                                </li>
                                <li><div class="courseedit course_extended_menu_itemcontainer">
                                    <a id="extended_menu_createcourses" class="pull-right course_extended_menu_itemlink" title = "'.get_string('create_newcourse','local_courses').'" data-action="createcoursemodal" onclick="(function(e){ require(\'local_courses/courseAjaxform\').init({contextid:'.$systemcontext->id.', component:\'local_courses\', callback:\'custom_course_form\', form_status:0, plugintype: \'local\', pluginname: \'courses\'}) })(event)">
                                        <span class="createicon"><i class="icon fa fa-book"></i><i class="fa fa-plus createiconchild" aria-hidden="true"></i></span>
                                    </a>
                                </div></li>';
}
    
$extended_menu_links .= '
        </ul>
    </div>';

echo $OUTPUT->header();
echo $extended_menu_links;

$filterparams = $renderer->get_catalog_courses(true);

if (is_siteadmin()) {
    $thisfilters = array('courses', 'organizations', 'categories', 'departments', 'subdepartment', 'status', 'type', 'coursetype' ,'courseproviders');
} else if (has_capability('local/costcenter:manage_ownorganization', $systemcontext)) {
    $thisfilters = array('courses', 'categories', 'departments', 'subdepartment', 'status', 'type' , 'coursetype' ,'courseproviders');
} else if (has_capability('local/costcenter:manage_owndepartments', $systemcontext)) {
    $thisfilters = array('subdepartment', 'courses', 'categories', 'status', 'type' , 'coursetype' ,'courseproviders');
} else {
    $thisfilters = array('courses', 'categories', 'status', 'type', 'coursetype' ,'courseproviders');
}

$mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams));
     
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/courses/courses.php');
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

echo '<a class="btn-link btn-sm" href="javascript:void(0);" data-toggle="collapse" data-target="#local_courses-filter_collapse" aria-expanded="false" aria-controls="local_courses-filter_collapse">
        <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
      </a>';
echo  '<div class="collapse '.$show.'" id="local_courses-filter_collapse">
            <div id="filters_form" class="card card-body p-2">';
                $mform->display();
echo        '</div>
        </div>';
$filterparams['submitid'] = 'form#filteringform';
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);
echo $renderer->get_catalog_courses();

echo $OUTPUT->footer();
