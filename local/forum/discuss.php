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

$d      = required_param('d', PARAM_INT);                // Discussion ID
$parent = optional_param('parent', 0, PARAM_INT);        // If set, then display this post and all children.
$mode   = optional_param('mode', 0, PARAM_INT);          // If set, changes the layout of the thread
$move   = optional_param('move', 0, PARAM_INT);          // If set, moves this discussion to another forum
$mark   = optional_param('mark', '', PARAM_ALPHA);       // Used for tracking read posts if user initiated.
$postid = optional_param('postid', 0, PARAM_INT);        // Used for tracking read posts if user initiated.
$pin    = optional_param('pin', -1, PARAM_INT);          // If set, pin or unpin this discussion.

$url = new moodle_url('/local/forum/discuss.php', array('d'=>$d));
if ($parent !== 0) {
    $url->param('parent', $parent);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$discussion = $DB->get_record('local_forum_discussions', array('id' => $d), '*', MUST_EXIST);
$forum = $DB->get_record('local_forum', array('id' => $discussion->forum), '*', MUST_EXIST);

require_login();

// move this down fix for MDL-6926
require_once($CFG->dirroot.'/local/forum/lib.php');

$context = context_system::instance();
if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))){
    if($forum->costcenterid != $USER->open_costcenterid){
        redirect(new moodle_url('/local/forum/index.php'));
    }else if(!has_capability('local/costcenter:manage_ownorganization', $context)){
        if(!($forum->departmentid == '' || $forum->departmentid == 0 ) && $forum->departmentid != $USER->open_departmentid){
            redirect(new moodle_url('/local/forum/index.php'));
        }
    }
}
require_capability('local/forum:viewdiscussion', $context, NULL, true, 'noviewdiscussionspermission', 'local_forum');

if (!empty($CFG->enablerssfeeds) && !empty($CFG->forum_enablerssfeeds) && $forum->rsstype && $forum->rssarticles) {
    require_once("$CFG->libdir/rsslib.php");

    $rsstitle = format_string($forum->name, true, array('context' => context_system::instance())) . ': ' . format_string($forum->name);
    rss_add_http_header($context, 'local_forum', $forum, $rsstitle);
}

// Move discussion if requested.
if ($move > 0 and confirm_sesskey()) {
    $return = $CFG->wwwroot.'/local/forum/discuss.php?d='.$discussion->id;

    if (!$forumto = $DB->get_record('forum', array('id' => $move))) {
        print_error('cannotmovetonotexist', 'local_forum', $return);
    }

    require_capability('local/forum:movediscussions', $context);

    if ($forum->type == 'single') {
        print_error('cannotmovefromsingleforum', 'local_forum', $return);
    }

    if (!$forumto = $DB->get_record('local_forum', array('id' => $move))) {
        print_error('cannotmovetonotexist', 'local_forum', $return);
    }

    if ($forumto->type == 'single') {
        print_error('cannotmovetosingleforum', 'local_forum', $return);
    }


    require_capability('local/forum:startdiscussion', $context);

    if (!local_forum_move_attachments($discussion, $forum->id, $forumto->id)) {
        echo $OUTPUT->notification("Errors occurred while moving attachment directories - check your file permissions");
    }
    // For each subscribed user in this forum and discussion, copy over per-discussion subscriptions if required.
    $discussiongroup = $discussion->groupid == -1 ? 0 : $discussion->groupid;
    $potentialsubscribers = \local_forum\subscriptions::fetch_subscribed_users(
        $forum,
        $discussiongroup,
        $context,
        'u.id',
        true
    );

    // Pre-seed the subscribed_discussion caches.
    // Firstly for the forum being moved to.
    \local_forum\subscriptions::fill_subscription_cache($forumto->id);
    // And also for the discussion being moved.
    \local_forum\subscriptions::fill_subscription_cache($forum->id);
    $subscriptionchanges = array();
    $subscriptiontime = time();
    foreach ($potentialsubscribers as $subuser) {
        $userid = $subuser->id;
        $targetsubscription = \local_forum\subscriptions::is_subscribed($userid, $forumto, null, $cmto);
        $discussionsubscribed = \local_forum\subscriptions::is_subscribed($userid, $forum, $discussion->id);
        $forumsubscribed = \local_forum\subscriptions::is_subscribed($userid, $forum);

        if ($forumsubscribed && !$discussionsubscribed && $targetsubscription) {
            // The user has opted out of this discussion and the move would cause them to receive notifications again.
            // Ensure they are unsubscribed from the discussion still.
            $subscriptionchanges[$userid] = \local_forum\subscriptions::LOCAL_FORUM_DISCUSSION_UNSUBSCRIBED;
        } else if (!$forumsubscribed && $discussionsubscribed && !$targetsubscription) {
            // The user has opted into this discussion and would otherwise not receive the subscription after the move.
            // Ensure they are subscribed to the discussion still.
            $subscriptionchanges[$userid] = $subscriptiontime;
        }
    }

    $DB->set_field('local_forum_discussions', 'forum', $forumto->id, array('id' => $discussion->id));
    $DB->set_field('local_forum_read', 'forumid', $forumto->id, array('discussionid' => $discussion->id));

    // Delete the existing per-discussion subscriptions and replace them with the newly calculated ones.
    $DB->delete_records('local_forum_discussion_subs', array('discussion' => $discussion->id));
    $newdiscussion = clone $discussion;
    $newdiscussion->forum = $forumto->id;
    foreach ($subscriptionchanges as $userid => $preference) {
        if ($preference != \local_forum\subscriptions::LOCAL_FORUM_DISCUSSION_UNSUBSCRIBED) {
            // Users must have viewdiscussion to a discussion.
            if (has_capability('local/forum:viewdiscussion', $context, $userid)) {
                \local_forum\subscriptions::subscribe_user_to_discussion($userid, $newdiscussion, $context);
            }
        } else {
            \local_forum\subscriptions::unsubscribe_user_from_discussion($userid, $newdiscussion, $context);
        }
    }

    // Delete the RSS files for the 2 forums to force regeneration of the feeds
    require_once($CFG->dirroot.'/local/forum/rsslib.php');
    local_forum_rss_delete_file($forum);
    local_forum_rss_delete_file($forumto);

    redirect($return.'&move=-1&sesskey='.sesskey());
}
// Pin or unpin discussion if requested.
if ($pin !== -1 && confirm_sesskey()) {
    require_capability('local/forum:pindiscussions', $context);

    $params = array('context' => $context, 'objectid' => $discussion->id, 'other' => array('forumid' => $forum->id));

    switch ($pin) {
        case LOCAL_FORUM_DISCUSSION_PINNED:
            // Pin the discussion and trigger discussion pinned event.
            local_forum_discussion_pin($context, $forum, $discussion);
            break;
        case LOCAL_FORUM_DISCUSSION_UNPINNED:
            // Unpin the discussion and trigger discussion unpinned event.
            local_forum_discussion_unpin($context, $forum, $discussion);
            break;
        default:
            echo $OUTPUT->notification("Invalid value when attempting to pin/unpin discussion");
            break;
    }

    redirect(new moodle_url('/local/forum/discuss.php', array('d' => $discussion->id)));
}

// Trigger discussion viewed event.
local_forum_discussion_view($context, $forum, $discussion);

unset($SESSION->fromdiscussion);

if ($mode) {
    set_user_preference('forum_displaymode', $mode);
}

$displaymode = get_user_preferences('forum_displaymode', $CFG->forum_displaymode);

if ($parent) {
    // If flat AND parent, then force nested display this time
    if ($displaymode == LOCAL_FORUM_MODE_FLATOLDEST or $displaymode == LOCAL_FORUM_MODE_FLATNEWEST) {
        $displaymode = LOCAL_FORUM_MODE_NESTED;
    }
} else {
    $parent = $discussion->firstpost;
}

if (! $post = local_forum_get_post_full($parent)) {
    print_error("notexists", 'forum', "$CFG->wwwroot/local/forum/view.php?f=$forum->id");
}

if (!local_forum_user_can_see_post($forum, $discussion, $post, null)) {
    print_error('noviewdiscussionspermission', 'forum', "$CFG->wwwroot/local/forum/view.php?id=$forum->id");
}

if ($mark == 'read' or $mark == 'unread') {
    if ($CFG->local_forum_usermarksread && local_forum_tp_can_track_forums($forum) && local_forum_tp_is_tracked($forum)) {
        if ($mark == 'read') {
            local_forum_tp_add_read_record($USER->id, $postid);
        } else {
            // unread
            local_forum_tp_delete_read_records($USER->id, $postid);
        }
    }
}

//$searchform = local_forum_search_form($forum);
$PAGE->navbar->add( get_string("forums", "local_forum"), new moodle_url('index.php'));
$PAGE->navbar->add( $forum->name, new moodle_url('view.php?f='.$forum->id.''));
$forumnode = $PAGE->navbar;
$node = $forumnode->add(format_string($discussion->name), new moodle_url('/local/forum/discuss.php', array('d'=>$discussion->id)));
$node->display = false;
if ($node && $post->id != $discussion->firstpost) {
    $node->add(format_string($post->subject), $PAGE->url);
}

$PAGE->set_title("$forum->name: ".format_string($discussion->name));
$PAGE->set_heading($forum->name);
//$PAGE->set_button($searchform);
$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('local_forum');

echo $OUTPUT->header();

//echo $OUTPUT->heading(format_string($forum->name), 2);
if(isset($_SERVER['HTTP_REFERER'])) {
    $previous = $_SERVER['HTTP_REFERER'];//".$CFG->wwwroot."/local/forum/index.php
}
echo "<div class='coursebackup course_extended_menu_itemcontainer pull-right'>
            <a href='".$previous."' title='".get_string("back")."' class='course_extended_menu_itemlink'>
              <i class='icon fa fa-reply'></i>
            </a>
        </div> <br>
       ";

echo $OUTPUT->heading(format_string($discussion->name), 3, 'discussionname');


//Added back button


// is_guest should be used here as this also checks whether the user is a guest in the current course.
// Guests and visitors cannot subscribe - only enrolled users.
if (( isloggedin()) && has_capability('local/forum:viewdiscussion', $context)) {
    // Discussion subscription.
    if (\local_forum\subscriptions::is_subscribable($forum)) {
        echo html_writer::div(
            local_forum_get_discussion_subscription_icon($forum, $post->discussion, null, true),
            'discussionsubscription'
        );
        echo local_forum_get_discussion_subscription_icon_preloaders();
    }
}


/// Check to see if groups are being used in this forum
/// If so, make sure the current person is allowed to see this discussion
/// Also, if we know they should be able to reply, then explicitly set $canreply for performance reasons

$canreply = local_forum_user_can_post($forum, $discussion, $USER, $context);
if (!$canreply and $forum->type !== 'news') {
    if (isguestuser() or !isloggedin()) {
        $canreply = true;
    }
}

// Output the links to neighbour discussions.
$neighbours = local_forum_get_discussion_neighbours($discussion, $forum);
$neighbourlinks = $renderer->neighbouring_discussion_navigation($neighbours['prev'], $neighbours['next']);
echo $neighbourlinks;

/// Print the controls across the top
echo '<div class="discussioncontrols clearfix"><div class="controlscontainer m-b-1">';

if (!empty($CFG->enableportfolios) && has_capability('local/forum:exportdiscussion', $context)) {
    require_once($CFG->libdir.'/portfoliolib.php');
    $button = new portfolio_add_button();
    $button->set_callback_options('forum_portfolio_caller', array('discussionid' => $discussion->id), 'local_forum');
    $button = $button->to_html(PORTFOLIO_ADD_FULL_FORM, get_string('exportdiscussion', 'local_forum'));
    $buttonextraclass = '';
    if (empty($button)) {
        // no portfolio plugin available.
        $button = '&nbsp;';
        $buttonextraclass = ' noavailable';
    }
    echo html_writer::tag('div', $button, array('class' => 'discussioncontrol exporttoportfolio'.$buttonextraclass));
} else {
    echo html_writer::tag('div', '&nbsp;', array('class'=>'discussioncontrol nullcontrol'));
}

// groups selector not needed here
echo '<div class="discussioncontrol displaymode">';
local_forum_print_mode_form($discussion->id, $displaymode);
echo "</div>";

if (has_capability('local/forum:pindiscussions', $context)) {
    if ($discussion->pinned == LOCAL_FORUM_DISCUSSION_PINNED) {
        $pinlink = LOCAL_FORUM_DISCUSSION_UNPINNED;
        $pintext = get_string('discussionunpin', 'forum');
    } else {
        $pinlink = LOCAL_FORUM_DISCUSSION_PINNED;
        $pintext = get_string('discussionpin', 'forum');
    }
    $button = new single_button(new moodle_url('discuss.php', array('pin' => $pinlink, 'd' => $discussion->id)), $pintext, 'post');
    echo html_writer::tag('div', $OUTPUT->render($button), array('class' => 'discussioncontrol pindiscussion'));
}


echo "</div></div>";

if (local_forum_discussion_is_locked($forum, $discussion)) {
    echo $OUTPUT->notification(get_string('discussionlocked', 'local_forum'), \core\output\notification::NOTIFY_INFO . ' discussionlocked');
}

if (!empty($forum->blockafter) && !empty($forum->blockperiod)) {
    $a = new stdClass();
    $a->blockafter  = $forum->blockafter;
    $a->blockperiod = get_string('secondstotime'.$forum->blockperiod);
    echo $OUTPUT->notification(get_string('thisforumisthrottled','local_forum',$a));
}

if ($forum->type == 'qanda' && !has_capability('local/forum:viewqandawithoutposting', $context) &&
            !local_forum_user_has_posted($forum->id,$discussion->id,$USER->id)) {
    echo $OUTPUT->notification(get_string('qandanotify', 'local_forum'));
}

if ($move == -1 and confirm_sesskey()) {
    echo $OUTPUT->notification(get_string('discussionmoved', 'local_forum', format_string($forum->name,true)), 'notifysuccess');
}

$canrate = has_capability('local/forum:rate', $context);
local_forum_print_discussion($forum, $discussion, $post, $displaymode, $canreply, $canrate);

echo $neighbourlinks;

// Add the subscription toggle JS.
$PAGE->requires->yui_module('moodle-local_forum-subscriptiontoggle', 'Y.M.local_forum.subscriptiontoggle.init');

echo $OUTPUT->footer();
