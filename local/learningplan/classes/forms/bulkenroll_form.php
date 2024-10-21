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

namespace local_learningplan\forms;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');
use moodleform;

/**
 * Class bulkenroll_form
 *
 * @package    local_trainingcourses
 * @copyright  2023 Moodle India Information Solutions Pvt Ltd
 * @author     Narendra Patel <narendra.patel@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulkenroll_form extends moodleform {

    /**
     * Form definition
     */
    public function definition() {
        global $DB,$CFG;
        $mform = $this->_form;

        $lpid = optional_param('lpid', 0, PARAM_INT);
        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_URL);
        
        $mform->addElement('html', '<h3>'. get_string('userbulkenroll', 'local_learningplan', get_string('pluginname', 'local_learningplan')).'</h3>');
        $mform->addElement('html', get_string('employeeidhelp', 'local_learningplan'));

        $filepickeroptions = array(
            'accepted_types' => array(get_string('csv', 'local_learningplan')),
            'maxbytes' => 0,
            'maxfiles' => 1,
        );
        $mform->addElement('filemanager', 'userfiles', get_string('file'), null, $filepickeroptions);
        $mform->addRule('userfiles', get_string('filerequired', 'local_learningplan'), 'required', null);
        $mform->addHelpButton('userfiles', 'uploaddoc', 'local_learningplan', get_string('pluginname', 'local_learningplan'));

        $mform->addElement('hidden', 'lpid', $lpid);

        $this->add_action_buttons(false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
