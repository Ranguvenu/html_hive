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
 * @subpackage local_courses
 */

require_once('../../config.php');

global $DB, $PAGE;

$courseid = required_param('id', PARAM_INT);

require_login();

$systemcontext = context_system::instance();

$PAGE->set_pagelayout('admin');

$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_courses/courses', 'usersdatatable', array(array('courseid' => $courseid,
																			'action'=>'enrolledusers')));

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/courses/enrolledusers.php');
$PAGE->set_title(get_string('courses'));
$PAGE->set_heading(get_string('enrolledusers','local_courses'));
$PAGE->navbar->ignore_active();

$renderer = $PAGE->get_renderer('local_courses');

echo $OUTPUT->header();

echo $renderer->display_course_enrolledusers($courseid);

echo $OUTPUT->footer();
