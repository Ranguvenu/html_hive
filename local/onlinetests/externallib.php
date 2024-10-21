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
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_onlinetest
 */


defined('MOODLE_INTERNAL') || die;
use local_onlinetests\local\userdashboard_content as onlinetests;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/local/onlinetests/lib.php');

/**
 * onlinetests external functions
 *
 * @package    local_onlinetests
 * @category   external
 * @author Sreenivas <sreenivasula@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class local_onlinetests_external extends external_api {




    /**
     * Describes the parameters for submit_create_group_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_onlinetest_form_parameters() {
        return new external_function_parameters(
            array(
                //'evalid' => new external_value(PARAM_INT, 'The onlinetests id '),
                'contextid' => new external_value(PARAM_INT, 'The context id for the onlinetests'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create group form.
     *
     * @param int $contextid The context id for the course.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new group id.
     */
    public static function submit_create_onlinetest_form($contextid, $jsonformdata) {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/local/onlinetests/onlinetest_form.php');
        require_once($CFG->dirroot . '/local/onlinetests/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_onlinetest_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        // The last param is the ajax submitted data.
        $mform = new onlinetests_form(null, array(), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
        if ($validateddata) {
            if ($validateddata->id > 0) {
                $onlinetestsid = onlinetests_update_instance($validateddata);
            } else if ($validateddata->id < 0) {
                $onlinetestsid = onlinetests_add_instance($validateddata);
            }

        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }

        return $onlinetestsid;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_onlinetest_form_returns() {
        return new external_value(PARAM_INT, 'onlinetests id');
    }

    /** Describes the parameters for delete_course webservice.
     * @return external_function_parameters
    */
    public static function tests_view_parameters() {
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0),
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
    public static function tests_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
        global $DB, $PAGE;
        require_login();
        $PAGE->set_url('/local/onlinetests/index.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::tests_view_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'contextid' => $contextid,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];
        $filtervalues = json_decode($filterdata);

        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = onlinetestslist($stable, $filtervalues);
        $totalcount = $data['totalrecords'];
        return [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' =>$data['records'],
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function tests_view_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
            'filterdata' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'length' => new external_value(PARAM_RAW, 'total number of challenges in result set'),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                    'testid' => new external_value(PARAM_INT, 'testid'),
                    'quizid' => new external_value(PARAM_INT, 'quizid'),
                    'testname' => new external_value(PARAM_RAW, 'testname'),
                    'testfullname' => new external_value(PARAM_RAW, 'testfullname'),
                    'testdate' => new external_value(PARAM_RAW, 'testdate'),
                    'maxgrade' => new external_value(PARAM_INT, 'maxgrade'),
                    'passgrade' => new external_value(PARAM_INT, 'passgrade'),
                    'enrolled' => new external_value(PARAM_INT, 'enrolled'),
                    'completed' => new external_value(PARAM_INT, 'completed'),
                    'mygrade' => new external_value(PARAM_RAW, 'mygrade'),
                    'sesskey' => new external_value(PARAM_RAW, 'sesskey'),
                    'attempts' => new external_value(PARAM_INT, 'attempts'),
                    'enrolledon' => new external_value(PARAM_RAW, 'enrolledon'),
                    'completedon' => new external_value(PARAM_RAW, 'completedon'),
                    'status' => new external_value(PARAM_RAW, 'status'),
                    'canreview' => new external_value(PARAM_INT, 'canreview'),
                    'is_admin' => new external_value(PARAM_BOOL, 'is_admin'),
                    'users' => new external_value(PARAM_BOOL, 'users'),
                    'addusers' => new external_value(PARAM_BOOL, 'addusers'),
                    'bulkenrollusers' => new external_value(PARAM_BOOL, 'bulkenrollusers'),
                    'delete' => new external_value(PARAM_BOOL, 'delete'),
                    'edit' => new external_value(PARAM_BOOL, 'edit'),
                    'questions' => new external_value(PARAM_BOOL, 'questions'),
                    'configpath' => new external_value(PARAM_RAW, 'configpath'),
                    'actions' => new external_value(PARAM_INT, 'actions'),
                    'departmentsCut' => new external_value(PARAM_RAW, 'departmentsCut'),
                    'deptname' => new external_value(PARAM_RAW, 'deptname'),
                    'userque' => new external_value(PARAM_BOOL, 'userque'),
                    'usertwoactions' => new external_value(PARAM_BOOL, 'usertwoactions'),
                    'userreview' => new external_value(PARAM_BOOL, 'userreview'),
                    'userhasactions' => new external_value(PARAM_BOOL, 'userhasactions'),
                    'userattemptid' => new external_value(PARAM_INT, 'userattemptid'),
                    'cmid' => new external_value(PARAM_INT, 'cmid'),
                    'contextid' => new external_value(PARAM_INT, 'contextid'),
                    'starttest_url' => new external_value(PARAM_RAW, 'starttest_url'),
                    'hide_show' => new external_value(PARAM_BOOL, 'hide_show'),
                    'show' => new external_value(PARAM_BOOL, 'show'),
                    'hide' => new external_value(PARAM_BOOL, 'hide'),
                    'certificate_exists' => new external_value(PARAM_RAW ,'certificate_exists',VALUE_OPTIONAL),
                    'certificate_download' => new external_value(PARAM_RAW ,'certificate_download',VALUE_OPTIONAL),
                    'certificateid' => new external_value (PARAM_RAW, 'certificateid',VALUE_OPTIONAL)
                    )
                )
            )
        ]);
    }
    // Get onlinetests
    public static function get_onlinetests_parameters() {
        return new external_function_parameters(
            array(
                   'status' => new external_value(PARAM_RAW, 'status'),
                   'search' => new external_value(PARAM_RAW, 'search', VALUE_OPTIONAL, ''),
                    'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                    'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 15)
                )
        );
    }

    public static function get_onlinetests($status, $search = '', $page=0, $perpage=15) {
       global $CFG, $DB,$USER;
        $data = array();
        require_once($CFG->dirroot . "/course/lib.php");
        //validate parameter
        $params = self::validate_parameters(self::get_onlinetests_parameters(),
                        array('status' => $status, 'search' => $search, 'page' => $page, 'perpage' => $perpage));

        if($status == 'inprogress') {
            $courseslist = onlinetests::inprogress_onlinetests($search, $page * $perpage, $perpage);
            $count  = onlinetests::inprogress_onlinetests_count();
        } else if($status == 'completed') {
            $courseslist = onlinetests::completed_onlinetests($search, $page * $perpage, $perpage);
            $count  = onlinetests::completed_onlinetests_count();
        } else {
            $sqlquery = "SELECT a.*, ou.timecreated, ou.timemodified as joindates";
            $sqlcount = "SELECT COUNT(a.id)";
            $sql = " from {local_onlinetests} a, {local_onlinetest_users} ou where a.id = ou.onlinetestid AND ou.userid = $USER->id AND a.visible = 1";
            if (!empty($search)) {
                $sql .= " AND a.name LIKE '%%{$search}%%'";
            }
            $sql .=  " ORDER BY ou.timecreated DESC";
            $courseslist = $DB->get_records_sql($sqlquery . $sql, array(), $page*$perpage, $perpage);
            $count = $DB->count_records_sql($sqlcount . $sql, array());
        }
        if (!empty($courseslist)) {

            foreach ($courseslist as $inprogress_coursename) {
                $onerow = array();
                $cm = get_coursemodule_from_instance('quiz', $inprogress_coursename->quizid, 0, false, MUST_EXIST);
                $gradeitem = $DB->get_record('grade_items', array('iteminstance'=>$inprogress_coursename->quizid, 'itemmodule'=>'quiz', 'courseid'=>1));
                $sql="SELECT * FROM {quiz_attempts} where id=(SELECT max(id) id from {quiz_attempts} where userid={$USER->id} and quiz={$inprogress_coursename->quizid})";
                $userattempt = $DB->get_record_sql($sql);
                $attempts = ($userattempt->attempt) ? $userattempt->attempt : 0;
                $grademax = ($gradeitem->grademax) ? round($gradeitem->grademax): '-';
                $gradepass = ($gradeitem->gradepass) ? round($gradeitem->gradepass): '-';
                $userquizrecord = $DB->get_record_sql("select * from {local_onlinetest_users} where onlinetestid=$inprogress_coursename->id AND userid = $USER->id");
                $enrolledon = $userquizrecord->timecreated;
                if ($gradeitem->id)
                $usergrade = $DB->get_record_sql("select * from {grade_grades} where itemid = $gradeitem->id AND userid = $USER->id");
                if ($usergrade) {
                    $mygrade = round($usergrade->finalgrade, 2);
                    if ($usergrade->finalgrade >= $gradepass) {
                        $completedon = date("j M 'Y", $usergrade->timemodified);
                        $status = 'Completed';
                        $can_review = 1;
                    } else {
                        $status = 'Incomplete';
                        $completedon = '-';
                    }

                } else {
                    $mygrade = 0;
                    $status = 'Pending';
                    $completedon = '-';
                    $attempts = 0;
                }
                // if($inprogress_coursename->timeopen==0 AND $inprogress_coursename->timeclose==0) {
                //     $dates= get_string('open', 'local_onlinetests');
                // } elseif(!empty($inprogress_coursename->timeopen) AND empty($inprogress_coursename->timeclose)) {
                //     $timeopen = $inprogress_coursename->timeopen;
                // } elseif (empty($inprogress_coursename->timeopen) AND !empty($inprogress_coursename->timeclose)) {
                //     $timeclose = $inprogress_coursename->timeclose;
                // } else {
                //     $timeopen = $inprogress_coursename->timeopen;
                //     $timeclose = $inprogress_coursename->timeclose;
                // }
                $testfullname = $inprogress_coursename->name;
                $testname = strlen($testfullname) > 35 ? substr($testfullname, 0, 35)."..." : $testfullname;
                //$buttons = implode('',$buttons);
                $onerow['id'] = $cm->id;
                $onerow['name'] = $testfullname;
                $onerow['modname'] = 'quiz';
                $onerow['modplural'] = 'Quizzes';
                $onerow['maxgrade'] = $grademax;
                $onerow['passgrade'] = $gradepass;
                $onerow['mygrade'] = $mygrade;
                $onerow['attempts'] = $attempts;
                $onerow['enrolledon'] = $enrolledon;
                $onerow['completedon'] = $completedon;
                $onerow['status'] = $status;
                $onerow['canreview'] = $can_review;
                $onerow['timeopen'] = $inprogress_coursename->timeopen;
                $onerow['timeclose'] = $inprogress_coursename->timeclose;
                $onerow['userattemptid'] = $userattempt->id;
                $onerow['url'] = $CFG->wwwroot .'/mod/quiz/view.php?id='. $cm->id .'';
                $data[] = $onerow;
            }
        }
        return array('modules' => $data, 'total' => $count);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_onlinetests_returns() {
        return new external_single_structure(
            array(
                'modules' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'activity id'),
                            'url' => new external_value(PARAM_URL, 'activity url', VALUE_OPTIONAL),
                            'name' => new external_value(PARAM_RAW, 'activity module name'),
                            'modname' => new external_value(PARAM_PLUGIN, 'activity module type'),
                            'modplural' => new external_value(PARAM_TEXT, 'activity module plural name'),
                            'maxgrade' => new external_value(PARAM_INT, 'activity max gradepass'),
                            'passgrade' => new external_value(PARAM_INT, 'activity pass grade'),
                            'mygrade' => new external_value(PARAM_FLOAT, 'activity user grade'),
                            'attempts' => new external_value(PARAM_INT, 'activity attempts'),
                            'enrolledon' => new external_value(PARAM_INT, 'user enrolled on'),
                            'completedon' => new external_value(PARAM_TEXT, 'user completed on'),
                            'status' => new external_value(PARAM_TEXT, 'activity status'),
                            'canreview' => new external_value(PARAM_TEXT, 'activity review'),
                            'timeopen' => new external_value(PARAM_INT, 'activity start date'),
                            'timeclose' => new external_value(PARAM_INT, 'activity end date'),
                            'userattemptid' => new external_value(PARAM_TEXT, 'activity attempt id'),
                        )
                    ), 'list of module'
                ),
                'total' => new external_value(PARAM_INT, 'Total')
            )
        );
    }
    public static function data_for_onlinetests_parameters(){
        $filter = new external_value(PARAM_TEXT, 'Filter text');
        $filter_text = new external_value(PARAM_TEXT, 'Filter name',VALUE_OPTIONAL);
        $filter_offset = new external_value(PARAM_INT, 'Offset value',VALUE_OPTIONAL);
        $filter_limit = new external_value(PARAM_INT, 'Limit value',VALUE_OPTIONAL);
        $params = array(
            'filter' => $filter,
            'filter_text' => $filter_text,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit
        );
        return new external_function_parameters($params);
    }
    public static function data_for_onlinetests($filter, $filter_text='', $filter_offset = 0, $filter_limit = 0){
        global $PAGE;

        $params = self::validate_parameters(self::data_for_onlinetests_parameters(), array(
            'filter' => $filter,
            'filter_text' => $filter_text,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit
        ));
        $PAGE->set_context(\context_system::instance());
        $renderable = new \local_onlinetests\output\onlinetests_courses($params['filter'],$params['filter_text'], $params['filter_offset'], $params['filter_limit']);
        $output = $PAGE->get_renderer('local_onlinetests');

        $data= $renderable->export_for_template($output);
        // print_object($data);
        return $data;

    }
    public static function data_for_onlinetests_returns(){
        return new external_single_structure(array (
            'total' => new external_value(PARAM_INT, 'Number of enrolled courses.', VALUE_OPTIONAL),
            'inprogresscount'=>  new external_value(PARAM_INT, 'Number of inprogress course count.'),
            'completedcount'=>  new external_value(PARAM_INT, 'Number of complete course count.'),
            'exam_count_view'=>  new external_value(PARAM_INT, 'Number of courses count.'),
            'enableslider'=>  new external_value(PARAM_INT, 'Flag for enable the slider.'),
            'inprogress_elearning_available'=>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
            'course_count_view'=>  new external_value(PARAM_TEXT, 'to add course count class'),
            'functionname' => new external_value(PARAM_TEXT, 'Function name'),
            'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
            'onlineteststemplate' => new external_value(PARAM_INT, 'template name',VALUE_OPTIONAL),
            'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, true),
            'moduledetails' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'Id of the test'),
                        'name' => new external_value(PARAM_TEXT, 'Name of test'),
                        'testfullname' => new external_value(PARAM_TEXT, 'Fullname of the test'),
                        'maxgrade' => new external_value(PARAM_TEXT, 'Maxgrade of the test'),
                        'passgrade' => new external_value(PARAM_TEXT, 'Passgrade of the test'),
                        'mygrade' => new external_value(PARAM_TEXT, 'User grade of the test'),
                        'attempts' => new external_value(PARAM_INT, 'Attempts of the test'),
                        'enrolledon' => new external_value(PARAM_TEXT, 'Date of enrolment to the test'),
                        'completedon' => new external_value(PARAM_TEXT, 'Date of Completion to the test'),
                        'status' => new external_value(PARAM_TEXT, 'User Status of the test'),
                        'canreview' => new external_value(PARAM_INT, 'Flag for the review capability check '),
                        'dates' => new external_value(PARAM_TEXT, 'Dates of availability of the test'),
                        'userattemptid' => new external_value(PARAM_INT, 'Id of the attempt'),
                        'sesskey' => new external_value(PARAM_RAW, 'Session id of logged in user'),
                        'configpath' => new external_value(PARAM_RAW, 'wwwroot path of the instance'),
                        'certificate_exists' => new external_value(PARAM_BOOL, 'Boolean of certificate existance'),
                        'certificate_download' => new external_value(PARAM_BOOL, 'Boolean of certificate download capability'),
                        'certificateid' => new external_value(PARAM_INT, 'Id of the mapped certificate'),
                        'starttest_url' => new external_value(PARAM_RAW, 'Url of the test'),
                        'can_start_test' => new external_value(PARAM_BOOL, 'Flag for the test'),
                        'can_start_test' => new external_value(PARAM_BOOL, 'Flag for the test'),
                        'index' => new external_value(PARAM_INT, 'Index of Card'),
                    )
                )
            ),
            // 'sub_tab' => new external_value(PARAM_INT, 'inprogress = 0 & completed = 1'),
            'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
            'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
            'index' => new external_value(PARAM_INT, 'number of courses count'),
            'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
            'filter_text' => new external_value(PARAM_TEXT, 'filtertext content',VALUE_OPTIONAL),
            'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
            'viewMoreCard' => new external_value(PARAM_BOOL, 'More info card to display'),
            'certificate_exists' => new external_value(PARAM_RAW ,'certificate_exists',VALUE_OPTIONAL),
            'certificate_download' => new external_value(PARAM_RAW ,'certificate_download',VALUE_OPTIONAL),
            'certificateid' => new external_value (PARAM_RAW, 'certificateid',VALUE_OPTIONAL)
               //'table_class' => new external_value(PARAM_TEXT, 'class for the table'),
        ));
    }
    public static function data_for_onlinetests_paginated_parameters(){
        return new external_function_parameters([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
                VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'contextid'),
            'filterdata' => new external_value(PARAM_RAW, 'filters applied'),
        ]);
    }
    public static function data_for_onlinetests_paginated($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata){
        global $DB, $PAGE;
        require_login();
        $PAGE->set_url('/local/courses/userdashboard.php', array());
        $PAGE->set_context($contextid);

        $decodedoptions = (array)json_decode($options);
        $decodedfilter = (array)json_decode($filterdata);
        $filter = $decodedoptions['filter'];
        $filter_text = isset($decodedfilter['search_query']) ? $decodedfilter['search_query'] : '';
        $filter_offset = $offset;
        $filter_limit = $limit;

        $renderable = new \local_onlinetests\output\onlinetests_courses($filter, $filter_text, $filter_offset, $filter_limit);
        $output = $PAGE->get_renderer('local_onlinetests');
        $data = $renderable->export_for_template($output);
        $totalcount = $renderable->coursesViewCount;
        return [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' => array($data),
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }
    public static function data_for_onlinetests_paginated_returns(){
        return new external_single_structure([
        'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
        'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
        'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
        'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        'records' => new external_multiple_structure(
                new external_single_structure(array (
                    'total' => new external_value(PARAM_INT, 'Number of enrolled courses.', VALUE_OPTIONAL),
                    'inprogresscount'=>  new external_value(PARAM_INT, 'Number of inprogress course count.'),
                    'completedcount'=>  new external_value(PARAM_INT, 'Number of complete course count.'),
                    'exam_count_view'=>  new external_value(PARAM_INT, 'Number of courses count.'),
                    // 'enableslider'=>  new external_value(PARAM_INT, 'Flag for enable the slider.'),
                    'inprogress_elearning_available'=>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
                    'course_count_view'=>  new external_value(PARAM_TEXT, 'to add course count class'),
                    'functionname' => new external_value(PARAM_TEXT, 'Function name'),
                    'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
                    'onlineteststemplate' => new external_value(PARAM_INT, 'template name',VALUE_OPTIONAL),
                    'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, false),
                    'moduledetails' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'Id of the test'),
                                'name' => new external_value(PARAM_TEXT, 'Name of test'),
                                'testfullname' => new external_value(PARAM_TEXT, 'Fullname of the test'),
                                'maxgrade' => new external_value(PARAM_TEXT, 'Maxgrade of the test'),
                                'passgrade' => new external_value(PARAM_TEXT, 'Passgrade of the test'),
                                'mygrade' => new external_value(PARAM_TEXT, 'User grade of the test'),
                                'attempts' => new external_value(PARAM_INT, 'Attempts of the test'),
                                'enrolledon' => new external_value(PARAM_TEXT, 'Date of enrolment to the test'),
                                'completedon' => new external_value(PARAM_TEXT, 'Date of Completion to the test'),
                                'status' => new external_value(PARAM_TEXT, 'User Status of the test'),
                                'canreview' => new external_value(PARAM_INT, 'Flag for the review capability check '),
                                'dates' => new external_value(PARAM_TEXT, 'Dates of availability of the test'),
                                'userattemptid' => new external_value(PARAM_INT, 'Id of the attempt'),
                                'sesskey' => new external_value(PARAM_RAW, 'Session id of logged in user'),
                                'configpath' => new external_value(PARAM_RAW, 'wwwroot path of the instance'),
                                'certificate_exists' => new external_value(PARAM_BOOL, 'Boolean of certificate existance'),
                                'certificate_download' => new external_value(PARAM_BOOL, 'Boolean of certificate download capability'),
                                'certificateid' => new external_value(PARAM_INT, 'Id of the mapped certificate'),
                                'starttest_url' => new external_value(PARAM_RAW, 'Url of the test'),
                                'can_start_test' => new external_value(PARAM_BOOL, 'Flag for the test'),
                                'can_start_test' => new external_value(PARAM_BOOL, 'Flag for the test'),
                            )
                        )
                    ),
                    // 'sub_tab' => new external_value(PARAM_INT, 'inprogress = 0 & completed = 1'),
                    'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
                    'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
                    'index' => new external_value(PARAM_INT, 'number of courses count'),
                    'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
                    'filter_text' => new external_value(PARAM_TEXT, 'filtertext content',VALUE_OPTIONAL),
                    // 'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
                    'certificate_exists' => new external_value(PARAM_RAW ,'certificate_exists',VALUE_OPTIONAL),
                    'certificate_download' => new external_value(PARAM_RAW ,'certificate_download',VALUE_OPTIONAL),
                    'certificateid' => new external_value (PARAM_RAW, 'certificateid',VALUE_OPTIONAL)
                       //'table_class' => new external_value(PARAM_TEXT, 'class for the table'),
                )
            )
        )
    ]);
    }
}
