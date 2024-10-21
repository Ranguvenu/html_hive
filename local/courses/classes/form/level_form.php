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
 * @subpackage courses
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courses\form;

use moodleform;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/lib.php');
class level_form extends moodleform {

    public function definition() {
        global $USER,$DB;

		$mform    =& $this->_form;
        $id = $this->_customdata['id'];
        $level = $this->_customdata['level'];

        $mform->addElement('text', 'level', get_string('level_name','local_courses'), 'maxlength="100" size="10"');
        $mform->addRule('level', get_string('required'), 'required', null);
        $mform->setType('level', PARAM_RAW);
		$mform->setDefault('level', $level);

		$mform->addElement('hidden', 'id');
		$mform->setType('id', PARAM_INT);
		$mform->setDefault('id', $id);
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
		if ($courselevel = $DB->get_record('local_levels', array('name' => $data['level']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $courselevel->id != $data['id']) {
                $errors['level'] = get_string('already_available', 'local_courses', $courselevel->name);
            }
        }

		return $errors;
    }

	
}
