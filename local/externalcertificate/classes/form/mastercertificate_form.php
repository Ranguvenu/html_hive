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

class mastercertificate_form extends moodleform {
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


        $mform->addElement('text', 'coursename', get_string('coursename','local_externalcertificate')); 
        $mform->addRule('coursename', get_string('coursenameerr','local_externalcertificate'), 'required', null);
        // $mform->addRule('coursename', null, 'required');
        $mform->setType('coursename', PARAM_NOTAGS);

        
        $mform->disable_form_change_checker();
    }

    // Custom validation should be added here
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if(empty(trim($data['coursename']))){
            $errors['coursename'] = get_string('coursecertificateerr', 'local_externalcertificate');        
        }
        return $errors;
    }
}
