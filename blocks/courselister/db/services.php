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
/**
 * Course list block caps.
 *
 * @author eabyas  <info@eabyas.in>
 * @package    Bizlms
 * @subpackage block_courselister
 */

defined('MOODLE_INTERNAL') || die;
$functions = array(

     'block_courselister_get_myselectedcourses' => array(
        'classname' => 'block_courselister_external',
        'methodname' => 'get_myselectedcourses',
        'classpath'   => 'blocks/courselister/classes/external.php',
        'description' => 'Get the my enrolled courses set for a user',
        'type' => 'read',
        'ajax' => true,
    ),
    'block_courselister_get_myenrolledcourses' => array(
        'classname' => 'block_courselister_external',
        'methodname' => 'get_myenrolledcourses',
        'classpath'   => 'blocks/courselister/classes/external.php',
        'description' => 'Get the my enrolled courses set for a user',
        'type' => 'read',
        'ajax' => true,
    ),
    'block_courselister_get_myenrolledlearningplans' => array(
        'classname' => 'block_courselister_external',
        'methodname' => 'get_myenrolledlearningplans',
        'classpath'   => 'blocks/courselister/classes/external.php',
        'description' => 'Get the my enrolled learningplans set for a user',
        'type' => 'read',
        'ajax' => true,
    ),
    'block_courselister_get_myalllearningplans' => array(
        'classname' => 'block_courselister_external',
        'methodname' => 'get_myalllearningplans',
        'classpath'   => 'blocks/courselister/classes/external.php',
        'description' => 'Get the all learningplans set for a user',
        'type' => 'read',
        'ajax' => true,
    ),
);
