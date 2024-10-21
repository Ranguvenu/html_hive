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
 * local courses
 *
 * @package    local_courses
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/course/lib.php');


/**
 * Upload a file CVS file with course information.
 *
 * @package    tool_uploadcourse
 * @copyright  eAbyas <www.eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_uploadcourse_category_form extends tool_uploadcourse_base_form {

    /**
     * The standard form definiton.
     * @return void
    */
    public function definition () {
        global $USER, $DB, $CFG;
        $maxbytes = $CFG->maxbytes;

        $mform = $this->_form;
        $mform->setDisableShortforms(true);
        //$mform->addElement('header', 'generalhdr', get_string('general'));

        $mform->addElement('filepicker', 'coursecategoryfile', get_string('coursefile', 'local_courses'), null, array('maxbytes' => $maxbytes, 'accepted_types' => '.csv'));
        $mform->addRule('coursecategoryfile', null, 'required');
        $mform->addHelpButton('coursecategoryfile', 'coursefile', 'tool_uploadcourse');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'local_courses'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }
        $mform->addHelpButton('delimiter_name', 'csvdelimiter', 'tool_uploadcourse');

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'local_courses'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
        $mform->addHelpButton('encoding', 'encoding', 'tool_uploadcourse');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'local_courses'), $choices);
        $mform->setType('previewrows', PARAM_INT);
        $mform->addHelpButton('previewrows', 'rowpreviewnum', 'tool_uploadcourse');

     //   $this->add_import_options();

        $mform->addElement('hidden', 'showpreview', 1);
        $mform->setType('showpreview', PARAM_INT);

        $this->add_action_buttons(false, get_string('preview', 'local_courses'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        return $errors;
    }
}
