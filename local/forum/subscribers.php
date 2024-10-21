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


require_once("../../config.php");
require_once("lib.php");

$id    = required_param('id',PARAM_INT);           // forum
$edit  = optional_param('edit',-1,PARAM_BOOL);     // Turn editing on and off

$url = new moodle_url('/local/forum/subscribers.php', array('id'=>$id));

if ($edit !== 0) {
    $url->param('edit', $edit);
}
$PAGE->set_url($url);

$forum = $DB->get_record('local_forum', array('id'=>$id), '*', MUST_EXIST);


require_login();

$context = context_system::instance();
if (!has_capability('local/forum:viewsubscribers', $context)) {
    print_error('nopermissiontosubscribe', 'forum');
}

unset($SESSION->fromdiscussion);


$forumoutput = $PAGE->get_renderer('local_forum');
$options = array('forumid'=>$forum->id, 'context'=>$context);
$existingselector = new local_forum_existing_subscriber_selector('existingsubscribers', $options);
$subscriberselector = new local_forum_potential_subscriber_selector('potentialsubscribers', $options);
$subscriberselector->set_existing_subscribers($existingselector->find_users(''));

if (data_submitted()) {
    require_sesskey();
    $subscribe = (bool)optional_param('subscribe', false, PARAM_RAW);
    $unsubscribe = (bool)optional_param('unsubscribe', false, PARAM_RAW);
    /** It has to be one or the other, not both or neither */
    if (!($subscribe xor $unsubscribe)) {
        print_error('invalidaction');
    }
    if ($subscribe) {
        $users = $subscriberselector->get_selected_users();
        foreach ($users as $user) {
            if (!\local_forum\subscriptions::subscribe_user($user->id, $forum)) {
                print_error('cannotaddsubscriber', 'forum', '', $user->id);
            }
        }
    } else if ($unsubscribe) {
        $users = $existingselector->get_selected_users();
        foreach ($users as $user) {
            if (!\local_forum\subscriptions::unsubscribe_user($user->id, $forum)) {
                print_error('cannotremovesubscriber', 'forum', '', $user->id);
            }
        }
    }
    $subscriberselector->invalidate_selected_users();
    $existingselector->invalidate_selected_users();
    $subscriberselector->set_existing_subscribers($existingselector->find_users(''));
}

$strsubscribers = get_string("subscribers", "forum");
$PAGE->navbar->add($strsubscribers);
$PAGE->set_title($strsubscribers);
$PAGE->set_heading($COURSE->fullname);
if (has_capability('local/forum:managesubscriptions', $context) && \local_forum\subscriptions::is_forcesubscribed($forum) === false) {
    if ($edit != -1) {
        $USER->subscriptionsediting = $edit;
    }
    $updatesubscriptionsbutton = local_forum_update_subscriptions_button($id);
} else {
    $updatesubscriptionsbutton = '';
    unset($USER->subscriptionsediting);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('forum', 'forum').' '.$strsubscribers);
if (!empty($updatesubscriptionsbutton)) {
    echo \html_writer::div($updatesubscriptionsbutton, 'pull-right');
}
if (empty($USER->subscriptionsediting)) {
    $subscribers = \local_forum\subscriptions::fetch_subscribed_users($forum, $context);
    if (\local_forum\subscriptions::is_forcesubscribed($forum)) {
        $subscribers = local_forum_filter_hidden_users($context, $subscribers);
    }
    echo $forumoutput->subscriber_overview($subscribers, $forum);
} else {
    echo $forumoutput->subscriber_selection_form($existingselector, $subscriberselector);
}
if (!empty($updatesubscriptionsbutton)) {
    echo $updatesubscriptionsbutton;
}
echo $OUTPUT->footer();

/**
 * Filters a list of users for whether they can see a given activity.
 * If the course module is hidden (closed-eye icon), then only users who have
 * the permission to view hidden activities will appear in the output list.
 *
 * @todo MDL-48625 This filtering should be handled in core libraries instead.
 *
 * @param stdClass $cm the course module record of the activity.
 * @param context_module $context the activity context, to save re-fetching it.
 * @param array $users the list of users to filter.
 * @return array the filtered list of users.
 */
function local_forum_filter_hidden_users($context, array $users) {
    // Filter for users that can view hidden activities.
    $filteredusers = array();
    $hiddenviewers = get_users_by_capability($context, 'moodle/course:viewhiddenactivities');
    foreach ($hiddenviewers as $hiddenviewer) {
        if (array_key_exists($hiddenviewer->id, $users)) {
            $filteredusers[$hiddenviewer->id] = $users[$hiddenviewer->id];
        }
    }
    return $filteredusers;
    
}
