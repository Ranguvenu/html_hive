<?php
namespace local_ilp\forms;
require_once($CFG->libdir . '/formslib.php');
// require_once($CFG->dirroot . '/local/users/lib.php');
use moodleform;
use local_ilp\render\view as render;
use local_ilp\lib\lib as lib;
class courseenrolform extends moodleform {
	public function definition() {
        global $USER, $DB, $CFG;
        $mform = $this->_form;

        $planid = $this->_customdata['planid'];
        $condition = $this->_customdata['condition'];
        
        $mform->addElement('hidden', 'planid', $planid);
        $mform->setType('planid', PARAM_INT);
        
        $mform->addElement('hidden', 'type', 'assign_courses');
        $mform->setType('type', PARAM_RAW);
        
        $mform->addElement('hidden','condition',$condition);
        $mform->setType('type', PARAM_RAW);
        
        $sql = "SELECT courseid, planid FROM {local_ilp_courses} WHERE planid = $planid";
		$existing_plan_courses = $DB->get_records_sql($sql);

		$courses = lib::ilp_courses_list($planid);

		$options = array();
		if(!empty($courses)){
			foreach ($courses as $key => $value) {
				if(!array_key_exists($key, $existing_plan_courses)){
					$options[$key] = $value;
				}
			}
		}
        $select = $mform->addElement('autocomplete',  'ilp_courses[]',  get_string('selectcourses', 'local_ilp'),$options);
        $mform->addHelpButton('ilp_courses[]', 'ilpcourses', 'local_ilp');
        $select->setMultiple(true);

    }
}