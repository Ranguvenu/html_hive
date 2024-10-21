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

class plugin_department extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filterdepartment', 'block_configurable_reports');
        $this->reporttypes = array('');
    }

    function summary($data) {
        return get_string('filterdepartment_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {

        $filter_users = optional_param('filter_users', 0, PARAM_INT);
        if (!$filter_users)
            return $finalelements;

        if ($this->report->type != 'sql') {
            return array($filter_users);
        } else {
            if (preg_match("/%%FILTER_USERS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filter_users;
                return str_replace('%%FILTER_USERS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform) {
        global $DB;

        $sql = " SELECT lc.id, lc.fullname AS departmentname
            FROM {local_costcenter} lc
            JOIN {user} u ON u.open_departmentid = lc.id
            WHERE u.id > 2 ";

        $departments = $DB->get_records_sql_menu($sql);

        $sql .= " ORDER BY lc.fullname ASC ";

        $departments = $DB->get_records_sql_menu($sql);


        $mform->addElement('select', 'filter_department', get_string('department','block_configurable_reports'), $departments);
        $mform->setType('filter_department', PARAM_INT);
        $mform->addElement('html', html_writer::script(" $('#id_filter_department').select2();"), array());
    }
}
