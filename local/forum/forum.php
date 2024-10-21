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
 * @subpackage local_forum
 */



require_once("../../config.php");

require_once("lib.php");
require_once('forum_form.php');
require_once($CFG->libdir.'/filelib.php');

$id = optional_param('id',-1, PARAM_INT);
$url = new moodle_url('/local/forum.php');

$context = context_system::instance();
$PAGE->set_url($url);
$PAGE->set_context($context);
require_login();
$PAGE->set_pagelayout('admin');

if ($id > 0) {
    $data = $DB->get_record('local_forum', array('id'=>$id));
} else {
    $data = new stdClass();
}

$params = array('id' => $id);
$mform = new forum_form(null, $params);
if (is_object($data)) {
    $data->introeditor['text'] = $data->intro;
    $default_values = (array)$data;
    $mform->data_preprocessing($default_values);
}

$mform->set_data($default_values);

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/forum/index.php");
} else if ($fromform = $mform->get_data()) {
    if ($fromform->id > 0) {
        redirect("$CFG->wwwroot/local/forum/index.php");
    } else if ($fromform->id < 0) {
        redirect("$CFG->wwwroot/local/forum/index.php");
    } else {
        print_error('invaliddata');
    }
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
