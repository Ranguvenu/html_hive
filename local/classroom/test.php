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
 * @package Bizlms 
 * @subpackage local_classroom
 */

echo 'hello';
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $DB;
$main = new \local_o365\feature\calsync\main();
//$token = $main->get_user_token(1488);
$muserid = get_config('local_classroom', 'outlook_event_credential');
$apiclient = $main->construct_calendar_api($muserid,true);
 
$attendees = $DB->get_records('user',['id' => 5035]);
$response = $apiclient->create_event('subject','body',time()+86400, time()+90000, $attendees,[],null);
print_object($response);
