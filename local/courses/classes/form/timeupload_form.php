<?php
namespace local_courses\form;
defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/formslib.php';

use moodleform;
use csv_import_reader;
use core_text;
define('ONLY_ADD', 1);
define('ONLY_UPDATE', 2);
define('ADD_UPDATE', 3);
define('MANUAL_ENROLL', 1);//Added revathi
class timeupload_form extends moodleform {
   
   function definition() {
		$mform = $this->_form;

		//$mform->addElement('header', 'settingsheader', get_string('upload'));

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
        

		$this->add_action_buttons(true, get_string('upload'));
	}
}
