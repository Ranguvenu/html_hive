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
class featuredcourse_form extends moodleform {

    public function definition() {
        global $USER,$DB;

		$contextid = optional_param('contextid', 1, PARAM_INT);
		$featured_id =  $this->_customdata['featured_id'];
		$featured_title =  $this->_customdata['title'];
        $mform = $this->_form;

	    $mform->addElement('text', 'title', get_string('title','local_courses'), array('size' => '30'),$featured_title );
        $mform->addRule('title', get_string('required'), 'required', null);
        $mform->setType('title', PARAM_TEXT);

		$featuredcourses =array();

	    $coursetypessql = "SELECT id FROM {local_course_types} WHERE shortname NOT IN ('learningpath') ";
        $coursetypes = $DB->get_fieldset_sql($coursetypessql);
        $coursetypes = implode(",",$coursetypes );

	    $fromsql = "SELECT c.id, c.fullname FROM {course} c 
							 WHERE c.id > 0 AND c.visible = 1 AND c.open_identifiedas IN ($coursetypes)  ";
		
        $ordersql= " ORDER BY c.id DESC";     

		$featured_courses_list = $DB->get_records_sql($fromsql .$ordersql);
		foreach($featured_courses_list as $courses){
			$featuredcourses[$courses->id] =  $courses->fullname;
		}
		                                                                                                                
		$options = array(                                                                                                           
			'multiple' => true,                                                  
			'noselectionstring' => get_string('selectcourse','local_courses'),                                                                
		);         
		$mform->addElement('autocomplete', 'course', get_string('selectcourse','local_courses'), $featuredcourses, $options);	
		$lpfromsql="SELECT id, name as fullname FROM {local_learningplan} AS lp WHERE lp.id > 0  AND selfenrol = 1";
		$lpordersql= " ORDER BY lp.id DESC";    
		
		$featured_lpaths_list = $DB->get_records_sql($lpfromsql .$lpordersql);
		foreach($featured_lpaths_list as $lp){
			$featuredlpaths[$lp->id] =  $lp->fullname;
		}	                                                                                                                      
		$options = array(                                                                                                           
			'multiple' => true,                                                  
			'noselectionstring' => get_string('selectlpaths','local_courses'),                                                                
		);         
		$mform->addElement('autocomplete', 'learningpaths', get_string('selectlpaths','local_courses'), $featuredlpaths, $options);     
		$mform->setType('id', PARAM_RAW);		
		$mform->disable_form_change_checker();
		
		if(empty($featured_id) || $featured_id == 0){
			$featured_course = 0;
		}

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', $featured_id);

		$mform->addElement('hidden', 'contextid');
		$mform->setType('contextid', PARAM_INT);
		$mform->setDefault('contextid', $contextid);
		 
		$this->add_action_buttons($cancel = null,get_string('featured_course', 'local_courses'));
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

		if (isset($data['title'])){
			if(empty($data['title'])){ // || !preg_match ("/^[a-zA-Z\s]+$/",$data['title'])
				$errors['title'] = get_string('err_title', 'local_courses');
			}
		}

		if (isset($data['course']) && isset($data['learningpaths'])){
			if(empty($data['course']) && empty($data['learningpaths'])){
				$errors['course'] = get_string('err_courses', 'local_courses');
				$errors['learningpaths'] = get_string('err_learningpaths', 'local_courses');
			}
		}

	/* 	if (!isset($data['course']) || !isset($data['lpaths'])){
			if(empty($data['course']) || empty($data['lpaths'])){
				$errors['course'] = get_string('err_courses', 'local_courses');
			}
		} */
	

		return $errors;
    }

	
}
