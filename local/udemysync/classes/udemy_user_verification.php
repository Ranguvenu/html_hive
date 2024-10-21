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

//use local_udemysync;

namespace local_udemysync;

defined('MOODLE_INTERNAL') || die;

class udemy_user_verification {

    public function __construct($useremail) {
        $this->username = "cL1dvWzyUizpeISO8TGA8CM1jV0Q9ta9YdeasHxE";
        $this->password = "tQpUnqkT4AQ7eFae5x4NRMGgHlTw9e1DetYJFvE5Y1tGsiQmVLr72gsCkMKSjg7gyNzRecb3TCWJ1i8aH9u0knchgYcvbpaSO8nvUnxe16YH9BgPmbfaka5OWy6TyPY6";
        $this->authcode = base64_encode($this->username.':'.$this->password);
        $this->hosturl = "https://fractal.udemy.com/api-2.0/organizations/190158";
        $this->searchemail = $useremail;
    }

    function verify_userlicence($pagesize) {

        if(function_exists('curl_init')){
            $curl = curl_init();
        } else {
            $curl = false;
            return $curl;
        }

        curl_setopt_array($curl, array(
           CURLOPT_URL => "$this->hosturl/users/list?page=$pagesize&page_size=100",
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 0,
           CURLOPT_FOLLOWLOCATION => true,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'GET',
           CURLOPT_HTTPHEADER => array("Authorization: Basic $this->authcode"),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response) {
            if (($udmusers = json_decode($response))) {
                $userexists = false;
                foreach ($udmusers->results as $key => $udmuser) {
                    $emailCheck = trim(strtolower($this->searchemail)); 
                    $emailConfirm = trim(strtolower($udmuser->email));
                     
                    if ($emailCheck == $emailConfirm) {
                        return true;
                    }
    	        }
                if (!empty($udmusers->next)) {
                    $pagesize++;
                    $userexists = self::verify_userlicence($pagesize);
                }
                return $userexists;
            }
        } else {
          echo "Not able to connect to API";
        }
    }
}

