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


$curl = curl_init();

$username= "cL1dvWzyUizpeISO8TGA8CM1jV0Q9ta9YdeasHxE";
$password= "tQpUnqkT4AQ7eFae5x4NRMGgHlTw9e1DetYJFvE5Y1tGsiQmVLr72gsCkMKSjg7gyNzRecb3TCWJ1i8aH9u0knchgYcvbpaSO8nvUnxe16YH9BgPmbfaka5OWy6TyPY6";
$authcode= base64_encode($username.':'.$password);
$hosturl= "https://fractal.percipio.com/api-2.0/organizations/190158";
curl_setopt_array($curl, array(
  CURLOPT_URL => "$hosturl/courses/list/?fields%5Bcourse%5D=@all",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
   "Authorization: Basic $authcode"
  ),
));

$response = curl_exec($curl);

curl_close($curl);
print_object($response);
/*
 $c = new \curl(array('cache'=>true));

            $requestparams = array(
                'clientId' => $settings['clientid'],
            );

            if ($settings['catlanguage'] !== 'en-us') {
                $requestparams['locale'] = $settings['catlanguage'];
            }

            $options = [
            'CURLOPT_HEADER' =>Authorization: Basic $authcode,
            ];
$response = $c->get($settings['apiurl'], $requestparams,$options);
*/
