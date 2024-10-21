<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Course list block caps.
 *
 * @author eabyas  <info@eabyas.in>
 * @package    Bizlms
 * @subpackage local_udemysync
 */

defined('MOODLE_INTERNAL') || die;
require_once("$CFG->libdir/externallib.php");
use local_udemysync\plugin;
use local_udemysync\output\udemysyncview;

class local_udemysync_external  extends external_api{
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters.
     */
    public static function manageudemylog_parameters() {
        return new external_function_parameters([
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                    VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'contextid' => new external_value(PARAM_INT, 'contextid'),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            ]);
    }

    /**
     * Gets the list of users based on the login user
     *
     * @param int $options need to give options targetid,viewtype,perpage,cardclass
     * @param int $dataoptions need to give data which you need to get records
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of items to skip from the beginning of the result set.
     * @param int $filterdata need to pass filterdata.
     * @return array The list of users and total users count.
     */
    public static function manageudemylog(
        $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $contextid,
        $filterdata
    ) {
        global $OUTPUT, $CFG, $DB,$USER,$PAGE;
        require_login();
        $PAGE->set_url('/local/udemysync/sync_history.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::manageudemylog_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        // print_object();
        $offset = $params['offset'];
        $limit = $params['limit'];
        $decodedata = json_decode($params['dataoptions']);
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = true;
        $totalsynchistory = (new udemysyncview)::manage_udemyhistory_count($stable,$filtervalues);
        $totalsynchistorycount = $totalsynchistory['count'];

        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = (new udemysyncview)::manage_udemyhistory_content($stable,$filtervalues);

        return [
            'totalcount' => $totalsynchistorycount,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];

    }

    /**
     * Returns description of method result value.
     */
    public static function  manageudemylog_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of users in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'moduletype' => new external_value(PARAM_RAW, 'modules', VALUE_OPTIONAL),
                                    'modules' => new external_value(PARAM_RAW, 'modules', VALUE_OPTIONAL),
                                    'status' => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                                    'statusmessage' => new external_value(PARAM_RAW, 'statusmessage', VALUE_OPTIONAL),
                                    'usercreated' => new external_value(PARAM_RAW, 'usercreated', VALUE_OPTIONAL),
                                    'timecreated' => new external_value(PARAM_RAW, 'timecreated', VALUE_OPTIONAL)
                                    
                                )
                            )
                        )
        ]);
    }
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters.
     */
    public static function manageudeystatics_parameters() {
        return new external_function_parameters([
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                    VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'contextid' => new external_value(PARAM_INT, 'contextid'),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            ]);
    }

    /**
     * Gets the list of users based on the login user
     *
     * @param int $options need to give options targetid,viewtype,perpage,cardclass
     * @param int $dataoptions need to give data which you need to get records
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of items to skip from the beginning of the result set.
     * @param int $filterdata need to pass filterdata.
     * @return array The list of users and total users count.
     */
    public static function manageudeystatics(
        $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $contextid,
        $filterdata
    ) {
        global $OUTPUT, $CFG, $DB,$USER,$PAGE;
        require_login();
        $PAGE->set_url('/local/udemysync/sync_historystatistics.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::manageudeystatics_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        // print_object();
        $offset = $params['offset'];
        $limit = $params['limit'];
        $decodedata = json_decode($params['dataoptions']);
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = true;
        $totalsynchistorystatistics = (new udemysyncview)::manage_synchistorystatistics_count($stable,$filtervalues);
        $totalsynchistorystatisticscount = $totalsynchistorystatistics['count'] ? $totalsynchistorystatistics['count'] : 0;

        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = (new udemysyncview)::manage_synchistorystatistics_content($stable,$filtervalues);
        return [
            'totalcount' => $totalsynchistorystatisticscount,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
            'filterdata' => $filterdata,
        ];

    }

    /**
     * Returns description of method result value.
     */
    public static function  manageudeystatics_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of count in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'newcoursescount' => new external_value(PARAM_INT, 'new courses count'),
                                    'updatedcoursescount' => new external_value(PARAM_INT, 'updated courses count'),
                                    'courseserrorscount' => new external_value(PARAM_INT, 'courses errors count'),
                                    'timecreated' => new external_value(PARAM_RAW, 'timecreated')
                                )
                            )
                        )
        ]);
    }
}