<?php
namespace local_courses\form;
use core;
use moodleform;
use context_system;
require_once($CFG->dirroot . '/lib/formslib.php');


class course_facilitator_form extends moodleform {
    public function definition() {
      global $USER, $CFG, $DB;
            $mform = $this->_form;
            $courseid = $this->_customdata['courseid']; 


            $coursecredits = $DB->get_field('course','open_facilitatorcredits',array('id' => $courseid));
            $mform->addElement('text', 'facilitatorcredits', get_string('facilitatorcredits','local_courses'), array('value' => $coursecredits, 'disabled' => true));

            $userlistarray = array(null=>'--Select User--');
            $userlists = $DB->get_records_sql("SELECT id, concat(firstname,' ',lastname) as name FROM {user} WHERE confirmed = 1 AND deleted= 0 AND suspended = 0 AND id > 2");
            foreach($userlists as $userlist){
                $userlistarray[$userlist->id] = $userlist->name;
            }

            $mform->addElement('autocomplete', 'facilitatorname', get_string('facilitatorname','local_courses'), $userlistarray);
            $mform->addRule('facilitatorname', null, 'required', null, 'client');

            $contenttype = array(null=>'--Select--',1=>'Project review and Viva',2=>'Classroom Content Development',3=>'eLearning Content Development',4=>'Others',5=>'Classroom Delivery');
            $mform->addElement('select', 'contenttype', get_string('contenttype','local_courses'), $contenttype);
            $mform->addRule('contenttype', null, 'required', null, 'client');

            $mform->addElement('text', 'credits', get_string('credits','local_courses'), 'maxlength="100" size="10"');
            $mform->addRule('credits', get_string('required'), 'required', null);
            $mform->setType('credits', PARAM_RAW);

            $facilitatorILTs = $DB->get_records_sql("SELECT lc.id, lc.name FROM {local_classroom} as lc JOIN {local_classroom_courses} as lcc ON lcc.classroomid = lc.id where lcc.courseid = {$courseid}");
             $facilitatorILTsList =array(null=>'--Select--');
            foreach($facilitatorILTs as $facilitatorILT){
             $facilitatorILTsList[$facilitatorILT->id] = $facilitatorILT->name;
            }
            $mform->addElement('select', 'facilitatorILTs', get_string('facilitatorILTs','local_courses'), $facilitatorILTsList);
            //$mform->addRule('facilitatorILTs', null, 'required', null, 'client');

            $this->add_action_buttons();
            $mform->addElement('hidden', 'id', 0);
            $mform->setType('id', PARAM_INT);
            $mform->setDefault('id', $courseid);
    }

   public function validation($data, $files) {
         global $DB;
        $errors = parent::validation($data, $files);
        if(!empty($data['credits'])) {
        $credits = $data['credits'];
        $coursecredits = $DB->get_field('course','open_facilitatorcredits',array('id' => $data['id']));
         if($coursecredits > $credits ){

              $errors['credits'] = get_string('creditserror', 'local_courses');
         }
       }
        return $errors;
    }
}
