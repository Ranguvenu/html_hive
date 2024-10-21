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
 * A Moodle block for creating configurable reports
 * @package blocks
 * @author: Madhavi Rajana <madhavi.r@eabyas.com>
 * @date: 2020
 */
require_once($CFG->dirroot . '/blocks/configurable_reports/plugin.class.php');
define('COMPLETION_TRACKING_NONE', 0); //Add <Revathi>

class plugin_startenddatefilter extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('startendtime', 'block_configurable_reports');
        $this->reporttypes = array('quizbycourse','sme_credit_points');
    }

    public function summary($data) {
        return get_string('filterdate_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {
        global $CFG;


        if ($this->report->type != 'sql')
            return $finalelements;

        if ($CFG->version < 2011120100) {
            $filter_starttime = optional_param('filter_starttime', 0, PARAM_RAW);
            $filter_endtime = optional_param('filter_endtime', 0, PARAM_RAW);
        } else {
            $filter_starttime = optional_param_array('filter_starttime', 0, PARAM_RAW);
            $filter_endtime = optional_param_array('filter_endtime', 0, PARAM_RAW);
        }
        if (!$filter_starttime || !$filter_endtime)
            return $finalelements;

        $filter_starttime = make_timestamp($filter_starttime['year'], $filter_starttime['month'], $filter_starttime['day']);
        $filter_endtime = make_timestamp($filter_endtime['year'], $filter_endtime['month'], $filter_endtime['day']);

        $operators = array('<', '>', '<=', '>=');

        if (preg_match("/%%FILTER_STARTTIME:([^%]+)%%/i", $finalelements, $output)) {
            list($field, $operator) = preg_split('/:/', $output[1]);
            if (!in_array($operator, $operators))
                print_error('nosuchoperator');
            $replace = ' AND ' . $field . ' ' . $operator . ' ' . $filter_starttime;
            $finalelements = str_replace('%%FILTER_STARTTIME:' . $output[1] . '%%', $replace, $finalelements);
        }

        if (preg_match("/%%FILTER_ENDTIME:([^%]+)%%/i", $finalelements, $output)) {
            list($field, $operator) = preg_split('/:/', $output[1]);
            if (!in_array($operator, $operators))
                print_error('nosuchoperator');
            $replace = ' AND ' . $field . ' ' . $operator . ' ' . $filter_endtime;
            $finalelements = str_replace('%%FILTER_ENDTIME:' . $output[1] . '%%', $replace, $finalelements);
        }

        $finalelements = str_replace('%STARTTIME%%', $filter_starttime, $finalelements);
        $finalelements = str_replace('%ENDTIME%%', $filter_endtime, $finalelements);

        return $finalelements;
    }

    function print_filter(&$mform) {
        global $DB, $CFG;
        
         $report_type = $DB->get_field('block_configurable_reports', 'type', array('id'=>filter_var($_REQUEST['id'])));
        if($report_type=='quizbycourse'|| $report_type=='quiz' ){
                $mform->addElement('html',html_writer::tag('label','<b>Response Submission From</b>',array('id'=>'filter_label')));
        }elseif($report_type=='sme_credit_points'){
                $mform->addElement('html',html_writer::tag('label','<b>Facilitator Assigned duration</b>',array('id'=>'filter_label')));
        }

        $mform->addElement('date_selector', 'filter_starttime', 'From',array('startyear' => 2015, 
                                                                                'stopyear'  => 2025,
                                                                                'optional'  => TRUE));
        $mform->disabledIf('filter_starttime', 'filter_starttime', 'eq', COMPLETION_TRACKING_NONE);
        
        $mform->addElement('date_selector', 'filter_endtime', 'To',array('startyear' => 2015, 
                                                                            'stopyear'  => 2025,
                                                                            'optional'  => TRUE));
        $mform->disabledIf('filter_endtime', 'filter_endtime', 'eq', COMPLETION_TRACKING_NONE);
       
      
    }

}
