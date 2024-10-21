<?php
use core_component;
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
class filters_form extends moodleform {

    function definition() {
        global $CFG;

        $mform    = $this->_form;
        $filterlist        = $this->_customdata['filterlist'];// this contains the data of this form
        $filterparams      = $this->_customdata['filterparams'];
        $action      = isset($this->_customdata['action']) ? $this->_customdata['action'] : null;

        $options           = $filterparams['options'];
        $dataoptions       = $filterparams['dataoptions'];
        $submitid = isset($this->_customdata['submitid']) ? $this->_customdata['submitid'] : 'filteringform';

        $this->_form->_attributes['id'] = $submitid;          

        $mform->addElement('hidden', 'options', $options);
        $mform->setType('options', PARAM_RAW);

        $mform->addElement('hidden', 'dataoptions', $dataoptions);
        $mform->setType('dataoptions', PARAM_RAW);        

        foreach ($filterlist as $key => $value) {
            if($value === 'tab_from_date' || $value === 'tab_to_date' ){
                $filter = 'empcredits';
            } else{
                $filter = $value;
            }
            // $core_component = new core_component();
            // $courses_plugin_exist = $core_component::get_plugin_directory('blocks', $filter);
            // if ($courses_plugin_exist) {
                require_once($CFG->dirroot . '/blocks/empcredits/lib.php');
                $functionname = $value.'_filter';
                $functionname($mform);
            // }
        }

        $buttonarray = array();
        $applyclassarray = array('class' => 'form-submit','onclick' => '(function(e){ require("local_costcenter/cardPaginate").filteringData(e,"'.$submitid.'") })(event)');
        $cancelclassarray = array('class' => 'form-submit','onclick' => '(function(e){ require("local_costcenter/cardPaginate").resetingData(e,"'.$submitid.'") })(event)');
        $buttonarray[] = &$mform->createElement('button', 'filter_apply', get_string('apply','local_courses'), $applyclassarray);
        $buttonarray[] = &$mform->createElement('button', 'cancel', get_string('reset','local_courses'), $cancelclassarray);

     
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->disable_form_change_checker();

    }
     /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        return $errors;
    }
}
