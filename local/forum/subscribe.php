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
 * @subpackage local_forums
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/forum/lib.php');

$id             = required_param('id', PARAM_INT);             // The forum to set subscription on.
$mode           = optional_param('mode', null, PARAM_INT);     // The forum's subscription mode.
$user           = optional_param('user', 0, PARAM_INT);        // The userid of the user to subscribe, defaults to $USER.
$discussionid   = optional_param('d', null, PARAM_INT);        // The discussionid to subscribe.
$sesskey        = optional_param('sesskey', null, PARAM_RAW);
$returnurl      = optional_param('returnurl', null, PARAM_RAW);

$url = new moodle_url('/local/forum/subscribe.php', array('id'=>$id));
if (!is_null($mode)) {
    $url->param('mode', $mode);
}
if ($user !== 0) {
    $url->param('user', $user);
}
if (!is_null($sesskey)) {
    $url->param('sesskey', $sesskey);
}
if (!is_null($discussionid)) {
    $url->param('d', $discussionid);
    if (!$discussion = $DB->get_record('local_forum_discussions', array('id' => $discussionid, 'forum' => $id))) {
        print_error('invaliddiscussionid', 'local_forum');
    }
}
$PAGE->set_url($url);

$forum   = $DB->get_record('local_forum', array('id' => $id), '*', MUST_EXIST);
$context = context_system::instance();

if ($user) {
    require_sesskey();
    if (!has_capability('local/forum:managesubscriptions', $context)) {
        print_error('nopermissiontosubscribe', 'forum');
    }
    $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);
} else {
    $user = $USER;
}

$issubscribed = \local_forum\subscriptions::is_subscribed($user->id, $forum, $discussionid);

require_login();

if (is_null($mode) and !isloggedin()) {   // Guests and visitors can't subscribe - only enrolled
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);
    if (isguestuser()) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('subscribeenrolledonly', 'local_forum').'<br /><br />'.get_string('liketologin'),
                     get_login_url(), new moodle_url('/local/forum/view.php', array('f'=>$id)));
        echo $OUTPUT->footer();
        exit;
    } else {
        // There should not be any links leading to this place, just redirect.
        redirect(
                new moodle_url('/local/forum/view.php', array('f'=>$id)),
                get_string('subscribeenrolledonly', 'forum'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
    }
}


$returnto = "index.php";
if ($returnurl) {
    $returnto = $returnurl;
}

if (!is_null($mode) and has_capability('local/forum:managesubscriptions', $context)) {
    require_sesskey();
    switch ($mode) {
        case LOCAL_FORUM_CHOOSESUBSCRIBE : // 0
            \local_forum\subscriptions::set_subscription_mode($forum->id, LOCAL_FORUM_CHOOSESUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('everyonecannowchoose', 'local_forum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        case LOCAL_FORUM_FORCESUBSCRIBE : // 1
            \local_forum\subscriptions::set_subscription_mode($forum->id, LOCAL_FORUM_FORCESUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('everyoneisnowsubscribed', 'local_forum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        case LOCAL_FORUM_INITIALSUBSCRIBE : // 2
            if ($forum->forcesubscribe <> LOCAL_FORUM_INITIALSUBSCRIBE) {
                $users = \local_forum\subscriptions::get_potential_subscribers($context, 0, 'u.id, u.email', '');
                foreach ($users as $user) {
                    \local_forum\subscriptions::subscribe_user($user->id, $forum, $context);
                }
            }
            \local_forum\subscriptions::set_subscription_mode($forum->id, LOCAL_FORUM_INITIALSUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('everyoneisnowsubscribed', 'local_forum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        case LOCAL_FORUM_DISALLOWSUBSCRIBE : // 3
            \local_forum\subscriptions::set_subscription_mode($forum->id, LOCAL_FORUM_DISALLOWSUBSCRIBE);
            redirect(
                    $returnto,
                    get_string('noonecansubscribenow', 'local_forum'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            break;
        default:
            print_error(get_string('invalidforcesubscribe', 'local_forum'));
    }
}

if (\local_forum\subscriptions::is_forcesubscribed($forum)) {
    redirect(
            $returnto,
            get_string('everyoneisnowsubscribed', 'local_forum'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
}

$info = new stdClass();
$info->name  = fullname($user);
$info->forum = format_string($forum->name);

if ($issubscribed) {
    if (is_null($sesskey)) {
        // We came here via link in email.
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();

        $viewurl = new moodle_url('/local/forum/view.php', array('f' => $id));
        if ($discussionid) {
            $a = new stdClass();
            $a->forum = format_string($forum->name);
            $a->discussion = format_string($discussion->name);
            echo $OUTPUT->confirm(get_string('confirmunsubscribediscussion', 'local_forum', $a),
                    $PAGE->url, $viewurl);
        } else {
            echo $OUTPUT->confirm(get_string('confirmunsubscribe', 'local_forum', format_string($forum->name)),
                    $PAGE->url, $viewurl);
        }
        echo $OUTPUT->footer();
        exit;
    }
    require_sesskey();
    if ($discussionid === null) {
        if (\local_forum\subscriptions::unsubscribe_user($user->id, $forum, $context, true)) {
            redirect(
                    $returnto,
                    get_string('nownotsubscribed', 'local_forum', $info),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
        } else {
            print_error('cannotunsubscribe', 'local_forum', get_local_referer(false));
        }
    } else {
        if (\local_forum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion, $context)) {
            $info->discussion = $discussion->name;
            redirect(
                    $returnto,
                    get_string('discussionnownotsubscribed', 'local_forum', $info),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
        } else {
            print_error('cannotunsubscribe', 'local_forum', get_local_referer(false));
        }
    }

} else {  // subscribe
    if (\local_forum\subscriptions::subscription_disabled($forum) && !has_capability('local/forum:managesubscriptions', $context)) {
        print_error('disallowsubscribe', 'local_forum', get_local_referer(false));
    }
    if (!has_capability('local/forum:viewdiscussion', $context)) {
        print_error('noviewdiscussionspermission', 'local_forum', get_local_referer(false));
    }
    if (is_null($sesskey)) {
        // We came here via link in email.
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();

        $viewurl = new moodle_url('/local/forum/view.php', array('f' => $id));
        if ($discussionid) {
            $a = new stdClass();
            $a->forum = format_string($forum->name);
            $a->discussion = format_string($discussion->name);
            echo $OUTPUT->confirm(get_string('confirmsubscribediscussion', 'local_forum', $a),
                    $PAGE->url, $viewurl);
        } else {
            echo $OUTPUT->confirm(get_string('confirmsubscribe', 'local_forum', format_string($forum->name)),
                    $PAGE->url, $viewurl);
        }
        echo $OUTPUT->footer();
        exit;
    }
    require_sesskey();
    if ($discussionid == null) {
        \local_forum\subscriptions::subscribe_user($user->id, $forum, $context, true);
        redirect(
                $returnto,
                get_string('nowsubscribed', 'forum', $info),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
    } else {
        $info->discussion = $discussion->name;
        \local_forum\subscriptions::subscribe_user_to_discussion($user->id, $discussion, $context);
        redirect(
                $returnto,
                get_string('discussionnowsubscribed', 'forum', $info),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
    }
}
