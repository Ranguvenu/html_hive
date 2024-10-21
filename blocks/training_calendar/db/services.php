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
 * Web service block plugin template external functions and service definitions.
 *
 * @package    block_training_calendar
 * @copyright  shilpa 2019
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
    
    'block_training_calendar_managecontentpopuptabs' => array(
        'classname' => 'block_training_calendar_external',
        'methodname' => 'managecontentpopuptabs',
        'classpath' => 'blocks/training_calendar/externallib.php',
        'description' => 'Display content in popup tab',
        'ajax' => true,
        'type' => 'read'
    ),

        'block_training_calendar_get_tabinfo_description' => array(
        'classname' => 'block_training_calendar_external',
        'methodname' => 'maduledescription',
        'classpath' => 'blocks/training_calendar/externallib.php',
        'description' => 'Display description tab',
        'ajax' => true,
        'type' => 'read'
    ),


    'block_training_calendar_get_tabinfo_sessions' => array(
        'classname' => 'block_training_calendar_external',
        'methodname' => 'modulesessions',
        'classpath' => 'blocks/training_calendar/externallib.php',
        'description' => 'Display sessions tab',
        'ajax' => true,
        'type' => 'read'
    ),
     'block_training_calendar_get_tabinfo_prerequisites' => array(
        'classname' => 'block_training_calendar_external',
        'methodname' => 'moduleprerequisites',
        'classpath' => 'blocks/training_calendar/externallib.php',
        'description' => 'Display Prerequisites tab',
        'ajax' => true,
        'type' => 'read'
    ),

    'block_training_calendar_get_tabinfo_targetlearners' => array(
        'classname' => 'block_training_calendar_external',
        'methodname' => 'moduletargetlearners',
        'classpath' => 'blocks/training_calendar/externallib.php',
        'description' => 'Display TL tab',
        'ajax' => true,
        'type' => 'read'
    ),


);

