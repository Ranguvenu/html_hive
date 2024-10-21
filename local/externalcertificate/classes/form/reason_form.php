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
 * @author eabyas
 */
/**
 * Assign roles to users.
 * @package    local
 * @subpackage local_external_certificate
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_externalcertificate\form;
use moodleform;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/lib.php');
class reason_form extends moodleform {

    public function definition() {
        global $USER,$DB;

		// $id = optional_param('id', 1, PARAM_INT);
		$mform    = $this->_form;
        $id = $this->_customdata['id'];
		$status = $this->_customdata['status'];

		$result = $DB->get_record('local_external_certificates',['id' =>$id]);
        foreach ($result as $value) {
            // code...
            $value = $result->username;
        }
        
		$mform->addElement('html', '<p style="font-size:15px">'.get_string('reason_form_heading','local_externalcertificate',$value).'</p>');


        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'status', $status);
        $mform->setType('status', PARAM_INT);

        $mform->addElement('textarea', 'reason', get_string('label','local_externalcertificate'));
        $mform->addRule('reason', get_string('reasonerr','local_externalcertificate'), 'required', null);
        $mform->addRule('reason', null, 'required');
        $mform->setType('reason', PARAM_RAW);
	}

	   /**
     * Validates the data submit for this form.
     *
     * @param array $data An array of key,value data pairs.
     * @param array $files Any files that may have been submit as well.
     * @return array An array of errors.
     */
    // public function validation($data, $files) {
	// 	global $DB;
    //     $errors = parent::validation($data, $files);
	// 	$form_data = data_submitted();	 	
		
	// 	if ($data['reason'] == null || ''){
    //         $errors['reason'] = get_string('reasonerr', 'local_external_certificate');
	// 	} 

	// 	return $errors;
    // }

	
}
