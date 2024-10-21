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
 * local courses
 *
 * @package    local_courseassignment
 * @copyright  eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_courseassignment_submit_graderaction' => array(
        'classname'   => 'local_courseassignment_external',
        'methodname'  => 'submit_graderaction',
        'classpath'   => 'local/courseassignment/classes/external.php',
        'description' => 'Submit form',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courseassignment_approve_graderaction' => array(
        'classname'   => 'local_courseassignment_external',
        'methodname'  => 'approve_graderaction',
        'classpath'   => 'local/courseassignment/classes/external.php',
        'description' => 'Approve',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_assignments' => array(
        'classname' => 'local_courseassignment_external',
        'methodname' => 'courses_assignments_view',
        'classpath' => 'local/courseassignment/classes/external.php',
        'description' => 'List all courses assignmnets in table view',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_smecourses_view' => array(
        'classname' => 'local_courseassignment_external',
        'methodname' => 'sme_courses_view',
        'classpath' => 'local/courseassignment/classes/external.php',
        'description' => 'List all sme courses in card view',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_courses_assignmentslist' => array(
        'classname' => 'local_courseassignment_external',
        'methodname' => 'courses_assignments_listview',
        'classpath' => 'local/courseassignment/classes/external.php',
        'description' => 'List all courses assignmnets in table view',
        'ajax' => true,
        'type' => 'read',
    ),
);