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
 * @package    block_empcredits
 * @copyright  shilpa 2019
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
    
    'block_empcredits_learninganalytics_year' => array(
        'classname' => 'block_empcredits_external',
        'methodname' => 'learninganalyticsyear',
        'classpath' => 'blocks/empcredits/externallib.php',
        'description' => 'Display content in popup tab',
        'ajax' => true,
        'type' => 'read'
    ),

        'block_empcredits_learninganalytics_allccdata' => array(
        'classname' => 'block_empcredits_external',
        'methodname' => 'learninganalyticsallccdata',
        'classpath' => 'blocks/empcredits/externallib.php',
        'description' => 'Display description tab',
        'ajax' => true,
        'type' => 'read'
    ),


    'block_empcredits_learninganalytics_certdata' => array(
        'classname' => 'block_empcredits_external',
        'methodname' => 'learninganalyticscertdata',
        'classpath' => 'blocks/empcredits/externallib.php',
        'description' => 'Display sessions tab',
        'ajax' => true,
        'type' => 'read'
    ),
     

);

