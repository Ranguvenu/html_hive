<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This percipiosync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This percipiosync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this percipiosync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * percipiosync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_percipiosync
 */

$tasks = array(
    array(
        'classname' => 'local_percipiosync\task\percipiosync',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '01',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ),
);