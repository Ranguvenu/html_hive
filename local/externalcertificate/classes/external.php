<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * Courses external API
 *
 * @package    local_external_certificate
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/local/externalcertificate/lib.php');
use local_external_certificates\event\request_merge;
class local_externalcertificate_external extends external_api
{
    // submint and updade records for levels.......
    public static function submit_certificates_form_parameters() {

          return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),
                'id' => new external_value(PARAM_INT,'Class id',0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create class form, encoded as a json array')
            )
        );
    }

    public static  function submit_certificates_form($contextid, $id, $jsonformdata) {

        global $CFG, $DB, $USER;
        $params=self::validate_parameters(
            self::submit_certificates_form_parameters(),
            array('contextid'=>$contextid,'id'=>$id,'jsonformdata'=>$jsonformdata)
        );
        $context=context::instance_by_id($params['contextid'],'MUST_EXIST');

        self::validate_context($context);

        $serialiseddata=json_decode($params['jsonformdata']);
        $formdata=array();
        
        parse_str($serialiseddata, $formdata);
       
        $mform = new local_externalcertificate\form\edit(null, array('id'=>$id), 'post', '', null, true, $formdata);

        $validateddata = $mform->get_data();
       
        $notification = new \local_externalcertificate\notification();
        if ($validateddata) {
          
            if($validateddata->id == 0) {
                //$createrec = new stdClass();
                $systemcontext = context_system::instance();
                
                //$createrec->certificate = $validateddata->certificate;
                file_save_draft_area_files($validateddata->certificate, $systemcontext->id, 'local_externalcertificate', 'certificate', $validateddata->certificate);
                if($validateddata->certificate_issuing_authority == 'Other'){
                    $validateddata->authority_type        = $validateddata->certificate_issuing_authority_text;   
                    //$validateddata->certificate_issuing_authority = $validateddata->certificate_issuing_authority_text;
                }

                if($validateddata->course_certificate == 'Other'){
                
                    $validateddata->mastercourse = $validateddata->coursename;
                    $validateddata->coursename = $validateddata->course_certificate;
                   
                }else{
                     $validateddata->coursename = $validateddata->course_certificate;
                }
               
                if (in_array("-1", $validateddata->skill)){
                    $validateddata->skill = "-1";
                  }else {
                    $validateddata->skill=implode(',',$validateddata->skill);
                  }
                $validateddata->status            = 0;
                $validateddata->description       = $validateddata->description['text'];
                $validateddata->usercreated       = $USER->id;
                $validateddata->timecreated       = time();

                $hours = $validateddata->hours * 3600 ;
                $minutes = $validateddata->min * 60;
                $validateddata->duration = $hours + $minutes;
                //print_r($validateddata);exit;
  
                $insertrecord = $DB->insert_record('local_external_certificates', $validateddata);
                
                if (class_exists('\local_externalcertificate\notification')) {
                    $userinfo = \core_user::get_support_user();
                    $certdetails = $validateddata;
                    $emailtype = 'certificate_uploaded';
                    $notification->send_extcertificate_notification($certdetails, $userinfo, $emailtype);
                }
                
            }
        } else {
            // Generate a warning.
           throw new moodle_exception('Error in submission');
        }
        $return = array(
            'id' => $id,
            'contextid' => $contextid,
        );

        return $return;
    }

    public static function submit_certificates_form_returns() {

         return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, ' id'),
            'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),

        ));
    }

    // for change certificate status
    public static function change_certificates_status_parameters() {
        return new external_function_parameters(
            array(
                'id'  => new external_value(PARAM_INT, 'id',0),
                'status' => new external_value(PARAM_INT, 'status',0),
            )
        );
    }

    public static function change_certificates_status($id,$status) {
        global $DB,$USER;
        $params = self::validate_parameters (
            self::change_certificates_status_parameters(),array('id'=>$id,'status'=>$status)
        );
        
        $context = context_system::instance();
        self::validate_context($context);
       
        if($id) {
            $fieldvalue = $DB->get_field( 'local_external_certificates','coursename',['id' => $id]);
            if ($fieldvalue == 'Other') {
                $return = $fieldvalue;

            } else {
                $updaterec = new \stdClass();
                $updaterec->id       = $id;
                $updaterec->status   = $status;
                $updaterec->usermodified   = $USER->id;
                $updaterec->timemodified   = time();

                if($DB->update_record('local_external_certificates', $updaterec)){  
                    $updaterec->extcertid       = $id;
                    $updaterec->usercreated   = $USER->id;
                    $updaterec->timecreated   = time();              
                    $DB->insert_record('local_external_certif_log', $updaterec);

                    //Event triggering
                    $requestinfo=$DB->get_record_sql("SELECT lec.*,lecc.coursename as mastercoursename,lecc.coursecode as coursecode FROM {local_external_certificates} as lec JOIN {local_external_certificates_courses} as lecc ON lec.coursename = lecc.id WHERE lec.id=$updaterec->id");
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $updaterec->id,
                        'courseid' => $requestinfo->coursecode,
                        'relateduserid' => $requestinfo->usercreated,
                        'other'=> array('coursename'=>$updaterec->coursename,
                        'id'=>$updaterec->id,'requestuserid'=>$USER->id)
                    );               
                    $event = local_externalcertificate\event\approve_externalcertificate::create($params);
                    $event->trigger();
                    send_notification($id,'certificate_approved');

                    $return = $fieldvalue;                
                }
                /*  if($result) {
                    \core\notification::add(get_string('noti','local_externalcertificate'), \core\output\notification::NOTIFY_SUCCESS);
                } */

            }
            
        } else {
              throw new moodle_exception('Error');
        }

        return $return;
    }

    public static function change_certificates_status_returns() {
        return new external_value(PARAM_RAW, 'return');
    }

    // for save reject reason form
    public static function save_decline_reason_form_parameters() {

          return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),
                'id' => new external_value(PARAM_INT,'ID',0),
                'status' => new external_value(PARAM_INT,'Status',0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from form, encoded as a json array')
            )
        );
    }

    public static  function save_decline_reason_form($contextid, $id,$status, $jsonformdata) {

        global $CFG, $DB, $USER;
        $params=self::validate_parameters(
            self::save_decline_reason_form_parameters(),
            array('contextid'=>$contextid,'id'=>$id,'status'=>$status,'jsonformdata'=>$jsonformdata)
        );
        $context=context::instance_by_id($params['contextid'],'MUST_EXIST');

        self::validate_context($context);   
        $serialiseddata=json_decode($params['jsonformdata']);
        $data=array();
        parse_str($serialiseddata, $data);
        $mform = new local_externalcertificate\form\reason_form(null, array('id'=>$id), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
       
        if ($validateddata) {
            if($validateddata->id >0) {
               $updaterec = new \stdClass();
               $updaterec->id       = $validateddata->id;
               $updaterec->status   = $validateddata->status;
               $updaterec->reason   = $validateddata->reason;
               $updaterec->usermodified   = $USER->id;
               $updaterec->timemodified   = time();

                if($DB->update_record('local_external_certificates', $updaterec)){  
                    $updaterec->extcertid       = $validateddata->id;   
                    $updaterec->usercreated   = $USER->id;
                    $updaterec->timecreated   = time();            
                    $DB->insert_record('local_external_certif_log', $updaterec);
                    send_notification($validateddata->id,'certificate_declined');
                }
            }
        } else {
            // Generate a warning.
           throw new moodle_exception('Error in submission');
        }
        $return = array(
            'id' => $id,
            'contextid' => $contextid,
        );

        return $return;
    }

    public static function save_decline_reason_form_returns() {

         return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, ' id'),
            'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),

        ));
    }

    /** Describes the parameters for external certificates.
     * @return external_function_parameters
     */
    public static function external_certificates_view_parameters()
    {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(
                PARAM_INT,
                'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of results to return',
                VALUE_DEFAULT,
                0
            ),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }

    /**
     * lists all external certificates
     *
     * @param array $options
     * @param array $dataoptions
     * @param int $offset
     * @param int $limit
     * @param int $contextid
     * @param array $filterdata
     * @return array external certificates list.
     */
    public static function external_certificates_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata)
    {
        global $DB, $PAGE;
        
        // Parameter validation.
        $params = self::validate_parameters(
            self::external_certificates_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);
        $filteroptions = json_decode($options);
        if(is_array($filtervalues)){
            $filtervalues = (object)$filtervalues;
        } 
        $filtervalues->courseid = $filteroptions->courseid;
  
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = get_listof_external_certificates($stable, $filtervalues);
        $totalcount = $data['totalrecords'];
        // echo "<pr>";print_r($data['result']);exit;
        return [
            'totalcount' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $data['result'],
            'actions' => $data['actions'],
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }

    /**
     * Returns description of method result value
     * @return 
     */

    public static function external_certificates_view_returns()
    {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
            'totalcount' => new external_value(PARAM_INT, 'total number of records in result set', VALUE_OPTIONAL),
            'filterdata' => new external_value(PARAM_RAW, 'filter data', VALUE_OPTIONAL),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(   
                        'id' =>   new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),    
                        'username' => new external_value(PARAM_RAW, 'username', VALUE_OPTIONAL),
                        'empid' => new external_value(PARAM_RAW, 'empid', VALUE_OPTIONAL),    
                        'coursename' => new external_value(PARAM_RAW, 'coursename', VALUE_OPTIONAL),
                        'institute_provider' =>new external_value(PARAM_RAW, 'institute provider', VALUE_OPTIONAL),
                        'category' =>new external_value(PARAM_RAW, 'category', VALUE_OPTIONAL),
                        'duration' =>new external_value(PARAM_RAW, 'duration', VALUE_OPTIONAL),
                        'description' =>new external_value(PARAM_RAW, 'description', VALUE_OPTIONAL),
                        'certificate_issuing_authority' => new external_value(PARAM_RAW, 'certificate issuing authority', VALUE_OPTIONAL),
                        'allskills' => new external_value(PARAM_RAW, 'allskills', VALUE_OPTIONAL),
                        'skill' => new external_value(PARAM_RAW, 'skill', VALUE_OPTIONAL),
                        'issueddate' => new external_value(PARAM_RAW, 'issued date', VALUE_OPTIONAL),
                        'validedate' => new external_value(PARAM_RAW, 'valid date', VALUE_OPTIONAL),
                        'uploadeddate' => new external_value(PARAM_RAW, 'uploaded date', VALUE_OPTIONAL),
                        'approveddate' => new external_value(PARAM_RAW, 'approved date', VALUE_OPTIONAL),
                        'status' => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                        'imageurl' => new external_value(PARAM_RAW, 'certificate url', VALUE_OPTIONAL),
                        'compreason' => new external_value(PARAM_RAW, 'compreason', VALUE_OPTIONAL),
                        'reason' => new external_value(PARAM_RAW, 'reason', VALUE_OPTIONAL),
                        'approvestatus' => new external_value(PARAM_RAW, 'approvestatus', VALUE_OPTIONAL),
                        'rejectstatus' => new external_value(PARAM_RAW, 'rejectstatus', VALUE_OPTIONAL),
                        'newstatus' =>new external_value(PARAM_BOOL, 'newstatus', VALUE_OPTIONAL),  
                        'actions' =>  new external_value(PARAM_BOOL, 'actions', VALUE_OPTIONAL), 
                        'url' =>  new external_value(PARAM_RAW, 'url', VALUE_OPTIONAL),
                        'mergestatus' => new external_value(PARAM_RAW, 'mergestatus', VALUE_OPTIONAL),
                        'credit' => new external_value(PARAM_RAW, 'credit', VALUE_OPTIONAL),
                        'department' => new external_value(PARAM_RAW, 'department', VALUE_OPTIONAL),
                        'empgrade' => new external_value(PARAM_RAW, 'empgrade', VALUE_OPTIONAL),
                    )
                ),'records', VALUE_OPTIONAL),
            'actions' =>  new external_value(PARAM_BOOL, 'actions', VALUE_OPTIONAL),  
            ]);
    }

    /** Describes the parameters for internal certificates.
     * @return external_function_parameters
     */
    public static function internal_certificates_view_parameters()
    {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(
                PARAM_INT,
                'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of results to return',
                VALUE_DEFAULT,
                0
            ),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }

    /**
     * lists all internal certificates
     *
     * @param array $options
     * @param array $dataoptions
     * @param int $offset
     * @param int $limit
     * @param int $contextid
     * @param array $filterdata
     * @return array internal certificates list.
     */
    public static function internal_certificates_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata)
    {
        global $DB, $PAGE;
        require_login();

        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::internal_certificates_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);
        $filteroptions = json_decode($options);
        if(is_array($filtervalues)){
            $filtervalues = (object)$filtervalues;
        } 
        $filtervalues->courseid = $filteroptions->courseid;
  
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = get_listof_internal_certificates($stable, $filtervalues);
        $totalcount = $data['totalrecords'];
        return [
            'totalcount' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $data['result'],
            'actions' => $data['actions'],
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function internal_certificates_view_returns()
    {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
            'totalcount' => new external_value(PARAM_INT, 'total number of records in result set', VALUE_OPTIONAL),
            'filterdata' => new external_value(PARAM_RAW, 'filter data', VALUE_OPTIONAL),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(   
                        'id' =>   new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),    
                        'coursename' => new external_value(PARAM_RAW, 'coursename', VALUE_OPTIONAL),
                        'learningtype' => new external_value(PARAM_RAW, 'learningtype', VALUE_OPTIONAL),
                        'skill' => new external_value(PARAM_RAW, 'skill', VALUE_OPTIONAL),
                        'uploadeddate' => new external_value(PARAM_RAW, 'uploaded date', VALUE_OPTIONAL),
                        'approveddate' => new external_value(PARAM_RAW, 'approved date', VALUE_OPTIONAL),
                        'status' => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                        'imageurl' => new external_value(PARAM_RAW, 'certificate url', VALUE_OPTIONAL),
                        'url' => new external_value(PARAM_RAW, 'url', VALUE_OPTIONAL),
                    )
                ),'records', VALUE_OPTIONAL),
           ]);
    }



    // For Merge request course/ certificate request <Revathi>
    public static function mergecourserequest_certificates_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),
                'id' => new external_value(PARAM_INT,'certificates id',0),
                'status' => new external_value(PARAM_INT,'Status',0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create master courses certificates form, encoded as a json array')
            )
        );
    }

    public static function mergecourserequest_certificates($contextid, $id,$status, $jsonformdata) {
        global $DB,$USER;


        $params=self::validate_parameters(
            self::mergecourserequest_certificates_parameters(),
            array('contextid'=>$contextid,'id'=>$id,'status'=>$status,'jsonformdata'=>$jsonformdata)
        );
       
        $context=context::instance_by_id($params['contextid'],'MUST_EXIST');

        self::validate_context($context);   
      

        $serialiseddata=json_decode($params['jsonformdata']);
      
        $formdata=array();
        
        parse_str($serialiseddata, $formdata);
       
        $mform = new local_externalcertificate\form\mastercourse_form(null, array('id'=>$id), 'post', '', null, true, $formdata);

        $validateddata = $mform->get_data();
        if ($validateddata) {            
            if(empty($validateddata->coursecertificate)) {
                if($validateddata->id >0) {
                   $updaterec = new \stdClass();
                   $updaterec->id       = $validateddata->id;
                   $updaterec->status   = $validateddata->status; 
                   $updaterec->coursename = $validateddata->coursename;                
                   $updaterec->usermodified   = $USER->id;
                   $updaterec->timemodified   = time();

                  $DB->update_record('local_external_certificates', $updaterec);
                   if($DB->update_record('local_external_certificates', $updaterec)){
                     $params = array(
                        'context' => context_system::instance(),
                            'objectid' => $updaterec->id,
                            'other'=>array('coursename'=>$updaterec->coursename,
                            'id'=>$updaterec->id,'requestuserid'=>$USER->id)
                        );
                        $event =local_externalcertificate\event\request_merge::create($params);                   
                        $requests=$DB->get_record('local_external_certificates', array('id'=> $updaterec->id));
                        $event->add_record_snapshot('local_external_certificates', $requests);
                        $event->trigger();                     

                        $updaterec->extcertid       = $validateddata->id;   
                        $updaterec->usercreated   = $USER->id;
                        $updaterec->timecreated   = time();            
                        $DB->insert_record('local_external_certif_log', $updaterec);
                        //send_notification($validateddata->id,'Merge request');
                    } 
                } 

            }else{
                if($validateddata->id >0) {
                   $updaterec = new \stdClass();
                   $updaterec->id       = $validateddata->id;                  
                   $updaterec->coursename = $validateddata->mastercourse;
                   $updaterec->coursecode = substr(hash("sha256", uniqid()),0,5);       
                   
                   $updaterec->usercreated   = $USER->id;
                   $updaterec->timecreated   = time();  

                    $masterdata = $DB->insert_record('local_external_certificates_courses', $updaterec);
                    $updaterec->usermodified   = $USER->id;
                    $updaterec->timemodified   = time();
                    $updaterec->coursename =  $masterdata;
                    $updaterec->status   = $validateddata->status; 
                    if($DB->update_record('local_external_certificates', $updaterec)){
                        $params = array(
                            'context' => context_system::instance(),
                            'objectid' => $updaterec->id,
                            'other'=>array('coursename'=>$updaterec->coursename,
                            'id'=>$updaterec->id,'requestuserid'=>$USER->id)
                        );
                        $event = local_externalcertificate\event\request_merge::create($params);
                      
                        $requests=$DB->get_record('local_external_certificates', array('id'=> $updaterec->id));
                        $event->add_record_snapshot('local_external_certificates', $requests);
                        $event->trigger();
                      
                        $updaterec->extcertid       = $validateddata->id;   
                        $updaterec->usercreated   = $USER->id;
                        $updaterec->timecreated   = time();            
                        $DB->insert_record('local_external_certif_log', $updaterec);
                        //send_notification($validateddata->id,'Merge request');
                    } 
                }
            }
          
        } else {
            // Generate a warning.
           throw new moodle_exception('Error in submission');
        }
       
        $return = array(
            'id' => $id,
            'contextid' => $contextid,
        );

        return $return;
    }

    public static function mergecourserequest_certificates_returns() {
        return new external_single_structure(array(
                'id' => new external_value(PARAM_INT, ' id'),
                'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),

            ));
    }

    //Submit Master ExternalCertificate Form <Revathi>
    public static function mastercertificate_form_parameters() {

          return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),
                'id' => new external_value(PARAM_INT,'certificate id',0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create class form, encoded as a json array')
            )
        );
    }

    public static  function mastercertificate_form($contextid, $id, $jsonformdata) {

        global $CFG, $DB, $USER;
        $params=self::validate_parameters(
            self::mastercertificate_form_parameters(),
            array('contextid'=>$contextid,'id'=>$id,'jsonformdata'=>$jsonformdata)
        );
        $context=context::instance_by_id($params['contextid'],'MUST_EXIST');

        self::validate_context($context);

        $serialiseddata=json_decode($params['jsonformdata']);
        $formdata=array();
        
        parse_str($serialiseddata, $formdata);
       
        $mform = new local_externalcertificate\form\mastercertificate_form(null, array('id'=>$id), 'post', '', null, true, $formdata);

        $validateddata = $mform->get_data();
        if ($validateddata) {
          
            if($validateddata->id > 0) {

                $validateddata->coursename =$validateddata->coursename;
                $validateddata->usermodified   = $USER->id;
                $validateddata->timemodified   = time();
                $masterdata = $DB->update_record('local_external_certificates_courses', $validateddata);  
            }else{
                //$createrec = new stdClass();
                $systemcontext = context_system::instance();
                $validateddata->coursename =$validateddata->coursename;
                $validateddata->coursecode = substr(hash("sha256", uniqid()),0,5);   
                $validateddata->usercreated       = $USER->id;
                $validateddata->timecreated       = time();
                $masterdata = $DB->insert_record('local_external_certificates_courses', $validateddata);    
            }
        } else {
            // Generate a warning.
           throw new moodle_exception('Error in submission');
        }
        $return = array(
            'id' => $id,
            'contextid' => $contextid,
        );

        return $return;
    }

    public static function mastercertificate_form_returns() {

         return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, ' id'),
            'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),

        ));
    }

   /** Describes the parameters for master external certificates.
     * @return master external_function_parameters
     */
    public static function masterexternal_certificates_view_parameters() {

        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(
                PARAM_INT,
                'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of results to return',
                VALUE_DEFAULT,
                0
            ),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }

    /**
     * lists all master external certificates
     *
     * @param array $options
     * @param array $dataoptions
     * @param int $offset
     * @param int $limit
     * @param int $contextid
     * @param array $filterdata
     * @return array master external certificates list.
     */
    public static function masterexternal_certificates_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata)
    {
        global $DB, $PAGE;
       
        // Parameter validation.
        $params = self::validate_parameters(
            self::masterexternal_certificates_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);
        $filteroptions = json_decode($options);
        if(is_array($filtervalues)){
            $filtervalues = (object)$filtervalues;
        } 
        $filtervalues->courseid = $filteroptions->courseid;
  
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = get_listof_masterexternal_certificates($stable, $filtervalues);
        
        $totalcount = $data['totalrecords'];
        return [
            'totalcount' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $data['result'],
            'actions' => $data['actions'],
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }

    /**
     * Returns description of method result value
     * @return 
     */

    public static function masterexternal_certificates_view_returns()
    {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
            'totalcount' => new external_value(PARAM_INT, 'total number of records in result set', VALUE_OPTIONAL),
            'filterdata' => new external_value(PARAM_RAW, 'filter data', VALUE_OPTIONAL),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(   
                        'id' =>   new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),                  
                        'coursename' => new external_value(PARAM_RAW, 'coursename', VALUE_OPTIONAL),
                        'coursecode' => new external_value(PARAM_RAW, 'coursecode', VALUE_OPTIONAL),
                        'editcap' => new external_value(PARAM_RAW, 'editcap', VALUE_OPTIONAL),
                        'deletecap' => new external_value(PARAM_RAW, 'deletecap', VALUE_OPTIONAL),
                       
                        'uploadeddate' => new external_value(PARAM_RAW, 'uploaded date', VALUE_OPTIONAL),
                        
                        'actions' =>  new external_value(PARAM_BOOL, 'actions', VALUE_OPTIONAL), 
                                          
                    )
                ),'records', VALUE_OPTIONAL),
            'actions' =>  new external_value(PARAM_BOOL, 'actions', VALUE_OPTIONAL),  
            ]);
    }
     //Delete
    public static function deletemastercertificate_parameters() {
        return new external_function_parameters(
            array(
                'id'  => new external_value(PARAM_INT, 'id',0),
            )
        );
    }


    public static function deletemastercertificate($id) {
        global $DB;
    
        $params = self::validate_parameters (
    
               self::deletemastercertificate_parameters(),array('id'=>$id
           )
       );

        $context = context_system::instance();
        self::validate_context($context);

        if($id) {
            $delete = $DB->delete_records('local_external_certificates_courses',array('id'=>$id));

        } else {
            throw new moodle_exception('Error');
        }
     }

    public static function deletemastercertificate_returns(){
    
        return null;
    }


}
