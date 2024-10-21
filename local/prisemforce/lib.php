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
 * @package    local_fmsapi
 * @copyright  2024 Shamala Kandula <shamala.kandula@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function custom_log_saving($eventname, $elementid, $transactionid, $params, $response, $status=0, $typeapikey=0, $userid = 0){
    global $DB, $USER;

    $object = new stdClass();
    $object->eventname = $eventname;
    $object->transactionid = isset($transactionid)?$transactionid : 0;
    $object->elementid = $elementid;
    $object->params = $params;
    $object->response = $response;
    $object->status = $status;
    $object->typeapikey = $typeapikey;
    $object->usercreated = ($userid == 0)?$USER->id : $userid;
    $object->timecreated = time();
    $id = $DB->insert_record('local_prisemforce_logs',$object);
   
}
