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
 * @package BizLMS
 * @subpackage local_courses
 */


defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/local/courses/lib.php');
class local_externalcertificate_renderer extends plugin_renderer_base {
  

    /**
     * Display the avialable courses
     *
     * @return string The text to render
     */
    public function display_certificates1() {
        global $DB, $OUTPUT,$USER;
        $systemcontext = context_system::instance();

        $result = $DB->get_records('local_external_certificates');

        foreach ($result as $key) {
            $key->issueddate = date('Y-m-d', $key->issueddate);
            $key->validedate = date('Y-m-d', $key->validedate);
            $key->uploadeddate = date('Y-m-d', $key->timecreated);
            $key->approveddate = ($key->timemodified) ? date('Y-m-d', $key->timemodified) : 'N/A';
            $key->newstatus  = $key->status == 1 ? true : false;
            $a = array('0' => 'Pending', '1' => 'Approved', '2' => 'Decline');
            $key->status = $a[$key->status];

           /*  if ($key->status == "0") {
                $key->status = 'Pending';
            }
            if ($key->status == "1") {
                $key->status = 'Approved';
            }
            if ($key->status == "2") {
                $key->status = 'Decline';
            } */
            $key->imageurl  = img_path2($key->certificate);
        }

        $data = (object)[
            'result'        => array_values($result),
        ];       
      
        if(((has_capability('local/costcenter:create', $systemcontext)&&has_capability('local/courses:bulkupload', $systemcontext)&&has_capability('local/courses:manage', $systemcontext)&&has_capability('moodle/course:create', $systemcontext)&&has_capability('moodle/course:update', $systemcontext)))|| is_siteadmin()){
            echo $OUTPUT->render_from_template('local_externalcertificate/excertificatereport_table', $data);
        } else {
            $internalcert = get_internal_certificates($USER->id);            
            $data->internalcert = array_values($internalcert);
            echo $OUTPUT->render_from_template('local_externalcertificate/excertificatereport_table_for_others', $data);
        }

    } 

    /**
     * Display the avialable certificates
     *
     * @return string The text to render
     */
    public function display_externalcertificates($filter = false) {
        $systemcontext = context_system::instance();
        
        $options = array('targetID' => 'external_certificates','perPage' => 4, 'cardClass' => 'tableformat', 'viewType' => 'table');
        $options['methodName']='local_external_certificates';
        $options['templateName']='local_externalcertificate/externalcertificatereport';
        
        $options = json_encode($options);

        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'external_certificates',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
       
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }
    
     /**
     * Display the avialable certificates
     *
     * @return string The text to render
     */
    public function display_internalcertificates($filter = false) {
        $systemcontext = context_system::instance();
        
        $options = array('targetID' => 'internal_certificates','perPage' => 4, 'cardClass' => 'tableformat', 'viewType' => 'table');
        $options['methodName']='local_internal_certificates';
        $options['templateName']='local_externalcertificate/internalcertificatereport';
        
        $options = json_encode($options);

        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'internal_certificates',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
       
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }

     /**
     * Display the avialable master certificates
     *
     * @return string The text to render
     */
    public function display_masterexternalcertificates($filter = false) {
        $systemcontext = context_system::instance();
       
        $options = array('targetID' => 'masterexternalcertificates','perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        $options['methodName']='local_external_certificates_masterdata';
        $options['templateName']='local_externalcertificate/masterexternalcertificateview';
        
        $options = json_encode($options);

        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
                'targetID' => 'masterexternalcertificates',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];
     
        if($filter){
            return  $context;
        }else{
            return  $this->render_from_template('local_costcenter/cardPaginate', $context);
        }
    } 
}
