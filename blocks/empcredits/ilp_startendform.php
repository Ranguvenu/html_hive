<?php
require_once("$CFG->libdir/formslib.php");
 
class ilp_startendform extends moodleform {
 
    function definition() {
        global $CFG;
         
        $mform = $this->_form; // Don't forget the underscore!
        $id = $this->_customdata['id'];
       
        $mform->addElement('hidden', 'id',$id); 
        $mform->setType('id', PARAM_INT); 
        
        $mform->addElement('date_selector', 'ilp_start', get_string('ilpstart','block_empcredits'));
        $mform->addElement('date_selector', 'ilp_end', get_string('ilpend','block_empcredits'));
        $this->add_action_buttons();
    }                           // Close the function
    function validation($data, $files) {
        $errors= array();      
            if ($data['ilp_end'] <= $data['ilp_start']){
                $errors['ilp_end'] = get_string('ilpdateerror','block_empcredits');
            }
        
        return $errors;
    }
}