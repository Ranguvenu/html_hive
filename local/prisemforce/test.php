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


echo $OUTPUT->header();

use local_prisemforce\api;
$api = new api();
$res = $api->get_jwt_token();
print_r($res);


echo $OUTPUT->footer();


