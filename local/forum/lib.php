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
 * @subpackage local_forum
 */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once(__DIR__ . '/deprecatedlib.php');
require_once($CFG->libdir.'/filelib.php');
//require_once($CFG->libdir.'/eventslib.php');

/// CONSTANTS ///////////////////////////////////////////////////////////

define('LOCAL_FORUM_MODE_FLATOLDEST', 1);
define('LOCAL_FORUM_MODE_FLATNEWEST', -1);
define('LOCAL_FORUM_MODE_THREADED', 2);
define('LOCAL_FORUM_MODE_NESTED', 3);

define('LOCAL_FORUM_CHOOSESUBSCRIBE', 0);
define('LOCAL_FORUM_FORCESUBSCRIBE', 1);
define('LOCAL_FORUM_INITIALSUBSCRIBE', 2);
define('LOCAL_FORUM_DISALLOWSUBSCRIBE',3);

/**
 * FORUM_TRACKING_OFF - Tracking is not available for this local_forum.
 */
define('LOCAL_FORUM_TRACKING_OFF', 0);

/**
 * FORUM_TRACKING_OPTIONAL - Tracking is based on user preference.
 */
define('LOCAL_FORUM_TRACKING_OPTIONAL', 1);

/**
 * FORUM_TRACKING_FORCED - Tracking is on, regardless of user setting.
 * Treated as FORUM_TRACKING_OPTIONAL if $CFG->local_forum_allowforcedreadtracking is off.
 */
define('LOCAL_FORUM_TRACKING_FORCED', 2);

define('LOCAL_FORUM_MAILED_PENDING', 0);
define('LOCAL_FORUM_MAILED_SUCCESS', 1);
define('LOCAL_FORUM_MAILED_ERROR', 2);

if (!defined('LOCAL_FORUM_CRON_USER_CACHE')) {
    /** Defines how many full user records are cached in local_forum cron. */
    define('LOCAL_FORUM_CRON_USER_CACHE', 5000);
}

/**
 * FORUM_POSTS_ALL_USER_GROUPS - All the posts in groups where the user is enrolled.
 */
define('LOCAL_FORUM_POSTS_ALL_USER_GROUPS', -2);

define('LOCAL_FORUM_DISCUSSION_PINNED', 1);
define('LOCAL_FORUM_DISCUSSION_UNPINNED', 0);

/// STANDARD FUNCTIONS ///////////////////////////////////////////////////////////

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $localforum add local_forum instance
 * @param local_forum_form $mform
 * @return int forum id
 */
function local_forum_add_instance($localforum, $mform = null) {
    global $CFG, $DB, $USER;

    $localforum->timemodified = time();
    $localforum->usermodified = $USER->id;
    if (empty($localforum->assessed)) {
        $localforum->assessed = 0;
    }

    if (empty($localforum->ratingtime) or empty($localforum->assessed)) {
        $localforum->assesstimestart  = 0;
        $localforum->assesstimefinish = 0;
    }
    if (is_array($localforum->costcenterid))
    $localforum->costcenterid = implode(',',$localforum->costcenterid);
    else
    $localforum->costcenterid = $localforum->costcenterid;
    
    if (is_array($localforum->departmentid))
    $localforum->departmentid = implode(',',$localforum->departmentid);
    else
    $localforum->departmentid = $localforum->departmentid;

    if (is_array($localforum->courseid))
        $localforum->courseid = implode(',',$localforum->courseid);
    else {
        if ($localforum->courseid)
            $localforum->courseid = $localforum->courseid;
        else
             $localforum->courseid = null;
    }
    $introeditor = $localforum->introeditor;
    unset($localforum->introeditor);
    $localforum->intro       = $introeditor['text'];
    $localforum->introformat = $introeditor['format'];
    $localforum->id = $DB->insert_record('local_forum', $localforum);
    $context = context_system::instance();

    if ($localforum->type == 'single') {  // Create related discussion.
        $discussion = new stdClass();
        $discussion->course        = 0;
        $discussion->forum   = $localforum->id;
        $discussion->name          = $localforum->name;
        $discussion->assessed      = $localforum->assessed;
        $discussion->message       = $localforum->intro;
        $discussion->messageformat = $localforum->introformat;
        $discussion->messagetrust  =null;
        $discussion->mailnow       = false;
        $discussion->groupid       = -1;

        $message = '';

        $discussion->id = local_forum_add_discussion($discussion, null, $message);

        if ($mform and $draftid = file_get_submitted_draft_itemid('introeditor')) {
            // Ugly hack - we need to copy the files somehow.
            $discussion = $DB->get_record('local_forum_discussions', array('id'=>$discussion->id), '*', MUST_EXIST);
            $post = $DB->get_record('local_forum_posts', array('id'=>$discussion->firstpost), '*', MUST_EXIST);

            $options = array('subdirs'=>true); // Use the same options as intro field!
            $post->message = file_save_draft_area_files($draftid, $context->id, 'local_forum', 'post', $post->id, $options, $post->message);
            $DB->set_field('local_forum_posts', 'message', $post->message, array('id'=>$post->id));
        }
    }
    // force subscription to users if group is selected

   /*  if (!is_null($localforum->local_group)) {
    $groupid = $DB->get_field('local_groups', 'cohortid', array('id'=>$localforum->local_group));
    } */
    local_forum_instance_created($context, $localforum, $groupid);
    
    // Update forum tags.
 /*    if (isset($localforum->tags)) {
        local_tags_tag::set_item_tags('local_forum', 'forum', $localforum->id, context_system::instance(), $localforum->tags, 0, $localforum->costcenterid, $localforum->departmentid);
    } */

    // Trigger forum created event.
    $params = array(
        'context' => $context,
        'objectid' => $localforum->id
    );

    $event = \local_forum\event\forum_created::create($params);
    $event->add_record_snapshot('local_forum', $localforum);
    $event->trigger();

    return $localforum->id;
}

/**
 * Handle changes following the creation of a local_forum instance.
 * This function is typically called by the course_module_created observer.
 *
 * @param object $context the local_forum context
 * @param stdClass $localforum The local_forum object
 * @return void
 */
function local_forum_instance_created($context, $localforum, $groupid) {
    if ($localforum->forcesubscribe == LOCAL_FORUM_INITIALSUBSCRIBE OR $localforum->forcesubscribe == LOCAL_FORUM_FORCESUBSCRIBE OR $groupid) {
        $users = \local_forum\subscriptions::get_potential_subscribers($context, $localforum, $groupid, 'u.id, u.email');

        foreach ($users as $user) {
            \local_forum\subscriptions::subscribe_user($user->id, $localforum, $context);
        }
    }
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $localforum local_forum instance (with magic quotes)
 * @return bool success
 */
function local_forum_update_instance($localforum, $mform) {
    global $DB, $OUTPUT, $USER;
    $context = context_system::instance();
    $localforum->timemodified = time();
    $localforum->usermodified = $USER->id;
    $localforum->id           = $localforum->id;
    
    $introeditor = $localforum->introeditor;
    unset($localforum->introeditor);
    $localforum->intro       = $introeditor['text'];
    $localforum->introformat = $introeditor['format'];
    
    if (is_array($localforum->costcenterid))
    $localforum->costcenterid = implode(',',$localforum->costcenterid);
    else
    $localforum->costcenterid = $localforum->costcenterid;
    
    if (is_array($localforum->departmentid))
    $localforum->departmentid = implode(',',$localforum->departmentid);
    else {
        if ($localforum->departmentid)
        $localforum->departmentid = $localforum->departmentid;
        else
        $localforum->departmentid = null;
    }

    if (is_array($localforum->courseid))
         $localforum->courseid = implode(',',$localforum->courseid);
    else {
        if ($localforum->courseid)
        $localforum->courseid = $localforum->courseid;
        else
        $localforum->courseid = null;
    }
    if (empty($localforum->assessed)) {
        $localforum->assessed = 0;
    }

    if (empty($localforum->ratingtime) or empty($localforum->assessed)) {
        $localforum->assesstimestart  = 0;
        $localforum->assesstimefinish = 0;
    }

    $oldlocal_forum = $DB->get_record('local_forum', array('id'=>$localforum->id));

    if ($localforum->type == 'single') {  // Update related discussion and post.
        $discussions = $DB->get_records('local_forum_discussions', array('forum'=>$localforum->id), 'timemodified ASC');
        if (!empty($discussions)) {
            if (count($discussions) > 1) {
                echo $OUTPUT->notification(get_string('warnformorepost', 'local_forum'));
            }
            $discussion = array_pop($discussions);
        } else {
            // try to recover by creating initial discussion - MDL-16262
            $discussion = new stdClass();
            $discussion->course          = $localforum->course;
            $discussion->forum    = $localforum->id;
            $discussion->name            = $localforum->name;
            $discussion->assessed        = $localforum->assessed;
            $discussion->message         = $localforum->intro;
            $discussion->messageformat   = $localforum->introformat;
            $discussion->messagetrust    = true;
            $discussion->mailnow         = false;
            $discussion->groupid         = -1;

            $message = '';

            local_forum_add_discussion($discussion, null, $message);

            if (! $discussion = $DB->get_record('local_forum_discussions', array('local_forum'=>$localforum->id))) {
                print_error('cannotadd', 'local_forum');
            }
        }
        if (! $post = $DB->get_record('local_forum_posts', array('id'=>$discussion->firstpost))) {
            print_error('cannotfindfirstpost', 'local_forum');
        }

        $post = $DB->get_record('local_forum_posts', array('id'=>$discussion->firstpost), '*', MUST_EXIST);
        $post->subject       = $localforum->name;
        $post->message       = $localforum->intro;
        $post->messageformat = $localforum->introformat;
        $post->messagetrust  = trusttext_trusted($context);
        $post->modified      = $localforum->timemodified;
        $post->userid        = $USER->id;    // MDL-18599, so that current teacher can take ownership of activities.

        if ($mform and $draftid = file_get_submitted_draft_itemid('introeditor')) {
            // Ugly hack - we need to copy the files somehow.
            $options = array('subdirs'=>true); // Use the same options as intro field!
            $post->message = file_save_draft_area_files($draftid, $context->id, 'local_forum', 'post', $post->id, $options, $post->message);
        }

        $DB->update_record('local_forum_posts', $post);
        $discussion->name = $localforum->name;
        $DB->update_record('local_forum_discussions', $discussion);
    }
    if ($localforum->form_status == 0) {
        if (empty($localforum->local_group)) {
            $localforum->local_group = null;
        }
    }
    $DB->update_record('local_forum', $localforum);

   /*  if (!is_null($oldlocal_forum->local_group)) {
    $groupid = $DB->get_field('local_groups', 'cohortid', array('id'=>$oldlocal_forum->local_group));
    } */
    local_forum_instance_created($context, $localforum, $groupid);

    // Update forum tags.
   /*  if (isset($localforum->tags)) {
        local_tags_tag::set_item_tags('local_forum', 'forum', $localforum->id, context_system::instance(), $localforum->tags, 0, $localforum->costcenterid, $localforum->departmentid);
    } */

    // Trigger forum created event.
    $params = array(
        'context' => $context,
        'objectid' => $localforum->id
    );

    $event = \local_forum\event\forum_updated::create($params);
    $event->add_record_snapshot('local_forum', $localforum);
    $event->trigger();

    return $localforum->id;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id local_forum instance id
 * @return bool success
 */
function local_forum_delete_instance($id) {
    global $DB;

    if (!$localforum = $DB->get_record('local_forum', array('id'=>$id))) {
        return false;
    }

    $context = context_system::instance();

    $result = true;

    // Delete digest and subscription preferences.
    $DB->delete_records('local_forum_digests', array('forum' => $localforum->id));
    $DB->delete_records('local_forum_subscriptions', array('forum'=>$localforum->id));
    $DB->delete_records('local_forum_discussion_subs', array('forum' => $localforum->id));

    if ($discussions = $DB->get_records('local_forum_discussions', array('forum'=>$localforum->id))) {
        foreach ($discussions as $discussion) {
            if (!local_forum_delete_discussion($discussion, true, $localforum)) {
                $result = false;
            }
        }
    }

    local_forum_tp_delete_read_records(-1, -1, -1, $localforum->id);

    if (!$DB->delete_records('local_forum', array('id'=>$localforum->id))) {
        $result = false;
    }
    // Trigger forum deleted event.

    $params = array(
        'context' => $context,
        'objectid' => $localforum->id
    );

    $event = \local_forum\event\forum_deleted::create($params);
    $event->add_record_snapshot('local_forum', $localforum);
    $event->trigger();

    return $result;
}


/**
 * this will return sql statement

 * @param $context int contexid of evaluation 
 * @return string
 */
function depsql($context) {
    global $DB, $USER;
    if ( has_capability('local/costcenter:manage_multiorganizations', $context ) ) {
        $costcenters = $DB->get_records_sql_menu('select fullname,id from {local_costcenter} where parentid = 0 ');
        $mycostcenters = implode(',', $costcenters);
        $sql =" and costcenterid IN( $mycostcenters )";
    } elseif(has_capability('local/costcenter:manage_ownorganization',$context)) {
        $costcenter = $DB->get_record_sql("SELECT cc.id, cc.parentid FROM {user} u JOIN {local_costcenter} cc ON u.open_costcenterid = cc.id WHERE u.id={$USER->id}");
        if ($costcenter->parentid == 0) {
            $sql =" and costcenterid IN( $costcenter->id )";
        } else {
            // $sql =" and ( find_in_set($costcenter->id, departmentid) <> 0)  ";
            $sql =" and CONCAT(',',departmentid,',') LIKE CONCAT('%,',$costcenter->id,',%') ";
        }
    } else {
        // $sql =" and ( find_in_set($USER->open_departmentid, departmentid) <> 0)  ";
        $sql =" and CONCAT(',',departmentid,',') LIKE CONCAT('%,',$USER->open_departmentid,',%') ";
    }
    return $sql;
}

/**
 * user forums

 * @param $context system context 
 * @return $object list of forums
 */
function local_get_forums($context) {
    global $USER, $DB;
    
    if (has_capability('local/costcenter:manage_multiorganizations', $context ) OR is_siteadmin()) {
        $sql ="SELECT f.*, d.maildigest FROM {local_forum} f
        LEFT JOIN {local_forum_digests} d ON d.forum = f.id AND d.userid = ?
        WHERE f.course = ? ORDER BY f.id DESC
        ";
        $forums = $DB->get_records_sql($sql, array($USER->id, 0));
   
    } else if ( has_capability('local/costcenter:manage_ownorganization',$context) OR has_capability('local/costcenter:manage_owndepartments',$context)) {
        $deptsql = depsql($context);
        $sql ="SELECT f.*, d.maildigest FROM {local_forum} f
        LEFT JOIN {local_forum_digests} d ON d.forum = f.id AND d.userid = ?
        WHERE f.course = ? $deptsql ORDER BY f.id DESC";
        $forums = $DB->get_records_sql($sql, array($USER->id, 0));
   
    } else { // check for users
        // get user local groups
        
     /*    $assigned_groups = $DB->get_records_sql_menu("SELECT cm.id as cmid, g.id as gid from {cohort} c, {local_groups} g, {cohort_members} cm where g.cohortid = c.id AND cm.cohortid = c.id AND cm.userid = $USER->id");
        if (empty($assigned_groups))
            $assigned_groups = array(0=>0);

        $usergroupcount = sizeof($assigned_groups);
 */
        //pending will check it later
        $sql ="SELECT f.* FROM {local_forum} f        
        JOIN {user} u ON  u.open_costcenterid = f.costcenterid 
        AND (f.departmentid = u.open_departmentid OR f.departmentid IS NULL) 
        AND u.id= ? ORDER BY f.id DESC";
        $forumslist = $DB->get_records_sql($sql, array($USER->id));
        $data = array();
        foreach ($forumslist as $key => $forum) {
            $data[$key] = $forum;
         /*   if($forum->departmentid || $forum->local_group){
                // if($forum->departmentid == $USER->open_departmentid){
                //     $data[$key] = $forum;
                // }
                if(in_array($forum->local_group, $assigned_groups)){
                    $data[$key] = $forum;
                // }else{
                //     unset($data[$key]);
                }
           }else{
                $data[$key] = $forum;
           } */
           
        }
        $forums = $data;
        // $sql ="SELECT f.* FROM {local_forum} f        
        // JOIN {user} u ON  u.open_costcenterid = f.costcenterid AND u.id= ?
        // AND 
        // case
        // when f.departmentid is NULL then f.departmentid is NULL
        // else
        // find_in_set( u.open_departmentid, f.departmentid )
        // end";
        
        // $sql .= " AND ";
        // $sql .= " (";
        // $i = 1;
        // foreach ($assigned_groups as $key=>$assigned_group) {
        //     $sql .= " (case
        //     when f.local_group is not NULL then find_in_set($assigned_group, f.local_group)
        //     else f.local_group is NULL AND u.open_costcenterid = f.costcenterid
        //     end ) 
        //     ";
        //     if ($usergroupcount > $i)
        //     $sql .= " OR ";
        //     $i++;
        // }
        // $sql .= " )";
        // $j = 1;
        // $sql .= " UNION ( SELECT f.* FROM {local_forum} f        
        // where ";
        // $sql .= " ("; // start case brace
        // foreach ($assigned_groups as $key=>$assigned_group) {
        //     $sql .= " (case
        //     when f.local_group is not NULL then find_in_set($assigned_group, f.local_group)
        //     end
        //     )
        //     ";
        //     if ($usergroupcount > $j)
        //     $sql .= " OR ";
        //     $j++;
        // }
        // $sql .= " )"; // end case brace
        // $sql .= " )"; // union brace
        // $sql .= " ORDER BY id DESC";

        // $forums = $DB->get_records_sql($sql, array($USER->id));
    }
    return $forums;
}

/**
 * Serve the new forum form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_forum_output_fragment_new_forum_form($args) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/local/forum/forum_form.php');
    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $form_status = $args->form_status;
    $o = '';
 
    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = ($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $data = new stdclass();
    if ($id > 0) {
		$data = $DB->get_record('local_forum', array('id'=>$id));
	}
    $params = array('id' => $id, 'form_status'=>$form_status);
    $mform = new forum_form(null, $params, 'post', '', null, true, $formdata);
    // Used to set the courseid.
    if (is_object($data)) {
		$data->introeditor['text'] = $data->intro;
        $data->departmentid = explode(',',$data->departmentid);
        // Populate tags.
        //$data->tags = local_tags_tag::get_item_tags_array('local_forum', 'forum', $id);
		$default_values = (array)$data;
		$mform->data_preprocessing($default_values);
	}

	$mform->set_data($default_values);
    
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
 * Indicates API features that the local_forum supports.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function local_forum_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_RATE:                    return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_PLAGIARISM:              return true;

        default: return null;
    }
}

/**
 * Create a message-id string to use in the custom headers of local_forum notification emails
 *
 * message-id is used by email clients to identify emails and to nest conversations
 *
 * @param int $postid The ID of the local_forum post we are notifying the user about
 * @param int $usertoid The ID of the user being notified
 * @return string A unique message-id
 */
function local_forum_get_email_message_id($postid, $usertoid) {
    return generate_email_messageid(hash('sha256', $postid . 'to' . $usertoid));
}

/**
 * Removes properties from user record that are not necessary
 * for sending post notifications.
 * @param stdClass $user
 * @return void, $user parameter is modified
 */
function local_forum_cron_minimise_user_record(stdClass $user) {

    // We store large amount of users in one huge array,
    // make sure we do not store info there we do not actually need
    // in mail generation code or messaging.

    unset($user->institution);
    unset($user->department);
    unset($user->address);
    unset($user->city);
    unset($user->url);
    unset($user->currentlogin);
    unset($user->description);
    unset($user->descriptionformat);
}

/**
 * Function to be run periodically according to the scheduled task.
 *
 * Finds all posts that have yet to be mailed out, and mails them
 * out to all subscribers as well as other maintance tasks.
 *
 * NOTE: Since 2.7.2 this function is run by scheduled task rather
 * than standard cron.
 *
 * @todo MDL-44734 The function will be split up into seperate tasks.
 */
function local_forum_cron() {
    global $CFG, $USER, $DB, $PAGE;
    $context = context_system::instance();
    $site = get_site();

    // The main renderers.
    $htmlout = $PAGE->get_renderer('local_forum', 'email', 'htmlemail');
    $textout = $PAGE->get_renderer('local_forum', 'email', 'textemail');
    $htmldigestfullout = $PAGE->get_renderer('local_forum', 'emaildigestfull', 'htmlemail');
    $textdigestfullout = $PAGE->get_renderer('local_forum', 'emaildigestfull', 'textemail');
    $htmldigestbasicout = $PAGE->get_renderer('local_forum', 'emaildigestbasic', 'htmlemail');
    $textdigestbasicout = $PAGE->get_renderer('local_forum', 'emaildigestbasic', 'textemail');

    // All users that are subscribed to any post that needs sending,
    // please increase $CFG->extramemorylimit on large sites that
    // send notifications to a large number of users.
    $users = array();
    $userscount = 0; // Cached user counter - count($users) in PHP is horribly slow!!!

    // Status arrays.
    $mailcount  = array();
    $errorcount = array();

    // caches
    $discussions        = array();
    $localforums             = array();
    $courses            = array();
    $coursemodules      = array();
    $subscribedusers    = array();
    $messageinboundhandlers = array();

    // Posts older than 2 days will not be mailed.  This is to avoid the problem where
    // cron has not been running for a long time, and then suddenly people are flooded
    // with mail from the past few weeks or months
    $timenow   = time();
    $endtime   = $timenow - $CFG->maxeditingtime;
    $starttime = $endtime - 48 * 3600;   // Two days earlier

    // Get the list of local_forum subscriptions for per-user per-local_forum maildigest settings.
    $digestsset = $DB->get_recordset('local_forum_digests', null, '', 'id, userid, forum, maildigest');
    $digests = array();
    foreach ($digestsset as $thisrow) {
        if (!isset($digests[$thisrow->forum])) {
            $digests[$thisrow->forum] = array();
        }
        $digests[$thisrow->forum][$thisrow->userid] = $thisrow->maildigest;
    }
    $digestsset->close();

    // Create the generic messageinboundgenerator.
    $messageinboundgenerator = new \core\message\inbound\address_manager();
    $messageinboundgenerator->set_handler('\local_forum\message\inbound\reply_handler');

    if ($posts = local_forum_get_unmailed_posts($starttime, $endtime, $timenow)) {
        // Mark them all now as being mailed.  It's unlikely but possible there
        // might be an error later so that a post is NOT actually mailed out,
        // but since mail isn't crucial, we can accept this risk.  Doing it now
        // prevents the risk of duplicated mails, which is a worse problem.

        if (!local_forum_mark_old_posts_as_mailed($endtime)) {
            mtrace('Errors occurred while trying to mark some posts as being mailed.');
            return false;  // Don't continue trying to mail them, in case we are in a cron loop
        }

        // checking post validity, and adding users to loop through later
        foreach ($posts as $pid => $post) {

            $discussionid = $post->discussion;
            if (!isset($discussions[$discussionid])) {
                if ($discussion = $DB->get_record('local_forum_discussions', array('id'=> $post->discussion))) {
                    $discussions[$discussionid] = $discussion;
                    \local_forum\subscriptions::fill_subscription_cache($discussion->forum);
                    \local_forum\subscriptions::fill_discussion_subscription_cache($discussion->forum);

                } else {
                    mtrace('Could not find discussion ' . $discussionid);
                    unset($posts[$pid]);
                    continue;
                }
            }
            $localforumid = $discussions[$discussionid]->forum;
            if (!isset($localforums[$localforumid])) {
                if ($localforum = $DB->get_record('local_forum', array('id' => $localforumid))) {
                    $localforums[$localforumid] = $localforum;
                } else {
                    mtrace('Could not find local_forum '.$localforumid);
                    unset($posts[$pid]);
                    continue;
                }
            }
          

            // Save the Inbound Message datakey here to reduce DB queries later.
            $messageinboundgenerator->set_data($pid);
            $messageinboundhandlers[$pid] = $messageinboundgenerator->fetch_data_key();

            // Caching subscribed users of each local_forum.
            if (!isset($subscribedusers[$localforumid])) {
                if ($subusers = \local_forum\subscriptions::fetch_subscribed_users($localforums[$localforumid], 0, $context, 'u.*', true)) {

                    foreach ($subusers as $postuser) {
                        // this user is subscribed to this local_forum
                        $subscribedusers[$localforumid][$postuser->id] = $postuser->id;
                        $userscount++;
                        if ($userscount > LOCAL_FORUM_CRON_USER_CACHE) {
                            // Store minimal user info.
                            $minuser = new stdClass();
                            $minuser->id = $postuser->id;
                            $users[$postuser->id] = $minuser;
                        } else {
                            // Cache full user record.
                            local_forum_cron_minimise_user_record($postuser);
                            $users[$postuser->id] = $postuser;
                        }
                    }
                    // Release memory.
                    unset($subusers);
                    unset($postuser);
                }
            }
            $mailcount[$pid] = 0;
            $errorcount[$pid] = 0;
        }
    }

    if ($users && $posts) {

        foreach ($users as $userto) {
            // Terminate if processing of any account takes longer than 2 minutes.
            core_php_time_limit::raise(120);

            mtrace('Processing user ' . $userto->id);

            // Init user caches - we keep the cache for one cycle only, otherwise it could consume too much memory.
            if (isset($userto->username)) {
                $userto = clone($userto);
            } else {
                $userto = $DB->get_record('user', array('id' => $userto->id));
                local_forum_cron_minimise_user_record($userto);
            }
            $userto->viewfullnames = array();
            $userto->canpost       = array();
            $userto->markposts     = array();

            foreach ($posts as $pid => $post) {
                $discussion = $discussions[$post->discussion];
                $localforum      = $localforums[$discussion->forum];

                // Do some checks to see if we can bail out now.

                // Only active enrolled users are in the list of subscribers.
                // This does not necessarily mean that the user is subscribed to the local_forum or to the discussion though.
                if (!isset($subscribedusers[$localforum->id][$userto->id])) {
                    // The user does not subscribe to this local_forum.
                    continue;
                }

                if (!\local_forum\subscriptions::is_subscribed($userto->id, $localforum, $post->discussion)) {
                    // The user does not subscribe to this local_forum, or to this specific discussion.
                    continue;
                }

                if ($subscriptiontime = \local_forum\subscriptions::fetch_discussion_subscription($localforum->id, $userto->id)) {
                    // Skip posts if the user subscribed to the discussion after it was created.
                    if (isset($subscriptiontime[$post->discussion]) && ($subscriptiontime[$post->discussion] > $post->created)) {
                        continue;
                    }
                }

                // Don't send email if the local_forum is Q&A and the user has not posted.
                // Initial topics are still mailed.
                if ($localforum->type == 'qanda' && !local_forum_get_user_posted_time($discussion->id, $userto->id) && $pid != $discussion->firstpost) {
                    mtrace('Did not email ' . $userto->id.' because user has not posted in discussion');
                    continue;
                }

                // Get info about the sending user.
                if (array_key_exists($post->userid, $users)) {
                    // We might know the user already.
                    $userfrom = $users[$post->userid];
                    if (!isset($userfrom->idnumber)) {
                        // Minimalised user info, fetch full record.
                        $userfrom = $DB->get_record('user', array('id' => $userfrom->id));
                        local_forum_cron_minimise_user_record($userfrom);
                    }

                } else if ($userfrom = $DB->get_record('user', array('id' => $post->userid))) {
                    local_forum_cron_minimise_user_record($userfrom);
                    // Fetch only once if possible, we can add it to user list, it will be skipped anyway.
                    if ($userscount <= LOCAL_FORUM_CRON_USER_CACHE) {
                        $userscount++;
                        $users[$userfrom->id] = $userfrom;
                    }
                } else {
                    mtrace('Could not find user ' . $post->userid . ', author of post ' . $post->id . '. Unable to send message.');
                    continue;
                }

                // Note: If we want to check that userto and userfrom are not the same person this is probably the spot to do it.

                // Fill caches.
                if (!isset($userto->viewfullnames[$localforum->id])) {
                    //$modcontext = context_module::instance($cm->id);
                    $userto->viewfullnames[$localforum->id] = has_capability('moodle/site:viewfullnames', $context);
                }
                if (!isset($userto->canpost[$discussion->id])) {
                    //$modcontext = context_module::instance($cm->id);
                    $userto->canpost[$discussion->id] = local_forum_user_can_post($localforum, $discussion, $userto, $context);
                }
                
                // Make sure we're allowed to see the post.
                if (!local_forum_user_can_see_post($localforum, $discussion, $post, null)) {
                    mtrace('User ' . $userto->id .' can not see ' . $post->id . '. Not sending message.');
                    continue;
                }

                // OK so we need to send the email.

                // Does the user want this post in a digest?  If so postpone it for now.
                $maildigest = local_forum_get_user_maildigest_bulk($digests, $userto, $localforum->id);

                if ($maildigest > 0) {
                    // This user wants the mails to be in digest form.
                    $queue = new stdClass();
                    $queue->userid       = $userto->id;
                    $queue->discussionid = $discussion->id;
                    $queue->postid       = $post->id;
                    $queue->timemodified = $post->created;
                    $DB->insert_record('local_forum_queue', $queue);
                    continue;
                }

                // Prepare to actually send the post now, and build up the content.

                $cleanlocal_forumname = str_replace('"', "'", \local_costcenter\lib::strip_tags_custom(format_string($localforum->name)));

                $userfrom->customheaders = array (
                    // Headers to make emails easier to track.
                    'List-Id: "'        . $cleanlocal_forumname . '" ' . generate_email_messageid('moodlelocal_forum' . $localforum->id),
                    'List-Help: '       . $CFG->wwwroot . '/local/forum/view.php?f=' . $localforum->id,
                    'Message-ID: '      . local_forum_get_email_message_id($post->id, $userto->id),
                    'X-Course-Id: '     . $localforum->id,
                    'X-Course-Name: '   . format_string($localforum->name, true),

                    // Headers to help prevent auto-responders.
                    'Precedence: Bulk',
                    'X-Auto-Response-Suppress: All',
                    'Auto-Submitted: auto-generated',
                );
                $shortname = $localforum->name;

                // Generate a reply-to address from using the Inbound Message handler.
                $replyaddress = null;
                if ($userto->canpost[$discussion->id] && array_key_exists($post->id, $messageinboundhandlers)) {
                    $messageinboundgenerator->set_data($post->id, $messageinboundhandlers[$post->id]);
                    $replyaddress = $messageinboundgenerator->generate($userto->id);
                }

                if (!isset($userto->canpost[$discussion->id])) {
                    $canreply = local_forum_user_can_post($localforum, $discussion, $userto, $context);
                } else {
                    $canreply = $userto->canpost[$discussion->id];
                }

                $data = new \local_forum\output\forum_post_email(
                        $localforum,
                        $discussion,
                        $post,
                        $userfrom,
                        $userto,
                        $canreply
                    );

                $userfrom->customheaders[] = sprintf('List-Unsubscribe: <%s>',
                    $data->get_unsubscribediscussionlink());

                if (!isset($userto->viewfullnames[$localforum->id])) {
                    $data->viewfullnames = has_capability('moodle/site:viewfullnames', $context, $userto->id);
                } else {
                    $data->viewfullnames = $userto->viewfullnames[$localforum->id];
                }

                // Not all of these variables are used in the default language
                // string but are made available to support custom subjects.
                $a = new stdClass();
                $a->subject = $data->get_subject();
                $a->forumname = $cleanlocal_forumname;
                $a->sitefullname = format_string($site->fullname);
                $a->siteshortname = format_string($site->shortname);
                $a->courseshortname = $cleanlocal_forumname;
                $postsubject = html_to_text(get_string('postmailsubject', 'local_forum', $a), 0);

                $rootid = local_forum_get_email_message_id($discussion->firstpost, $userto->id);

                if ($post->parent) {
                    // This post is a reply, so add reply header (RFC 2822).
                    $parentid = local_forum_get_email_message_id($post->parent, $userto->id);
                    $userfrom->customheaders[] = "In-Reply-To: $parentid";

                    // If the post is deeply nested we also reference the parent message id and
                    // the root message id (if different) to aid threading when parts of the email
                    // conversation have been deleted (RFC1036).
                    if ($post->parent != $discussion->firstpost) {
                        $userfrom->customheaders[] = "References: $rootid $parentid";
                    } else {
                        $userfrom->customheaders[] = "References: $parentid";
                    }
                }

                // MS Outlook / Office uses poorly documented and non standard headers, including
                // Thread-Topic which overrides the Subject and shouldn't contain Re: or Fwd: etc.
                $a->subject = $discussion->name;
                $threadtopic = html_to_text(get_string('postmailsubject', 'local_forum', $a), 0);
                $userfrom->customheaders[] = "Thread-Topic: $threadtopic";
                $userfrom->customheaders[] = "Thread-Index: " . substr($rootid, 1, 28);
                // Send the post now!
                mtrace('Sending ', '');
                $eventdata = new \core\message\message();
                $eventdata->courseid            = 1;
                $eventdata->component           = 'local_forum';
                $eventdata->name                = 'posts';
                $eventdata->userfrom            = $userfrom;
                $eventdata->userto              = $userto;
                $eventdata->subject             = $postsubject;
                $eventdata->fullmessage         = $textout->render($data);
                $eventdata->fullmessageformat   = FORMAT_PLAIN;
                $eventdata->fullmessagehtml     = $htmlout->render($data);
                $eventdata->notification        = 1;
                $eventdata->replyto             = $replyaddress;
                if (!empty($replyaddress)) {
                    // Add extra text to email messages if they can reply back.
                    $textfooter = "\n\n" . get_string('replytopostbyemail', 'local_forum');
                    $htmlfooter = html_writer::tag('p', get_string('replytopostbyemail', 'local_forum'));
                    $additionalcontent = array('fullmessage' => array('footer' => $textfooter),
                                     'fullmessagehtml' => array('footer' => $htmlfooter));
                    $eventdata->set_additional_content('email', $additionalcontent);
                }

                $smallmessagestrings = new stdClass();
                $smallmessagestrings->user          = fullname($userfrom);
                $smallmessagestrings->forumname     = "$shortname: " . format_string($localforum->name, true) . ": " . $discussion->name;
                $smallmessagestrings->message       = $post->message;

                // Make sure strings are in message recipients language.
                $eventdata->smallmessage = get_string_manager()->get_string('smallmessage', 'local_forum', $smallmessagestrings, $userto->lang);

                $contexturl = new moodle_url('/local/forum/discuss.php', array('d' => $discussion->id), 'p' . $post->id);
                $eventdata->contexturl = $contexturl->out();
                $eventdata->contexturlname = $discussion->name;

                $mailresult = message_send($eventdata);
                if (!$mailresult) {
                    mtrace("Error: local/forum/lib.php local_forum_cron(): Could not send out mail for id $post->id to user $userto->id".
                            " ($userto->email) .. not trying again.");
                    $errorcount[$post->id]++;
                } else {
                    $mailcount[$post->id]++;

                    // Mark post as read if local_forum_usermarksread is set off.
                    if (!$CFG->local_forum_usermarksread) {
                        $userto->markposts[$post->id] = $post->id;
                    }
                }

                mtrace('post ' . $post->id . ': ' . $post->subject);
            }

            // Mark processed posts as read.
            if (get_user_preferences('local_forum_markasreadonnotification', 1, $userto->id) == 1) {
                local_forum_tp_mark_posts_read($userto, $userto->markposts);
            }

            unset($userto);
        }
    }

    if ($posts) {
        foreach ($posts as $post) {
            mtrace($mailcount[$post->id]." users were sent post $post->id, '$post->subject'");
            if ($errorcount[$post->id]) {
                $DB->set_field('local_forum_posts', 'mailed', LOCAL_FORUM_MAILED_ERROR, array('id' => $post->id));
            }
        }
    }

    // release some memory
    unset($subscribedusers);
    unset($mailcount);
    unset($errorcount);

    //cron_setup_user();

    $sitetimezone = core_date::get_server_timezone();

    // Now see if there are any digest mails waiting to be sent, and if we should send them

    mtrace('Starting digest processing...');

    core_php_time_limit::raise(300); // terminate if not able to fetch all digests in 5 minutes

    if (!isset($CFG->digestmailtimelast)) {    // To catch the first time
        set_config('digestmailtimelast', 0);
    }

    $timenow = time();
    $digesttime = usergetmidnight($timenow, $sitetimezone) + ($CFG->local_digestmailtime * 3600);

    // Delete any really old ones (normally there shouldn't be any)
    $weekago = $timenow - (7 * 24 * 3600);
    $DB->delete_records_select('local_forum_queue', "timemodified < ?", array($weekago));
    mtrace ('Cleaned old digest records');

    if ($CFG->digestmailtimelast < $digesttime and $timenow > $digesttime) {

        mtrace('Sending local_forum digests: '.userdate($timenow, '', $sitetimezone));

        $digestposts_rs = $DB->get_recordset_select('local_forum_queue', "timemodified < ?", array($digesttime));

        if ($digestposts_rs->valid()) {

            // We have work to do
            $usermailcount = 0;

            //caches - reuse the those filled before too
            $discussionposts = array();
            $userdiscussions = array();

            foreach ($digestposts_rs as $digestpost) {
                if (!isset($posts[$digestpost->postid])) {
                    if ($post = $DB->get_record('local_forum_posts', array('id' => $digestpost->postid))) {
                        $posts[$digestpost->postid] = $post;
                    } else {
                        continue;
                    }
                }
                $discussionid = $digestpost->discussionid;
                if (!isset($discussions[$discussionid])) {
                    if ($discussion = $DB->get_record('local_forum_discussions', array('id' => $discussionid))) {
                        $discussions[$discussionid] = $discussion;
                    } else {
                        continue;
                    }
                }
                $localforumid = $discussions[$discussionid]->local_forum;
                if (!isset($localforums[$localforumid])) {
                    if ($localforum = $DB->get_record('local_forum', array('id' => $localforumid))) {
                        $localforums[$localforumid] = $localforum;
                    } else {
                        continue;
                    }
                }
                $userdiscussions[$digestpost->userid][$digestpost->discussionid] = $digestpost->discussionid;
                $discussionposts[$digestpost->discussionid][$digestpost->postid] = $digestpost->postid;
            }
            $digestposts_rs->close(); /// Finished iteration, let's close the resultset

            // Data collected, start sending out emails to each user
            foreach ($userdiscussions as $userid => $thesediscussions) {

                core_php_time_limit::raise(120); // terminate if processing of any account takes longer than 2 minutes

                mtrace(get_string('processingdigest', 'local_forum', $userid), '... ');

                // First of all delete all the queue entries for this user
                $DB->delete_records_select('local_forum_queue', "userid = ? AND timemodified < ?", array($userid, $digesttime));

                // Init user caches - we keep the cache for one cycle only,
                // otherwise it would unnecessarily consume memory.
                if (array_key_exists($userid, $users) and isset($users[$userid]->username)) {
                    $userto = clone($users[$userid]);
                } else {
                    $userto = $DB->get_record('user', array('id' => $userid));
                    local_forum_cron_minimise_user_record($userto);
                }
                $userto->viewfullnames = array();
                $userto->canpost       = array();
                $userto->markposts     = array();

                // Override the language and timezone of the "current" user, so that
                // mail is customised for the receiver.
                //cron_setup_user($userto);

                $postsubject = get_string('digestmailsubject', 'local_forum', format_string($site->shortname, true));

                $headerdata = new stdClass();
                $headerdata->sitename = format_string($site->fullname, true);
                $headerdata->userprefs = $CFG->wwwroot.'/user/local_forum.php?id='.$userid.'&amp;course='.$site->id;

                $posttext = get_string('digestmailheader', 'local_forum', $headerdata)."\n\n";
                $headerdata->userprefs = '<a target="_blank" href="'.$headerdata->userprefs.'">'.get_string('digestmailprefs', 'local_forum').'</a>';

                $posthtml = '<p>'.get_string('digestmailheader', 'local_forum', $headerdata).'</p>'
                    . '<br /><hr size="1" noshade="noshade" />';

                foreach ($thesediscussions as $discussionid) {

                    core_php_time_limit::raise(120);   // to be reset for each post

                    $discussion = $discussions[$discussionid];
                    $localforum      = $localforums[$discussion->local_forum];
                    $course     = $courses[$localforum->course];
                    $cm         = $coursemodules[$localforum->id];


                    // Fill caches
                    if (!isset($userto->viewfullnames[$localforum->id])) {
                        $userto->viewfullnames[$localforum->id] = has_capability('moodle/site:viewfullnames', $context);
                    }
                    if (!isset($userto->canpost[$discussion->id])) {
                        $userto->canpost[$discussion->id] = local_forum_user_can_post($localforum, $discussion, $userto, $context);
                    }

                    $strlocal_forums      = get_string('local_forums', 'local_forum');
                    $canunsubscribe = ! \local_forum\subscriptions::is_forcesubscribed($localforum);
                    $canreply       = $userto->canpost[$discussion->id];
                    $shortname = format_string($localforum->name);

                    $posttext .= "\n \n";
                    $posttext .= '=====================================================================';
                    $posttext .= "\n \n";
                    $posttext .= "$shortname -> $strlocal_forums -> ".format_string($localforum->name,true);
                    if ($discussion->name != $localforum->name) {
                        $posttext  .= " -> ".format_string($discussion->name,true);
                    }
                    $posttext .= "\n";
                    $posttext .= $CFG->wwwroot.'/local/forum/discuss.php?d='.$discussion->id;
                    $posttext .= "\n";

                    $posthtml .= "<p><font face=\"sans-serif\">".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/local/forum/index.php\">$strlocal_forums</a> -> ".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/local/forum/view.php?f=$localforum->id\">".format_string($localforum->name,true)."</a>";
                    if ($discussion->name == $localforum->name) {
                        $posthtml .= "</font></p>";
                    } else {
                        $posthtml .= " -> <a target=\"_blank\" href=\"$CFG->wwwroot/local/forum/discuss.php?d=$discussion->id\">".format_string($discussion->name,true)."</a></font></p>";
                    }
                    $posthtml .= '<p>';

                    $postsarray = $discussionposts[$discussionid];
                    sort($postsarray);
                    $sentcount = 0;

                    foreach ($postsarray as $postid) {
                        $post = $posts[$postid];

                        if (array_key_exists($post->userid, $users)) { // we might know him/her already
                            $userfrom = $users[$post->userid];
                            if (!isset($userfrom->idnumber)) {
                                $userfrom = $DB->get_record('user', array('id' => $userfrom->id));
                                local_forum_cron_minimise_user_record($userfrom);
                            }

                        } else if ($userfrom = $DB->get_record('user', array('id' => $post->userid))) {
                            local_forum_cron_minimise_user_record($userfrom);
                            if ($userscount <= FORUM_CRON_USER_CACHE) {
                                $userscount++;
                                $users[$userfrom->id] = $userfrom;
                            }

                        } else {
                            mtrace('Could not find user '.$post->userid);
                            continue;
                        }

                        // Headers to help prevent auto-responders.
                        $userfrom->customheaders = array(
                                "Precedence: Bulk",
                                'X-Auto-Response-Suppress: All',
                                'Auto-Submitted: auto-generated',
                            );

                        $maildigest = local_forum_get_user_maildigest_bulk($digests, $userto, $localforum->id);
                        if (!isset($userto->canpost[$discussion->id])) {
                            $canreply = local_forum_user_can_post($localforum, $discussion, $userto,$context);
                        } else {
                            $canreply = $userto->canpost[$discussion->id];
                        }

                        $data = new \local_forum\output\local_forum_post_email(
                                $localforum,
                                $discussion,
                                $post,
                                $userfrom,
                                $userto,
                                $canreply
                            );

                        if (!isset($userto->viewfullnames[$localforum->id])) {
                            $data->viewfullnames = has_capability('moodle/site:viewfullnames', $context, $userto->id);
                        } else {
                            $data->viewfullnames = $userto->viewfullnames[$localforum->id];
                        }

                        if ($maildigest == 2) {
                            // Subjects and link only.
                            $posttext .= $textdigestbasicout->render($data);
                            $posthtml .= $htmldigestbasicout->render($data);
                        } else {
                            // The full treatment.
                            $posttext .= $textdigestfullout->render($data);
                            $posthtml .= $htmldigestfullout->render($data);

                            // Create an array of postid's for this user to mark as read.
                            if (!$CFG->local_forum_usermarksread) {
                                $userto->markposts[$post->id] = $post->id;
                            }
                        }
                        $sentcount++;
                    }
                    $footerlinks = array();
                    if ($canunsubscribe) {
                        $footerlinks[] = "<a href=\"$CFG->wwwroot/local/forum/subscribe.php?id=$localforum->id\">" . get_string("unsubscribe", "local_forum") . "</a>";
                    } else {
                        $footerlinks[] = get_string("everyoneissubscribed", "local_forum");
                    }
                    $footerlinks[] = "<a href='{$CFG->wwwroot}/local/forum/index.php?id={$localforum->course}'>" . get_string("digestmailpost", "local_forum") . '</a>';
                    $posthtml .= "\n<div class='mdl-right'><font size=\"1\">" . implode('&nbsp;', $footerlinks) . '</font></div>';
                    $posthtml .= '<hr size="1" noshade="noshade" /></p>';
                }

                if (empty($userto->mailformat) || $userto->mailformat != 1) {
                    // This user DOESN'T want to receive HTML
                    $posthtml = '';
                }

                $eventdata = new \core\message\message();
                $eventdata->courseid            = SITEID;
                $eventdata->component           = 'local_forum';
                $eventdata->name                = 'digests';
                $eventdata->userfrom            = core_user::get_noreply_user();
                $eventdata->userto              = $userto;
                $eventdata->subject             = $postsubject;
                $eventdata->fullmessage         = $posttext;
                $eventdata->fullmessageformat   = FORMAT_PLAIN;
                $eventdata->fullmessagehtml     = $posthtml;
                $eventdata->notification        = 1;
                $eventdata->smallmessage        = get_string('smallmessagedigest', 'local_forum', $sentcount);
                $mailresult = message_send($eventdata);

                if (!$mailresult) {
                    mtrace("ERROR: local/forum/cron.php: Could not send out digest mail to user $userto->id ".
                        "($userto->email)... not trying again.");
                } else {
                    mtrace("success.");
                    $usermailcount++;

                    // Mark post as read if local_forum_usermarksread is set off
                    if (get_user_preferences('local_forum_markasreadonnotification', 1, $userto->id) == 1) {
                        local_forum_tp_mark_posts_read($userto, $userto->markposts);
                    }
                }
            }
        }
    /// We have finishied all digest emails, update $CFG->digestmailtimelast
        set_config('digestmailtimelast', $timenow);
    }

    if (!empty($usermailcount)) {
        mtrace(get_string('digestsentusers', 'local_forum', $usermailcount));
    }

    if (!empty($CFG->local_forum_lastreadclean)) {
        $timenow = time();
        if ($CFG->local_forum_lastreadclean + (24*3600) < $timenow) {
            set_config('local_forum_lastreadclean', $timenow);
            mtrace('Removing old local_forum read tracking info...');
            local_forum_tp_clean_read_records();
        }
    } else {
        set_config('local_forum_lastreadclean', time());
    }

    return true;
}


/**
 * Filters the local_forum discussions according to groups membership and config.
 *
 * @deprecated since 3.3
 * @todo The final deprecation of this function will take place in Moodle 3.7 - see MDL-57487.
 * @since  Moodle 2.8, 2.7.1, 2.6.4
 * @param  array $discussions Discussions with new posts array
 * @return array Forums with the number of new posts
 */
function local_forum_filter_user_groups_discussions($discussions) {

    debugging('The function local_forum_filter_user_groups_discussions() is now deprecated.', DEBUG_DEVELOPER);

    // Group the remaining discussions posts by their local_forumid.
    $filteredlocal_forums = array();

    // Discard not visible groups.
    foreach ($discussions as $discussion) {

        // Grouping results by local_forum.
        if (empty($filteredlocal_forums[$localforum->instance])) {
            $filteredlocal_forums[$localforum->instance] = new stdClass();
            $filteredlocal_forums[$localforum->instance]->id = $localforum->id;
            $filteredlocal_forums[$localforum->instance]->count = 0;
        }
        $filteredlocal_forums[$localforum->instance]->count += $discussion->count;

    }

    return $filteredlocal_forums;
}

/**
 * @deprecated since 3.3
 * @todo The final deprecation of this function will take place in Moodle 3.7 - see MDL-57487.
 * @global object
 * @global object
 * @global object
 * @param array $courses
 * @param array $htmlarray
 */
function local_forum_print_overview($courses,&$htmlarray) {
    global $USER, $CFG, $DB, $SESSION;

    debugging('The function local_forum_print_overview() is now deprecated.', DEBUG_DEVELOPER);

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return array();
    }

    if (!$localforums = get_all_instances_in_courses('local_forum',$courses)) {
        return;
    }

    // Courses to search for new posts
    $coursessqls = array();
    $params = array();
    foreach ($courses as $course) {

        // If the user has never entered into the course all posts are pending
        if ($course->lastaccess == 0) {
            $coursessqls[] = '(d.course = ?)';
            $params[] = $course->id;

        // Only posts created after the course last access
        } else {
            $coursessqls[] = '(d.course = ? AND p.created > ?)';
            $params[] = $course->id;
            $params[] = $course->lastaccess;
        }
    }
    $params[] = $USER->id;
    $coursessql = implode(' OR ', $coursessqls);

    $sql = "SELECT d.id, d.forum, d.course, d.groupid, COUNT(*) as count "
                .'FROM {local_forum_discussions} d '
                .'JOIN {local_forum_posts} p ON p.discussion = d.id '
                ."WHERE ($coursessql) "
                .'AND p.userid != ? '
                .'AND (d.timestart <= ? AND (d.timeend = 0 OR d.timeend > ?)) '
                .'GROUP BY d.id, d.forum, d.course, d.groupid '
                .'ORDER BY d.course, d.forum';
    $params[] = time();
    $params[] = time();

    // Avoid warnings.
    if (!$discussions = $DB->get_records_sql($sql, $params)) {
        $discussions = array();
    }

    $localforumsnewposts = local_forum_filter_user_groups_discussions($discussions);

    // also get all local_forum tracking stuff ONCE.
    $trackinglocal_forums = array();
    foreach ($localforums as $localforum) {
        if (local_forum_tp_can_track_forums($localforum)) {
            $trackinglocal_forums[$localforum->id] = $localforum;
        }
    }

    if (count($trackinglocal_forums) > 0) {
        $cutoffdate = isset($CFG->local_forum_oldpostdays) ? (time() - ($CFG->local_forum_oldpostdays*24*60*60)) : 0;
        $sql = 'SELECT d.forum,d.course,COUNT(p.id) AS count '.
            ' FROM {local_forum_posts} p '.
            ' JOIN {local_forum_discussions} d ON p.discussion = d.id '.
            ' LEFT JOIN {local_forum_read} r ON r.postid = p.id AND r.userid = ? WHERE (';
        $params = array($USER->id);

        foreach ($trackinglocal_forums as $track) {
            $sql .= '(d.forum = ? AND (d.groupid = -1 OR d.groupid = 0 OR d.groupid = ?)) OR ';
            $params[] = $track->id;
            if (isset($SESSION->currentgroup[$track->course])) {
                $groupid =  $SESSION->currentgroup[$track->course];
            } else {
                // get first groupid
                $groupids = groups_get_all_groups($track->course, $USER->id);
                if ($groupids) {
                    reset($groupids);
                    $groupid = key($groupids);
                    $SESSION->currentgroup[$track->course] = $groupid;
                } else {
                    $groupid = 0;
                }
                unset($groupids);
            }
            $params[] = $groupid;
        }
        $sql = substr($sql,0,-3); // take off the last OR
        $sql .= ') AND p.modified >= ? AND r.id is NULL ';
        $sql .= 'AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?)) ';
        $sql .= 'GROUP BY d.forum,d.course';
        $params[] = $cutoffdate;
        $params[] = time();
        $params[] = time();

        if (!$unread = $DB->get_records_sql($sql, $params)) {
            $unread = array();
        }
    } else {
        $unread = array();
    }

    if (empty($unread) and empty($localforumsnewposts)) {
        return;
    }

    $strlocal_forum = get_string('modulename','local_forum');

    foreach ($localforums as $localforum) {
        $str = '';
        $count = 0;
        $thisunread = 0;
        $showunread = false;
        // either we have something from logs, or trackposts, or nothing.
        if (array_key_exists($localforum->id, $localforumsnewposts) && !empty($localforumsnewposts[$localforum->id])) {
            $count = $localforumsnewposts[$localforum->id]->count;
        }
        if (array_key_exists($localforum->id,$unread)) {
            $thisunread = $unread[$localforum->id]->count;
            $showunread = true;
        }
        if ($count > 0 || $thisunread > 0) {
            $str .= '<div class="overview local_forum"><div class="name">'.$strlocal_forum.': <a title="'.$strlocal_forum.'" href="'.$CFG->wwwroot.'/local/forum/view.php?f='.$localforum->id.'">'.
                $localforum->name.'</a></div>';
            $str .= '<div class="info"><span class="postsincelogin">';
            $str .= get_string('overviewnumpostssince', 'local_forum', $count)."</span>";
            if (!empty($showunread)) {
                $str .= '<div class="unreadposts">'.get_string('overviewnumunread', 'local_forum', $thisunread).'</div>';
            }
            $str .= '</div></div>';
        }
        if (!empty($str)) {
            if (!array_key_exists($localforum->course,$htmlarray)) {
                $htmlarray[$localforum->course] = array();
            }
            if (!array_key_exists('local_forum',$htmlarray[$localforum->course])) {
                $htmlarray[$localforum->course]['local_forum'] = ''; // initialize, avoid warnings
            }
            $htmlarray[$localforum->course]['local_forum'] .= $str;
        }
    }
}

/**
 * Given a course and a date, prints a summary of all the new
 * messages posted in the course since that date
 *
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @uses VISIBLEGROUPS
 * @param object $course
 * @param bool $viewfullnames capability
 * @param int $timestart
 * @return bool success
 */
function local_forum_print_recent_activity($viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    // do not use log table if possible, it may be huge and is expensive to join with other tables

    $allnamefields = user_picture::fields('u', null, 'duserid');
    if (!$posts = $DB->get_records_sql("SELECT p.*, f.type AS local_forumtype, d.forum, d.groupid,
                                              d.timestart, d.timeend, $allnamefields
                                         FROM {local_forum_posts} p
                                              JOIN {local_forum_discussions} d ON d.id = p.discussion
                                              JOIN {local_forum} f             ON f.id = d.forum
                                              JOIN {user} u              ON u.id = p.userid
                                        WHERE p.created > ? 
                                     ORDER BY p.id ASC", array($timestart))) { // order by initial posting date
         return false;
    }

    $groupmodes = array();
    $cms    = array();

    $strftimerecent = get_string('strftimerecent');

    $printposts = array();
    foreach ($posts as $post) {
        $context = context_module::instance($cm->id);

        if (!has_capability('local/forum:viewdiscussion', $context)) {
            continue;
        }

        if (!empty($CFG->local_forum_enabletimedposts) and $USER->id != $post->duserid
          and (($post->timestart > 0 and $post->timestart > time()) or ($post->timeend > 0 and $post->timeend < time()))) {
            if (!has_capability('local/forum:viewhiddentimedposts', $context)) {
                continue;
            }
        }
        $printposts[] = $post;
    }
    unset($posts);

    if (!$printposts) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newforumposts', 'local_forum').':', 3);
    $list = html_writer::start_tag('ul', ['class' => 'unlist']);

    foreach ($printposts as $post) {
        $subjectclass = empty($post->parent) ? ' bold' : '';
        $authorhidden = local_forum_is_author_hidden($post, (object) ['type' => $post->forumtype]);

        $list .= html_writer::start_tag('li');
        $list .= html_writer::start_div('head');
        $list .= html_writer::div(userdate($post->modified, $strftimerecent), 'date');
        if (!$authorhidden) {
            $list .= html_writer::div(fullname($post, $viewfullnames), 'name');
        }
        $list .= html_writer::end_div(); // Head.

        $list .= html_writer::start_div('info' . $subjectclass);
        $discussionurl = new moodle_url('/local/forum/discuss.php', ['d' => $post->discussion]);
        if (!empty($post->parent)) {
            $discussionurl->param('parent', $post->parent);
            $discussionurl->set_anchor('p'. $post->id);
        }
        $post->subject = break_up_long_words(format_string($post->subject, true));
        $list .= html_writer::link($discussionurl, $post->subject);
        $list .= html_writer::end_div(); // Info.
        $list .= html_writer::end_tag('li');
    }

    $list .= html_writer::end_tag('ul');
    echo $list;

    return true;
}

// SQL FUNCTIONS ///////////////////////////////////////////////////////////

/**
 * Gets a post with all info ready for local_forum_print_post
 * Most of these joins are just to get the local_forum id
 *
 * @global object
 * @global object
 * @param int $postid
 * @return mixed array of posts or false
 */
function local_forum_get_post_full($postid) {
    global $CFG, $DB;

    //$allnames = get_all_user_name_fields(true, 'u');
     //Add for deprecated this function get_all_user_name_fields <Revathi>
     $fields =  \core_user\fields::get_name_fields(true, 'u', null, 'u');
     $userf = \core_user\fields::for_identity($context, false)->with_userpic()->including(...$fields);
     $allnames = $userf->get_sql('u', false, '', 'id', false)->selects;
    return $DB->get_record_sql("SELECT p.*, d.forum, $allnames, u.email, u.picture, u.imagealt
                             FROM {local_forum_posts} p
                                  JOIN {local_forum_discussions} d ON p.discussion = d.id
                                  LEFT JOIN {user} u ON p.userid = u.id
                            WHERE p.id = ?", array($postid));
}

/**
 * Gets all posts in discussion including top parent.
 *
 * @global object
 * @global object
 * @global object
 * @param int $discussionid
 * @param string $sort
 * @param bool $tracking does user track the local_forum?
 * @return array of posts
 */
function local_forum_get_all_discussion_posts($discussionid, $sort, $tracking=false) {
    global $CFG, $DB, $USER;

    $tr_sel  = "";
    $tr_join = "";
    $params = array();

    if ($tracking) {
        $tr_sel  = ", fr.id AS postread";
        $tr_join = "LEFT JOIN {local_forum_read} fr ON (fr.postid = p.id AND fr.userid = ?)";
        $params[] = $USER->id;
    }

    //$allnames = get_all_user_name_fields(true, 'u');
    // Add for deprecated this function get_all_user_name_fields <Revathi>
    $fields =  \core_user\fields::get_name_fields(true, 'u', null, 'u');
    $userf = \core_user\fields::for_identity($context, false)->with_userpic()->including(...$fields);
    $allnames = $userf->get_sql('u', false, '', 'id', false)->selects;
    $params[] = $discussionid;
    if (!$posts = $DB->get_records_sql("SELECT p.*, $allnames, u.email, u.picture, u.imagealt $tr_sel
                                     FROM {local_forum_posts} p
                                          LEFT JOIN {user} u ON p.userid = u.id
                                          $tr_join
                                    WHERE p.discussion = ?
                                 ORDER BY $sort", $params)) {
        return array();
    }

    foreach ($posts as $pid=>$p) {
        if ($tracking) {
            if (local_forum_tp_is_post_old($p)) {
                 $posts[$pid]->postread = true;
            }
        }
        if (!$p->parent) {
            continue;
        }
        if (!isset($posts[$p->parent])) {
            continue; // parent does not exist??
        }
        if (!isset($posts[$p->parent]->children)) {
            $posts[$p->parent]->children = array();
        }
        $posts[$p->parent]->children[$pid] =& $posts[$pid];
    }

    // Start with the last child of the first post.
    $post = &$posts[reset($posts)->id];

    $lastpost = false;
    while (!$lastpost) {
        if (!isset($post->children)) {
            $post->lastpost = true;
            $lastpost = true;
        } else {
             // Go to the last child of this post.
            $post = &$posts[end($post->children)->id];
        }
    }

    return $posts;
}

/**
 * Returns a list of all new posts that have not been mailed yet
 *
 * @param int $starttime posts created after this time
 * @param int $endtime posts created before this
 * @param int $now used for timed discussions only
 * @return array
 */
function local_forum_get_unmailed_posts($starttime, $endtime, $now=null) {
    global $CFG, $DB;

    $params = array();
    $params['mailed'] = LOCAL_FORUM_MAILED_PENDING;
    $params['ptimestart'] = $starttime;
    $params['ptimeend'] = $endtime;
    $params['mailnow'] = 1;

    if (!empty($CFG->local_forum_enabletimedposts)) {
        if (empty($now)) {
            $now = time();
        }
        $selectsql = "AND (p.created >= :ptimestart OR d.timestart >= :pptimestart)";
        $params['pptimestart'] = $starttime;
        $timedsql = "AND (d.timestart < :dtimestart AND (d.timeend = 0 OR d.timeend > :dtimeend))";
        $params['dtimestart'] = $now;
        $params['dtimeend'] = $now;
    } else {
        $timedsql = "";
        $selectsql = "AND p.created >= :ptimestart";
    }

    return $DB->get_records_sql("SELECT p.*, d.course, d.forum
                                 FROM {local_forum_posts} p
                                 JOIN {local_forum_discussions} d ON d.id = p.discussion
                                 WHERE p.mailed = :mailed
                                 $selectsql
                                 AND (p.created < :ptimeend OR p.mailnow = :mailnow)
                                 $timedsql
                                 ORDER BY p.modified ASC", $params);
}

/**
 * Marks posts before a certain time as being mailed already
 *
 * @global object
 * @global object
 * @param int $endtime
 * @param int $now Defaults to time()
 * @return bool
 */
function local_forum_mark_old_posts_as_mailed($endtime, $now=null) {
    global $CFG, $DB;

    if (empty($now)) {
        $now = time();
    }

    $params = array();
    $params['mailedsuccess'] = LOCAL_FORUM_MAILED_SUCCESS;
    $params['now'] = $now;
    $params['endtime'] = $endtime;
    $params['mailnow'] = 1;
    $params['mailedpending'] = LOCAL_FORUM_MAILED_PENDING;

    if (empty($CFG->local_forum_enabletimedposts)) {
        return $DB->execute("UPDATE {local_forum_posts}
                             SET mailed = :mailedsuccess
                             WHERE (created < :endtime OR mailnow = :mailnow)
                             AND mailed = :mailedpending", $params);
    } else {
        return $DB->execute("UPDATE {local_forum_posts}
                             SET mailed = :mailedsuccess
                             WHERE discussion NOT IN (SELECT d.id
                                                      FROM {local_forum_discussions} d
                                                      WHERE d.timestart > :now)
                             AND (created < :endtime OR mailnow = :mailnow)
                             AND mailed = :mailedpending", $params);
    }
}

/**
 * Get all the posts for a user in a local_forum suitable for local_forum_print_post
 *
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @return array
 */
function local_forum_get_user_posts($localforumid, $userid) {
    global $CFG, $DB;

    $timedsql = "";
    $params = array($localforumid, $userid);

    if (!empty($CFG->local_forum_enabletimedposts)) {
        $cm = get_coursemodule_from_instance('local_forum', $localforumid);
        if (!has_capability('local/forum:viewhiddentimedposts' , context_module::instance($cm->id))) {
            $now = time();
            $timedsql = "AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
        }
    }

    $allnames = get_all_user_name_fields(true, 'u');
    return $DB->get_records_sql("SELECT p.*, d.forum, $allnames, u.email, u.picture, u.imagealt
                              FROM {local_forum} f
                                   JOIN {local_forum_discussions} d ON d.forum = f.id
                                   JOIN {local_forum_posts} p       ON p.discussion = d.id
                                   JOIN {user} u              ON u.id = p.userid
                             WHERE f.id = ?
                                   AND p.userid = ?
                                   $timedsql
                          ORDER BY p.modified ASC", $params);
}

/**
 * Get all the discussions user participated in
 *
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param int $localforumid
 * @param int $userid
 * @return array Array or false
 */
function local_forum_get_user_involved_discussions($localforumid, $userid) {
    global $CFG, $DB;

    $timedsql = "";
    $params = array($localforumid, $userid);
    if (!empty($CFG->local_forum_enabletimedposts)) {
        $cm = get_coursemodule_from_instance('local_forum', $localforumid);
        if (!has_capability('local/forum:viewhiddentimedposts' , context_module::instance($cm->id))) {
            $now = time();
            $timedsql = "AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
        }
    }

    return $DB->get_records_sql("SELECT DISTINCT d.*
                              FROM {local_forum} f
                                   JOIN {local_forum_discussions} d ON d.forum = f.id
                                   JOIN {local_forum_posts} p       ON p.discussion = d.id
                             WHERE f.id = ?
                                   AND p.userid = ?
                                   $timedsql", $params);
}

/**
 * Get all the posts for a user in a local_forum suitable for local_forum_print_post
 *
 * @global object
 * @global object
 * @param int $localforumid
 * @param int $userid
 * @return array of counts or false
 */
function local_forum_count_user_posts($localforumid, $userid) {
    global $CFG, $DB;

    $timedsql = "";
    $params = array($localforumid, $userid);
    if (!empty($CFG->local_forum_enabletimedposts)) {
        $cm = get_coursemodule_from_instance('local_forum', $localforumid);
        if (!has_capability('local/forum:viewhiddentimedposts' , context_module::instance($cm->id))) {
            $now = time();
            $timedsql = "AND (d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
        }
    }

    return $DB->get_record_sql("SELECT COUNT(p.id) AS postcount, MAX(p.modified) AS lastpost
                             FROM {local_forum} f
                                  JOIN {local_forum_discussions} d ON d.forum = f.id
                                  JOIN {local_forum_posts} p       ON p.discussion = d.id
                                  JOIN {user} u              ON u.id = p.userid
                            WHERE f.id = ?
                                  AND p.userid = ?
                                  $timedsql", $params);
}

/**
 * Given a log entry, return the local_forum post details for it.
 *
 * @global object
 * @global object
 * @param object $log
 * @return array|null
 */
function local_forum_get_post_from_log($log) {
    global $CFG, $DB;

    $allnames = get_all_user_name_fields(true, 'u');
    if ($log->action == "add post") {

        return $DB->get_record_sql("SELECT p.*, f.type AS local_forumtype, d.forum, d.groupid, $allnames, u.email, u.picture
                                 FROM {local_forum_discussions} d,
                                      {local_forum_posts} p,
                                      {local_forum} f,
                                      {user} u
                                WHERE p.id = ?
                                  AND d.id = p.discussion
                                  AND p.userid = u.id
                                  AND u.deleted <> '1'
                                  AND f.id = d.forum", array($log->info));


    } else if ($log->action == "add discussion") {

        return $DB->get_record_sql("SELECT p.*, f.type AS local_forumtype, d.forum, d.groupid, $allnames, u.email, u.picture
                                 FROM {local_forum_discussions} d,
                                      {local_forum_posts} p,
                                      {local_forum} f,
                                      {user} u
                                WHERE d.id = ?
                                  AND d.firstpost = p.id
                                  AND p.userid = u.id
                                  AND u.deleted <> '1'
                                  AND f.id = d.forum", array($log->info));
    }
    return NULL;
}

/**
 * Given a discussion id, return the first post from the discussion
 *
 * @global object
 * @global object
 * @param int $dicsussionid
 * @return array
 */
function local_forum_get_firstpost_from_discussion($discussionid) {
    global $CFG, $DB;

    return $DB->get_record_sql("SELECT p.*
                             FROM {local_forum_discussions} d,
                                  {local_forum_posts} p
                            WHERE d.id = ?
                              AND d.firstpost = p.id ", array($discussionid));
}

/**
 * Returns an array of counts of replies to each discussion
 *
 * @global object
 * @global object
 * @param int $localforumid
 * @param string $localforumsort
 * @param int $limit
 * @param int $page
 * @param int $perpage
 * @return array
 */
function local_forum_count_discussion_replies($localforumid, $localforumsort="", $limit=-1, $page=-1, $perpage=0) {
    global $CFG, $DB;

    if ($limit > 0) {
        $limitfrom = 0;
        $limitnum  = $limit;
    } else if ($page != -1) {
        $limitfrom = $page*$perpage;
        $limitnum  = $perpage;
    } else {
        $limitfrom = 0;
        $limitnum  = 0;
    }

    if ($localforumsort == "") {
        $orderby = "";
        $groupby = "";

    } else {
        $orderby = "ORDER BY $localforumsort";
        $groupby = ", ".strtolower($localforumsort);
        $groupby = str_replace('desc', '', $groupby);
        $groupby = str_replace('asc', '', $groupby);
    }

    if (($limitfrom == 0 and $limitnum == 0) or $localforumsort == "") {
        $sql = "SELECT p.discussion, COUNT(p.id) AS replies, MAX(p.id) AS lastpostid
                  FROM {local_forum_posts} p
                       JOIN {local_forum_discussions} d ON p.discussion = d.id
                 WHERE p.parent > 0 AND d.forum = ?
              GROUP BY p.discussion";
        return $DB->get_records_sql($sql, array($localforumid));

    } else {
        $sql = "SELECT p.discussion, (COUNT(p.id) - 1) AS replies, MAX(p.id) AS lastpostid
                  FROM {local_forum_posts} p
                       JOIN {local_forum_discussions} d ON p.discussion = d.id
                 WHERE d.forum = ?
              GROUP BY p.discussion $groupby $orderby";
        return $DB->get_records_sql($sql, array($localforumid), $limitfrom, $limitnum);
    }
}

/**
 * @global object
 * @param object $localforum
 * @return mixed
 */
function local_forum_count_discussions($localforum) {
    global $CFG, $DB, $USER;

    $now = round(time(), -2); // db cache friendliness
    $params = array();
   
    $params[] = $localforum->id;

    if (!empty($CFG->local_forum_enabletimedposts)) {
        $timedsql = "AND d.timestart < $now AND (d.timeend = 0 OR d.timeend > $now)";
        $params[] = $now;
        $params[] = $now;
    } else {
        $timedsql = "";
    }
    $sql = "SELECT COUNT(d.id)
              FROM {local_forum_discussions} d
             WHERE d.forum = ?
                   $timedsql";

    return $DB->get_field_sql($sql, $params);
}

/**
 * Get all discussions in a local_forum
 *
 * @global object
 * @global object
 * @global object
 * @param object $localforum
 * @param string $localforumsort
 * @param bool $fullpost
 * @param int $unused
 * @param int $limit
 * @param bool $userlastmodified
 * @param int $page
 * @param int $perpage
 * @param int $groupid if groups enabled, get discussions for this group overriding the current group.
 *                     Use FORUM_POSTS_ALL_USER_GROUPS for all the user groups
 * @param int $updatedsince retrieve only discussions updated since the given time
 * @return array
 */
function local_forum_get_discussions($localforum, $localforumsort="", $fullpost=true, $unused=-1, $limit=-1,
                                $userlastmodified=false, $page=-1, $perpage=0, $groupid = -1,
                                $updatedsince = 0) {
    global $CFG, $DB, $USER;

    $timelimit = '';

    $now = round(time(), -2);
    $params = array($localforum->id);

    $context = context_system::instance();

    if (!has_capability('local/forum:viewdiscussion', $context)) { /// User must have perms to view discussions
        return array();
    }

    if (!empty($CFG->local_forum_enabletimedposts)) { /// Users must fulfill timed posts

        if (!has_capability('local/forum:viewhiddentimedposts', $context)) {
            $timelimit = " AND ((d.timestart <= ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
            if (isloggedin()) {
                $timelimit .= " OR d.userid = ?";
                $params[] = $USER->id;
            }
            $timelimit .= ")";
        }
    }

    if ($limit > 0) {
        $limitfrom = 0;
        $limitnum  = $limit;
    } else if ($page != -1) {
        $limitfrom = $page*$perpage;
        $limitnum  = $perpage;
    } else {
        $limitfrom = 0;
        $limitnum  = 0;
    }

    if (empty($localforumsort)) {
        $localforumsort = local_forum_get_default_sort_order();
    }
    if (empty($fullpost)) {
        $postdata = "p.id,p.subject,p.modified,p.discussion,p.userid";
    } else {
        $postdata = "p.*";
    }

    if (empty($userlastmodified)) {  // We don't need to know this
        $umfields = "";
        $umtable  = "";
    } else {
        // Add for deprecated this function get_all_user_name_fields <Revathi>
        $fields =  \core_user\fields::get_name_fields(true, 'u', null, 'u');
        $userf = \core_user\fields::for_identity($context, false)->with_userpic()->including(...$fields);
        $userfields = $userf->get_sql('u', false, '', 'id', false)->selects;
        // End for deprecated this function get_all_user_name_fields <Revathi>
        $umfields = ', ' .$userfields. ', um.email AS umemail, um.picture AS umpicture,
                        um.imagealt AS umimagealt';// \core_user\fields::get_name_fields
        $umtable  = " LEFT JOIN {user} um ON (d.usermodified = um.id)";
    }

    $updatedsincesql = '';
    if (!empty($updatedsince)) {
        $updatedsincesql = 'AND d.timemodified > ?';
        $params[] = $updatedsince;
    }
    //Add for deprecated this function get_all_user_name_fields <Revathi>
    $fields =  \core_user\fields::get_name_fields(true, 'u', null, 'u');
    $userf = \core_user\fields::for_identity($context, false)->with_userpic()->including(...$fields);
    $allnames = $userf->get_sql('u', false, '', 'id', false)->selects;
    //$allnames = get_all_user_name_fields(true, 'u');
    // End for deprecated this function get_all_user_name_fields <Revathi>
    $sql = "SELECT $postdata, d.name, d.timemodified, d.usermodified, d.groupid, d.timestart, d.timeend, d.pinned, $allnames,
                   u.email, u.picture, u.imagealt $umfields
              FROM {local_forum_discussions} d
                   JOIN {local_forum_posts} p ON p.discussion = d.id
                   JOIN {user} u ON p.userid = u.id
                   $umtable
             WHERE d.forum = ? AND p.parent = 0
                   $timelimit $updatedsincesql
          ORDER BY $localforumsort, d.id DESC";

    return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
}

/**
 * Gets the neighbours (previous and next) of a discussion.
 *
 * The calculation is based on the timemodified when time modified or time created is identical
 * It will revert to using the ID to sort consistently. This is better tha skipping a discussion.
 *
 * For blog-style local_forums, the calculation is based on the original creation time of the
 * blog post.
 *
 * Please note that this does not check whether or not the discussion passed is accessible
 * by the user, it simply uses it as a reference to find the neighbours. On the other hand,
 * the returned neighbours are checked and are accessible to the current user.
 *
 * @param object $discussion The discussion record.
 * @param object $localforum The local_forum instance record.
 * @return array That always contains the keys 'prev' and 'next'. When there is a result
 *               they contain the record with minimal information such as 'id' and 'name'.
 *               When the neighbour is not found the value is false.
 */
function local_forum_get_discussion_neighbours($discussion, $localforum) {
    global $CFG, $DB, $USER;

    if ( $discussion->forum != $localforum->id ) {
        throw new coding_exception('Discussion is not part of the same forum.');
    }

    $neighbours = array('prev' => false, 'next' => false);
    $now = round(time(), -2);
    $params = array();

    $context = context_system::instance();

    // Users must fulfill timed posts.
    $timelimit = '';
    if (!empty($CFG->local_forum_enabletimedposts)) {
        if (!has_capability('local/forum:viewhiddentimedposts', $context)) {
            $timelimit = ' AND ((d.timestart <= :tltimestart AND (d.timeend = 0 OR d.timeend > :tltimeend))';
            $params['tltimestart'] = $now;
            $params['tltimeend'] = $now;
            if (isloggedin()) {
                $timelimit .= ' OR d.userid = :tluserid';
                $params['tluserid'] = $USER->id;
            }
            $timelimit .= ')';
        }
    }

    $params['forumid'] = $localforum->id;
    $params['discid1'] = $discussion->id;
    $params['discid2'] = $discussion->id;
    $params['discid3'] = $discussion->id;
    $params['discid4'] = $discussion->id;
    $params['disctimecompare1'] = $discussion->timemodified;
    $params['disctimecompare2'] = $discussion->timemodified;
    $params['pinnedstate1'] = (int) $discussion->pinned;
    $params['pinnedstate2'] = (int) $discussion->pinned;
    $params['pinnedstate3'] = (int) $discussion->pinned;
    $params['pinnedstate4'] = (int) $discussion->pinned;

    $sql = "SELECT d.id, d.name, d.timemodified, d.groupid, d.timestart, d.timeend
              FROM {local_forum_discussions} d
              JOIN {local_forum_posts} p ON d.firstpost = p.id
             WHERE d.forum = :forumid
               AND d.id <> :discid1
                   $timelimit
                   ";
    $comparefield = "d.timemodified";
    $comparevalue = ":disctimecompare1";
    $comparevalue2  = ":disctimecompare2";
    if (!empty($CFG->local_forum_enabletimedposts)) {
        // Here we need to take into account the release time (timestart)
        // if one is set, of the neighbouring posts and compare it to the
        // timestart or timemodified of *this* post depending on if the
        // release date of this post is in the future or not.
        // This stops discussions that appear later because of the
        // timestart value from being buried under discussions that were
        // made afterwards.
        $comparefield = "CASE WHEN d.timemodified < d.timestart
                                THEN d.timestart ELSE d.timemodified END";
        if ($discussion->timemodified < $discussion->timestart) {
            // Normally we would just use the timemodified for sorting
            // discussion posts. However, when timed discussions are enabled,
            // then posts need to be sorted base on the later of timemodified
            // or the release date of the post (timestart).
            $params['disctimecompare1'] = $discussion->timestart;
            $params['disctimecompare2'] = $discussion->timestart;
        }
    }
    $orderbydesc = local_forum_get_default_sort_order(true, $comparefield, 'd', false);
    $orderbyasc = local_forum_get_default_sort_order(false, $comparefield, 'd', false);

    if ($localforum->type === 'blog') {
         $subselect = "SELECT pp.created
                   FROM {local_forum_discussions} dd
                   JOIN {local_forum_posts} pp ON dd.firstpost = pp.id ";

         $subselectwhere1 = " WHERE dd.id = :discid3";
         $subselectwhere2 = " WHERE dd.id = :discid4";

         $comparefield = "p.created";

         $sub1 = $subselect.$subselectwhere1;
         $comparevalue = "($sub1)";

         $sub2 = $subselect.$subselectwhere2;
         $comparevalue2 = "($sub2)";

         $orderbydesc = "d.pinned, p.created DESC";
         $orderbyasc = "d.pinned, p.created ASC";
    }

    $prevsql = $sql . " AND ( (($comparefield < $comparevalue) AND :pinnedstate1 = d.pinned)
                         OR ($comparefield = $comparevalue2 AND (d.pinned = 0 OR d.pinned = :pinnedstate4) AND d.id < :discid2)
                         OR (d.pinned = 0 AND d.pinned <> :pinnedstate2))
                   ORDER BY CASE WHEN d.pinned = :pinnedstate3 THEN 1 ELSE 0 END DESC, $orderbydesc, d.id DESC";

    $nextsql = $sql . " AND ( (($comparefield > $comparevalue) AND :pinnedstate1 = d.pinned)
                         OR ($comparefield = $comparevalue2 AND (d.pinned = 1 OR d.pinned = :pinnedstate4) AND d.id > :discid2)
                         OR (d.pinned = 1 AND d.pinned <> :pinnedstate2))
                   ORDER BY CASE WHEN d.pinned = :pinnedstate3 THEN 1 ELSE 0 END DESC, $orderbyasc, d.id ASC";

    $neighbours['prev'] = $DB->get_record_sql($prevsql, $params, IGNORE_MULTIPLE);
    $neighbours['next'] = $DB->get_record_sql($nextsql, $params, IGNORE_MULTIPLE);
    return $neighbours;
}

/**
 * Get the sql to use in the ORDER BY clause for local_forum discussions.
 *
 * This has the ordering take timed discussion windows into account.
 *
 * @param bool $desc True for DESC, False for ASC.
 * @param string $compare The field in the SQL to compare to normally sort by.
 * @param string $prefix The prefix being used for the discussion table.
 * @param bool $pinned sort pinned posts to the top
 * @return string
 */
function local_forum_get_default_sort_order($desc = true, $compare = 'd.timemodified', $prefix = 'd', $pinned = true) {
    global $CFG;

    if (!empty($prefix)) {
        $prefix .= '.';
    }

    $dir = $desc ? 'DESC' : 'ASC';

    if ($pinned == true) {
        $pinned = "{$prefix}pinned DESC,";
    } else {
        $pinned = '';
    }

    $sort = "{$prefix}timemodified";
    if (!empty($CFG->local_forum_enabletimedposts)) {
        $sort = "CASE WHEN {$compare} < {$prefix}timestart
                 THEN {$prefix}timestart
                 ELSE {$compare}
                 END";
    }
    return "$pinned $sort $dir";
}

/**
 *
 * @global object
 * @param object $localforum
 * @return array
 */
function local_forum_get_discussions_unread($localforum) {
    global $CFG, $DB, $USER;

    $now = round(time(), -2);
    $cutoffdate = $now - ($CFG->local_forum_oldpostdays*24*60*60);

    $params = array();
    

    if (!empty($CFG->local_forum_enabletimedposts)) {
        $timedsql = "AND d.timestart < :now1 AND (d.timeend = 0 OR d.timeend > :now2)";
        $params['now1'] = $now;
        $params['now2'] = $now;
    } else {
        $timedsql = "";
    }

    $sql = "SELECT d.id, COUNT(p.id) AS unread
              FROM {local_forum_discussions} d
                   JOIN {local_forum_posts} p     ON p.discussion = d.id
                   LEFT JOIN {local_forum_read} r ON (r.postid = p.id AND r.userid = $USER->id)
             WHERE d.forum = {$localforum->id}
                   AND p.modified >= :cutoffdate AND r.id is NULL
                   $groupselect
                   $timedsql
          GROUP BY d.id";
    $params['cutoffdate'] = $cutoffdate;

    if ($unreads = $DB->get_records_sql($sql, $params)) {
        foreach ($unreads as $unread) {
            $unreads[$unread->id] = $unread->unread;
        }
        return $unreads;
    } else {
        return array();
    }
}

/**
 * @global object
 * @param object $forumid
 * @return array
 */
function local_forum_get_discussions_count($forumid) {
    global $CFG, $DB, $USER;

    $now = round(time(), -2);
    $params = array($forumid);
    
    $timelimit = "";

    if (!empty($CFG->local_forum_enabletimedposts)) {

        $context = context_system::instance();

        if (!has_capability('local/forum:viewhiddentimedposts', $context)) {
            $timelimit = " AND ((d.timestart <= ? AND (d.timeend = 0 OR d.timeend > ?))";
            $params[] = $now;
            $params[] = $now;
            if (isloggedin()) {
                $timelimit .= " OR d.userid = ?";
                $params[] = $USER->id;
            }
            $timelimit .= ")";
        }
    }

    $sql = "SELECT COUNT(d.id)
              FROM {local_forum_discussions} d
                   JOIN {local_forum_posts} p ON p.discussion = d.id
             WHERE d.forum = ? AND p.parent = 0
                   $timelimit";

    return $DB->get_field_sql($sql, $params);
}


// OTHER FUNCTIONS ///////////////////////////////////////////////////////////
/**
 * Print a local_forum post
 *
 * @global object
 * @global object
 * @uses FORUM_MODE_THREADED
 * @uses PORTFOLIO_FORMAT_PLAINHTML
 * @uses PORTFOLIO_FORMAT_FILE
 * @uses PORTFOLIO_FORMAT_RICHHTML
 * @uses PORTFOLIO_ADD_TEXT_LINK
 * @uses CONTEXT_MODULE
 * @param object $post The post to print.
 * @param object $discussion
 * @param object $localforum
 * @param boolean $ownpost Whether this post belongs to the current user.
 * @param boolean $reply Whether to print a 'reply' link at the bottom of the message.
 * @param boolean $link Just print a shortened version of the post as a link to the full post.
 * @param string $footer Extra stuff to print after the message.
 * @param string $highlight Space-separated list of terms to highlight.
 * @param int $post_read true, false or -99. If we already know whether this user
 *          has read this post, pass that in, otherwise, pass in -99, and this
 *          function will work it out.
 * @param boolean $dummyifcantsee When local_forum_user_can_see_post says that
 *          the current user can't see this post, if this argument is true
 *          (the default) then print a dummy 'you can't see this post' post.
 *          If false, don't output anything at all.
 * @param bool|null $istracked
 * @return void
 */
function local_forum_print_post($post, $discussion, $localforum, $ownpost=false, $reply=false, $link=false,
                          $footer="", $highlight="", $postisread=null, $dummyifcantsee=true, $istracked=null, $return=false) {
    global $USER, $CFG, $OUTPUT;

    require_once($CFG->libdir . '/filelib.php');

    // String cache
    static $str;
    // This is an extremely hacky way to ensure we only print the 'unread' anchor
    // the first time we encounter an unread post on a page. Ideally this would
    // be moved into the caller somehow, and be better testable. But at the time
    // of dealing with this bug, this static workaround was the most surgical and
    // it fits together with only printing th unread anchor id once on a given page.
    static $firstunreadanchorprinted = false;

    $context = context_system::instance();

    $post->course = 0;
    $post->forum  = $localforum->id;
    $post->message = file_rewrite_pluginfile_urls($post->message, 'pluginfile.php', $context->id, 'local_forum', 'post', $post->id);
    if ($istracked && is_null($postisread)) {
        $postisread = local_forum_tp_is_post_read($USER->id, $post);
    }

    if (!local_forum_user_can_see_post($localforum, $discussion, $post, NULL)) {
        $output = '';
        if (!$dummyifcantsee) {
            if ($return) {
                return $output;
            }
            echo $output;
            return;
        }
        $output .= html_writer::tag('a', '', array('id'=>'p'.$post->id));
        $output .= html_writer::start_tag('div', array('class'=>'forumpost clearfix',
                                                       'role' => 'region',
                                                       'aria-label' => get_string('hiddenforumpost', 'local_forum')));
        $output .= html_writer::start_tag('div', array('class'=>'row header'));
        $output .= html_writer::tag('div', '', array('class'=>'left picture')); // Picture
        if ($post->parent) {
            $output .= html_writer::start_tag('div', array('class'=>'topic'));
        } else {
            $output .= html_writer::start_tag('div', array('class'=>'topic starter'));
        }
        $output .= html_writer::tag('div', get_string('forumsubjecthidden','local_forum'), array('class' => 'subject',
                                                                                           'role' => 'header')); // Subject.
        $output .= html_writer::tag('div', get_string('forumauthorhidden', 'local_forum'), array('class' => 'author',
                                                                                           'role' => 'header')); // Author.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div'); // row
        $output .= html_writer::start_tag('div', array('class'=>'row'));
        $output .= html_writer::tag('div', '&nbsp;', array('class'=>'left side')); // Groups
        $output .= html_writer::tag('div', get_string('forumbodyhidden','local_forum'), array('class'=>'content')); // Content
        $output .= html_writer::end_tag('div'); // row
        $output .= html_writer::end_tag('div'); // local_forumpost

        if ($return) {
            return $output;
        }
        echo $output;
        return;
    }

    if (empty($str)) {
        $str = new stdClass;
        $str->edit         = get_string('edit', 'local_forum');
        $str->delete       = get_string('delete', 'local_forum');
        $str->reply        = get_string('reply', 'local_forum');
        $str->parent       = get_string('parent', 'local_forum');
        $str->pruneheading = get_string('pruneheading', 'local_forum');
        $str->prune        = get_string('prune', 'local_forum');
        $str->displaymode     = get_user_preferences('forum_displaymode', $CFG->local_forum_displaymode);
        $str->markread     = get_string('markread', 'local_forum');
        $str->markunread   = get_string('markunread', 'local_forum');
    }

    $discussionlink = new moodle_url('/local/forum/discuss.php', array('d'=>$post->discussion));

    // Build an object that represents the posting user
    $postuser = new stdClass;
    $postuserfields = explode(',', user_picture::fields());
    $postuser = username_load_fields_from_object($postuser, $post, null, $postuserfields);
    $postuser->id = $post->userid;
    $postuser->fullname    = fullname($postuser);
    $postuser->profilelink = new moodle_url('/user/view.php', array('id'=>$post->userid, 'course'=>1));

    // Prepare the attachements for the post, files then images
    list($attachments, $attachedimages) = local_forum_print_attachments($post, 'separateimages');
    // Determine if we need to shorten this post
    $shortenpost = ($link && (strlen(\local_costcenter\lib::strip_tags_custom($post->message)) > $CFG->local_forum_longpost));

    // Prepare an array of commands
    $commands = array();

    // Add a permalink.
    $permalink = new moodle_url($discussionlink);
    $permalink->set_anchor('p' . $post->id);
    $commands[] = array('url' => $permalink, 'text' => get_string('permalink', 'local_forum'));

    // SPECIAL CASE: The front page can display a news item post to non-logged in users.
    // Don't display the mark read / unread controls in this case.
    if ($istracked && $CFG->local_forum_usermarksread && isloggedin()) {
        $url = new moodle_url($discussionlink, array('postid'=>$post->id, 'mark'=>'unread'));
        $text = $str->markunread;
        if (!$postisread) {
            $url->param('mark', 'read');
            $text = $str->markread;
        }
        if ($str->displaymode == LOCAL_FORUM_MODE_THREADED) {
            $url->param('parent', $post->parent);
        } else {
            $url->set_anchor('p'.$post->id);
        }
        $commands[] = array('url'=>$url, 'text'=>$text);
    }

    // Zoom in to the parent specifically
    if ($post->parent) {
        $url = new moodle_url($discussionlink);
        if ($str->displaymode == LOCAL_FORUM_MODE_THREADED) {
            $url->param('parent', $post->parent);
        } else {
            $url->set_anchor('p'.$post->parent);
        }
        $commands[] = array('url'=>$url, 'text'=>$str->parent);
    }
    

    // Hack for allow to edit news posts those are not displayed yet until they are displayed
    $age = time() - $post->created;
    if (!$post->parent && $localforum->type == 'news' && $discussion->timestart > time()) {
        $age = 0;
    }

    //added based on capability 14.11.22
    if(is_siteadmin() || has_capability('local/forum:startdiscussion',$context)){
        $commands[] = array('url'=>new moodle_url('/local/forum/post.php', array('edit'=>$post->id)), 'text'=>$str->edit);
    }else  if (($ownpost && $age < $CFG->maxeditingtime)) {
        $commands[] = array('url'=>new moodle_url('/local/forum/post.php', array('edit'=>$post->id)), 'text'=>$str->edit);
    }
   /*  if ($localforum->type == 'single' and $discussion->firstpost == $post->id ) { 
        $commands[] = array('url'=>new moodle_url('/local/forum/post.php', array('edit'=>$post->id)), 'text'=>$str->edit);
    } else  if (($ownpost && $age < $CFG->maxeditingtime)) {
        $commands[] = array('url'=>new moodle_url('/local/forum/post.php', array('edit'=>$post->id)), 'text'=>$str->edit);
    }*/
   

    //commented split link
   /*  if ( $post->parent && $localforum->type != 'single') {
        $commands[] = array('url'=>new moodle_url('/local/forum/post.php', array('prune'=>$post->id)), 'text'=>$str->prune, 'title'=>$str->pruneheading);
    } */


     //end

    if ($localforum->type == 'single' and $discussion->firstpost == $post->id) {
        // Do not allow deleting of first post in single simple type.
    } else if (($ownpost && $age < $CFG->maxeditingtime && has_capability('local/forum:deleteownpost', $context)) ) {
        $commands[] = array('url'=>new moodle_url('/local/forum/post.php', array('delete'=>$post->id)), 'text'=>$str->delete);
    }

    if ($reply) {
        $commands[] = array('url'=>new moodle_url('/local/forum/post.php#mformlocal_forum', array('reply'=>$post->id)), 'text'=>$str->reply);
    }

    if ($CFG->enableportfolios && ($cm->cache->caps['local/forum:exportpost'] || ($ownpost && $cm->cache->caps['local/local_forum:exportownpost']))) {
        $p = array('postid' => $post->id);
        require_once($CFG->libdir.'/portfoliolib.php');
        $button = new portfolio_add_button();
        $button->set_callback_options('local_forum_portfolio_caller', array('postid' => $post->id), 'local_forum');
        if (empty($attachments)) {
            $button->set_formats(PORTFOLIO_FORMAT_PLAINHTML);
        } else {
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
        }

        $porfoliohtml = $button->to_html(PORTFOLIO_ADD_TEXT_LINK);
        if (!empty($porfoliohtml)) {
            $commands[] = $porfoliohtml;
        }
    }
    // Finished building commands


    // Begin output

    $output  = '';

    if ($istracked) {
        if ($postisread) {
            $localforumpostclass = ' read';
        } else {
            $localforumpostclass = ' unread';
            // If this is the first unread post printed then give it an anchor and id of unread.
            if (!$firstunreadanchorprinted) {
                $output .= html_writer::tag('a', '', array('id' => 'unread'));
                $firstunreadanchorprinted = true;
            }
        }
    } else {
        // ignore trackign status if not tracked or tracked param missing
        $localforumpostclass = '';
    }

    $topicclass = '';
    if (empty($post->parent)) {
        $topicclass = ' firstpost starter';
    }

    if (!empty($post->lastpost)) {
        $localforumpostclass .= ' lastpost';
    }

    // Flag to indicate whether we should hide the author or not.
    $authorhidden = local_forum_is_author_hidden($post, $localforum);
    $postbyuser = new stdClass;
    $postbyuser->post = $post->subject;
    $postbyuser->user = $postuser->fullname;
    $discussionbyuser = get_string('postbyuser', 'local_forum', $postbyuser);
    $output .= html_writer::tag('a', '', array('id'=>'p'.$post->id));
    // Begin local_forum post.
    $output .= html_writer::start_div('forumpost clearfix' . $localforumpostclass . $topicclass,
        ['role' => 'region', 'aria-label' => $discussionbyuser]);
    // Begin header row.
    $output .= html_writer::start_div('row header clearfix');

    // User picture.
    if (!$authorhidden) {
        $picture = $OUTPUT->user_picture($postuser, array('link' => false));
        $output .= html_writer::div($picture, 'left picture');
        $topicclass = 'topic' . $topicclass;
    }

    // Begin topic column.
    $output .= html_writer::start_div($topicclass);
    $postsubject = $post->subject;
    if (empty($post->subjectnoformat)) {
        $postsubject = format_string($postsubject);
    }
    $output .= html_writer::div($postsubject, 'subject', ['role' => 'heading', 'aria-level' => '2']);

    if ($authorhidden) {
        $bytext = userdate($post->modified);
    } else {
        $by = new stdClass();
        $by->date = userdate($post->modified);
        // $by->name = html_writer::link($postuser->profilelink, $postuser->fullname);
        $by->name = html_writer::tag('span', $postuser->fullname);
        $bytext = get_string('bynameondate', 'local_forum', $by);
    }
    $bytextoptions = [
        'role' => 'heading',
        'aria-level' => '2',
    ];
    $output .= html_writer::div($bytext, 'author', $bytextoptions);
    // End topic column.
    $output .= html_writer::end_div();

    // End header row.
    $output .= html_writer::end_div();

    // Row with the local_forum post content.
    $output .= html_writer::start_div('row maincontent clearfix');

    $output .= html_writer::start_tag('div', array('class'=>'no-overflow'));
    $output .= html_writer::start_tag('div', array('class'=>'content'));

    $options = new stdClass;
    $options->para    = false;
    $options->trusted = $post->messagetrust;
    $options->context = $context;
    if ($shortenpost) {
        // Prepare shortened version by filtering the text then shortening it.
        $postclass    = 'shortenedpost';
        $postcontent  = format_text($post->message, $post->messageformat, $options);
        $postcontent  = shorten_text($postcontent, $CFG->local_forum_shortpost);
        $postcontent .= html_writer::link($discussionlink, get_string('readtherest', 'local_forum'));
        $postcontent .= html_writer::tag('div', '('.get_string('numwords', 'moodle', count_words($post->message)).')',
            array('class'=>'post-word-count'));
    } else {
        // Prepare whole post
        $postclass    = 'fullpost';
        $postcontent  = format_text($post->message, $post->messageformat, $options);
        if (!empty($highlight)) {
            $postcontent = highlight($highlight, $postcontent);
        }
        if (!empty($localforum->displaywordcount)) {
            $postcontent .= html_writer::tag('div', get_string('numwords', 'moodle', count_words($post->message)),
                array('class'=>'post-word-count'));
        }
        $postcontent .= html_writer::tag('div', $attachedimages, array('class'=>'attachedimages'));
    }

 /*    if (\core_tag_tag::is_enabled('local_forum', 'local_forum_posts')) {
        $postcontent .= $OUTPUT->tag_list(core_tag_tag::get_item_tags('local_forum', 'local_forum_posts', $post->id), null, 'local_forum-tags');
    } */

    // Output the post content
    $output .= html_writer::tag('div', $postcontent, array('class'=>'posting '.$postclass));
    $output .= html_writer::end_tag('div'); // Content
    $output .= html_writer::end_tag('div'); // Content mask
    $output .= html_writer::end_tag('div'); // Row

    $output .= html_writer::start_tag('div', array('class'=>'row side'));
    // $output .= html_writer::tag('div','&nbsp;', array('class'=>'left'));
    $output .= html_writer::start_tag('div', array('class'=>'options clearfix'));

    if (!empty($attachments)) {
        $output .= html_writer::tag('div', $attachments, array('class' => 'attachments'));
    }

    // Output ratings
    if (!empty($post->rating)) {
        $output .= html_writer::tag('div', $OUTPUT->render($post->rating), array('class'=>'local_forum-post-rating'));
    }  
    // Output the commands
    $commandhtml = array();
    foreach ($commands as $command) {
        if (is_array($command)) {
            $commandhtml[] = html_writer::link($command['url'], $command['text']);
        } else {
            $commandhtml[] = $command;
        }
    }    
   /*  $commandhtml[] .= html_writer::tag('i', '', array('class' => 'fa fa-thumbs-up','aria-hidden' => true));
    $commandhtml[] .= html_writer::tag('i', '', array('class' => 'fa fa-thumbs-down','aria-hidden' => true));
     */
    $output .= html_writer::tag('div', implode(' | ', $commandhtml), array('class'=>'commands'));

    // Output link to post if required
    if ($link) {
        if (local_forum_user_can_post($localforum, $discussion, $USER, $context)) {
            $langstring = 'discussthistopic';
        } else {
            $langstring = 'viewthediscussion';
        }
        if ($post->replies == 1) {
            $replystring = get_string('repliesone', 'local_forum', $post->replies);
        } else {
            $replystring = get_string('repliesmany', 'local_forum', $post->replies);
        }
        if (!empty($discussion->unread) && $discussion->unread !== '-') {
            $replystring .= ' <span class="sep">/</span> <span class="unread">';
            if ($discussion->unread == 1) {
                $replystring .= get_string('unreadpostsone', 'local_forum');
            } else {
                $replystring .= get_string('unreadpostsnumber', 'local_forum', $discussion->unread);
            }
            $replystring .= '</span>';
        }

        $output .= html_writer::start_tag('div', array('class'=>'link'));
        $output .= html_writer::link($discussionlink, get_string($langstring, 'local_forum'));
        $output .= '&nbsp;('.$replystring.')';
        $output .= html_writer::end_tag('div'); // link
    }   
  
    //added like/dislike 14.11.22
    if($post->parent != 0){
        $forum_like = local_forum_like_unlike($post->forum,$post->discussion,$post->parent,$post->id,'local_forum');
        $output .= html_writer::start_tag('span', array('class'=>'course_like'));
        $output .= html_writer::tag('span', '<span class="course_detail_labelname"></span>', array('class'=>'course_detail_label'));
        $output .= html_writer::tag('span', $forum_like, array('class'=>'course_detail_value d-inline-block'));
        $output .= html_writer::end_tag('span');    
    }
    // Output footer if required
    if ($footer) {
        $output .= html_writer::tag('div', $footer, array('class'=>'footer'));
    }

    // Close remaining open divs
    $output .= html_writer::end_tag('div'); // content
    $output .= html_writer::end_tag('div'); // row
    $output .= html_writer::end_tag('div'); // local_forumpost

    // Mark the local_forum post as read if required
    if ($istracked && !$CFG->local_forum_usermarksread && !$postisread) {
        local_forum_tp_mark_post_read($USER->id, $post);
    }

    if ($return) {
        return $output;
    }
    echo $output;
    return;
}

/**
 * This function prints the overview of a discussion in the local_forum listing.
 * It needs some discussion information and some post information, these
 * happen to be combined for efficiency in the $post parameter by the function
 * that calls this one: local_forum_print_latest_discussions()
 *
 * @global object
 * @global object
 * @param object $post The post object (passed by reference for speed).
 * @param object $localforum The local_forum object.
 * @param int $group Current group.
 * @param string $datestring Format to use for the dates.
 * @param boolean $cantrack Is tracking enabled for this local_forum.
 * @param boolean $localforumtracked Is the user tracking this local_forum.
 * @param boolean $canviewparticipants True if user has the viewparticipants permission for this course
 * @param boolean $canviewhiddentimedposts True if user has the viewhiddentimedposts permission for this local_forum
 */
function local_forum_print_discussion_header(&$post, $localforum, $group = -1, $datestring = "",
                                        $cantrack = true, $localforumtracked = true, $canviewparticipants = true, $context = null,
                                        $canviewhiddentimedposts = false) {

    global $COURSE, $USER, $CFG, $OUTPUT, $PAGE;
    static $rowcount;
    static $strmarkalldread;

    if (!isset($rowcount)) {
        $rowcount = 0;
        $strmarkalldread = get_string('markalldread', 'local_forum');
    } else {
        $rowcount = ($rowcount + 1) % 2;
    }

    $post->subject = format_string($post->subject,true);

    $timeddiscussion = !empty($CFG->local_forum_enabletimedposts) && ($post->timestart || $post->timeend);
    $timedoutsidewindow = '';
    if ($timeddiscussion && ($post->timestart > time() || ($post->timeend != 0 && $post->timeend < time()))) {
        $timedoutsidewindow = ' dimmed_text';
    }

    echo "\n\n";
    echo '<tr class="discussion r'.$rowcount.$timedoutsidewindow.'">';

    $topicclass = 'topic starter';
    if (LOCAL_FORUM_DISCUSSION_PINNED == $post->pinned) {
        $topicclass .= ' pinned';
    }
    echo '<td class="'.$topicclass.'">';
    if (LOCAL_FORUM_DISCUSSION_PINNED == $post->pinned) {
        echo $OUTPUT->pix_icon('i/pinned', get_string('discussionpinned', 'local_forum'), 'local_forum');
    }
    $canalwaysseetimedpost = $USER->id == $post->userid || $canviewhiddentimedposts;
    if ($timeddiscussion && $canalwaysseetimedpost) {
        echo $PAGE->get_renderer('local_forum')->timed_discussion_tooltip($post, empty($timedoutsidewindow));
    }

    echo '<a href="'.$CFG->wwwroot.'/local/forum/discuss.php?d='.$post->discussion.'">'.$post->subject.'</a>';
    echo "</td>\n";

    // Picture
    $postuser = new stdClass();
    $postuserfields = explode(',', user_picture::fields());
    $postuser = username_load_fields_from_object($postuser, $post, null, $postuserfields);
    $postuser->id = $post->userid;
    echo '<td class="author">';
    echo '<span class="picture">';
    echo $OUTPUT->user_picture($postuser);
    echo '</span>';
    echo '<span class="name">';
    // User name
    $fullname = fullname($postuser, has_capability('moodle/site:viewfullnames', $context));
    echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$post->userid.'&amp;course=1">'.$fullname.'</a>';
    echo '</span>';
    echo "</td>\n";

    if (has_capability('local/forum:viewdiscussion', $context)) {   // Show the column with replies
        echo '<td class="replies">';
        echo '<a href="'.$CFG->wwwroot.'/local/forum/discuss.php?d='.$post->discussion.'">';
        echo $post->replies.'</a>';
        echo "</td>\n";

        if ($cantrack) {
            echo '<td class="replies">';
            if ($localforumtracked) {
                if ($post->unread > 0) {
                    echo '<span class="unread">';
                    echo '<a href="'.$CFG->wwwroot.'/local/forum/discuss.php?d='.$post->discussion.'#unread">';
                    echo $post->unread;
                    echo '</a>';
                    echo '<a title="'.$strmarkalldread.'" href="'.$CFG->wwwroot.'/local/forum/markposts.php?f='.
                         $localforum->id.'&amp;d='.$post->discussion.'&amp;mark=read&amp;returnpage=view.php&amp;sesskey=' . sesskey() . '">' .
                         $OUTPUT->pix_icon('t/markasread', $strmarkalldread) . '</a>';
                    echo '</span>';
                } else {
                    echo '<span class="read">';
                    echo $post->unread;
                    echo '</span>';
                }
            } else {
                echo '<span class="read">';
                echo '-';
                echo '</span>';
            }
            echo "</td>\n";
        }
    }

    echo '<td class="lastpost">';
    $usedate = (empty($post->timemodified)) ? $post->modified : $post->timemodified;  // Just in case
    $parenturl = '';
    $usermodified = new stdClass();
    $usermodified->id = $post->usermodified;
    $usermodified = username_load_fields_from_object($usermodified, $post, 'um');

    // In QA local_forums we check that the user can view participants.
    if ($localforum->type !== 'qanda' || $canviewparticipants) {
        echo '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$post->usermodified.'&amp;course=1">'.
             fullname($usermodified).'</a><br />';
        $parenturl = (empty($post->lastpostid)) ? '' : '&amp;parent='.$post->lastpostid;
    }

    echo '<a href="'.$CFG->wwwroot.'/local/forum/discuss.php?d='.$post->discussion.$parenturl.'">'.
          userdate($usedate, $datestring).'</a>';
    echo "</td>\n";

    // is_guest should be used here as this also checks whether the user is a guest in the current course.
    // Guests and visitors cannot subscribe - only enrolled users.
    if ((isloggedin()) && has_capability('local/forum:viewdiscussion', $context)) {
        // Discussion subscription.
        if (\local_forum\subscriptions::is_subscribable($localforum)) {
            echo '<td class="discussionsubscription">';
            echo local_forum_get_discussion_subscription_icon($localforum, $post->discussion);
            echo '</td>';
        }
    }

    echo "</tr>\n\n";

}

/**
 * Return the markup for the discussion subscription toggling icon.
 *
 * @param stdClass $localforum The local_forum object.
 * @param int $discussionid The discussion to create an icon for.
 * @return string The generated markup.
 */
function local_forum_get_discussion_subscription_icon($localforum, $discussionid, $returnurl = null, $includetext = false) {
    global $USER, $OUTPUT, $PAGE;

    if ($returnurl === null && $PAGE->url) {
        $returnurl = $PAGE->url->out();
    }

    $o = '';
    $subscriptionstatus = \local_forum\subscriptions::is_subscribed($USER->id, $localforum, $discussionid);
    $subscriptionlink = new moodle_url('/local/forum/subscribe.php', array(
        'sesskey' => sesskey(),
        'id' => $localforum->id,
        'd' => $discussionid,
        'returnurl' => $returnurl,
    ));

    if ($includetext) {
        $o .= $subscriptionstatus ? get_string('subscribed', 'local_forum') : get_string('notsubscribed', 'local_forum');
    }

    if ($subscriptionstatus) {
        $output = $OUTPUT->pix_icon('t/subscribed', get_string('clicktounsubscribe', 'local_forum'), 'local_forum');
        if ($includetext) {
            $output .= get_string('subscribed', 'local_forum');
        }

        return html_writer::link($subscriptionlink, $output, array(
            'title' => get_string('clicktounsubscribe', 'local_forum'),
            'class' => 'discussiontoggle iconsmall',
            'data-forumid' => $localforum->id,
            'data-discussionid' => $discussionid,
            'data-includetext' => $includetext,
        ));
    } else {
        $output = $OUTPUT->pix_icon('t/unsubscribed', get_string('clicktosubscribe', 'local_forum'), 'local_forum');
        if ($includetext) {
            $output .= get_string('notsubscribed', 'local_forum');
        }

        return html_writer::link($subscriptionlink, $output, array(
            'title' => get_string('clicktosubscribe', 'local_forum'),
            'class' => 'discussiontoggle iconsmall',
            'data-forumid' => $localforum->id,
            'data-discussionid' => $discussionid,
            'data-includetext' => $includetext,
        ));
    }
}

/**
 * Return a pair of spans containing classes to allow the subscribe and
 * unsubscribe icons to be pre-loaded by a browser.
 *
 * @return string The generated markup
 */
function local_forum_get_discussion_subscription_icon_preloaders() {
    $o = '';
    $o .= html_writer::span('&nbsp;', 'preload-subscribe');
    $o .= html_writer::span('&nbsp;', 'preload-unsubscribe');
    return $o;
}

/**
 * Print the drop down that allows the user to select how they want to have
 * the discussion displayed.
 *
 * @param int $id local_forum id if $localforumtype is 'single',
 *              discussion id for any other local_forum type
 * @param mixed $mode local_forum layout mode
 * @param string $localforumtype optional
 */
function local_forum_print_mode_form($id, $mode, $localforumtype='') {
    global $OUTPUT;
    if ($localforumtype == 'single') {
        $select = new single_select(new moodle_url("/local/forum/view.php", array('f'=>$id)), 'mode', local_forum_get_layout_modes(), $mode, null, "mode");
        $select->set_label(get_string('displaymode', 'local_forum'), array('class' => 'accesshide'));
        $select->class = "local_forummode";
    } else {
        $select = new single_select(new moodle_url("/local/forum/discuss.php", array('d'=>$id)), 'mode', local_forum_get_layout_modes(), $mode, null, "mode");
        $select->set_label(get_string('displaymode', 'local_forum'), array('class' => 'accesshide'));
    }
    echo $OUTPUT->render($select);
}

/**
 * @global object
 * @param object $course
 * @param string $search
 * @return string
 */
function local_forum_search_form($forum, $search='') {
    global $CFG, $PAGE;
    $localforumsearch = new \local_forum\output\quick_search_form($forum->id, $search);
    $output = $PAGE->get_renderer('local_forum');
    return $output->render($localforumsearch);
}


/**
 * @global object
 * @global object
 */
function local_forum_set_return() {
    global $CFG, $SESSION;

    if (! isset($SESSION->fromdiscussion)) {
        $referer = get_local_referer(false);
        // If the referer is NOT a login screen then save it.
        if (! strncasecmp("$CFG->wwwroot/login", $referer, 300)) {
            $SESSION->fromdiscussion = $referer;
        }
    }
}


/**
 * @global object
 * @param string|\moodle_url $default
 * @return string
 */
function local_forum_go_back_to($default) {
    global $SESSION;

    if (!empty($SESSION->fromdiscussion)) {
        $returnto = $SESSION->fromdiscussion;
        unset($SESSION->fromdiscussion);
        return $returnto;
    } else {
        return $default;
    }
}

/**
 * Given a discussion object that is being moved to $localforumto,
 * this function checks all posts in that discussion
 * for attachments, and if any are found, these are
 * moved to the new local_forum directory.
 *
 * @global object
 * @param object $discussion
 * @param int $localforumfrom source local_forum id
 * @param int $localforumto target local_forum id
 * @return bool success
 */
function local_forum_move_attachments($discussion, $localforumfrom, $localforumto) {
    global $DB;

    $fs = get_file_storage();

    $newcm = get_coursemodule_from_instance('local_forum', $localforumto);
    $oldcm = get_coursemodule_from_instance('local_forum', $localforumfrom);

    $newcontext = context_module::instance($newcm->id);
    $oldcontext = context_module::instance($oldcm->id);

    // loop through all posts, better not use attachment flag ;-)
    if ($posts = $DB->get_records('local_forum_posts', array('discussion'=>$discussion->id), '', 'id, attachment')) {
        foreach ($posts as $post) {
            $fs->move_area_files_to_new_context($oldcontext->id,
                    $newcontext->id, 'local_forum', 'post', $post->id);
            $attachmentsmoved = $fs->move_area_files_to_new_context($oldcontext->id,
                    $newcontext->id, 'local_forum', 'attachment', $post->id);
            if ($attachmentsmoved > 0 && $post->attachment != '1') {
                // Weird - let's fix it
                $post->attachment = '1';
                $DB->update_record('local_forum_posts', $post);
            } else if ($attachmentsmoved == 0 && $post->attachment != '') {
                // Weird - let's fix it
                $post->attachment = '';
                $DB->update_record('local_forum_posts', $post);
            }
        }
    }

    return true;
}

/**
 * Returns attachments as formated text/html optionally with separate images
 *
 * @global object
 * @global object
 * @global object
 * @param object $post
 * @param string $type html/text/separateimages
 * @return mixed string or array of (html text withouth images and image HTML)
 */
function local_forum_print_attachments($post, $type) {
    global $CFG, $DB, $USER, $OUTPUT;
    $context = context_system::instance();
    if (empty($post->attachment)) {
        return $type !== 'separateimages' ? '' : array('', '');
    }

    if (!in_array($type, array('separateimages', 'html', 'text'))) {
        return $type !== 'separateimages' ? '' : array('', '');
    }

    $strattachment = get_string('attachment', 'local_forum');

    $fs = get_file_storage();

    $imagereturn = '';
    $output = '';

    $canexport = !empty($CFG->enableportfolios) && (has_capability('local/forum:exportpost', $context) || ($post->userid == $USER->id && has_capability('local/forum:exportownpost', $context)));

    if ($canexport) {
        require_once($CFG->libdir.'/portfoliolib.php');
    }

    // We retrieve all files according to the time that they were created.  In the case that several files were uploaded
    // at the sametime (e.g. in the case of drag/drop upload) we revert to using the filename.
    $files = $fs->get_area_files($context->id, 'local_forum', 'attachment', $post->id, "filename", false);

    if ($files) {
        if ($canexport) {
            $button = new portfolio_add_button();
        }
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $iconimage = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
            $path = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$context->id.'/local_forum/attachment/'.$post->id.'/'.$filename);

            if ($type == 'html') {
                $output .= "<a href=\"$path\">$iconimage</a> ";
                $output .= "<a href=\"$path\">".s($filename)."</a>";
                if ($canexport) {
                    $button->set_callback_options('local_forum_portfolio_caller', array('postid' => $post->id, 'attachment' => $file->get_id()), 'local_forum');
                    $button->set_format_by_file($file);
                    $output .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                }
                $output .= "<br />";

            } else if ($type == 'text') {
                $output .= "$strattachment ".s($filename).":\n$path\n";

            } else { //'returnimages'
                if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) {
                    // Image attachments don't get printed as links
                    $imagereturn .= "<br /><img src=\"$path\" alt=\"\" />";
                    if ($canexport) {
                        $button->set_callback_options('local_forum_portfolio_caller', array('postid' => $post->id, 'attachment' => $file->get_id()), 'local_forum');
                        $button->set_format_by_file($file);
                        $imagereturn .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                    }
                } else {
                    $output .= "<a href=\"$path\">$iconimage</a> ";
                    $output .= format_text("<a href=\"$path\">".s($filename)."</a>", FORMAT_HTML, array('context'=>$context));
                    if ($canexport) {
                        $button->set_callback_options('local_forum_portfolio_caller', array('postid' => $post->id, 'attachment' => $file->get_id()), 'local_forum');
                        $button->set_format_by_file($file);
                        $output .= $button->to_html(PORTFOLIO_ADD_ICON_LINK);
                    }
                    $output .= '<br />';
                }
            }
        }
    }

    if ($type !== 'separateimages') {
        return $output;

    } else {
        return array($output, $imagereturn);
    }
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Lists all browsable file areas
 *
 * @package  local_forum
 * @category files
 * @param stdClass $context context object
 * @return array
 */
function local_forum_get_file_areas($context) {
    return array(
        'attachment' => get_string('areaattachment', 'local_forum'),
        'post' => get_string('areapost', 'local_forum'),
    );
}

/**
 * Serves the local_forum attachments. Implements needed access control ;-)
 *
 * @package  local_forum
 * @category files
 * @param stdClass $course course object
 * @param stdClass $module module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function local_forum_pluginfile($course, $module, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    $areas = local_forum_get_file_areas($context);

    // filearea must contain a real area
    if (!isset($areas[$filearea])) {
        return false;
    }

    $postid = (int)array_shift($args);

    if (!$post = $DB->get_record('local_forum_posts', array('id'=>$postid))) {
        return false;
    }

    if (!$discussion = $DB->get_record('local_forum_discussions', array('id'=>$post->discussion))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_forum/$filearea/$postid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}

/**
 * If successful, this function returns the name of the file
 *
 * @global object
 * @param object $post is a full post record, including course and local_forum
 * @param object $localforum
 * @param mixed $mform
 * @param string $unused
 * @return bool
 */
function local_forum_add_attachment($post, $localforum, $mform=null, $unused=null) {
    global $DB;

    if (empty($mform)) {
        return false;
    }

    if (empty($post->attachments)) {
        return true;   // Nothing to do
    }

    $context = context_system::instance();

    $info = file_get_draft_area_info($post->attachments);
    $present = ($info['filecount']>0) ? '1' : '';
    file_save_draft_area_files($post->attachments, $context->id, 'local_forum', 'attachment', $post->id,
            local_forum_post_form::attachment_options($localforum));

    $DB->set_field('local_forum_posts', 'attachment', $present, array('id'=>$post->id));

    return true;
}

/**
 * Add a new post in an existing discussion.
 *
 * @param   stdClass    $post       The post data
 * @param   mixed       $mform      The submitted form
 * @param   string      $unused
 * @return int
 */
function local_forum_add_new_post($post, $mform, $unused = null) {
    global $USER, $DB;

    $discussion = $DB->get_record('local_forum_discussions', array('id' => $post->discussion));
    $localforum      = $DB->get_record('local_forum', array('id' => $discussion->forum));


    $post->created    = $post->modified = time();
    $post->mailed     = LOCAL_FORUM_MAILED_PENDING;
    $post->userid     = $USER->id;
    $post->attachment = "";
    if (!isset($post->totalscore)) {
        $post->totalscore = 0;
    }
    if (!isset($post->mailnow)) {
        $post->mailnow    = 0;
    }
    $context = context_system::instance();
    $post->id = $DB->insert_record("local_forum_posts", $post);
    $post->message = file_save_draft_area_files($post->itemid, $context->id, 'local_forum', 'post', $post->id,
            local_forum_post_form::editor_options($context, null), $post->message);
    $DB->set_field('local_forum_posts', 'message', $post->message, array('id'=>$post->id));
    local_forum_add_attachment($post, $localforum, $mform);

    // Update discussion modified date
    $DB->set_field("local_forum_discussions", "timemodified", $post->modified, array("id" => $post->discussion));
    $DB->set_field("local_forum_discussions", "usermodified", $post->userid, array("id" => $post->discussion));

    if (local_forum_tp_can_track_forums($localforum) && local_forum_tp_is_tracked($localforum)) {
        local_forum_tp_mark_post_read($post->userid, $post);
    }

   /*  if (isset($post->tags)) {
        core_tag_tag::set_item_tags('local_forum', 'local_forum_posts', $post->id, $context, $post->tags);
    } */

    return $post->id;
}

/**
 * Update a post.
 *
 * @param   stdClass    $newpost    The post to update
 * @param   mixed       $mform      The submitted form
 * @param   string      $unused
 * @return  bool
 */
function local_forum_update_post($newpost, $mform, $unused = null) {
    global $DB, $USER;

    $post       = $DB->get_record('local_forum_posts', array('id' => $newpost->id));
    $discussion = $DB->get_record('local_forum_discussions', array('id' => $post->discussion));
    $localforum      = $DB->get_record('local_forum', array('id' => $discussion->forum));
    $context    = context_system::instance();

    // Allowed modifiable fields.
    $modifiablefields = [
        'subject',
        'message',
        'messageformat',
        'messagetrust',
        'timestart',
        'timeend',
        'pinned',
        'attachments',
    ];
    foreach ($modifiablefields as $field) {
        if (isset($newpost->{$field})) {
            $post->{$field} = $newpost->{$field};
        }
    }
    $post->modified = time();

    // Last post modified tracking.
    $discussion->timemodified = $post->modified;
    $discussion->usermodified = $post->userid;

    if (!$post->parent) {   // Post is a discussion starter - update discussion title and times too
        $discussion->name      = $post->subject;
        $discussion->timestart = $post->timestart;
        $discussion->timeend   = $post->timeend;

        if (isset($post->pinned)) {
            $discussion->pinned = $post->pinned;
        }
    }
    $post->message = file_save_draft_area_files($newpost->itemid, $context->id, 'local_forum', 'post', $post->id,
            local_forum_post_form::editor_options($context, $post->id), $post->message);
    $DB->update_record('local_forum_posts', $post);
    $DB->update_record('local_forum_discussions', $discussion);

    local_forum_add_attachment($post, $localforum, $mform);

   /*  if (isset($newpost->tags)) {
        core_tag_tag::set_item_tags('local_forum', 'local_forum_posts', $post->id, $context, $newpost->tags);
    }
 */
    if (local_forum_tp_can_track_forums($localforum) && local_forum_tp_is_tracked($localforum)) {
        local_forum_tp_mark_post_read($USER->id, $post);
    }

    return true;
}

/**
 * Given an object containing all the necessary data,
 * create a new discussion and return the id
 *
 * @param object $post
 * @param mixed $mform
 * @param string $unused
 * @param int $userid
 * @return object
 */
function local_forum_add_discussion($discussion, $mform=null, $unused=null, $userid=null) {
    global $USER, $CFG, $DB;

    $timenow = isset($discussion->timenow) ? $discussion->timenow : time();

    if (is_null($userid)) {
        $userid = $USER->id;
    }

    // The first post is stored as a real post, and linked
    // to from the discuss entry.

    $localforum = $DB->get_record('local_forum', array('id'=>$discussion->forum));

    $post = new stdClass();
    $post->discussion    = 0;
    $post->parent        = 0;
    $post->userid        = $userid;
    $post->created       = $timenow;
    $post->modified      = $timenow;
    $post->mailed        = LOCAL_FORUM_MAILED_PENDING;
    $post->subject       = $discussion->name;
    $post->message       = $discussion->message;
    $post->messageformat = $discussion->messageformat;
    $post->messagetrust  = true;
    $post->attachments   = isset($discussion->attachments) ? $discussion->attachments : null;
    $post->forum         = $localforum->id;     // speedup
    $post->course        = 0; // speedup
    $post->mailnow       = $discussion->mailnow;

    $post->id = $DB->insert_record("local_forum_posts", $post);

    if (!empty($localforum->id) && !empty($discussion->itemid)) {   // In "single simple discussions" this may not exist yet
        $context = context_system::instance();
        $text = file_save_draft_area_files($discussion->itemid, $context->id, 'local_forum', 'post', $post->id,
                local_forum_post_form::editor_options($context, null), $post->message);
        $DB->set_field('local_forum_posts', 'message', $text, array('id'=>$post->id));
    }

    // Now do the main entry for the discussion, linking to this first post

    $discussion->firstpost    = $post->id;
    $discussion->timemodified = $timenow;
    $discussion->usermodified = $post->userid;
    $discussion->userid       = $userid;
    $discussion->assessed     = 0;
    $discussion->course     = 0;

    $post->discussion = $DB->insert_record("local_forum_discussions", $discussion);

    // Finally, set the pointer on the post.
    $DB->set_field("local_forum_posts", "discussion", $post->discussion, array("id"=>$post->id));

    if (!empty($localforum->id)) {
        local_forum_add_attachment($post, $localforum, $mform, $unused);
    }

   /*  if (isset($discussion->tags)) {
        core_tag_tag::set_item_tags('local_forum', 'local_forum_posts', $post->id, context_system::instance(), $discussion->tags);
    } */

    if (local_forum_tp_can_track_forums($localforum) && local_forum_tp_is_tracked($localforum)) {
        local_forum_tp_mark_post_read($post->userid, $post);
    }

    return $post->discussion;
}


/**
 * Deletes a discussion and handles all associated cleanup.
 *
 * @global object
 * @param object $discussion Discussion to delete
 * @param bool $fulldelete True when deleting entire local_forum
 * @param object $localforum Forum
 * @return bool
 */
function local_forum_delete_discussion($discussion, $fulldelete, $localforum) {
    global $DB, $CFG;

    $result = true;

    if ($posts = $DB->get_records("local_forum_posts", array("discussion" => $discussion->id))) {
        foreach ($posts as $post) {
            $post->forum  = $discussion->forum;
            if (!local_forum_delete_post($post, 'ignore', $localforum, $fulldelete)) {
                $result = false;
            }
        }
    }

    local_forum_tp_delete_read_records(-1, -1, $discussion->id);

    // Discussion subscriptions must be removed before discussions because of key constraints.
    $DB->delete_records('local_forum_discussion_subs', array('discussion' => $discussion->id));
    if (!$DB->delete_records("local_forum_discussions", array("id" => $discussion->id))) {
        $result = false;
    }

    return $result;
}


/**
 * Deletes a single local_forum post.
 *
 * @global object
 * @param object $post Forum post object
 * @param mixed $children Whether to delete children. If false, returns false
 *   if there are any children (without deleting the post). If true,
 *   recursively deletes all children. If set to special value 'ignore', deletes
 *   post regardless of children (this is for use only when deleting all posts
 *   in a disussion).
 * @param object $localforum Forum
 * @param bool $skipcompletion True to skip updating completion state if it
 *   would otherwise be updated, i.e. when deleting entire local_forum anyway.
 * @return bool
 */
function local_forum_delete_post($post, $children, $localforum, $skipcompletion=false) {
    global $DB, $CFG, $USER;
    $context = context_system::instance();

    if ($children !== 'ignore' && ($childposts = $DB->get_records('local_forum_posts', array('parent'=>$post->id)))) {
       if ($children) {
           foreach ($childposts as $childpost) {
               local_forum_delete_post($childpost, true, $localforum, $skipcompletion);
           }
       } else {
           return false;
       }
    }

    // Delete attachments.
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'local_forum', 'attachment', $post->id);
    $fs->delete_area_files($context->id, 'local_forum', 'post', $post->id);

    if ($DB->delete_records("local_forum_posts", array("id" => $post->id))) {

        local_forum_tp_delete_read_records(-1, $post->id);

    // Just in case we are deleting the last post
        local_forum_discussion_update_last_post($post->discussion);
        return true;
    }
    return false;
}

/**
 * Sends post content to plagiarism plugin
 * @param object $post Forum post object
 * @param string $name
 * @return bool
*/
function local_forum_trigger_content_uploaded_event($post, $name) {
    $context = context_system::instance();
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_forum', 'attachment', $post->id, "timemodified", false);
    return true;
}

/**
 * @global object
 * @param object $post
 * @param bool $children
 * @return int
 */
function local_forum_count_replies($post, $children=true) {
    global $DB;
    $count = 0;

    if ($children) {
        if ($childposts = $DB->get_records('local_forum_posts', array('parent' => $post->id))) {
           foreach ($childposts as $childpost) {
               $count ++;                   // For this child
               $count += local_forum_count_replies($childpost, true);
           }
        }
    } else {
        $count += $DB->count_records('local_forum_posts', array('parent' => $post->id));
    }

    return $count;
}

/**
 * Given a new post, subscribes or unsubscribes as appropriate.
 * Returns some text which describes what happened.
 *
 * @param object $fromform The submitted form
 * @param stdClass $localforum The local_forum record
 * @param stdClass $discussion The local_forum discussion record
 * @return string
 */
function local_forum_post_subscription($fromform, $localforum, $discussion) {
    global $USER;

    if (\local_forum\subscriptions::is_forcesubscribed($localforum)) {
        return "";
    } else if (\local_forum\subscriptions::subscription_disabled($localforum)) {
        $subscribed = \local_forum\subscriptions::is_subscribed($USER->id, $localforum);
        if ($subscribed && !has_capability('moodle/course:manageactivities', context_course::instance($localforum->course), $USER->id)) {
            // This user should not be subscribed to the local_forum.
            \local_forum\subscriptions::unsubscribe_user($USER->id, $localforum);
        }
        return "";
    }

    $info = new stdClass();
    $info->name  = fullname($USER);
    $info->discussion = format_string($discussion->name);
    $info->forum = format_string($localforum->name);

    if (isset($fromform->discussionsubscribe) && $fromform->discussionsubscribe) {
        if ($result = \local_forum\subscriptions::subscribe_user_to_discussion($USER->id, $discussion)) {
            return html_writer::tag('p', get_string('discussionnowsubscribed', 'local_forum', $info));
        }
    } else {
        if ($result = \local_forum\subscriptions::unsubscribe_user_from_discussion($USER->id, $discussion)) {
            return html_writer::tag('p', get_string('discussionnownotsubscribed', 'local_forum', $info));
        }
    }

    return '';
}

/**
 * Generate and return the subscribe or unsubscribe link for a local_forum.
 *
 * @param object $localforum the local_forum. Fields used are $localforum->id and $localforum->forcesubscribe.
 * @param object $context the context object for this local_forum.
 * @param array $messages text used for the link in its various states
 *      (subscribed, unsubscribed, forcesubscribed or cantsubscribe).
 *      Any strings not passed in are taken from the $defaultmessages array
 *      at the top of the function.
 * @param bool $cantaccessagroup
 * @param bool $unused1
 * @param bool $backtoindex
 * @param array $unused2
 * @return string
 */
function local_forum_get_subscribe_link($localforum, $context, $messages = array(), $cantaccessagroup = false, $unused1 = true,
    $backtoindex = false, $unused2 = null) {
    global $CFG, $USER, $PAGE, $OUTPUT;
    $defaultmessages = array(
        'subscribed' => get_string('unsubscribe', 'local_forum'),
        'unsubscribed' => get_string('subscribe', 'local_forum'),
        'cantaccessgroup' => get_string('no'),
        'forcesubscribed' => get_string('everyoneissubscribed', 'local_forum'),
        'cantsubscribe' => get_string('disallowsubscribe','local_forum')
    );
    $messages = $messages + $defaultmessages;

    if (\local_forum\subscriptions::is_forcesubscribed($localforum)) {
        return $messages['forcesubscribed'];
    } else if (\local_forum\subscriptions::subscription_disabled($localforum) &&
            !has_capability('local/forum:managesubscriptions', $context)) {
        return $messages['cantsubscribe'];
    } else if ($cantaccessagroup) {
        return $messages['cantaccessgroup'];
    } else {

        $subscribed = \local_forum\subscriptions::is_subscribed($USER->id, $localforum);
        if ($subscribed) {
            $linktext = $messages['subscribed'];
            $linktitle = get_string('subscribestop', 'local_forum');
        } else {
            $linktext = $messages['unsubscribed'];
            $linktitle = get_string('subscribestart', 'local_forum');
        }

        $options = array();
        if ($backtoindex) {
            $backtoindexlink = '&amp;backtoindex=1';
            $options['backtoindex'] = 1;
        } else {
            $backtoindexlink = '';
        }

        $options['id'] = $localforum->id;
        $options['sesskey'] = sesskey();
        $url = new moodle_url('/local/forum/subscribe.php', $options);
        return $OUTPUT->single_button($url, $linktext, 'get', array('title' => $linktitle));
    }
}

/**
 * Returns true if user created new discussion already.
 *
 * @param int $localforumid  The local_forum to check for postings
 * @param int $userid   The user to check for postings
 * @return bool
 */
function local_forum_user_has_posted_discussion($localforumid, $userid) {
    global $CFG, $DB;

    $sql = "SELECT 'x'
              FROM {local_forum_discussions} d, {local_forum_posts} p
             WHERE d.forum = ? AND p.discussion = d.id AND p.parent = 0 AND p.userid = ?";

    $params = [$localforumid, $userid];

    return $DB->record_exists_sql($sql, $params);
}

/**
 * @global object
 * @param int $localforumid
 * @param int $userid
 * @return array
 */
function local_forum_discussions_user_has_posted_in($localforumid, $userid) {
    global $CFG, $DB;

    $haspostedsql = "SELECT d.id AS id,
                            d.*
                       FROM {local_forum_posts} p,
                            {local_forum_discussions} d
                      WHERE p.discussion = d.id
                        AND d.forum = ?
                        AND p.userid = ?";

    return $DB->get_records_sql($haspostedsql, array($localforumid, $userid));
}

/**
 * @global object
 * @global object
 * @param int $localforumid
 * @param int $did
 * @param int $userid
 * @return bool
 */
function local_forum_user_has_posted($localforumid, $did, $userid) {
    global $DB;

    if (empty($did)) {
        // posted in any local_forum discussion?
        $sql = "SELECT 'x'
                  FROM {local_forum_posts} p
                  JOIN {local_forum_discussions} d ON d.id = p.discussion
                 WHERE p.userid = :userid AND d.forum = :local_forumid";
        return $DB->record_exists_sql($sql, array('local_forumid'=>$localforumid,'userid'=>$userid));
    } else {
        return $DB->record_exists('local_forum_posts', array('discussion'=>$did,'userid'=>$userid));
    }
}

/**
 * Returns creation time of the first user's post in given discussion
 * @global object $DB
 * @param int $did Discussion id
 * @param int $userid User id
 * @return int|bool post creation time stamp or return false
 */
function local_forum_get_user_posted_time($did, $userid) {
    global $DB;

    $posttime = $DB->get_field('local_forum_posts', 'MIN(created)', array('userid'=>$userid, 'discussion'=>$did));
    if (empty($posttime)) {
        return false;
    }
    return $posttime;
}

/**
 * @global object
 * @param object $localforum
 * @param object $context
 * @return bool
 */
function local_forum_user_can_post_discussion($localforum, $context=NULL) {
    // $localforum is an object
    global $USER;

    // shortcut - guest and not-logged-in users can not post
    if (isguestuser() or !isloggedin()) {
        return false;
    }

    if (!$context) {
        $context = context_system::instance();
    }

    if ($localforum->type == 'news') {
        $capname = 'local/forum:addnews';
    } else if ($localforum->type == 'qanda') {
        $capname = 'local/forum:addquestion';
    } else {
        $capname = 'local/forum:startdiscussion';
    }

    if (!has_capability($capname, $context)) {
        
        return false;
    }

    if ($localforum->type == 'single') {
        return false;
    }

    if ($localforum->type == 'eachuser') {
        if (local_forum_user_has_posted_discussion($localforum->id, $USER->id)) {
            return false;
        }
    }
    return true;
}

/**
 * This function checks whether the user can reply to posts in a local_forum
 * discussion. Use local_forum_user_can_post_discussion() to check whether the user
 * can start discussions.
 *
 * @global object
 * @global object
 * @uses DEBUG_DEVELOPER
 * @uses CONTEXT_MODULE
 * @uses VISIBLEGROUPS
 * @param object $localforum local_forum object
 * @param object $discussion
 * @param object $user
 * @param object $context
 * @return bool
 */
function local_forum_user_can_post($localforum, $discussion, $user=NULL, $context=NULL) {
    global $USER, $DB;
    if (empty($user)) {
        $user = $USER;
    }

    // shortcut - guest and not-logged-in users can not post
    if (isguestuser($user) or empty($user->id)) {
        return false;
    }

    if (!$context) {
        $context = context_system::instance();
    }

    // Check whether the discussion is locked.
    if (local_forum_discussion_is_locked($localforum, $discussion)) {
        if (!has_capability('local/forum:canoverridediscussionlock', $context)) {
            return false;
        }
    }

    if ($localforum->type == 'news') {
        $capname = 'local/forum:replynews';
    } else {
        $capname = 'local/forum:replypost';
    }

    if (has_capability($capname, $context, $user->id)) {
        return true;
    }

    if (has_capability('moodle/site:accessallgroups', $context)) {
        return true;
    }
}

/**
* Check to ensure a user can view a timed discussion.
*
* @param object $discussion
* @param object $user
* @param object $context
* @return boolean returns true if they can view post, false otherwise
*/
function local_forum_user_can_see_timed_discussion($discussion, $user, $context) {
    global $CFG;
    // Check that the user can view a discussion that is normally hidden due to access times.
    if (!empty($CFG->local_forum_enabletimedposts)) {
        $time = time();
        if (($discussion->timestart != 0 && $discussion->timestart > $time)
            || ($discussion->timeend != 0 && $discussion->timeend < $time)) {
            if (!has_capability('local/forum:viewhiddentimedposts', $context, $user->id)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * @global object
 * @global object
 * @uses DEBUG_DEVELOPER
 * @param object $localforum
 * @param object $discussion
 * @param object $context
 * @param object $user
 * @return bool
 */
function local_forum_user_can_see_discussion($localforum, $discussion, $context, $user=NULL) {
    global $USER, $DB;

    if (empty($user) || empty($user->id)) {
        $user = $USER;
    }

    // retrieve objects (yuk)
    if (is_numeric($localforum)) {
        debugging('missing full local_forum', DEBUG_DEVELOPER);
        if (!$localforum = $DB->get_record('local_forum',array('id'=>$localforum))) {
            return false;
        }
    }
    if (is_numeric($discussion)) {
        debugging('missing full discussion', DEBUG_DEVELOPER);
        if (!$discussion = $DB->get_record('local_forum_discussions',array('id'=>$discussion))) {
            return false;
        }
    }


    if (!has_capability('local/forum:viewdiscussion', $context)) {
        return false;
    }

    if (!local_forum_user_can_see_timed_discussion($discussion, $user, $context)) {
        return false;
    }

    return true;
}

/**
 * @global object
 * @global object
 * @param object $localforum
 * @param object $discussion
 * @param object $post
 * @param object $user
 * @return bool
 */
function local_forum_user_can_see_post($localforum, $discussion, $post, $user=NULL) {
    global $CFG, $USER, $DB;

    // Context used throughout function.
    $context = context_system::instance();

    // retrieve objects (yuk)
    if (is_numeric($localforum)) {
        debugging('missing full local_forum', DEBUG_DEVELOPER);
        if (!$localforum = $DB->get_record('local_forum',array('id'=>$localforum))) {
            return false;
        }
    }

    if (is_numeric($discussion)) {
        debugging('missing full discussion', DEBUG_DEVELOPER);
        if (!$discussion = $DB->get_record('local_forum_discussions',array('id'=>$discussion))) {
            return false;
        }
    }
    if (is_numeric($post)) {
        debugging('missing full post', DEBUG_DEVELOPER);
        if (!$post = $DB->get_record('local_forum_posts',array('id'=>$post))) {
            return false;
        }
    }

    if (!isset($post->id) && isset($post->parent)) {
        $post->id = $post->parent;
    }

    if (empty($user) || empty($user->id)) {
        $user = $USER;
    }

    $canviewdiscussion = has_capability('local/forum:viewdiscussion', $context, $user->id);
    if (!$canviewdiscussion && !has_all_capabilities(array('moodle/user:viewdetails', 'moodle/user:readuserposts'), context_user::instance($post->userid))) {
        return false;
    }

    if (!local_forum_user_can_see_timed_discussion($discussion, $user, $context)) {
        return false;
    }

    if ($localforum->type == 'qanda') {
        if (has_capability('local/forum:viewqandawithoutposting', $context, $user->id) || $post->userid == $user->id
                || (isset($discussion->firstpost) && $discussion->firstpost == $post->id)) {
            return true;
        }
        $firstpost = local_forum_get_firstpost_from_discussion($discussion->id);
        if ($firstpost->userid == $user->id) {
            return true;
        }
        $userfirstpost = local_forum_get_user_posted_time($discussion->id, $user->id);
        return (($userfirstpost !== false && (time() - $userfirstpost >= $CFG->maxeditingtime)));
    }
    return true;
}


/**
 * Prints the discussion view screen for a local_forum.
 *
 * @global object
 * @global object
 * @param object $course The current course object.
 * @param object $localforum Forum to be printed.
 * @param int $maxdiscussions .
 * @param string $displayformat The display format to use (optional).
 * @param string $sort Sort arguments for database query (optional).
 * @param int $groupmode Group mode of the local_forum (optional).
 * @param void $unused (originally current group)
 * @param int $page Page mode, page to display (optional).
 * @param int $perpage The maximum number of discussions per page(optional)
 * @param boolean $subscriptionstatus Whether the user is currently subscribed to the discussion in some fashion.
 *
 */
function local_forum_print_latest_discussions($localforum, $maxdiscussions = -1, $displayformat = 'plain', $sort = '',
                                        $currentgroup = -1, $groupmode = -1, $page = -1, $perpage = 100) {
    global $CFG, $USER, $OUTPUT, $PAGE;

    
    $context = context_system::instance();

    if (empty($sort)) {
        $sort = local_forum_get_default_sort_order();
    }

    $olddiscussionlink = false;

 // Sort out some defaults
    if ($perpage <= 0) {
        $perpage = 0;
        $page    = -1;
    }

    if ($maxdiscussions == 0) {
        // all discussions - backwards compatibility
        $page    = -1;
        $perpage = 0;
        if ($displayformat == 'plain') {
            $displayformat = 'header';  // Abbreviate display by default
        }

    } else if ($maxdiscussions > 0) {
        $page    = -1;
        $perpage = $maxdiscussions;
    }

    $fullpost = false;
    if ($displayformat == 'plain') {
        $fullpost = true;
    }


// Decide if current user is allowed to see ALL the current discussions or not

// If the user can post discussions, then this is a good place to put the
// button for it. We do not show the button if we are showing site news
// and the current user is a guest.

    $canstart = local_forum_user_can_post_discussion($localforum, $context);
    if (!$canstart and $localforum->type !== 'news') {
        if (isguestuser() or !isloggedin()) {
            $canstart = true;
        }
    }

    if ($canstart) {
        switch ($localforum->type) {
            case 'news':
            case 'blog':
                $buttonadd = get_string('addanewtopic', 'local_forum');
                break;
            case 'qanda':
                $buttonadd = get_string('addanewquestion', 'local_forum');
                break;
            default:
                $buttonadd = get_string('addanewdiscussion', 'local_forum');
                break;
        }
        echo "<div class='d-flex flex-row-reverse mb-2'>";
        echo "<div class=' d-inline-block coursebackup course_extended_menu_itemcontainer '>
            <a href='".$CFG->wwwroot."/local/forum/index.php' title='".get_string("back")."' class='course_extended_menu_itemlink'>
              <i class='icon fa fa-reply'></i>
            </a>
        </div>";
       

        $button = new single_button(new moodle_url('/local/forum/post.php', ['forum' => $localforum->id]), $buttonadd, 'get');
        $button->class = 'singlebutton local_forumaddnew';
        $button->formid = 'newdiscussionform';
        echo $OUTPUT->render($button);
        echo "</div>";


    } else if (isguestuser() or !isloggedin() or $localforum->type == 'news' or
        $localforum->type == 'qanda' and !has_capability('local/forum:addquestion', $context) or
        $localforum->type != 'qanda' and !has_capability('local/forum:startdiscussion', $context)) {
        // no button and no info

    } 

// Get all the recent discussions we're allowed to see

    $getuserlastmodified = ($displayformat == 'header');

    if (! $discussions = local_forum_get_discussions($localforum, $sort, $fullpost, null, $maxdiscussions, $getuserlastmodified, $page, $perpage) ) {
        echo '<div class="local_forumnodiscuss">';
        if ($localforum->type == 'news') {
            echo '('.get_string('nonews', 'local_forum').')';
        } else if ($localforum->type == 'qanda') {
            echo '('.get_string('noquestions','local_forum').')';
        } else {
            echo '('.get_string('nodiscussions', 'local_forum').')';
        }
        echo "</div>\n";
        return;
    }

// If we want paging
    if ($page != -1) {
        ///Get the number of discussions found
        $numdiscussions = local_forum_get_discussions_count($localforum->id);

        ///Show the paging bar
        echo $OUTPUT->paging_bar($numdiscussions, $page, $perpage, "view.php?f=$localforum->id");
        if ($numdiscussions > 1000) {
            // saves some memory on sites with very large local_forums
            $replies = local_forum_count_discussion_replies($localforum->id, $sort, $maxdiscussions, $page, $perpage);
        } else {
            $replies = local_forum_count_discussion_replies($localforum->id);
        }

    } else {
        $replies = local_forum_count_discussion_replies($localforum->id);

        if ($maxdiscussions > 0 and $maxdiscussions <= count($discussions)) {
            $olddiscussionlink = true;
        }
    }

    $canviewparticipants = course_can_view_participants($context);
    $canviewhiddentimedposts = has_capability('local/forum:viewhiddentimedposts', $context);

    $strdatestring = get_string('strftimerecentfull');

    // Check if the local_forum is tracked.
    if ($cantrack = local_forum_tp_can_track_forums($localforum)) {
        $localforumtracked = local_forum_tp_is_tracked($localforum);
    } else {
        $localforumtracked = false;
    }

    if ($localforumtracked) {
        $unreads = local_forum_get_discussions_unread($localforum);
    } else {
        $unreads = array();
    }

    foreach ($discussions as $discussion) {
        if ($localforum->type == 'qanda' && !has_capability('local/forum:viewqandawithoutposting', $context) &&
            !local_forum_user_has_posted($localforum->id, $discussion->discussion, $USER->id)) {
            $canviewparticipants = false;
        }

        if (!empty($replies[$discussion->discussion])) {
            $discussion->replies = $replies[$discussion->discussion]->replies;
            $discussion->lastpostid = $replies[$discussion->discussion]->lastpostid;
        } else {
            $discussion->replies = 0;
        }

        // SPECIAL CASE: The front page can display a news item post to non-logged in users.
        // All posts are read in this case.
        if (!$localforumtracked) {
            $discussion->unread = '-';
        } else if (empty($USER)) {
            $discussion->unread = 0;
        } else {
            if (empty($unreads[$discussion->discussion])) {
                $discussion->unread = 0;
            } else {
                $discussion->unread = $unreads[$discussion->discussion];
            }
        }

        if (isloggedin()) {
            $ownpost = ($discussion->userid == $USER->id);
        } else {
            $ownpost=false;
        }
        // Use discussion name instead of subject of first post.
        $discussion->subject = $discussion->name;

        switch ($displayformat) {
            case 'header':
            $renderer = $PAGE->get_renderer('local_forum');

            echo $renderer->get_forumtopics_list($discussion, $localforum, $group = -1, $strdatestring, $cantrack, $localforumtracked, $canviewparticipants, $context, $canviewhiddentimedposts);
            break;
            default:
                $link = false;

                if ($discussion->replies) {
                    $link = true;
                } else {
                    $link = local_forum_user_can_see_discussion($localforum, $discussion, $context, $USER);
                }

                $discussion->forum = $localforum->id;

                local_forum_print_post($discussion, $discussion, $localforum, $ownpost, 0, $link, false,
                        '', null, true, $localforumtracked);
            break;
        }
    }

    if ($olddiscussionlink) {
        if ($localforum->type == 'news') {
            $strolder = get_string('oldertopics', 'local_forum');
        } else {
            $strolder = get_string('olderdiscussions', 'local_forum');
        }
        echo '<div class="local_forumolddiscuss">';
        echo '<a href="'.$CFG->wwwroot.'/local/forum/view.php?f='.$localforum->id.'&amp;showall=1">';
        echo $strolder.'</a> ...</div>';
    }

    if ($page != -1) { ///Show the paging bar
        echo $OUTPUT->paging_bar($numdiscussions, $page, $perpage, "view.php?f=$localforum->id");
    }
}


/**
 * Prints a local_forum discussion
 *
 * @uses CONTEXT_MODULE
 * @uses FORUM_MODE_FLATNEWEST
 * @uses FORUM_MODE_FLATOLDEST
 * @uses FORUM_MODE_THREADED
 * @uses FORUM_MODE_NESTED
 * @param stdClass $localforum
 * @param stdClass $discussion
 * @param stdClass $post
 * @param int $mode
 * @param mixed $canreply
 * @param bool $canrate
 */
function local_forum_print_discussion($localforum, $discussion, $post, $mode, $canreply=NULL, $canrate=false) {
    global $USER, $CFG;

    require_once($CFG->dirroot.'/rating/lib.php');

    $ownpost = (isloggedin() && $USER->id == $post->userid);

    $context = context_system::instance();
    if ($canreply === NULL) {
        $reply = local_forum_user_can_post($localforum, $discussion, $USER, $context);
    } else {
        $reply = $canreply;
    }

    $posters = array();

    // preload all posts - TODO: improve...
    if ($mode == LOCAL_FORUM_MODE_FLATNEWEST) {
        $sort = "p.created DESC";
    } else {
        $sort = "p.created ASC";
    }

    $localforumtracked = local_forum_tp_is_tracked($localforum);
    $posts = local_forum_get_all_discussion_posts($discussion->id, $sort, $localforumtracked);
    $post = $posts[$post->id];

    foreach ($posts as $pid=>$p) {
        $posters[$p->userid] = $p->userid;
    }


    //load ratings
    if ($localforum->assessed != RATING_AGGREGATE_NONE) {
        $ratingoptions = new stdClass;
        $ratingoptions->context = $context;
        $ratingoptions->component = 'local_forum';
        $ratingoptions->ratingarea = 'post';
        $ratingoptions->items = $posts;
        $ratingoptions->aggregate = $localforum->assessed;//the aggregation method
        $ratingoptions->scaleid = $localforum->scale;
        $ratingoptions->userid = $USER->id;
        if ($localforum->type == 'single' or !$discussion->id) {
            $ratingoptions->returnurl = "$CFG->wwwroot/local/forum/view.php?id=$localforum->id";
        } else {
            $ratingoptions->returnurl = "$CFG->wwwroot/local/forum/discuss.php?d=$discussion->id";
        }
        $ratingoptions->assesstimestart = $localforum->assesstimestart;
        $ratingoptions->assesstimefinish = $localforum->assesstimefinish;

        $rm = new rating_manager();
        $posts = $rm->get_ratings($ratingoptions);
    }


    $post->forum = $localforum->id;   // Add the local_forum id to the post object, later used by local_forum_print_post
    $post->forumtype = $localforum->type;

    $post->subject = format_string($post->subject);

    $postread = !empty($post->postread);

    local_forum_print_post($post, $discussion, $localforum, $ownpost, $reply, false, '', '', $postread, true, $localforumtracked);

    switch ($mode) {
        case LOCAL_FORUM_MODE_FLATOLDEST :
        case LOCAL_FORUM_MODE_FLATNEWEST :
        default:
            local_forum_print_posts_flat($localforum, $discussion, $post, $mode, $reply, $localforumtracked, $posts);
            break;

        case LOCAL_FORUM_MODE_THREADED :
            local_forum_print_posts_threaded($localforum, $discussion, $post, 0, $reply, $localforumtracked, $posts);
            break;

        case LOCAL_FORUM_MODE_NESTED :
            local_forum_print_posts_nested($localforum, $discussion, $post, $reply, $localforumtracked, $posts);
            break;
    }
}


/**
 * @global object
 * @global object
 * @uses FORUM_MODE_FLATNEWEST
 * @param object $localforum
 * @param object $discussion
 * @param object $post
 * @param object $mode
 * @param bool $reply
 * @param bool $localforumtracked
 * @param array $posts
 * @return void
 */
function local_forum_print_posts_flat($localforum, $discussion, $post, $mode, $reply, $localforumtracked, $posts) {
    global $USER, $CFG;

    $link  = false;

    foreach ($posts as $post) {
        if (!$post->parent) {
            continue;
        }
        $post->subject = format_string($post->subject);
        $ownpost = ($USER->id == $post->userid);

        $postread = !empty($post->postread);

        local_forum_print_post($post, $discussion, $localforum, $ownpost, $reply, $link,
                             '', '', $postread, true, $localforumtracked);
    }
}

/**
 * @todo Document this function
 *
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @return void
 */
function local_forum_print_posts_threaded($localforum, $discussion, $parent, $depth, $reply, $localforumtracked, $posts) {
    global $USER, $CFG;

    $link  = false;

    if (!empty($posts[$parent->id]->children)) {
        $posts = $posts[$parent->id]->children;

        $context       = context_system::instance();
        $canviewfullnames = has_capability('moodle/site:viewfullnames', $context);

        foreach ($posts as $post) {

            echo '<div class="indent">';
            if ($depth > 0) {
                $ownpost = ($USER->id == $post->userid);
                $post->subject = format_string($post->subject);

                $postread = !empty($post->postread);

                local_forum_print_post($post, $discussion, $localforum, $ownpost, $reply, $link,
                                     '', '', $postread, true, $localforumtracked);
            } else {
                if (!local_forum_user_can_see_post($localforum, $discussion, $post, NULL)) {
                    echo "</div>\n";
                    continue;
                }
                $by = new stdClass();
                $by->name = fullname($post, $canviewfullnames);
                $by->date = userdate($post->modified);

                if ($localforumtracked) {
                    if (!empty($post->postread)) {
                        $style = '<span class="local_forumthread read">';
                    } else {
                        $style = '<span class="local_forumthread unread">';
                    }
                } else {
                    $style = '<span class="local_forumthread">';
                }
                echo $style."<a name=\"$post->id\"></a>".
                     "<a href=\"discuss.php?d=$post->discussion&amp;parent=$post->id\">".format_string($post->subject,true)."</a> ";
                print_string("bynameondate", "local_forum", $by);
                echo "</span>";
            }

            local_forum_print_posts_threaded($localforum, $discussion, $post, $depth-1, $reply, $localforumtracked, $posts);
            echo "</div>\n";
        }
    }
}

/**
 * @todo Document this function
 * @global object
 * @global object
 * @return void
 */
function local_forum_print_posts_nested($localforum, $discussion, $parent, $reply, $localforumtracked, $posts) {
    global $USER, $CFG;

    $link  = false;

    if (!empty($posts[$parent->id]->children)) {
        $posts = $posts[$parent->id]->children;

        foreach ($posts as $post) {

            echo '<div class="indent">';
            if (!isloggedin()) {
                $ownpost = false;
            } else {
                $ownpost = ($USER->id == $post->userid);
            }

            $post->subject = format_string($post->subject);
            $postread = !empty($post->postread);

            local_forum_print_post($post, $discussion, $localforum, $ownpost, $reply, $link,
                                 '', '', $postread, true, $localforumtracked);
            local_forum_print_posts_nested($localforum, $discussion, $post, $reply, $localforumtracked, $posts);
            echo "</div>\n";
        }
    }
}


/**
 * recursively sets the discussion field to $discussionid on $postid and all its children
 * used when pruning a post
 *
 * @global object
 * @param int $postid
 * @param int $discussionid
 * @return bool
 */
function local_forum_change_discussionid($postid, $discussionid) {
    global $DB;
    $DB->set_field('local_forum_posts', 'discussion', $discussionid, array('id' => $postid));
    if ($posts = $DB->get_records('local_forum_posts', array('parent' => $postid))) {
        foreach ($posts as $post) {
            local_forum_change_discussionid($post->id, $discussionid);
        }
    }
    return true;
}

/**
 * Prints the editing button on subscribers page
 *
 * @global object
 * @param int $localforumid
 * @return string
 */
function local_forum_update_subscriptions_button($localforumid) {
    global $CFG, $USER;

    if (!empty($USER->subscriptionsediting)) {
        $string = get_string('managesubscriptionsoff', 'local_forum');
        $edit = "off";
    } else {
        $string = get_string('managesubscriptionson', 'local_forum');
        $edit = "on";
    }

    $subscribers = html_writer::start_tag('form', array('action' => $CFG->wwwroot . '/local/forum/subscribers.php',
        'method' => 'get', 'class' => 'form-inline'));
    $subscribers .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => $string,
        'class' => 'btn btn-secondary'));
    $subscribers .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $localforumid));
    $subscribers .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'edit', 'value' => $edit));
    $subscribers .= html_writer::end_tag('form');

    return $subscribers;
}

// Functions to do with read tracking.

/**
 * Mark posts as read.
 *
 * @global object
 * @global object
 * @param object $user object
 * @param array $postids array of post ids
 * @return boolean success
 */
function local_forum_tp_mark_posts_read($user, $postids) {
    global $CFG, $DB;

    if (!local_forum_tp_can_track_forums(false, $user)) {
        return true;
    }

    $status = true;

    $now = time();
    $cutoffdate = $now - ($CFG->local_forum_oldpostdays * 24 * 3600);

    if (empty($postids)) {
        return true;

    } else if (count($postids) > 200) {
        while ($part = array_splice($postids, 0, 200)) {
            $status = local_forum_tp_mark_posts_read($user, $part) && $status;
        }
        return $status;
    }

    list($usql, $postidparams) = $DB->get_in_or_equal($postids, SQL_PARAMS_NAMED, 'postid');

    $insertparams = array(
        'userid1' => $user->id,
        'userid2' => $user->id,
        'userid3' => $user->id,
        'firstread' => $now,
        'lastread' => $now,
        'cutoffdate' => $cutoffdate,
    );
    $params = array_merge($postidparams, $insertparams);

    if ($CFG->local_forum_allowforcedreadtracking) {
        $trackingsql = "AND (f.trackingtype = ".LOCAL_FORUM_TRACKING_FORCED."
                        OR (f.trackingtype = ".LOCAL_FORUM_TRACKING_OPTIONAL." AND tf.id IS NULL))";
    } else {
        $trackingsql = "AND ((f.trackingtype = ".LOCAL_FORUM_TRACKING_OPTIONAL."  OR f.trackingtype = ".LOCAL_FORUM_TRACKING_FORCED.")
                            AND tf.id IS NULL)";
    }

    // First insert any new entries.
    $sql = "INSERT INTO {local_forum_read} (userid, postid, discussionid, local_forumid, firstread, lastread)

            SELECT :userid1, p.id, p.discussion, d.forum, :firstread, :lastread
                FROM {local_forum_posts} p
                    JOIN {local_forum_discussions} d       ON d.id = p.discussion
                    JOIN {local_forum} f                   ON f.id = d.forum
                    LEFT JOIN {local_forum_track_prefs} tf ON (tf.userid = :userid2 AND tf.local_forumid = f.id)
                    LEFT JOIN {local_forum_read} fr        ON (
                            fr.userid = :userid3
                        AND fr.postid = p.id
                        AND fr.discussionid = d.id
                        AND fr.local_forumid = f.id
                    )
                WHERE p.id $usql
                    AND p.modified >= :cutoffdate
                    $trackingsql
                    AND fr.id IS NULL";

    $status = $DB->execute($sql, $params) && $status;

    // Then update all records.
    $updateparams = array(
        'userid' => $user->id,
        'lastread' => $now,
    );
    $params = array_merge($postidparams, $updateparams);
    $status = $DB->set_field_select('local_forum_read', 'lastread', $now, '
                userid      =  :userid
            AND lastread    <> :lastread
            AND postid      ' . $usql,
            $params) && $status;

    return $status;
}

/**
 * Mark post as read.
 * @global object
 * @global object
 * @param int $userid
 * @param int $postid
 */
function local_forum_tp_add_read_record($userid, $postid) {
    global $CFG, $DB;

    $now = time();
    $cutoffdate = $now - ($CFG->local_forum_oldpostdays * 24 * 3600);

    if (!$DB->record_exists('local_forum_read', array('userid' => $userid, 'postid' => $postid))) {
        $sql = "INSERT INTO {local_forum_read} (userid, postid, discussionid, local_forumid, firstread, lastread)

                SELECT ?, p.id, p.discussion, d.forum, ?, ?
                  FROM {local_forum_posts} p
                       JOIN {local_forum_discussions} d ON d.id = p.discussion
                 WHERE p.id = ? AND p.modified >= ?";
        return $DB->execute($sql, array($userid, $now, $now, $postid, $cutoffdate));

    } else {
        $sql = "UPDATE {local_forum_read}
                   SET lastread = ?
                 WHERE userid = ? AND postid = ?";
        return $DB->execute($sql, array($now, $userid, $userid));
    }
}

/**
 * If its an old post, do nothing. If the record exists, the maintenance will clear it up later.
 *
 * @param   int     $userid The ID of the user to mark posts read for.
 * @param   object  $post   The post record for the post to mark as read.
 * @param   mixed   $unused
 * @return bool
 */
function local_forum_tp_mark_post_read($userid, $post, $unused = null) {
    if (!local_forum_tp_is_post_old($post)) {
        return local_forum_tp_add_read_record($userid, $post->id);
    } else {
        return true;
    }
}

/**
 * Marks a whole local_forum as read, for a given user
 *
 * @global object
 * @global object
 * @param object $user
 * @param int $localforumid
 * @param int|bool $groupid
 * @return bool
 */
function local_forum_tp_mark_local_forum_read($user, $localforumid, $groupid=false) {
    global $CFG, $DB;

    $cutoffdate = time() - ($CFG->local_forum_oldpostdays*24*60*60);

    $groupsel = "";
    $params = array($user->id, $localforumid, $cutoffdate);

    if ($groupid !== false) {
        $groupsel = " AND (d.groupid = ? OR d.groupid = -1)";
        $params[] = $groupid;
    }

    $sql = "SELECT p.id
              FROM {local_forum_posts} p
                   LEFT JOIN {local_forum_discussions} d ON d.id = p.discussion
                   LEFT JOIN {local_forum_read} r        ON (r.postid = p.id AND r.userid = ?)
             WHERE d.forum = ?
                   AND p.modified >= ? AND r.id is NULL
                   $groupsel";

    if ($posts = $DB->get_records_sql($sql, $params)) {
        $postids = array_keys($posts);
        return local_forum_tp_mark_posts_read($user, $postids);
    }

    return true;
}

/**
 * Marks a whole discussion as read, for a given user
 *
 * @global object
 * @global object
 * @param object $user
 * @param int $discussionid
 * @return bool
 */
function local_forum_tp_mark_discussion_read($user, $discussionid) {
    global $CFG, $DB;

    $cutoffdate = time() - ($CFG->local_forum_oldpostdays*24*60*60);

    $sql = "SELECT p.id
              FROM {local_forum_posts} p
                   LEFT JOIN {local_forum_read} r ON (r.postid = p.id AND r.userid = ?)
             WHERE p.discussion = ?
                   AND p.modified >= ? AND r.id is NULL";

    if ($posts = $DB->get_records_sql($sql, array($user->id, $discussionid, $cutoffdate))) {
        $postids = array_keys($posts);
        return local_forum_tp_mark_posts_read($user, $postids);
    }

    return true;
}

/**
 * @global object
 * @param int $userid
 * @param object $post
 */
function local_forum_tp_is_post_read($userid, $post) {
    global $DB;
    return (local_forum_tp_is_post_old($post) ||
            $DB->record_exists('local_forum_read', array('userid' => $userid, 'postid' => $post->id)));
}

/**
 * @global object
 * @param object $post
 * @param int $time Defautls to time()
 */
function local_forum_tp_is_post_old($post, $time=null) {
    global $CFG;

    if (is_null($time)) {
        $time = time();
    }
    return ($post->modified < ($time - ($CFG->local_forum_oldpostdays * 24 * 3600)));
}

/**
 * Returns the count of records for the provided user and course.
 * Please note that group access is ignored!
 *
 * @global object
 * @global object
 * @param int $userid
 * @param int $courseid
 * @return array
 */
function local_forum_tp_get_course_unread_posts($userid, $courseid) {
    global $CFG, $DB;

    $now = round(time(), -2); // DB cache friendliness.
    $cutoffdate = $now - ($CFG->local_forum_oldpostdays * 24 * 60 * 60);
    $params = array($userid, $userid, $courseid, $cutoffdate, $userid);

    if (!empty($CFG->local_forum_enabletimedposts)) {
        $timedsql = "AND d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?)";
        $params[] = $now;
        $params[] = $now;
    } else {
        $timedsql = "";
    }

    if ($CFG->local_forum_allowforcedreadtracking) {
        $trackingsql = "AND (f.trackingtype = ".LOCAL_FORUM_TRACKING_FORCED."
                            OR (f.trackingtype = ".LOCAL_FORUM_TRACKING_OPTIONAL." AND tf.id IS NULL
                                AND (SELECT trackforums FROM {user} WHERE id = ?) = 1))";
    } else {
        $trackingsql = "AND ((f.trackingtype = ".LOCAL_FORUM_TRACKING_OPTIONAL." OR f.trackingtype = ".LOCAL_FORUM_TRACKING_FORCED.")
                            AND tf.id IS NULL
                            AND (SELECT trackforums FROM {user} WHERE id = ?) = 1)";
    }

    $sql = "SELECT f.id, COUNT(p.id) AS unread
              FROM {local_forum_posts} p
                   JOIN {local_forum_discussions} d       ON d.id = p.discussion
                   JOIN {local_forum} f                   ON f.id = d.forum
                   JOIN {course} c                  ON c.id = f.course
                   LEFT JOIN {local_forum_read} r         ON (r.postid = p.id AND r.userid = ?)
                   LEFT JOIN {local_forum_track_prefs} tf ON (tf.userid = ? AND tf.forumid = f.id)
             WHERE f.course = ?
                   AND p.modified >= ? AND r.id is NULL
                   $trackingsql
                   $timedsql
          GROUP BY f.id";

    if ($return = $DB->get_records_sql($sql, $params)) {
        return $return;
    }

    return array();
}

/**
 * Returns the count of records for the provided user and local_forum and [optionally] group.
 * @param int $forumid
 * @return int
 */
function local_forum_tp_count_forum_unread_posts($forumid) {
    global $CFG, $USER, $DB;

    $localforumid = $forumid;

    $now = round(time(), -2); // db cache friendliness
    $cutoffdate = $now - ($CFG->local_forum_oldpostdays*24*60*60);
    $params = array($USER->id, $localforumid, $cutoffdate);

    if (!empty($CFG->local_forum_enabletimedposts)) {
        $timedsql = "AND d.timestart < ? AND (d.timeend = 0 OR d.timeend > ?)";
        $params[] = $now;
        $params[] = $now;
    } else {
        $timedsql = "";
    }

    $sql = "SELECT COUNT(p.id)
              FROM {local_forum_posts} p
                   JOIN {local_forum_discussions} d ON p.discussion = d.id
                   LEFT JOIN {local_forum_read} r   ON (r.postid = p.id AND r.userid = ?)
             WHERE d.forum = ?
                   AND p.modified >= ? AND r.id is NULL
                   $timedsql ";

    return $DB->get_field_sql($sql, $params);
}

/**
 * Deletes read records for the specified index. At least one parameter must be specified.
 *
 * @global object
 * @param int $userid
 * @param int $postid
 * @param int $discussionid
 * @param int $localforumid
 * @return bool
 */
function local_forum_tp_delete_read_records($userid=-1, $postid=-1, $discussionid=-1, $localforumid=-1) {
    global $DB;
    $params = array();

    $select = '';
    if ($userid > -1) {
        if ($select != '') $select .= ' AND ';
        $select .= 'userid = ?';
        $params[] = $userid;
    }
    if ($postid > -1) {
        if ($select != '') $select .= ' AND ';
        $select .= 'postid = ?';
        $params[] = $postid;
    }
    if ($discussionid > -1) {
        if ($select != '') $select .= ' AND ';
        $select .= 'discussionid = ?';
        $params[] = $discussionid;
    }
    if ($localforumid > -1) {
        if ($select != '') $select .= ' AND ';
        $select .= 'forumid = ?';
        $params[] = $localforumid;
    }
    if ($select == '') {
        return false;
    }
    else {
        return $DB->delete_records_select('local_forum_read', $select, $params);
    }
}
/**
 * Get a list of local_forums not tracked by the user.
 *
 * @global object
 * @global object
 * @param int $userid The id of the user to use.
 * @param int $courseid The id of the course being checked.
 * @return mixed An array indexed by local_forum id, or false.
 */
function local_forum_tp_get_untracked_forums($userid, $courseid) {
    global $CFG, $DB;

    if ($CFG->local_forum_allowforcedreadtracking) {
        $trackingsql = "AND (f.trackingtype = ".LOCAL_FORUM_TRACKING_OFF."
                            OR (f.trackingtype = ".LOCAL_FORUM_TRACKING_OPTIONAL." AND (ft.id IS NOT NULL
                                OR (SELECT track_forums FROM {user} WHERE id = ?) = 0)))";
    } else {
        $trackingsql = "AND (f.trackingtype = ".LOCAL_FORUM_TRACKING_OFF."
                            OR ((f.trackingtype = ".LOCAL_FORUM_TRACKING_OPTIONAL." OR f.trackingtype = ".LOCAL_FORUM_TRACKING_FORCED.")
                                AND (ft.id IS NOT NULL
                                    OR (SELECT track_forums FROM {user} WHERE id = ?) = 0)))";
    }

    $sql = "SELECT f.id
              FROM {local_forum} f
                   LEFT JOIN {local_forum_track_prefs} ft ON (ft.forumid = f.id AND ft.userid = ?)
             WHERE f.course = ?
                   $trackingsql";

    if ($localforums = $DB->get_records_sql($sql, array($userid, $courseid, $userid))) {
        foreach ($localforums as $localforum) {
            $localforums[$localforum->id] = $localforum;
        }
        return $localforums;

    } else {
        return array();
    }
}

/**
 * Determine if a user can track local_forums and optionally a particular local_forum.
 * Checks the site settings, the user settings and the local_forum settings (if
 * requested).
 *
 * @global object
 * @global object
 * @global object
 * @param mixed $localforum The local_forum object to test, or the int id (optional).
 * @param mixed $userid The user object to check for (optional).
 * @return boolean
 */
function local_forum_tp_can_track_forums($localforum=false, $user=false) {
    global $USER, $CFG, $DB;

    // if possible, avoid expensive
    // queries
    if (empty($CFG->local_forum_trackreadposts)) {
        return false;
    }

    if ($user === false) {
        $user = $USER;
    }

    if (isguestuser($user) or empty($user->id)) {
        return false;
    }

    if ($localforum === false) {
        if ($CFG->local_forum_allowforcedreadtracking) {
            // Since we can force tracking, assume yes without a specific local_forum.
            return true;
        } else {
            return (bool)$user->track_forums;
        }
    }

    // Work toward always passing an object...
    if (is_numeric($localforum)) {
        debugging('Better use proper local_forum object.', DEBUG_DEVELOPER);
        $localforum = $DB->get_record('local_forum', array('id' => $localforum), '', 'id,trackingtype');
    }

    $localforumallows = ($localforum->trackingtype == LOCAL_FORUM_TRACKING_OPTIONAL);
    $localforumforced = ($localforum->trackingtype == LOCAL_FORUM_TRACKING_FORCED);

    if ($CFG->local_forum_allowforcedreadtracking) {
        // If we allow forcing, then forced local_forums takes procidence over user setting.
        return ($localforumforced || ($localforumallows  && (!empty($user->track_forums) && (bool)$user->track_forums)));
    } else {
        // If we don't allow forcing, user setting trumps.
        return ($localforumforced || $localforumallows)  && !empty($user->track_forums);
    }
}

/**
 * Tells whether a specific local_forum is tracked by the user. A user can optionally
 * be specified. If not specified, the current user is assumed.
 *
 * @global object
 * @global object
 * @global object
 * @param mixed $localforum If int, the id of the local_forum being checked; if object, the local_forum object
 * @param int $userid The id of the user being checked (optional).
 * @return boolean
 */
function local_forum_tp_is_tracked($localforum, $user=false) {
    global $USER, $CFG, $DB;

    if ($user === false) {
        $user = $USER;
    }

    if (isguestuser($user) or empty($user->id)) {
        return false;
    }

    // Work toward always passing an object...
    if (is_numeric($localforum)) {
        debugging('Better use proper local_forum object.', DEBUG_DEVELOPER);
        $localforum = $DB->get_record('local_forum', array('id' => $localforum));
    }

    if (!local_forum_tp_can_track_forums($localforum, $user)) {
        return false;
    }

    $localforumallows = ($localforum->trackingtype == LOCAL_FORUM_TRACKING_OPTIONAL);
    $localforumforced = ($localforum->trackingtype == LOCAL_FORUM_TRACKING_FORCED);
    $userpref = $DB->get_record('local_forum_track_prefs', array('userid' => $user->id, 'forumid' => $localforum->id));

    if ($CFG->local_forum_allowforcedreadtracking) {
        return $localforumforced || ($localforumallows && $userpref === false);
    } else {
        return  ($localforumallows || $localforumforced) && $userpref === false;
    }
}

/**
 * @global object
 * @global object
 * @param int $localforumid
 * @param int $userid
 */
function local_forum_tp_start_tracking($localforumid, $userid=false) {
    global $USER, $DB;

    if ($userid === false) {
        $userid = $USER->id;
    }

    return $DB->delete_records('local_forum_track_prefs', array('userid' => $userid, 'forumid' => $localforumid));
}

/**
 * @global object
 * @global object
 * @param int $localforumid
 * @param int $userid
 */
function local_forum_tp_stop_tracking($localforumid, $userid=false) {
    global $USER, $DB;

    if ($userid === false) {
        $userid = $USER->id;
    }

    if (!$DB->record_exists('local_forum_track_prefs', array('userid' => $userid, 'forumid' => $localforumid))) {
        $track_prefs = new stdClass();
        $track_prefs->userid = $userid;
        $track_prefs->forumid = $localforumid;
        $DB->insert_record('local_forum_track_prefs', $track_prefs);
    }

    return local_forum_tp_delete_read_records($userid, -1, -1, $localforumid);
}


/**
 * Clean old records from the local_forum_read table.
 * @global object
 * @global object
 * @return void
 */
function local_forum_tp_clean_read_records() {
    global $CFG, $DB;

    if (!isset($CFG->local_forum_oldpostdays)) {
        return;
    }
// Look for records older than the cutoffdate that are still in the local_forum_read table.
    $cutoffdate = time() - ($CFG->local_forum_oldpostdays*24*60*60);

    //first get the oldest tracking present - we need tis to speedup the next delete query
    $sql = "SELECT MIN(fp.modified) AS first
              FROM {local_forum_posts} fp
                   JOIN {local_forum_read} fr ON fr.postid=fp.id";
    if (!$first = $DB->get_field_sql($sql)) {
        // nothing to delete;
        return;
    }

    // now delete old tracking info
    $sql = "DELETE
              FROM {local_forum_read}
             WHERE postid IN (SELECT fp.id
                                FROM {local_forum_posts} fp
                               WHERE fp.modified >= ? AND fp.modified < ?)";
    $DB->execute($sql, array($first, $cutoffdate));
}

/**
 * Sets the last post for a given discussion
 *
 * @global object
 * @global object
 * @param into $discussionid
 * @return bool|int
 **/
function local_forum_discussion_update_last_post($discussionid) {
    global $CFG, $DB;

// Check the given discussion exists
    if (!$DB->record_exists('local_forum_discussions', array('id' => $discussionid))) {
        return false;
    }

// Use SQL to find the last post for this discussion
    $sql = "SELECT id, userid, modified
              FROM {local_forum_posts}
             WHERE discussion=?
             ORDER BY modified DESC";

// Lets go find the last post
    if (($lastposts = $DB->get_records_sql($sql, array($discussionid), 0, 1))) {
        $lastpost = reset($lastposts);
        $discussionobject = new stdClass();
        $discussionobject->id           = $discussionid;
        $discussionobject->usermodified = $lastpost->userid;
        $discussionobject->timemodified = $lastpost->modified;
        $DB->update_record('local_forum_discussions', $discussionobject);
        return $lastpost->id;
    }

// To get here either we couldn't find a post for the discussion (weird)
// or we couldn't update the discussion record (weird x2)
    return false;
}


/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function local_forum_get_view_actions() {
    return array('view discussion', 'search', 'local_forum', 'local_forums', 'subscribers', 'view local_forum');
}

/**
 * List the options for local_forum subscription modes.
 * This is used by the settings page and by the mod_form page.
 *
 * @return array
 */
function local_forum_get_subscriptionmode_options() {
    $options = array();
    $options[LOCAL_FORUM_CHOOSESUBSCRIBE] = get_string('subscriptionoptional', 'local_forum');
    $options[LOCAL_FORUM_FORCESUBSCRIBE] = get_string('subscriptionforced', 'local_forum');
    $options[LOCAL_FORUM_INITIALSUBSCRIBE] = get_string('subscriptionauto', 'local_forum');
    $options[LOCAL_FORUM_DISALLOWSUBSCRIBE] = get_string('subscriptiondisabled', 'local_forum');
    return $options;
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function local_forum_get_post_actions() {
    return array('add discussion','add post','delete discussion','delete post','move discussion','prune post','update post');
}

/**
 * Returns a warning object if a user has reached the number of posts equal to
 * the warning/blocking setting, or false if there is no warning to show.
 *
 * @param int|stdClass $localforum the local_forum id or the local_forum object
 * @return stdClass|bool returns an object with the warning information, else
 *         returns false if no warning is required.
 */
function local_forum_check_throttling($localforum) {
    global $CFG, $DB, $USER;

    if (is_numeric($localforum)) {
        $localforum = $DB->get_record('local_forum', array('id' => $localforum), '*', MUST_EXIST);
    }

    if (!is_object($localforum)) {
        return false; // This is broken.
    }

    if (empty($localforum->blockafter)) {
        return false;
    }

    if (empty($localforum->blockperiod)) {
        return false;
    }

    $context = context_system::instance();
    if (has_capability('local/forum:postwithoutthrottling', $context)) {
        return false;
    }

    // Get the number of posts in the last period we care about.
    $timenow = time();
    $timeafter = $timenow - $localforum->blockperiod;
    $numposts = $DB->count_records_sql('SELECT COUNT(p.id) FROM {local_forum_posts} p
                                        JOIN {local_forum_discussions} d
                                        ON p.discussion = d.id WHERE d.forum = ?
                                        AND p.userid = ? AND p.created > ?', array($localforum->id, $USER->id, $timeafter));

    $a = new stdClass();
    $a->blockafter = $localforum->blockafter;
    $a->numposts = $numposts;
    $a->blockperiod = get_string('secondstotime'.$localforum->blockperiod);

    if ($localforum->blockafter <= $numposts) {
        $warning = new stdClass();
        $warning->canpost = false;
        $warning->errorcode = 'local_forumblockingtoomanyposts';
        $warning->module = 'error';
        $warning->additional = $a;
        $warning->link = $CFG->wwwroot . '/local/forum/view.php?f=' . $localforum->id;

        return $warning;
    }

    if ($localforum->warnafter <= $numposts) {
        $warning = new stdClass();
        $warning->canpost = true;
        $warning->errorcode = 'local_forumblockingalmosttoomanyposts';
        $warning->module = 'local_forum';
        $warning->additional = $a;
        $warning->link = null;

        return $warning;
    }
}

/**
 * Throws an error if the user is no longer allowed to post due to having reached
 * or exceeded the number of posts specified in 'Post threshold for blocking'
 * setting.
 *
 * @since Moodle 2.5
 * @param stdClass $thresholdwarning the warning information returned
 *        from the function local_forum_check_throttling.
 */
function local_forum_check_blocking_threshold($thresholdwarning) {
    if (!empty($thresholdwarning) && !$thresholdwarning->canpost) {
        print_error($thresholdwarning->errorcode,
                    $thresholdwarning->module,
                    $thresholdwarning->link,
                    $thresholdwarning->additional);
    }
}

/**
 * Returns array of local_forum layout modes
 *
 * @return array
 */
function local_forum_get_layout_modes() {
    return array (LOCAL_FORUM_MODE_FLATOLDEST => get_string('modeflatoldestfirst', 'local_forum'),
                  LOCAL_FORUM_MODE_FLATNEWEST => get_string('modeflatnewestfirst', 'local_forum'),
                  LOCAL_FORUM_MODE_THREADED   => get_string('modethreaded', 'local_forum'),
                  LOCAL_FORUM_MODE_NESTED     => get_string('modenested', 'local_forum'));
}

/**
 * Returns array of local_forum types chooseable on the local_forum editing form
 *
 * @return array
 */
function local_forum_get_forum_types() {
    return array ('general'  => get_string('generalforum', 'local_forum'),
                  'eachuser' => get_string('eachuserforum', 'local_forum'),
                  'single'   => get_string('singleforum', 'local_forum'),
                  'qanda'    => get_string('qandaforum', 'local_forum'),
                  'blog'     => get_string('blogforum', 'local_forum'));
}

/**
 * Returns array of all local_forum layout modes
 *
 * @return array
 */
function local_forum_get_forum_types_all() {
    return array ('news'     => get_string('namenews','local_forum'),
                  'social'   => get_string('namesocial','local_forum'),
                  'general'  => get_string('generalforum', 'local_forum'),
                  'eachuser' => get_string('eachuserforum', 'local_forum'),
                  'single'   => get_string('singleforum', 'local_forum'),
                  'qanda'    => get_string('qandaforum', 'local_forum'),
                  'blog'     => get_string('blogforum', 'local_forum'));
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function local_forum_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames', 'moodle/site:trustcontent', 'moodle/rating:view', 'moodle/rating:viewany', 'moodle/rating:viewall', 'moodle/rating:rate');
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function local_forum_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $localforum_pagetype = array(
        'mod-local_forum-*'=>get_string('page-mod-local_forum-x', 'local_forum'),
        'mod-local_forum-view'=>get_string('page-mod-local_forum-view', 'local_forum'),
        'mod-local_forum-discuss'=>get_string('page-mod-local_forum-discuss', 'local_forum')
    );
    return $localforum_pagetype;
}


/**
 * Set the per-local_forum maildigest option for the specified user.
 *
 * @param stdClass $localforum The local_forum to set the option for.
 * @param int $maildigest The maildigest option.
 * @param stdClass $user The user object. This defaults to the global $USER object.
 * @throws invalid_digest_setting thrown if an invalid maildigest option is provided.
 */
function local_forum_set_user_maildigest($localforum, $maildigest, $user = null) {
    global $DB, $USER;

    if (is_number($localforum)) {
        $localforum = $DB->get_record('local_forum', array('id' => $localforum));
    }

    if ($user === null) {
        $user = $USER;
    }
    
    $context = context_system::instance();

    // User must be allowed to see this local_forum.
    require_capability('local/forum:viewdiscussion', $context, $user->id);

    // Validate the maildigest setting.
    $digestoptions = local_forum_get_user_digest_options($user);

    if (!isset($digestoptions[$maildigest])) {
        throw new moodle_exception('invaliddigestsetting', 'local_forum');
    }

    // Attempt to retrieve any existing local_forum digest record.
    $subscription = $DB->get_record('local_forum_digests', array(
        'userid' => $user->id,
        'forum' => $localforum->id,
    ));

    // Create or Update the existing maildigest setting.
    if ($subscription) {
        if ($maildigest == -1) {
            $DB->delete_records('local_forum_digests', array('forum' => $localforum->id, 'userid' => $user->id));
        } else if ($maildigest !== $subscription->maildigest) {
            // Only update the maildigest setting if it's changed.

            $subscription->maildigest = $maildigest;
            $DB->update_record('local_forum_digests', $subscription);
        }
    } else {
        if ($maildigest != -1) {
            // Only insert the maildigest setting if it's non-default.

            $subscription = new stdClass();
            $subscription->forum = $localforum->id;
            $subscription->userid = $user->id;
            $subscription->maildigest = $maildigest;
            $subscription->id = $DB->insert_record('local_forum_digests', $subscription);
        }
    }
}

/**
 * Determine the maildigest setting for the specified user against the
 * specified local_forum.
 *
 * @param Array $digests An array of local_forums and user digest settings.
 * @param stdClass $user The user object containing the id and maildigest default.
 * @param int $localforumid The ID of the local_forum to check.
 * @return int The calculated maildigest setting for this user and local_forum.
 */
function local_forum_get_user_maildigest_bulk($digests, $user, $localforumid) {
    if (isset($digests[$localforumid]) && isset($digests[$localforumid][$user->id])) {
        $maildigest = $digests[$localforumid][$user->id];
        if ($maildigest === -1) {
            $maildigest = $user->maildigest;
        }
    } else {
        $maildigest = $user->maildigest;
    }
    return $maildigest;
}

/**
 * Retrieve the list of available user digest options.
 *
 * @param stdClass $user The user object. This defaults to the global $USER object.
 * @return array The mapping of values to digest options.
 */
function local_forum_get_user_digest_options($user = null) {
    global $USER;

    // Revert to the global user object.
    if ($user === null) {
        $user = $USER;
    }

    $digestoptions = array();
    $digestoptions['0']  = get_string('emaildigestoffshort', 'local_forum');
    $digestoptions['1']  = get_string('emaildigestcompleteshort', 'local_forum');
    $digestoptions['2']  = get_string('emaildigestsubjectsshort', 'local_forum');

    // We need to add the default digest option at the end - it relies on
    // the contents of the existing values.
    $digestoptions['-1'] = get_string('emaildigestdefault', 'local_forum',
            $digestoptions[$user->maildigest]);

    // Resort the options to be in a sensible order.
    ksort($digestoptions);

    return $digestoptions;
}

/**
 * Determine the current context if one was not already specified.
 *
 *
 * @param int $localforumid The ID of the local_forum
 * @param context_module $context The current context.
 * @return context_module The context determined
 */
function local_forum_get_context($localforumid, $context = null) {
    global $PAGE;

    if (!$context || !($context instanceof context_module)) {
        $context = \context_system::instance();
    }

    return $context;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $localforum   local_forum object
 * @param  stdClass $context context object
 * @since Moodle 2.9
 */
function local_forum_view($localforum, $context) {

    // Trigger course_module_viewed event.

    $params = array(
        'context' => $context,
        'objectid' => $localforum->id
    );

    $event = \local_forum\event\course_module_viewed::create($params);
    $event->add_record_snapshot('local_forum', $localforum);
    $event->trigger();
}

/**
 * Trigger the discussion viewed event
 *
 * @param  stdClass $modcontext module context object
 * @param  stdClass $localforum      local_forum object
 * @param  stdClass $discussion discussion object
 * @since Moodle 2.9
 */
function local_forum_discussion_view($context, $localforum, $discussion) {
    $params = array(
        'context' => $context,
        'objectid' => $discussion->id,
    );

    $event = \local_forum\event\discussion_viewed::create($params);
    $event->add_record_snapshot('local_forum_discussions', $discussion);
    $event->add_record_snapshot('local_forum', $localforum);
    $event->trigger();
}

/**
 * Set the discussion to pinned and trigger the discussion pinned event
 *
 * @param  stdClass $modcontext module context object
 * @param  stdClass $localforum      local_forum object
 * @param  stdClass $discussion discussion object
 * @since Moodle 3.1
 */
function local_forum_discussion_pin($modcontext, $localforum, $discussion) {
    global $DB;

    $DB->set_field('local_forum_discussions', 'pinned', LOCAL_FORUM_DISCUSSION_PINNED, array('id' => $discussion->id));

    $params = array(
        'context' => $modcontext,
        'objectid' => $discussion->id,
        'other' => array('forumid' => $localforum->id)
    );

    $event = \local_forum\event\discussion_pinned::create($params);
    $event->add_record_snapshot('local_forum_discussions', $discussion);
    $event->trigger();
}

/**
 * Set discussion to unpinned and trigger the discussion unpin event
 *
 * @param  stdClass $modcontext module context object
 * @param  stdClass $localforum      local_forum object
 * @param  stdClass $discussion discussion object
 * @since Moodle 3.1
 */
function local_forum_discussion_unpin($modcontext, $localforum, $discussion) {
    global $DB;

    $DB->set_field('local_forum_discussions', 'pinned', LOCAL_FORUM_DISCUSSION_UNPINNED, array('id' => $discussion->id));

    $params = array(
        'context' => $modcontext,
        'objectid' => $discussion->id,
        'other' => array('forumid' => $localforum->id)
    );
    
    $event = \local_forum\event\discussion_unpinned::create($params);
    $event->add_record_snapshot('local_forum_discussions', $discussion);
    $event->trigger();
}

/**
 * Checks whether the author's name and picture for a given post should be hidden or not.
 *
 * @param object $post The local_forum post.
 * @param object $localforum The local_forum object.
 * @return bool
 * @throws coding_exception
 */
function local_forum_is_author_hidden($post, $localforum) {
    if (!isset($post->parent)) {
        throw new coding_exception('$post->parent must be set.');
    }
    if (!isset($localforum->type)) {
        throw new coding_exception('$localforum->type must be set.');
    }
    if ($localforum->type === 'single' && empty($post->parent)) {
        return true;
    }
    return false;
}

/**
 * Manage inplace editable saves.
 *
 * @param   string      $itemtype       The type of item.
 * @param   int         $itemid         The ID of the item.
 * @param   mixed       $newvalue       The new value
 * @return  string
 */
function local_forum_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $PAGE;

    if ($itemtype === 'digestoptions') {
        // The itemid is the local_forumid.
        $localforum   = $DB->get_record('local_forum', array('id' => $itemid), '*', MUST_EXIST);
        $context = context_system::instance();

        $PAGE->set_context($context);
        require_login();
        local_forum_set_user_maildigest($localforum, $newvalue);

        $renderer = $PAGE->get_renderer('local_forum');
        return $renderer->render_digest_options($localforum, $newvalue);
    }
}

/**
 * Determine whether the specified discussion is time-locked.
 *
 * @param   stdClass    $localforum          The local_forum that the discussion belongs to
 * @param   stdClass    $discussion     The discussion to test
 * @return  bool
 */
function local_forum_discussion_is_locked($localforum, $discussion) {
    if (empty($localforum->lockdiscussionafter)) {
        return false;
    }

    if ($localforum->type === 'single') {
        // It does not make sense to lock a single discussion local_forum.
        return false;
    }

    if (($discussion->timemodified + $localforum->lockdiscussionafter) < time()) {
        return true;
    }

    return false;
}

/**
 * Check if the user can create attachments in a local_forum.
 * @param  stdClass $localforum   local_forum object
 * @param  stdClass $context context object
 * @return bool true if the user can create attachments, false otherwise
 * @since  Moodle 3.3
 */
function local_forum_can_create_attachment($localforum, $context) {
    // If maxbytes == 1 it means no attachments at all.
    if (empty($localforum->maxattachments) || $localforum->maxbytes == 1 ||
            !has_capability('local/forum:createattachment', $context)) {
        return false;
    }
    return true;
}

/**
 * Get icon mapping for font-awesome.
 *
 * @return  array
 */
function local_forum_get_fontawesome_icon_map() {
    return [
        'local_forum:i/pinned' => 'fa-map-pin',
        'local_forum:t/selected' => 'fa-check',
        'local_forum:t/subscribed' => 'fa-envelope-o',
        'local_forum:t/unsubscribed' => 'fa-envelope-open-o',
    ];
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_forum_leftmenunode(){
    $systemcontext = context_system::instance();
    $forumnode = '';
    if(has_capability('local/forum:viewdiscussion',$systemcontext) || is_siteadmin()){
        $forumnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_browseforums', 'class'=>'pull-left user_nav_div browseforums'));
            $forum_url = new moodle_url('/local/forum/index.php');
            if(has_capability('local/forum:editanypost',$systemcontext)){
                $forum_label = get_string('manageforum','local_forum');
            }else{
                $forum_label = get_string('discussion_forum','local_forum');
            }
            $forum = html_writer::link($forum_url, '<span class="manage_forums_icon left_menu_icons"></span><span class="user_navigation_link_text">'.$forum_label.'</span>',array('class'=>'user_navigation_link'));
            $forumnode .= $forum;
        $forumnode .= html_writer::end_tag('li');
    }

    return array('14' => $forumnode);
}
function local_forum_quicklink_node(){
    global $CFG, $PAGE, $OUTPUT;
    $systemcontext = context_system::instance();
    if (is_siteadmin() || has_capability('local/forum:viewdiscussion',$systemcontext)){
        //local forums content
        $PAGE->requires->js_call_amd('local_forum/newforum', 'load', array());
        // $local_forums_content = $PAGE->requires->js_call_amd('local_forum/newforum', 'load', array());
        // $local_forums_content .= '<span class="anch_span"><i class="fa fa-comments-o" aria-hidden="true"></i></span>';
        // $local_forums_content .= '<div class="quick_navigation_detail">
        //                                 <div class="span_str">'.get_string("manage_br_forum", "local_forum").'</div>';
        //     $display_line = false;
        //     if(is_siteadmin() || has_capability('local/forum:addinstance', $systemcontext)) {
        //         $local_forums_content .= '<span class="span_createlink">
        //                                     <a href="javascript:void(0);" class="quick_nav_link goto_local_forum" data-action="createforummodal" onclick ="(function(e){ require(\'local_forum/newforum\').init({selector:\'createforummodal\', context:'.$systemcontext->id.', form_status:0}) })(event)" title="'.get_string('forum:addinstance', 'local_forum').'">'.get_string("create").'</a>';
        //         $display_line = true;
        //     }
            
        //     if($display_line) {
        //         $local_forums_content .= " | ";
        //     }
                            
        //     $local_forums_content .= '<a href="'.$CFG->wwwroot.'/local/forum/index.php" class="viewlink" title= "'.get_string("view_forum", "local_forum").'">'.get_string('view').'</a>
        //                             </span>';
        // $local_forums_content .= '</div>';
        // $local_forums = '<div class="quick_nav_list manage_forums one_of_three_columns" >'.$local_forums_content.'</div>';

        $forums = array();
        $forums['node_header_string'] = get_string("manage_br_forum", "local_forum");
        $forums['pluginname'] = 'forums';
        $forums['plugin_icon_class'] = 'fa fa-comments-o';
        if(is_siteadmin() || has_capability('local/forum:addinstance', $systemcontext)){
            $forums['create'] = TRUE;
            $forums['create_element'] = html_writer::link('javascript:void(0)', get_string('create'), array('class' => "quick_nav_link goto_local_forum", 'data-action' => "createforummodal", 'onclick' => "(function(e){ require('local_forum/newforum').init({selector:'createforummodal', context:".$systemcontext->id.", form_status:0}) })(event)", 'title' => get_string('forum:addinstance', 'local_forum')));
        }
        // if(has_capability('local/courses:view', $systemcontext) || has_capability('local/courses:manage', $systemcontext)){
        $forums['viewlink_url'] = $CFG->wwwroot.'/local/forum/index.php';
        $forums['view'] = TRUE;
        $forums['viewlink_title'] = get_string("view_forum", "local_forum");
        // }
        $forums['space_count'] = 'one';
        $content = $OUTPUT->render_from_template('block_quick_navigation/quicklink_node', $forums);
    }

    
    return array('6' => $content);
}

/**
 * Returns forums tagged with a specified tag.
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
/* function local_forum_get_tagged_forums($tag, $exclusivemode = false, $fromctx = 0, $ctx = 0, $rec = 1, $page = 0, $sort = '') {
    global $CFG, $PAGE;
    // prepare for display of tags related to forums
    $perpage = $exclusivemode ? 10 : 5;
    $displayoptions = array(
        'limit' => $perpage,
        'offset' => $page * $perpage,
        'viewmoreurl' => null,
    );
    $renderer = $PAGE->get_renderer('local_forum');
    $totalcount = $renderer->tagged_forums($tag->id, $exclusivemode, $ctx, $rec, $displayoptions, $count = 1, $sort);
    $content = $renderer->tagged_forums($tag->id, $exclusivemode, $ctx, $rec, $displayoptions,0, $sort);
    $totalpages = ceil($totalcount / $perpage);
    if ($totalcount)
    return new local_tags\output\tagindex($tag, 'local_forum', 'forum', $content,
            $exclusivemode, $fromctx, $ctx, $rec, $page, $totalpages);
    else
    return '';
} */

function get_forum_details($testid) {
    global $USER, $DB;
    $context = context_system::instance();
    $details = array();
    $joinsql = '';
    $selectsql = "select c.*  ";
    $fromsql = " from  {local_forum} c ";
    if ($DB->get_manager()->table_exists('local_rating')) {
        $selectsql .= " , AVG(rating) as avg ";
        $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = c.id AND r.ratearea = 'local_forum' ";
    }
    $wheresql = " where c.id = ? ";
    $adminrecord = $DB->get_record_sql($selectsql.$fromsql.$joinsql.$wheresql, [$testid]);
    $details['manage'] = 1;
    $details['postedby'] = $DB->get_field_sql("select concat(firstname,' ',lastname) from {user} where id=?", array($adminrecord->usermodified));
    $details['topics'] = local_forum_count_discussions($adminrecord);    
    return $details;
}


/* @function display_like_unlike
 *  @function returns option to like or unlike.
 */
 function local_forum_like_unlike($itemid,$discussionid,$parentid,$postid,$likearea){
    global $DB, $CFG, $USER, $PAGE, $OUTPUT;
   
  /*   $ask_rating_data = ask_for_rating($itemid, $likearea, $heading=NULL, 0);
    if(!$ask_rating_data['enroll']){
        $params = array('class' => 'like_unlike disable_pointer', 'style'=>'pointer-events:none');
    }else{
        $params = array('class' => 'like_unlike');
    } */

    $PAGE->requires->js_call_amd('local_forum/ratings', 'load');

    $params = array('class' => 'like_unlike');
    $output = html_writer::start_tag('div', $params);
    $forumid = $itemid;
    
    $output .= html_writer::start_tag('div', array('id'=>'contents_'.$itemid, 'style'=>'float: left; clear:both;font-size:16px;'));
    $likestyle = "";
    $unlikestyle = "";
    $mylike_unlike = $DB->get_record('local_forum_like',array('userid' => $USER->id, 'forumid'=>$forumid, 'discussionid'=>$discussionid, 'parentid'=>$parentid, 'postid'=>$postid, 'likearea' => $likearea));

    if($mylike_unlike){
        if($mylike_unlike->likestatus==1){
            $likestyle = "style='color:#0769ad'";
        }
        else if($mylike_unlike->likestatus==2){
            $unlikestyle = "style='color:#0769ad'";
        }
    }
    //Like button----------
    
    $likeicon = "<i class='fa fa-thumbs-up' $likestyle class='thumb_like_'".$postid."'>  </i>";
    $likeEnable = html_writer::start_span('thubmbsup',array('title'=>'Like', 'title'=>'Like', 'onclick'=>"(function(e){ require('local_forum/ratings').updatevalues({ action: 0 , forumid: ".$forumid.", discussionid: ".$discussionid.", parentid: ".$parentid.", postid : ".$postid.", likearea : '".$likearea."'}) })(event)"));
    $likeEnable .= $likeicon;
    $likeEnable .= html_writer::end_span();

    $likeparams = array('id'=>'label_like_'.$itemid, 'style'=>'float: left; padding: 0 4px 0 0;cursor:pointer;');

	$output .= html_writer::tag('div', $likeEnable, $likeparams);
    //Like count-------------------
    $likecount = $DB->count_records('local_forum_like', array('likearea'=>$likearea, 'forumid'=>$forumid, 'discussionid'=>$discussionid, 'parentid'=>$parentid, 'postid'=>$postid, 'likestatus'=>1));
    $output .= '<span style="float: left;" class="count_likearea_'.$itemid.' count_like_'.$postid.'">'.$likecount.'</span>';
    
    
    
    //Unlike button----------
    $unlikeicon = "<i class='fa fa-thumbs-down' $unlikestyle class='thumb_dislike_'".$postid."'> </i>";
    $unlikeEnable = html_writer::div($unlikeicon,'thubmbsdown', array('src'=>$CFG->wwwroot.'/local/ratings/pix/unlikeN.png', 'title'=>'Dislike', 'style'=>'cursor: pointer;', 'onclick'=>"(function(e){ require('local_forum/ratings').updatevalues({ action: 1 , forumid: ".$forumid.", discussionid: ".$discussionid.", parentid: ".$parentid.",postid : ".$postid.",  likearea : '".$likearea."'}) })(event)"));
    $unlikeDisable = html_writer::empty_tag('img', array('src'=>$CFG->wwwroot.'/local/ratings/pix/unlike_disableN.png', 'title'=>'You Disliked it'));
    $unlike = html_writer::empty_tag('img', array('src'=>$CFG->wwwroot.'/local/ratings/pix/unlike.png'));
    $unlikeparams = array('id'=>'label_unlike_'.$itemid, 'style'=>'float: left; padding: 0 4px 0 15px;cursor:pointer;');

	$output .= html_writer::tag('div', $unlikeEnable, $unlikeparams);
    
    //Dislike count---------------------
    $unlikecount = $DB->count_records('local_forum_like', array('likearea'=>$likearea, 'forumid'=>$forumid, 'discussionid'=>$discussionid, 'parentid'=>$parentid, 'postid'=>$postid, 'likestatus'=>2));
    $output .= '<span style="float: left;" class="count_unlikearea_'.$itemid.' count_like_'.$postid.'">'.$unlikecount.'</span>';

    $output .= html_writer::end_tag('div'); //End of #contents_$item 
    
    $output .= html_writer::end_tag('div'); //End of .like_unlike
    // $output .= display_comment($itemid, $likearea);
    return $output;
} 
