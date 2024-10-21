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

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_allcourses extends plugin_base {

    function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercourses', 'block_configurable_reports');
        $this->reporttypes = array('sql', 'detailcourseinfo', 'coursesconfiguration', 'detailed_course_completion', 'quizbycourse','sme_credit_points','course_certification');
    }

    function summary($data) {
        return get_string('filtercourses_summary', 'block_configurable_reports');
    }

    function execute($finalelements, $data) {

        $filter_courses = optional_param('filter_courses', 0, PARAM_INT);
        if (!$filter_courses)
            return $finalelements;

        if ($this->report->type != 'sql') {
            return array($filter_courses);
        } else {
            if (preg_match("/%%FILTER_COURSES:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND ' . $output[1] . ' = ' . $filter_courses;
                return str_replace('%%FILTER_COURSES:' . $output[1] . '%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    function print_filter(&$mform) {
        global $DB, $USER;

        $systemcontext = context_system::instance();

        $filtercourses = optional_param('filter_courses', 0, PARAM_INT);

        $sql = "SELECT c.id, c.fullname
                FROM {course} c
                WHERE c.id > 1 ";
        // $sql .= " AND (CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',3,',%') OR CONCAT(',',c.open_identifiedas,',') LIKE CONCAT('%,',1,',%'))"; 

        if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
            $sql .= "";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid ";
        }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid 
                    AND c.open_departmentid = $USER->open_departmentid";
        }else{
            $sql .= " AND c.open_costcenterid = $USER->open_costcenterid 
                    AND c.open_departmentid = $USER->open_departmentid";
        }
        $sql .= " ORDER BY c.fullname ASC ";
                

        $allcourses = $DB->get_records_sql_menu($sql,$inparams);

        $courseoptions = array(null => get_string('select_option', 'block_configurable_reports'));
        $courseoptions[-1] = get_string('selectall_option', 'block_configurable_reports');

        $courseslist = $courseoptions + $allcourses;

        $mform->addElement('select', 'filter_courses', get_string('course'), $courseslist);
        $mform->setType('filter_courses', PARAM_INT);

        $mform->addElement('html', html_writer::script(" $('#id_filter_courses').select2();"), array());
    }
}
