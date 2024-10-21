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
 
$id = optional_param('id', 0, PARAM_INT);

global $DB;
require_login();
$systemcontext = context_system::instance();

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/courses/courseexpiry.php');
$PAGE->set_title(get_string('courses'));
$coursedata = $DB->get_record('course',array('id' => $id));
$coursename = $coursedata->fullname;
$PAGE->set_heading(get_string('course_expiry','local_courses',$coursename));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('course_expiry','local_courses',$coursename));
 
echo $OUTPUT->header();
    
    echo get_string('course_expiry_users','local_courses',$coursename); 
    $course_backurl = new moodle_url('/my');
    $course_viewback = '<div class="courseedit course_extended_menu_itemcontainer pull-right">';
    $course_viewback .= '<a class="course_extended_menu_itemlink" href="' . $course_backurl . '">';
    $course_viewback .= '<i class="icon fa fa-reply fa-fw" aria-hidden="true" aria-label="" title ="'.get_string('back_url','local_courses').'"></i>';
    $course_viewback .= '</a>';
    $course_viewback .= '</div>';
    echo $course_viewback;
echo $OUTPUT->footer();
