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
 * @copyright  revathi.m@eabyas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_externalcertificate\form;

use moodleform;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/lib.php');

class mastercourse_form extends moodleform {
    // Add elements to form.
    public function definition() {

        global $CFG, $DB, $USER;

        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->_customdata['id'];
        $status = $this->_customdata['status'];        
       
        // $mform->addElement('html', '<p style="font-size:15px">'.get_string('mergecourse_heading','local_externalcertificate').'</p>');
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'status', $status);
        $mform->setType('status', PARAM_INT);     


     
        $mastercourse_certificate = $DB->get_field('local_external_certificates', 'mastercourse', array('id' => $id));     

        $mform->addElement('text', 'mastercourse', get_string('course_certificate','local_externalcertificate')); 
        $mform->disabledIf('mastercourse', 'id', 'neq', 0);
        $mform->setType('mastercourse', PARAM_NOTAGS);                 
        $mform->setDefault('mastercourse', $mastercourse_certificate);


        $mform->addElement('advcheckbox', 'coursecertificate', get_string('addcoursecertificate', 'local_externalcertificate'));
        $mform->setDefault('coursecertificate', 0);
        $mform->addHelpButton('coursecertificate', 'addcoursecertificate', 'local_externalcertificate');

        $course_certificate_select = array(null => 'Select Course/Certificate');
        $coursecertificatesql = "SELECT id,coursename FROM {local_external_certificates_courses}
                                    WHERE 1=1 ";
        
        $course_certificate = $DB->get_records_sql_menu($coursecertificatesql);
       
        if($course_certificate){
            $course_certificate_select = $course_certificate_select + $course_certificate;
        }
      
        $mform->addElement('select', 'coursename',get_string('mastercourse_certificate','local_externalcertificate'), $course_certificate_select);
        $mform->setType('coursename', PARAM_INT);
        $mform->hideIf('coursename', 'coursecertificate', 'ne', 0);
       

        
        $mform->disable_form_change_checker();
    }

    // Custom validation should be added here
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['coursecertificate'] == 0 || $data['coursecertificate'] == " ") {
            if(empty(trim($data['coursename']))){
                $errors['coursename'] = get_string('coursecertificateerr', 'local_externalcertificate');        
            }
        }else if($data['coursecertificate'] == 0 && empty($data['coursename'])) {
            $errors['coursename'] = get_string('coursecertificateerr', 'local_externalcertificate');        

        }
       
  
   
        return $errors;
    }
}
