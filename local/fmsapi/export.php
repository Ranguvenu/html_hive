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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   local_fmsapi
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once '../../config.php'; // to include $CFG, for example
require_once ($CFG->libdir . '/csvlib.class.php');

$downloadfilename = clean_filename ( "fmsapi_coursesearchlist_csv" );
$csvexport = new csv_export_writer ();
$csvexport->set_filename ( $downloadfilename );
$sql = "SELECT u.id,u.firstname,u.lastname,u.open_employeeid,f.id,f.coursename,f.employee_id,f.employee_name,
                f.employee_email,f.message,f.requested_date 
                FROM {user} u
                RIGHT JOIN {local_fmsapi_course_search} f  ON u.open_employeeid = f.employee_id
                ORDER BY f.requested_date desc";
$result = $DB->get_records_sql($sql,$params=null, $limitfrom=0, $limitnum=0);
$records = array();
	
foreach ($result as $res) {
	$array = array();
	$array[] = $res->coursename;
	$array[] =  ucfirst($res->firstname).' '.ucfirst($res->lastname);
	$array[] = $res->employee_email;
	$array[] = $res->message;
	$array[] = date('d M Y', $res->requested_date);
	$records[]  = $array;	
}

$fieldnames = array (
		'Search keyword',
		'Requested by',
		'Email',
        'Response',
        'Requested on' 
);
$exporttitle = array ();
foreach ( $fieldnames as $field ) {
	$exporttitle [] = $field;
}

$csvexport->add_data( $exporttitle );
foreach ($records as $rec ) {
	$csvexport->add_data( $rec );
}
$csvexport->download_file();
