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
 * local courses
 *
 * @package    local_courses
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courses\action;
defined('MOODLE_INTERNAL') or die;
use enrol_get_plugin;
use stdClass;

class postcourse{

    private function getHeaders($respHeaders) {
          $headers = array();
          $headerText = substr($respHeaders, 0, strpos($respHeaders, "\r\n\r\n"));
          foreach (explode("\r\n", $headerText) as $i => $line) {
              if ($i === 0) {
                  $headers['http_code'] = $line;
              } else {
                  list ($key, $value) = explode(': ', $line);
                  $headers[$key] = $value;
              }
          }
          return $headers;
   }

 /*
     * @method local_postcourse
     * @param $courseinfo 
     * @output data will be insert into mdl_local_logs table
     */    
function local_postcourse($courseid){
        global $DB, $USER, $CFG;
          $course=$DB->get_record('course',array('id'=>$courseid));
          $id = $course->id;
          $coursetitle = $course->shortname;
          $description = $course->summary;
          $categoryname= $DB->get_field('course_categories','name',array('id'=>$course->category));

          $category = $categoryname;

      

            $courseapi=get_config('local_users', 'courseapi');
           
        
            // $requinfo=new stdClass();

            // $courseval= new stdClass();
           
            // $courseval->ID= $courseid;
            // $courseval->Inactive=$coursestatusid;
            // $courseval->Course_Title=$coursename;
            // $courseval->Topic_Reference=array('ID'=>$categoryid);
            // $courseval->Description=$description;
            // $authinfo= $this->user_authentication();
            // $params= json_encode($courseval);

                    
            // $curl = curl_init();
            // curl_setopt_array($curl, array(
            //   CURLOPT_URL => $host,
            //   CURLOPT_RETURNTRANSFER => true,
            //   CURLOPT_ENCODING => "",
            //   CURLOPT_MAXREDIRS => 10,
            //   CURLOPT_TIMEOUT => 30,
            //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //   CURLOPT_SSL_VERIFYPEER => false,
            //   CURLOPT_CUSTOMREQUEST => "POST",
            //   CURLOPT_POSTFIELDS => $params,
            //   CURLOPT_HTTPHEADER => array(
            //     "accept-language: en",
            //     "applicationtype: 5",
            //     "cache-control: no-cache",
            //     "consumerid:".$clientid,
            //     "Authorization:".$authinfo,
            //     "content-type: application/json"
            //   ),
            // ));


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://wd2-impl-services1.workday.com/ccx/service/fractal2/Learning/v38.0',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/" 
xmlns:xsd="http://www.w3.org/2001/XMLSchema"
xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
xmlns:wd="urn:com.workday/bsvc" wd:version="v38.0">
    <env:Header>
        <wsse:Security env:mustUnderstand="1">
            <wsse:UsernameToken>
                <wsse:Username>INT Hive to Workday User@fractal2</wsse:Username>
                <wsse:Password
Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">Workday@31</wsse:Password>
            </wsse:UsernameToken>
        </wsse:Security>
    </env:Header>
    <env:Body>
        <wd:Manage_Learning_Course_Request>
            <wd:Manage_Learning_Course_Data>
                <wd:Put_Learning_Digital_Course_Request>
                    <wd:Learning_Digital_Course_Data>
                        <!-- Optional:  -->
                        <wd:ID>$id</wd:ID>
                        <wd:Inactive>false</wd:Inactive>
                        <wd:Course_Title>$coursetitle</wd:Course_Title>
                        <wd:Topic_Reference>
                            <wd:ID wd:type="Learning_Topic">$category</wd:ID>
                        </wd:Topic_Reference>
                        <wd:Description>$description</wd:Description>
                        <wd:Learning_Other_Unit_Type_Data>
                            <wd:Other_Unit_Type_Reference>
                                <wd:ID wd:type="Learning_Other_Unit_Type">Skillsoft Percipio</wd:ID>
                            </wd:Other_Unit_Type_Reference>
                            <wd:Other_Unit_Value>1</wd:Other_Unit_Value>
                        </wd:Learning_Other_Unit_Type_Data>
                        <wd:Learning_Other_Unit_Type_Data>
                            <wd:Other_Unit_Type_Reference>
                                <wd:ID wd:type="Learning_Other_Unit_Type">Learning Credit</wd:ID>
                            </wd:Other_Unit_Type_Reference>
                            <wd:Other_Unit_Value>1</wd:Other_Unit_Value>
                        </wd:Learning_Other_Unit_Type_Data>
                        <wd:Registrable_Status_Reference>
                            <wd:ID wd:type="Learning_Registerable_Status_ID">Open</wd:ID>
                        </wd:Registrable_Status_Reference>
                    </wd:Learning_Digital_Course_Data>
                </wd:Put_Learning_Digital_Course_Request>
            </wd:Manage_Learning_Course_Data>
        </wd:Manage_Learning_Course_Request>
    </env:Body>
</env:Envelope>',
  CURLOPT_HTTPHEADER => array(
    'Authorization: Basic SU5UIEhpdmUgdG8gV29ya2RheSBVc2VyQGZyYWN0YWwyOldvcmtkYXlAMzE=',
    'Content-Type: application/xml',
    'Cookie: TS012df9cf=01da122c49138f2907685b03a5b91e64bd3b252654b7d29256842b08d0347cb806b6508bef381237be0e9f7064e8a8dcb5bc65b015'
  ),
));
  $response = curl_exec($curl);
            $err = curl_error($curl);
           
           if ($err) {
              // echo "cURL Error #:" . $err;

             $cologs = new stdClass();
                $cologs->courseid=$courseid;
                $cologs->message= $err;
                $cologs->response= $response;
                $cologs->timecreated=time();
               $DB->insert_record('local_courselogs', $cologs, false);
         
            } else {
                                     
                $cologs = new stdClass();
                $cologs->courseid=$courseid;
                // $cologs->batchname  = $status;
                $cologs->message= $params;
                $cologs->response= $response;
                // $cologs->userid= $userid;
                // $cologs->coursecode= $coursekey;
                // $cologs->event= $logevent;
                $cologs->timecreated=time();
         
               $DB->insert_record('local_courselogs', $cologs, false);
            }
     
   }

 /*End of service to send grades */


}
