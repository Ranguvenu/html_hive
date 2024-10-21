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
 * @subpackage local_fmsapi
 */

require_once(__DIR__ . '/../../config.php');
require_login();
global $DB, $PAGE;

$PAGE->set_url(new moodle_url('/local/coursesearch/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_heading(get_string('course_search','local_fmsapi'));
$PAGE->set_title(get_string('result','local_fmsapi'));
$PAGE->navbar->add(get_string('pluginname','local_fmsapi'));

$PAGE->requires->js_call_amd('local_fmsapi/fmsapi', 'Datatable', array());

$sql = "SELECT f.id as id,u.id,CONCAT_WS(' ', u.firstname, u.lastname) AS name,u.open_employeeid,f.id,f.skillkeyword,f.coursename,f.employee_id,f.employee_name,
                f.employee_email,f.message,date(from_unixtime(  f.requested_date )) as requested_date 
                FROM {user} u
                JOIN {local_fmsapi_course_search} f  ON u.open_employeeid = f.employee_id
                ORDER BY f.id desc";
$result = $DB->get_records_sql($sql,$params=null, $limitfrom=0, $limitnum=0);

/* foreach ($result as $key) {
  // code...
  $key->requested_date  = date('d M Y ', $key->requested_date);
  $key->name  = ucfirst($key->firstname).' '.ucfirst($key->lastname);
} */

$statussql = "SELECT distinct(message) FROM {local_fmsapi_course_search}";
$status = $DB->get_fieldset_sql($statussql,array());

echo $OUTPUT->header();

$data = (object)[
  'downloadurl' => new moodle_url('/local/fmsapi/export.php'),
  'result' => array_values($result),
  'status' => $status,
 ];

echo $OUTPUT->render_from_template('local_fmsapi/index', $data);

echo $OUTPUT->footer();