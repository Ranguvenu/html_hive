<?php

/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package fractal
 * @subpackage local_externalcertificate
 */


require_once('../../config.php');
require_once($CFG->dirroot . '/local/externalcertificate/lib.php');
require_once($CFG->dirroot . '/local/externalcertificate/filters_form.php');

require_login();
global $CFG,$DB, $PAGE, $visible,$OUTPUT;
$PAGE->requires->jquery();

$PAGE->requires->js_call_amd('local_externalcertificate/external_certificates','load',array());

$systemcontext = \context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/externalcertificate/index.php');
$PAGE->set_title(get_string('pluginname','local_externalcertificate'));
//$PAGE->set_heading(get_string('add_externalcert', 'local_externalcertificate'));

 $heading = format_string(get_string('add_externalcert', 'local_externalcertificate'));
 $pill = "<div class='exclamatoryicon tooltip'>
 <span class='tooliptext '>
     <ul class='exclamatory content'>
         <li class='text-muted'>
             Certificate mustbe of current fiscal year
         </li>
         <li class='text-muted'>
             Non-expired
         </li>
         <li class='text-muted'> 
             Only upload certificates wherein the existing course page is not available on Hive
         </li>
     </ul>
 </span>
</div> ";  
$subheading = get_string('add_externalcertmsg', 'local_externalcertificate');
$PAGE->set_raw_heading($heading . $pill . '<br>' . $subheading, true);


//echo '<i class="fa fa-exclamation-circle" aria-hidden="true"></i>';
$PAGE->requires->js_call_amd('local_externalcertificate/external_certificates','load',array());
$PAGE->requires->js_call_amd('local_externalcertificate/reason_form', 'load', array());
$PAGE->requires->js_call_amd('local_costcenter/newcostcenter', 'downloadcert', array());
$PAGE->navbar->add(get_string('add_externalcert', 'local_externalcertificate'));

echo $OUTPUT->header(); 
$renderer = $PAGE->get_renderer('local_externalcertificate');
$thisfilters = array('from_date','to_date', 'cert_status');

echo '<h4>' .get_string('addedcertificate', 'local_externalcertificate'). '</h4>';

$filterparams = $renderer->display_externalcertificates(true);
$filterparams['submitid'] = 'form#externalfilteringform';
$formtype = 'externalfilteringform';
$mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams ,'submitid' => 'externalfilteringform'));

include($CFG->dirroot . '/local/externalcertificate/certdata.php');
echo "<a href='javascript:void(0);' data-href='".$CFG->wwwroot."/local/externalcertificate/export.php' class = 'pull-right downloadcsv' style='cursor:pointer' >Download CSV</a>";

echo $renderer->display_externalcertificates();

// if(!is_siteadmin() && !(has_capability('local/externalcertificate:manage', $systemcontext) && has_capability('local/externalcertificate:view', $systemcontext))){
    
//     echo '<h4>' .get_string('internalcertificate', 'local_externalcertificate'). '</h4>';
//     $filterparams = $renderer->display_internalcertificates(true);
//     $filterparams['submitid'] = 'form#internalfilteringform';
//     $formtype = 'internalfilteringform';
//     $mform = new filters_form(null, array('filterlist'=> $thisfilters, 'filterparams' => $filterparams ,'submitid' => 'internalfilteringform'));
//     include($CFG->dirroot . '/local/externalcertificate/certdata.php');
    
//     echo $renderer->display_internalcertificates();
//     //include_once($CFG->dirroot . '/local/externalcertificate/internalcertificates.php');
// }

echo $OUTPUT->footer();
