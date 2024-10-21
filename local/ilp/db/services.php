<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    local evalaution
 * @copyright  sreenivas 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
        'local_ilp_submit_ilp_form' => array(
                'classname'   => 'local_ilp_external',
                'methodname'  => 'submit_ilp',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_ilp_deleteplan' => array(
                'classname' => 'local_ilp_external',
                'methodname' => 'delete_ilp',
                'description' => 'deletion of ilps',
                'ajax' => true,
                'type' => 'write'
    	),
        'local_ilp_toggleplan' => array(
                'classname' => 'local_ilp_external',
                'methodname' => 'toggle_ilp',
                'description' => 'altering status of ilps',
                'ajax' => true,
                'type' => 'write'
        ),
        'local_ilp_lpcourse_enrol_form' => array(
                'classname' => 'local_ilp_external',
                'methodname' => 'lpcourse_enrol_form',
                'description' => 'enrol courses to ilps',
                'ajax' => true,
                'type' => 'write'
        ),
        'local_ilp_unassign_course' => array(
                'classname' => 'local_ilp_external',
                'methodname' => 'lpcourse_unassign_course',
                'description' => 'unasssign courses from ilps',
                'ajax' => true,
                'type' => 'write'
        ),
        'local_ilp_unassign_user' => array(
                'classname' => 'local_ilp_external',
                'methodname' => 'lpcourse_unassign_user',
                'description' => 'unasssign users from ilps',
                'ajax' => true,
                'type' => 'write'
        )
);

