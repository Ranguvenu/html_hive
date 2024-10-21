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

class plugin_users extends plugin_base {

    public function init() {
        $this->form = false;
        $this->unique = true;
        $this->fullname = get_string('filterusers', 'block_configurable_reports');
        $this->reporttypes = array('courses', 'sql','idp');
    }

    public function summary($data) {
        return get_string('filterusers_summary', 'block_configurable_reports');
    }

    public function execute($finalelements, $data) {

        $filterusers = optional_param('filter_users', 0, PARAM_INT);
        if (!$filterusers) {
            return $finalelements;
        }

        if ($this->report->type != 'sql') {
            return array($filterusers);
        } else {
            if (preg_match("/%%FILTER_USERS:([^%]+)%%/i", $finalelements, $output)) {
                $replace = ' AND '.$output[1].' = '.$filterusers;
                return str_replace('%%FILTER_USERS:'.$output[1].'%%', $replace, $finalelements);
            }
        }
        return $finalelements;
    }

    public function print_filter(&$mform) {
        global $DB;

        $usersoptions = array();

        $usersoptions[null] = get_string('select_option', 'block_configurable_reports');
        $usersoptions[-1] = get_string('filter_all', 'block_configurable_reports');

        $sql = "SELECT id, CONCAT(firstname,' ',lastname) as empname 
               FROM {user} 
               WHERE id > 2 AND deleted = 0 AND suspended = 0
               ORDER BY firstname ASC";
        $users = $DB->get_records_sql_menu($sql);

        $userslist = $usersoptions + $users;

        $mform->addElement('select', 'filter_users', get_string('users'), $userslist);
        $mform->setType('filter_users', PARAM_INT);

        $mform->addElement('html', html_writer::script(" $('#id_filter_users').select2();"), array());
    }
}
