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
 * Version details.
 *
 * @package    local_external_certificate
 * @copyright  akshat.c@eabyas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_externalcertificate\form;

use moodleform;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/lib.php');

class edit extends moodleform {
    // Add elements to form.
    public function definition() {

        global $CFG, $DB, $USER;

        $mform = $this->_form; // Don't forget the underscore!

        $userid     =$USER->id;
        $username   = $USER->username;
        $email      = $USER->email;
        // print_object($username); die;
     
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
       

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', $userid);

        $mform->addElement('text', 'username', get_string('uname','local_externalcertificate')); 
        $mform->disabledIf('username', 'id', 'neq', 0);
        $mform->setType('username', PARAM_NOTAGS);                 
        $mform->setDefault('username', $username);


        $mform->addElement('hidden', 'email', get_string('email','local_externalcertificate'));
        // $mform->addRule('email', get_string('emailerr', 'local_external_certificate'), 'email', null, 'client');
        // $mform->addRule('email', null, 'required'); 
        $mform->setType('email', PARAM_NOTAGS);
        $mform->setDefault('email', $email);

        $course_certificate_select = array(null => 'Select Course/Certificate','Other' => 'Other');
        $coursecertificatesql = "SELECT id,coursename FROM {local_external_certificates_courses}
                                    WHERE 1=1 ";
        
        $course_certificate = $DB->get_records_sql_menu($coursecertificatesql);
       
        if($course_certificate){
            $course_certificate_select = $course_certificate_select + $course_certificate;
        }

        $mform->addElement('select', 'course_certificate',get_string('course_certificate','local_externalcertificate'), $course_certificate_select);
         $mform->addRule('course_certificate', get_string('coursecertificateerr','local_externalcertificate'), 'required', null);
        $mform->addRule('course_certificate', null, 'required');
         $mform->setType('coursename', PARAM_INT);

        $mform->addElement('text', 'coursename', get_string('coursename','local_externalcertificate')); 
        // $mform->addRule('coursename', get_string('coursenameerr','local_externalcertificate'), 'required', null);
        // $mform->addRule('coursename', null, 'required');
        $mform->setType('coursename', PARAM_NOTAGS);
        $mform->hideIf('coursename', 'course_certificate', 'ne', 'Other');


        $mform->addElement('text', 'institute_provider', get_string('institute_provider','local_externalcertificate')); 
        $mform->addRule('institute_provider', get_string('institute_providererr','local_externalcertificate'), 'required', null);
        $mform->addRule('institute_provider', null, 'required');
        $mform->setType('institute_provider', PARAM_NOTAGS);


        $mform->addElement('text', 'category', get_string('category','local_externalcertificate')); 
        $mform->addRule('category', get_string('categoryerr','local_externalcertificate'), 'required', null);
        $mform->addRule('category', null, 'required');
        $mform->setType('category', PARAM_NOTAGS);


        // $mform->addElement('text', 'duration', get_string('duration','local_externalcertificate'));
        // $mform->addRule('duration', get_string('durationerr','local_externalcertificate'), 'required', null);
        // $mform->addRule('duration', null, 'required');
        // $mform->setType('duration', PARAM_NOTAGS);

         $radioarray=array();
        $radioarray[] = $mform->createElement('static', '','','<b class=hours>Hours</b>');
        $radioarray[] = $mform->createElement('static', '','','<b class=minutes>Minutes(MM)</b>');
        $mform->addGroup($radioarray, 'duration_label', '', array(' '), false);

        $radioarray=array();
        $radioarray[] = $mform->createElement('text', 'hours','',array('placeholder'=>'Hours'));
        $radioarray[] = $mform->createElement('static', '','','<b>:</b>');
        $radioarray[] = $mform->createElement('text', 'min','',array('placeholder'=>'Minutes(MM)'));
        $mform->addGroup($radioarray, 'duration', 'Duration', array(' '), false);
                        
        $templaterules['hours'][] = array( get_string('numbersonlyhours'), 'numeric', null, 'client');
        $templaterules['min'][] = array( get_string('numbersonlyminutes'), 'numeric', null, 'client');
        $mform->addGroupRule('duration', $templaterules);
        $mform->addRule('duration', null, 'required');


        $mform->addElement('text', 'url', get_string('url','local_externalcertificate')); 
        $mform->addRule('url', get_string('urlerr','local_externalcertificate'), 'required', null);
        $mform->addRule('url', null, 'required');
        $mform->setType('url', PARAM_NOTAGS);

        $authorities = array(null => 'Select', 
        'Microsoft' => 'Microsoft',
        'GCP' => 'GCP', 
        'AWS' => 'AWS', 
        'Databricks' => 'Databricks',
        'Oracle' => 'Oracle',
        'PM' => 'PM', 
        'Cognitiveclass' => 'Cognitiveclass',
        'Qlikview' => 'Qlikview', 
        'Qliksense' => 'Qliksense', 
        'SAP' => 'SAP',  'SAS' => 'SAS','Scrum' => 'Scrum', 'Snowflake' => 'Snowflake', 'Other' => 'Other');
        $mform->addElement('select', 'certificate_issuing_authority',get_string('authority','local_externalcertificate'), $authorities);
        $mform->addRule('certificate_issuing_authority', get_string('authorityerr','local_externalcertificate'), 'required', null);
        $mform->addRule('certificate_issuing_authority', null, 'required');
        
        $mform->addElement('text', 'certificate_issuing_authority_text', get_string('others','local_externalcertificate')); 
        $mform->hideIf('certificate_issuing_authority_text', 'certificate_issuing_authority', 'ne', 'Other');

        $dbdata = $DB->get_records('local_skill', null);
        $options = [];
        //$options[-1] = get_string('select_skill','local_externalcertificate');
        foreach ($dbdata as $key) {
            $options[$key->name] = $key->name;
        }

        $skill = $mform->addElement('autocomplete', 'skill', get_string('skill','local_externalcertificate'), $options,array('data-placeholder'=> get_string('select_skill','local_externalcertificate')));
        $mform->setType('skill', PARAM_TEXT);
        $mform->addRule('skill', get_string('skillerr','local_externalcertificate'), 'required', null);
        $mform->addRule('skill', null, 'required');
        $skill->setMultiple(true);

        $mform->addElement('editor', 'description', get_string('description','local_externalcertificate'));
        $mform->setType('description', PARAM_NOTAGS);
        $mform->addRule('description', null, 'required');


        $mform->addElement('date_selector', 'issueddate', get_string('issueddate','local_externalcertificate'));
        $mform->setType('issueddate', PARAM_NOTAGS);
        $mform->addRule('issueddate', get_string('ishuedateerr','local_externalcertificate'), 'required', null);
        $mform->addRule('issueddate', null, 'required');

        $mform->addElement('date_selector', 'validedate', get_string('validedate','local_externalcertificate'));
        $mform->setType('validedate', PARAM_NOTAGS);
       
        $mform->addElement('advcheckbox', 'expiry', null,'This Credential does not expire', array('group' => 1), array(0, 1));
        $mform->disabledIf('validedate', 'expiry', 'neq', 0);
        
        $mform->addElement('filemanager', 'certificate', get_string('uploadexcertificate','local_externalcertificate'), null, array('maxfiles' => 1, 'accepted_types' => 'PDF,ZIP,RAR'));
        $mform->addRule('certificate', get_string('uploaderr','local_externalcertificate'), 'required', null);
        $mform->disable_form_change_checker();
    }

    // Custom validation should be added here
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
	   
        if($data['skill'] == -1 || null || empty($data['skill'])) {
            $errors['skill'] = get_string('skillerr', 'local_externalcertificate');

        }
      
        if(filter_var($data['url'], FILTER_VALIDATE_URL)) {
        } else {
            $errors['url'] = get_string('urlerr2', 'local_externalcertificate');
        }

        if(filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
        }else{
            $errors['email'] = get_string('emailerr2', 'local_externalcertificate');
        }

        // if ($data['validedate'] <= $data['issueddate']) {
        //     $errors['validedate'] = get_string('validedateerr2', 'local_externalcertificate');
        // }
        if($data['expiry'] != 1) {
            if ($data['validedate'] <= $data['issueddate']) {
                $errors['validedate'] = get_string('validedateerr2', 'local_externalcertificate');
            }
        }

        if (isset($data['certificate_issuing_authority']) && $data['certificate_issuing_authority'] == 'Other') {
            if(empty(trim($data['certificate_issuing_authority_text']))){
                $errors['certificate_issuing_authority_text'] = get_string('authorityerr', 'local_externalcertificate');        
            }
        }else if($data['certificate_issuing_authority'] == -1 || null ) {
            $errors['certificate_issuing_authority'] = get_string('authorityerr', 'local_externalcertificate');        

        }
         if (isset($data['course_certificate']) && $data['course_certificate'] == 'Other') {
            if(empty(trim($data['coursename']))){
                $errors['coursename'] = get_string('coursecertificateerr', 'local_externalcertificate');        
            }
        }else if($data['course_certificate'] == -1 || null ) {
            $errors['coursename'] = get_string('coursecertificateerr', 'local_externalcertificate');        

        }

         $data['hours'] ;
        $hour=$data['hours'];$min=$data['min'];
        if($data['form_status']==0){
            if(!empty($min)){
            if (strlen((string) $min)!="2"){
                $errors['duration'] = 'Minutes accepts two digits and It accepts up to 59 Minutes';
            }elseif ($min>59){
                $errors['duration'] = 'It accepts up to 59 Minutes only';
            }     
          }
            if( (empty($hour) || $hour == 0) && (empty($min) || $min == 0) ){
             $errors['duration'] = 'Hours cannot be empty and it will not accepts 0';
            }
            if(!empty($hour) && (isset($min) && $min =='')){
                $errors['duration'] = 'Minutes accepts two digits and It accepts up to 59 Minutes'; 
             }
       }

       /*  if (isset($data['certificate_issuing_authority'])) {
            if(empty(trim($data['certificate_issuing_authority_text']))){
                $errors['certificate_issuing_authority_text'] = get_string('authorityerr', 'local_externalcertificate');        
            }
        } */
        
        return $errors;
    }
}
