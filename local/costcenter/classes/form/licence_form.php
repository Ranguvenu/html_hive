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
 * local local_costcenter
 *
 * @package    local_costcenter
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_costcenter\form;

use core;
use moodleform;
use context_system;
use core_component;

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->libdir . '/filelib.php');

class licence_form extends moodleform {

    public function definition() {
        global $CFG, $DB;
        // $corecomponent = new core_component();

        $mform = $this->_form;
        // $id = $this->_customdata['licencekey'];
        
        $mform->addElement('text', 'licencekey', get_string('licencekey','local_costcenter'));
        $mform->addRule('licencekey', get_string('licencekeynotemptymsg', 'local_costcenter'), 'required', null, 'client');
        $mform->setType('licencekey', PARAM_RAW);

        // $this->add_action_buttons('false', $submit);

        $mform->disable_form_change_checker();
    }

    /**
     * validates licencekey
     *
     * @param [object] $data 
     * @param [object] $files 
     * @return costcenter validation errors
     */
    public function validation($data, $files) {
        global $COURSE, $DB, $CFG;
        $errors = parent::validation($data, $files);
        
        if(empty(trim($data['licencekey']))){
            $errors['licencekey'] = get_string('licencekeycannotbeempty', 'local_costcenter');
        }

        $curl = new \curl;

        $params['serial'] = $data['licencekey'];
        $params['surl'] = $CFG->wwwroot;

        $param = json_encode($params);

        $json = $curl->post('http://sitev2.bizlms.net/?wc-api=custom_validate_serial_key', $param);
        $response = (object)json_decode($json);

        if ($response->success != 'true') {
            $errors['licencekey'] = $response->message;
        }
        
        return $errors;
    }
     
}
