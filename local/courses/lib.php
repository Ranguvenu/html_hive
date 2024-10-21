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


if(file_exists($CFG->dirroot.'/local/costcenter/lib.php')){
    require_once($CFG->dirroot.'/local/costcenter/lib.php');                  
}
require_once($CFG->dirroot . '/user/selector/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/completion/completion_completion.php');
require_once($CFG->dirroot.'/lib/formslib.php');
use \local_courses\form\custom_course_form as custom_course_form;


defined('MOODLE_INTERNAL') || die();
define('elearning', 1);
define('courses', 1);
/**
 * process the mass enrolment
 * @param csv_import_reader $cir  an import reader created by caller
 * @param Object $course  a course record from table mdl_course
 * @param Object $context  course context instance
 * @param Object $data    data from a moodleform
 * @return string  log of operations
 */
function mass_enroll($cir, $course, $context, $data) {
    global $CFG, $DB, $USER;
    require_once ($CFG->dirroot . '/group/lib.php');

    $result = '';

    $courseid=$course->id;
    $roleid = $data->roleassign;
    $useridfield = $data->firstcolumn;

    $enrollablecount = 0;
    $createdgroupscount = 0;
    $createdgroupingscount = 0;
    $createdgroups = '';
    $createdgroupings = '';


    $plugin = enrol_get_plugin('manual');
    //Moodle 2.x enrolment and role assignment are different
    // make sure couse DO have a manual enrolment plugin instance in that course
    //that we are going to use (only one instance is allowed @see enrol/manual/lib.php get_new_instance)
    // thus call to get_record is safe
    $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'manual'));
    if (empty($instance)) {
        // Only add an enrol instance to the course if non-existent
        $enrolid = $plugin->add_instance($course);
        $instance = $DB->get_record('enrol', array('id' => $enrolid));
    }


    // init csv import helper
    $notification = new \local_courses\notification();
    $type = 'course_enrol';
    $notificationdata = $notification->get_existing_notification($course, $type);
    
    $cir->init();

    while ($fields = $cir->next()) {
        $a = new stdClass();
        if (empty ($fields))
        continue;
        $coscenter=$DB->get_field('course','open_costcenterid',array('id'=>$course->id));
        $coscenter_name=$DB->get_field('local_costcenter','shortname',array('id'=>$coscenter));

        $string=strtolower($coscenter_name);

        // 1st column = id Moodle (idnumber,username or email)
        // get rid on eventual double quotes unfortunately not done by Moodle CSV importer
        /*****Checking with all costcenters*****/

        $fields[0]= str_replace('"', '', trim($fields[0]));
        $fieldcontcat=$string.$fields[0];
        /******The below code is for the AH checking condtion if AH any user can be enrolled else if OH only his costcenter users enrol*****/
        // $id=$DB->get_field('course','open_costcenterid',array('id'=>$course->id));
        $systemcontext = context_system::instance();
        // if(!is_siteadmin()  && has_capability('local/costcenter:assign_multiple_departments_manage', $systemcontext)){
        //     $sql=" ";
        // }else{
        //     $sql=" and u.open_costcenterid=$id ";
        // }

        /*First Condition To validate users*/
        $sql="SELECT u.* from {user} u where u.deleted=0 and u.suspended=0 and u.$useridfield='$fields[0]' ";
        //$sql
        if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext))){
            $sql .= " and u.open_costcenterid={$USER->open_costcenterid} ";
            if(!has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
                $sql .= " and u.open_departmentid={$USER->open_departmentid} ";
            }
            $sql .= " and u.id <> {$USER->id} ";
        }

        if (!$user = $DB->get_record_sql($sql)) {
            $result .= '<div class="alert alert-error">'.get_string('im:user_unknown', 'local_courses', $fields[0] ). '</div>';
            continue;
        }

        $id=$DB->get_field('course','open_costcenterid',array('id'=>$course->id));
        /** The below code is for the AH checking condtion if AH any user can be enrolled else if OH only his costcenter users enrol **/
        if(!is_siteadmin()  && has_capability('local/costcenter:assign_multiple_departments_manage', $systemcontext)){

            $sql=" ";
        }else{

            $sql=" open_costcenterid=$id AND ";
        }
        /*Second Condition To validate users*/
        if(!$DB->record_exists_sql("select id from {user} where $sql  id=$user->id")){

            $costcentername = $DB->get_field('local_costcenter','fullname',array('id'=>$course->costcenter));
            $cs_object = new stdClass();
            $cs_object->csname = $costcentername;
            $cs_object->user   = fullname($user);
            $result .= '<div class="alert alert-error">'.get_string('im:user_notcostcenter', 'local_courses',$cs_object ). '</div>';
            continue;
        }

        //already enroled ?

        $instance_auto = $DB->get_field('enrol', 'id',array('courseid' => $course->id, 'enrol' => 'auto'));
        $instance_self = $DB->get_field('enrol', 'id',array('courseid' => $course->id, 'enrol' => 'self'));

        if(!$instance_auto){
         $instance_auto=0;

        }
        if(!$instance_self){
            $instance_self=0;
        }

        $enrol_ids=/*$instance_auto.",".*/$instance_self.",".$instance->id;

        $sql="select id from {user_enrolments} where enrolid IN ($enrol_ids) and userid=$user->id";
        $enrolormnot=$DB->get_field_sql($sql);

        if (user_has_role_assignment($user->id, $roleid, $context->id)) {
            $result .= '<div class="alert alert-error">'.get_string('im:already_in', 'local_courses', fullname($user)). '</div>';

        } elseif($enrolormnot){
         $result .= '<div class="alert alert-error">'.get_string('im:already_in', 'local_courses', fullname($user)). '</div>';
         continue;
        }else {
            //TODO take care of timestart/timeend in course settings
            // done in rev 1.1
            $timestart=$DB->get_field('course','startdate',array('id'=>$course->id));
            $timeend=0;
            // not anymore so easy in Moodle 2.x
            // Enrol the user with this plugin instance (unfortunately return void, no more status )
            $plugin->enrol_user($instance, $user->id,$roleid,$timestart,$timeend);

            if($notificationdata){
                $notification->send_course_email($course, $user, $type, $notificationdata);
            }
            $result .= '<div class="alert alert-success">'.get_string('im:enrolled_ok', 'local_courses', fullname($user)).'</div>';
            $enrollablecount++;
        }

        $group = str_replace('"','',trim($fields[1]));
        // 2nd column ?
        if (empty ($group)) {
            $result .= "";
            continue; // no group for this one
        }

        // create group if needed
        if (!($gid = mass_enroll_group_exists($group, $courseid))) {
            if ($data->creategroups) {
                if (!($gid = mass_enroll_add_group($group, $courseid))) {
                    $a->group = $group;
                    $a->courseid = $courseid;
                    $result .= '<div class="alert alert-error">'.get_string('im:error_addg', 'local_courses', $a) . '</div>';
                    continue;
                }
                $createdgroupscount++;
                $createdgroups .= " $group";
            } else {
                $result .= '<div class="alert alert-error">'.get_string('im:error_g_unknown', 'local_courses', $group) . '</div>';
                continue;
            }
        }

        // if groupings are enabled on the site (should be ?)
        // if ($CFG->enablegroupings) { // not anymore in Moodle 2.x
        if (!($gpid = mass_enroll_grouping_exists($group, $courseid))) {
            if ($data->creategroupings) {
                if (!($gpid = mass_enroll_add_grouping($group, $courseid))) {
                    $a->group = $group;
                    $a->courseid = $courseid;
                    $result .= '<div class="alert alert-error">'.get_string('im:error_add_grp', 'local_courses', $a) . '</div>';
                    continue;
                }
                $createdgroupingscount++;
                $createdgroupings .= " $group";
            } else {
                // don't complains,
                // just do the enrolment to group
            }
        }
        // if grouping existed or has just been created
        if ($gpid && !(mass_enroll_group_in_grouping($gid, $gpid))) {
            if (!(mass_enroll_add_group_grouping($gid, $gpid))) {
                $a->group = $group;
                $result .= '<div class="alert alert-error">'.get_string('im:error_add_g_grp', 'local_courses', $a) . '</div>';
                continue;
            }
        }
        //}

        // finally add to group if needed
        if (!groups_is_member($gid, $user->id)) {
            $ok = groups_add_member($gid, $user->id);
            if ($ok) {
                $result .= '<div class="alert alert-success">'.get_string('im:and_added_g', 'local_courses', $group) . '</div>';
            } else {
                $result .= '<div class="alert alert-error">'.get_string('im:error_adding_u_g', 'local_courses', $group) . '</div>';
            }
        } else {
            $result .= '<div class="alert alert-notice">'.get_string('im:already_in_g', 'local_courses', $group) . '</div>';
        }

    }
    $result .= '<br />';
    //recap final
    $result .= get_string('im:stats_i', 'local_courses', $enrollablecount) . "";
    // $a->nb = $createdgroupscount;
    // if(!isset($createdgroups) || empty($createdgroups)||$createdgroups='')
    // $a->what = '-';
    // else
    // $a->what = $createdgroups;
    // $result .= get_string('im:stats_g', 'local_courses', $a) . "";
    // $a->nb = $createdgroupingscount;
    // if(!isset($createdgroupings) || empty($createdgroupings)||$createdgroupings='')
    // $a->what = '-';
    // else
    // $a->what = $createdgroupings;
    // $result .= get_string('im:stats_grp', 'local_courses', $a) . "";

    return $result;
}


/**
 * Enter description here ...
 * @param string $newgroupname
 * @param int $courseid
 * @return int id   Moodle id of inserted record
 */
function mass_enroll_add_group($newgroupname, $courseid) {
    $newgroup = new stdClass();
    $newgroup->name = $newgroupname;
    $newgroup->courseid = $courseid;
    $newgroup->lang = current_language();
    return groups_create_group($newgroup);
}


/**
 * Enter description here ...
 * @param string $newgroupingname
 * @param int $courseid
 * @return int id Moodle id of inserted record
 */
function mass_enroll_add_grouping($newgroupingname, $courseid) {
    $newgrouping = new StdClass();
    $newgrouping->name = $newgroupingname;
    $newgrouping->courseid = $courseid;
    return groups_create_grouping($newgrouping);
}

/**
 * @param string $name group name
 * @param int $courseid course
 * @return string or false
 */
function mass_enroll_group_exists($name, $courseid) {
    return groups_get_group_by_name($courseid, $name);
}

/**
 * @param string $name group name
 * @param int $courseid course
 * @return string or false
 */
function mass_enroll_grouping_exists($name, $courseid) {
    return groups_get_grouping_by_name($courseid, $name);

}

/**
 * @param int $gid group ID
 * @param int $gpid grouping ID
 * @return mixed a fieldset object containing the first matching record or false
 */
function mass_enroll_group_in_grouping($gid, $gpid) {
     global $DB;
    $sql =<<<EOF
   select * from {groupings_groups}
   where groupingid = ?
   and groupid = ?
EOF;
    $params = array($gpid, $gid);
    return $DB->get_record_sql($sql,$params,IGNORE_MISSING);
}

/**
 * @param int $gid group ID
 * @param int $gpid grouping ID
 * @return bool|int true or new id
 * @throws dml_exception A DML specific exception is thrown for any errors.
 */
function mass_enroll_add_group_grouping($gid, $gpid) {
     global $DB;
    $new = new stdClass();
    $new->groupid = $gid;
    $new->groupingid = $gpid;
    $new->timeadded = time();
    return $DB->insert_record('groupings_groups', $new);
}
/**
* todo displays the categories
* @param string $requiredcapability
* @param int $excludeid
* @param string $separator
* @param int $departmentcat
* @param int $orgcat
* @param array $args List of named arguments for the fragment loader.
* @return string
*/
function categorylist($requiredcapability = '', $excludeid = 0, $separator = ' / ',$departmentcat = 0,$orgcat=0) {
    global $DB, $USER;
    $coursecatcache = cache::make('core', 'coursecat');

    // Check if we cached the complete list of user-accessible category names ($baselist) or list of ids
    // with requried cap ($thislist).
    $currentlang = current_language();
    $basecachekey = $currentlang . '_catlist';
    $baselist = $coursecatcache->get($basecachekey);
    $thislist = false;
    $thiscachekey = null;
    if (!empty($requiredcapability)) {
        $requiredcapability = (array)$requiredcapability;
        $thiscachekey = 'catlist:'. serialize($requiredcapability);
        if ($baselist !== false && ($thislist = $coursecatcache->get($thiscachekey)) !== false) {
            $thislist = preg_split('|,|', $thislist, -1, PREG_SPLIT_NO_EMPTY);
        }
    } else if ($baselist !== false) {
        $thislist = array_keys($baselist);
    }

    if ($baselist === false) {
        // We don't have $baselist cached, retrieve it. Retrieve $thislist again in any case.
        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT cc.id, cc.sortorder, cc.name, cc.visible, cc.parent, cc.path, $ctxselect
                FROM {course_categories} cc
                JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = :contextcoursecat AND cc.visible = :value
                WHERE cc.depth<=2
                ORDER BY cc.sortorder";
        $rs = $DB->get_recordset_sql($sql, array('contextcoursecat' => CONTEXT_COURSECAT,'value' => 1));
        $baselist = array();
        $thislist = array();
        foreach ($rs as $record) {
            // If the category's parent is not visible to the user, it is not visible as well.
            if (!$record->parent || isset($baselist[$record->parent])) {
                context_helper::preload_from_record($record);
                $context = context_coursecat::instance($record->id);
                if (!$record->visible && !has_capability('moodle/category:viewhiddencategories', $context)) {
                    // No cap to view category, added to neither $baselist nor $thislist.
                    continue;
                }
                $baselist[$record->id] = array(
                    'name' => format_string($record->name, true, array('context' => $context)),
                    'path' => $record->path,
                );
                if (!empty($requiredcapability) && !has_all_capabilities($requiredcapability, $context)) {
                    // No required capability, added to $baselist but not to $thislist.
                    continue;
                }
                $thislist[] = $record->id;
            }
        }
        $rs->close();
        $coursecatcache->set($basecachekey, $baselist);
        if (!empty($requiredcapability)) {
            $coursecatcache->set($thiscachekey, join(',', $thislist));
        }
    } else if ($thislist === false) {
        // We have $baselist cached but not $thislist. Simplier query is used to retrieve.
        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT ctx.instanceid AS id, $ctxselect
                FROM {context} ctx WHERE ctx.contextlevel = :contextcoursecat ";
        $contexts = $DB->get_records_sql($sql, array('contextcoursecat' => CONTEXT_COURSECAT));
        $thislist = array();
        foreach (array_keys($baselist) as $id) {
            context_helper::preload_from_record($contexts[$id]);
            if (has_all_capabilities($requiredcapability, context_coursecat::instance($id))) {
                $thislist[] = $id;
            }
        }
        $coursecatcache->set($thiscachekey, join(',', $thislist));
    }

    // Now build the array of strings to return, mind $separator and $excludeid.
    $names = array();
    $category = $DB->get_field('local_costcenter', 'category' ,array('id' => $USER->open_costcenterid));
    foreach ($thislist as $id) {

        $path = preg_split('|/|', $baselist[$id]['path'], -1, PREG_SPLIT_NO_EMPTY);
        if($departmentcat){
            if($path[1] == $departmentcat){
                if (!$excludeid || !in_array($excludeid, $path)) {
                    $namechunks = array();
                    foreach ($path as $parentid) {
                        $namechunks[] = $baselist[$parentid]['name'];
                    }
                    $names[$id] = join($separator, $namechunks);
                }
            }
        }else if($orgcat){
            if($path[0] == $orgcat){
                if (!$excludeid || !in_array($excludeid, $path)) {
                    $namechunks = array();
                    foreach ($path as $parentid) {
                        $namechunks[] = $baselist[$parentid]['name'];
                    }
                    $names[$id] = join($separator, $namechunks);
                }
            }
        }
        else{
                if (!$excludeid || !in_array($excludeid, $path)) {
                    $namechunks = array();
                    foreach ($path as $parentid) {
                        $namechunks[] = $baselist[$parentid]['name'];
                    }
                    $names[$id] = join($separator, $namechunks);
                }
        }
    }
    return $names;
}

/**
 * Serve the new course form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_custom_course_form($args){
    global $DB,$CFG,$PAGE;
    $args = (object) $args;
    $context = $args->context;
    $renderer = $PAGE->get_renderer('local_courses');
    $courseid = $args->courseid;
    $o = '';
    if ($courseid) {
        $course = get_course($courseid);
        $course = course_get_format($course)->get_course();
        $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
        $coursecontext = context_course::instance($course->id);
        $course->hours = floor($course->duration / 3600);
        $course->min = (($course->duration / 60) ) % 60;
        if(!empty($course->open_certificateid)){
            $course->map_certificate = 1;
        }
        require_capability('moodle/course:update', $coursecontext);
    }else{
        $category = $CFG->defaultrequestcategory;
    }
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata =($args->jsonformdata);// json_decode
        parse_str($serialiseddata, $formdata);
    }
    if(!empty($course) && empty($formdata)){
        $formdata = (array)$course;
    }

    if ($courseid > 0) {
        $heading = get_string('updatecourse', 'local_courses');
        $collapse = false;
        $data = $DB->get_record('course', array('id'=>$courseid));
    }
    // Populate course tags.
    //$course->tags = local_tags_tag::get_item_tags_array('local_courses', 'courses', $course->id);
    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true,'autosave'=>false);
    $overviewfilesoptions = course_overviewfiles_options($course);
    if ($courseid) {
        // Add context for editor.
        $editoroptions['context'] = $coursecontext;
        $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
        $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
        if ($overviewfilesoptions) {
            file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
        }
        $get_coursedetails=$DB->get_record('course',array('id'=>$course->id));
    } else {
        // Editor should respect category context if course context is not set.
        $editoroptions['context'] = $catcontext;
        $editoroptions['subdirs'] = 0;
        $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
        if ($overviewfilesoptions) {
            file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
        }
    }

    $params = array(
        'course' => $course,
        'category' => $category,
        'editoroptions' => $editoroptions,
        'returnto' => $returnto,
        'get_coursedetails'=>$get_coursedetails,
        'form_status' => $args->form_status,
        'costcenterid' => $data->open_costcenterid
    );
    $mform = new custom_course_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    //$mform->set_data($course);

    if (!empty($args->jsonformdata) && strlen($args->jsonformdata)>2) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_courses\output\form_status($formstatus);
    $o .= $renderer->render($formstatusview);
    $o = $PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'getCatlist');
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}

/**
 * Serve the delete category form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_deletecategory_form($args){
 global $DB,$CFG,$PAGE;
    require_once($CFG->libdir.'/coursecatlib.php');
    require_once($CFG->libdir . '/questionlib.php');

    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;
    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    if ($categoryid) {
        $category = coursecat::get($categoryid);
        $context = context_coursecat::instance($category->id);
    }else {
        $category = coursecat::get_default();
        $categoryid = $category->id;
        $context = context_coursecat::instance($category->id);
    }

    $mform = new local_courses\form\deletecategory_form(null, $category, 'post', '', null, true, $formdata);
    // Used to set the courseid.

    if (!empty($args->jsonformdata)) {
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
 * Serve the new course category form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_coursecategory_form($args){
 global $DB,$CFG,$PAGE;
   // require_once($CFG->libdir.'/coursecatlib.php');

    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;

    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata =$args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }
    if (empty($formdata) && $categoryid > 0) {
        // $heading = get_string('updatecourse', 'local_courses');
        // $collapse = false;
        $data = $DB->get_record('course_categories', array('id'=>$categoryid));
        $formdata = new stdClass();
        $formdata->id = $data->id;
        $formdata->parent = $data->parent;
        $formdata->name = $data->name;
        $formdata->idnumber = $data->idnumber;
        $formdata->description_editor['text'] = $data->description;
    }

    if($categoryid){
        $coursecat =  core_course_category::get($categoryid, MUST_EXIST, true);
        $category = $coursecat->get_db_record();
        $context = context_coursecat::instance($categoryid);
        $category->description_editor['text'] = $category->description;

         $itemid = 0;
    }else{
        $parent = optional_param('parent', 0, PARAM_INT);

        if ($parent) {
            $DB->record_exists('course_categories', array('id' => $parent), '*', MUST_EXIST);
            $context = context_coursecat::instance($parent);
        } else {
            $context = context_system::instance();
        }
        $category = new stdClass();
        $category->id = 0;
        $category->parent = $parent;
    }

    // if ($categoryid > 0) {
    //     // $heading = get_string('updatecourse', 'local_courses');
    //     // $collapse = false;
    //     $data = $DB->get_record('course_categories', array('id'=>$categoryid));
    //     $formdata = new stdClass();
    //     $formdata->id = $data->id;
    //     $formdata->parent = $data->parent;
    //     $formdata->name = $data->name;
    //     $formdata->idnumber = $data->idnumber;
    //     $formdata->description_editor['text'] = $data->description;
    // }

    $params = array(
    'categoryid' => $categoryid,
    'parent' => $category->parent,
    'context' => $context,
    'itemid' => $itemid
    );

    $mform = new local_courses\form\coursecategory_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    $mform->set_data($category);

    if (!empty($args->jsonformdata)) {
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
 * Serve the table for course categories
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string 
 */
function local_courses_output_fragment_coursecategory_display($args){
    global $DB,$CFG,$PAGE,$OUTPUT;

    $args = (object) $args;
    $context = $args->context;
    $categoryid = $args->categoryid;

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }

    $table = new html_table();
    $table->id = 'popup_category';
    $table->align = ['left','center','center','center','center'];
    $table->head = array(get_string('course_name', 'local_courses'),get_string('enrolledusers', 'local_courses'),get_string('completed_users', 'local_courses'),get_string('type', 'local_courses'),get_string('actions', 'local_courses'));
    $courses = $DB->get_records_sql("SELECT c.id,c.category,c.fullname FROM {course} c WHERE c.id > 1
                                     AND c.category = ?", [$categoryid]);
    if($courses){
    $data=array();
    foreach($courses as $course){
        $row = array();
        $row[] = html_writer::link(new moodle_url('/course/view.php',array('id'=>$course->id)),$course->fullname);
        $course_sql = "SELECT count(ue.userid) as enrolled,count(cc.course) as completed
                            FROM {user_enrolments} as ue
                            JOIN {enrol} as e ON e.id=ue.enrolid
                            RIGHT JOIN {course} as c ON c.id =e.courseid
                            LEFT JOIN {course_completions} cc ON cc.course=e.courseid and ue.userid=cc.userid and cc.timecompleted IS NOT NULL
                            WHERE c.id = ?
                                group by e.courseid";
        $course_stats = $DB->get_record_sql($course_sql, [$course->id]);
       if($course_stats->enrolled){
            $row[] = $course_stats->enrolled;
        }else{
             $row[] = "N/A";
        }
        if($course_stats->completed){
            $row[] = $course_stats->completed;
        }else{
             $row[] = "N/A";
        }
        $ilt_sql = "SELECT open_identifiedas from {course}  WHERE id = ? " ;  
        $ilt_stats = $DB->get_record_sql($ilt_sql, [$course->id]);
        $types = explode(',',$ilt_stats->open_identifiedas);
        $classtype = array();
        foreach($types as $type){

            if($type == 2){
              $classtype[0]= get_string('ilt','local_courses');
            }
            if($type == 3){
             $classtype[2]= get_string('elearning','local_courses');
            }
            if($type == 4){
             $classtype[3]= get_string('learningplan','local_courses');
            }
            if($type == 5){
             $classtype[5]= get_string('program','local_courses');
            }
            if($type == 6){
             $classtype[6]= get_string('certification','local_courses');
            }
        }
        $ctype = implode(',',$classtype);

        if($ctype){

            $row[] = $ctype;
        }else{
             $row[] = "N/A";
        }



        $enrolid = $DB->get_field('enrol','id', array('courseid'=>$course->id, 'enrol'=>'manual'));

        $enrolicon = html_writer::link(new moodle_url('/local/courses/courseenrol.php',array('id'=>$course->id,'enrolid' => $enrolid)),html_writer::tag('i','',array('class'=>'fa fa-user-plus icon text-muted', 'title' => get_string('enrol','local_courses'), 'alt' => get_string('enrol'))));
        $actions = $enrolicon.' '.$editicon;
        $row[] = $actions;

        $data[] = $row;
    }
    $table->data = $data;
    $output = html_writer::table($table);
    $output .= html_writer::script("$('#popup_category').DataTable({
        'language': {
            paginate: {
            'previous': '<',
            'next': '>'
            }
        },
        'bInfo' : false,
        lengthMenu: [
            [5, 10, 25, 50, 100, -1],
            [5, 10, 25, 50, 100, 'All']
        ]
    });");
    }else{
        $output = "No Courses Available";
    }

    return $output;
}

/**
 * Serve the table for course status
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_courses_output_fragment_coursestatus_display($args){
    global $DB,$CFG,$PAGE,$OUTPUT,$USER;
    $args = (object) $args;
    $course = $DB->get_record('course', array('id' => $args->courseid));
    $info = new completion_info($course);
        
    // Is course complete?
    $coursecomplete = $info->is_course_complete($USER->id);

    // Has this user completed any criteria?
    $criteriacomplete = $info->count_course_user_data($USER->id);
    $params = array(
        'userid' => $USER->id,
        'course' => $course->id,
    );
    $completions = $info->get_completions($USER->id);
    $ccompletion = new completion_completion($params);

    $rows = array();
    // Loop through course criteria.
    foreach ($completions as $completion) {
        $criteria = $completion->get_criteria();
        $row = array();
            $row['type'] = $criteria->criteriatype;
            $row['title'] = $criteria->get_title();
            $row['complete'] = $completion->is_complete();
            $row['timecompleted'] = $completion->timecompleted;
            $row['details'] = $criteria->get_details($completion);
            $rows[] = $row;

        }
    // Print table.
    $last_type = '';
    $agg_type = false;
    // $oddeven = 0;

    $table = new html_table();
    $table->head = array(get_string('criteriagroup','format_tabtopics'),get_string('criteria','format_tabtopics'),get_string('requirement','format_tabtopics'),get_string('complete','format_tabtopics'),get_string('completiondate','format_tabtopics'));
    $table->size=array('20%','20%','25%','5%','30%');
    $table->align=array('left','left','left','center','center');
    $table->id = 'scrolltable';
    foreach ($rows as $row) {
        if ($last_type !== $row['details']['type']) {
        $last_type = $row['details']['type'];
        $agg_type = true;
        }else {
        // Display aggregation type.
            if ($agg_type) {
                $agg = $info->get_aggregation_method($row['type']);
                $last_type .= '('. html_writer::start_tag('i');
                if ($agg == COMPLETION_AGGREGATION_ALL) {
                    $last_type .= core_text::strtolower(get_string('all', 'completion'));
                } else {
                    $last_type .= core_text::strtolower(get_string('any', 'completion'));
                }
                $last_type .= html_writer::end_tag('i') .core_text::strtolower(get_string('required')).')';
                $agg_type = false;
            }
        }
        if ($row['timecompleted']) {
            $timecompleted=userdate($row['timecompleted'], get_string('strftimedate', 'langconfig'));
        } else {
            $timecompleted = '-';
        }
        $table->data[] = new html_table_row(array($last_type,$row['details']['criteria'],$row['details']['requirement'],$row['complete'] ? get_string('yes') : get_string('no'),$timecompleted));
    }
    $output = html_writer::table($table);
    $output .= html_writer::script("
         $(document).ready(function(){
            var table_rows = $('#scrolltable tr');
            // if(table_rows.length>6){
                $('#scrolltable').dataTable({
                    'searching': false,
                    'language': {
                        'paginate': {
                            'next': '>',
                            'previous': '<'
                        }
                    },
                    'pageLength': 5,
                });
            // }
        });
    ");
    return $output;
}

/*
* todo provides form element - courses
* @param $mform formobject
* return void
*/
function courses_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $sql = "SELECT id, fullname FROM {course} WHERE id > 1";
    $sql2 = " AND open_costcenterid = ?";
    $sql3 = " AND open_departmentid = ?";
    if(is_siteadmin()){
       $courseslist = $DB->get_records_sql_menu($sql);
    }else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $courseslist = $DB->get_records_sql_menu($sql.$sql2, [$USER->open_costcenterid]);
    }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
        $courseslist = $DB->get_records_sql_menu($sql.$sql2.$sql3, [$USER->open_costcenterid, $USER->open_departmentid]);
    }
    $select = $mform->addElement('autocomplete', 'courses', '', $courseslist, array('placeholder' => get_string('course')));
    $mform->setType('courses', PARAM_RAW);
    $select->setMultiple(true);
}
function status_filter($mform){
    $statusarray = array('active' => 'Active', 'inactive' => 'Inactive');
    $select = $mform->addElement('autocomplete', 'status', '', $statusarray, array('placeholder' => get_string('status')));
    $mform->setType('status', PARAM_RAW);
    $select->setMultiple(true);
} 

/* function coursetype_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $sql = "SELECT id, course_type FROM {local_course_types} WHERE id > 0 AND active = 1";
    $coursetypeslist = $DB->get_records_sql_menu($sql, []);
    $select = $mform->addElement('autocomplete', 'coursetypes', '', $coursetypeslist, array('placeholder' => get_string('course_type','local_courses')));
    $mform->setType('coursetypes', PARAM_RAW);
    $select->setMultiple(true);
} */

function courseproviders_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $sql = "SELECT id, course_provider FROM {local_course_providers} WHERE id > 0 AND active = 1";
    $courseproviderslist = $DB->get_records_sql_menu($sql, []);
    $select = $mform->addElement('autocomplete', 'courseproviders', '', $courseproviderslist, array('placeholder' => get_string('course_prov','local_courses')));
    $mform->setType('courseproviders', PARAM_RAW);
    $select->setMultiple(true);
}

function featuredcourses_filter($query, $action, $options, $searchanywhere){
  
    global  $DB, $USER;
    $params = array(
        'query' => $query,
        'action' => $action,
        'options' => $options,
        'searchanywhere' => $searchanywhere
    );
    $query = $params['query'];
    $action = $params['action'];
    $options = $params['options'];

    $searchanywhere=$params['searchanywhere'];
    
    $fields = array("fullname"/*, "shortname"*/);
    $likesql = array();
    $sqlparams['open_costcenterid'] = $USER->open_costcenterid;
    $i = 0;
    if (!empty($options)) {
        $formoptions = json_decode($options);
    }
    $concatsql="";
    if(!empty($query)){
        foreach ($fields as $field) {
            $i++;
            $likesql[] = $DB->sql_like($field, ":queryparam$i", false);
            $sqlparams["queryparam$i"] = "%$query%";
        }

        $sqlfields = implode(" OR ", $likesql);
        $concatsql = " AND ($sqlfields) ";
    }

    $currenttime = time();

    $coursetypessql = "SELECT id FROM {local_course_types} WHERE shortname IN ('ilt','learningpath')";
    $coursetypes = $DB->get_fieldset_sql($coursetypessql);
    $ctypes = implode(",",$coursetypes );

    $fields      = 'SELECT id, fullname';
    $coursessql = " FROM {course}
                    WHERE open_costcenterid = :open_costcenterid AND visible = :visible AND selfenrol = :enrol
                    AND c.open_identifiedas NOT IN ($ctypes) and (c.expirydate = 0 OR c.expirydate IS NULL OR c.expirydate >= :currenttime";
    $sqlparams['visible'] = 1;
    $sqlparams['enrol'] = 1;
    $sqlparams['currenttime'] = $currenttime;
    if(!empty($concatsql)){
        $coursessql .= " $concatsql";
    } 
    
    $courses = $DB->get_records_sql($fields.$coursessql, $sqlparams);
    return $courses;  
}

function featuredlpaths_filter($query, $action, $options, $searchanywhere){
  
    global $DB, $USER;
    $params = array(
        'query' => $query,
        'action' => $action,
        'options' => $options,
        'searchanywhere' => $searchanywhere
    );
    $query = $params['query'];
    $action = $params['action'];
    $options = $params['options'];

    $searchanywhere=$params['searchanywhere'];
    
    $fields = array("name"/*, "shortname"*/);
    $likesql = array();
    $sqlparams['costcenter'] = $USER->open_costcenterid;
    $i = 0;
  
    $concatsql="";
    if(!empty($query)){
        foreach ($fields as $field) {
            $i++;
            $likesql[] = $DB->sql_like($field, ":queryparam$i", false);
            $sqlparams["queryparam$i"] = "%$query%";
        }

        $sqlfields = implode(" OR ", $likesql);
        $concatsql = " AND ($sqlfields) ";
    }
    $fields      = 'SELECT id, name as fullname';
    $lpathssql = " FROM {local_learningplan}
                    WHERE costcenter = :costcenter AND selfenrol = 1";
    if(!empty($concatsql)){
        $lpathssql .= " $concatsql";
    } 
    $lpath_select = [null => get_string('selectlpaths','local_courses')];
    $lpaths = $DB->get_records_sql($fields.$lpathssql, $sqlparams);
    $lpaths = $lpath_select + $lpaths;
    return $lpaths;  
}

/*
* todo provides form element - courses
* @param $mform formobject
* return void
*/
function elearning_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
        $courseslist = $DB->get_records_sql_menu("SELECT id, fullname FROM {course} WHERE visible = 1");
    }
    $select = $mform->addElement('autocomplete', 'elearning', '', $courseslist, array('placeholder' => get_string('course_name', 'local_courses')));
    $mform->setType('elearning', PARAM_RAW);
    $select->setMultiple(true);
}

/*
* todo provides form element - categories
* @param $mform formobject
* return void
*/
function categories_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();
    $catslib = new local_courses\catslib();
    if(is_siteadmin()){
        $categorylist = $DB->get_records_sql_menu("SELECT id, name FROM {course_categories} ");
    } else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {
        $categories = $catslib->get_categories($USER->open_costcenterid);
        list($categoriessql, $categoriesparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'param', true, false);
        $categorylist = $DB->get_records_sql_menu("SELECT cc.id, cc.name FROM {course_categories} AS cc WHERE cc.id 
            $categoriessql ", $categoriesparams);
    }else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
        $categories = $catslib->get_categories($USER->open_departmentid);
        list($categoriessql, $categoriesparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED, 'param', true, false);
        $categorylist = $DB->get_records_sql_menu("SELECT cc.id, cc.name FROM {course_categories} AS cc WHERE cc.id 
            $categoriessql", $categoriesparams);
    }

    $select = $mform->addElement('autocomplete', 'categories', '', $categorylist, array('placeholder' => get_string('category')));
    $mform->setType('categories', PARAM_RAW);
    $select->setMultiple(true);
}

/*
* todo provides form element - courses
* @param $mform formobject
* return void
*/
function type_filter($mform){
    global $DB,$USER;
    $systemcontext = context_system::instance();

    $types = array();
    $classroom_plugin_exist = \core_component::get_plugin_directory('local', 'classroom');
    $learningplan_plugin_exist = \core_component::get_plugin_directory('local', 'learningplan');
    //$program_plugin_exist = \core_component::get_plugin_directory('local', 'program');
    //$certification_plugin_exist = \core_component::get_plugin_directory('local', 'certification');
    
    $coursetypeslist['1'] = get_string('mooc','local_courses');
    if(!empty($classroom_plugin_exist)){
        $coursetypeslist['2'] = get_string('ilt','local_courses');
    }
    $coursetypeslist['3'] = get_string('elearning','local_courses');
    if(!empty($learningplan_plugin_exist)){
        $coursetypeslist['4'] = get_string('learningplan','local_courses');
    }
    /* 
    if(!empty($program_plugin_exist)){
        $typeslist['5'] = get_string('program','local_courses');
    }
    if(!empty($certification_plugin_exist)){
      $typeslist['6'] = get_string('certification','local_courses');
    } 
    */    
    $sql = "SELECT id, course_type FROM {local_course_types} WHERE id > 4 AND active = 1";
    $ctypeslist  = $DB->get_records_sql_menu($sql, []);
    $coursetypeslist = $coursetypeslist + $ctypeslist; 
          
    //$coursetypeslist = array_merge( $coursetypeslist,$ctypeslist);
    
    $select = $mform->addElement('autocomplete', 'type', '', $coursetypeslist, array('placeholder' => 'Type'));
    $mform->setType('type', PARAM_RAW);
    $select->setMultiple(true);
}
/*
* todo prints the filter form
*/
function print_filterform(){
    global $DB, $CFG;
    require_once($CFG->dirroot . '/local/courses/filters_form.php');
    $mform = new filters_form(null, array('filterlist'=>array('courses', 'costcenter', 'categories')));
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
    $heading = '<button >'.get_string('course_filters', 'local_courses').'</button>';
    print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
    $mform->display();
    print_collapsible_region_end();
    return $filterdata;
}

/**
* [course_enrolled_users description]
* @param  string  $type       [description]
* @param  integer $evaluationid [description]
* @param  [type]  $params     [description]
* @param  integer $total      [description]
* @param  integer $offset    [description]
* @param  integer $perpage    [description]
* @param  integer $lastitem   [description]
* @return [type]              [description]
*/
function course_enrolled_users($type = null, $course_id = 0, $params, $total=0, $offset=-1, $perpage=-1, $lastitem=0){

    global $DB, $USER;
    $context = context_system::instance();
    $course = $DB->get_record('course', array('id' => $course_id));
 
    $params['suspended'] = 0;
    $params['deleted'] = 0;
 
    if($total==0){
         $sql = "SELECT u.id,concat(u.firstname,' ',u.lastname,' ','(',u.email,')') as fullname";
    }else{
        $sql = "SELECT count(u.id) as total";
    }
    $sql.=" FROM {user} AS u WHERE  u.id > 2 AND u.suspended = :suspended AND u.deleted = :deleted ";
    if($lastitem!=0){
       $sql.=" AND u.id > $lastitem";
    }
    if (!is_siteadmin()) {
        $user_detail = $DB->get_record('user', array('id'=>$USER->id));
        $sql .= " AND u.open_costcenterid = :costcenter";
        $params['costcenter'] = $course ->open_costcenterid;
        if (has_capability('local/costcenter:manage_owndepartments',$context) AND !has_capability('local/costcenter:manage_ownorganization',$context)) {
            $sql .=" AND u.open_departmentid = :department";
            $params['department'] = $user_detail->open_departmentid;
        }
    }
    $sql .=" AND u.id <> $USER->id";
    if (!empty($params['email'])) {
         $sql.=" AND u.id IN ({$params['email']})";
    }
    if (!empty($params['uname'])) {
         $sql .=" AND u.id IN ({$params['uname']})";
    }
    if (!empty($params['department'])) {
         $sql .=" AND u.open_departmentid IN ({$params['department']})";
    }
    if (!empty($params['organization'])) {
         $sql .=" AND u.open_costcenterid IN ({$params['organization']})";
    }
    if (!empty($params['idnumber'])) {
         $sql .=" AND u.id IN ({$params['idnumber']})";
    }
    if (!empty($params['groups'])) {
         $group_list = $DB->get_records_sql_menu("select cm.id, cm.userid from {cohort_members} cm, {user} u where u.id = cm.userid AND u.deleted = 0 AND u.suspended = 0 AND cm.cohortid IN ({$params['groups']})");
         
         $groups_members = implode(',', $group_list);
         if (!empty($groups_members))
         $sql .=" AND u.id IN ({$groups_members})";
         else
         $sql .=" AND u.id =0";
    }
    if ($type=='add') {
        $sql .= " AND u.id NOT IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self')))";
    }elseif ($type=='remove') {
        $sql .= " AND u.id IN (SELECT ue.userid
                             FROM {user_enrolments} AS ue 
                             JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid=$course_id and (e.enrol='manual' OR e.enrol='self')))";
    }

    $order = ' ORDER BY u.id ASC ';
    if($total==0){
        $availableusers = $DB->get_records_sql_menu($sql, $params, $offset, $perpage);
    }else{
        $availableusers = $DB->count_records_sql($sql, $params);
    }
    return $availableusers;
}

/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_courses_leftmenunode(){
    $systemcontext = context_system::instance();
    $coursecatnodes = '';
    if(has_capability('moodle/category:manage', $systemcontext) || is_siteadmin()) {
        $coursecatnodes .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_categories', 'class'=>'pull-left user_nav_div categories usernavdep'));
        $categories_url = new moodle_url('/local/courses/index.php');
        $categories = html_writer::link($categories_url, '<span class="manage_categories_icon left_menu_icons"></span><span class="user_navigation_link_text">'.get_string('leftmenu_browsecategories','local_courses').'</span>',array('class'=>'user_navigation_link'));
        $coursecatnodes .= $categories;
        $coursecatnodes .= html_writer::end_tag('li');
    }

    if(has_capability('local/courses:view', $systemcontext) || has_capability('local/courses:manage', $systemcontext) || is_siteadmin()) {
        $coursecatnodes .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_browsecourses', 'class'=>'pull-left user_nav_div browsecourses'));
            $courses_url = new moodle_url('/local/courses/courses.php');
            $courses = html_writer::link($courses_url, '<span class="manage_courses_icon left_menu_icons"></span><span class="user_navigation_link_text">'.get_string('manage_courses','local_courses').'</span>',array('class'=>'user_navigation_link'));
            $coursecatnodes .= $courses;
        $coursecatnodes .= html_writer::end_tag('li');
    }

    return array('6' => $coursecatnodes);
}

function local_courses_quicklink_node(){
    global $CFG, $PAGE, $OUTPUT;
    $systemcontext = context_system::instance();
    if (has_capability('local/courses:view', $systemcontext) || has_capability('local/courses:manage', $systemcontext) || is_siteadmin()){
        //local courses content
        // $local_courses_content = $PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'load');
        $PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'load');
        // $local_courses_content = "<span class='anch_span'><i class='fa fa-book' aria-hidden='true'></i></span>";
        // $local_courses_content .= "<div class='quick_navigation_detail'>
        //                                 <div class='span_str'>".get_string('manage_br_courses', 'local_courses')."</div>";
        // $local_courses_content .= "<span class='span_createlink'>";
        // if(is_siteadmin() ||( has_capability('moodle/course:create', $systemcontext)&& has_capability('moodle/course:update', $systemcontext)&&has_capability('local/courses:manage', $systemcontext))){
        //     $local_courses_content .="<a href='javascript:void(0);' class='quick_nav_link goto_local_courses' title = '".get_string('create_newcourse', 'local_courses')."' data-action='createcoursemodal' onclick='(function(e){ require(\"local_courses/courseAjaxform\").init({contextid:".$systemcontext->id.", component:\"local_courses\", callback:\"custom_course_form\", form_status:0, plugintype: \"local\", pluginname: \"courses\"}) })(event)'>".get_string('create')."</a> | ";
            
        // }
        // if(has_capability('local/courses:view', $systemcontext) || has_capability('local/courses:manage', $systemcontext) ){
        //     $local_courses_content .=" <a href='".$CFG->wwwroot."/local/courses/courses.php' class='viewlink' title= ' ".get_string('view_courses','local_courses')." '>".get_string('view')."</a>";
        // }
        // $local_courses_content .=" </span>";
        // $local_courses_content .= "</div>";
        // $local_courses = '<div class="quick_nav_list manage_courses one_of_three_columns" >'.$local_courses_content.'</div>';
        $coursedata = array();
        $coursedata['node_header_string'] = get_string('manage_br_courses', 'local_courses');
        $coursedata['pluginname'] = 'courses';
        $coursedata['plugin_icon_class'] = 'fa fa-book';
        if(is_siteadmin() ||( has_capability('moodle/course:create', $systemcontext)&& has_capability('moodle/course:update', $systemcontext)&&has_capability('local/courses:manage', $systemcontext))){
            $coursedata['create'] = TRUE;
            $coursedata['create_element'] = html_writer::link('javascript:void(0)', get_string('create'), array('onclick' => '(function(e){ require("local_courses/courseAjaxform").init({contextid:'.$systemcontext->id.', component:"local_courses", callback:"custom_course_form", form_status:0, plugintype: "local", pluginname: "courses"}) })(event)'));
        }
        if(has_capability('local/courses:view', $systemcontext) || has_capability('local/courses:manage', $systemcontext)){
            $coursedata['viewlink_url'] = $CFG->wwwroot.'/local/courses/courses.php';
            $coursedata['view'] = TRUE;
            $coursedata['viewlink_title'] = get_string('view_courses','local_courses');
        }
        $coursedata['space_count'] = 'one';
        $content = $OUTPUT->render_from_template('block_quick_navigation/quicklink_node', $coursedata);
    }
    return array('3' => $content);
}

/**
    * function costcenterwise_courses_count
    * @todo count of courses under selected costcenter
    * @param int $costcenter costcenter
    * @param int $department department
    * @return  array courses count of each type
*/
function costcenterwise_courses_count($costcenter,$department = false){
    global $USER, $DB;
    $params = array();
    $params['costcenter'] = $costcenter;
    $countcoursesql = "SELECT count(id) FROM {course} WHERE open_costcenterid = :costcenter";
    if($department){
        $countcoursesql .= " AND open_departmentid = :department ";
        $params['department'] = $department;
    }
    $activesql = " AND visible = 1 ";
    $inactivesql = " AND visible = 0 ";

    $countcourses = $DB->count_records_sql($countcoursesql, $params);
    $activecourses = $DB->count_records_sql($countcoursesql.$activesql, $params);
    $inactivecourses = $DB->count_records_sql($countcoursesql.$inactivesql, $params);

    return array('coursecount' => $countcourses,'activecoursecount' => $activecourses,'inactivecoursecount' => $inactivecourses);
}

/**
    * function get_listof_courses
    * @todo all courses based  on costcenter / department
    * @param object $stable limit values
    * @param object $filterdata filterdata
    * @return  array courses
*/

function get_listof_courses($stable, $filterdata) {
    global $CFG,$DB,$OUTPUT,$USER;
    $core_component = new core_component();
    require_once($CFG->dirroot . '/course/lib.php');
   // require_once($CFG->libdir. '/coursecatlib.php');
    require_once($CFG->dirroot.'/course/renderer.php');
    require_once($CFG->dirroot . '/enrol/locallib.php');
    $autoenroll_plugin_exist = $core_component::get_plugin_directory('enrol','auto');
    if(!empty($autoenroll_plugin_exist)){
      require_once($CFG->dirroot . '/enrol/auto/lib.php');
    }
    $systemcontext = context_system::instance();
     $filter = new stdClass();
    $departmentsparams = array();
    $subdepartmentsparams = array();
    $organizationsparams = array();
    $userorg = array();
    $userdep = array();
    $filtercategoriesparams= array();
    $filtercoursesparams = array();
    $chelper = new coursecat_helper();
    $selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_points,c.open_costcenterid, c.open_identifiedas, c.visible, c.open_skill FROM {course} AS c"; 
    $countsql  = "SELECT count(c.id) FROM {course} AS c ";
    $formsql = "";
     $filter->categories = str_replace('_qf__force_multiselect_submission', '', $filterdata->categories);
    $filter->courses = str_replace('_qf__force_multiselect_submission', '', $filterdata->courses);
    $filter->organizations = str_replace('_qf__force_multiselect_submission', '', $filterdata->organizations);
    $filter->departments = str_replace('_qf__force_multiselect_submission', '', $filterdata->departments);
    $filter->subdepartment = str_replace('_qf__force_multiselect_submission', '', $filterdata->subdepartment);
    $filter->status = str_replace('_qf__force_multiselect_submission', '', $filterdata->status);
    $filter->coursetypes = str_replace('_qf__force_multiselect_submission', '', $filterdata->coursetypes);
    $filter->courseproviders = str_replace('_qf__force_multiselect_submission', '', $filterdata->courseproviders);
    $filter->type = str_replace('_qf__force_multiselect_submission', '', $filterdata->type);
    if(!empty($filterdata->coursetypes)){
        $formsql .= " JOIN {local_course_types} AS ct ON ct.id = c.open_coursetype ";
    }
    if(!empty($filterdata->courseproviders)){
        $formsql .= " LEFT JOIN {local_course_providers} AS cp ON cp.id = c.open_courseprovider";
    }
     if(is_siteadmin()){
        $formsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                     JOIN {course_categories} AS cc ON cc.id = c.category";
    } elseif(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
        $formsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = :usercostcenter";
    } elseif(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
        $formsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = :usercostcenter 
                   AND c.open_departmentid = :userdepartment";
    } else {
        $formsql .= " JOIN {local_costcenter} AS co ON co.id = c.open_costcenterid
                   JOIN {course_categories} AS cc ON cc.id = c.category
                   WHERE c.open_costcenterid = :usercostcenter 
                   AND c.open_departmentid = :userdepartment";
    }
    $formsql .= " AND c.id > 1 ";
    if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
        $formsql .= " AND c.fullname LIKE :search";
        $searchparams = array('search' => '%'.trim($filterdata->search_query).'%');
    }else{
        $searchparams = array();
    }
    if(!empty($filter->categories)){
        $filtercategories = explode(',', $filter->categories);
        list($filtercategoriessql, $filtercategoriesparams) = $DB->get_in_or_equal($filtercategories, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND cc.id $filtercategoriessql";
    }
    if(!empty($filter->courses)){
        $filtercourses = explode(',', $filter->courses);
        list($filtercoursessql, $filtercoursesparams) = $DB->get_in_or_equal($filtercourses, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND c.id $filtercoursessql";
    }
    
    if(!empty($filter->departments)){
        $departments = explode(',', $filter->departments);
        list($departmentssql, $departmentsparams) = $DB->get_in_or_equal($departments, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND c.open_departmentid $departmentssql";
    
    }
    if(!empty($filter->subdepartment)){
        $subdepartments = explode(',', $filter->subdepartment);
        list($subdepartmentssql, $subdepartmentsparams) = $DB->get_in_or_equal($subdepartments, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND c.open_subdepartment $subdepartmentssql";
    
    }
    
    if(!empty($filter->organizations)){
        $organizations = explode(',', $filter->organizations);
        list($organizationssql, $organizationsparams) = $DB->get_in_or_equal($organizations, SQL_PARAMS_NAMED, 'param', true, false);
        $formsql .= " AND c.open_costcenterid $organizationssql";
    }

    if(!empty($filter->status)){
        $status = explode(',',$filter->status);
        if(!(in_array('active',$status) && in_array('inactive',$status))){
            if(in_array('active' ,$status)){
                $formsql .= " AND c.visible = 1 ";           
            }else if(in_array('inactive' ,$status)){
                $formsql .= " AND c.visible = 0 ";
            }
        }
    }
    
    if(!empty($filter->type)){
        $selectedtypes = array_filter(explode(',', $filter->type), 'is_numeric');
        $typesfilterarray = array();
        foreach ($selectedtypes as $selectedtype) {
            $typesfilterarray[] = " CONCAT(',',c.open_identifiedas,',') LIKE 
                                    CONCAT('%,',$selectedtype,',%') ";
        }
        $typesfilterconcat = implode(' OR ', $typesfilterarray);
        $formsql .= " AND ( $typesfilterconcat ) ";
    }

    if(!empty($filter->coursetypes)){
        $selectedcoursetypes = explode(',',$filter->coursetypes);
        $coursetypesfilterarray = array();
        foreach ($selectedcoursetypes as $selectedcoursetype) {
            $coursetypesfilterarray[] = " CONCAT(',',c.open_coursetype,',') LIKE 
                                    CONCAT('%,',$selectedcoursetype,',%') ";
        }
        $coursetypesfilterconcat = implode(' OR ', $coursetypesfilterarray);
        $formsql .= " AND ( $coursetypesfilterconcat ) ";
    }

    if(!empty($filter->courseproviders)){
        $selectedcourseproviders = array_filter(explode(',', $filter->courseproviders), 'is_numeric');
        $courseprovidersfilterarray = array();
        foreach ($selectedcourseproviders as $selectedcourseprovider) {
            $courseprovidersfilterarray[] = " CONCAT(',',c.open_courseprovider,',') LIKE 
                                    CONCAT('%,',$selectedcourseprovider,',%') ";
        }
        $courseprovidersfilterconcat = implode(' OR ', $courseprovidersfilterarray);
        $formsql .= " AND ( $courseprovidersfilterconcat ) ";
    }

    if (!is_siteadmin()) {
        $userorg = array('usercostcenter'=>$USER->open_costcenterid);
        $userdep = array('userdepartment'=>$USER->open_departmentid);
    }

    $params = array_merge($searchparams, $userorg, $userdep, $filtercategoriesparams, $filtercoursesparams, $departmentsparams, $subdepartmentsparams, $organizationsparams);
    $totalcourses = $DB->count_records_sql($countsql.$formsql, $params);
    $formsql .=" ORDER BY c.id DESC";
    $courses = $DB->get_records_sql($selectsql.$formsql, $params, $stable->start,$stable->length);

    $ratings_plugin_exist = $core_component::get_plugin_directory('local', 'ratings');
    $courseslist = array();
    if(!empty($courses)){
        $count = 0;
        foreach ($courses as $key => $course) {
            $course_in_list = new core_course_list_element($course);
            $context = context_course::instance($course->id);
            $category = $DB->get_record('course_categories',array('id'=>$course->category));

            $params = array('courseid'=>$course->id);
            
            $enrolledusersssql = " SELECT COUNT(DISTINCT(ue.id)) as ccount
                                FROM {course} c
                                JOIN {enrol} e ON e.courseid = c.id AND 
                                            (e.enrol = 'manual' OR e.enrol = 'self' OR e.enrol='auto') 
                                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                                JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1 
                                                AND u.deleted = 0 AND u.suspended = 0
                                JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
                                JOIN {role_assignments} as ra ON ra.userid = u.id
                                JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
                                WHERE c.id = :courseid";

            $enrolled_count =  $DB->count_records_sql($enrolledusersssql, $params);


            $completedusersssql = " SELECT COUNT(DISTINCT(cc.id)) as ccount
                                FROM {course} c
                                JOIN {enrol} e ON e.courseid = c.id AND 
                                            (e.enrol = 'manual' OR e.enrol = 'self' OR e.enrol='auto') 
                                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                                JOIN {user} u ON u.id = ue.userid AND u.confirmed = 1 
                                                AND u.deleted = 0 AND u.suspended = 0
                                JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
                                JOIN {role_assignments} as ra ON ra.userid = u.id
                                JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
                                JOIN {course_completions} as cc 
                                        ON cc.course = c.id AND u.id = cc.userid
                                WHERE c.id = :courseid AND cc.timecompleted IS NOT NULL ";

            $completed_count = $DB->count_records_sql($completedusersssql,$params);

            $coursename = $course->fullname;
            if (strlen($coursename)>23){
                $coursenameCut = substr($coursename, 0, 23)."...";
                $courseslist[$count]["coursenameCut"] = \local_costcenter\lib::strip_tags_custom($coursenameCut);
            }
            $catname = $category->name;
            $catnamestring = strlen($catname) > 12 ? substr($catname, 0, 12)."..." : $catname;
        
            $courestypes = explode(',', $course->open_identifiedas);
            $displayed_names = array();
            foreach ($courestypes as $key => $courestype){ 
                $coursetypedetails = $DB->get_record('local_course_types',array('id'=>$courestype),'shortname,course_type,course_image');
                $displayed_names[] = '<span class="pl-10 '.$coursetypedetails->shortname.'">'.$coursetypedetails->course_type.'</span>';
                $coursetypeforimg = $coursetypedetails->course_image;
            }
            if($ratings_plugin_exist){
                require_once($CFG->dirroot.'/local/ratings/lib.php');
                $ratingenable = True;
                $avgratings = get_rating($course->id, 'local_courses');
                $rating_value = $avgratings->avg == 0 ? 'N/A' : $avgratings->avg/*/2*/ ;
            }else{
                $ratingenable = False;
                $rating_value = 'N/A';
            }
            $classname = '\local_tags\tags';
            if(class_exists($classname)){
                $tags = new $classname;
                $tagstring = $tags->get_item_tags($component = 'local_courses', $itemtype='courses', $itemid=$course->id, $contextid=context_course::instance($course->id)->id, $arrayflag = 0,$more = 0);
                $tagstring = strlen($tagstring) > 35 ? substr($tagstring, 0, 35).'...' : $tagstring;
                $tagenable = True;
            }else{
                $tagenable = False;
                $tagstring = '';
            }

            if($course->open_skill){
                $sql = "SELECT GROUP_CONCAT(name separator ', ')
                        FROM {local_skill}
                        WHERE id IN ($course->open_skill) ";
                $skill = $DB->get_field_sql($sql);
                if($skill){
                    $skillname = $skill;
                } else {
                    $skillname = 'N/A';
                }
            } else {
                $skillname = 'N/A';                
            }
            
            $displayed_names = implode(',' ,$displayed_names);
            $courseslist[$count]["coursename"] = \local_costcenter\lib::strip_tags_custom($coursename);
            $courseslist[$count]["skillname"] = \local_costcenter\lib::strip_tags_custom($skillname);
            $courseslist[$count]["ratings_value"] = $rating_value;
            $courseslist[$count]["ratingenable"] = $ratingenable;
            $courseslist[$count]["tagstring"] = \local_costcenter\lib::strip_tags_custom($tagstring);
            $courseslist[$count]["tagenable"] = $tagenable;
            $courseslist[$count]["catname"] = \local_costcenter\lib::strip_tags_custom($catname);
            $courseslist[$count]["catnamestring"] = \local_costcenter\lib::strip_tags_custom($catnamestring);
            $courseslist[$count]["enrolled_count"] = $enrolled_count;
            $courseslist[$count]["courseid"] = $course->id;
            $courseslist[$count]["completed_count"] = $completed_count;
            $courseslist[$count]["points"] = $course->open_points != NULL ? $course->open_points: 0;
            $courseslist[$count]["coursetype"] = \local_costcenter\lib::strip_tags_custom($displayed_names);
            $courseslist[$count]["course_class"] = $course->visible ? 'active' : 'inactive';
            
            $coursesummary = \local_costcenter\lib::strip_tags_custom($chelper->get_course_formatted_summary($course_in_list,
                    array('overflowdiv' => false, 'noclean' => false, 'para' => false)));
            $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
            $courseslist[$count]["coursesummary"] = \local_costcenter\lib::strip_tags_custom($summarystring);
    
            //course image
          /*   if(file_exists($CFG->dirroot.'/local/includes.php')){
                require_once($CFG->dirroot.'/local/includes.php');
                $includes = new user_course_details();
                $courseimage = $includes->course_summary_files($course);   
              
                if(is_object($courseimage)){
                    $courseslist[$count]["courseimage"] = $courseimage->out();                    
                }else{
                    $courseslist[$count]["courseimage"] = $courseimage;
                }                
            }   */  
        
            $courseslist[$count]["courseimage"] =  course_thumbimage($course);
            $courseslist[$count]["courseurl"] = $CFG->wwwroot."/course/view.php?id=".$course->id;
            $enrolid = $DB->get_field('enrol','id',array('enrol'=>'manual','courseid'=>$course->id));
            
            if(has_capability('local/courses:enrol',$systemcontext)&&has_capability('local/courses:manage', $systemcontext)){
                $courseslist[$count]["enrollusers"] = $CFG->wwwroot."/local/courses/courseenrol.php?id=".$course->id."&enrolid=".$enrolid;
            }
            
            $categorycontext = context_coursecat::instance($course->category);
            
            if(has_capability('local/courses:update',$systemcontext)&&has_capability('local/courses:manage', $systemcontext)&&has_capability('moodle/course:update', $systemcontext)){
                $courseedit = html_writer::link('javascript:void(0)', html_writer::tag('i', '', array('class' => 'fa fa-pencil icon')) , array('title' => get_string('edit'), 'alt' => get_string('edit'),'data-action' => 'createcoursemodal', 'class'=>'createcoursemodal', 'data-value'=>$course->id, 'onclick' =>'(function(e){ require("local_courses/courseAjaxform").init({contextid:'.$categorycontext->id.', component:"local_courses", callback:"custom_course_form", form_status:0, plugintype: "local", pluginname: "courses", courseid: ' . $course->id . ' }) })(event)'));
                $courseslist[$count]["editcourse"] = $courseedit;
                if($course->visible){
                    $icon = 't/hide';
                    $string = get_string('le_active','local_evaluation');
                    $title = get_string('le_inactive','local_evaluation');
                }else{
                    $icon = 't/show';
                    $string = get_string('le_inactive','local_evaluation');
                    $title = get_string('le_active','local_evaluation');
                }
                $image = $OUTPUT->pix_icon($icon, $title, 'moodle', array('class' => 'iconsmall', 'title' => ''));
                $params = json_encode(array('coursename' => $coursename, 'coursestatus' => $course->visible));
                $courseslist[$count]["update_status"] .= html_writer::link("javascript:void(0)", $image, array('data-fg'=>"d", 'data-method' => 'course_update_status','data-plugin' => 'local_courses', 'data-params' => $params, 'data-id'=>$course->id));
                if(!empty($autoenroll_plugin_exist)){
                    $autoplugin = enrol_get_plugin('auto');
                    $instance = $autoplugin->get_instance_for_course($course->id);
                    if($instance){
                        if ($instance->status == ENROL_INSTANCE_DISABLED) {
                            
                        $courseslist[$count]["auto_enrol"] = $CFG->wwwroot."/enrol/auto/edit.php?courseid=".$course->id."&id=".$instance->id;
                        }
                    }
                }
            }
            
            if(has_capability('local/courses:delete',$systemcontext)&&has_capability('local/courses:manage', $systemcontext)){
                $deleteactionshtml = html_writer::link('javascript:void(0)', $OUTPUT->pix_icon('t/delete', get_string('delete'), 'moodle', array('')), array('title' => get_string('delete'), 'id' => "courses_delete_confirm_".$course->id,'onclick'=>'(function(e){ require(\'local_courses/courseAjaxform\').deleteConfirm({action:\'deletecourse\' , id: ' . $course->id . ', name:"'.$coursename.'" }) })(event)'));
                $courseslist[$count]["deleteaction"] = $deleteactionshtml;
            }
            
            if(has_capability('local/courses:grade_view',$systemcontext)&&has_capability('local/courses:manage', $systemcontext)){
                $courseslist[$count]["grader"] = $CFG->wwwroot."/grade/report/grader/index.php?id=".$course->id;
            }
            if(has_capability('local/courses:report_view',$systemcontext)&&has_capability('local/courses:manage', $systemcontext)){
                $courseslist[$count]["activity"] = $CFG->wwwroot."/report/outline/index.php?id=".$course->id;
            }
            if ((has_capability('local/request:approverecord', context_system::instance()) || is_siteadmin())) {
                $courseslist[$count]["requestlink"] = $CFG->wwwroot."/local/request/index.php?courseid=".$course->id;
            }
            $courseslist[$count]["facilitatorlink"] = $CFG->wwwroot."/local/courses/facilitator.php?courseid=".$course->id;
            $count++;
        }
        $nocourse = false;
        $pagination = false;
    }else{
        $nocourse = true;
        $pagination = false;
    }
    // check the course instance is not used in any plugin
    $candelete = true;
    $core_component = new core_component();
    $classroom_plugin_exist = $core_component::get_plugin_directory('local', 'classroom');
    if ($classroom_plugin_exist) {
        $exist_sql = "Select id from {local_classroom_courses} where courseid = ?";
        if ($DB->record_exists_sql($exist_sql, array($course->id)))
        $candelete = false;
    }
    
    $program_plugin_exist = $core_component::get_plugin_directory('local', 'program');
    if ($program_plugin_exist) {
        $exist_sql = "Select id from {local_program_level_courses} where courseid = ?";
        if ($DB->record_exists_sql($exist_sql, array($course->id)))
        $candelete = false;
    }
    $certification_plugin_exist = $core_component::get_plugin_directory('local', 'certification');
    if ($certification_plugin_exist) {
        $exist_sql = "Select id from {local_certification_courses} where courseid = ?";
        if ($DB->record_exists_sql($exist_sql, array($course->id)))
        $candelete = false;
    }
    $coursesContext = array(
        "hascourses" => $courseslist,
        "nocourses" => $nocourse,
        "totalcourses" => $totalcourses,
        "length" => count($courseslist),
        "actions"=>(((has_capability('local/courses:enrol',
        context_system::instance())|| has_capability('local/courses:update',
        context_system::instance())||has_capability('local/courses:delete',
        context_system::instance()) || has_capability('local/courses:grade_view',
        context_system::instance())|| has_capability('local/courses:report_view',
        context_system::instance())) || is_siteadmin())&&has_capability('local/courses:manage', $systemcontext)) ? true : false,
        "enrol"=>((has_capability('local/courses:enrol',
        context_system::instance())  || is_siteadmin())&&has_capability('local/courses:manage', $systemcontext)) ? true : false,
        "update"=>((has_capability('local/courses:update',
        context_system::instance()) || is_siteadmin())&&has_capability('local/courses:manage', $systemcontext)) ? true : false,
        "delete"=>((has_capability('local/courses:delete',
        context_system::instance()) || is_siteadmin())&&has_capability('local/courses:manage', $systemcontext)) ? true : false,
        "grade_view"=>((has_capability('local/courses:grade_view',
        context_system::instance()) || is_siteadmin())&&has_capability('local/courses:manage', $systemcontext)) ? true : false,
        "report_view"=>((has_capability('local/courses:report_view',
        context_system::instance()) || is_siteadmin())&&has_capability('local/courses:manage', $systemcontext)) ? true : false,
        "request_view"=>((has_capability('local/request:approverecord', $systemcontext)) || is_siteadmin()) ? true : false, 
    );

    return $coursesContext;

}

/**
    * function get_listof_categories
    * @todo all courses based  on costcenter / department
    * @param object $stable limit values
    * @param object $filterdata filterdata
    * @return  array courses
*/
function get_listof_categories($stable, $filterdata) {
    global $DB, $CFG, $OUTPUT, $PAGE ,$USER;
    require_once($CFG->dirroot.'/course/lib.php');
    //require_once($CFG->libdir.'/coursecatlib.php');
    $categorylib = new local_courses\catslib();

    $organizationsparams = array();
    $deptcategoryparams = array();
    $categoryparams = array();
    $filtercategoriesparams= array();
    $table = new html_table();
    $table->id = 'category_tbl';
    $table->head = array('','','','');

    $systemcontext = context_system::instance();
    $countsql = "select count(c.id) ";
    $sql = "SELECT c.id, c.name, c.parent, c.visible, c.coursecount, c.idnumber ";
    $fromsql = "FROM {course_categories} as  c WHERE id > 1 ";
        
    if(!empty($filterdata->parentid)){
        $fromsql .= " AND c.parent = $filterdata->parentid ";
        
    } else{ 
        if(is_siteadmin()) {
            $fromsql .= " AND c.parent =0 ";
        } elseif(has_capability('local/costcenter:manage_ownorganization',$systemcontext)) {

        // $orgcategories = $categorylib->get_categories($USER->open_costcenterid);
        
        // list($organizationssql, $organizationsparams) = $DB->get_in_or_equal($orgcategories, SQL_PARAMS_NAMED, 'param', true, false);
        // $fromsql .= " AND c.id $organizationssql";
            $fromsql .= " AND c.id = (SELECT category FROM {local_costcenter} WHERE id = {$USER->open_costcenterid} )";

        } elseif(has_capability('local/costcenter:manage_owndepartments',$systemcontext)) {
        // $deptcategories = $categorylib->get_categories($USER->open_departmentid);
        // list($deptcategorysql, $deptcategoryparams) = $DB->get_in_or_equal($deptcategories, SQL_PARAMS_NAMED, 'param', true, false);
        // $fromsql .= " AND c.id $deptcategorysql ";
            $fromsql .= " AND c.id = (SELECT category FROM {local_costcenter} WHERE id = {$USER->open_departmentid} )" ;
        // } else {
        //     $categorieslist = $categorylib->get_categories();
        //     list($categorysql, $categoryparams) = $DB->get_in_or_equal($categorieslist, SQL_PARAMS_NAMED, 'param', true, false);
        //     $fromsql .= " AND c.id $categorysql ";
        } 
    }

   
    if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
        $fromsql .= " AND c.name LIKE :search ";
        $searchparams = array('search' => '%'.$filterdata->search_query.'%');
    }else{
        $searchparams = array();
    }
    $ordersql = " ORDER BY c.id DESC ";

    $params = array_merge($searchparams,$organizationsparams, $deptcategoryparams, $categoryparams);

    $allcategories = $DB->get_records_sql($sql.$fromsql.$ordersql, $params, $stable->start, $stable->length);      
    $categoriescount = $DB->count_records_sql($countsql.$fromsql, $params);
    $data = array();
    $totalrecords = count($allcategories);
    $org_categories = $DB->get_records_menu('local_costcenter', array(),'', 'id, category');
    foreach($allcategories as $categories){
        $row = array();
        $result = $categories->name;
        $cate= $categories->id;
        $sql = $DB->get_records_sql("SELECT c.name FROM {course_categories} as  c
                                    WHERE c.parent=$cate");
        $categorynames =  count($sql);
        $categoryidnumber = $categories->idnumber;
        $categorycontext = context_coursecat::instance($categories->id);
        if($categories->visible ==0){
            $count =  $categories->coursecount;
        }

        if($categorynames > 0){
            $linkurl = new moodle_url("/local/courses/index.php?id=".$categories->id."");
        }else{
            $linkurl = null;
        }

        $counts =html_writer::link($linkurl, $categorynames, array());

        $count = html_writer::link('javascript:void(0)', $categories->coursecount, array('title' => '', 'alt' => '', 'class'=>'createcoursemodal course_count_popup', 'onclick' =>'(function(e){ require("local_courses/newcategory").courselist({contextid:'.$categorycontext->id.', categoryname: "'.$categories->name.'", categoryid: "' . $categories->id . '" }) })(event)'));

        $actions = '';
        if(has_capability('moodle/category:manage',$systemcontext)){
            $actions = true;
            if(!empty($categories->visible)){
                $visible_value = 0;
                $show = true;
            }else{
                $visible_value = 1;
                $show =  false;
            }
        }
        if($result  != ''){
            $parentname_str = strlen($result) > 20 ? substr($result, 0, 20)."..." : $result;

        }else{
            $parentname_str = 'N/A';
        }

        if(!empty($categories->visible)) {
            $line['parentname_str'] = $parentname_str;
            $line['result'] = $result;
        } else {
            $line['parentname_str'] = $parentname_str;
            $line['result'] = $result;
        }
        if($categoryidnumber != ''){
        $categoryidnumber_idnumber = strlen($categoryidnumber) > 13 ? substr($categoryidnumber, 0, 13)."..." : $categoryidnumber;

        }else{
            $categoryidnumber_idnumber = 'N/A';
        }
        if(!empty($categories->visible)) {
            $line['categoryidnumber_idnumber'] = $categoryidnumber_idnumber;
            $line['categoryidnumber'] = $categoryidnumber;
        } else {
            $line['categoryidnumber_idnumber'] = $categoryidnumber_idnumber;
            $line['categoryidnumber'] = $categoryidnumber;
        }

        if(!empty($categories->visible)){
            $line['catcount'] = $count;
        }else {
            $line['catcount'] = $count;
        }

        if(!empty($categories->visible)) {
            $line['categoryname_str'] = $counts;
        } else {
            $line['categoryname_str'] = $counts;
        }
        $catdepth = $DB->get_field('course_categories','depth',array('id'=>$filterdata->parentid));
        if($catdepth < 2){
            $depth = true;
        }else{
             $depth = false;
        }
        $line['showsubcategory'] =  $depth;

        $catimage = $OUTPUT->image_url('catlist', 'local_courses');
        if(is_object($catimage)){
            $line['catlisticon'] = $catimage->out_as_local_url();
        }else{
            $line['catlisticon'] = $catimage;
        }
        $line['catgoryid'] = $categories->id;
        $line['actions'] = $actions;
        $line['contextid'] = $systemcontext->id;
        $line['show'] = $show;
        $line['visible_value'] = $visible_value;
        $line['sesskey'] = sesskey();

        $coursesexists = $DB->record_exists('course', array('category'=>$categories->id));
        $subcatexists = $DB->record_exists('course_categories', array('parent'=>$categories->id));

        if(in_array($categoriescount->id, $org_categories)){
            $line['delete_enable'] = FALSE;
            $line['unabletodelete_reason'] = get_string('reason_linkedtocostcenter','local_courses');
        }elseif($subcatexists){
            $line['delete_enable'] = FALSE;
            $line['unabletodelete_reason'] = get_string('reason_subcategoriesexists','local_courses');
        }elseif($coursesexists){
            $line['delete_enable'] = FALSE;
            $line['unabletodelete_reason'] = get_string('reason_coursesexists','local_courses');
        }else{
            $line['delete_enable'] = TRUE;
        }
        
        $data[] = $line;
    }
    return array('totalrecords' => $categoriescount,'records' => $data);
}

/*
* Author sarath
* @return true for reports under category
*/
function learnerscript_courses_list(){
    return 'Courses';
}

/**
 * Returns onlinetests tagged with a specified tag.
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
function local_courses_get_tagged_courses($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0, $sort = '') {
    global $CFG, $PAGE;
    // prepare for display of tags related to tests
    $perpage = $exclusivemode ? 10 : 5;
    $displayoptions = array(
        'limit' => $perpage,
        'offset' => $page * $perpage,
        'viewmoreurl' => null,
    );
    $renderer = $PAGE->get_renderer('local_courses');
    $totalcount = $renderer->tagged_courses($tag->id, $exclusivemode, $ctx, $rec, $displayoptions, $count = 1, $sort);
    $content = $renderer->tagged_courses($tag->id, $exclusivemode, $ctx, $rec, $displayoptions, 0, $sort);
    $totalpages = ceil($totalcount / $perpage);
    if ($totalcount)
    return new local_tags\output\tagindex($tag, 'local_courses', 'courses', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
    else
    return '';
}
/**
* todo sql query departmentwise
* @param  $systemcontext object 
* @return array
**/

function get_course_details($courseid) {
    global $USER, $DB, $PAGE;
    $context = context_system::instance();
    $PAGE->requires->js_call_amd('local_courses/courses','load', array());
    $PAGE->requires->js_call_amd('local_request/requestconfirm','load', array());
    $details = array();
    $joinsql = '';
    if(is_siteadmin() OR has_capability('local/costcenter:manage_ownorganization',$context) OR 
        has_capability('local/costcenter:manage_owndepartments',$context)) {
        $sql = "select c.* from {course} c where c.id = ?";

        $selectsql = "select c.*  ";
        $fromsql = " from  {course} c";
        if ($DB->get_manager()->table_exists('local_rating')) {
            $selectsql .= " , AVG(rating) as avg ";
            $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_courses' ";
        }
        $wheresql = " where c.id = ? ";

        $adminrecord = $DB->get_record_sql($selectsql.$fromsql.$joinsql.$wheresql, [$courseid]);
        $enrolsql = "SELECT count(id) as ccount from {course_completions} where course = ? AND timecompleted IS NOT NULL";

        $completionsql="SELECT count(u.id) as total FROM {user} AS u WHERE u.id > 2 AND u.suspended =0 AND u.deleted = 0 AND u.id <> 3 AND u.id IN (
            SELECT ue.userid FROM {user_enrolments} ue
            JOIN {enrol} e ON (e.id = ue.enrolid and e.courseid = ? and (e.enrol='manual' OR e.enrol='self')))";
        $completedcount =  $DB->count_records_sql($completionsql, [$adminrecord->id]);
        $enrolledcount = $DB->count_records_sql($enrolsql,[$adminrecord->id]);
        $courestypes_names = array('1'=>'MOOC','2'=>'Classroom','3'=>'E-Learning', '4'=> 'Learning Path', '5' => 'Program', '6' => 'Certification');
        $text_class = array('1'=>'MOOC','2'=>'classroom','3'=>'elearning', '4'=> 'learningpath', '5' => 'program', '6' => 'certification');
        $courestypes = explode(',', $adminrecord->open_identifiedas);
        $displayed_names = array();
        foreach ($courestypes as $key => $courestype){
            $displayed_names[] = $courestypes_names[$courestype];
        }
        $displayed_names = implode(',' ,$displayed_names);
        $details['manage'] = 1;
        $details['completed'] = $completedcount;
        $details['enrolled'] = $enrolledcount;
        $details['type'] = $displayed_names;
    } else {
        $ccsql = "SELECT * from {course_completions} where course = ? AND userid = ?";
        $userrecord = $DB->get_record_sql($ccsql, [$courseid, $USER->id]);
        // $courserecord = $DB->get_record('course', ['id'=>$courseid]);
        $selectsql = "select c.*, ra.timemodified ";

        $fromsql = " from {course} c ";
        
        if ($DB->get_manager()->table_exists('local_rating')) {
            $selectsql .= " , AVG(rating) as avg ";
            $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_courses' ";
        }
        $joinsql .= " JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50
        JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ? ";
        $wheresql = " where 1 = 1 AND c.id = ? ";
        $courserecord = $DB->get_record_sql($selectsql.$fromsql.$joinsql.$wheresql, [$USER->id, $courseid], IGNORE_MULTIPLE);
        if ($courserecord->selfenrol == 1 && $courserecord->approvalreqd == 0) {
            $enrollmentbtn = '<a href="javascript:void(0);" data-action="courseselfenrol'.$courseid.'" class="courseselfenrol enrolled'.$courseid.'" onclick ="(function(e){ require(\'local_catalog/courseinfo\').test({selector:\'courseselfenrol'.$courseid.'\', courseid:'.$courseid.', enroll:1}) })(event)"><button class="cat_btn viewmore_btn"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('enroll','local_catalog').'</button></a>';
        } elseif ($courserecord->selfenrol == 1 && $courserecord->approvalreqd == 1) {
            $enrollmentbtn = '<a href="javascript:void(0);" class="cat_btn" alt = ' . get_string('requestforenroll','local_classroom'). ' title = ' .get_string('requestforenroll','local_classroom'). ' onclick="(function(e){ require(\'local_request/requestconfirm\').init({action:\'add\', componentid: '.$courserecord->id.', component:\'elearning\',componentname:\''.$courserecord->fullname.'\'}) })(event)" ><button class="cat_btn viewmore_btn"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>'.get_string('requestforenroll','local_classroom').'</button></a>';
        } else {
            $enrollmentbtn ='-';
        }
        $details['manage'] = 0;
        $details['status'] = (!is_null($userrecord->timecompleted)) ? get_string('completed', 'local_onlinetests'):get_string('pending', 'local_onlinetests');
        $details['enrolled'] = ($courserecord->timemodified) ? date('d-m-Y', $courserecord->timemodified):$enrollmentbtn;
        $details['completed'] = ($courserecord->timecompleted) ? date('d-m-Y', $courserecord->timecompleted): '-';
    }
    
    return $details;
}
function local_courses_request_dependent_query($aliasname){
    $returnquery = " WHEN ({$aliasname}.compname LIKE 'elearning') THEN (SELECT fullname from {course} WHERE id = {$aliasname}.componentid) ";
    return $returnquery;
}

function get_enrolledusers($courseid){
    global $DB, $USER, $OUTPUT, $CFG;

    $sql = "SELECT ue.id, u.id as userid, u.firstname, u.lastname, u.email, u.open_employeeid, 
            cc.timecompleted
            FROM {course} c
            JOIN {course_categories} cat ON cat.id = c.category
            JOIN {enrol} e ON e.courseid = c.id AND 
                        (e.enrol = 'manual' OR e.enrol = 'self') 
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
            JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
            JOIN {role_assignments} as ra ON ra.userid = u.id
            JOIN {context} AS cxt ON cxt.id=ra.contextid AND cxt.contextlevel = 50 AND cxt.instanceid=c.id
            JOIN {role} as r ON r.id = ra.roleid AND r.shortname = 'employee'
            LEFT JOIN {course_completions} as cc ON cc.course = c.id AND u.id = cc.userid 
            WHERE c.id = :courseid ";

    $params = array();
    $params['courseid'] = $courseid;

    $systemcontext = \context_system::instance();

    if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $sql .= " AND c.open_costcenterid = :costcenterid ";
        $params['costcenterid'] = $USER->open_costcenterid;
    }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $sql .= " AND c.open_costcenterid = :costcenterid AND c.open_departmentid = :departmentid ";
        $params['costcenterid'] = $USER->open_costcenterid;
        $params['departmentid'] = $USER->open_departmentid;
    }

    $courseusers = $DB->get_records_sql($sql , $params);

    $userslist = array();
    if($courseusers){
        $userslist['usersexists'] = true;
        $certificateid = $DB->get_field('course', 'open_certificateid', array('id'=>$courseid));
        if($certificateid){
            $userslist['certid'] = $certificateid;
        }else{
            $userslist['certid'] = null;
        }
        $userslist['courseid'] = $courseid;
        $userslist['configpath'] = $CFG->wwwroot;
        foreach ($courseusers as $enroluser) {
            $userinfo = array();
            $userinfo['userid'] = $enroluser->userid;
            $userinfo['employeename'] = $enroluser->firstname.' '.$enroluser->lastname;
            $userinfo['employeeid'] = $enroluser->open_employeeid;
            $userinfo['email'] = $enroluser->email;
            if($enroluser->timecompleted){
                $userinfo['completiondate'] = date('d/m/Y h:i a', $enroluser->timecompleted);
            }else{
                $userinfo['completiondate'] = null;
            }
            $userslist['userdata'][] = $userinfo;
        }
    }else{
        $userslist['usersexists'] = false;
    }

    echo $OUTPUT->render_from_template('local_courses/enrolledusersview', $userslist);

}

/**
 * Function to display the courses form in popup to add featured courses
 * returns data of the popup 
 */
function local_courses_output_fragment_featured_course($args) {
    global $CFG, $DB;

    $args = (object) $args;
   
    $context = $args->context;
    $featured_id = $args->featured_id;
    $o = '';
    $formdata = [];

    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    if (empty($formdata) && !empty($featured_id)) {
        $data = $DB->get_record('local_featured_courses', array('id'=>$featured_id));
        $formdata = new stdClass();
        $formdata->id = $data->id;
        $formdata->title = $data->title;  
        $formdata->course = $data->featured_course_ids;
        $formdata->learningpaths = $data->featured_lpath_ids;
    } 
    $params =  array('contextid'=> $context, 'title'=>$formdata->title, 'course' => $formdata->course,'featured_lpaths' => $formdata->learningpaths, 'featured_id' => $featured_id );

    $mform = new local_courses\form\featuredcourse_form(null,$params , 'post', '', null, true, (array)$formdata);
    
    $mform->set_data($formdata);
        
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
 * Function to display the courses form in popup to create course types
 * returns data of the popup 
 */
function local_courses_output_fragment_course_type($args) {
    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $coursetypeid = $args->coursetypeid;
    $o = '';
    $formdata = [];

    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    if (!empty($coursetypeid)) {
        $data = $DB->get_record('local_course_types', array('id'=>$coursetypeid));
        $formdata = new stdClass();
        $formdata->id = $data->id;  
        $formdata->course_type = $data->course_type;
        $formdata->shortname = $data->shortname;
	    $formdata->course_image   = $data->course_image;
    } 
 
    $params = array(
        'id' => $coursetypeid,
        'course_type' => $formdata->course_type,
        'shortname' => $formdata->shortname,
        'course_image' => $formdata->course_image,
        'contextid' => $context
    ); 
   
    $mform = new local_courses\form\coursetype_form(null, $params, 'post', '', null, true, (array)$formdata);
    $mform->set_data($formdata);
    
    if (!empty($args->jsonformdata)) {
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
    * function get_listof_coursetypes
    * @return  array coursetypes
*/
function get_listof_coursetypes($stable, $filterdata) {
    global $DB, $CFG, $OUTPUT, $PAGE ,$USER;
    
    $systemcontext = context_system::instance();
    $allcoursetypes=$DB->get_records('local_course_types');
    $coursesContext = array(
        "result" => $allcoursetypes );

    return $coursesContext;
}

/**
 * Function to display the courses form in popup to create course types
 * returns data of the popup 
 */
function local_courses_output_fragment_course_providers($args) {
    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $courseprovid = $args->courseprovid;
    $o = '';
    $formdata = [];

    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    if (!empty($courseprovid) /*&& empty($formdata)*/) {
        $data = $DB->get_record('local_course_providers', array('id'=>$courseprovid));
        $formdata = new stdClass();
        $formdata->id = $data->id;  
        $formdata->courseprov = $data->course_provider;
        $formdata->courseprovshortname = $data->shortname;
    } 
 
    $params = array(
        'id' => $courseprovid,
        'course_provider' => $formdata->course_provider,
        'shortname' => $formdata->shortname,
        'contextid' => $context
    ); 
    $mform = new local_courses\form\courseprovider_form(null, $params, 'post', '', null, true, (array)$formdata);
    $mform->set_data($formdata);
    
    if (!empty($args->jsonformdata)) {
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
    * function get_listof_courseproviders
    * @return  array courseproviders
*/
function get_listof_courseproviders($stable, $filterdata) {
    global $DB, $CFG, $OUTPUT, $PAGE ,$USER;
    
    $systemcontext = context_system::instance();
    $allcourseproviders=$DB->get_records('local_course_providers');
    $coursesContext = array(
        "result" => $allcourseproviders );

    return $coursesContext;
}


/** 
 * 
 */
function get_unenrolled_courses_list(){
    global $DB;
    $sql = 'SELECT uel.id as id,MAX(from_unixtime(uel.time )) as time, CONCAT(u.firstname,u.lastname) as username,
                c.fullname as coursename,ct.course_type as learningtype,uel.unenrol_reason,c.id as courseid,u.email
                FROM {user_enrolments_log} As uel 
                JOIN {user} AS u ON u.id = uel.userid 
                JOIN {course} AS c ON c.id = uel.courseid
                JOIN {local_course_types} As ct ON ct.id = c.open_identifiedas
                GROUP BY  uel.id,u.firstname,u.lastname,c.fullname,uel.unenrol_reason,c.id,u.email
                ORDER BY uel.id desc';

    $unenroldata = $DB->get_records_sql($sql); 
  
    return $unenroldata;
}


function local_courses_output_fragment_level_form($args) {

    global $CFG, $DB;

    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $o = '';
    $formdata = [];

    $o = '';
    if (!empty($args->jsonformdata)) {
        $serialiseddata = $args->jsonformdata;
        parse_str($serialiseddata, $formdata);
    }

    if (!empty($id)) {
        $data = $DB->get_record('local_levels', array('id'=>$id));
        $formdata = new stdClass();
        $formdata->id = $data->id;  
        $formdata->level = $data->name;
    } 
 
    $params = array(
        'id' => $id,
        'level' => $formdata->level,
        'contextid' => $context
    ); 
    $mform = new local_courses\form\level_form(null, $params, 'post', '', null, true, (array)$formdata);
    // print_r($formdata);die;
    $mform->set_data($formdata);
    
    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
    return $o;
}


function get_internal_certificates($userid){
    global $DB;
    $sql = " SELECT l.id, l.code,DATE_FORMAT(FROM_UNIXTIME(cc.timecompleted), '%Y-%m-%d') as timecompleted,c.fullname,ct.course_type 
        FROM {tool_certificate_issues} l
        JOIN {tool_certificate_templates} AS lc ON lc.id = l.templateid 
        JOIN {user} u ON l.userid = u.id 
        JOIN {course} c ON l.moduleid = c.id
        JOIN {local_course_types} ct ON ct.id = c.open_identifiedas
        LEFT JOIN {course_completions} as cc ON cc.course = c.id AND u.id = cc.userid 
        WHERE l.moduletype LIKE 'course' AND l.userid = :userid ";
    $certificates = $DB->get_records_sql($sql,array('userid' => $userid));

    return $certificates;

}

function get_learningsummary_content($coursetype , $filtervalues,$data_object, $stable){
    global $DB,$USER;  
    $systemcontext = context_system::instance();
    $course_type = $DB->get_field('local_course_types','shortname',array('id' => $data_object->id,'active' => 1));

    if($course_type == 'ilt'){
        $countsql =  "SELECT COUNT(lc.id) ";
        $sqlquery = "SELECT lc.id,lc.name AS fullname,lc.startdate,lc.enddate,lc.description ,'ilt' as open_identifiedas";
        $fromsql = " FROM {local_classroom} AS lc 
                JOIN {local_classroom_users} AS lcu ON lc.id=lcu.classroomid
                WHERE lcu.userid={$USER->id} and lc.visible=1 ";
        if ($data_object->blocktype == 'inprogress') {
            $fromsql .= " AND lc.status=1 ";
        }
        if ($data_object->blocktype == 'completed') {
            $fromsql .= " AND lc.status=4 ";
        }
        if(!empty($data_object->search_query)){
            $fromsql .= " AND ( lc.name LIKE '%". $data_object->search_query."%' ) ";
        }

        $allcoursecount = $DB->count_records_sql($countsql . $fromsql);
        $allcourses = $DB->get_records_sql($sqlquery . $fromsql, array(), $stable->start, $stable->length);
        //return $allcourses;
       
    }else if($course_type == 'learningpath'){

        $lp_params = [];

        $countsql =  "SELECT COUNT(llp.id) ";
        $lp_selectsql = "SELECT llp.id as id,llp.name as fullname,llp.description as summary,lla.userid as userid, 'learningpath' as open_identifiedas  ";
        $lp_fromsql = " FROM {local_learningplan} llp 
                        JOIN {local_learningplan_user}  lla ON lla.planid = llp.id 
                        WHERE llp.visible = 1 AND lla.userid = :userid ";

        if ($data_object->blocktype == 'inprogress') {
            $lp_fromsql .= " AND lla.completiondate IS NULL AND lla.status IS NULL ";
        }
        if ($data_object->blocktype == 'completed') {
            $lp_fromsql .= " AND lla.completiondate is NOT NULL AND status=1 AND lla.status IS NOT NULL  ";
        }
        $lp_params['userid'] = $USER->id;

        if(!empty($data_object->search_query)){
          $lp_fromsql .= " AND ( llp.name LIKE '%".  $data_object->search_query."%')";
        }
        $allcoursecount = $DB->count_records_sql($countsql . $lp_fromsql,$lp_params);
        $allcourses = $DB->get_records_sql($lp_selectsql.$lp_fromsql,$lp_params,$stable->start, $stable->length);
        //return $allcourses ;
        
    }else{
       
        $coursetype_sql = "SELECT id FROM {local_course_types} WHERE active = 1 AND shortname NOT IN ( 'ilt', 'learningpath' ) ";
        if($data_object->id != 0){
            $coursetype_sql .= " AND id = :typeid ";
        } 
        $coursetype = $DB->get_fieldset_sql($coursetype_sql,array('typeid' => $data_object->id));
        $coursetypes = implode(",",$coursetype);
     
        $courseparams = [];
        $countsql =  "SELECT COUNT(course.id ) ";
        $selectsql = "SELECT course.id as id,course.fullname as fullname,course.summary as summary,ue.userid as userid,course.open_identifiedas as open_identifiedas ";

        $fromsql = " FROM {course} AS course 
                        JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','auto')
                        JOIN {user_enrolments} ue ON e.id = ue.enrolid 
                        WHERE ue.userid = :userid AND course.visible = 1 AND course.open_identifiedas IN ( $coursetypes ) ";
            
        if ($data_object->blocktype == 'inprogress') {
            $fromsql .= " AND course.id NOT IN (SELECT course FROM {course_completions} WHERE course = course.id AND userid = {$USER->id} AND timecompleted IS NOT NULL)  ";
        }
        if ($data_object->blocktype == 'completed') {
            $fromsql .= " AND course.id  IN (SELECT course FROM {course_completions} WHERE course = course.id AND userid = {$USER->id} AND timecompleted IS NOT NULL)  ";
        }
        $courseparams['userid'] = $USER->id;
                                    
        if(!empty($data_object->search_query)){ 
            $fromsql .= " AND ( course.fullname LIKE '%". $data_object->search_query."%')";
        }
        $allcoursecount = $DB->count_records_sql($countsql . $fromsql,$courseparams);
        $allcourses = $DB->get_records_sql($selectsql.$fromsql,$courseparams,$stable->start, $stable->length);
        
        //return $allcourses ;  
        
    }
    try {
        $allcoursecount = $allcoursecount;
    } catch (dml_exception $ex) {
        $allcoursecount = 0;
    }
    return compact('allcourses', 'allcoursecount');
}

function local_courses_search_page_filter_element(&$filterelements){
    global $CFG;
    if(file_exists($CFG->dirroot.'/local/search/lib.php')){
        require_once($CFG->dirroot.'/local/search/lib.php');
        $filterelements['elearning'] = ['tagitemid' => 'moduletype_elearning', 'tagitemname' => 'Online Course', 'tagitemshortname' => 'elearning', 'coursecount' => local_search_get_coursecount_for_modules(['moduletype_elearning'])];
    }
}
function local_courses_enabled_search(){
    return ['pluginname' => 'local_courses', 'templatename' => 'local_courses/searchpagecontent', 'type' => elearning];
}
function  local_courses_applicable_filters_for_search_page(&$filterapplicable){
    $filterapplicable[elearning] = ['learningtype', 'status', 'categories', 'level', 'skillcategory','courseprovider'];
}


function local_courses_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
   
    if ($filearea !== 'course_image') {
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

    $file = $fs->get_file($context->id, 'local_courses', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }
    send_file($file, $filename, 0, $forcedownload, $options);
}


function course_img_path($itemid) {
   
        global $DB;
        $imgurl = false;
        $sql = "SELECT * FROM {files} WHERE itemid = :course_image AND component = 'local_courses' AND filearea = 'course_image' AND filename != '.' ORDER BY id DESC";
        $imgdata = $DB->get_record_sql($sql, array('course_image' => $itemid), 1);
      
    if (!empty($imgdata)) {
        // code...
        $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);

        $imgurl = $imgurl->out();
    } 
   
    return $imgurl;

}

function course_thumbimage($course){
    global $DB,$CFG;
     
    if(file_exists($CFG->dirroot.'/local/includes.php')){
        require_once($CFG->dirroot.'/local/includes.php');
        $includes = new \user_course_details();              
    }     
  
    if(file_exists($CFG->dirroot.'/local/includes.php')){                    
        $courseimage = $includes->course_summary_files($course);                           
        if(is_object($courseimage)){
            $imageurl = $courseimage->out();                    
        }else{
            $imageurl =  $courseimage;
        }  
    }
    
    return $imageurl;

}


/**
 * Change the navigation block and bar only for external users
 * Force course or activity navigation and modify CSS also
 * Please note that this function is only called in pages where the navigation block is present
 *
 * @global moodle_user $USER
 * @global moodle_database $DB
 * @param navigation_node $nav Current navigation object
 */
function local_courses_extend_navigation($nav) {
    global $CFG, $USER, $PAGE, $SESSION,$DB;

   // if (isset($USER) and isset($USER->auth) and strpos($USER->username, 'inbiz') === 0) {
    if (isset($USER) and isset($USER->auth) and (($USER->auth =="disprz") || $SESSION->disprzcourseid)) {
       
     // Force course or activity navigation.
    if ($SESSION->disprzcourseid) {
            $urltogo = '';
    if (! $moodlecontext = $DB->get_record('context', array('instanceid' => $SESSION->disprzcourseid,'contextlevel'=>50))) {
            print_error("invalidcontext");
     }

  
            if ($moodlecontext->contextlevel == CONTEXT_COURSE and $PAGE->course->id != $SESSION->disprzcourseid) {
                $urltogo = new moodle_url('/course/view.php', array('id' => $SESSION->disprzcourseid));
            } 

            
            if ($urltogo) {
          
            redirect($urltogo);
            }
        }

        // Delete all the navigation nodes except the course one. 
        if ($coursenode = $nav->find($PAGE->course->id, $nav::TYPE_COURSE)) {
            foreach (array('myprofile', 'users', 'site', 'home', 'myhome', 'mycourses', 'courses', '1') as $nodekey) {
                if ($node = $nav->get($nodekey)) {
                    $node->remove();
                }
            }
            $nav->children->add($coursenode);
        }

     
    }
}





