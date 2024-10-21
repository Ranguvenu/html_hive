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
 * doselect configuration form
 *
 * @package mod_doselect
 * @copyright  2019 Anilkumar Cheguri (anil@eabyas.in)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/doselect/locallib.php');
require_once($CFG->dirroot.'/mod/doselect/classes/doselect.php');
require_once($CFG->libdir.'/filelib.php');

class mod_doselect_mod_form extends moodleform_mod {

    public static $datefieldoptions = array('optional' => true, 'step' => 1);

    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // $config = get_config('doselect');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        $mform->setType('name', PARAM_CLEANHTML);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // $doselectlist = array(null=>get_string('select'),
        //                     'test'=>'test assessment');
        $doselect = new doselect();
        $assessmentslist = $doselect->doselect_assessmentslist(true);
        $selectarray = array(null => '-- Select --');
        $doselectlist = $selectarray + $assessmentslist;
        $mform->addElement('select', 'doselect', get_string('pluginname','doselect'), $doselectlist);
        $mform->addRule('doselect', null, 'required', null, 'client');

        // Number of attempts.
        $attemptoptions = array('0' => get_string('unlimited'));
        for ($i = 1; $i <= DOSELECT_MAX_ATTEMPT_OPTION; $i++) {
            $attemptoptions[$i] = $i;
        }
        $mform->addElement('select', 'attempts', get_string('attemptsallowed', 'quiz'),$attemptoptions);

        $mform->addElement('text', 'mingrade', get_string('mingrade', 'doselect'),array('size'=>5,'pattern'=>"^\d*(\.\d{0,2})?$"));
        $mform->setType('mingrade', PARAM_FLOAT);
        $mform->addRule('mingrade', null, 'required', null, 'client');
        $mform->addRule('mingrade', null, 'numeric', null, 'client');

        $mform->addElement('text', 'maxgrade', get_string('maxgrade', 'doselect'),array('size'=>5,'pattern'=>"^\d*(\.\d{0,2})?$"));
        $mform->setType('maxgrade', PARAM_FLOAT);
        $mform->addRule('maxgrade', null, 'required', null, 'client');
        $mform->addRule('maxgrade', null, 'numeric', null, 'client');

        $mform->addElement('text', 'gradepass', get_string('gradepass', 'doselect'),array('size'=>5,'pattern'=>"^\d*(\.\d{0,2})?$"));
        $mform->setType('gradepass', PARAM_FLOAT);
        $mform->addRule('gradepass', null, 'required', null, 'client');
        $mform->addRule('gradepass', null, 'numeric', null, 'client');
        

        // start and end dates.
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'doselect'),
                self::$datefieldoptions);

        $mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'doselect'),
                self::$datefieldoptions);


        $this->standard_intro_elements();

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $items = array();

        $group = array();
        $group[] = $mform->createElement('advcheckbox', 'completionpass', null, get_string('completionpass', 'quiz'),
                array('group' => 'cpass'));

        // $group[] = $mform->createElement('advcheckbox', 'completionattemptsexhausted', null,
        //         get_string('completionattemptsexhausted', 'quiz'),
        //         array('group' => 'cattempts'));
        $mform->disabledIf('completionattemptsexhausted', 'completionpass', 'notchecked');
        $mform->addGroup($group, 'completionpassgroup', get_string('completionpass', 'quiz'), ' &nbsp; ', false);
        $mform->addHelpButton('completionpassgroup', 'completionpass', 'quiz');
        $items[] = 'completionpassgroup';
        return $items;
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionattemptsexhausted']) || !empty($data['completionpass']);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        if ($data['starttime'] != 0 && $data['endtime'] != 0 && ($data['endtime'] < $data['starttime'])) {
            $errors['endtime'] = get_string('endbeforstart', 'doselect');
        }

        if (($data['mingrade'] != 0) && ($data['maxgrade'] < $data['mingrade'])) {
            $errors['maxgrade'] = get_string('mingradeerror', 'doselect');
        }


        if (($data['passgrade'] != 0 && $data['maxgrade'] != 0) && ($data['passgrade'] > $data['maxgrade'])) {
            $errors['passgrade'] = get_string('passgradeerror', 'doselect');
        }

        if (($data['passgrade'] != 0 && $data['mingrade'] != 0) && ($data['passgrade'] < $data['mingrade'])) {
            $errors['passgrade'] = get_string('passgrade_notless_mingrade_error', 'doselect');
        }

        return $errors;

    }
}
