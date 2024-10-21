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
 * @subpackage local_onlinetest
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_onlinetests_submit_create_onlinetest_form' => array(
            'classname'   => 'local_onlinetests_external',
            'methodname'  => 'submit_create_onlinetest_form',
            'classpath'   => 'local/onlinetests/externallib.php',
            'description' => 'Submit form',
            'type'        => 'write',
            'ajax' => true,
    ),
    'local_onlinetests_tests_view' => array(
            'classname'   => 'local_onlinetests_external',
            'methodname'  => 'tests_view',
            'classpath'   => 'local/onlinetests/externallib.php',
            'description' => 'list of online exams',
            'type'        => 'read',
            'ajax' => true,
    ),
	'local_onlinetests_get_onlinetests' => array(
        'classname' => 'local_onlinetests_external',
        'methodname' => 'get_onlinetests',
        'classpath' => 'local/onlinetests/externallib.php',
        'description' => 'Get onlinetests contents',
        'type' => 'read',
        'capabilities' => 'moodle/course:update, moodle/course:viewhiddencourses',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_onlinetests_userdashboard_content'=> array(
        'classname'   => 'local_onlinetests_external',
        'methodname'  => 'data_for_onlinetests',
        'classpath'   => 'local/onlinetests/externallib.php',
        'description' => 'Get user Tests for dashboard',
        'type'        => 'read',
        'ajax' => true,
    ),
    'local_onlinetests_userdashboard_content_paginated'=> array(
        'classname'   => 'local_onlinetests_external',
        'methodname'  => 'data_for_onlinetests_paginated',
        'classpath'   => 'local/onlinetests/externallib.php',
        'description' => 'Get user Tests for dashboard',
        'type'        => 'read',
        'ajax' => true,
    )
);

