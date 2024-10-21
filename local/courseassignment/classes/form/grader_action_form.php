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
 * @subpackage courseassignment
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseassignment\form;
use moodleform;
use core_component;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/lib.php');
class grader_action_form extends moodleform {

    public function definition() {
        global $USER,$DB;

		$contextid = optional_param('contextid', 1, PARAM_INT);
		$mform    = $this->_form;
        $moduleid = $this->_customdata['moduleid'];
        $method = $this->_customdata['method'];
		$userid = $this->_customdata['userid'];
		$courseid = $this->_customdata['courseid'];
		$options = $this->_customdata['options'];
		$dataoptions = $this->_customdata['dataoptions'];
		$filterdata = $this->_customdata['filterdata'];
		
		$user = $DB->get_record('user', array('id'=>$userid));
		//echo get_string($method.'_reason','local_courseassignment');die;
		$mform->addElement('html', '<p style="font-size:15px">'.get_string($method.'_gradermessage', 'local_courseassignment', format_string($user->firstname)).'</p>');
        $mform->addElement('textarea', $method.'reason', get_string($method.'_reason','local_courseassignment'));
        $mform->addRule($method.'reason', get_string('required'), 'required', null);
        $mform->setType($method.'reason', PARAM_RAW);
	
		//$mform->addElement('html',html_writer::tag('span', 'Please specify the reason</span>', array('class' => 'specifyreason', 'style' => 'display:none;color:red;')));
		$mform->addElement('html', '<span class="specifyreason" style = "display:none;color:red;padding-left: 30%" >Please specify the reason</span><br>');

		$mform->addElement('hidden', 'moduleid');
		$mform->setType('moduleid', PARAM_INT);
		$mform->setDefault('moduleid', $moduleid);

		$mform->addElement('hidden', 'contextid');
		$mform->setType('contextid', PARAM_INT);
		$mform->setDefault('contextid', $contextid);

		$mform->addElement('hidden', 'method');
		$mform->setType('method', PARAM_RAW);
		$mform->setDefault('method', $method);

		$mform->addElement('hidden', 'courseid');
		$mform->setType('courseid', PARAM_INT);
		$mform->setDefault('courseid', $courseid);

		$mform->addElement('hidden', 'userid');
		$mform->setType('userid', PARAM_INT);
		$mform->setDefault('userid', $userid);
		
		$mform->addElement('hidden', 'options');
		$mform->setType('options', PARAM_RAW);
		$mform->setDefault('options', $options);

		$mform->addElement('hidden', 'dataoptions');
		$mform->setType('dataoptions', PARAM_RAW);
		$mform->setDefault('dataoptions', $dataoptions);

		$mform->addElement('hidden', 'filterdata');
		$mform->setType('filterdata', PARAM_RAW);
		$mform->setDefault('filterdata', $filterdata);
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
		
		if (isset($data['reason'])){
			if(empty($data['reason'])){
				$errors['reason'] = get_string('err_reason', 'local_courseassignment');
			}
		} 

		return $errors;
    }

	
}
