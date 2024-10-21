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
 * @package    local_externalcertificate
 * @copyright  eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_externalcertificate_submit_certificates_form' => array( 
        'classname'   => 'local_externalcertificate_external', 
        'classpath'   => 'local/externalcertificate/classes/external.php',
        'methodname'  => 'submit_certificates_form', 
        'description' => 'This documentation will be displayed in the generated API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write', 
        'ajax'        => true, 
    ),

    'local_externalcertificate_changestatus' => array(
        'classname'   => 'local_externalcertificate_external',
        'classpath'   => 'local/externalcertificate/classes/external.php',
        'methodname'  => 'change_certificates_status', 
        'description' => 'This documentation will be displayed in the generated API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write', 
        'ajax'        => true, 

    ),
    'local_externalcertificate_submit_reason_form' => array(
        'classname'   => 'local_externalcertificate_external',
        'classpath'   => 'local/externalcertificate/classes/external.php',
        'methodname'  => 'save_decline_reason_form', 
        'description' => 'This documentation will be displayed in the generated API documentationAdministration > Plugins > Webservices > API documentation)',
        'type'        => 'write', 
        'ajax'        => true, 

    ) ,
    'local_external_certificates' => array(
        'classname' => 'local_externalcertificate_external',
        'classpath' => 'local/externalcertificate/classes/external.php',
        'methodname' => 'external_certificates_view',        
        'description' => 'List all external certificates in table view',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_internal_certificates' => array(
        'classname' => 'local_externalcertificate_external',
        'classpath' => 'local/externalcertificate/classes/external.php',
        'methodname' => 'internal_certificates_view',        
        'description' => 'List all internal certificates in table view',
        'ajax' => true,
        'type' => 'read',
    ), 
    /* 'local_externalcertificate_filter' => array(
        'classname' => 'local_externalcertificate_external',
        'classpath' => 'local/externalcertificate/classes/external.php',
        'methodname' => 'external_certificates_filter',        
        'description' => 'List all external certificates in table view',
        'ajax' => true,
        'type' => 'read', 
    ) */
    'local_externalcertificate_mergecourserequest' => array(
        'classname'   => 'local_externalcertificate_external',
        'classpath'   => 'local/externalcertificate/classes/external.php',
        'methodname'  => 'mergecourserequest_certificates', 
        'description' => 'Course / certificates merge request',
        'type'        => 'write', 
        'ajax'        => true, 

    ),
    'local_externalcertificate_mastercertificate_form' => array(
        'classname'   => 'local_externalcertificate_external',
        'classpath'   => 'local/externalcertificate/classes/external.php',
        'methodname'  => 'mastercertificate_form', 
        'description' => 'Course / certificates merge request',
        'type'        => 'write', 
        'ajax'        => true, 

    ),
    //View the master certificate data
    'local_external_certificates_masterdata' => array(
        'classname' => 'local_externalcertificate_external',
        'classpath' => 'local/externalcertificate/classes/external.php',
        'methodname' => 'masterexternal_certificates_view',        
        'description' => 'List all masterexternal certificates in table view',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_externalcertificate_deletemastercertificate' => array(
      'classname'   => 'local_externalcertificate_external',
      'classpath'   => 'local/externalcertificate/classes/external.php',
      'methodname'  => 'deletemastercertificate',
      'description' => 'Delete mastercertificate',
      'type'        => 'write',
      'ajax'        => true,

  ),

       
);
