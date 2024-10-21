<?php
namespace local_users\forms;
		defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir . '/formslib.php';

use moodleform;
use csv_import_reader;
use core_text;
define('ONLY_ADD', 1);
define('ONLY_UPDATE', 2);
define('ADD_UPDATE', 3);
//Add <Revathi>
define('MANUAL_ENROLL', 1);
define('LDAP_ENROLL', 2);
//End
class hrms_async extends moodleform{


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

        $enrollmentmethod = array(null=>'---Select---',LDAP_ENROLL=>'Ldap',MANUAL_ENROLL=>'Manual');
		$mform->addElement('select', 'enrollmentmethod', get_string('authenticationmethods', 'local_users'), $enrollmentmethod);
        $mform->addRule('enrollmentmethod', null, 'required', null, 'client');
		$mform->setType('enrollmentmethod', PARAM_INT);
        
        $options = array(null=>'---Select---',ONLY_ADD=>'Only Add', ONLY_UPDATE=>'Only Update', ADD_UPDATE=>'Both Add and Update');
		$mform->addElement('select', 'option', get_string('options', 'local_users'), $options);
        $mform->addRule('option', null, 'required', null, 'client');
		$mform->setType('option', PARAM_INT);

		$this->add_action_buttons(true, get_string('upload'));
	}

}