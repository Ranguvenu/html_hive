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
 * @package Bizlms 
 * @subpackage local_classroom
 */

require_once(dirname(__FILE__) . '/../../config.php');
use local_classroom\classroom;
global $CFG;

$classroomid = required_param('cid',PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$status = optional_param('status', 0, PARAM_INT);

$sitecontext = context_system::instance();
require_login();
$PAGE->set_url('/local/classroom/view.php', array('cid' => $classroomid));
$PAGE->set_context($sitecontext);
$PAGE->set_title(get_string('classrooms', 'local_classroom'));

$renderer = $PAGE->get_renderer('local_classroom');

$classroom=$renderer->classroomview_check($classroomid);

$PAGE->navbar->add(get_string("pluginname", 'local_classroom'), new moodle_url('index.php'));
$PAGE->navbar->add($classroom->name);
$PAGE->set_heading($classroom->name);

$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js_call_amd('local_classroom/ajaxforms', 'load');
$PAGE->requires->js_call_amd('local_classroom/classroom', 'coursesData',array('classroomid' => $classroomid,'contextid'=>$sitecontext->id));
$PAGE->requires->js_call_amd('local_classroom/classroomunenrol', 'load');
$PAGE->requires->js_call_amd('local_evaluation/newevaluation','load');
// $PAGE->requires->event_handler('#usernotcompleted_prereq', 'click', 'M.util.show_confirm_dialog', array('message' => get_string('usernotcompleted_prereq', 'local_catalog'), 'callbacks' => array()));
if ($action === 'classroomstatus') {
    $return = (new classroom)->classroom_status_action($classroomid, $status);
    if ($return) {
        redirect($PAGE->url);
    }
}
elseif ($action === 'selfenrol') {

    $return = (new classroom)->classroom_self_enrolment($classroomid,$USER->id, $selfenrol=1,'self');
    if ($return) {
        redirect($PAGE->url);
    }
}

$content = $renderer->get_content_viewclassroom($classroomid);
echo $OUTPUT->header();
	echo $content;
echo $OUTPUT->footer();
