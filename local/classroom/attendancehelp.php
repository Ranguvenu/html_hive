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
 * @subpackage local_classroom
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/classroom/attendancehelp.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('pluginname', 'local_classroom') . ' : ' . get_string('help_manual', 'local_classroom');
$PAGE->set_title($strheading);

if(!(has_capability('local/classroom:createsession', $systemcontext) && has_capability('local/classroom:managesession',$systemcontext))){
    echo print_error('no permission');
}
if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_classroom'), new moodle_url('/local/classroom/index.php'));
$PAGE->navbar->add(get_string('bulk_session_attendance','local_classroom'), new moodle_url('/local/classroom/uploadattendance.php'));
$PAGE->navbar->add(get_string('help_manual', 'local_classroom'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('help_manual', 'local_classroom'));
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo $OUTPUT->box(get_string('helpmanual', 'local_classroom'));
    echo '<div style="float:right;"><a href="upload_attendance.php"><button>' . get_string('back_upload', 'local_classroom') . '</button></a></div>';
}

if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext) || has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
  echo get_string('help_2', 'local_classroom');
}
echo $OUTPUT->footer();
?>
