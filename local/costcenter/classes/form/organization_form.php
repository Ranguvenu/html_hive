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
require_once($CFG->dirroot . '/local/costcenter/lib.php');
class organization_form extends moodleform { /*costcenter creation form*/

    public function definition() {
        global $USER, $CFG,$DB;
        $costcenter = new \costcenter();
        $corecomponent = new core_component();

        $mform = $this->_form;
        $id = $this->_customdata['id'];

 
        $parentid = $this->_customdata['parentid']; 
        $formtype = $this->_customdata['formtype'];
        $headstring = $this->_customdata['headstring'];

        $systemcontext = context_system::instance();
       
        if($formtype != 'organization'){
            if($formtype == 'department'){
                $parent_label = get_string('organization', 'local_costcenter');
                $departmentsql = "SELECT lc.id, lc.fullname 
                    FROM {local_costcenter} AS lc WHERE lc.depth = 1 ";
            }else if($formtype == 'subdepartment'){
                $parent_label = get_string('department', 'local_costcenter');
                $subdepartmentsql = "SELECT lc.id, CONCAT(llc.fullname,' / ',lc.fullname) AS fullname 
                    FROM {local_costcenter} AS lc 
                    JOIN {local_costcenter} AS llc ON llc.id=lc.parentid 
                    WHERE lc.depth = 2 ";
            }
            if($id){
                $parentid = $DB->get_field('local_costcenter', 'parentid', array('id' => $id));
                $departmentsql .= " AND lc.id = {$parentid} ";
                $subdepartmentsql .= " AND lc.id = {$parentid} ";
            }
            if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
                $departmentsql .= " AND lc.id = {$USER->open_costcenterid} ";
                $subdepartmentsql .= " AND llc.id = {$USER->open_costcenterid} "; 
            }else if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
                $subdepartmentsql .= " AND lc.id = {$USER->open_departmentid} "; 
            }
            if($formtype == 'department'){
                $options = $DB->get_records_sql_menu($departmentsql);
            }else if($formtype == 'subdepartment'){
                $options = $DB->get_records_sql_menu($subdepartmentsql);
            }
            
            if(count($options) > 1){
                $mform->addElement('select', 'parentid', $parent_label, $options);
                $mform->setType('parentid', PARAM_INT);
                $mform->addRule('parentid', get_string('orgemptymsg', 'local_costcenter'), 'required', null, 'client');
            }else{
                $parentid = array_keys($options)[0];
                $parentname = $options[$parentid];
                // $parentelement = "<span class='costcenter_form name'>$parentname</span> ";
                // $mform->addElement('html',  $parentelement);
                $mform->addElement('static',  'parentname', $parent_label, $parentname);
                $mform->addElement('hidden',  'parentid', $parentid);
                $mform->setType('parentid', PARAM_INT);
            }
        }else{
            $mform->addElement('hidden', 'parentid', 0);
            $mform->setType('parentid', PARAM_INT);
        }

        $mform->addElement('text', 'fullname', get_string('costcentername', 'local_costcenter'), array());
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('missingcostcentername', 'local_costcenter'), 'required', null, 'client');
        
        $mform->addElement('text', 'shortname', get_string('shortname','local_costcenter'), 'maxlength="100" size="20"');
        
        $mform->addRule('shortname', get_string('shortnamecannotbeempty', 'local_costcenter'), 'required', null, 'client');
            
        $mform->setType('shortname', PARAM_TEXT);
        $attributes = array('rows' => '8', 'cols' => '40');

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden',  'formtype',  $formtype);
        $mform->setType('formtype', PARAM_TEXT);

        $mform->addElement('hidden',  'headstring', $headstring);
        $mform->setType('headstring', PARAM_TEXT);

        $now = date("d-m-Y");
        $now = strtotime($now);
        $mform->addElement('hidden', 'timecreated', $now);
        $mform->setType('timecreated', PARAM_RAW);
        
        $mform->addElement('hidden', 'usermodified', $USER->id);
        $mform->setType('usermodified', PARAM_RAW);

        if($formtype == 'organization'){
            $theme_epsilon_plugin_exist = $corecomponent::get_plugin_directory('theme', 'epsilon');
            if(!empty($theme_epsilon_plugin_exist)){


            $radioarray=array();

            $radioarray[] =& $mform->createElement('radio','theme','', get_string('scheme_1', 'local_costcenter'),'scheme1',array('class' => 'first'));
            $radioarray[] =& $mform->createElement('radio','theme','', get_string('scheme_2', 'local_costcenter'),'scheme2',array('class' => 'second'));
            $radioarray[] =& $mform->createElement('radio','theme','', get_string('scheme_3', 'local_costcenter'),'scheme3',array('class' => 'third'));
            $radioarray[] =& $mform->createElement('radio','theme','', get_string('scheme_4', 'local_costcenter'),'scheme4',array('class' => 'fourth'));
            $radioarray[] =& $mform->createElement('radio','theme','', get_string('scheme_5', 'local_costcenter'),'scheme5',array('class' => 'fifth'));
            $radioarray[] =& $mform->createElement('radio','theme','', get_string('scheme_6', 'local_costcenter'),'scheme6',array('class' => 'sixth'));

                $mform->addGroup($radioarray,'theme',get_string('preferredscheme', 'local_costcenter'), array(''), false);

            $iconstyle=array();
            $default = 'circle';
            $iconstyle[] =& $mform->createElement('radio','shell','', get_string('square', 'theme_epsilon'),'square',array('class' => 'square'));
            $iconstyle[] =& $mform->createElement('radio','shell','', get_string('rounded', 'theme_epsilon'),'circle',array('class' => 'circle'));
            $iconstyle[] =& $mform->createElement('radio','shell','', get_string('rounded-square', 'theme_epsilon'),'rounded',array('class' => 'rounded'));

                $mform->addGroup($iconstyle,'shell',get_string('iconstyle', 'local_costcenter'), array(''), false);


                // $choices = array('scheme1' => get_string('scheme_1', 'theme_epsilon'),
                //                  'scheme2' => get_string('scheme_2', 'theme_epsilon'),
                //                  'scheme3' => get_string('scheme_3', 'theme_epsilon'),
                //                  'scheme4' => get_string('scheme_4', 'theme_epsilon'),
                //                  'scheme5' => get_string('scheme_5', 'theme_epsilon'),
                //                  'scheme6' => get_string('scheme_6', 'theme_epsilon')
                //          );
                // $mform->addElement('select', 'theme', get_string('preferredscheme', 'local_costcenter'), $choices);
            
            }
            $logoupload = array('maxbytes'     => $CFG->maxbytes,
                              'subdirs'        => 0,                             
                              'maxfiles'       => 1,                             
                              'accepted_types' => 'web_image');
            $mform->addElement('filemanager', 'costcenter_logo', get_string('costcenter_logo', 'local_costcenter'), '', $logoupload);
        }



        $submit = ($id > 0) ? get_string('update_costcenter', 'local_costcenter') : get_string('create', 'local_costcenter');
        $this->add_action_buttons('false', $submit);
    }

    /**
     * validates costcenter name and returns instance of this object
     *
     * @param [object] $data 
     * @param [object] $files 
     * @return costcenter validation errors
     */
     public function validation($data, $files) {
        global $COURSE, $DB, $CFG;
        $errors = parent::validation($data, $files);
        // fix for OL01 issue by mahesh
        if(empty(trim($data['shortname']))){
            $errors['shortname'] = get_string('shortnamecannotbeempty', 'local_costcenter');
        }
        if(empty(trim($data['fullname']))){
            $errors['fullname'] = get_string('fullnamecannotbeempty', 'local_costcenter');
        }
        // OL01 fix ends.
        if ($DB->record_exists('local_costcenter', array('shortname' => trim($data['shortname'])), '*', IGNORE_MULTIPLE)) {
            $costcenter = $DB->get_record('local_costcenter', array('shortname' => trim($data['shortname'])), '*', IGNORE_MULTIPLE);
            if (empty($data['id']) || $costcenter->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametakenlp', 'local_costcenter', $costcenter->shortname);
            }
        }
        return $errors;
     }
     
}
