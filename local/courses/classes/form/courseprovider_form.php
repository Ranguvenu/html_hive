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
 * @subpackage courses
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courses\form;
use moodleform;
use core_component;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/lib.php');
class courseprovider_form extends moodleform {

    public function definition() {
        global $USER,$DB;

		$contextid = optional_param('contextid', 1, PARAM_INT);
		$mform    =& $this->_form;
        $id = $this->_customdata['id'];
        $course_provider = $this->_customdata['course_provider'];
		$shortname = $this->_customdata['shortname'];
		
        $mform->addElement('text', 'courseprov', get_string('course_prov','local_courses'), 'maxlength="100" size="10"');
        $mform->addRule('courseprov', get_string('required'), 'required', null);
        $mform->setType('courseprov', PARAM_RAW);
		$mform->setDefault('courseprov', $course_provider);

		$mform->addElement('text', 'courseprovshortname', get_string('course_prov_shortname','local_courses'), 'maxlength="100" size="10"');
        $mform->addRule('courseprovshortname', get_string('required'), 'required', null);
        $mform->setType('courseprovshortname', PARAM_RAW);
		$mform->setDefault('courseprovshortname', $shortname);

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', $id);

		$mform->addElement('hidden', 'contextid');
		$mform->setType('contextid', PARAM_INT);
		$mform->setDefault('contextid', $contextid);
		 
		 $mform->disable_form_change_checker();
		//$this->add_action_buttons($cancel = null,get_string('featured_course', 'local_courses'));
	}

	   /**
     * Validates the data submit for this form.
     *
     * @param array $data An array of key,value data pairs.
     * @param array $files Any files that may have been submit as well.
     * @return array An array of errors.
     */
    public function validation($data, $files) {
		global $DB;
        $errors = parent::validation($data, $files);
		$form_data = data_submitted();	
		if ($courseprov = $DB->get_record('local_course_providers', array('course_provider' => $data['courseprov']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $courseprov->id != $data['id']) {
                $errors['courseprov'] = get_string('courseprovexists', 'local_courses', $courseprov->course_provider);
            }
        }  
		
		if (isset($data['courseprov'])){
			if(empty($data['courseprov'])){
				$errors['courseprov'] = get_string('err_courseprov', 'local_courses');
			}
		}
		if (isset($data['courseprovshortname'])){
			if(empty($data['courseprovshortname'])){
				$errors['courseprovshortname'] = get_string('err_coursetypeshortname', 'local_courses');
			}
		} 

		return $errors;
    }

	
}
