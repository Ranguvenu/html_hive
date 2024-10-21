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
 * @subpackage local_onlinetest
 */

//It must be included from a Moodle page
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir . '/formslib.php');
if(file_exists($CFG->dirroot . '/local/costcenter/lib.php')){
	require_once($CFG->dirroot . '/local/costcenter/lib.php');
}
require_once('lib.php');
use local_costcenter\functions\userlibfunctions as costcenterlib;
class onlinetests_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;

        $context = context_system::instance();
        $costcenter = new costcenter();
        $mform    =& $this->_form;
        $mformajax    =& $this->_ajaxformdata;
        $id = $this->_customdata['id'];
        $this->_form->_attributes['id'] = 'onlinetest_add_update_form';

        // $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'local_onlinetests'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('employeename', 'local_onlinetests'), 'required', null, 'client');
        //$mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $getdepartmentelements = costcenterlib::department_elements($mform, $id, $context, $mformajax, 'onlinetests');        
        

        $attemptnumbers = range(0,10);
        $attemptnumbers[0] = get_string('unlimited'); 
        $mform->addElement('select',  'attempts', get_string('attempts', 'mod_quiz'), $attemptnumbers);
        $mform->setType('attempts', PARAM_INT);


        $mform->addElement('text', 'grade',get_string('maxgrade','local_onlinetests'), array('size'=>'20'));
        $mform->addRule('grade', get_string('missinggrade', 'local_onlinetests'), 'required', null, 'client');
		$mform->setType('grade', PARAM_FLOAT);
        
        $mform->addElement('text', 'gradepass', get_string('gradepass', 'local_onlinetests'));
        $mform->addRule('gradepass', get_string('missinggrade', 'local_onlinetests'), 'required', null, 'client');
        $mform->setType('gradepass', PARAM_FLOAT);

        $mform->addElement('text', 'open_points', get_string('points','local_onlinetests'));
        $mform->addHelpButton('open_points', 'open_pointsonlineexam', 'local_onlinetests');
        $mform->setType('open_points', PARAM_INT);  
		
		// Open and close dates.
		$datefieldoptions = array('optional' => true, 'step' => 1);
        $mform->addElement('date_time_selector', 'timeopen', get_string('quizopen', 'quiz'),
                $datefieldoptions);
        $mform->addHelpButton('timeopen', 'quizopenclose', 'quiz');

        $mform->addElement('date_time_selector', 'timeclose', get_string('quizclose', 'quiz'),
                $datefieldoptions);
		
		$mform->addElement('selectyesno', 'visible', get_string('visible'));
        $mform->setDefault('visible', 1);
        
        $mform->addElement('editor', 'introeditor', get_string('moduleintro'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true,  'subdirs' => true, 'autosave'=>false));
        $mform->setType('introeditor', PARAM_RAW); // no XSS prevention here, users must be trusted
        $mform->addHelpButton('introeditor','description','local_onlinetests');

        // tags
        // $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
      //  $mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'onlinetests', 'component' => 'local_onlinetests'));

        //certificate
        $core_component = new core_component();
        $certificate_plugin_exist = $core_component::get_plugin_directory('local', 'certificates');
        if($certificate_plugin_exist){
            $checkboxes = array();
            $checkboxes[] = $mform->createElement('advcheckbox', 'map_certificate', null, '', array(),array(0,1));
            $mform->addGroup($checkboxes, 'map_certificate', get_string('add_certificate', 'local_onlinetests'), array(' '), false);
            $mform->addHelpButton('map_certificate', 'add_certificate', 'local_onlinetests');


            $select = array(null => get_string('select_certificate','local_onlinetests'));

            if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context)){
                $cert_templates = $DB->get_records_menu('local_certificate',array(),'name', 'id,name');
            }else{
                $cert_templates = $DB->get_records_menu('local_certificate',array('costcenter'=>$USER->open_costcenterid),'name', 'id,name');
            }
            $certificateslist = $select + $cert_templates;

            $mform->addElement('select',  'certificateid', get_string('certificate_template','local_onlinetests'), $certificateslist);
            $mform->addHelpButton('certificateid', 'certificate_template', 'local_onlinetests');
            $mform->setType('certificateid', PARAM_INT);
            $mform->hideIf('certificateid', 'map_certificate', 'neq', 1);
        }

        
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
    }

    /**
     * Enforce validation rules here
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array
     **/
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
		if ( array_key_exists('costcenterid', $data) ) {
			if (empty($data['costcenterid']) AND ($data['id'] < 0)) {
				$errors['costcenterid'] = get_string('required');
			}
		}
        if ( array_key_exists('gradepass', $data) ) {
			if ($data['gradepass'] <= 0) {
				$errors['gradepass'] = get_string('missinggrade', 'local_onlinetests');
			}
		}
		if ( array_key_exists('grade', $data) ) {
			if ($data['grade'] <= 0) {
				$errors['grade'] = get_string('missinggrade', 'local_onlinetests');
			}
		}
        if (array_key_exists('grade', $data) AND array_key_exists('gradepass', $data)) {
			if ($data['gradepass'] > $data['grade']) {
                $errors['gradepass'] = get_string('shouldbeless', 'local_onlinetests');
            }
		}
		
		// Check open and close times are consistent.
        if ($data['timeopen'] != 0 && $data['timeclose'] != 0 &&
                $data['timeclose'] < $data['timeopen']) {
            $errors['timeclose'] = get_string('closebeforeopen', 'quiz');
        }elseif(($data['timeopen'] != 0 && $data['timeclose'] != 0) &&
                ($data['timeclose'] == $data['timeopen'])){
            $errors['timeclose'] = get_string('openclose_shoudnotsame', 'local_onlinetests');
        }

        if($data['map_certificate'] == 1 && empty($data['certificateid'])){
            $errors['certificateid'] = get_string('err_certificate','local_onlinetests');
        }

        return $errors;
    }
}
