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
namespace local_prisemforce;

use moodle_exception;
require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

class api {
    protected $clientid;
    protected $clientsecret;
    protected $prisemtoken;
    protected $prisemurl;
    protected $authurl;
    protected $xapikey;
    public function __construct() {
        $this->clientid = get_config('local_prisemforce', 'client_id');
        $this->clientsecret = get_config('local_prisemforce', 'client_secret');
        $this->prisemtoken = get_config('local_prisemforce', 'accesstoken');
        $this->prisemurl = get_config('local_prisemforce', 'prisemurl');
        $this->authurl = get_config('local_prisemforce', 'authurl');        
    }
    /**
     * Function for getting access token
     */
    public function get_jwt_token() {
        $url =  $this->authurl;//$this->prisemurl.'/v1/api/oauth2/token?grant_type=client_credentials&scope=integration-microservice-resource-identifier%2Fwrite';
        $params = "";
        $curl = new \curl();
        $options = array(
            'CURLOPT_HTTPHEADER' => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic '.$this->prisemtoken,
              ),            
            'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_ENCODING' => '',
            'CURLOPT_MAXREDIRS' => 10,
            'CURLOPT_TIMEOUT' => 0,
            'CURLOPT_FOLLOWLOCATION' => true,
        );        
        $result = $curl->post($url, $params, $options);
        if($curl->get_errno()) {
            throw new moodle_exception('connecterror', 'local_prisemforce', '', array('url' => $url));
        }
        return json_decode($result);
    }
    public function api_data($apikey, $params, $method) {
        $curl = new \curl();
        $result = '';
        if ($apikey) {
            $url = $this->prisemurl.'/v1/api/integration';               
            $jwtresult = $this->get_jwt_token();        
            $access_token = $jwtresult->access_token;
            $options = array(
                'CURLOPT_HTTPHEADER' => array(
                    'x-api-key: '.$apikey,
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
                throw new moodle_exception('connecterror', 'local_prisemforce', '', array('url' => $url));
            }
        }
       
        return $result;
    }

    // public function api_data($url, $params, $method) {

    //     $curl = new \curl();
    //     $url = $this->prisemurl.''.$url;
    //     $options = array(
    //         'returntransfer' => true,
    //         'timeout' => 0,
    //         'CURLOPT_HTTPHEADER' => array(
    //             'Content-Type: application/json'
    //         ),           
    //         'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
    //         'CURLOPT_USERPWD' => $this->clientid.':'.$this->accesstoken,
    //         'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
    //     );
    //     if($method == 'post'){
    //         $result = $curl->post($url, $params, $options);
    //     }else{
    //         $result = $curl->get($url, $params, $options);
    //     }
    //     if($curl->get_errno()) {
    //         throw new moodle_exception('connecterror', 'local_jira', '', array('url' => $url));
    //     }        
    //     return json_decode($result);
    // }

}
