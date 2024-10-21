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

class plugin_coursecompletionstatus extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('coursecompletionstatus', 'block_configurable_reports');
        $this->reporttypes = array('sql', 'detailed_course_completion','course_certification');
    }

    function summary($data) {
        return get_string('status_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {

        $filter_status = optional_param('filter_coursestatus', 0, PARAM_INT);
        if (!$filter_status)
            return $finalelements;

        if ($this->report->type != 'sql') {
            return array($filter_status);
        } else {
            if (preg_match("/%%FILTER_STATUS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filter_status;
                return str_replace('%%FILTER_STATUS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform) {
        global $remoteDB, $CFG;

        $filter_status = optional_param('filter_coursestatus', 0, PARAM_INT);

        $reportclassname = 'report_' . $this->report->type;
        $reportclass = new $reportclassname($this->report);

        if ($this->report->type != 'sql') {
            $components = cr_unserialize($this->report->components);
            $conditions = $components['conditions'];

            $course_status_list = $reportclass->elements_by_conditions($conditions);
        } else {
            //$course_status_list = array_keys($remoteDB->get_records('course_categories'));
            $course_status_list = array(0, 1);
        }

        $status_options = array();
        $status_options[null] = get_string('filter_all', 'block_configurable_reports');

        if (!empty($course_status_list)) {
            //list($usql, $params) = $remoteDB->get_in_or_equal($course_status_list);
            //$categories = $remoteDB->get_records_select('course_categories', "id $usql", $params);
            $course_status_options = array('1'=>'Completed', '2'=>'Not Completed');
            foreach ($course_status_options as $key=>$value) {
                $status_options[$key] = format_string($value);
            }
        }
        $mform->addElement('select', 'filter_coursestatus', get_string('coursecompletionstatus', 'block_configurable_reports'), $status_options);
        $mform->setType('filter_coursestatus', PARAM_INT);
    }

}
