<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_doselect
 * @copyright  2019 Anilkumar Cheguri (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot.'/mod/doselect/classes/doselect.php');

define('DOSELECT_MAX_ATTEMPT_OPTION', 10);

/**
 * List of features supported in Doselect module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function doselect_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                    return false;
        case FEATURE_GROUPINGS:                 return false;
        case FEATURE_MOD_INTRO:                 return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:   return true;
        case FEATURE_COMPLETION_HAS_RULES:      return true;
        case FEATURE_GRADE_HAS_GRADE:           return true;
        case FEATURE_GRADE_OUTCOMES:            return false;
        case FEATURE_BACKUP_MOODLE2:            return true;
        case FEATURE_SHOW_DESCRIPTION:          return true;
        case FEATURE_CONTROLS_GRADE_VISIBILITY: return true;
        case FEATURE_USES_QUESTIONS:            return true;

        default: return null;
    }
}

// /**
//  * Returns all other caps used in module
//  * @return array
//  */
// function page_get_extra_capabilities() {
//     return array('moodle/site:accessallgroups');
// }

// *
//  * This function is used by the reset_course_userdata function in moodlelib.
//  * @param $data the data submitted from the reset course.
//  * @return array status array
 
// function page_reset_userdata($data) {
//     return array();
// }

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
//  */
// function page_get_view_actions() {
//     return array('view','view all');
// }

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
//  */
// function page_get_post_actions() {
//     return array('update', 'add');
// }

/**
 * Add doselect instance.
 * @param stdClass $data
 * @param mod_doselect_mod_form $mform
 * @return int new doselect instance id
 */
function doselect_add_instance($data, $mform = null) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;
    $doselect = new doselect();
    $testinfo = $doselect->doselect_assessmentslist();
    $doselectinfo = $testinfo[$data->doselect];

    $data->doselect = $doselectinfo->name;
    $data->doselect_slug = $doselectinfo->slug;
    $data->duration = $doselectinfo->duration;
    $data->total_test_score = $doselectinfo->total_test_score;
    $data->cutoff = $doselectinfo->cutoff;

    $data->timemodified = time();

    $data->id = $DB->insert_record('doselect', $data);

    // we need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    
    // for insert grade items and grade_grades table
    doselect_grade_item_update($data);
     return $data->id;
}

/**
 * Update doselect instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function doselect_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;
    $data->id    = $data->instance;

    $doselect = new doselect();
    $testinfo = $doselect->doselect_assessmentslist();
    
    $doselectinfo = $testinfo[$data->doselect];
    
    $data->doselect = $doselectinfo->name;
    $data->doselect_slug = $doselectinfo->slug;
    $data->duration = $doselectinfo->duration;
    $data->total_test_score = $doselectinfo->total_test_score;
    $data->cutoff = $doselectinfo->cutoff;

    $data->timemodified = time();
    
   $DB->update_record('doselect', $data);
    
    // for update grade items and grade_grades table
    doselect_grade_item_update($data);

    return true;
}

/**
 * Delete doselect instance.
 * @param int $id
 * @return bool true
 */
function doselect_delete_instance($id) {
    global $DB;

    if (!$doselect = $DB->get_record('doselect', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('doselect', array('id'=>$doselect->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main page display
 */
function doselect_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$doselect = $DB->get_record('doselect', array('id'=>$coursemodule->instance),
            'id, name, doselect, doselect_slug, duration, total_test_score, cutoff')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $doselect->name;

    $fullurl = "$CFG->wwwroot/mod/doslect/view.php?id=$coursemodule->id&amp;inpopup=1";

    return $info;
}


/**
 * Lists all browsable file areas
 *
 * @package  mod_doselect
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function doselect_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['description'] = get_string('description', 'doselect');
    return $areas;
}

/**
 * File browsing support for page module content area.
 *
 * @package  mod_doselect
 * @category files
 * @param stdClass $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function doselect_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'description') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_page', 'description', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_doselect', 'description', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/description/locallib.php");
        return new doselect_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: page_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the doselect files.
 *
 * @package  mod_doselect
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function doselect_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/doeselect:view', $context)) {
        return false;
    }

    if ($filearea !== 'description') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    // $arg could be revision number or index.html
    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // serve page content
        $filename = $arg;

        if (!$page = $DB->get_record('doeselect', array('id'=>$cm->instance), '*', MUST_EXIST)) {
            return false;
        }
    }
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function doselect_dndupload_handle($uploadinfo) {
    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->doselect = $uploadinfo->doselect;
    $data->doselect_slug = $uploadinfo->doselect_slug;
    $data->duration = $uploadinfo->duration;
    $data->total_test_score = $uploadinfo->total_test_score;
    $data->cutoff = $uploadinfo->cutoff;
    
    $data->description = clean_param($uploadinfo->description, PARAM_CLEANHTML);
    $data->coursemodule = $uploadinfo->coursemodule;

    // Set the display options to the site defaults.
    $config = get_config('doselect');

    return doselect_add_instance($data, null);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $page       page object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function doselect_view($doselect, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $doselect->id
    );

    $event = \mod_doselect\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('doselect', $doselect);
    $event->trigger();

    // Completion.
    // $completion = new completion_info($course);
    // $completion->set_module_viewed($cm);
}

 /*Function to submit the grades */ 
function doselect_grade_item_update($data,$grades=null) {
    global $DB,$CFG;
    require_once($CFG->libdir . '/gradelib.php');
   require_once($CFG->libdir . '/completionlib.php');

    $params = array('itemname' => $data->name);

     if($cm!==null){
        $params['idnumber']= $cm->id;
    }
  
     if ($data->maxgrade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $data->maxgrade;
        $params['grademin']  = 0;
        $params['gradepass'] = $data->gradepass;

    }
     else if($grades){
       $params['gradetype'] = GRADE_TYPE_VALUE;
     }
     else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }
    $params['gradetype'] = GRADE_TYPE_VALUE;

    $submitgrades=grade_update('mod/doselect', $data->course, 'mod', 'doselect', $data->id, 0, $grades, $params);

	if($grades) {
   		$cm = get_coursemodule_from_instance('doselect',$data->id,$data->course);
               /// Finally start processing of grades
   		$doselect = $DB->get_record('doselect', array('id'=>$cm->instance), '*', MUST_EXIST);
                $course=$DB->get_record('course',array('id'=>$data->course));
   		// Update completion state.
    		$completion = new completion_info($course);
               
    		if ($grades->rawgrade >= $doselect->gradepass) {
        		$completion->update_state($cm, COMPLETION_COMPLETE, $data->userid);
    		}
  	}

     return $submitgrades;
  } /*End of Grade Submission */

/**
 * Obtains the automatic completion state for this quiz on any conditions
 * in quiz settings, such as if all attempts are used or a certain grade is achieved.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function doselect_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    global $CFG;

    $doselect = $DB->get_record('doselect', array('id' => $cm->instance), '*', MUST_EXIST);
    if (!$doselect->gradepass) {
        return $type;
    }

      // Check for passing grade.
    if ($doselect->gradepass) {
        require_once($CFG->libdir . '/gradelib.php');
        $item = grade_item::fetch(array('courseid' => $course->id, 'itemtype' => 'mod',
                'itemmodule' => 'doselect', 'iteminstance' => $cm->instance, 'outcomeid' => null));
        if ($item) {
            $grades = grade_grade::fetch_users_grades($item, array($userid), false);        
            if (!empty($grades[$userid])) {
                $value=$grades[$userid]->is_passed($item);

                return $value;
            }
        }
    }
    return false;
}


