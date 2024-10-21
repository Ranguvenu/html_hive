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

namespace local_learningdashboard\output;

/**
 * Class learningdashboard
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learningdashboard
{
    /**
     * Renders Dashboard Tabs.
     *
     * @param object $data data 
     * @return string
     */
    public function display_dashboard($data) {
        global $OUTPUT;

        return $OUTPUT->render_from_template('local_learningdashboard/mylearnings', $data);
    }
    /**
     * Renders Team Status table.
     *
     * @param bool $filter filter true/false 
     * @return string/array
     */
    public function display_teamcompletionstatus($filter = false) {
        global $OUTPUT;
        $systemcontext = \context_system::instance();

        $options = array('targetID' => 'teamsstatuscreditdata', 'perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        $options['methodName'] = 'local_learningdashboard_teams_creditsdata';
        $options['templateName'] = 'local_learningdashboard/teamcreditsdata_view';

        $options = json_encode($options);

        $filterdata = json_encode(array());
        $dataoptions = json_encode(array('contextid' => $systemcontext->id));
        $context = [
            'targetID' => 'teamsstatuscreditdata',
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata
        ];

        if ($filter) {
            return  $context;
        } else {
            return  $OUTPUT->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }
}
