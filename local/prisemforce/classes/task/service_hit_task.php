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
 * @package     local_prisemforce
 * @copyright   2024 Moodle India Information Solutions Pvt Ltd
 * @author      2024 Shamala <shamala.kandula@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * service_hit_task
 */
namespace local_prisemforce\task;

use local_prisemforce\api;
use stdClass;
require_once($CFG->dirroot . '/local/prisemforce/lib.php');

class service_hit_task extends \core\task\scheduled_task{
	public function get_name() {
		return get_string('servicehit','local_prisemforce');
	}
	public function execute() {
		global $DB;
		$api = new api();
		$prisemlogs = $DB->get_records('local_prisemforce_logs',['status' => 2]);
		
        foreach ($prisemlogs as $log) {
			if ($log->status == 2) {
				$apikey = '';
				if ($log->typeapikey == 1) {
					$apikey = get_config('local_prisemforce', 'masterxapikey');
				} else {
					$apikey = get_config('local_prisemforce', 'userxapikey');
				}
				$response = $api->api_data($apikey, $log->params, 'post');
				$jsonresp = json_decode($response);
				$status = 2;
				if ($jsonresp->success) {
					$status = 1;
				}				
				//Saving the response in the custom logs
				custom_log_saving($log->eventname, $log->elementid, $jsonresp->pfTransactionId, $log->params, $response, $status, $log->typeapikey);
                $obj = new stdClass();
				$obj->id = $log->id;
				$obj->status = 1;
				$DB->update_record('local_prisemforce_logs', $obj);

			}           

        }
		
	}

}