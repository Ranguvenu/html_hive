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

class plugin_certifications extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('certificationinfo', 'block_configurable_reports');
        $this->reporttypes = array('sql', 'certificationinfo');
    }

    function summary($data) {
        return get_string('certificationsfilter_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {

        $filter_certifications = optional_param('filter_certifications', 0, PARAM_INT);
        if (!$filter_certifications)
            return $finalelements;

        if ($this->report->type != 'sql') {
            return array($filter_certifications);
        } else {
            if (preg_match("/%%FILTER_CERTIFICATIONS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filter_certifications;
                return str_replace('%%FILTER_CERTIFICATIONS:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform) {
        global $DB, $USER;

        $systemcontext = context_system::instance();
        
        $filtercertifications = optional_param('filter_certifications', 0, PARAM_INT);

        $sql = "SELECT c.id, c.name
                FROM {local_certification} c
                WHERE 1 = 1  ";

        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $sql .= "";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $sql .= " AND c.costcenter = $USER->open_costcenterid ";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $sql .= " AND c.costcenter = $USER->open_costcenterid 
                    AND c.department = $USER->open_departmentid";
        }else{
            $sql .= " AND c.costcenter = $USER->open_costcenterid 
                    AND c.department = $USER->open_departmentid";
        }

        $sql .= " ORDER BY c.name ASC ";

        $certifications = $DB->get_records_sql_menu($sql);

        $selectoptions = array(null => get_string('select_option', 'block_configurable_reports'));
        $selectoptions[-1] = get_string('allcertifications_option', 'block_configurable_reports');

        $certificationslist = $selectoptions + $certifications;

        $mform->addElement('select', 'filter_certifications', get_string('certification','block_configurable_reports'), $certificationslist);
        $mform->setType('filter_certifications', PARAM_INT);
    }
}