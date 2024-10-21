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

/** Configurable Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
  * @date: 2009
  */  

  //Started by rajut for adding columns for manage reports
  
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class trainingfeedbackresponses_form extends moodleform {
    function definition() {
        global $DB, $USER, $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', '', get_string('trainingfeedbackresponses','block_configurable_reports'), '');
						
		$columns = array('trainers', 'respondedempid','coursename','iltname','iltstartenddates','iltlocation','month','year', 'response1','response2','response3','response4','response5','response6', 'comment1', 'comment2');
		$reportcolumns = array();
			foreach($columns as $c){
				$reportcolumns[$c] = ucwords($c);
			}
        $mform->addElement('select', 'column', get_string('column','block_configurable_reports'), $reportcolumns);
		
		$this->_customdata['compclass']->add_form_elements($mform,$this); 
		
        // buttons
        $this->add_action_buttons(true, get_string('add'));

    }

	function validation($data, $files){
		$errors = parent::validation($data, $files);
		
		$errors = $this->_customdata['compclass']->validate_form_elements($data,$errors);
		
		return $errors;
	}	
	
}
  //Ended by rajut for adding columns for manage reports

