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
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_fmsapi
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * service_hit_task
 */
namespace local_fmsapi\task;

use local_fmsapi\api;
use stdClass;
require_once($CFG->dirroot . '/local/fmsapi/lib.php');

class fmsservice_hit_task extends \core\task\scheduled_task{
	public function get_name() {
		return get_string('fmsservicehit','local_fmsapi');
	}
	public function execute() {
		global $DB;
		$api = new api();
		$fmslogs = $DB->get_records('local_fmsapi_logs',['status' => 2]);
		
        foreach ($fmslogs as $log) {
			if ($log->status == 2) {

                $url = get_config('local_fmsapi', 'fmsapiurl');
                $response = $api->api_data($url, $log->params, 'post');       
                $jsonresp = json_decode($response);
                $decodeparams = json_decode($log->params);
                $user = $DB->get_record('user',['open_employeeid' => strtolower($decodeparams->employeeCode)]);
                //Saving the response in the custom logs
                if ($jsonresp->_message) {
                    $status = 0;
                    if (strtolower($jsonresp->_message) != 'success') {
                        $status = 2;
                        send_email_fms($decodeparams, $user);
                    } else {
                        $status = 1;
                    }                   
                    custom_fmsapi_log_saving($log->eventname, $log->transactionid, $log->elementid, $log->params, $response,$status);
                }        
				$obj = new stdClass();
				$obj->id = $log->id;
				$obj->status = 1;
				$DB->update_record('local_fmsapi_logs', $obj);

			}           

        }
		
	}

}