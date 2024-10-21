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
 * @subpackage block_user_bookmarks_external
 */

defined('MOODLE_INTERNAL') || die;
$functions = array(
    'block_user_bookmarks_getcontent' => array(
        'classname' => 'block_user_bookmarks_external',
        'methodname' => 'get_bookmark_content',
        'classpath'   => 'blocks/user_bookmarks/classes/external.php',
        'description' => 'Get Bookmarks fo users',
        'type' => 'read',
        'ajax' => true,
    ),
);
