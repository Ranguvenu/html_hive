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
 * @package    local_courses
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_courses_submit_create_course_form' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'submit_create_course_form',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Submit form',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_submit_create_category_form' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'submit_create_category_form',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Submit form',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_submit_delete_category_form' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'submit_delete_category_form',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Submit form',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_course_update_status' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'course_update_status',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Update status',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_departmentlist' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'departmentlist',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Department List',
        'type'        => 'read',
        'ajax' => true,
    ),
    'local_courses_deletecourse' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'delete_course',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'deletion of courses',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_courses_form_option_selector' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'global_filters_form_option_selector',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'All global filters forms event handling',
        'ajax' => true,
        'type' => 'read',
    ), 
    'local_courses_courses_view' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'courses_view',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'List all courses in card view',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_courses_categories_view' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'categories_view',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'List all categories in card view',
        'ajax' => true,
        'type' => 'read',
    ),  
	'local_courses_get_users_course_status_information' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'get_users_course_status_information',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'get completed courses list',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),      
    'local_courses_get_recently_enrolled_courses' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'get_recently_enrolled_courses',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'get recently enrolled courses list',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_courses_userdashboard_content' => array(
        'classname'    => 'local_courses_external',
        'methodname'   => 'data_for_courses',
        'classpath'    => 'local/courses/classes/external.php',
        'description'  => 'Load the data for the elearning courses in Userdashboard.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),
    'local_courses_userdashboard_content_paginated' => array(
        'classname'    => 'local_courses_external',
        'methodname'   => 'data_for_courses_paginated',
        'classpath'    => 'local/courses/classes/external.php',
        'description'  => 'Load the data for the elearning courses in Userdashboard.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),
    'local_courses_submit_featured_course_form' => array(
        'classname'    => 'local_courses_external',
        'methodname'   => 'submit_featured_courses_form',
        'classpath'    => 'local/courses/classes/external.php',
        'description' => 'Submit form',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_featured_courses_selector' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'featured_courses_selector',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'All global filters forms event handling',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_courses_unenrol_course'=> array(
        'classname' => 'local_courses_external',
        'methodname' => 'unenrol_course',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'Unenrol course with reason',
        'ajax' => true,
        'type' => 'write',
    ),
    'local_courses_submit_course_type_form' => array(
        'classname'    => 'local_courses_external',
        'methodname'   => 'submit_course_type_form',
        'classpath'    => 'local/courses/classes/external.php',
        'description' => 'Submit course type form',
        'type'        => 'write',
        'ajax' => true,
    ),
  
    'local_courses_delete_coursetype' => array(
        'classname'    => 'local_courses_external',
        'methodname'   => 'delete_coursetype',
        'classpath'    => 'local/courses/classes/external.php',
        'description' => 'deletion of course type',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_coursetype_update_status' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'coursetype_update_status',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Update Course type status',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_submit_course_provider_form' => array(
        'classname'    => 'local_courses_external',
        'methodname'   => 'submit_course_provider_form',
        'classpath'    => 'local/courses/classes/external.php',
        'description' => 'Submit course provider form',
        'type'        => 'write',
        'ajax' => true,
    ),
  
    'local_courses_delete_courseprovider' => array(
        'classname'    => 'local_courses_external',
        'methodname'   => 'delete_courseprovider',
        'classpath'    => 'local/courses/classes/external.php',
        'description' => 'deletion of course provider',
        'type'        => 'write',
        'ajax' => true,
    ),
    'local_courses_courseprovider_update_status' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'courseprovider_update_status',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Update Course provider status',
        'type'        => 'write',
        'ajax' => true,
    ),

    'local_courses_submit_level_form' => array( 
        'classname'   => 'local_courses_external', 
        'classpath'   => 'local/courses/classes/external.php',
        'methodname'  => 'submit_level', 
        'description' => 'This documentation will be displayed in the generated API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write', 
        'ajax'        => true, 
    ),

    'local_courses_delete_level' => array( 
        'classname'   => 'local_courses_external', 
        'classpath'   => 'local/courses/classes/external.php',
        'methodname'  => 'delete_level', 
        'description' => 'This documentation will be displayed in the generated API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write',
        'ajax'        => true,

    ),

    'local_courses_change_level_status' => array(
        'classname'   => 'local_courses_external',
        'classpath'   => 'local/courses/classes/external.php',
        'methodname'  => 'change_level_status', 
        'description' => 'This documentation will be displayed in the generated API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write', 
        'ajax'        => true, 

    ),
    'local_courses_getallcourses' => array(
		'classname' => 'local_courses_external',
		'methodname' => 'getallcourses',
		'classpath' => 'local/courses/classes/external.php',
		'description' => 'Get all courses',
		'type' => 'read',
		'ajax' => true,
	),
     'local_courses_reissue_certificate' => array(
        'classname'   => 'local_courses_external',
        'methodname'  => 'reissue_course_certificate',
        'classpath'   => 'local/courses/classes/external.php',
        'description' => 'Submit form',
        'type'        => 'write',
        'ajax' => true,
    )
);

$services = array(   
	'get_allcourses_service' => array(
        'functions' =>array(
            'local_courses_getallcourses'
        ),
        'enabled' => 1,
		'shortname' => 'getallcourses'
    ) 
);

