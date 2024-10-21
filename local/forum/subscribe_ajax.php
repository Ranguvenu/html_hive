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


define('AJAX_SCRIPT', true);
require(__DIR__.'/../../config.php');
require_once($CFG->dirroot . '/local/forum/lib.php');

$forumid        = required_param('forumid', PARAM_INT);             // The forum to subscribe or unsubscribe.
$discussionid   = optional_param('discussionid', null, PARAM_INT);  // The discussionid to subscribe.
$includetext    = optional_param('includetext', false, PARAM_BOOL);

$forum          = $DB->get_record('local_forum', array('id' => $forumid), '*', MUST_EXIST);
if (!$discussion = $DB->get_record('local_forum_discussions', array('id' => $discussionid, 'forum' => $forumid))) {
    print_error('invaliddiscussionid', 'local_forum');
}

$context        = context_system::instance();
$PAGE->set_context($context);
require_sesskey();
require_login();
require_capability('local/forum:viewdiscussion', $context);

$return = new stdClass();

if (!isloggedin()) {
    // is_guest should be used here as this also checks whether the user is a guest in the current course.
    // Guests and visitors cannot subscribe - only enrolled users.
    throw new moodle_exception('noguestsubscribe', 'local_forum');
}

if (!\local_forum\subscriptions::is_subscribable($forum)) {
    // Nothing to do. We won't actually output any content here though.
    echo json_encode($return);
    die;
}

if (\local_forum\subscriptions::is_subscribed($USER->id, $forum, $discussion->id)) {
    // The user is subscribed, unsubscribe them.
    \local_forum\subscriptions::unsubscribe_user_from_discussion($USER->id, $discussion, $context);
} else {
    // The user is unsubscribed, subscribe them.
    \local_forum\subscriptions::subscribe_user_to_discussion($USER->id, $discussion, $context);
}

// Now return the updated subscription icon.
$return->icon = local_forum_get_discussion_subscription_icon($forum, $discussion->id, null, $includetext);
echo json_encode($return);
die;
