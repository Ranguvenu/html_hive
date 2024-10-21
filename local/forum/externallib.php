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
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_forum
 */


defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class local_forum_external extends external_api {

    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_forum_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the forums'),
                'form_status' => new external_value(PARAM_INT, 'form status 0,1'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }
    
    /**
     * Submit the forum form.
     * @param int $contextid The context id for the system.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new forum id.
     */
    public static function submit_create_forum_form($contextid, $form_status, $jsonformdata ) {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/local/forum/forum_form.php');
        require_once($CFG->dirroot . '/local/forum/lib.php');
 
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_forum_form_parameters(), ['contextid' => $contextid, 'jsonformdata' => $jsonformdata, 'form_status'=>$form_status]);
 
        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
 
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
 
        $data = array();
        parse_str($serialiseddata, $data); 
        $warnings = array();
        // The last param is the ajax submitted data.
        $mform = new forum_form(null, array('form_status' => $form_status,'id' => $data['id']), 'post', '', null, true, $data);
        
        $validateddata = $mform->get_data();
        
        if (is_array($data['local_group']))
        $validateddata->local_group = implode(',',$data['local_group']);
        else
        $validateddata->local_group = $data['local_group'];
        if ($validateddata) {
            if ($validateddata->id > 0) {
                
                if ($validateddata->form_status == 1) {
                    $existingdata = $DB->get_record('local_forum', array('id'=>$validateddata->id));
                    if (!$validateddata->introeditor) {                    
                        $validateddata->introeditor['text'] = $existingdata->intro;
                        $validateddata->introeditor['format'] = $existingdata->introformat;
                    }
                    if (empty($validateddata->costcenterid)) {
                        $validateddata->costcenterid = $existingdata->costcenterid;
                    }
                    if (empty($validateddata->departmentid)) {
                        $validateddata->departmentid = $existingdata->departmentid;
                    }
                    if (empty($validateddata->courseid)) {
                        $validateddata->courseid = $existingdata->courseid;
                    }
                    if (empty($validateddata->local_group)) {
                        $validateddata->local_group = $existingdata->local_group;
                    }
                }
                
                
                $forumid = local_forum_update_instance($validateddata,$mform);
            } else if ($validateddata->id <= 0) {
                $forumid = local_forum_add_instance($validateddata);                
            }
            $form_status = $form_status + 1;
        } else {
            // Throw error 
            throw new moodle_exception('Error in submission');
        }
        
        $return = array(
            'id' => $forumid,
            'form_status' => $form_status);
 
        return $return;
    }
 
    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_forum_form_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'forum id'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
}
