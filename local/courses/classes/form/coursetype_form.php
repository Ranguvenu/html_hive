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
class coursetype_form extends moodleform {

    public function definition() {
        global $USER,$DB,$CFG;

		$contextid = optional_param('contextid', 1, PARAM_INT);
		$mform    =& $this->_form;
        $id = $this->_customdata['id'];
        $course_type = $this->_customdata['course_type'];
		$shortname = $this->_customdata['shortname'];
		$maxbytes = $CFG->maxbytes;

		if(in_array($shortname,array('learningpath', 'ilt', 'mooc','e-learning'))){
	
			$mform->addElement('static', 'coursetype_static', get_string('course_type','local_courses'), $course_type);
			$mform->addElement('hidden', 'coursetype'); 
			$mform->setType('coursetype', PARAM_TEXT);
			$mform->setConstant('coursetype', $course_type);
        
			$mform->addElement('static', 'coursetypeshortname_static', get_string('course_type_shortname','local_courses'), $shortname);
			$mform->addElement('hidden', 'coursetypeshortname'); 
			$mform->setType('coursetypeshortname', PARAM_TEXT);
			$mform->setConstant('coursetypeshortname', $shortname);

			
			
		}else{
			$mform->addElement('text', 'coursetype', get_string('course_type','local_courses'), 'maxlength="100" size="10" ');
			$mform->addRule('coursetype', get_string('required'), 'required', null);
			$mform->setType('coursetype', PARAM_RAW);
			$mform->setDefault('coursetype', $course_type);

			$mform->addElement('text', 'coursetypeshortname', get_string('course_type_shortname','local_courses'), 'maxlength="100" size="10"');				
			$mform->addRule('coursetypeshortname', get_string('required'), 'required', null);
			$mform->setType('coursetypeshortname', PARAM_RAW);
			$mform->setDefault('coursetypeshortname', $shortname);
		
		}
		
		$mform->addElement('filemanager', 'course_image', get_string('image','local_courses'), null, array('maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => 'JPEG, JPG, PNG, WEBP, TIFF, BMP, SVG, HIEF'));
       
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
		//print_R($data);die;
		//if($data['coursetypeshortname'] != 'learningpath' && $data['coursetypeshortname'] != "ilt"){
			if ($coursetype = $DB->get_record('local_course_types', array('course_type' => $data['coursetype']), '*', IGNORE_MULTIPLE)) {
				if (empty($data['id']) || $coursetype->id != $data['id']) {
					$errors['coursetype'] = get_string('coursetypeexists', 'local_courses', $coursetype->course_type);
				}
			}  
			
			if (isset($data['coursetype']) && !in_array($data['coursetype'] , array('learningpath', 'ilt', 'mooc','e-learning'))){
				if(empty($data['coursetype'])){
					$errors['coursetype'] = get_string('err_coursetype', 'local_courses');
				}
			}

			if (isset($data['coursetypeshortname'])){
				if(empty($data['coursetypeshortname'])){
					$errors['coursetypeshortname'] = get_string('err_coursetypeshortname', 'local_courses');
				}
			} 
		//}

		return $errors;
    }

	
}
