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



require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/completionlib.php');
global $USER;
$reply   = optional_param('reply', 0, PARAM_INT);
$forum   = optional_param('forum', 0, PARAM_INT);
$edit    = optional_param('edit', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$prune   = optional_param('prune', 0, PARAM_INT);
$name    = optional_param('name', '', PARAM_CLEAN);
$confirm = optional_param('confirm', 0, PARAM_INT);
$groupid = optional_param('groupid', null, PARAM_INT);

$PAGE->set_url('/local/forum/post.php', array(
        'reply' => $reply,
        'forum' => $forum,
        'edit'  => $edit,
        'delete'=> $delete,
        'prune' => $prune,
        'name'  => $name,
        'confirm'=>$confirm,
        'groupid'=>$groupid,
        ));
//these page_params will be passed as hidden variables later in the form.
$page_params = array('reply'=>$reply, 'forum'=>$forum, 'edit'=>$edit);

$context = context_system::instance();

if (!isloggedin() or isguestuser()) {

    if (!isloggedin() and !get_local_referer()) {
        // No referer+not logged in - probably coming in via email  See MDL-9052
        require_login();
    }

    if (!empty($forum)) {      // User is starting a new discussion in a forum
        if (! $forum = $DB->get_record('local_forum', array('id' => $forum))) {
            print_error('invalidforumid', 'local_forum');
        }
    } else if (!empty($reply)) {      // User is writing a new reply
        if (! $parent = local_forum_get_post_full($reply)) {
            print_error('invalidparentpostid', 'local_forum');
        }
        if (! $discussion = $DB->get_record('local_forum_discussions', array('id' => $parent->discussion))) {
            print_error('notpartofdiscussion', 'local_forum');
        }
        if (! $forum = $DB->get_record('local_forum', array('id' => $discussion->forum))) {
            print_error('invalidforumid');
        }
    }

    $PAGE->set_context($context);
    $PAGE->set_title($forum->name);
    $PAGE->set_heading($forum->name);
    $referer = get_local_referer(false);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('noguestpost', 'local_forum').'<br /><br />'.get_string('liketologin'), get_login_url(), $referer);
    echo $OUTPUT->footer();
    exit;
}

require_login(0, false);   // Script is useless unless they're logged in

if (!empty($forum)) {      // User is starting a new discussion in a forum
    if (! $forum = $DB->get_record("local_forum", array("id" => $forum))) {
        print_error('invalidforumid', 'local_forum');
    }

    if (! local_forum_user_can_post_discussion($forum, $context)) {
        print_error('nopostforum', 'local_forum');
    }

    $SESSION->fromurl = get_local_referer(false);

    // Load up the $post variable.

    $post = new stdClass();
    $post->course        = 0;
    $post->forum         = $forum->id;
    $post->discussion    = 0;           // ie discussion # not defined yet
    $post->parent        = 0;
    $post->subject       = '';
    $post->userid        = $USER->id;
    $post->message       = '';
    $post->messageformat = editors_get_preferred_format();
    $post->messagetrust  = 0;
    $post->groupid  = 0;

    // Unsetting this will allow the correct return URL to be calculated later.
    unset($SESSION->fromdiscussion);

} else if (!empty($reply)) {      // User is writing a new reply

    if (! $parent = local_forum_get_post_full($reply)) {
        print_error('invalidparentpostid', 'local_forum');
    }
    if (! $discussion = $DB->get_record("local_forum_discussions", array("id" => $parent->discussion))) {
        print_error('notpartofdiscussion', 'local_forum');
    }
    if (! $forum = $DB->get_record("local_forum", array("id" => $discussion->forum))) {
        print_error('invalidforumid', 'local_forum');
    }

    if (! local_forum_user_can_post($forum, $discussion, $USER, $context)) {
        print_error('nopostforum', 'local_forum');
    }
    // Load up the $post variable.

    $post = new stdClass();
    $post->course      = 0;
    $post->forum       = $forum->id;
    $post->discussion  = $parent->discussion;
    $post->parent      = $parent->id;
    $post->subject     = $parent->subject;
    $post->userid      = $USER->id;
    $post->message     = '';

    $post->groupid = 0;

    $strre = get_string('re', 'local_forum');
    if (!(substr($post->subject, 0, strlen($strre)) == $strre)) {
        $post->subject = $strre.' '.$post->subject;
    }

    // Unsetting this will allow the correct return URL to be calculated later.
    unset($SESSION->fromdiscussion);

} else if (!empty($edit)) {  // User is editing their own post

    if (! $post = local_forum_get_post_full($edit)) {
        print_error('invalidpostid', 'local_forum');
    }
    if ($post->parent) {
        if (! $parent = local_forum_get_post_full($post->parent)) {
            print_error('invalidparentpostid', 'local_forum');
        }
    }

    if (! $discussion = $DB->get_record("local_forum_discussions", array("id" => $post->discussion))) {
        print_error('notpartofdiscussion', 'local_forum');
    }
    if (! $forum = $DB->get_record("local_forum", array("id" => $discussion->forum))) {
        print_error('invalidforumid', 'local_forum');
    }

    if (!($forum->type == 'news' && !$post->parent && $discussion->timestart > time())) {
        if (((time() - $post->created) > $CFG->maxeditingtime) and
                    !has_capability('local/forum:editanypost', $context)) {
            print_error('maxtimehaspassed', 'local_forum', '', format_time($CFG->maxeditingtime));
        }
    }
    if (($post->userid <> $USER->id) and
                !has_capability('local/forum:editanypost', $context)) {
        print_error('cannoteditposts', 'local_forum');
    }


    // Load up the $post variable.
    $post->edit   = $edit;
    $post->course = 0;
    $post->forum  = $forum->id;
    $post->groupid = 0 ;

    $post = trusttext_pre_edit($post, 'message', $context);

    // Unsetting this will allow the correct return URL to be calculated later.
    unset($SESSION->fromdiscussion);

}else if (!empty($delete)) {  // User is deleting a post

    if (! $post = local_forum_get_post_full($delete)) {
        print_error('invalidpostid', 'forum');
    }
    if (! $discussion = $DB->get_record("local_forum_discussions", array("id" => $post->discussion))) {
        print_error('notpartofdiscussion', 'local_forum');
    }
    if (! $forum = $DB->get_record("local_forum", array("id" => $discussion->forum))) {
        print_error('invalidforumid', 'local_forum');
    }

    require_login();

    if ( !(($post->userid == $USER->id && has_capability('local/forum:deleteownpost', $context))
                || has_capability('local/forum:deleteanypost', $context)) ) {
        print_error('cannotdeletepost', 'local_forum');
    }


    $replycount = local_forum_count_replies($post);

    if (!empty($confirm) && confirm_sesskey()) {   // User has confirmed the delete
        //check user capability to delete post.
        $timepassed = time() - $post->created;
        if (($timepassed > $CFG->maxeditingtime) && !has_capability('local/forum:deleteanypost', $context)) {
            print_error("cannotdeletepost", "local_forum",
                        local_forum_go_back_to(new moodle_url("/local/forum/discuss.php", array('d' => $post->discussion))));
        }

        if ($post->totalscore) {
            notice(get_string('couldnotdeleteratings', 'rating'),
                   local_forum_go_back_to(new moodle_url("/local/forum/discuss.php", array('d' => $post->discussion))));

        } else if ($replycount && !has_capability('local/forum:deleteanypost', $context)) {
            print_error("couldnotdeletereplies", "local_forum",
                        local_forum_go_back_to(new moodle_url("/local/forum/discuss.php", array('d' => $post->discussion))));

        } else {
            if (! $post->parent) {  // post is a discussion topic as well, so delete discussion
                if ($forum->type == 'single') {
                    notice("Sorry, but you are not allowed to delete that discussion!",
                           local_forum_go_back_to(new moodle_url("/local/forum/discuss.php", array('d' => $post->discussion))));
                }
                local_forum_delete_discussion($discussion, false, $forum);

                $params = array(
                    'objectid' => $discussion->id,
                    'context' => $context,
                    'other' => array(
                        'forumid' => $forum->id,
                    )
                );

                redirect("view.php?f=$discussion->forum");

            } else if (local_forum_delete_post($post, has_capability('local/forum:deleteanypost', $context), $forum)) {

                if ($forum->type == 'single') {
                    // Single discussion forums are an exception. We show
                    // the forum itself since it only has one discussion
                    // thread.
                    $discussionurl = new moodle_url("/local/forum/view.php", array('f' => $forum->id));
                } else {
                    $discussionurl = new moodle_url("/local/forum/discuss.php", array('d' => $discussion->id));
                }

                redirect(local_forum_go_back_to($discussionurl));
            } else {
                print_error('errorwhiledelete', 'forum');
            }
        }


    } else { // User just asked to delete something

        local_forum_set_return();
        $PAGE->navbar->add(get_string('delete', 'local_forum'));
        $PAGE->set_title($forum->name);
        $PAGE->set_heading($forum->name);

        if ($replycount) {
            if (!has_capability('local/forum:deleteanypost', $context)) {
                print_error("couldnotdeletereplies", "local_forum",
                      local_forum_go_back_to(new moodle_url('/local/forum/discuss.php', array('d' => $post->discussion), 'p'.$post->id)));
            }
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($forum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesureplural", "local_forum", $replycount+1),
                         "post.php?delete=$delete&confirm=$delete",
                         $CFG->wwwroot.'/local/forum/discuss.php?d='.$post->discussion.'#p'.$post->id);

            local_forum_print_post($post, $discussion, $forum, false, false, false);

            if (empty($post->edit)) {
                $forumtracked = local_forum_tp_is_tracked($forum);
                $posts = local_forum_get_all_discussion_posts($discussion->id, "created ASC", $forumtracked);
                local_forum_print_posts_nested($forum, $discussion, $post, false, false, $forumtracked, $posts);
            }
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($forum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesure", "forum", $replycount),
                         "post.php?delete=$delete&confirm=$delete",
                         $CFG->wwwroot.'/local/forum/discuss.php?d='.$post->discussion.'#p'.$post->id);
            local_forum_print_post($post, $discussion, $forum, false, false, false);
        }

    }
    echo $OUTPUT->footer();
    die;


} else if (!empty($prune)) {  // Pruning

    if (!$post = local_forum_get_post_full($prune)) {
        print_error('invalidpostid', 'local_forum');
    }
    if (!$discussion = $DB->get_record("local_forum_discussions", array("id" => $post->discussion))) {
        print_error('notpartofdiscussion', 'local_forum');
    }
    if (!$forum = $DB->get_record("local_forum", array("id" => $discussion->forum))) {
        print_error('invalidforumid', 'local_forum');
    }
    if ($forum->type == 'single') {
        print_error('cannotsplit', 'local_forum');
    }
    if (!$post->parent) {
        print_error('alreadyfirstpost', 'local_forum');
    }
    
    if (!has_capability('local/forum:splitdiscussions', $context)) {
        print_error('cannotsplit', 'local_forum');
    }

    $PAGE->set_context($context);

    $prunemform = new local_forum_prune_form(null, array('prune' => $prune, 'confirm' => $prune));


    if ($prunemform->is_cancelled()) {
        redirect(local_forum_go_back_to(new moodle_url("/local/forum/discuss.php", array('d' => $post->discussion))));
    } else if ($fromform = $prunemform->get_data()) {
        // User submits the data.
        $newdiscussion = new stdClass();
        $newdiscussion->course       = $discussion->course;
        $newdiscussion->forum        = $discussion->forum;
        $newdiscussion->name         = $name;
        $newdiscussion->firstpost    = $post->id;
        $newdiscussion->userid       = $discussion->userid;
        $newdiscussion->groupid      = $discussion->groupid;
        $newdiscussion->assessed     = $discussion->assessed;
        $newdiscussion->usermodified = $post->userid;
        $newdiscussion->timestart    = $discussion->timestart;
        $newdiscussion->timeend      = $discussion->timeend;

        $newid = $DB->insert_record('local_forum_discussions', $newdiscussion);

        $newpost = new stdClass();
        $newpost->id      = $post->id;
        $newpost->parent  = 0;
        $newpost->subject = $name;

        $DB->update_record("local_forum_posts", $newpost);

        local_forum_change_discussionid($post->id, $newid);

        // Update last post in each discussion.
        local_forum_discussion_update_last_post($discussion->id);
        local_forum_discussion_update_last_post($newid);

    
        redirect(local_forum_go_back_to(new moodle_url("/local/forum/discuss.php", array('d' => $newid))));

    } else {
        // Display the prune form.
        $PAGE->navbar->add(format_string($post->subject, true), new moodle_url('/local/forum/discuss.php', array('d'=>$discussion->id)));
        $PAGE->navbar->add(get_string("prune", "local_forum"));
        $PAGE->set_title(format_string($discussion->name).": ".format_string($post->subject));
        $PAGE->set_heading($forum->name);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($forum->name), 2);
        echo $OUTPUT->heading(get_string('pruneheading', 'forum'), 3);

        $prunemform->display();

        local_forum_print_post($post, $discussion, $forum, false, false, false);
    }

    echo $OUTPUT->footer();
    die;
} else {
    print_error('unknowaction');

}

if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))){
    if($forum->costcenterid != $USER->open_costcenterid){
        redirect(new moodle_url('/local/forum/index.php'));
    }else if(!has_capability('local/costcenter:manage_ownorganization', $context)){
        if(!($forum->departmentid == '' || $forum->departmentid == 0 ) && $forum->departmentid != $USER->open_departmentid){
            redirect(new moodle_url('/local/forum/index.php'));
        }
    }
}
// from now on user must be logged on properly
require_login();

if (isguestuser()) {
    // just in case
    print_error('noguest');
}

if (!isset($forum->maxattachments)) {  // TODO - delete this once we add a field to the forum table
    $forum->maxattachments = 3;
}

$thresholdwarning = local_forum_check_throttling($forum);
$mform_post = new local_forum_post_form('post.php', array(
                                                        'context' => $context,
                                                        'forum' => $forum,
                                                        'post' => $post,
                                                        'subscribe' => \local_forum\subscriptions::is_subscribed($USER->id, $forum,
                                                                null),
                                                        'thresholdwarning' => $thresholdwarning,
                                                        'edit' => $edit), 'post', '', array('id' => 'mformforum'));

$draftitemid = file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area($draftitemid, $context->id, 'local_forum', 'attachment', empty($post->id)?null:$post->id, local_forum_post_form::attachment_options($forum));

//load data into form NOW!

if ($USER->id != $post->userid) {   // Not the original author, so add a message to the end
    $data = new stdClass();
    $data->date = userdate($post->modified);
    if ($post->messageformat == FORMAT_HTML) {
        $data->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&course='.$post->course.'">'.
                       fullname($USER).'</a>';
        $post->message .= '<p><span class="edited">('.get_string('editedby', 'local_forum', $data).')</span></p>';
    } else {
        $data->name = fullname($USER);
        $post->message .= "\n\n(".get_string('editedby', 'forum', $data).')';
    }
    unset($data);
}

$formheading = '';
if (!empty($parent)) {
    $heading = get_string("yourreply", "local_forum");
    $formheading = get_string('reply', 'local_forum');
} else {
    if ($forum->type == 'qanda') {
        $heading = get_string('yournewquestion', 'local_forum');
    } else {
        $heading = get_string('yournewtopic', 'local_forum');
    }
}

$postid = empty($post->id) ? null : $post->id;
$draftid_editor = file_get_submitted_draft_itemid('message');
$currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'local_forum', 'post', $postid, local_forum_post_form::editor_options($context, $postid), $post->message);

$manageactivities = has_capability('moodle/course:manageactivities', $context);
if (\local_forum\subscriptions::subscription_disabled($forum) && !$manageactivities) {
    // User does not have permission to subscribe to this discussion at all.
    $discussionsubscribe = false;
} else if (\local_forum\subscriptions::is_forcesubscribed($forum)) {
    // User does not have permission to unsubscribe from this discussion at all.
    $discussionsubscribe = true;
} else {
    if (isset($discussion) && \local_forum\subscriptions::is_subscribed($USER->id, $forum, $discussion->id)) {
        // User is subscribed to the discussion - continue the subscription.
        $discussionsubscribe = true;
    } else if (!isset($discussion) && \local_forum\subscriptions::is_subscribed($USER->id, $forum, null)) {
        // Starting a new discussion, and the user is subscribed to the forum - subscribe to the discussion.
        $discussionsubscribe = true;
    } else {
        // User is not subscribed to either forum or discussion. Follow user preference.
        $discussionsubscribe = $USER->autosubscribe;
    }
}

$mform_post->set_data(array(        'attachments'=>$draftitemid,
                                    'general'=>$heading,
                                    'subject'=>$post->subject,
                                    'message'=>array(
                                        'text'=>$currenttext,
                                        'format'=>empty($post->messageformat) ? editors_get_preferred_format() : $post->messageformat,
                                        'itemid'=>$draftid_editor
                                    ),
                                    'discussionsubscribe' => $discussionsubscribe,
                                    'mailnow'=>!empty($post->mailnow),
                                    'userid'=>$post->userid,
                                    'parent'=>$post->parent,
                                    'discussion'=>$post->discussion,
                                    'course'=>0) +
                                    $page_params +

                            (isset($post->format)?array(
                                    'format'=>$post->format):
                                array())+

                            (isset($discussion->timestart)?array(
                                    'timestart'=>$discussion->timestart):
                                array())+

                            (isset($discussion->timeend)?array(
                                    'timeend'=>$discussion->timeend):
                                array())+

                            (isset($discussion->pinned) ? array(
                                     'pinned' => $discussion->pinned) :
                                array()) +

                            (isset($post->groupid)?array(
                                    'groupid'=>$post->groupid):
                                array())+

                            (isset($discussion->id)?
                                    array('discussion'=>$discussion->id):
                                    array()));

if ($mform_post->is_cancelled()) {
    if (!isset($discussion->id) || $forum->type === 'qanda') {
        // Q and A forums don't have a discussion page, so treat them like a new thread..
        redirect(new moodle_url('/local/forum/view.php', array('f' => $forum->id)));
    } else {
        redirect(new moodle_url('/local/forum/discuss.php', array('d' => $discussion->id)));
    }
} else if ($fromform = $mform_post->get_data()) {

    if (empty($SESSION->fromurl)) {
        $errordestination = "$CFG->wwwroot/local/forum/view.php?f=$forum->id";
    } else {
        $errordestination = $SESSION->fromurl;
    }

    $fromform->itemid        = $fromform->message['itemid'];
    $fromform->messageformat = $fromform->message['format'];
    $fromform->message       = $fromform->message['text'];
    // WARNING: the $fromform->message array has been overwritten, do not use it anymore!
    $fromform->messagetrust  = trusttext_trusted($context);

    if ($fromform->edit) {       // Updating a post
        unset($fromform->groupid);
        $fromform->id = $fromform->edit;
        $message = '';

        //fix for bug #4314
        if (!$realpost = $DB->get_record('local_forum_posts', array('id' => $fromform->id))) {
            $realpost = new stdClass();
            $realpost->userid = -1;
        }


        // if user has edit any post capability
        // or has either startnewdiscussion or reply capability and is editting own post
        // then he can proceed
        // MDL-7066
        if ( !(($realpost->userid == $USER->id && (has_capability('local/forum:replypost', $context)
                            || has_capability('local/forum:startdiscussion', $context))) ||
                            has_capability('local/forum:editanypost', $context)) ) {
            print_error('cannotupdatepost', 'local_forum');
        }
        // When editing first post/discussion.
        if (!$fromform->parent) {
            if (has_capability('local/forum:pindiscussions', $context)) {
                // Can change pinned if we have capability.
                $fromform->pinned = !empty($fromform->pinned) ? LOCAL_FORUM_DISCUSSION_PINNED : LOCAL_FORUM_DISCUSSION_UNPINNED;
            } else {
                // We don't have the capability to change so keep to previous value.
                unset($fromform->pinned);
            }
        }
        $updatepost = $fromform; //realpost
        $updatepost->forum = $forum->id;
        if (!local_forum_update_post($updatepost, $mform_post)) {
            print_error("couldnotupdate", "local_forum", $errordestination);
        }

        // MDL-11818
        if (($forum->type == 'single') && ($updatepost->parent == '0')){ // updating first post of single discussion type -> updating forum intro
            $forum->intro = $updatepost->message;
            $forum->timemodified = time();
            $DB->update_record("local_forum", $forum);
        }

        if ($realpost->userid == $USER->id) {
            $message .= get_string("postupdated", "local_forum");
        } else {
            $realuser = $DB->get_record('user', array('id' => $realpost->userid));
            $message .= get_string("editedpostupdated", "local_forum", fullname($realuser));
        }

        $subscribemessage = local_forum_post_subscription($fromform, $forum, $discussion);
        if ($forum->type == 'single') {
            // Single discussion forums are an exception. We show
            // the forum itself since it only has one discussion
            // thread.
            $discussionurl = new moodle_url("/local/forum/view.php", array('f' => $forum->id));
        } else {
            $discussionurl = new moodle_url("/local/forum/discuss.php", array('d' => $discussion->id), 'p' . $fromform->id);
        }

        $params = array(
            'context' => $context,
            'objectid' => $fromform->id,
            'other' => array(
                'discussionid' => $discussion->id,
                'forumid' => $forum->id,
                'forumtype' => $forum->type,
            )
        );

        if ($realpost->userid !== $USER->id) {
            $params['relateduserid'] = $realpost->userid;
        }


        redirect(local_forum_go_back_to($discussionurl), $message . $subscribemessage,null,\core\output\notification::NOTIFY_SUCCESS
            );

    } else if ($fromform->discussion) { // Adding a new post to an existing discussion
        // Before we add this we must check that the user will not exceed the blocking threshold.
        local_forum_check_blocking_threshold($thresholdwarning);

        unset($fromform->groupid);
        $message = '';
        $addpost = $fromform;
        $addpost->forum = $forum->id;
        if ($fromform->id = local_forum_add_new_post($addpost, $mform_post)) {
            $subscribemessage = local_forum_post_subscription($fromform, $forum, $discussion);

            if (!empty($fromform->mailnow)) {
                $message .= get_string("postmailnow", "local_forum");
            } else {
                $message .= '<p>'.get_string("postaddedsuccess", "local_forum") . '</p>';
                $message .= '<p>'.get_string("postaddedtimeleft", "local_forum", format_time($CFG->maxeditingtime)) . '</p>';
            }

            if ($forum->type == 'single') {
                // Single discussion forums are an exception. We show
                // the forum itself since it only has one discussion
                // thread.
                $discussionurl = new moodle_url("/local/forum/view.php", array('f' => $forum->id), 'p'.$fromform->id);
            } else {
                $discussionurl = new moodle_url("/local/forum/discuss.php", array('d' => $discussion->id), 'p'.$fromform->id);
            }

            $params = array(
                'context' => $context,
                'objectid' => $fromform->id,
                'other' => array(
                    'discussionid' => $discussion->id,
                    'forumid' => $forum->id,
                    'forumtype' => $forum->type,
                )
            );

            redirect(
                    local_forum_go_back_to($discussionurl),
                    $message . $subscribemessage,
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );

        } else {
            print_error("couldnotadd", "local_forum", $errordestination);
        }
        exit;

    } else { // Adding a new discussion.
        // The location to redirect to after successfully posting.
        $redirectto = new moodle_url('view.php', array('f' => $fromform->forum));

        $fromform->mailnow = empty($fromform->mailnow) ? 0 : 1;

        $discussion = $fromform;
        $discussion->name = $fromform->subject;

        $newstopic = false;
        if ($forum->type == 'news' && !$fromform->parent) {
            $newstopic = true;
        }
        $discussion->timestart = $fromform->timestart;
        $discussion->timeend = $fromform->timeend;

        if (has_capability('local/forum:pindiscussions', $context) && !empty($fromform->pinned)) {
            $discussion->pinned = LOCAL_FORUM_DISCUSSION_PINNED;
        } else {
            $discussion->pinned = LOCAL_FORUM_DISCUSSION_UNPINNED;
        }
        
        if ($discussion->id = local_forum_add_discussion($discussion, $mform_post)) {

            if ($fromform->mailnow) {
                $message .= get_string("postmailnow", "local_forum");
            } else {
                $message .= '<p>'.get_string("postaddedsuccess", "local_forum") . '</p>';
                $message .= '<p>'.get_string("postaddedtimeleft", "local_forum", format_time($CFG->maxeditingtime)) . '</p>';
            }

            $subscribemessage = local_forum_post_subscription($fromform, $forum, $discussion);
        } else {
            print_error("couldnotadd", "local_forum", $errordestination);
        }
        
        // Redirect back to the discussion.
        redirect(
                local_forum_go_back_to($redirectto->out()),
                $message . $subscribemessage,
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
    }
}



// To get here they need to edit a post, and the $post
// variable will be loaded with all the particulars,
// so bring up the form.

// $course, $forum are defined.  $discussion is for edit and reply only.

if ($post->discussion) {
    if (! $toppost = $DB->get_record("local_forum_posts", array("discussion" => $post->discussion, "parent" => 0))) {
        print_error('cannotfindparentpost', 'local_forum', '', $post->id);
    }
} else {
    $toppost = new stdClass();
    $toppost->subject = ($forum->type == "news") ? get_string("addanewtopic", "local_forum") :
                                                   get_string("addanewdiscussion", "local_forum");
}

if (empty($post->edit)) {
    $post->edit = '';
}

if (empty($discussion->name)) {
    if (empty($discussion)) {
        $discussion = new stdClass();
    }
    $discussion->name = $forum->name;
}
if ($forum->type == 'single') {
    // There is only one discussion thread for this forum type. We should
    // not show the discussion name (same as forum name in this case) in
    // the breadcrumbs.
    $strdiscussionname = '';
} else {
    // Show the discussion name in the breadcrumbs.
    $strdiscussionname = format_string($discussion->name).':';
}

$forcefocus = empty($reply) ? NULL : 'message';

if (!empty($discussion->id)) {
    $PAGE->navbar->add(format_string($toppost->subject, true), "discuss.php?d=$discussion->id");
}

if ($post->parent) {
    $PAGE->navbar->add(get_string('reply', 'local_forum'));
}

if ($edit) {
    $PAGE->navbar->add(get_string('edit', 'local_forum'));
}

$PAGE->set_title(format_string($toppost->subject));
$PAGE->set_heading($toppost->subject);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($forum->name), 2);

// checkup
if (!empty($parent) && !local_forum_user_can_see_post($forum, $discussion, $post, null)) {
    print_error('cannotreply', 'local_forum');
}
if (empty($parent) && empty($edit) && !local_forum_user_can_post_discussion($forum, $context)) {
    print_error('cannotcreatediscussion', 'local_forum');
}

if ($forum->type == 'qanda'
            && !has_capability('local/forum:viewqandawithoutposting', $context)
            && !empty($discussion->id)
            && !local_forum_user_has_posted($forum->id, $discussion->id, $USER->id)) {
    echo $OUTPUT->notification(get_string('qandanotify','local_forum'));
}

// If there is a warning message and we are not editing a post we need to handle the warning.
if (!empty($thresholdwarning) && !$edit) {
    // Here we want to throw an exception if they are no longer allowed to post.
    local_forum_check_blocking_threshold($thresholdwarning);
}

if (!empty($parent)) {
    if (!$discussion = $DB->get_record('local_forum_discussions', array('id' => $parent->discussion))) {
        print_error('notpartofdiscussion', 'forum');
    }

    local_forum_print_post($parent, $discussion, $forum, false, false, false);
    if (empty($post->edit)) {
        if ($forum->type != 'qanda' || local_forum_user_can_see_discussion($forum, $discussion, $context)) {
            $forumtracked = local_forum_tp_is_tracked($forum);
            $posts = local_forum_get_all_discussion_posts($discussion->id, "created ASC", $forumtracked);
            local_forum_print_posts_threaded($forum, $discussion, $parent, 0, false, $forumtracked, $posts);
        }
    }
} else {
    if (!empty($forum->intro)) {
        echo $OUTPUT->box($forum->intro, 'generalbox', 'intro');

        if (!empty($CFG->enableplagiarism)) {
            require_once($CFG->libdir.'/plagiarismlib.php');
            echo plagiarism_print_disclosure($forum->id);
        }
    }
}

if (!empty($formheading)) {
    echo $OUTPUT->heading($formheading, 2, array('class' => 'accesshide'));
}

$data = new StdClass();
/* if (isset($postid)) {
    $data->tags = core_tag_tag::get_item_tags_array('local_forum', 'forum_posts', $postid);
    $mform_post->set_data($data);
} */

$mform_post->display();

echo $OUTPUT->footer();
