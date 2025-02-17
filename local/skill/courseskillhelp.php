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
 * @subpackage local_
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;
$systemcontext =  context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/skill/help.php');
$PAGE->set_heading(get_string('manual', 'local_skill'));
$strheading = get_string('pluginname', 'local_skill') . ' : ' . get_string('manual', 'local_skill');
$PAGE->set_title($strheading);

if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
echo $OUTPUT->header();

if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo $OUTPUT->box(get_string('helpmanual', 'local_skill'));
    echo '<div style="float:right;"><a href="courseskilluploadform.php"><button>' . get_string('back_upload', 'local_skill') . '</button></a></div>';
}

echo get_string('help_2', 'local_skill', array('countries' => $countries, 'timezones' => $timezones));
echo $OUTPUT->footer();
?>
