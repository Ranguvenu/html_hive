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
require_once($CFG->dirroot . '/local/courses/filters_form.php');
// require_once($CFG->dirroot . '/local/courses/classes/form/level_form.php');

$id        = optional_param('id', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$jsonparam    = optional_param('jsonparam', '', PARAM_RAW);

$systemcontext = context_system::instance();
if (!has_capability('local/courses:view', $systemcontext) && !has_capability('local/courses:manage', $systemcontext)) {
    print_error("You don't have permissions to view this page.");
}
$PAGE->set_pagelayout('admin');

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/courses/levels.php');
$PAGE->set_title(get_string('courses'));
$PAGE->set_heading(get_string('heading2', 'local_courses'));
$PAGE->requires->js_call_amd('local_courses/createCourseProviders', 'Datatable', array());
$PAGE->requires->js_call_amd('local_courses/levels','load',array());
$PAGE->navbar->add(get_string('manage_courses','local_courses'), new moodle_url('/local/courses/courses.php'));
$PAGE->navbar->add(get_string('heading2', 'local_courses'));


// $renderer = $PAGE->get_renderer('local_courses');


echo $OUTPUT->header();
echo $extended_menu_links;

$result = $DB->get_records('local_levels');

foreach ($result as $key) {
    $key->status        = $key->active == 1 ? true : false;
    if($key->active == 1){
        $key->active = 'Active';
    }
    else {
        $key->active = 'Inactive';
    }
}


// echo '<pre>';
// print_r($result);die;

$data = (object)[
    'result'    => array_values($result),
    'status'    => $status
];

echo $OUTPUT->render_from_template('local_courses/levels_table', $data);
echo $OUTPUT->footer();
