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
 * @package fractal
 * @subpackage local_coursessignment
 */


require_once('../../config.php');
require_login();

$systemcontext = context_system::instance();

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/courses/courses.php');
$PAGE->set_title(get_string('courses'));
$PAGE->set_heading(get_string('sme_courses','local_courseassignment'));
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_courses/courseAjaxform', 'load');
$PAGE->requires->js_call_amd('local_costcenter/fragment', 'init', array());
$PAGE->requires->js_call_amd('local_courses/courses', 'load', array());

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('sme_courses','local_courseassignment'));

$renderer = $PAGE->get_renderer('local_courseassignment');
echo $OUTPUT->header();
/* $extended_menu_links = '';  
$extended_menu_links = '<div class="course_contextmenu_extended">
            <ul class="course_extended_menu_list">';  
$courseassign_url = new moodle_url('/local/courseassignment/assignments.php');   
$extended_menu_links .= '<li>
                            <div class="courseedit course_extended_menu_itemcontainer">
                                <a  href="'. $courseassign_url .'" title="' . get_string('page_title', 'local_courseassignment') . '" class="course_extended_menu_itemlink">
                                    <i class="icon fa fa-check-square-o" aria-hidden="true"></i>
                                </a>
                            </div>
                        </li>';      
$extended_menu_links .= ' </ul> </div>';


echo $extended_menu_links; */

$filterparams['submitid'] = 'form#filteringform';
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);
echo $renderer->get_sme_courses();

echo $OUTPUT->footer();
