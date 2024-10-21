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

class plugin_empstatus extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('empstatus', 'block_configurable_reports');
        $this->reporttypes = array('sql',
                                   'detailed_course_completion',
                                   'quizbycourse',
                                   'idp',
                                   'sme_credit_points','trainerfeedbackresponses','course_certification');
    }

    function summary($data) {
        return get_string('emploeyee_status_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {

        $emp_status = optional_param('filter_emp_status', 0, PARAM_INT);
        if (!$emp_status)
            return $finalelements;

        if ($this->report->type != 'sql') {
            return array($emp_status);
        } else {
            if (preg_match("/%%FILTER_EMP_STATUS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $emp_status;
                return str_replace('%%FILTER_EMP_STATUS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform) {
        global $remoteDB, $CFG;
        // $emp_status = optional_param('filter_emp_status', 0, PARAM_INT);

        // $reportclassname = 'report_' . $this->report->type;
        // $reportclass = new $reportclassname($this->report);

        // if ($this->report->type != 'sql') {
        //     $components = cr_unserialize($this->report->components);
        //     $conditions = $components['conditions'];
        //     $emp_status_list = $reportclass->elements_by_conditions($conditions);
        // } else {
        //     $emp_status_list = array(1, 2);
        // }

        $status_options = array();
        $status_options[null] = get_string('select', 'block_configurable_reports');
        $empstatus_options = array(1=>get_string('active'), 2=>get_string('inactive'));
        $status_options = $status_options + $empstatus_options;

        $mform->addElement('select', 'filter_emp_status', get_string('empstatus', 'block_configurable_reports'), $status_options);
        $mform->setType('filter_emp_status', PARAM_INT);
    }

}
