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
 * @package   local
 * @subpackage  udemysync
 * @author eabyas  <info@eabyas.in>
**/

defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_udemysync_manageudemylog' => array(
        'classname'   => 'local_udemysync_external',
        'methodname'  => 'manageudemylog',
        'classpath'   => 'local/udemysync/classes/external.php',
        'description' => 'Display the Udemy log Page',
            'type'        => 'write',
        'ajax' => true
    ),
    'local_udemysync_manageudeystatics' => array(
        'classname'   => 'local_udemysync_external',
        'methodname'  => 'manageudeystatics',
        'classpath'   => 'local/udemysync/classes/external.php',
        'description' => 'Display Udemy Statics Page',
        'type'        => 'write',
        'ajax' => true
    )
);
