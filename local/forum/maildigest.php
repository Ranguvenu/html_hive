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

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/forum/lib.php');

$id = required_param('id', PARAM_INT);
$maildigest = required_param('maildigest', PARAM_INT);
$backtoindex = optional_param('backtoindex', 0, PARAM_INT);

// We must have a valid session key.
require_sesskey();

$forum = $DB->get_record('local_forum', array('id' => $id));
$context = context_system::instance();

require_login();

$url = new moodle_url('/local/forum/maildigest.php', array(
    'id' => $id,
    'maildigest' => $maildigest,
));
$PAGE->set_url($url);
$PAGE->set_context($context);

$digestoptions = local_forum_get_user_digest_options();

$info = new stdClass();
$info->name  = fullname($USER);
$info->forum = format_string($forum->name);
local_forum_set_user_maildigest($forum, $maildigest);
$info->maildigest = $maildigest;

if ($maildigest === -1) {
    // Get the default maildigest options.
    $info->maildigest = $USER->maildigest;
    $info->maildigesttitle = $digestoptions[$info->maildigest];
    $info->maildigestdescription = get_string('emaildigest_' . $info->maildigest,
        'mod_forum', $info);
    $updatemessage = get_string('emaildigestupdated_default', 'forum', $info);
} else {
    $info->maildigesttitle = $digestoptions[$info->maildigest];
    $info->maildigestdescription = get_string('emaildigest_' . $info->maildigest,
        'mod_forum', $info);
    $updatemessage = get_string('emaildigestupdated', 'forum', $info);
}

if ($backtoindex) {
    $returnto = "index.php";
} else {
    $returnto = "view.php?f={$id}";
}

redirect($returnto, $updatemessage, null, \core\output\notification::NOTIFY_SUCCESS);
