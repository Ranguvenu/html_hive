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

/**
 * Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @subpackage Configurable Reports
 * @copyright  2019 Eabyas Info Solutions <www.eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_completionstate extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('completionstate', 'block_configurable_reports');
        $this->reporttypes = array('sql', 'detailcourseinfo', 'detailclassroominfo','onlineexamsinfo','programinfo','certificationinfo','lpath_completion');
    }

    function summary($data) {
        return get_string('filtercompletionstate_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {

        $filter_completionstate = optional_param('filter_completionstate', -1, PARAM_INT);
        if (!$filter_classrooms)
            return $finalelements;

        if ($this->report->type != 'sql') {
            return array($filter_classrooms);
        } else {
            if (preg_match("/%%FILTER_COMPLETIONSTATE:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filter_classrooms;
                return str_replace('%%FILTER_COMPLETIONSTATE:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform) {
        global $DB,$USER;

        $filtercompletionstate = optional_param('filter_completionstate', -1, PARAM_INT);

        $selectoptions = array(-1 => get_string('select_option', 'block_configurable_reports'));
        $selectoptions[0] = get_string('not_completed', 'block_configurable_reports');
        $selectoptions[1] = get_string('completed', 'block_configurable_reports');

        $mform->addElement('select', 'filter_completionstate', get_string('completionstate','block_configurable_reports'), $selectoptions);
        $mform->setType('filter_completionstate', PARAM_INT);
    }
}