<?php
namespace local_courses\form;

defined('MOODLE_INTERNAL') || die();
use core;
use moodleform;
use context_system;
use csv_import_reader;
use core_text;
define('credits', 1);
define('duration', 2);
define('levels', 3);
define('credits_duration_levels', 4);
define('MANUAL_ENROLL', 1);//Added revathi

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');


class credits_form extends moodleform {
	function definition() {

		$mform = $this->_form;

		$mform->addElement('filepicker', 'userfile', get_string('file'));
		$mform->addRule('userfile', null, 'required');
		
		$mform->addElement('hidden',  'delimiter_name');
		$mform->setType('delimiter_name', PARAM_TEXT);
		$mform->setDefault('delimiter_name',  'comma');


		$mform->addElement('hidden',  'encoding');
		$mform->setType('encoding', PARAM_RAW);
		$mform->setDefault('encoding',  'UTF-8');

		$mform->addElement('hidden', 'enrollmentmethod');
		$mform->setType('enrollmentmethod', PARAM_INT);
		$mform->setDefault('enrollmentmethod', MANUAL_ENROLL);	

		$options = array(null=>'---Select---',credits => 'Credits', duration => 'Duration', levels => 'Levels', credits_duration_levels => 'Both Credits,Duration and Levels');
		$mform->addElement('select', 'option', get_string('options', 'local_users'), $options);
          $mform->addRule('option', null, 'required', null, 'client');
		$mform->setType('option', PARAM_INT);

		$this->add_action_buttons(true, get_string('upload'));
	}
}
