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
 * @subpackage  percipiosync
 * @author eabyas  <info@eabyas.in>
**/
require_once(dirname(__FILE__) . '/../../config.php');

//namespace local_percipiosync\task;

use local_percipiosync\plugin;
/*

$URL= "https://fractal.percipio.com/api-2.0/organizations/190158/analytics/user-course-activity/?fields%5Baggregate_organization_user_course_activity%5D=@all&course_first_completion_date=2022-08-13";

$username= "cL1dvWzyUizpeISO8TGA8CM1jV0Q9ta9YdeasHxE";
$password= "tQpUnqkT4AQ7eFae5x4NRMGgHlTw9e1DetYJFvE5Y1tGsiQmVLr72gsCkMKSjg7gyNzRecb3TCWJ1i8aH9u0knchgYcvbpaSO8nvUnxe16YH9BgPmbfaka5OWy6TyPY6";

  $ch = curl_init();
  
    curl_setopt($ch, CURLOPT_URL,$URL);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
  
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
  
    $result=curl_exec ($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  
    curl_close ($ch);

    print_object($ch);
echo $response;
*/

$testing=0;
 plugin::crud_percipiosync($testing,$crud);

