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
 * List of the users
 *
 * @package   local
 * @subpackage  users
 * @copyright  2020 Anilkumar.cheguri <anil@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $CFG, $OUTPUT;
require_once($CFG->libdir . '/csvlib.class.php');
$systemcontext = context_system::instance();
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_heading(get_string('pluginname', 'local_users'));
$PAGE->set_url('/local/users/externalservice.php');
$PAGE->navbar->add(get_string('pluginname', 'local_users'));
$PAGE->set_title(get_string('pluginname', 'local_users'));

    echo $OUTPUT->header();
    
    function externalservice_for_getusers(){
        $curl = curl_init();
        
			curl_setopt_array($curl, array(
			// CURLOPT_URL => "https://converge.namely.com/api/v1/reports/0060df54-c368-4d43-9725-84fda9f6257d",
			CURLOPT_URL => "https://converge.namely.com/api/v1/reports/23767fbf-ab21-44bf-8df7-60b889538904",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"oauth_consumer_key\"\r\n\r\noi32nf\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"oauth_token\"\r\n\r\n\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"oauth_signature_method\"\r\n\r\nHMAC-SHA1\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"oauth_timestamp\"\r\n\r\n1500281538\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"oauth_nonce\"\r\n\r\nbqt54T\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"oauth_version\"\r\n\r\n1.0\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"oauth_signature\"\r\n\r\n3smneRQSDJXY8f58bel/rgy041I=\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
			// CURLOPT_HTTPHEADER => array(
			// 	"accept: application/json",
			// 	"authorization: Bearer    017Wgr1rwDd7FVDwaWQgB3hF3Cr1bEpkPfwCvULz0wVTsdHtT4cNPcupHkCmE2Ip",
			// 	"content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
			// 	"id: 0060df54-c368-4d43-9725-84fda9f6257d"
			// ),
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"authorization: Bearer    bp0rhxDqn9oV2v3Ggfsqj0pwR8Ld59LRw1dpsGttp1r1NsGdsB45TxTTpH5Bw9dd",
				"content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
				"id: 23767fbf-ab21-44bf-8df7-60b889538904"
			),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          return $response;
        }
    }
    //O Auth 2.0 for Hive Report
    function access_token(){
    		$accesstokenurl = get_config('local_users', 'usersyncaccesstoken'); 
    		$refresh_token = get_config('local_users', 'refresh_token'); 
    		$grant_type = get_config('local_users', 'grant_type'); 
				$curl = curl_init();
				curl_setopt_array($curl, array(
			  // CURLOPT_URL => 'https://wd2-impl-services1.workday.com/ccx/oauth2/fractal1/token',
					CURLOPT_URL => $accesstokenurl,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS => 'grant_type='.$grant_type.'&refresh_token='.$refresh_token,
			  CURLOPT_HTTPHEADER => array(
			    'Authorization: Basic TlRSa1ptWTFPVEF0WkdJek1pMDBPVFk1TFdJNE9XWXRZVFJrWVdVM01qYzFPREE1OmRyaDBiN25rcnBhbmY3azQ5M3E3d3d0bWZ6ZG1vaGt3NTFzdGRlamduOG8yOTUwazk3Zjg5cGlpdWw0OWRpcnN1enhkYzBjZWM0ejltZG9wZng1emc0NTJtdWRqdzg0cDNqcQ==',
			    'Content-Type: application/x-www-form-urlencoded'
			    
			  ),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			
			if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response;
		}    
	}
    function get_users_fromhrms(){
    	$response = access_token();
    
			$data = json_decode($response);
			$access_token = $data->access_token;

    	$curl = curl_init();
    	$startdate = Date('Y-m-d',strtotime("-1 days"));
    	$enddate= Date('Y-m-d', time());
       
      // $startdate ="2022-10-06";
      // $enddate= "2022-10-07";

    	// $api ='https://wd2-impl-services1.workday.com/ccx/service/customreport2/fractal3/INT%20Hive%20to%20Workday%20User/CR_Workday_to_Hive_Report?Include_Terminated_Workers=1&';
      // CURLOPT_URL => $api."&&Start_Date=2021-01-13-07:00&End_Date=2022-05-30-07:00",

     	$api = get_config('local_users', 'usersyncworkdayapi'); 

     	curl_setopt_array($curl, array(
			  CURLOPT_URL => $api.'&End_Date='.$enddate.'T00:00:00.000-07:00&Start_Date='.$startdate.'T00:00:00.000-07:00',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'GET',
			  CURLOPT_HTTPHEADER => array(
			    'Authorization: Bearer '.$access_token.'',
			  
			  ),
			));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);


		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response;
		}    }

    // $users = externalservice_for_getusers();
    $users = get_users_fromhrms();

    $usersdata = json_decode($users);

	$directory = '/var/sftp-files/';
	if(!file_exists($directory)){
		if (!mkdir($directory, 0777, true)) {
			die('Failed to create folders...');
		}
	}
	
	$csvfile = glob($directory . '*.csv');
	
	$csvexport = new csv_export_writer();
	
	$today = date('dmY', time());
	$csvexport->filename = 'FAEMPDATA_'.$today.'.csv';
	$fileexists = file_exists($csvexport->filename);
	if($fileexists){
		chmod('/var/sftp-files/'.$csvexport->filename, 0777);	
	}	
	$fp = fopen('/var/sftp-files/'.$csvexport->filename, 'w');
	
	// chmod('/var/sftp-files/'.$csvexport->filename, 0777);
	// $fp = fopen('/var/sftp-files/'.$csvexport->filename, 'w');
	
	if (!$fp) {
		die("Unable to open file for output");
	}
	
	$columns = user_fields();
	fputcsv($fp, $columns);
	$allusers = $usersdata->Report_Entry;

	//print_object($allusers);
	if($allusers){
		$csvrow = array();
		foreach ($allusers as $userdata) {
			$csvrow[0]  = $userdata->employee_id;
			$csvrow[1]  = $userdata->first_name;
			$csvrow[2]  = $userdata->last_name;
			$csvrow[3]  = $userdata->middle_name;
			$csvrow[4]  = $userdata->email;
			$csvrow[5]  = $userdata->reports_to_email;
			$csvrow[6]  = '';  // functional reporting to
			$csvrow[7]  = $userdata->OU_Name;  // OU name
			$csvrow[8]  = $userdata->Cost_Center; // costcenter
			$csvrow[9]  = $userdata->Cost_Center;  // departmentname
			$csvrow[10] = '';  // subdepartment
			$csvrow[11] = $userdata->Job_Title;  // designationname
			$csvrow[12] = $userdata->grade;  // gradename
			$csvrow[13] = '';  // position
			
			// 14 - employeestatus
			if($userdata->user_status == 1){
				$csvrow[14] =  'Active';
			}else{
				$csvrow[14] =  'Inactive';
			}
			
			// 15,16 - not capturing this data in LMS
			$csvrow[15] = '';  // resignationstatus
			$csvrow[16] = $userdata->Employee_Type;


			// $csvrow[17] = $userdata->start_date;  // DOJ, should be in dd-mm-YYYY format
			if(!empty($userdata->start_date)){
				$doj = explode('-', $userdata->start_date);
				$csvrow[17] = $doj[0].'-'.$doj[1].'-'.$doj[2];
			}else{
				$csvrow[17] = '';
			}
			// 18 - DOB, should be in dd-mm-YYYY format
			if(!empty($userdata->dateOfBirth)){
				$dob = explode('-', $userdata->dateOfBirth);
				$csvrow[18] = $dob[0].'-'.$dob[1].'-'.$dob[2];
			}else{
				$csvrow[18] = '';
			}
			$csvrow[19] = $userdata->gender;
			$csvrow[20] = $userdata->category_4549;  // location
			$csvrow[21] = '';  // calendar name
			$csvrow[22] = $userdata->career_track;  // career track

			$csvrow[23] = $userdata->office_country_name; 
			$csvrow[24] = $userdata->current_address_2; 
			fputcsv($fp,$csvrow);
		}
	}
		
	fclose($fp);
	
	// COlumns list
	function user_fields(){
		$fields = array('employeeid','firstname','lastname','middlename','emailid','reportingto',
						'functionalreportingto','ouname','costcenter','departmentname','subdepartment',
						'designationname','gradename','position','employeestatus','resignationstatus',
						'employment_type', 'dateofjoining', 'dateofbirth', 'gender', 'location',
						'calendarname', 'careertrack','country','address');
		return $fields;
	}

echo $OUTPUT->footer();
