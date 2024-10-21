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
 * local classroom
 *
 * @package    local_classroom
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_classroom\form;
use core;
use moodleform;
use context_system;
require_once($CFG->dirroot . '/lib/formslib.php');
 
class uploadsessionattendance_form extends moodleform {

	function definition() {
		global $CFG,$DB;
		$mform = & $this->_form;
		$id = $this->_customdata['id'];
		$sid = $this->_customdata['sid'];
		$cid = $this->_customdata['cid'];
	 

		$mform->addElement('hidden', 'sid', $sid);
		$mform->setType('sid', PARAM_INT);

		$mform->addElement('hidden','cid',$cid);
		$mform->setType('cid',PARAM_INT);
 

		$mform->addElement('filepicker', 'attachment', get_string('session_attachment', 'local_classroom'));
		$mform->addRule('attachment', null, 'required');
		
		$choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
		
		$this->add_action_buttons(true, get_string('uploadattendance','local_classroom'));
	}

	function validation($data, $files) {
		$errors = parent :: validation($data, $files);
		return $errors;
	}
}