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
 * Version details.
 *
 * @package    local_video
 * @copyright  akshat.c@eabyas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("$CFG->libdir/formslib.php");

class edit extends moodleform {
    // Add elements to form.
    public function definition() {

        global $CFG, $DB;

        $mform = $this->_form; // Don't forget the underscore!
       
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);


        $mform->addElement('text', 'title', get_string('vtitle','local_video')); // Add elements to your form.
        $mform->addRule('title', get_string('titleerr','local_video'), 'required', null);
        $mform->addRule('title', null, 'required');
        $mform->setType('title', PARAM_NOTAGS);                   // Set type of element.
        $maxbytes = $CFG->maxbytes;
        $mform->addElement('filemanager', 'video', get_string('video','local_video'), null, array('maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => 'MP4, MOV, WMV, AVI, MKV, WEBM, OGV'));
        $mform->addRule('video', get_string('videoerr', 'local_video'), 'required', null, 'client');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'Submit', get_string('upload', 'local_video'));
        $buttonarray[] = $mform->createElement('cancel', 'cancel', get_string('cancel', 'local_video'));
        $mform->addgroup($buttonarray, 'buttonar', '', ' ', false);

    }

    // Custom validation should be added here
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
		$form_data = data_submitted();	 	
		if ($video = $DB->get_record('local_video', array('title' => $data['title']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $video->id != $data['id']) {
                $errors['title'] = get_string('videotitleexists', 'local_video', $video->title);
            }
        } 
		if (isset($data['title'])){
			if(empty($data['title'])){
				$errors['title'] = get_string('titleerr', 'local_video');
			}
		}
        return $errors;
    }
}
