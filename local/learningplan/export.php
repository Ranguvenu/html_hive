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
 * @package   local_learningplan
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once '../../config.php'; // to include $CFG, for example
require_once ($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/local/learningplan/lib.php');

$downloadfilename = clean_filename ( "lp_unrolledusers_csv" );
$csvexport = new csv_export_writer ();
$csvexport->set_filename ( $downloadfilename );
$result = get_unenrolled_lpaths_list(); 
	
foreach ($result as $res) {
	$array = array();
	$array[] = $res->name;
	$array[] =  $res->username;
	$array[] =  $res->email;
	$array[] = $res->unenrol_reason;
	$array[] = $res->time;
	$records[]  = $array;	
}

$fieldnames = array (
		'LearningPath Name',
		'Username',
		'Email',
		'Reason for unenrol',
        'Unenrolled time'
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
