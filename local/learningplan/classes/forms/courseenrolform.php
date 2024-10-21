<?php
namespace local_learningplan\forms;
require_once($CFG->libdir . '/formslib.php');
use moodleform;
use local_learningplan\render\view as render;
use local_learningplan\lib\lib as lib;
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
        
        // ///This radion buttons are added for moduletype of assigning by sharath
        // $moduletypes = array();
        // $moduletypes[] = $mform->createElement('radio', 'moduletype', '', get_string('courses','local_learningplan'), 'courses', array('class' => 'moduletype'));
        // $moduletypes[] = $mform->createElement('radio', 'moduletype', '', get_string('classrooms','local_learningplan'), 'classrooms', array('class' => 'moduletype'));
        // $moduletypes[] = $mform->createElement('radio', 'moduletype', '', get_string('programs','local_learningplan'), 'programs', array('class' => 'moduletype'));
        // $moduletypes[] = $mform->createElement('radio', 'moduletype', '', get_string('certifications','local_learningplan'), 'certifications', array('class' => 'moduletype'));
        // $mform->addGroup($moduletypes, 'modulename',
        //     get_string('moduletype', 'local_learningplan'),
        //     array('&nbsp;&nbsp;'), false);
        // //ended here by sharath

        $sql = "SELECT courseid, planid FROM {local_learningplan_courses} WHERE planid = $planid";
		$existing_plan_courses = $DB->get_records_sql($sql);

		$courses = lib::learningplan_courses_list($planid);

		$options = array();
		if(!empty($courses)){
			foreach ($courses as $key => $value) {
				if(!array_key_exists($key, $existing_plan_courses)){
					$options[$key] = $value;
				}
			}
		}
        $select = $mform->addElement('autocomplete',  'learning_plan_courses[]',  get_string('selectcourses', 'local_learningplan'),$options);
        $select->setMultiple(true);

    }
}