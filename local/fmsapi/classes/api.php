<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Class for loading/storing oauth2 linked logins from the DB.
 *
 * @package    local_prisemforce
 * @copyright  2023 Shamala Kandula <shamala.kandula@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_fmsapi;

use moodle_exception;
require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

class api {
    protected $clientid;
    protected $clientsecret;
    protected $code;
    protected $authurl;
    public function __construct() {
        $this->clientid = get_config('local_fmsapi', 'client_id');
        $this->clientsecret = get_config('local_fmsapi', 'client_secret');
        $this->authurl = get_config('local_fmsapi', 'fmsauthurl'); 
        $this->code = get_config('local_fmsapi', 'code'); 
    }
    /**
     * Function for getting access token
     */
    public function get_token() {
        $url =  $this->authurl;
        $clientid =   $this->clientid;  
        $clientsecret = $this->clientsecret; 
        $code = $this->code;
        $postfields = "client_id=$clientid&client_secret=$clientsecret&grant_type=authorization_code&code=$code";
       
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => array(
              'Content-Type: application/x-www-form-urlencoded',
              ),
          ));
        $response = curl_exec($curl);
        curl_close($curl);
         
        return json_decode($response);       

    }
    public function api_data($url, $params, $method) {
        $curl = new \curl();
        $result = '';        
        if ($url) {
            $tokenresult = $this->get_token();
            $access_token = $tokenresult->_message->token->accessToken;            
            $options = array(
                'CURLOPT_HTTPHEADER' => array(
                    'content-type:application/json',
                    'Authorization:Bearer '.$access_token
                  ), 
                'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
                'CURLOPT_RETURNTRANSFER' => true,
                'CURLOPT_ENCODING' => '',
                'CURLOPT_MAXREDIRS' => 10,
                'CURLOPT_TIMEOUT' => 0,
                'CURLOPT_FOLLOWLOCATION' => true,
            );          
            if($method == 'post'){
                $result = $curl->post($url, $params, $options);
            }else{
                $result = $curl->get($url, $params, $options);
            }        
            if($curl->get_errno()) {
                throw new moodle_exception('connecterror', 'local_fmsapi', '', array('url' => $url));
            }

        }
        
        return $result;
    }

}
