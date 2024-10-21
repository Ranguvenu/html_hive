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
 * @package   local_externalcertificates
 * @copyright  eabyas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once '../../config.php'; // to include $CFG, for example
require_once ($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/local/externalcertificate/lib.php');
//$filtervalues = json_decode($_REQUEST['formdata']);
$filterdata = $_REQUEST;
$filtervalues['fromdate[enabled]'] =(int) $filterdata['fromdateenable'];
$filtervalues['todate[enabled]'] = (int)$filterdata['todateenable'];
$filtervalues['fromdate[day]'] = (int)$filterdata['fromdateday'];
$filtervalues['fromdate[month]'] = (int)$filterdata['fromdatemonth'];
$filtervalues['fromdate[year]'] =(int) $filterdata['fromdateyear'];
$filtervalues['todate[day]'] = (int)$filterdata['todateday'];
$filtervalues['todate[year]'] =(int) $filterdata['todateyear'];
$filtervalues['todate[month]'] =(int) $filterdata['todatemonth'];
$filtervalues['status'] = $filterdata['status'];
$filtervalues = (object) $filtervalues;

$downloadfilename = clean_filename ( "externalcertificates_report" );
$csvexport = new csv_export_writer ();
$csvexport->set_filename ( $downloadfilename );
$stable = new \stdClass();
$stable->thead = false;
$stable->start = 0;
$stable->length = 0;
$result = get_listof_external_certificates($stable, $filtervalues);

foreach ($result['result'] as $key) {	
	$array = array();
    $array['username'] = $key['username'];
    $array['empid'] = $key['empid'];
    $array['coursename'] = \local_costcenter\lib::strip_tags_custom($key['coursename']);
    if($key['certificate_issuing_authority ']== 'Other'){
        $key['certificate_issuing_authority'] = $key['authority_type'] .' ('. $key['certificate_issuing_authority'].') ';
    } 
    $array['certificate_issuing_authority'] = \local_costcenter\lib::strip_tags_custom($key['certificate_issuing_authority']);    
    $array['institute_provider'] = \local_costcenter\lib::strip_tags_custom($key['institute_provider']);
    $array['externalcertificate'] = 'External Certificate';
    $array['category'] = $key['category'];
    $array['duration'] = $key['duration'];
    $array['credit'] = $key['credit'];
    $array['description'] = \local_costcenter\lib::strip_tags_custom($key['description']);
    $array['skill'] = $key['skill'];
    $array['issueddate'] = $key['issueddate'];
    $array['validedate'] = $key['validedate'];
    $array['uploadeddate'] = $key['uploadeddate'];
    $array['approveddate'] = $key['approveddate'];
    $array['department'] = $key['department'];
    $array['empgrade'] = $key['empgrade'];
    $array['status'] =  $key['status'];   
    $array['reason'] =  $key['reason']; 
    $array['url'] = $key['url']; 
    $records[]  = $array;	
}

$fieldnames = array (        
    get_string('uname', 'local_externalcertificate'),
    get_string('employeeid', 'local_externalcertificate'),
    get_string('cname', 'local_externalcertificate'),
    get_string('authority', 'local_externalcertificate'),
    get_string('institute_provider', 'local_externalcertificate'),
    get_string('externalcertificate','local_externalcertificate'),
    get_string('category', 'local_externalcertificate'),
    get_string('duration', 'local_externalcertificate'),
    get_string('credit', 'local_externalcertificate'),
    get_string('description', 'local_externalcertificate'),
    get_string('skill', 'local_externalcertificate'),
    get_string('issueddate', 'local_externalcertificate'),
    get_string('validedate', 'local_externalcertificate'),
    get_string('uploadeddate', 'local_externalcertificate'),
    get_string('approveddate', 'local_externalcertificate'),
    get_string('department', 'local_externalcertificate'),
    get_string('empgrade', 'local_externalcertificate'),
    get_string('status', 'local_externalcertificate'),
    get_string('reason', 'local_externalcertificate'),
    get_string('url', 'local_externalcertificate'),
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
exit;
