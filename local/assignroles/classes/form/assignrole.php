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
 * @author Maheshchandra  <maheshchandra@eabyas.in>
 */
/**
 * Assign roles to users.
 * @package    local
 * @subpackage assignroles
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_assignroles\form;
use moodleform;
require_once("{$CFG->libdir}/formslib.php");
require_once($CFG->dirroot . '/local/assignroles/lib.php');

class assignrole extends moodleform {

    public function definition() {
        global $USER,$DB;
		$contextid = optional_param('contextid', 1, PARAM_INT);
        $mform = & $this->_form;
		$roleid = $this->_customdata['roleid'];
		$options = array(
            'ajax' => 'local_assignroles/form-options-selector',
            'multiple' => true,
            'data-action' => 'role_users',
            'data-options' => json_encode(array('id' => 0, 'roleid' => $roleid)),
        );
		$users =array();
        $mform->addElement('autocomplete', 'users', get_string('employees', 'local_users'), $users, $options);
        $mform->setType('users', PARAM_RAW);
		$mform->addRule('users', null, 'required', null, 'client');

		$mform->addElement('hidden', 'roleid');
		$mform->setType('roleid', PARAM_TEXT);
		$mform->setDefault('roleid', $roleid);
		
		if(!$contextid){
			$mform->addElement('text', 'contextid', get_string('contextid', 'local_assignroles'));
			$mform->setType('contextid', PARAM_TEXT);
			$mform->setDefault('contextid', $contextid);
		}else{
			$mform->addElement('hidden', 'contextid');
			$mform->setType('contextid', PARAM_TEXT);
			$mform->setDefault('contextid', $contextid);
		}

        $this->add_action_buttons($cancel = null,get_string('assign', 'local_assignroles'));
		$mform->disable_form_change_checker();
    }
}
