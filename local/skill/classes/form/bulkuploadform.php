<?php
namespace local_skill\form;

defined('MOODLE_INTERNAL') || die();
use core;
use moodleform;
use context_system;
use csv_import_reader;
use core_text;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');


class bulkuploadform extends moodleform {
	public function definition () {
			global $USER, $DB;

			$mform = $this->_form;
			$mform->setDisableShortforms(true);
			//$mform->addElement('header', 'generalhdr', get_string('general'));

			$mform->addElement('filepicker', 'coursefile', get_string('skill_file', 'local_skill'));
			$mform->addRule('coursefile', null, 'required');
			$mform->addHelpButton('coursefile', 'coursefile', 'tool_uploadcourse');

			$choices = csv_import_reader::get_delimiter_list();
			$mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'local_skill'), $choices);
			if (array_key_exists('cfg', $choices)) {
					$mform->setDefault('delimiter_name', 'cfg');
			}else if (get_string('listsep', 'langconfig') == ';') {
					$mform->setDefault('delimiter_name', 'semicolon');
			} else {
					$mform->setDefault('delimiter_name', 'comma');
			}
			$mform->addHelpButton('delimiter_name', 'csvdelimiter', 'tool_uploadcourse');

			$choices = core_text::get_encodings();
			$mform->addElement('select', 'encoding', get_string('encoding', 'local_skill'), $choices);
			$mform->setDefault('encoding', 'UTF-8');
			$mform->addHelpButton('encoding', 'encoding', 'tool_uploadcourse');

			$this->add_action_buttons(true, get_string('upload'));

	}


	/**
	 * Validation.
	 *
	 * @param array $data
	 * @param array $files
	 * @return array the errors that were found
	 */
	public function validation($data, $files) {
			global $DB;

			$errors = parent::validation($data, $files);
			return $errors;
	}
}
