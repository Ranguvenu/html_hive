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
 * Class external
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/learningdashboard/lib.php');

class local_learningdashboard_external extends \external_api
{
    /** Describes the parameters for delete_course webservice.
     * @return external_function_parameters
     */
    public static function creditsdata_view_parameters() {
        return new external_function_parameters([
            'status' => new external_value(PARAM_RAW, 'Status of the credits'),
        ]);
    }

    /**
     * lists all courses
     *
     * @param array $options
     * @param array $dataoptions
     * @param int $offset
     * @param int $limit
     * @param int $contextid
     * @param array $filterdata
     * @return array courses list.
     */
    public static function creditsdata_view($status) {
        global $DB, $PAGE;
        require_login();
        $PAGE->set_context(context_system::instance());
        ob_start();
        // Parameter validation.
        $params = self::validate_parameters(
            self::creditsdata_view_parameters(),
            [
                'status' => $status,
            ]
        );
        $data = mylearningsdata($status);
        $totalcount = count($data['learnerdata']);
        return [
            'totalcount' => $totalcount,
            'records' => $data,
            'graphdata' => $data['graphdata']
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function creditsdata_view_returns() {
        return new external_single_structure([
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set', VALUE_OPTIONAL),
            'records' => new external_single_structure(
                array(
                    'learnerdata' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'targetcredits' => new external_value(PARAM_RAW, 'targetcredits', VALUE_OPTIONAL),
                                'achievedcredits' => new external_value(PARAM_RAW, 'achievedcredits', VALUE_OPTIONAL),
                                'pendingcredits' => new external_value(PARAM_RAW, 'pendingcredits', VALUE_OPTIONAL),
                                'managercredits' => new external_value(PARAM_RAW, 'managercredits', VALUE_OPTIONAL),
                                'coursenameCut' => new external_value(PARAM_RAW, 'coursenameCut', VALUE_OPTIONAL),
                                'creditstype' => new external_value(PARAM_RAW, 'creditstype', VALUE_OPTIONAL),
                                'courseids' => new external_value(PARAM_RAW, 'courseids', VALUE_OPTIONAL),
                                'coursestatus' => new external_value(PARAM_RAW, 'coursestatus', VALUE_OPTIONAL),
                                'coursecount' => new external_value(PARAM_RAW, 'coursecount', VALUE_OPTIONAL),
                                'issupervisor' => new external_value(PARAM_RAW, 'issupervisor', VALUE_OPTIONAL),
                            )
                        ),
                        'learnerdata',
                        VALUE_OPTIONAL
                    ),
                    'islearnerview' => new external_value(PARAM_RAW, 'islearnerview', VALUE_OPTIONAL),
                    'ismanagerview' => new external_value(PARAM_RAW, 'ismanagerview', VALUE_OPTIONAL),
                    'completedtab' =>  new external_value(PARAM_RAW, 'completedtab', VALUE_OPTIONAL),
                    'pendingtab' =>  new external_value(PARAM_RAW, 'pendingtab', VALUE_OPTIONAL),
                    'data' => new external_value(PARAM_RAW, 'data', VALUE_OPTIONAL),
                    'charthtml' => new external_value(PARAM_RAW, 'charthtml', VALUE_OPTIONAL),
                    'labelone' => new external_value(PARAM_RAW, 'labelone', VALUE_OPTIONAL),
                    'urlone' => new external_value(PARAM_RAW, 'urlone', VALUE_OPTIONAL),
                    'labeltwo' => new external_value(PARAM_RAW, 'labeltwo', VALUE_OPTIONAL),
                    'urltwo' => new external_value(PARAM_RAW, 'urltwo', VALUE_OPTIONAL),
                ),
                'records',
                VALUE_OPTIONAL
            ),
            'graphdata' => new external_value(PARAM_RAW, 'graphdata', VALUE_OPTIONAL),


        ]);
    }
    /** Describes the parameters for delete_course webservice.
     * @return external_function_parameters
     */
    public static function teams_creditsdata_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(
                PARAM_INT,
                'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of results to return',
                VALUE_DEFAULT,
                0
            ),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }

    /**
     * lists all courses
     *
     * @param array $options
     * @param array $dataoptions
     * @param int $offset
     * @param int $limit
     * @param int $contextid
     * @param array $filterdata
     * @return array courses list.
     */
    public static function teams_creditsdata($options, $dataoptions,  $offset, $limit, $contextid, $filterdata) {
        global $DB, $PAGE;
        require_login();
        $PAGE->set_context(context_system::instance());
        ob_start();
        // Parameter validation.
        $params = self::validate_parameters(
            self::teams_creditsdata_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $stable = new \stdClass();
        $stable->thead = false;
        $offset = $params['offset'];
        $limit = $params['limit'];
        $stable->start = $offset;
        $stable->length = $limit;
        $data = teamslearningsdata($stable, json_decode($filterdata));
        $totalcount = $data['count'];
        return [
            'totalcount' => $totalcount,
            'records' => $data['records'],
            'options' => $options,
            'dataoptions' => $dataoptions,
            'isadminview' => $data['isadminview'],
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function teams_creditsdata_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
            'totalcount' => new external_value(PARAM_INT, 'total number of records in result set', VALUE_OPTIONAL),
            'filterdata' => new external_value(PARAM_RAW, 'filter data', VALUE_OPTIONAL),
            'ismanagerview' =>  new external_value(PARAM_BOOL, 'actions', VALUE_OPTIONAL),
            'isadminview' =>  new external_value(PARAM_BOOL, 'actions', VALUE_OPTIONAL),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'targetcredits' => new external_value(PARAM_RAW, 'targetcredits', VALUE_OPTIONAL),
                        'achievedcredits' => new external_value(PARAM_RAW, 'achievedcredits', VALUE_OPTIONAL),
                        'creditstype' => new external_value(PARAM_RAW, 'creditstype', VALUE_OPTIONAL),
                        'courseids' => new external_value(PARAM_RAW, 'courseids', VALUE_OPTIONAL),
                        'coursestatus' => new external_value(PARAM_RAW, 'coursestatus', VALUE_OPTIONAL),
                        'status' => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                        'pendingtab' => new external_value(PARAM_RAW, 'pendingtab', VALUE_OPTIONAL),
                        'completedtab' => new external_value(PARAM_RAW, 'completedtab', VALUE_OPTIONAL),
                        'username' => new external_value(PARAM_RAW, 'username', VALUE_OPTIONAL),
                        'employeename' => new external_value(PARAM_RAW, 'employeename', VALUE_OPTIONAL),
                        'email' => new external_value(PARAM_RAW, 'email', VALUE_OPTIONAL),
                        'mobile' => new external_value(PARAM_RAW, 'mobile', VALUE_OPTIONAL),
                        'coursecount' => new external_value(PARAM_RAW, 'coursecount', VALUE_OPTIONAL),
                        'startdate' => new external_value(PARAM_RAW, 'startdate', VALUE_OPTIONAL),

                    )
                ),
                'records',
                VALUE_OPTIONAL
            ),
        ]);
    }
}
