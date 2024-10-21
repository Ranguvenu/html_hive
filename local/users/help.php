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
 * @subpackage local_users
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/users/help.php');
$PAGE->set_pagelayout('admin');
$strheading = get_string('pluginname', 'local_users') . ' : ' . get_string('manual', 'local_users');
$PAGE->set_title($strheading);
if(!(has_capability('local/users:manage', $systemcontext) && has_capability('local/users:create', $systemcontext))){
	echo print_error('no permission');
}
if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_users'), new moodle_url('/local/users/index.php'));
$PAGE->navbar->add(get_string('uploadusers', 'local_users'), new moodle_url('/local/users/sync/hrms_async.php'));
$PAGE->navbar->add(get_string('manual', 'local_users'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manual', 'local_users'));
if (isset($CFG->allowframembedding) and ! $CFG->allowframembedding) {
    echo $OUTPUT->box(get_string('helpmanual', 'local_users'));
    echo '<div style="float:right;"><a href="sync/hrms_async.php"><button>' . get_string('back_upload', 'local_users') . '</button></a></div>';
}
if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
	echo get_string('help_1', 'local_users');
}else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
	echo get_string('help_1_orghead', 'local_users');
}else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
	echo get_string('help_1_dephead', 'local_users');
	
}
echo get_string('help_2', 'local_users');

echo $OUTPUT->footer();
?>
