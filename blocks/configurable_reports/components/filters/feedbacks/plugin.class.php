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

class plugin_feedbacks extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('feedbackinfo', 'block_configurable_reports');
        $this->reporttypes = array('sql', 'feedbackinfo');
    }

    function summary($data) {
        return get_string('feedbackfilter_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {

        $filter_feedbacks = optional_param('filter_feedbacks', 0, PARAM_INT);
        if (!$filter_feedbacks)
            return $finalelements;

        if ($this->report->type != 'sql') {
            return array($filter_feedbacks);
        } else {
            if (preg_match("/%%FILTER_FEEDBACKS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filter_feedbacks;
                return str_replace('%%FILTER_FEEDBACKS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform) {
        global $DB,$USER;

        $filter_feedbacks = optional_param('filter_feedbacks', 0, PARAM_INT);

        $sql = "SELECT le.id, le.name
                FROM {local_evaluations} le
                WHERE 1 = 1  ";

        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $sql .= "";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $sql .= " AND le.costcenterid = $USER->open_costcenterid ";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $sql .= " AND le.costcenterid = $USER->open_costcenterid 
                    AND le.departmentid = $USER->open_departmentid";
        }else{
            $sql .= " AND le.costcenterid = $USER->open_costcenterid 
                    AND le.departmentid = $USER->open_departmentid";
        }

        $sql .= " ORDER BY le.name ASC ";

        $feedbacks = $DB->get_records_sql_menu($sql);

        $selectoptions = array(null => get_string('select_option', 'block_configurable_reports'));
        $selectoptions[-1] = get_string('allfeedbacks_option', 'block_configurable_reports');

        $onlinetests = $selectoptions + $feedbacks;

        $mform->addElement('select', 'filter_feedbacks', get_string('feedback','block_configurable_reports'), $onlinetests);
        $mform->setType('filter_feedbacks', PARAM_INT);
    }
}