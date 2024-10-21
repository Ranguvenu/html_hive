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

class plugin_learningpaths extends plugin_base{

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filterlearningpaths', 'block_configurable_reports');
        $this->reporttypes = array('lpathoverview', 'lpath_completion', 'sql');
    }

    public function summary($data) {
        return get_string('lpathfilter_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filterlpaths = optional_param('filter_learningpath', NULL, PARAM_INT);
        if (!$filterlpaths) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterlpaths);
        } else {
            if (preg_match("/%%FILTER_COURSES:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filterlpaths;
                return str_replace('%%FILTER_LEARNINGPATH:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $DB, $CFG;

        $sql = "SELECT id, name
                FROM {local_learningplan}
                ORDER BY name ASC";

        $lpathslist = $DB->get_records_sql_menu($sql);

        $lpathoptions = array();
        $lpathoptions[null] = get_string('filter_all', 'block_configurable_reports');

        $courseslist = $lpathoptions + $lpathslist;

        $mform->addElement('select', 'filter_learningpath', get_string('learningpaths','block_configurable_reports'), $courseslist);
        $mform->setType('filter_learningpath', PARAM_INT);
    }
}
