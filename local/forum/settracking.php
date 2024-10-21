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

$id         = required_param('id',PARAM_INT);                           // The forum to subscribe or unsubscribe to
$returnpage = optional_param('returnpage', 'index.php', PARAM_FILE);    // Page to return to.

require_sesskey();

if (! $forum = $DB->get_record("forum", array("id" => $id))) {
    print_error('invalidforumid', 'forum');
}

require_login();
$returnpageurl = new moodle_url('/local/forum/' . $returnpage);
$returnto = local_forum_go_back_to($returnpageurl);

if (!local_forum_tp_can_track_forums($forum)) {
    redirect($returnto);
}

$info = new stdClass();
$info->name  = fullname($USER);
$info->forum = format_string($forum->name);

$eventparams = array(
    'context' => context_system::instance(),
    'relateduserid' => $USER->id,
    'other' => array('forumid' => $forum->id),
);

if (local_forum_tp_is_tracked($forum) ) {
    if (local_forum_tp_stop_tracking($forum->id)) {
        $event = \local_forum\event\readtracking_disabled::create($eventparams);
        $event->trigger();
        redirect($returnto, get_string("nownottracking", "local_forum", $info), 1);
    } else {
        print_error('cannottrack', '', get_local_referer(false));
    }

} else { // subscribe
    if (local_forum_tp_start_tracking($forum->id)) {
        $event = \local_forum\event\readtracking_enabled::create($eventparams);
        $event->trigger();
        redirect($returnto, get_string("nowtracking", "local_forum", $info), 1);
    } else {
        print_error('cannottrack', '', get_local_referer(false));
    }
}