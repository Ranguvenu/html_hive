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
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_courselearnertype extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filtercoursetype', 'block_configurable_reports');
        $this->reporttypes = array('courses', 'sql','course_certification');
    }

    public function summary($data) {
        return get_string('filtercourses_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filtercoursetype = optional_param('filter_coursetype',0,PARAM_INT);
        if(!$filtercoursetype){
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filtercoursetype);
        } else {
            if (preg_match("/%%FILTER_COURSETYPE:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filtercoursetype;
                return str_replace('%%FILTER_COURSETYPE:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $DB, $CFG;

        $filtercoursetype = optional_param('filter_coursetype', 0, PARAM_INT);
 
        $sql = "SELECT lct.id, lct.course_type FROM {local_course_types} lct WHERE 1=1 ORDER BY lct.course_type ASC ";

        $coursetypes = $DB->get_records_sql_menu($sql);

        $coursetypeoptions = array();
        $coursetypeoptions[0] = get_string('filter_all', 'block_configurable_reports');

        $coursetypelist = $coursetypeoptions + $coursetypes;

        $mform->addElement('select', 'filter_coursetype', get_string('filtercoursetype','block_configurable_reports'), $coursetypelist);
        $mform->setType('filter_coursetype', PARAM_INT);
    }
}
