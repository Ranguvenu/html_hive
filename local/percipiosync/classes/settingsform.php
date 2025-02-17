<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This percipiosync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This percipiosync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this percipiosync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * percipiosync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_percipiosync
 */
use local_percipiosync\plugin;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require($CFG->libdir . '/formslib.php');

class local_percipiosync_form extends moodleform {

    /**
     *
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsform', get_string('cfgformheading',plugin::COMPONENT));
        $mform->setExpanded('settingsform', true);

        /* Enabled - used to check that the plugin has been configured */
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled',plugin::COMPONENT));        // null, array('group' => 1), array(0, 1));
        $mform->setType('enabled', PARAM_INT);
        $mform->setDefault('enabled', $this->_customdata['enabled']);
        $mform->addHelpButton('enabled', 'enabled',plugin::COMPONENT);

        /* API URL -  https://docs.microsoft.com/en-us/learn/support/catalog-api */
        $mform->addElement('text', 'apiurl', get_string('apiurl',plugin::COMPONENT));
        $mform->setType('apiurl', PARAM_URL);
        $mform->addHelpButton('apiurl', 'apiurl',plugin::COMPONENT);
        $mform->setDefault('apiurl', $this->_customdata['apiurl']);

         $mform->addElement('text', 'accountid', get_string('accountid',plugin::COMPONENT));
        $mform->setType('accountid', PARAM_TEXT);
        $mform->addHelpButton('accountid', 'accountid',plugin::COMPONENT);
        $mform->setDefault('accountid', $this->_customdata['accountid']);

        // ClientId – the client id to be used with the API calls – required.
        $mform->addElement('text', 'clientid', get_string('clientid',plugin::COMPONENT));
        $mform->setType('clientid', PARAM_TEXT);
        $mform->addHelpButton('clientid', 'clientid',plugin::COMPONENT);
        $mform->setDefault('clientid', $this->_customdata['clientid']);


         $mform->addElement('text', 'clientsecret', get_string('clientsecret',plugin::COMPONENT));
        $mform->setType('clientsecret', PARAM_TEXT);
        $mform->addHelpButton('clientsecret', 'clientsecret',plugin::COMPONENT);
        $mform->setDefault('clientsecret', $this->_customdata['clientsecret']);
        
          $mform->addElement('text', 'syncfromdate', get_string('syncfromdate',plugin::COMPONENT));
        $mform->setType('syncfromdate', PARAM_TEXT);
        $mform->addHelpButton('syncfromdate', 'syncfromdate',plugin::COMPONENT);
        $mform->setDefault('syncfromdate', $this->_customdata['syncfromdate']);
        
        $mform->addElement('text', 'synctdate', get_string('synctdate',plugin::COMPONENT));
        $mform->setType('synctdate', PARAM_TEXT);
        $mform->addHelpButton('synctdate', 'synctdate',plugin::COMPONENT);
        $mform->setDefault('synctdate', $this->_customdata['synctdate']);
        
        /* Course Category – the course category that the courses will be synchronised to – defaults to the top category. */
        $crsecats = core_course_category::make_categories_list('', 0, ' / ');
        $crsecats = array('0' => get_string('ccategoriesoption',plugin::COMPONENT)) + $crsecats;
        $mform->addElement('select', 'ccategories', get_string('ccategories',plugin::COMPONENT), $crsecats);
        $mform->setType('ccategories', PARAM_TEXT);
        $mform->addHelpButton('ccategories', 'ccategories',plugin::COMPONENT);
        if ($this->_customdata['ccategories'] > 0) {
            $mform->setDefault('ccategories', $this->_customdata['ccategories']);
        }

        /* Course Field Mappings */
        $mform->addElement('header', 'settingsform', get_string('cfgformcrseheading',plugin::COMPONENT));
        $mform->setExpanded('settingsform', true);

        $customcourseoptions = $this->makeoptions($this->_customdata['coursecustomfields']);
        $customcourseoptions = array('course_none' => get_string('cfhformnoteselected',plugin::COMPONENT)) + $customcourseoptions;
        foreach ($this->_customdata['coursemappingfields'] as $crsefld => $fldvalue) {
            $formfldname = 'course_' . $crsefld;
            if ($fldvalue == '-CUSTOM-') {
                $mform->addElement('select', $formfldname, $crsefld, $customcourseoptions);
                $mform->setType($formfldname, PARAM_TEXT);
                if (!empty($this->_customdata['coursemappings'][$crsefld])) {
                    $mform->setDefault($formfldname, $this->_customdata['coursemappings'][$crsefld]);
                }
            } else {        // this is a course field.coursecustomfields
                $mform->addElement('static', $formfldname, $crsefld, $fldvalue);
                $mform->setType($formfldname, PARAM_TEXT);
            }

        }
        /* Enabled - used to check that the plugin has been configured */
        $mform->addElement('advcheckbox', 'coursecustomfilesenabled', get_string('coursecustomfiles',plugin::COMPONENT), get_string('coursecustomfilesenabled',plugin::COMPONENT));      
        $mform->setType('coursecustomfilesenabled', PARAM_INT);
        $mform->setDefault('coursecustomfilesenabled', $this->_customdata['coursecustomfilesenabled']);
        $mform->addHelpButton('coursecustomfilesenabled', 'coursecustomfilesenabled',plugin::COMPONENT);

             

        $this->add_action_buttons(false);
    }

    /**
     * Turn the mappings into an options list for the selects.
     *
     * @param array of stdClass $dbrecords
     * @return array
     */
    private function makeoptions($dbrecords) {
        $options = array();
        foreach ($dbrecords as $dbrecord) {
            $options[$dbrecord->shortname] = $dbrecord->fullname;
        }
        return $options;
    }

    /**
     *
     * {@inheritDoc}
     * @see moodleform::validation()
     */
    function validation($data, $files) {
        $errors = array();

        $coursefields = array();
        $progamfields = array();

        // We want to ensure that we have no duplicate mapped fields
        foreach ($data as $fld => $val) {
            if (strpos($fld, 'course_') === 0) {
                if ($val !== 'course_none') {       // Not mapped does not count
                    if (in_array($val, $coursefields)) {
                        $errors[$fld] = get_string('cfhformduplicaterr',plugin::COMPONENT);
                    } else {
                        $coursefields[] = $val;
                    }
                }
            } 
        }

        return $errors;
    }



}
