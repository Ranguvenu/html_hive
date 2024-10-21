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

$f          = required_param('f',PARAM_INT); // The forum to mark
$mark       = required_param('mark',PARAM_ALPHA); // Read or unread?
$d          = optional_param('d',0,PARAM_INT); // Discussion to mark.
$returnpage = optional_param('returnpage', 'index.php', PARAM_FILE);    // Page to return to.

$url = new moodle_url('/local/forum/markposts.php', array('f'=>$f, 'mark'=>$mark));
if ($d !== 0) {
    $url->param('d', $d);
}
if ($returnpage !== 'index.php') {
    $url->param('returnpage', $returnpage);
}
$PAGE->set_url($url);

if (! $forum = $DB->get_record("local_forum", array("id" => $f))) {
    print_error('invalidforumid', 'forum');
}

$user = $USER;

require_login();
require_sesskey();

if ($returnpage == 'index.php') {
    $returnto = new moodle_url("/local/forum/$returnpage", array('id' => $course->id));
} else {
    $returnto = new moodle_url("/local/forum/$returnpage", array('f' => $forum->id));
}

if (isguestuser()) {   // Guests can't change forum
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('noguesttracking', 'forum').'<br /><br />'.get_string('liketologin'), get_login_url(), $returnto);
    echo $OUTPUT->footer();
    exit;
}

$info = new stdClass();
$info->name  = fullname($user);
$info->forum = format_string($forum->name);

if ($mark == 'read') {
    if (!empty($d)) {
        if (! $discussion = $DB->get_record('local_forum_discussions', array('id'=> $d, 'forum'=> $forum->id))) {
            print_error('invaliddiscussionid', 'forum');
        }

        local_forum_tp_mark_discussion_read($user, $d);
    } else {
        $currentgroup=false;
        local_forum_tp_mark_local_forum_read($user, $forum->id, $currentgroup);
    }
}

redirect($returnto);

