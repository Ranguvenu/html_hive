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

class plugin_allusers extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filterallusers', 'block_configurable_reports');
        $this->reporttypes = array('sql', 'detailcourseinfo','detailclassroominfo','onlineexamsinfo','feedbackinfo',
            'programinfo','certificationinfo','detailed_course_completion','course_certification', 'learning_dashboard');
    }

    function summary($data) {
        return get_string('filterallusers_summary', 'block_configurable_reports');
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
        global $DB, $USER;

        $systemcontext = context_system::instance();
        
        $filterusers = optional_param('filter_users', 0, PARAM_INT);

        $sql = "SELECT u.id, concat(u.firstname,' ',u.lastname,' (',u.open_employeeid,')') as employeename
                FROM {user} u
                WHERE u.deleted = 0 AND u.id > 2  ";

        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $sql .= "";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $sql .= " AND u.open_costcenterid = $USER->open_costcenterid ";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $sql .= " AND u.open_costcenterid = $USER->open_costcenterid 
                    AND u.open_departmentid = $USER->open_departmentid";
        }else{
            $sql .= " AND u.open_costcenterid = $USER->open_costcenterid 
                    AND u.open_departmentid = $USER->open_departmentid";
        }

        $sql .= " ORDER BY u.firstname ASC ";

        $allusers = $DB->get_records_sql_menu($sql);

        $useroptions = array(null => get_string('select_option', 'block_configurable_reports'));
        $useroptions[-1] = get_string('selectall_employees', 'block_configurable_reports');

        $userslist = $useroptions + $allusers;

        $mform->addElement('select', 'filter_users', get_string('employee','block_configurable_reports'), $userslist);
        $mform->setType('filter_users', PARAM_INT);
        $mform->addElement('html', html_writer::script(" $('#id_filter_users').select2();"), array());
    }
}
