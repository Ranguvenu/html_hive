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
 * @package Bizlms
 * @subpackage local_classroom
 */
defined('MOODLE_INTERNAL') || die();
global $PAGE, $OUTPUT;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/local/classroom/lib.php');
use \local_classroom\classroom as classroom;
use \local_classroom\form\classroom_form as classroom_form;


class local_classroom_external extends external_api {

    public static function get_classrooms_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0)
        ]);
    }

    public static function get_classrooms($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0
    ) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::get_classrooms_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];

        $formatted_dataoptions = json_decode($dataoptions);
        $search = $formatted_dataoptions->search_query;
        $status = $formatted_dataoptions->status;

        $classrooms = (new classroom)->get_classrooms($status, $search, $offset, $limit);
        // print_object($classrooms);exit;
        $totalcount = $classrooms['classroomscount'];
        $formattedclassrooms = $classrooms['classrooms'];

        $return = [
            'totalcount' => $totalcount,
            'records' => $formattedclassrooms,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
        // print_object($return);exit;
        return $return;

    }

    public static function get_classrooms_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'classroomid' => new external_value(PARAM_INT, 'Classroom id'),
                                    'classname' => new external_value(PARAM_RAW, 'Classroom name'),
                                    'classname_string' => new external_value(PARAM_RAW, 'Classroom formatted name'),
                                    'classroomurl' => new external_value(PARAM_RAW, 'Classroom url'),
                                    'crstatustitle' => new external_value(PARAM_TEXT, 'Classroom status title'),
                                    'classesimg' => new external_value(PARAM_RAW, 'Classroom seatallocation'),
                                    'classroomstatusclass' => new external_value(PARAM_TEXT, 'Classroom status class'),
                                    'description' => new external_value(PARAM_RAW, 'Classroom description'),
                                    'descriptionstring' => new external_value(PARAM_RAW, 'Classroom formatted description'),
                                    'isdescription' => new external_value(PARAM_RAW, 'Classroom isdescription available'),
                                    'seatallocation' => new external_value(PARAM_RAW, 'Classroom seatallocation'),
                                    'usercreated' => new external_value(PARAM_TEXT, 'Classroom usercreated'),
                                    'startdate' => new external_value(PARAM_RAW, 'Classroom startdate'),
                                    'enddate' => new external_value(PARAM_RAW, 'Classroom enddate'),
                                    // 'classroom_actionstatus' => new external_value(PARAM_RAW, 'Classroom action status'),
                                    'courses' => new external_multiple_structure(
                                                    new external_single_structure(
                                                        array(
                                                            'coursetitle' => new external_value(PARAM_RAW, 'Classroom Course data'),
                                                            'coursename' => new external_value(PARAM_RAW, 'Classroom Course data'),
                                                            'courseurl' => new external_value(PARAM_RAW, 'Classroom Course data'),
                                                            )
                                                    )
                                                ),
                                    'enrolled_users' => new external_value(PARAM_INT, 'Classroom enrolled_users'),
                                    'departmentname' => new external_value(PARAM_RAW, 'Classroom departmentname'),
                                    'departmenttitle' => new external_value(PARAM_RAW, 'Classroom departmenttitle'),
                                    'trainers' => new external_multiple_structure(
                                                    new external_single_structure(
                                                        array(
                                                            'trainerpic' => new external_value(PARAM_RAW, 'Classroom classroomtrainerpic'),
                                                            'trainername' => new external_value(PARAM_TEXT, 'Classroom trainername'),
                                                            'trainerdesignation' => new external_value(PARAM_TEXT, 'Classroom trainerdesignation'),
                                                            'trainerprofileurl' => new external_value(PARAM_TEXT, 'Classroom trainerprofileurl'),
                                                            )
                                                    )
                                                ),
                                    'moretrainers' => new external_multiple_structure(
                                                    new external_single_structure(
                                                        array(
                                                            'classroomtrainerpic' => new external_value(PARAM_RAW, 'Classroom classroomtrainerpic'),
                                                            'trainername' => new external_value(PARAM_TEXT, 'Classroom trainername'),
                                                            'trainerdesignation' => new external_value(PARAM_TEXT, 'Classroom trainerdesignation'),
                                                            )
                                                    )
                                                ),
                                    'trainerslimit' => new external_value(PARAM_INT, 'Classroom trainerslimit'),
                                    // 'editicon' => new external_value(PARAM_RAW, 'Classroom editicon'),
                                    // 'deleteicon' => new external_value(PARAM_RAW, 'Classroom deleteicon'),
                                    // 'assignusersicon' => new external_value(PARAM_RAW, 'Classroom assignusersicon'),
                                    'action' => new external_value(PARAM_BOOL, 'Classroom action'),
                                    'edit' => new external_value(PARAM_BOOL, 'Classroom edit'),
                                    'delete' => new external_value(PARAM_BOOL, 'Classroom delete'),
                                    'assignusers' => new external_value(PARAM_BOOL, 'Classroom assignusers'),
                                    'assignusersurl' => new external_value(PARAM_RAW, 'Classroom assignusersurl'),
                                    'classroomcompletion' => new external_value(PARAM_BOOL, 'Classroom classroomcompletion'),
                                    'mouse_overicon' => new external_value(PARAM_BOOL, 'Classroom mouse_overicon'),
                                )
                            )
            )
        ]);
    }

    /**
     * [classroomviewsessions description]
     * @method classroomviewsessions
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewsessions_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service')
        ]);
    }

    /**
     * [classroomviewsessions description]
     * @method classroomviewsessions
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewsessions($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewsessions_parameters(),
            [
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];

        $decodeddataoptions = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $renderer = $PAGE->get_renderer('local_classroom');
        $sessions = (new classroom)->classroomsessions($decodeddataoptions->classroomid, $stable);
        $totalcount = $sessions['sessionscount'];
        $functinname = 'viewclassroom'.$decodeddataoptions->tabname;
        if(method_exists($renderer, $functinname)){
            $sessionsdata = $renderer->$functinname($sessions['sessions'],$decodeddataoptions->classroomid);
        }
        $return = [
            'totalcount' => $totalcount,
            'records' => $sessionsdata['data'],
            'createsession' => $sessionsdata['createsession'],
            'options' => $options,
            'classroomid' => $decodeddataoptions->classroomid,
            'dataoptions' => $dataoptions,
        ];
        return $return;

    }

    public static function classroomviewsessions_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'createsession' => new external_value(PARAM_BOOL, 'createsession'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'date' => new external_value(PARAM_RAW, 'session startdate'),
                                    'starttime' => new external_value(PARAM_RAW, 'timelimit'),
                                    'endtime' => new external_value(PARAM_RAW, 'timelimit'),
                                    'link' => new external_value(PARAM_RAW, 'link'),
                                    'room' => new external_value(PARAM_RAW, 'room'),
                                    'status' => new external_value(PARAM_RAW, 'status'),
                                    'attendacecount' => new external_value(PARAM_RAW, 'attendacecount'),
                                    'trainer' => new external_value(PARAM_RAW, 'trainer'),
                                    'action' => new external_value(PARAM_RAW, 'action'),
                                    'cfgwwwroot' => new external_value(PARAM_RAW, 'cfgwwwroot'),
                                    'editicon' => new external_value(PARAM_RAW, 'name'),
                                    'deleteicon' => new external_value(PARAM_RAW, 'name'),
                                    'assignrolesicon' => new external_value(PARAM_RAW, 'name'),
                                    'uploadattendanceicon' => new external_value(PARAM_RAW, 'name'),

                                )
                            )
            )
        ]);
    }


    /**
     * [classroomviewcourses description]
     * @method classroomviewcourses
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewcourses_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),

                'offset' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service')
        ]);
    }


    /**
     * [classroomviewcourses description]
     * @method classroomviewcourses
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewcourses($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewcourses_parameters(),
            [
                'contextid' => $contextid,
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata,
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];

        $decodeddataoptions = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $renderer = $PAGE->get_renderer('local_classroom');
        $courses = (new classroom)->classroom_courses($decodeddataoptions->classroomid, $stable);
        $totalcount = $courses['classroomcoursescount'];
        $functinname = 'viewclassroom'.$decodeddataoptions->tabname;
        if(method_exists($renderer, $functinname)){
            $coursesdata = $renderer->$functinname($courses['classroomcourses'],$decodeddataoptions->classroomid);
            $assigncourses = $coursesdata['assign_courses'];
            $selfenrolmenttabcap = $coursesdata['selfenrolmenttabcap'];
        }
        $return = [
            'assigncourses' => $assigncourses,
            'selfenrolmenttabcap' => $selfenrolmenttabcap,
            'classroomid' => $classroomid,
            'totalcount' => $totalcount,
            'records' => $coursesdata['data'],
            'options' => $options,
            'classroomid' => $decodeddataoptions->classroomid,
            'dataoptions' => $dataoptions,
        ];
        return $return;

    }

    public static function classroomviewcourses_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'assigncourses' => new external_value(PARAM_BOOL, 'assigncourses'),
            'selfenrolmenttabcap' => new external_value(PARAM_BOOL, 'selfenrolmenttabcap'),
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'status' => new external_value(PARAM_RAW, 'status'),
                                    'action' => new external_value(PARAM_BOOL, 'action'),
                                    'linkpath' => new external_value(PARAM_RAW, 'linkpath'),

                                )
                            )
            )
        ]);
    }


    /**
     * [classroomlastchildpopup description]
     * @method classroomlastchildpopup
     * @param  [type]                contextid [which context]
     * @param  [type]                classroomid      [classroomid]
     */
    public static function classroomlastchildpopup_parameters() {
        return new external_function_parameters([
                'classroomid' => new external_value(PARAM_INT, 'classroomid'),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
        ]);
    }


    /**
     * [classroomlastchildpopup description]
     * @method classroomlastchildpopup
     * @param  [type]                contextid [which context]
     * @param  [type]                classroomid      [classroomid]
     */
    public static function classroomlastchildpopup($classroomid,$contextid) {
        global $DB, $PAGE;
        // print_object($classroomid)
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomlastchildpopup_parameters(),
            [
                'classroomid' => $classroomid,
                'contextid' => $contextid,
            ]
        );

        $renderer = $PAGE->get_renderer('local_classroom');

        $data = $renderer->viewclassroomlastchildpopup($classroomid);
        $array = array();
        $array[] = $data;
        $return = [
            'records' => $array
        ];

        return $return;


    }

    public static function classroomlastchildpopup_returns() {
        return new external_single_structure([
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'startdate' => new external_value(PARAM_RAW, 'startdate'),
                                    'enddate' => new external_value(PARAM_RAW, 'enddate'),
                                    'classroomlocation' => new external_value(PARAM_RAW, 'classroomlocation'),
                                    'classroomdepartment' => new external_value(PARAM_RAW, 'classroomdepartment'),
                                    'trainers' => new external_single_structure(
                                                        array(
                                                            'classroomtrainerpic' => new external_value(PARAM_RAW, 'classroomtrainerpic'),
                                                            'trainername' => new external_value(PARAM_RAW, 'trainername'),
                                                            'trainerdesignation' => new external_value(PARAM_RAW, 'trainerdesignation'),
                                                            'traineremail' => new external_value(PARAM_RAW, 'traineremail'),
                                                        )
                                                    ),
                                    'classroomid' => new external_value(PARAM_INT, 'classroomid'),
                                    'totalseats' => new external_value(PARAM_RAW, 'totalseats'),
                                    'allocatedseats' => new external_value(PARAM_INT, 'allocatedseats'),
                                    'description' => new external_value(PARAM_RAW, 'description'),
                                    'descriptionstring' => new external_value(PARAM_RAW, 'descriptionstring'),
                                    'isdescription' => new external_value(PARAM_RAW, 'isdescription'),
                                    'seats_progress' => new external_value(PARAM_INT, 'seats_progress'),
                                    'contextid' => new external_value(PARAM_INT, 'contextid'),
                                    'linkpath' => new external_value(PARAM_RAW, 'linkpath'),
                                )
                            )
            )
        ]);
    }


    /**
     * [classroomviewusers description]
     * @method classroomviewusers
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewusers_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),

                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service')
        ]);
    }


    /**
     * [classroomviewusers description]
     * @method classroomviewusers
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewusers($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ) {
        global $DB, $PAGE, $CFG;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewusers_parameters(),
            [
                'contextid' => $contextid,
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];

        $decodeddataoptions = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $renderer = $PAGE->get_renderer('local_classroom');
        $users = (new classroom)->classroomusers($decodeddataoptions->classroomid, $stable);
        $totalcount = $users['classroomuserscount'];
        $functinname = 'viewclassroom'.$decodeddataoptions->tabname;
        if(method_exists($renderer, $functinname)){
            $usersdata = $renderer->$functinname($users['classroomusers'],$decodeddataoptions->classroomid);
            $assignusers = $usersdata['assignusers'];
        }
        $return = [
            'assignusers' => $assignusers,
            'totalcount' => $totalcount,
            'mapped_certificate' => $usersdata['mapped_certificate'],
            'records' => $usersdata['data'],
            'options' => $options,
            'wwwroot' => $CFG->wwwroot,
            'classroomid' => $decodeddataoptions->classroomid,
            'dataoptions' => $dataoptions,
        ];
        return $return;

    }

    public static function classroomviewusers_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'mapped_certificate' => new external_value(PARAM_BOOL, 'mapped_certificate'),
            'assignusers' => new external_value(PARAM_BOOL, 'assignusers'),
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'wwwroot' => new external_value(PARAM_RAW, 'wwwroot'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'employeeid' => new external_value(PARAM_RAW, 'employeeid'),
                                    'email' => new external_value(PARAM_RAW, 'email'),
                                    'supervisor' => new external_value(PARAM_RAW, 'supervisor'),
                                    'attendedsessions' => new external_value(PARAM_RAW, 'attendedsessions'),
                                    'hours' => new external_value(PARAM_RAW, 'hours'),
                                    'completionstatus' => new external_value(PARAM_BOOL, 'status'),
                                    'downloadcertificate' => new external_value(PARAM_RAW, 'downloadcertificate'),
                                    'certificateid' => new external_value(PARAM_RAW, 'certificateid'),
                                    'moduleid' => new external_value(PARAM_RAW,'moduleid',VALUE_OPTIONAL),
                                    'userid' => new external_value(PARAM_RAW,'userid',VALUE_OPTIONAL),
                                )

                            )
            )
        ]);
    }

    /**
     * [classroomviewfeedbacks description]
     * @method classroomviewfeedbacks
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewfeedbacks_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),

                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service')
        ]);
    }


    /**
     * [classroomviewfeedbacks description]
     * @method classroomviewfeedbacks
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewfeedbacks($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewfeedbacks_parameters(),
            [
                'contextid' => $contextid,
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];

        $decodeddataoptions = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $renderer = $PAGE->get_renderer('local_classroom');
        $feedbacks = (new classroom)->classroom_evaluations($decodeddataoptions->classroomid, $stable);
        $totalcount = $feedbacks['evaluationscount'];
        $functinname = 'viewclassroom'.$decodeddataoptions->tabname;
        $lineaction = false;
        if(method_exists($renderer, $functinname)){
            $feedbacksdata = $renderer->$functinname($feedbacks['evaluations'],$decodeddataoptions->classroomid);
            $createfeedback = $feedbacksdata['createfeedback'];
            if ((has_capability('local/classroom:editfeedback', context_system::instance()) || has_capability('local/classroom:deletefeedback', context_system::instance()))&&(has_capability('local/classroom:manageclassroom', context_system::instance()))) {
                $lineaction = true;
            }
        }
        $return = [
            'createfeedback' => $createfeedback,
            'lineaction'=>$lineaction,
            'totalcount' => $totalcount,
            'records' => $feedbacksdata['data'],
            'options' => $options,
            'classroomid' => $decodeddataoptions->classroomid,
            'dataoptions' => $dataoptions,
        ];
        return $return;

    }

    public static function classroomviewfeedbacks_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'createfeedback' => new external_value(PARAM_BOOL, 'createfeedback'),
            'lineaction' => new external_value(PARAM_BOOL, 'createfeedback'),
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'feedbackview' => new external_value(PARAM_BOOL, 'feedbackview'),
                                    'feedbacktype' => new external_value(PARAM_RAW, 'feedbacktype'),
                                    'trainer' => new external_value(PARAM_RAW, 'trainer'),
                                    'submittedcount' => new external_value(PARAM_RAW, 'submittedcount'),
                                    'url' => new external_value(PARAM_RAW, 'url'),
                                    'action' => new external_value(PARAM_BOOL, 'action'),
                                    'cfgwwwroot' => new external_value(PARAM_RAW, 'cfgwwwroot'),
                                    'editicon' => new external_value(PARAM_RAW, 'name'),
                                    'preview' => new external_value(PARAM_RAW, 'name'),
                                    'deleteicon' => new external_value(PARAM_RAW, 'name'),
                                    'string' => new external_value(PARAM_BOOL, 'string'),
                                )
                            )
            )
        ]);
    }


     /**
     * [classroomviewcompletioninfo description]
     * @method classroomviewcompletioninfo
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewcompletioninfo_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'classroomid' => new external_value(PARAM_INT, 'classroomid'),
                'name' => new external_value(PARAM_RAW, 'name')
        ]);
    }


    /**
     * [classroomviewcompletioninfo description]
     * @method classroomviewcompletioninfo
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewcompletioninfo($contextid, $classroomid,$name) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewcompletioninfo_parameters(),
            [
                'contextid' => $contextid,
                'classroomid' => $classroomid,
                'name' => $name,
            ]
        );

        $completion_settings = (new classroom)->classroom_completion_settings_tab($classroomid);
        $return = [
            'records' => $completion_settings,
            'classroomid' => $classroomid,
        ];
        return $return;

    }

    public static function classroomviewcompletioninfo_returns() {
        return new external_single_structure([
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'classroomid' => new external_value(PARAM_INT, 'classroomid'),
                                    'courses' => new external_value(PARAM_RAW, 'courses'),
                                    'sessions' => new external_value(PARAM_RAW, 'sessions'),
                                    'tracking' => new external_value(PARAM_RAW, 'tracking'),
                                )
                            )
            )
        ]);
    }

     /**
     * [classroomviewtargetaudience description]
     * @method classroomviewtargetaudience
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewtargetaudience_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'classroomid' => new external_value(PARAM_INT, 'classroomid'),
                'name' => new external_value(PARAM_RAW, 'name')
        ]);
    }


    /**
     * [classroomviewtargetaudience description]
     * @method classroomviewtargetaudience
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewtargetaudience($contextid, $classroomid,$name) {
        global $DB, $PAGE;
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewtargetaudience_parameters(),
            [
                'contextid' => $contextid,
                'classroomid' => $classroomid,
                'name' => $name,
            ]
        );

        $targetaudience = (new classroom)->classroomtarget_audience_tab($classroomid);
        $return = [
            'records' => $targetaudience,
            'classroomid' => $classroomid,
        ];
        return $return;


    }

    public static function classroomviewtargetaudience_returns() {
        return new external_single_structure([
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'department' => new external_value(PARAM_RAW, 'department'),
                                    'subdepartment' => new external_value(PARAM_RAW, 'subdepartment'),
                                    'group' => new external_value(PARAM_RAW, 'group'),
                                    'hrmsrole' => new external_value(PARAM_RAW, 'hrmsrole'),
                                    'designation' => new external_value(PARAM_RAW, 'designation'),
                                    'location' => new external_value(PARAM_RAW, 'location'),
                                    'grade' => new external_value(PARAM_RAW, 'grade'),
                                )
                            )
            )
        ]);
    }

    /**
     * [classroomviewusers description]
     * @method classroomviewusers
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewrequestedusers_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),

                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service')
        ]);
    }


    /**
     * [classroomviewrequestedusers description]
     * @method classroomviewrequestedusers
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewrequestedusers($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewrequestedusers_parameters(),
            [
                'contextid' => $contextid,
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];

        $decodeddataoptions = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $classroom = $DB->get_records('local_request_records', array('compname' => 'classroom' , 'componentid' => $decodeddataoptions->classroomid));
        $output = $PAGE->get_renderer('local_request');
        $component = 'classroom';
        $data = (new classroom)->classroomrequestedusers($classroom,$component,'','',$decodeddataoptions->classroomid, $stable);
        $totalcount = $data['requestscount'];
        $data = (new classroom)->requestsdata($data['requestlist']);


        $return = [
            'totalcount' => $totalcount,
            'records' => $data,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
        return $return;

    }

    public static function classroomviewrequestedusers_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'status' => new external_value(PARAM_RAW, 'status'),
                                    'approvestatus' => new external_value(PARAM_INT, 'approvestatus'),
                                    'rejectstatus' => new external_value(PARAM_RAW, 'rejectstatus'),
                                    'compname' => new external_value(PARAM_RAW, 'compname'),
                                    'requestedby' => new external_value(PARAM_RAW, 'requestedby'),
                                    'requesteddate' => new external_value(PARAM_RAW, 'requesteddate'),
                                    'componentid' => new external_value(PARAM_INT, 'componentid'),
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'requesteduser' => new external_value(PARAM_RAW, 'requesteduser'),
                                    'responder' => new external_value(PARAM_RAW, 'responder'),
                                    'respondeddate' => new external_value(PARAM_RAW, 'respondeddate'),
                                    'componentname' => new external_value(PARAM_RAW, 'componentname'),
                                    'capability' => new external_single_structure(
                                                        array(
                                                            'viewrecord_capability' => new external_value(PARAM_INT, 'viewrecord_capability'),
                                                            'approve_capability' => new external_value(PARAM_INT, 'approve_capability'),
                                                            'deny_capability' => new external_value(PARAM_INT, 'deny_capability'),
                                                            'addrecord_capability' => new external_value(PARAM_INT, 'addrecord_capability'),
                                                            'deleterecord_capability' => new external_value(PARAM_INT, 'deleterecord_capability'),
                                                            'addcomment_capability' => new external_value(PARAM_INT, 'addcomment_capability')
                                                            )
                                                        )
                                )
                            )
            )
        ]);
    }

    public static function classroom_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function classroom_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();

        $classroom = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new classroom_form(null, array('form_status' => $form_status, 'id' => $id), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $formheaders = array_keys($mform->formstatus);
            if (method_exists(new classroom, $formheaders[$form_status])) {
                $classroomid = (new classroom)->{$formheaders[$form_status]}($validateddata);
                if($formheaders[$form_status] == 'manage_classroom' || $formheaders[$form_status] == 'target_audience' || $formheaders[$form_status] == 'classroom_misc' || $formheaders[$form_status] == 'prerequisites'){
                    // if(class_exists('\block_trending_modules\lib')){
                    //     $trendingclass = new \block_trending_modules\lib();
                    //     if(method_exists($trendingclass, 'trending_modules_crud')){
                    //         $trendingclass->trending_modules_crud($classroomid, 'local_classroom');
                    //     }
                    // }
                }

            } else {
                throw new moodle_exception('missingfunction', 'local_classroom');
            }
            $next = $form_status + 1;
            $nextform = array_key_exists($next, $formheaders);
            if ($nextform !== false/*&& end($formheaders) !== $form_status*/) {
                $form_status = $next;
                $error = false;
            } else {
                $form_status = -1;
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingclassroom', 'local_classroom');
        }
        $return = array(
            'id' => $classroomid,
            'form_status' => $form_status);
        return $return;

    }

    public static function classroom_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

    public static function delete_classroom_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                 'classroomid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'classroomname' => new external_value(PARAM_RAW, 'Action of the event', false),
            )
        );
    }

    public static function delete_classroom_instance($action, $id, $confirm,$classroomname) {
        global $DB;
        try {

            //$classroomcourses = $DB->get_records_menu('local_classroom_courses',
            //    array('classroomid' => $classroomid), 'courseid', 'id, courseid');
            //foreach($classroomcourses as $classroomcourse){
            //    $classroomtrainers = $DB->get_records_menu('local_classroom_trainers',
            //        array('classroomid' => $classroomid), 'trainerid', 'id, trainerid');
            //    if (!empty($classroomtrainers)) {
            //        foreach ($classroomtrainers as $classroomtrainer) {
            //            $unenrolclassroomtrainer = (new classroom)->manage_classroom_course_enrolments($classroomcourse, $classroomtrainer,
            //                'editingteacher', 'unenrol');
            //        }
            //    }
            //    $classroomusers = $DB->get_records_menu('local_classroom_users',
            //        array('classroomid' => $classroomid), 'userid', 'id, userid');
            //    if (!empty($classroomusers)) {
            //        foreach ($classroomusers as $classroomuser) {
            //            $unenrolclassroomuser = (new classroom)->manage_classroom_course_enrolments($classroomcourse, $classroomuser,
            //                'employee', 'unenrol');
            //        }
            //    }
            //    $DB->delete_records('local_classroom_courses', array('classroomid' => $id,'courseid' => $classroomcourse));
            //}

            $DB->delete_records('local_classroom_courses', array('classroomid' => $id));

            $local_evaluations=$DB->get_records_menu('local_evaluations',  array('plugin' =>'classroom', 'instance' =>$id), 'id', 'id, id as evid');
            foreach($local_evaluations as $local_evaluation){

                $DB->delete_records('local_evaluation_item', array('evaluation' => $local_evaluation));
                $DB->delete_records('local_evaluation_users',  array('evaluationid' => $local_evaluation));

                $evaluation_completions=$DB->get_records_menu('local_evaluation_completed',  array('evaluation' =>$local_evaluation), 'id', 'id, id as evcmtd');
                foreach($evaluation_completions as $evaluation_completion){
                    $DB->delete_records('local_evaluation_value', array('completed' =>$evaluation_completion));
                    $DB->delete_records('local_evaluation_completed', array('id' =>$evaluation_completion));
                }
                $DB->delete_records('local_evaluations',  array('id' => $local_evaluation));
            }


            $DB->delete_records('local_classroom_attendance', array('classroomid' => $id));
    
            $calendarevent_delete = classroom::classroom_calendar_delete_event($id);
            $DB->delete_records('local_classroom_sessions', array('classroomid' => $id));

            $DB->delete_records('local_classroom_users', array('classroomid' => $id));
            $DB->delete_records('local_classroom_trainers', array('classroomid' => $id));
            $DB->delete_records('local_classroom_trainerfb', array('classroomid' => $id));
            $DB->delete_records('local_classroom_completion', array('classroomid' => $id));

            // delete events in calendar
            $DB->delete_records('event', array('plugin_instance'=>$id, 'plugin'=>'local_classroom')); // added by sreenivas
            $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
            );

            $event = \local_classroom\event\classroom_deleted::create($params);
            $event->add_record_snapshot('local_classroom', $id);
            $event->trigger();
            $DB->delete_records('local_classroom', array('id' => $id));
            // if(class_exists('\block_trending_modules\lib')){
            //     $trendingclass = new \block_trending_modules\lib();
            //     if(method_exists($trendingclass, 'trending_modules_crud')){
            //         $classroom_object = new stdClass();
            //         $classroom_object->id = $id;
            //         $classroom_object->module_type = 'local_classroom';
            //         $classroom_object->delete_record = True;
            //         $trendingclass->trending_modules_crud($classroom_object, 'local_classroom');
            //     }
            // }
            $return = true;
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_classroom');
            $return = false;
        }
        return $return;
    }

    public static function delete_classroom_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    public static function manageclassroomStatus_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_RAW, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                 'classroomid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'actionstatusmsg' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'classroomname' => new external_value(PARAM_RAW, 'Action of the event', false),
            )
        );
    }

    public static function manageclassroomStatus_instance($action, $id, $confirm,$actionstatusmsg,$classroomname) {
        global $DB,$USER, $PAGE;
        $systemcontext = \context_system::instance();
        $PAGE->set_context($systemcontext);
        $return_status="";
        try {
            if ($action === 'selfenrol') {

                $return = (new classroom)->classroom_self_enrolment($id,$USER->id, $selfenrol=1,'self');
                if($return > 0){
                    $params=array();
                    /*$sql = "SELECT lw.sortorder as classroomwaitinglistno,c.name as classroom,
                            (select GROUP_CONCAT(lcw.id) FROM {local_classroom_waitlist} as lcw where lcw.classroomid=lw.classroomid and lcw.enrolstatus=0) as active
                            FROM {local_classroom_waitlist} as lw
                            JOIN {local_classroom} AS c ON c.id = lw.classroomid
                            where lw.id=:waitlistid";*/
                    // $params['waitlistid'] = $return;
                    // $stringobj=$DB->get_record_sql($sql, $params);
                    // $active=explode(',',$stringobj->active);
                    // $classroomwaitinglistno=array_search ($return, $active);
                    $stringobj = new stdClass();
                    $classroomname=$DB->get_field('local_classroom','name',array('id'=>$id));
                    $stringobj->classroom = $classroomname;
                    $countsql = "SELECT COUNT(id) FROM {local_classroom_waitlist} WHERE classroomid = {$id} AND id <= {$return} ";
                    $stringobj->classroomwaitinglistno = $DB->count_records_sql($countsql);
                    // $stringobj->classroomwaitinglistno=($classroomwaitinglistno+1) ? ($classroomwaitinglistno+1) : $stringobj->classroomwaitinglistno ;
                    $return_status=get_string("classroomwaitlistinfo",'local_classroom',$stringobj);
                }

            }elseif ($action === 'enrolrequest') {

                $return =  (new \local_request\api\requestapi)::create('classroom',$id);
                if($return){
                    $return=true;
                }

            }else{
                $return = (new classroom)->classroom_status_action($id, $action);
            }

        } catch (dml_exception $ex) {
            print_error($ex);
            $return = false;
        }
        $return = array(
            'return' => $return,
            'return_status' => $return_status);

        return $return;

    }

    public static function manageclassroomStatus_instance_returns() {
        // return new external_value(PARAM_BOOL, 'return');
        return new external_single_structure(array(
            'return' => new external_value(PARAM_INT, 'return'),
            'return_status' => new external_value(PARAM_RAW, 'return_status'),
        ));
    }
    public static function classroom_course_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $includes = new external_value(
            PARAM_ALPHA,
            'What other contexts to fetch the frameworks from. (all, parents, self)',
            VALUE_DEFAULT,
            'parents'
        );
        // $limitfrom = new external_value(
        //  PARAM_INT,
        //  'limitfrom we are fetching the records from',
        //  VALUE_DEFAULT,
        //  0
        // );
        // $limitnum = new external_value(
        //  PARAM_INT,
        //  'Number of records to fetch',
        //  VALUE_DEFAULT,
        //  25
        // );
        return new external_function_parameters(array(
            'query' => $query,
            'context' => self::get_context_parameters(),
            'includes' => $includes,
            // 'limitfrom' => $limitfrom,
            // 'limitnum' => $limitnum,
        ));
    }

    public static function classroom_course_selector($query, $context, $includes = 'parents' /*, $limitfrom = 0, $limitnum = 25*/) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::classroom_course_selector_parameters(), array(
            'query' => $query,
            'context' => $context,
            'includes' => $includes,
            // 'limitfrom' => $limitfrom,
            // 'limitnum' => $limitnum,
        ));
        $query = $params['query'];
        $includes = $params['includes'];
        $context = self::get_context_from_params($params['context']);
        // $limitfrom = $params['limitfrom'];
        // $limitnum = $params['limitnum'];

        self::validate_context($context);
        $courses = array();
        if ($query) {
            $queryparams = array();
            $concatsql = '';
            if ((has_capability('local/classroom:manageclassroom', context_system::instance())) && ( !is_siteadmin() && (!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && !has_capability('local/costcenter:manage_multiorganizations', context_system::instance())))) {
                $concatsql .= " AND open_costcenterid = :costcenterid";
                $queryparams['costcenterid'] = $USER->open_costcenterid;
                if ((has_capability('local/classroom:manage_owndepartments', context_system::instance())|| has_capability('local/costcenter:manage_owndepartments', context_system::instance()))) {
                     $concatsql .= " AND open_departmentid = :department";
                     $queryparams['department'] = $USER->open_departmentid;
                 }
           }

            $cousresql = "SELECT c.id, c.fullname
                           FROM {course} AS c
                        
                          WHERE c.visible = 1 AND CONCAT(',',c.open_identifiedas,',') LIKE '%,2,%' AND c.fullname LIKE '%$query%' AND c.id <> " . SITEID . " $concatsql";//AND FIND_IN_SET(2,c.open_identifiedas)
            $courses = $DB->get_records_sql($cousresql, $queryparams);
        }

        return array('courses' => $courses);
    }
    public static function classroom_course_selector_returns() {
        return new external_single_structure(array(
            'courses' => new external_multiple_structure(
                new external_single_structure(array(
                    'id' => new external_value(PARAM_INT, 'ID of the course'),
                    'fullname' => new external_value(PARAM_RAW, 'course fullname'),
                ))
            ),
        ));
    }
    public static function delete_session_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'classroomid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'sessionname' => new external_value(PARAM_RAW, 'Session name', VALUE_OPTIONAL)
            )
        );
    }

    public static function delete_session_instance($action, $id, $confirm) {
        global $DB,$USER;
        try {
            if ($confirm) {
                $classroomid=$DB->get_field('local_classroom_sessions','classroomid',array('id'=>$id));

                //$DB->execute("UPDATE {local_classroom_users}
                //             SET attended_sessions=(attended_sessions-1)
                //             WHERE classroomid=$classroomid and userid in (SELECT userid
                //             FROM {local_classroom_attendance} WHERE sessionid = $id)");
                //
                $classroom_completiondata =$DB->get_record_sql("SELECT id,sessionids
                                        FROM {local_classroom_completion}
                                        WHERE classroomid = $classroomid");

                if($classroom_completiondata->sessionids!=null){
                    $classroom_sessionids=explode(',',$classroom_completiondata->sessionids);
                    $array_diff=array_diff($classroom_sessionids, array($id));
                    if(!empty($array_diff)){
                        $classroom_completiondata->sessionids = implode(',',$array_diff);
                    }else{
                        $classroom_completiondata->sessionids="NULL";
                    }
                    //$DB->execute('UPDATE {local_classroom_completion}
                    //             SET sessionids = REPLACE(sessionids,'.$classroom_sessionids.')
                    //             WHERE id = ' .$classroom_completiondata->id. '');
                    $DB->update_record('local_classroom_completion', $classroom_completiondata);
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $classroom_completiondata->id
                    );

                    $event = \local_classroom\event\classroom_completions_settings_updated::create($params);
                    $event->add_record_snapshot('local_classroom', $classroomid);
                    $event->trigger();


                }


                $DB->delete_records('local_classroom_attendance', array('sessionid' => $id));
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
                );

                $event = \local_classroom\event\classroom_sessions_deleted::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();

                $calendarevent_delete = classroom::classroom_calendar_session_delete_event($classroomid,$id);

                $DB->delete_records('local_classroom_sessions', array('id' => $id));



                $classroom = new stdClass();
                $classroom->id = $classroomid;
                $classroom->totalsessions = $DB->count_records('local_classroom_sessions', array('classroomid' => $classroomid));
                $classroom->activesessions = $DB->count_records('local_classroom_sessions', array('classroomid' => $classroomid,'attendance_status'=>1));
                $DB->update_record('local_classroom', $classroom);

                //$params = array(
                //    'context' => context_system::instance(),
                //    'objectid' => $classroomid
                //);
                //
                //$event = \local_classroom\event\classroom_updated::create($params);
                //$event->add_record_snapshot('local_classroom',$classroomid);
                //$event->trigger();

                $classroom_users=$DB->get_records_menu('local_classroom_users',  array('classroomid' =>$classroomid), 'id', 'id, userid');

                foreach($classroom_users as $classroom_user){

                    $attendedsessions = $DB->count_records('local_classroom_attendance',
                    array('classroomid' => $classroomid,
                        'userid' => $classroom_user, 'status' => 1));

                    $attendedsessions_hours=$DB->get_field_sql("SELECT ((sum(lcs.duration))/60) AS hours
                                                FROM {local_classroom_sessions} as lcs
                                                WHERE  lcs.classroomid =$classroomid
                                                and lcs.id in(SELECT sessionid  FROM {local_classroom_attendance} where classroomid=$classroomid and userid=$classroom_user and status=1)");

                    if(empty($attendedsessions_hours)){
                        $attendedsessions_hours=0;
                    }

                    $DB->execute('UPDATE {local_classroom_users} SET attended_sessions = ' .
                        $attendedsessions . ',hours = ' .
                        $attendedsessions_hours . ', timemodified = ' . time() . ',
                        usermodified = ' . $USER->id . ' WHERE classroomid = ' .
                    $classroomid . ' AND userid = ' . $classroom_user);
                }

                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_classroom');
            $return = false;
        }
        return $return;
    }

    public static function delete_session_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    public static function delete_classroomevaluation_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'classroomid' => new external_value(PARAM_INT, 'Classroom ID', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_classroomevaluation_instance($action, $id, $classroomid, $confirm) {
        global $DB,$CFG;
        try {
            if ($confirm) {
                 require_once($CFG->dirroot . '/local/evaluation/lib.php');
                // $DB->delete_records('local_evaluations', array('id' => $id));
                evaluation_delete_instance($id);
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_classroom');
            $return = false;
        }
        return $return;
    }

    public static function delete_classroomevaluation_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
    public static function classroom_form_option_selector_parameters() {
        $query = new external_value(
            PARAM_RAW,
            'Query string'
        );
        $action = new external_value(
            PARAM_RAW,
            'Action for the classroom form selector'
        );
        $options = new external_value(
            PARAM_RAW,
            'Action for the classroom form selector'
        );
        // $limitfrom = new external_value(
        //  PARAM_INT,
        //  'limitfrom we are fetching the records from',
        //  VALUE_DEFAULT,
        //  0
        // );
        // $limitnum = new external_value(
        //  PARAM_INT,
        //  'Number of records to fetch',
        //  VALUE_DEFAULT,
        //  25
        // );
        return new external_function_parameters(array(
            'query' => $query,
            'context' => self::get_context_parameters(),
            'action' => $action,
            'options' => $options,
            // 'limitfrom' => $limitfrom,
            // 'limitnum' => $limitnum,
        ));
    }

    public static function classroom_form_option_selector($query, $context, $action, $options/*, $limitfrom = 0, $limitnum = 25*/) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::classroom_form_option_selector_parameters(), array(
            'query' => $query,
            'context' => $context,
            'action' => $action,
            'options' => $options
            // 'limitfrom' => $limitfrom,
            // 'limitnum' => $limitnum,
        ));
        $query = $params['query'];
        $action = $params['action'];
        $context = self::get_context_from_params($params['context']);
        $options = $params['options'];
        if (!empty($options)) {
            $formoptions = json_decode($options);
        }

        // $limitfrom = $params['limitfrom'];
        // $limitnum = $params['limitnum'];
        //

        self::validate_context($context);
        if ($query && $action) {
            $querieslib = new \local_classroom\local\querylib();
            $return = array();

            switch($action) {
                case 'classroom_trainer_selector':
                    $parentid = $formoptions->parnetid;
                    $return = $querieslib->get_user_department_trainerslist(true,array($parentid), array(), $query);
                break;
                case 'classroom_institute_selector':
                    $service = array();
                    $service['classroomid'] = $formoptions->id;
                    $service['query'] = $query;
                    $return = $querieslib->get_classroom_institutes($formoptions->institute_type, $service);
                break;
                case 'classroom_costcenter_selector':
                // OL-1042 Add Target Audience to Classrooms//
                    if($formoptions->id>0&&!isset($formoptions->parnetid)){
                        $parentid=$DB->get_field('local_classroom','costcenter', array('id'=>$formoptions->id));
                    }else{
                         $parentid = $formoptions->parnetid;
                    }
                // OL-1042 Add Target Audience to Classrooms//
                    $depth = $formoptions->depth;
                    $params = array();
                    $costcntersql = "SELECT id, fullname
                                        FROM {local_costcenter}
                                        WHERE visible = 1 ";
                    if ($parentid >= 0) {
                        $costcntersql .= " AND parentid = :parentid ";
                        $params['parentid'] = $parentid;
                    }
                    if ($depth > 0) {
                        $costcntersql .= " AND depth = :depth ";
                        $params['depth'] = $depth;
                    }
                    if (!empty($query)) {
                        $costcntersql .= " AND fullname LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }
                    if($depth == 1){
                        $concat_array = array();
                    }else{
                        $concat_array = array(-1 => array('id' => -1,'fullname' => 'All'));
                    }
                    $return = $concat_array + $DB->get_records_sql($costcntersql, $params);
                    //$return=(object)((array)$return+array('0'=>(object)array('id'=>-1,'fullname'=>get_string('all')) ));
                    //print_object($return);
                break;
                case 'classroom_subdepartment_selector':
                    if($formoptions->departments_selected){
                        $departments_selected = is_array($formoptions->departments_selected) ? implode(',', $formoptions->departments_selected): $formoptions->departments_selected;
                        $subdept_sql = "SELECT id, fullname
                                        FROM {local_costcenter}
                                        WHERE visible = 1 AND '%,{$departments_selected},%' LIKE CONCAT('%,',parentid,',%') ";

                        $depth = $formoptions->depth;
                        if ($depth > 0) {
                            $subdept_sql .= " AND depth = :depth ";
                            $params['depth'] = $depth;
                        }
                        if (!empty($query)) {
                            $subdept_sql .= " AND fullname LIKE :query ";
                            $params['query'] = '%' . $query . '%';
                        }
                        $return = array(-1 => array('id' => -1,'fullname' => 'All'))+$DB->get_records_sql($subdept_sql, $params);
                    }
                break;
                case 'classroomsession_trainer_selector':
                    $classroomtrainerssql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname FROM {user} AS u JOIN {local_classroom_trainers} AS ct ON ct.trainerid = u.id
                        WHERE ct.classroomid = :classroomid AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2";
                    $params = array();
                    $params['classroomid'] = $formoptions->classroomid;
                    if (!empty($query)) {
                        $classroomtrainerssql .= " AND CONCAT(u.firstname, ' ', u.lastname) LIKE :query ";
                        $params['query'] = '%' . $query . '%';
                    }
                    $return = $DB->get_records_sql($classroomtrainerssql, $params);
                break;
                case 'classroom_completions_sessions_selector':
                    $sessions_sql = "SELECT id, name as fullname
                                        FROM {local_classroom_sessions}
                                        WHERE classroomid = $formoptions->classroomid";
                    $return = $DB->get_records_sql($sessions_sql);


                break;
                case 'classroom_completions_courses_selector':
                    $courses_sql = "SELECT c.id,c.fullname FROM {course} as c JOIN {local_classroom_courses} as lcc on lcc.courseid=c.id where lcc.classroomid=$formoptions->classroomid";
                    $return = $DB->get_records_sql($courses_sql);

                break;
            }
            return json_encode($return);
        }
    }
    public static function classroom_form_option_selector_returns() {
        return new external_value(PARAM_RAW, 'data');
    }
    public static function classroom_session_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function classroom_session_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $classroom = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new \local_classroom\form\session_form(null, array('id' => $data['id'],
            'cid' => $data['classroomid'], 'form_status' => $form_status), 'post', '', null,
             true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $sessionid = (new classroom)->manage_classroom_sessions($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingclassroom', 'local_classroom');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function classroom_session_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function classroom_completion_settings_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function classroom_completion_settings_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $classroom = new stdClass();
        //print_object($data);
        // The last param is the ajax submitted data.
        $mform = new \local_classroom\form\classroom_completion_form(null, array('id' => $data['id'],
            'cid' => $data['classroomid'], 'form_status' => $form_status), 'post', '', null,
             true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $classroom_completionid = (new classroom)->manage_classroom_completions($validateddata);
            if ($classroom_completionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingclassroom', 'local_classroom');
        }
        $return = array(
            'id' => $classroom_completionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function classroom_completion_settings_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function classroom_course_instance_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'ID', 0),
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'Submitted Form Data', false),
            )
        );
    }

    public static function classroom_course_instance($id, $contextid, $form_status, $jsonformdata) {
        global $PAGE, $DB, $CFG, $USER;
        $context = context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($jsonformdata);
        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $classroom = new stdClass();

        // The last param is the ajax submitted data.
        $mform = new classroomcourse_form(null, array('cid' => $data['classroomid'],
            'form_status' => $form_status), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // Do the action.
            $sessionid = (new classroom)->manage_classroom_courses($validateddata);
            if ($sessionid > 0) {
                $form_status = -2;
                $error = false;
            } else {
                $error = true;
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('missingclassroom', 'local_classroom');
        }
        $return = array(
            'id' => $sessionid,
            'form_status' => $form_status);
        return $return;
    }

    public static function classroom_course_instance_returns() {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, 'Context id for the framework'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }
    public static function delete_classroomcourse_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'classroomid' => new external_value(PARAM_INT, 'Classroom ID', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
            )
        );
    }

    public static function delete_classroomcourse_instance($action, $id, $classroomid, $confirm) {
        global $DB;
        try {
            if ($confirm) {

                $course = $DB->get_field('local_classroom_courses', 'courseid', array('classroomid' => $classroomid, 'id' => $id));

                $classroom_completiondata =$DB->get_record_sql("SELECT id,courseids
                                        FROM {local_classroom_completion}
                                        WHERE classroomid = $classroomid");

                if($classroom_completiondata->courseids!=null){

                    $classroom_courseids=explode(',',$classroom_completiondata->courseids);

                    $array_diff=array_diff($classroom_courseids, array($course));

                    if(!empty($array_diff)){
                        $classroom_completiondata->courseids = implode(',',$array_diff);
                    }else{
                        $classroom_completiondata->courseids="NULL";
                    }

                    //$DB->execute('UPDATE {local_classroom_completion} SET courseids = ' .
                    //    $classroom_courseids . ' WHERE id = ' .
                    //$classroom_completiondata->id. '');
                    $DB->update_record('local_classroom_completion', $classroom_completiondata);
                    $params = array(
                        'context' => context_system::instance(),
                        'objectid' => $classroom_completiondata->id
                    );

                    $event = \local_classroom\event\classroom_completions_settings_updated::create($params);
                    $event->add_record_snapshot('local_classroom', $classroomid);
                    $event->trigger();

                }


                $classroomtrainers = $DB->get_records_menu('local_classroom_trainers',
                    array('classroomid' => $classroomid), 'trainerid', 'id, trainerid');
                if (!empty($classroomtrainers)) {
                    foreach ($classroomtrainers as $classroomtrainer) {
                        $unenrolclassroomtrainer = (new classroom)->manage_classroom_course_enrolments($course, $classroomtrainer,
                            'editingteacher', 'unenrol');
                    }
                }
                $classroomusers = $DB->get_records_menu('local_classroom_users',
                    array('classroomid' => $classroomid), 'userid', 'id, userid');
                if (!empty($classroomusers)) {
                    foreach ($classroomusers as $classroomuser) {
                        $unenrolclassroomuser = (new classroom)->manage_classroom_course_enrolments($course, $classroomuser,
                            'employee', 'unenrol');
                    }
                }
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' =>$id
                );

                $event = \local_classroom\event\classroom_courses_deleted::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                $DB->delete_records('local_classroom_courses', array('id' => $id));
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_classroom');
            $return = false;
        }
        return $return;
    }

    public static function delete_classroomcourse_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }


/*sree*/
public static function submit_instituteform_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the evaluation'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create group form, encoded as a json array'),

            )
        );
    }

    /**
     * form submission of institute name and returns instance of this object
     *
     * @param int $contextid
     * @param [string] $jsonformdata
     * @return institute form submits
     */
    public function submit_catform_form($contextid, $jsonformdata){
        global $PAGE, $CFG;

        require_once($CFG->dirroot . '/local/classroom/lib.php');
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_instituteform_form_parameters(),
                                    ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);
        // $context = $params['contextid'];
        $context = context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        // throw new moodle_exception('Error in creation');
        // die;
        $data = array();

        parse_str($serialiseddata, $data);
        $warnings = array();
         $mform = new local_classroom\form\catform(null, array(), 'post', '', null, true, $data);
        $category  = new local_classroom\event\category();
        $valdata = $mform->get_data();

        if($valdata){
            if($valdata->id>0){

                $institutes->category_update_instance($valdata);
            } else{

                $institutes->category_insert_instance($valdata);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in creation');
        }
    }


    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_catform_form_returns() {
        return new external_value(PARAM_INT, 'category id');
    }

        public static function unenroll_classroom_instance_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'id' => new external_value(PARAM_INT, 'ID of the record', 0),
                'classroomid' => new external_value(PARAM_INT, 'Classroom ID', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'classroomname' => new external_value(PARAM_RAW, 'Action of the event', false),
                'contextid' => new external_value(PARAM_INT, 'The context id for the unenrol classroom'),
                'reason' => new external_value(PARAM_TEXT, 'The reason for the unenrol classroom'),
            )
        );
    }

    public static function unenroll_classroom_instance($action, $id, $classroomid, $confirm,$classroomname,$contextid,$reason) {
       
        global $DB,$USER,$CFG;
        require_once($CFG->dirroot . '/local/lib.php');
        try {
            if ($confirm) {
                $classroom_notification = new \local_classroom\notification();
                $classroomclass =new \local_classroom\classroom();



                $classroomenrol = enrol_get_plugin('classroom');
                $courses        = $DB->get_records_menu('local_classroom_courses', array(
                'classroomid' => $classroomid
                ), 'id', 'id, courseid');
                $type           = 'classroom_unenroll';
                $dataobj        = $classroomid;
                $fromuserid     = $USER->id;
                $localclassroom = $DB->get_record_sql("SELECT id,name,status FROM {local_classroom} where id= $classroomid");
                $classroominstance = $DB->get_record('local_classroom', array('id' => $classroomid));
                if ($localclassroom->status != 0) {
                        if (!empty($courses)) {
                            foreach ($courses as $course) {
                                if ($course > 0) {
                                    $unenrolclassroomuser = $classroomclass->manage_classroom_course_enrolments($course, $USER->id, 'employee', 'unenrol');
                                }
                            }
                        }
                }
                classroom_evaluations_add_remove_users($classroomid, 0, 'users_to_feedback', $USER->id, 'update');
                $params = array(
                    'context' => context_system::instance(),
                    'objectid' => $classroomid
                );
                $event  = \local_classroom\event\classroom_users_deleted::create($params);
                $event->add_record_snapshot('local_classroom', $classroomid);
                $event->trigger();
                $DB->delete_records('local_classroom_users', array(
                    'classroomid' => $classroomid,
                    'userid' => $USER->id
                ));
                if ($localclassroom->status != 0) {
                    // $emaillogs = $class_emaillogs->classroom_emaillogs($type, $dataobj, $removeuser, $fromuserid);
                    $touser = \core_user::get_user($USER->id);
                    $emaillogs = $classroom_notification->classroom_notification($type, $touser, $USER, $classroominstance);
                }
                $DB->delete_records('local_classroom_trainerfb', array(
                    'classroomid' => $classroomid,
                    'userid' => $USER->id
                ));
                $classroomclass->remove_classroom_signups($classroomid, $USER->id);

                //$coursetype = $DB->get_records_menu('local_course_types', array('shortname' => 'ilt'), '', 'id,name', $limitfrom=0, $limitnum=0);
                $data = new stdClass();          
                $data->classroomid = $classroomid;
                $data->coursetypeid = 2;
                $data->coursetype = 'ILT';
                $data->userid = $USER->id;            
                $data->unenrol_reason =  $reason;
                $data->timecreated = time();

                $DB->insert_record('local_classroom_unenrol_log',$data);

                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('unenrollerror', 'local_classroom');
            $return = false;
        }     
        
        return $return;
    }

    public static function unenroll_classroom_instance_returns() {
        return new external_value(PARAM_BOOL, 'return');
    }
     /**
     * [classroomviewusers description]
     * @method classroomviewusers
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewwaitinglistusers_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'The context id', false),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
                'offset' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),

                'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 0),
                'filterdata' => new external_value(PARAM_RAW, 'The data for the service')
        ]);
    }


    /**
     * [classroomviewrequestedusers description]
     * @method classroomviewrequestedusers
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomviewwaitinglistusers($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomviewwaitinglistusers_parameters(),
            [
                'contextid' => $contextid,
                'options' => $options,
                'dataoptions' => $dataoptions,
                'offset' => $offset,
                'limit' => $limit,
                'filterdata' => $filterdata
            ]
        );
        $offset = $params['offset'];
        $limit = $params['limit'];

        $decodeddataoptions = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $renderer = $PAGE->get_renderer('local_classroom');
        $users = (new classroom)->classroomwaitinglistusers($decodeddataoptions->classroomid,$stable);
        $totalcount = $users['classroomuserscount'];
        $functinname = 'viewclassroom'.$decodeddataoptions->tabname;
        if(method_exists($renderer, $functinname)){
            $usersdata = $renderer->$functinname($users['classroomusers'],$decodeddataoptions->classroomid,$stable);
        }
        $return = [
            'totalcount' => $totalcount,
            'records' => $usersdata['data'],
            'options' => $options,
            'classroomid' => $decodeddataoptions->classroomid,
            'dataoptions' => $dataoptions,
        ];
        return $return;

    }

    public static function classroomviewwaitinglistusers_returns() {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'employeeid' => new external_value(PARAM_RAW, 'employeeid'),
                                    'email' => new external_value(PARAM_RAW, 'email'),
                                    'supervisor' => new external_value(PARAM_RAW, 'supervisor'),
                                    'sortorder' => new external_value(PARAM_RAW, 'sortorder'),
                                    'enroltype' => new external_value(PARAM_RAW, 'enroltype'),
                                    'waitingtime' => new external_value(PARAM_RAW, 'waitingtime'),
                                )
                            )
            )
        ]);
    }
    public static function get_user_classrooms_parameters() {
        return new external_function_parameters(
            array('userid' => new external_value(PARAM_INT, 'UserID', VALUE_OPTIONAL),
                    'status' => new external_value(PARAM_INT, 'Status'),
                    'searchterm' => new external_value(PARAM_RAW, 'Search'),
                    'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                    'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 10)
            )
        );
    }
     public static function get_user_classrooms( $userid, $status, $searchterm = "", $page=0, $perpage=10) {
        global $DB,$USER,$CFG;
        require_once($CFG->dirroot.'/local/ratings/lib.php');
        $programsinfo = array();
        $session_list = array();
        $sqlquery = "SELECT *";
        $sql = " FROM {local_classroom_users} as lbu
                JOIN {local_classroom} as lb ON lbu.classroomid = lb.id";
        $sqlcount = "SELECT COUNT(lb.id) ";
        if($status == 10){
            $sql .= " AND lb.status IN(1, 4) WHERE lbu.userid=".$USER->id;
        }
        if($status == 1){
            $sql .= " AND lb.status = 1 WHERE lbu.userid=".$USER->id;
        }
        if($status == 2){
            $sql .= " AND lb.status = 3 WHERE lbu.userid=".$USER->id;
        }
        if($status == 8){
            $sql .= " AND lb.status = 4 WHERE lbu.userid=".$USER->id;
        }
        if($searchterm !=""){
            $sql.=" AND lb.name LIKE '%".$searchterm."%'";
        }
        $allclassrooms = $DB->get_records_sql($sqlquery . $sql, array(),  $page * $perpage, $perpage);
        $total = $DB->count_records_sql($sqlcount . $sql);
        $data = array();
        $classcourse= array();
        $trainerlist = array();
        foreach ($allclassrooms as $classroom) {
            $classcourse = array();
            $classroominfo['id'] = $classroom->id;
            $classroominfo['status'] = $classroom->status;
            $classroominfo['name'] = $classroom->name;
            $classroominfo['startdate'] = date("j M 'y",$classroom->startdate);
            $classroominfo['enddate'] = date("j M 'y",$classroom->enddate);
            $classroominfo['summary'] = $classroom->description;
            $location = $DB->get_record_sql("SELECT * FROM {local_location_institutes} WHERE id =". $classroom->instituteid);
            if ($location->fullname) {
                $classroominfo['location'] = $location->fullname;
            }
            else {
                $classroominfo['location'] = 'N/A';
            }
            $classroomcourse = $DB->get_records_sql("SELECT c.id,c.fullname FROM {course} as c
                JOIN {local_classroom_courses} as lbc ON lbc.courseid = c.id WHERE lbc.classroomid=".$classroom->id);
            foreach($classroomcourse as $key => $course){
                $classcourse[$key]['id'] = $course->id;
                $classcourse[$key]['fullname'] = $course->fullname;
            }
            $classroominfo['courseslist'] = $classcourse;
            $trainerlist = array();
            $primarytrainer = $DB->get_records_sql("SELECT concat(u.firstname, u.lastname) as username FROM {local_classroom_trainers} as lbt
                JOIN {user} as u ON lbt.trainerid = u.id WHERE lbt.classroomid=".$classroom->id);
            foreach ($primarytrainer as $trainer) {
                $trainers = array();
                $trainers = $trainer;
                $trainerlist[] = $trainers;
            }

            $classroominfo['primarytrainer'] = $trainerlist;
            $classroominfo['count'] = COUNT($allclassrooms);
            $modulerating = $DB->get_field('local_ratings_likes', 'module_rating', array('module_id' => $classroom->id, 'module_area' => 'local_classroom'));
            if(!$modulerating){
                 $modulerating = 0;
            }
            $classroominfo['rating'] = $modulerating;
            $likes = $DB->count_records('local_like', array('likearea'=> 'local_classroom', 'itemid'=>$classroom->id, 'likestatus'=>'1'));
            $dislikes = $DB->count_records('local_like', array('likearea'=> 'local_classroom', 'itemid'=>$classroom->id, 'likestatus'=>'2'));
            $avgratings = get_rating($classroom->id, 'local_classroom');
            $avgrating = $avgratings->avg;
            $ratingusers = $avgratings->count;
            $classroominfo['likes'] = $likes;
            $classroominfo['dislikes'] = $dislikes;
            $classroominfo['avgrating'] = $avgrating;
            $classroominfo['ratingusers'] = $ratingusers;
            $data[] = $classroominfo;
        }


        return array('modules' => $data, 'total' => $total, 'page' => $page);

    }
    public static function get_user_classrooms_returns() {
        return new external_single_structure(
            array(
                'modules' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Classroom ID'),
                            'status' => new external_value(PARAM_INT, 'Classroom status'),
                            'name' => new external_value(PARAM_RAW, 'Classroom Name'),
                            'startdate' => new external_value(PARAM_RAW, 'Classroom Start Date'),
                            'enddate' => new external_value(PARAM_RAW, 'Classroom End Date'),
                            'summary' => new external_value(PARAM_RAW, 'Classroom Summary'),
                            'location' => new external_value(PARAM_RAW, 'Location'),
                            'courseslist' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'id' => new external_value(PARAM_INT, 'Course Id'),
                                        'fullname' => new external_value(PARAM_RAW, 'Course name'),
                                    )
                                )
                            ),
                            'primarytrainer' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'username' => new external_value(PARAM_RAW, 'Course name'),

                                    )
                                )
                            ),
                            'count' => new external_value(PARAM_RAW, 'Count of batches'),
                            'rating' => new external_value(PARAM_INT, 'Classroom Rating'),
                            'likes' => new external_value(PARAM_INT, 'Classroom Likes'),
                            'dislikes' => new external_value(PARAM_INT, 'Classroom Dislikes'),
                            'avgrating' => new external_value(PARAM_FLOAT, 'Classroom avgrating'),
                            'ratingusers' => new external_value(PARAM_FLOAT, 'Classroom users rating')
                        )
                    )
                ),
                'total' => new external_value(PARAM_INT, 'Total Records'),
            )
        );
    }
    public static function get_classroom_sessions_parameters() {
        return new external_function_parameters(
             array( 'userid' => new external_value(PARAM_INT, 'UserID'),
                    'classroomid' => new external_value(PARAM_INT, 'Classroomid'),
                    'searchterm' => new external_value(PARAM_RAW, 'Search'),
                    'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                    'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 20)
                )
        );
    }
     public static function get_classroom_sessions($userid,$classroomid,$searchterm, $page = 0, $perpage = 20) {
        global $DB,$USER,$PAGE;
            $classroom = $DB->get_record_sql("SELECT lbs.name FROM {local_classroom} as lbs WHERE lbs.id=".$classroomid);
            $sqlquery = "SELECT * ";
            $sqlcount = "SELECT COUNT(lbs.id) ";
            $sql = "FROM {local_classroom_sessions} as lbs WHERE lbs.classroomid=".$classroomid;
            if($searchterm !=""){
                $sql.=" AND lbs.name LIKE '%".$searchterm."%'";
            }
            $sessions = $DB->get_records_sql($sqlquery. $sql, array(), $page * $perpage, $perpage);
            $total = $DB->count_records_sql($sqlcount . $sql);
            $sessiondata =  array();
            foreach($sessions as $key => $session){
                $sessiondata[$key]['name'] = $session->name;
                $sessiondata[$key]['date'] = date('d-m-Y',$session->timestart);
                $sessiondata[$key]['time'] = date('H:i',$session->timestart).' - '.date('H:i',$session->timefinish);
                if($session->onlinesession == 0) {
                    $sessiondata[$key]['type'] = 'Classroom';
                }
                else{
                    $sessiondata[$key]['type'] = 'Webex';
                }
                $sessionroom = $DB->get_record_sql("SELECT name as roominfo FROM {local_location_room} WHERE id=".$session->roomid);
                if($sessionroom){
                    $sessiondata[$key]['room'] = $sessionroom->roominfo;
                }
                else{
                     $sessiondata[$key]['room'] = 'NA';
                }
                $sesstrainer =  $DB->get_record_sql("SELECT * FROM {user} WHERE id=".$session->trainerid);
                if($sesstrainer){
                    $sessiondata[$key]['trainer'] = fullname($sesstrainer);
                    $sessiondata[$key]['trainerprofile'] = (new user_picture($sesstrainer))->get_url($PAGE)->out(false);
                } else{
                    $sessiondata[$key]['trainer'] = 'NA';
                    $sessiondata[$key]['trainerprofile'] = '';
                }
            }
         return array('mysessions' => $sessiondata, 'classroomname' => $classroom->name, 'total' => $total);
    }
     public static function get_classroom_sessions_returns() {
        return new external_single_structure(
            array(
                'mysessions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                                'name' => new external_value(PARAM_RAW, 'Session name'),
                                'date' => new external_value(PARAM_RAW, 'Session date'),
                                'time' => new external_value(PARAM_RAW, 'Session start and end date.'),
                                'type' => new external_value(PARAM_RAW, 'Session type'),
                                'room' => new external_value(PARAM_RAW, 'Session location'),
                                'trainer' => new external_value(PARAM_RAW, 'Session Trainer'),
                                'trainerprofile' => new external_value(PARAM_RAW, 'Session Trainer profile')
                        )
                    )
                ),
                'classroomname' => new external_value(PARAM_RAW, 'classroomname'),
                'total' => new external_value(PARAM_INT, 'Total'),
            )
        );
    }
    public static function get_weekly_sessions_parameters() {
        return new external_function_parameters(
             array()
        );
    }
    public static function get_weekly_sessions(){
        global $DB,$USER,$PAGE;
        $data = array();
        $res = array();
        // $currentdate = date('Y-m-d H:i:s');
        $currentdate_timestamp = strtotime('tomorrow');
        $afteroneweek = strtotime("+7 day", $currentdate_timestamp);
        $daystart = strtotime(date("Y-m-d 00:00:01"));
        $dayend = strtotime(date("Y-m-d 23:59:59"));
        $allclassrooms = $DB->get_records_sql("SELECT lcs.id as sessionid,lb.id as classid,lb.name as classname,lcs.name,lcs.timestart,lcs.timefinish,CONCAT(lr.name, lr.building, lr.address) as sessionroom,lcs.trainerid
                FROM {local_classroom_users} as lbu
                JOIN {local_classroom} as lb ON lbu.classroomid = lb.id
                JOIN {local_classroom_sessions} as lcs ON lcs.classroomid = lbu.classroomid
                LEFT JOIN {local_location_room} as lr ON  lr.id = lcs.roomid
                WHERE lb.status=1 AND lbu.userid = $USER->id AND lcs.timestart BETWEEN $currentdate_timestamp AND $afteroneweek");
        foreach ($allclassrooms as $value) {
            $result = array();
            $result['classid'] = $value->classid;
            $result['classname'] = $value->classname;
            $result['sessionid'] = $value->sessionid;
            $result['name'] = $value->name;
            $result['sessiondate'] = date('d  M Y', $value->timestart);
            $result['sessiontime'] = date('H:i',$value->timestart).' - '.date('H:i',$value->timefinish);
            if ($value->sessionroom) {
                $result['sessionroom'] = $value->sessionroom;
            } else {
                $result['sessionroom'] = 'NA';
            }
            if($value->onlinesession == 0) {
                $result['sessiontype'] = 'Classroom';
            }
            else{
                $result['sessiontype'] = 'Webex';
            }
            $sesstrainers =  $DB->get_record_sql("SELECT * FROM {user} WHERE id=".$value->trainerid);
            if($sesstrainers){
                $result['sessiontrainer'] = fullname($sesstrainers);
                $result['sessiontrainerprofile'] = (new user_picture($sesstrainers))->get_url($PAGE)->out(false);
            } else{
                $result['sessiontrainer'] = 'NA';
                $result['sessiontrainerprofile'] = '';
            }
            $data[] = $result;
        }

        return array('weeklysessions' => $data);
    }
    public static function get_weekly_sessions_returns(){
        return new external_single_structure(
            array(
                'weeklysessions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'classid' => new external_value(PARAM_INT, 'Classroom ID'),
                            'classname' => new external_value(PARAM_RAW, 'Classroom Name'),
                            'sessionid' => new external_value(PARAM_RAW, 'Session Id'),
                            'name' => new external_value(PARAM_RAW, 'Session Name'),
                            'sessiondate' => new external_value(PARAM_RAW, 'Session Date'),
                            'sessiontime' => new external_value(PARAM_RAW, 'Session Time'),
                            'sessionroom' => new external_value(PARAM_RAW, 'Session location'),
                            'sessiontype' => new external_value(PARAM_RAW, 'Session type'),
                            'sessiontrainer' => new external_value(PARAM_RAW, 'Session Trainer'),
                            'sessiontrainerprofile' => new external_value(PARAM_RAW, 'Session Trainer profile')
                        )
                    )
                )
            )
        );
    }
    public static function get_today_sessions_parameters() {
        return new external_function_parameters(
             array()
        );
    }
    public static function get_today_sessions(){
        global $DB,$USER,$PAGE;
        $data = array();
        $res = array();
        // $currentdate = date('Y-m-d H:i:s');
        $currentdate_timestamp = strtotime('tomorrow');
        $afteroneweek = strtotime("+7 day", $currentdate_timestamp);
        $daystart = strtotime(date("Y-m-d 00:00:01"));
        $dayend = strtotime(date("Y-m-d 23:59:59"));

        $todaysessions = $DB->get_records_sql("SELECT lcs.id as sessionid,lb.id as classid,lb.name as classname,lcs.name,lcs.timestart,lcs.timefinish,CONCAT(lr.name, lr.building, lr.address) as sessionroom,lcs.trainerid
                FROM {local_classroom_users} as lbu
                JOIN {local_classroom} as lb ON lbu.classroomid = lb.id
                JOIN {local_classroom_sessions} as lcs ON lcs.classroomid = lbu.classroomid
                LEFT JOIN {local_location_room} as lr ON  lr.id = lcs.roomid
                WHERE lb.status=1 AND lbu.userid = $USER->id AND lcs.timestart BETWEEN $daystart AND $dayend");
        foreach ($todaysessions as $todaysession) {
            $todays = array();
            $todays['classid'] = $todaysession->classid;
            $todays['classname'] = $todaysession->classname;
            $todays['sessionid'] = $todaysession->sessionid;
            $todays['name'] = $todaysession->name;
            $todays['sessiondate'] = date('d  M Y', $todaysession->timestart);
            $todays['sessiontime'] = date('H:i',$todaysession->timestart).' - '.date('H:i',$todaysession->timefinish);
            if ($todaysession->sessionroom) {
                $todays['sessionroom'] = $todaysession->sessionroom;
            } else {
                $todays['sessionroom'] = 'NA';
            }
            if($todaysession->onlinesession == 0) {
                $todays['sessiontype'] = 'Classroom';
            }
            else{
                $todays['sessiontype'] = 'Webex';
            }
            $sesstrainer =  $DB->get_record_sql("SELECT * FROM {user} WHERE id=".$todaysession->trainerid);
            if($sesstrainer){
                $todays['sessiontrainer'] = fullname($sesstrainer);
                $todays['sessiontrainerprofile'] = (new user_picture($sesstrainer))->get_url($PAGE)->out(false);
            } else{
                $todays['sessiontrainer'] = 'NA';
                $todays['sessiontrainerprofile'] = '';
            }

            $res[] = $todays;
        }
        return array('todaysessions' => $res);
    }
    public static function get_today_sessions_returns(){
        return new external_single_structure(
            array(
                'todaysessions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'classid' => new external_value(PARAM_INT, 'Classroom ID'),
                            'classname' => new external_value(PARAM_RAW, 'Classroom Name'),
                            'sessionid' => new external_value(PARAM_RAW, 'Session Id'),
                            'name' => new external_value(PARAM_RAW, 'Session Name'),
                            'sessiondate' => new external_value(PARAM_RAW, 'Session Date'),
                            'sessiontime' => new external_value(PARAM_RAW, 'Session Time'),
                            'sessionroom' => new external_value(PARAM_RAW, 'Session Location'),
                            'sessiontype' => new external_value(PARAM_RAW, 'Session type'),
                            'sessiontrainer' => new external_value(PARAM_RAW, 'Session Trainer'),
                            'sessiontrainerprofile' => new external_value(PARAM_RAW, 'Session Trainer profile')
                        )
                    )
                ),
            )
        );
    }
    public static function get_classroom_sessions_page_parameters() {
        return new external_function_parameters(
             array('page' => new external_value(PARAM_INT, 'page'),
                )
        );
    }
     public static function get_classroom_sessions_page($page) {
        global $DB,$USER,$PAGE;
        $data = array();
        $userid = 9;
        $status = 10;
        $classroomid = 46;
        $searchterm = '';
            $classroom = $DB->get_record_sql("SELECT lbs.name FROM {local_classroom} as lbs WHERE lbs.id=".$classroomid);
            $sql = "SELECT * FROM {local_classroom_sessions} as lbs WHERE lbs.classroomid=".$classroomid;
            if($searchterm !=""){
                $sql.=" AND lbs.name LIKE '%".$searchterm."%'";
            }
            $sessions = $DB->get_records_sql($sql, array(), $page *10 , 10);
            $sessiondata =  array();
            $sessioninfo = array();
            foreach($sessions as $key => $session){
                $sessiondata[$key]['sessionname'] = $session->name;
                $sessiondata[$key]['sessiondate'] = date('d-m-Y',$session->timestart);
                $sessiondata[$key]['sessiontime'] = date('H:i',$session->timestart).' - '.date('H:i',$session->timefinish);
                if($session->onlinesession == 0) {
                    $sessiondata[$key]['sessiontype'] = 'Classroom';
                }
                else{
                    $sessiondata[$key]['sessiontype'] = 'Webex';
                }
                $sessionroom = $DB->get_record_sql("SELECT name as roominfo FROM {local_location_room} WHERE id=".$session->roomid);
                if($sessionroom){
                    $sessiondata[$key]['sessionroom'] = $sessionroom->roominfo;
                }
                else{
                     $sessiondata[$key]['sessionroom'] = 'NA';
                }
                $sesstrainer =  $DB->get_record_sql("SELECT * FROM {user} WHERE id=".$session->trainerid);
                if($sesstrainer){
                    $sessiondata[$key]['sessiontrainer'] = fullname($sesstrainer);
                    $sessiondata[$key]['sessiontrainerprofile'] = (new user_picture($sesstrainer))->get_url($PAGE)->out(false);
                } else{
                    $sessiondata[$key]['sessiontrainer'] = 'NA';
                    $sessiondata[$key]['sessiontrainerprofile'] = '';
                }
            }

            $classroominfo['sessionslist'] = $sessiondata;
            $classroominfo['classroomname'] = $classroom->name;
            $data[] = $classroominfo;
         return array('results' => $data);
    }
     public static function get_classroom_sessions_page_returns() {
        return new external_single_structure(
            array(
                'results' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'classroomname' => new external_value(PARAM_RAW, 'classroomname'),
                            'sessionslist' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                    'sessionname' => new external_value(PARAM_RAW, 'Session name'),
                                    'sessiondate' => new external_value(PARAM_RAW, 'Session date'),
                                    'sessiontime' => new external_value(PARAM_RAW, 'Session start and end date.'),
                                    'sessiontype' => new external_value(PARAM_RAW, 'Session type'),
                                    'sessionroom' => new external_value(PARAM_RAW, 'Session location'),
                                    'sessiontrainer' => new external_value(PARAM_RAW, 'Session Trainer'),
                                    'sessiontrainerprofile' => new external_value(PARAM_RAW, 'Session Trainer profile')
                                    )
                                )
                            ),
                        )
                    )
                ),
            )
        );
    }
    public static function get_classroom_courses_parameters() {
        return new external_function_parameters(
             array( 'userid' => new external_value(PARAM_INT, 'UserID'),
                    'classroomid' => new external_value(PARAM_INT, 'Classroomid'),
                    'searchterm' => new external_value(PARAM_RAW, 'Search'),
                    'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                    'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 10)
                )
        );
    }
     public static function get_classroom_courses($userid, $classroomid, $searchterm, $page = 0, $perpage = 10) {
        global $DB,$USER,$PAGE,$CFG;
        require_once($CFG->dirroot.'/local/ratings/lib.php');
            $classroom = $DB->get_record_sql("SELECT lbs.name FROM {local_classroom} as lbs WHERE lbs.id=".$classroomid);
            $sqlquery = "SELECT c.* ";
            $sqlcount = "SELECT COUNT(c.id) ";
            $sql = " FROM {course} as c
                    JOIN {local_classroom_courses} as lbc ON lbc.courseid = c.id WHERE lbc.classroomid=".$classroomid;
            if($searchterm !=""){
                $sql.=" AND c.fullname LIKE '%".$searchterm."%'";
            }
            $courses = $DB->get_records_sql($sqlquery . $sql, array(), $page * $perpage, $perpage);
            $total = $DB->count_records_sql($sqlcount . $sql);
            $sessiondata =  array();
            $classcourse= array();

            foreach($courses as $key => $course){
                if ($course->enablecompletion) {
                    $progress = \core_completion\progress::get_course_progress_percentage($course, $userid);
                }
                $classcourse[$key]['id'] = $course->id;
                $classcourse[$key]['fullname'] = $course->fullname;
                $classcourse[$key]['shortname'] = $course->shortname;
                $classcourse[$key]['summary'] = $course->summary;
                $classcourse[$key]['summaryformat'] = $course->summaryformat;
                $classcourse[$key]['startdate'] = $course->startdate;
                $classcourse[$key]['enddate'] = $course->enddate;
                $classcourse[$key]['timecreated'] = $course->timecreated;
                $classcourse[$key]['timemodified'] = $course->timemodified;
                $classcourse[$key]['visible'] = $course->visible;
                $classcourse[$key]['idnumber'] = $course->idnumber;
                $classcourse[$key]['format'] = $course->format;
                $classcourse[$key]['showgrades'] = $course->showgrades;
                $classcourse[$key]['lang'] = clean_param($course->lang,PARAM_LANG);
                $classcourse[$key]['enablecompletion'] = $course->enablecompletion;
                $classcourse[$key]['category'] = $course->category;
                $classcourse[$key]['progress'] = $progress;
                $modulerating = $DB->get_field('local_ratings_likes', 'module_rating', array('module_id' => $course->id, 'module_area' => 'local_courses'));
                if(!$modulerating){
                     $modulerating = 0;
                }
                $likes = $DB->count_records('local_like', array('likearea'=> 'local_courses', 'itemid'=>$course->id, 'likestatus'=>'1'));
                $dislikes = $DB->count_records('local_like', array('likearea'=> 'local_courses', 'itemid'=>$course->id, 'likestatus'=>'2'));
                $classcourse[$key]['rating'] = $modulerating;
                $classcourse[$key]['likes'] = $likes;
                $classcourse[$key]['dislikes'] = $dislikes;
                $avgratings = get_rating($course->id, 'local_courses');
                $avgrating = $avgratings->avg;
                $ratingusers = $avgratings->count;
                $classcourse[$key]['avgrating'] = $avgrating;
                $classcourse[$key]['ratingusers'] = $ratingusers;
                }
         return array('classroomcourses' => $classcourse, 'classroomname' => $classroom->name, 'total' => $total);
    }
     public static function get_classroom_courses_returns() {
        return new external_single_structure(
            array(
                'classroomcourses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'=> new external_value(PARAM_INT, 'id of course'),
                            'fullname'=> new external_value(PARAM_RAW, 'fullname of course'),
                            'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                            'summary' => new external_value(PARAM_RAW, 'course summary'),
                            'summaryformat' => new external_value(PARAM_RAW, 'course summary format'),
                            'startdate' => new external_value(PARAM_RAW, 'startdate of course'),
                            'enddate' => new external_value(PARAM_RAW, 'enddate of course'),
                            'timecreated' => new external_value(PARAM_RAW, 'course create time'),
                            'timemodified' => new external_value(PARAM_RAW, 'course modified time'),
                            'visible' => new external_value(PARAM_RAW, 'course status'),
                            'idnumber' => new external_value(PARAM_RAW, 'course idnumber'),
                            'format' => new external_value(PARAM_RAW, 'course format'),
                            'showgrades' => new external_value(PARAM_RAW, 'course grade status'),
                            'lang' => new external_value(PARAM_RAW, 'course language'),
                            'enablecompletion' => new external_value(PARAM_RAW, 'course completion'),
                            'category' => new external_value(PARAM_RAW, 'course category'),
                            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage'),
                            'rating' => new external_value(PARAM_INT, 'Course rating'),
                            'likes' => new external_value(PARAM_INT, 'Course Likes'),
                            'dislikes' => new external_value(PARAM_INT, 'Course Dislikes'),
                            'avgrating' => new external_value(PARAM_FLOAT, 'Course Avg rating'),
                            'ratingusers' => new external_value(PARAM_INT, 'Course rating users'),
                        )
                    )
                ),
                'classroomname' => new external_value(PARAM_RAW, 'classroomname'),
                'total' => new external_value(PARAM_INT, 'Total'),
            )
        );
    }
    public static function get_classroom_trainers_parameters() {
        return new external_function_parameters(
             array( 'userid' => new external_value(PARAM_INT, 'UserID'),
                    'classroomid' => new external_value(PARAM_INT, 'Classroomid'),
                    'searchterm' => new external_value(PARAM_RAW, 'Search'),
                    'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                    'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 10)
                )
        );
    }
     public static function get_classroom_trainers($userid, $classroomid, $searchterm, $page = 0, $perpage = 10) {
        global $DB,$USER,$PAGE;
         $classroom = $DB->get_record_sql("SELECT lbs.name FROM {local_classroom} as lbs WHERE lbs.id=".$classroomid);
            $sqlquery = "SELECT u.*";
            $sqlcount = "SELECT COUNT(u.id) ";
            $sql = " FROM {local_classroom_trainers} as lbt
                JOIN {user} as u ON lbt.trainerid = u.id WHERE lbt.classroomid=".$classroomid;
            if($searchterm !=""){
                $sql.=" AND u.username LIKE '%".$searchterm."%'";
            }
            $primarytrainer = $DB->get_records_sql($sqlquery . $sql, array(), $page * $perpage, $perpage);
            $total = $DB->count_records_sql($sqlcount . $sql);
            $trainerlist = array();
            foreach ($primarytrainer as $trainer) {
                $trainerlist[] = array(
                    'id' => $trainer->id,
                    'profilename' => fullname($trainer),
                    'email' => $trainer->email,
                    'profile' => (new user_picture($trainer))->get_url($PAGE)->out(false)
                );
            }
         return array('classroomtrainers' => $trainerlist,  'classroomname' => $classroom->name, 'total' => $total);
    }
     public static function get_classroom_trainers_returns() {
        return new external_single_structure(
            array(
                'classroomtrainers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Trainer id'),
                            'profilename' => new external_value(PARAM_RAW, 'Trainer name'),
                            'email' => new external_value(PARAM_RAW, 'Trainer Email ID'),
                            'profile' => new external_value(PARAM_RAW, 'Trainer profile'),

                        )
                    )
                ),
                'classroomname' => new external_value(PARAM_RAW, 'classroomname'),
                'total' => new external_value(PARAM_INT, 'Total'),
            )
        );
    }
       public static function get_classroom_completions_parameters() {
        return new external_function_parameters(
             array( 'userid' => new external_value(PARAM_INT, 'UserID'),
                    'classroomid' => new external_value(PARAM_INT, 'Classroomid')
                )
        );
    }
     public static function get_classroom_completions($userid, $classroomid) {
        global $DB,$USER,$PAGE;
         $classroom = $DB->get_record_sql("SELECT lbs.name FROM {local_classroom} as lbs WHERE lbs.id=".$classroomid);
            $completioncriteria = \local_classroom\classroom::classroom_completion_settings_tab($classroomid);
         return array('completions' => $completioncriteria,  'classroomname' => $classroom->name);
    }
     public static function get_classroom_completions_returns() {
        return new external_single_structure(
            array(
                'completions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'classroomid' => new external_value(PARAM_INT, 'Trainer id'),
                            'sessions' => new external_value(PARAM_RAW, 'Sessions'),
                            'courses' => new external_value(PARAM_RAW, 'courses'),
                            'tracking' => new external_value(PARAM_RAW, 'Tracking')
                        )
                    )
                ),
                'classroomname' => new external_value(PARAM_RAW, 'classroomname'),
            )
        );
    }



    /**
     * [classroomviewfeedbacks description]
     * @method classroomviewfeedbacks
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomfeedbacks_parameters() {
        return new external_function_parameters([
                'userid' => new external_value(PARAM_INT, 'user id', false),
                'classroomid' => new external_value(PARAM_INT, 'classroom id', false),
                'search' => new external_value(PARAM_RAW, 'Search',
                    VALUE_DEFAULT, ''),
                'page' => new external_value(PARAM_INT, 'Number of items',
                    VALUE_DEFAULT, 0),
                'perpage' => new external_value(PARAM_INT, 'Maximum number of results to return',
                    VALUE_DEFAULT, 10)
        ]);
    }


    /**
     * [classroomviewfeedbacks description]
     * @method classroomviewfeedbacks
     * @param  [type]                contextid [which context]
     * @param  [type]                options      [options]
     * @param  [type]                dataoptions [dataoptions]
     * @param  [type]                offset      [offset]
     * @param  [type]                limit [limit]
     */
    public static function classroomfeedbacks($userid, $classroomid, $search = '',
        $page = 0,
        $perpage = 10
    ) {
        global $DB, $PAGE;
        // Parameter validation.
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::classroomfeedbacks_parameters(),
            [
                'userid' => $userid,
                'classroomid' => $classroomid,
                'search' => $search,
                'page' => $page,
                'perpage' => $perpage
            ]
        );

        $stable = new \stdClass();
        $stable->search = false;
        $stable->thead = false;
        $stable->start = $page * $perpage;
        $stable->length = $perpage;
        $renderer = $PAGE->get_renderer('local_classroom');
        $feedbacks = (new classroom)->classroom_evaluations($classroomid, $stable);
        $totalcount = $feedbacks['evaluationscount'];
        $functinname = 'viewclassroomfeedbacks';

        if(method_exists($renderer, $functinname)){
            $feedbacksdata = $renderer->$functinname($feedbacks['evaluations'],$classroomid);
            $createfeedback = $feedbacksdata['createfeedback'];
        }
        $return = [
            'totalcount' => $totalcount,
            'classroomfeedbacks' => $feedbacksdata['data'],
            'classroomid' => $classroomid,
        ];
        return $return;

    }

    public static function classroomfeedbacks_returns() {
        return new external_single_structure([
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'classroomfeedbacks' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'id'),
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'feedbackview' => new external_value(PARAM_BOOL, 'feedbackview'),
                                    'feedbacktype' => new external_value(PARAM_RAW, 'feedbacktype'),
                                    'trainer' => new external_value(PARAM_RAW, 'trainer'),
                                    'action' => new external_value(PARAM_BOOL, 'action'),
                                    'preview' => new external_value(PARAM_RAW, 'name')
                                )
                            )
            )
        ]);
    }
    /**
     * Returns the description of the
     data_for_elearning_courses_parameters.
     *
     * @return external_function_parameters.
     */
    public static function data_for_classrooms_parameters() {
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


    /**
     * Data to render in the related elearning_courses section.
     *
     * @param int $filter
     * @return array elearning courses list.
     */
    public static function data_for_classrooms($filter, $filter_text='', $filter_offset = 0, $filter_limit = 0) {
        global $PAGE;

        $params = self::validate_parameters(self::data_for_classrooms_parameters(), array(
            'filter' => $filter,
            'filter_text' => $filter_text,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit
        ));
        $PAGE->set_context(context_system::instance());
        $renderable = new \local_classroom\output\classroom_courses($params['filter'],$params['filter_text'], $params['filter_offset'], $params['filter_limit']);
        $output = $PAGE->get_renderer('local_classroom');

        $data= $renderable->export_for_template($output);


        return $data;
    }

    /**
     * Returns description of data_for_elearning_courses_returns() result value.
     *
     * @return external_description
     */
   public static function data_for_classrooms_returns() {
        return new external_single_structure(array (
            'total' => new external_value(PARAM_INT, 'Number of enrolled courses.', VALUE_OPTIONAL),           
            'inprogresscount'=>  new external_value(PARAM_INT, 'Number of inprogress course count.'),  
            'completedcount'=>  new external_value(PARAM_INT, 'Number of complete course count.'), 
            'classroom_view_count'=>  new external_value(PARAM_INT, 'Number of classroom count.'), 
            'enableslider'=>  new external_value(PARAM_INT, 'Flag for enable the slider.'),
            'inprogress_elearning_available'=>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
            'course_count_view'=>  new external_value(PARAM_TEXT, 'to add course count class'),
            'functionname' => new external_value(PARAM_TEXT, 'Function name'),
            'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
            'classroomtemplate' => new external_value(PARAM_INT, 'template name',VALUE_OPTIONAL),
            'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, true),
            'moduledetails' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        // 'inprogress_coursename' => new external_value(PARAM_RAW, 'Course name'),
                        'image' => new external_value(PARAM_RAW, 'Classroom Image'),
                        'classroomSummary' => new external_value(PARAM_RAW, 'Classroom Summary'),
                        'classroomFullname' => new external_value(PARAM_RAW, 'Classroom Fullname'),
                        'displayClassroomFullname' => new external_value(PARAM_RAW, 'Display Classroom Fullname'),
                        'classroomid' => new external_value(PARAM_INT, 'Classroom id'),
                        'rating_element' => new external_value(PARAM_RAW, 'Classroom rating element'),
                        'startdate' => new external_value(PARAM_RAW, 'Classroom startdate'),
                        'enddate' => new external_value(PARAM_RAW, 'Classroom enddate'),
                        'classroom_url' => new external_value(PARAM_RAW, 'Classroom url'),
                        'index' => new external_value(PARAM_INT, 'Index of Card'),
                    )
                )
            ),
            'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
            'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
            'index' => new external_value(PARAM_INT, 'number of courses count'),
            'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
            'filter_text' => new external_value(PARAM_TEXT, 'filtertext content',VALUE_OPTIONAL),
            'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
            'viewMoreCard' => new external_value(PARAM_BOOL, 'More info card to display'),
        ));

    }  // end of the function data_for_elearning_courses_returns
    public static function data_for_classrooms_paginated_parameters(){
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
    public static function data_for_classrooms_paginated($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata){
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

        $PAGE->set_context(context_system::instance());
        $renderable = new \local_classroom\output\classroom_courses($filter, $filter_text, $filter_offset, $filter_limit);
        $output = $PAGE->get_renderer('local_classroom');

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
    public static function data_for_classrooms_paginated_returns(){
        return new external_single_structure([
        'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
        'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
        'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
        'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        'records' => new external_multiple_structure(
                new external_single_structure(
                    array (
                    'total' => new external_value(PARAM_INT, 'Number of enrolled courses.', VALUE_OPTIONAL),           
                    'inprogresscount'=>  new external_value(PARAM_INT, 'Number of inprogress course count.'),  
                    'completedcount'=>  new external_value(PARAM_INT, 'Number of complete course count.'), 
                    'classroom_view_count'=>  new external_value(PARAM_INT, 'Number of classroom count.'), 
                    // 'enableslider'=>  new external_value(PARAM_INT, 'Flag for enable the slider.'),
                    'inprogress_elearning_available'=>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
                    'course_count_view'=>  new external_value(PARAM_TEXT, 'to add course count class'),
                    'functionname' => new external_value(PARAM_TEXT, 'Function name'),
                    'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
                    'classroomtemplate' => new external_value(PARAM_INT, 'template name',VALUE_OPTIONAL),
                    'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, false),
                    'moduledetails' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                // 'inprogress_coursename' => new external_value(PARAM_RAW, 'Course name'),
                                'image' => new external_value(PARAM_RAW, 'Classroom Image'),
                                'classroomSummary' => new external_value(PARAM_RAW, 'Classroom Summary'),
                                'classroomFullname' => new external_value(PARAM_RAW, 'Classroom Fullname'),
                                'displayClassroomFullname' => new external_value(PARAM_RAW, 'Display Classroom Fullname'),
                                'classroomid' => new external_value(PARAM_INT, 'Classroom id'),
                                'rating_element' => new external_value(PARAM_RAW, 'Classroom rating element'),
                                'startdate' => new external_value(PARAM_RAW, 'Classroom startdate'),
                                'enddate' => new external_value(PARAM_RAW, 'Classroom enddate'),
                                'classroom_url' => new external_value(PARAM_RAW, 'Classroom url'),
                            )
                        )
                    ),
                    'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
                    'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
                    'index' => new external_value(PARAM_INT, 'number of courses count'),
                    'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
                    'filter_text' => new external_value(PARAM_TEXT, 'filtertext content',VALUE_OPTIONAL),
                    // 'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
                )
            ) 
        )
    ]);
    }
}
