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
 * Simple slider block for Moodle
 *
 * If You like my plugin please send a small donation https://paypal.me/limsko Thanks!
 *
 * @package   block_slider
 * @copyright 2015-2020 Kamil Łuczak    www.limsko.pl     kamil@limsko.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Class add_slider_image
 */
class add_slider_image extends moodleform {

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form; // Don't forget the underscore!
        $context = context_block::instance($this->_customdata['sliderid']);

        $mform->addElement('hidden', 'view', 'manage');
        $mform->setType('view', PARAM_NOTAGS);

        if (!empty($this->_customdata['id'])) {
            $id = $this->_customdata['id'];
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);
            $mform->addElement('header', 'events', get_string('modify_slide', 'block_slider'));
        } else {
            $mform->addElement('header', 'events', get_string('add_slide', 'block_slider'));
        }

        $mform->addElement('hidden', 'sliderid', $this->_customdata['sliderid']);
        $mform->setType('sliderid', PARAM_INT);


        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'file_type', '', get_string('image', 'block_slider'), 0);
        $radioarray[] = $mform->createElement('radio', 'file_type', '', get_string('video', 'block_slider'), 1);
        $mform->addGroup($radioarray, 'upload_type', get_string('upload_type', 'block_slider'), array(' '), false);


        $mform->addElement('text', 'slide_link', get_string('slide_url', 'block_slider'));
        $mform->setType('slide_link', PARAM_URL);

        $mform->addElement('text', 'slide_title', get_string('slide_title', 'block_slider'));
        $mform->setType('slide_title', PARAM_NOTAGS);

        for ($i = -10; $i <= 10; $i++) {
            $orderarray[$i] = $i;
        }
        $mform->addElement('select', 'slide_order', get_string('slide_order', 'block_slider'), $orderarray);
        $mform->setType('slide_order', PARAM_INT);
        $mform->setDefault('slide_order', 0);

        $maxbytes = $CFG->maxbytes;
        if (!isset($id) or $id == null) {
            $mform->addElement('filemanager', 'slide_image', get_string('slide_image', 'block_slider'), null,
                    array('maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => 'jpg, png, gif, jpeg'));
            $mform->addRule('slide_image', null, 'required', null, 'client');

            // new 
            $mform->addElement('filemanager', 'slide_video', get_string('slide_video','block_slider'), null, array('maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => 'MP4, MOV, WMV, AVI, MKV, WEBM, OGV'));
        } else {
            // Display actual photo.
          
            $mform->addElement('filemanager', 'slide_image', get_string('new_slide_image', 'block_slider'), null,
                    array('maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => 'jpg, png, gif, jpeg'));
            $mform->addElement('filemanager', 'slide_video', get_string('new_slide_video','block_slider'), null, array('maxbytes' => $maxbytes, 'maxfiles' => 1, 'accepted_types' => 'MP4, MOV, WMV, AVI, MKV, WEBM, OGV'));
        }
     
         $context2 = context_system::instance();
        if (!isset($id) or $id == null) {
           
            $editoroptions = array(
                'noclean' => false,
                'autosave' => false,
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $CFG->maxbytes,
                'trusttext' => false,
                'forcehttps' => false,
                'context' => $context2
            );

            $mform->addElement('editor', 'slide_desc', get_string('slide_desc', 'block_slider'), null, $editoroptions);
            //$mform->setType('slide_desc', PARAM_NOTAGS);
        } else {
            $editoroptions = array(
                'noclean' => false,
                'autosave' => false,
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'maxbytes' => $CFG->maxbytes,
                'trusttext' => false,
                'forcehttps' => false,
                'context' => $context2
            );


            $slide_desc = $DB->get_field('slider_slides', 'slide_desc', array('id' => $id));

            $description            = array();
            $description['text']    = $slide_desc;
            $new_desc               = $description;

            $mform->addElement('editor', 'slide_desc', get_string('slide_desc', 'block_slider'), null, $editoroptions);
            // $mform->setType('slide_desc', PARAM_NOTAGS);
            $mform->setDefault('slide_desc', $new_desc);
        }


        $mform->hideIf('slide_video', 'file_type', 'eq', 0);
        $mform->hideIf('slide_url', 'file_type', 'eq', 0);
        $mform->hideIf('slide_url', 'file_type', 'eq', 1);

        $this->add_action_buttons();
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
   
        if ($data['file_type'] == 0) {
            if ($data['slide_image'] == 0 || $data['slide_image'] == '' || $data['slide_image'] == null) {
                $errors['slide_image'] = get_string('imageerr', 'block_slider');
            }
        }

        if ($data['file_type'] == 1) {
     
            if ($data['slide_image'] == 0 || '' || null) {
                $errors['slide_image'] = get_string('imageerr', 'block_slider');
            }
        }
        return $errors;
    }
}


