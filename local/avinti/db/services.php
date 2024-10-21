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
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_avinti
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
$functions = array(
	'local_avinti_courses' => array(
		'classname' => 'local_avinti_external',
		'methodname' => 'get_courses',
		'classpath' => 'local/avinti/classes/external.php',
		'description' => 'Get Courses',
		'type' => 'read',
		'ajax' => true,
	),
	'local_avinti_course_enrolment' => array(
		'classname' => 'local_avinti_external',
		'methodname' => 'course_enrolment',
		'classpath' => 'local/avinti/classes/external.php',
		'description' => 'enrolment to course',
		'type' => 'read',
		'ajax' => true,
	),
	'local_avinti_custom_course_complete' => array(
		'classname' => 'local_avinti_external',
		'methodname' => 'custom_course_complete',
		'classpath' => 'local/avinti/classes/external.php',
		'description' => 'user unenroll to the course',
		'type' => 'read',
		'ajax' => true,
	),
	
);

$services = array(   
	'courseslist_service' => array(
        'functions' =>array(
            'local_avinti_courses'
        ),
        'enabled' => 1,
		'shortname' => 'get_courseslist'
    ),
	'courseenrolment_service' => array(
        'functions' =>array(
            'local_avinti_course_enrolment'
        ),
        'enabled' => 1,
		'shortname' => 'courseenrolment'
    ),	
	'coursecomplete_service' => array(
        'functions' =>array(
            'local_avinti_custom_course_complete'
        ),
        'enabled' => 1,
		'shortname' => 'coursecompletion'
    ),
	
);
