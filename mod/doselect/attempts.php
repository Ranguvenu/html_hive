<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Doselect module version information
 *
 * @package mod_doselect
 * @copyright  2019 Anilkumar Cheguri (anil@eabyas.in)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/doselect/lib.php');
require_once($CFG->dirroot.'/mod/doselect/locallib.php');

global $PAGE, $USER,$DB;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID

	if($id) {
	    if (!$cm = get_coursemodule_from_id('doselect', $id)) {
			print_error('invalidcoursemodule');
	    }
	    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
			print_error('coursemisconf');
	   	}
		$doselect = $DB->get_record('doselect', array('id'=>$cm->instance), '*', MUST_EXIST);
	}


$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/doselect:view', $context);
$doselectid=$cm->instance;
$doselectname = $DB->get_field('doselect','name',array('id'=>$doselectid));
$PAGE->set_url('/mod/doselect/view.php', array('id' => $cm->id));
$PAGE->set_title('Attempt info');

echo $OUTPUT->header();
echo '<div style="float:right";> <a href="/mod/doselect/view.php?id='.$id.'">Back</a></div>';
echo '<h2>'. $doselectname .'</h2>';
doselect_attempts_table($cm->instance);

echo $OUTPUT->footer();
