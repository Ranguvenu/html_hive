<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or localify
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
 * Courses external API
 *
 * @package    local_courseassignment
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */

defined('MOODLE_INTERNAL') || die;

use \local_courseassignment\form\grader_action_form as grader_action_form;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/local/courseassignment/lib.php');
require_once("$CFG->dirroot/local/courses/lib.php");

class local_courseassignment_external extends external_api
{

    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_graderaction_parameters()
    {
        return new external_function_parameters(
            [
                'contextid' => new external_value(PARAM_INT, 'context id '),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the grader action form, encoded as a json array'),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
                'filterdata' => new external_value(PARAM_RAW, 'total number of records in result set', VALUE_OPTIONAL),  
        ]
            
        );
    }

    public static function submit_graderaction($contextid, $jsonformdata,$options,$dataoptions,$filterdata)
    {
        global $DB, $CFG, $USER;

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::submit_graderaction_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        $data = array();
        $params = array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $params = array(
            'moduleid' => $data['moduleid'],
            'courseid' => $data['courseid'],
            'userid' => $data['userid'],
            'method' => $data['method'],
            'options' => $data['options'],
            'dataoptions' => $data['dataoptions'],
            'filterdata' =>$data['filterdata'],
        );
        // The last param is the ajax submitted data.
        $mform = new grader_action_form(null, $params, 'post', '', null, true, $data);

        $validateddata = $mform->get_data();

        if ($validateddata) {
            insert_graderaction($validateddata);
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
    }

    public static function submit_graderaction_returns()
    {
        return new external_value(PARAM_INT, '');
    }



    /** Describes the parameters for approve_graderaction webservice.
     * @return external_function_parameters
     */
    public static function approve_graderaction_parameters()
    {
        return new external_function_parameters([
           
                'contextid' => new external_value(PARAM_INT, 'context id '),
                'userid' => new external_value(PARAM_INT, 'USer id'),
                'moduleid' => new external_value(PARAM_INT, 'Module id'),
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'method' => new external_value(PARAM_RAW, 'Method'),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm status'),
                'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
                'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
                'filterdata' => new external_value(PARAM_RAW, 'total number of records in result set', VALUE_OPTIONAL),
        ]
        );
    }

    /**
     * Approve course activity completion
     *
     * @param int $courseid,$userid,$moduleid,$method
     * @param int $confirm
     * @return int .
     */
    public static function approve_graderaction($contextid, $userid, $moduleid, $courseid, $method, $confirm,$options,$dataoptions)
    {
        global $DB;
      
        try {
            if ($confirm) {
                $validateddata = new stdClass();
                $validateddata->resetreason = '';
                $validateddata->moduleid = $moduleid;
                $validateddata->contextid = $contextid;
                $validateddata->method = $method;
                $validateddata->courseid = $courseid;
                $validateddata->userid = $userid;
                insert_graderaction($validateddata);
                $return = true;
            } else {
                $return = false;
            }
        } catch (dml_exception $ex) {
            print_error('deleteerror', 'local_courses');
            $return = false;
        }
        return $return;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function approve_graderaction_returns()
    {
        return new external_value(PARAM_BOOL, 'return');
    }


    /** Describes the parameters for courses_assignments_view webservice.
     * @return external_function_parameters
     */
    public static function courses_assignments_view_parameters()
    {
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
    public static function courses_assignments_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata)
    {
        global $DB, $PAGE;
        require_login();

        $PAGE->set_url('/local/courseassignment/courses.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::courses_assignments_view_parameters(),
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
        $filteroptions = json_decode($options);
        if(is_array($filtervalues)){
            $filtervalues = (object)$filtervalues;
        } 
        $filtervalues->courseid = $filteroptions->courseid;
  
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = get_listof_courses_assignments($stable, $filtervalues);
        $totalcount = $data['totalrecords'];
        return [
            'totalcount' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $data['result'],
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function courses_assignments_view_returns()
    {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
            'totalcount' => new external_value(PARAM_INT, 'total number of records in result set', VALUE_OPTIONAL),
            'filterdata' => new external_value(PARAM_RAW, 'total number of records in result set', VALUE_OPTIONAL),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
                        'completiondate' =>new external_value(PARAM_RAW, 'completion date', VALUE_OPTIONAL),
                        'reason' => new external_value(PARAM_RAW, 'reason', VALUE_OPTIONAL),
                        'completereason' => new external_value(PARAM_RAW, 'completereason', VALUE_OPTIONAL),
                        'resetstatus' => new external_value(PARAM_BOOL, 'reset status', VALUE_OPTIONAL),
                        'rejectstatus' => new external_value(PARAM_BOOL, 'reject status', VALUE_OPTIONAL),
                        'approvestatus' => new external_value(PARAM_BOOL, 'approve status', VALUE_OPTIONAL),
                        'status' => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                        'method' => new external_value(PARAM_RAW, 'method', VALUE_OPTIONAL),
                        'contextid' => new external_value(PARAM_INT, 'contextid', VALUE_OPTIONAL),
                        'fileid' => new external_value(PARAM_INT, 'fileid', VALUE_OPTIONAL),
                        'itemid' => new external_value(PARAM_INT, 'itemid', VALUE_OPTIONAL),
                        'filename' => new external_value(PARAM_RAW, 'file name', VALUE_OPTIONAL),
                        'courseid' => new external_value(PARAM_INT, 'course id', VALUE_OPTIONAL),
                        'userid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL),
                        'firstname' => new external_value(PARAM_RAW, 'first name', VALUE_OPTIONAL),
                        'lastname' => new external_value(PARAM_RAW, 'last name', VALUE_OPTIONAL),
                        'email' => new external_value(PARAM_RAW, 'email', VALUE_OPTIONAL),
                        'open_employeeid' => new external_value(PARAM_RAW, ' employee id', VALUE_OPTIONAL),
                        'fullname' => new external_value(PARAM_RAW, 'full name', VALUE_OPTIONAL),
                        'moduleid' => new external_value(PARAM_INT, 'module id', VALUE_OPTIONAL),
                        'completionstate' => new external_value(PARAM_INT, 'completion state', VALUE_OPTIONAL),
                        'name' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                        'modifieddate' => new external_value(PARAM_RAW, 'modified date', VALUE_OPTIONAL),
                        'initialdate' => new external_value(PARAM_RAW, 'initial date', VALUE_OPTIONAL),
                        'assignurl' => new external_value(PARAM_RAW, 'download url', VALUE_OPTIONAL),
                        'courseurl' =>  new external_value(PARAM_RAW, 'course url', VALUE_OPTIONAL),
                        'gradeurl' =>  new external_value(PARAM_RAW, 'grade url', VALUE_OPTIONAL),
                    )
                ),'records', VALUE_OPTIONAL)
            ]);
    }


   /** Describes the parameters for delete_course webservice.
    * @return external_function_parameters
    */
    public static function sme_courses_view_parameters() {
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

    public static function sme_courses_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
        global $DB, $PAGE;
        require_login();
        $PAGE->set_url('/local/courseassignment/smecourses.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::sme_courses_view_parameters(),
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
        $data = get_listof_smecourses($stable, $filtervalues);
        $totalcount = $data['totalcourses'];
        return [
            'totalcount' => $totalcount,
            'length' => $totalcount,
            'filterdata' => $filterdata,
            'records' =>$data,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }


  /**
   * Returns description of method result value
   * @return external_description
   */

  public static function sme_courses_view_returns() {
    return new external_single_structure([
        'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
        'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
        'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set', VALUE_OPTIONAL),
        'filterdata' => new external_value(PARAM_RAW, 'total number of challenges in result set', VALUE_OPTIONAL),
        'length' => new external_value(PARAM_RAW, 'total number of challenges in result set', VALUE_OPTIONAL),
        'records' => new external_single_structure(
                array(
                    'hascourses' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'coursename' => new external_value(PARAM_RAW, 'coursename', VALUE_OPTIONAL),
                                'coursenameCut' => new external_value(PARAM_RAW, 'coursenameCut', VALUE_OPTIONAL),
                                'catname' => new external_value(PARAM_RAW, 'catname', VALUE_OPTIONAL),
                                'catnamestring' => new external_value(PARAM_RAW, 'catnamestring', VALUE_OPTIONAL),
                                'courseimage' => new external_value(PARAM_RAW, 'catnamestring', VALUE_OPTIONAL),
                                'enrolled_count' => new external_value(PARAM_INT, 'enrolled_count', VALUE_OPTIONAL),
                                'courseid' => new external_value(PARAM_INT, 'courseid', VALUE_OPTIONAL),
                                'completed_count' => new external_value(PARAM_INT, 'completed_count', VALUE_OPTIONAL),
                                'points' => new external_value(PARAM_INT, 'points', VALUE_OPTIONAL),
                                'coursetype' => new external_value(PARAM_RAW, 'coursetype', VALUE_OPTIONAL),
                                'coursesummary' => new external_value(PARAM_RAW, 'coursesummary', VALUE_OPTIONAL),
                                'courseurl' => new external_value(PARAM_RAW, 'courseurl',VALUE_OPTIONAL),
                                'enrollusers' => new external_value(PARAM_RAW, 'enrollusers', VALUE_OPTIONAL),
                                'editcourse' => new external_value(PARAM_RAW, 'editcourse', VALUE_OPTIONAL),
                                'update_status' => new external_value(PARAM_RAW, 'update_status', VALUE_OPTIONAL),
                                'course_class' => new external_value(PARAM_TEXT, 'course_status', VALUE_OPTIONAL),
                                'deleteaction' => new external_value(PARAM_RAW, 'designation', VALUE_OPTIONAL),
                                'grader' => new external_value(PARAM_RAW, 'grader', VALUE_OPTIONAL),
                                'activity' => new external_value(PARAM_RAW, 'activity', VALUE_OPTIONAL),
                                'requestlink' => new external_value(PARAM_RAW, 'requestlink', VALUE_OPTIONAL),
                                'facilitatorlink' => new external_value(PARAM_RAW, 'facilitatorlink', VALUE_OPTIONAL),
                                'skillname' => new external_value(PARAM_RAW, 'skillname', VALUE_OPTIONAL),
                                'ratings_value' => new external_value(PARAM_RAW, 'ratings_value', VALUE_OPTIONAL),
                                'ratingenable' => new external_value(PARAM_BOOL, 'ratingenable', VALUE_OPTIONAL),
                                'tagstring' => new external_value(PARAM_RAW, 'tagstring', VALUE_OPTIONAL),
                                'tagenable' => new external_value(PARAM_BOOL, 'tagenable', VALUE_OPTIONAL),
                            )
                        ), 'hascourses', VALUE_OPTIONAL
                    ),
                 
                    'nocourses' => new external_value(PARAM_BOOL, 'nocourses', VALUE_OPTIONAL),
                    'totalcourses' => new external_value(PARAM_INT, 'totalcourses', VALUE_OPTIONAL),
                    'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                ), 'records', VALUE_OPTIONAL
            )

        ]);
    }

     /** Describes the parameters for courses_assignments_view webservice.
     * @return external_function_parameters
     */
    public static function courses_assignments_listview_parameters()
    {
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
    public static function courses_assignments_listview($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata)
    {
        global $DB, $PAGE;
        require_login();

        $PAGE->set_url('/local/courseassignment/assignments.php', array());
        $PAGE->set_context($contextid);
        // Parameter validation.
        $params = self::validate_parameters(
            self::courses_assignments_listview_parameters(),
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
        $filteroptions = json_decode($options);
        if(is_array($filtervalues)){
            $filtervalues = (object)$filtervalues;
        } 
        $filtervalues->courseid = $filteroptions->courseid;
  
        $stable = new \stdClass();
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $data = get_listof_courses_assignments_new($stable, $filtervalues);
        $totalcount = $data['totalrecords'];
        return [
            'totalcount' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $data['result'],
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_description
     */

    public static function courses_assignments_listview_returns()
    {
        return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service', VALUE_OPTIONAL),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service', VALUE_OPTIONAL),
            'totalcount' => new external_value(PARAM_INT, 'total number of records in result set', VALUE_OPTIONAL),
            'filterdata' => new external_value(PARAM_RAW, 'total number of records in result set', VALUE_OPTIONAL),
            'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'id', VALUE_OPTIONAL),
                        'completiondate' =>new external_value(PARAM_RAW, 'completion date', VALUE_OPTIONAL),
                        'reason' => new external_value(PARAM_RAW, 'reason', VALUE_OPTIONAL),
                        'completereason' => new external_value(PARAM_RAW, 'completereason', VALUE_OPTIONAL),
                        'resetstatus' => new external_value(PARAM_BOOL, 'reset status', VALUE_OPTIONAL),
                        'rejectstatus' => new external_value(PARAM_BOOL, 'reject status', VALUE_OPTIONAL),
                        'approvestatus' => new external_value(PARAM_BOOL, 'approve status', VALUE_OPTIONAL),
                        'status' => new external_value(PARAM_RAW, 'status', VALUE_OPTIONAL),
                        'method' => new external_value(PARAM_RAW, 'method', VALUE_OPTIONAL),
                        'contextid' => new external_value(PARAM_INT, 'contextid', VALUE_OPTIONAL),
                        'fileid' => new external_value(PARAM_INT, 'fileid', VALUE_OPTIONAL),
                        'itemid' => new external_value(PARAM_INT, 'itemid', VALUE_OPTIONAL),
                        'filename' => new external_value(PARAM_RAW, 'file name', VALUE_OPTIONAL),
                        'courseid' => new external_value(PARAM_INT, 'course id', VALUE_OPTIONAL),
                        'userid' => new external_value(PARAM_INT, 'user id', VALUE_OPTIONAL),
                        'firstname' => new external_value(PARAM_RAW, 'first name', VALUE_OPTIONAL),
                        'lastname' => new external_value(PARAM_RAW, 'last name', VALUE_OPTIONAL),
                        'email' => new external_value(PARAM_RAW, 'email', VALUE_OPTIONAL),
                        'open_employeeid' => new external_value(PARAM_RAW, ' employee id', VALUE_OPTIONAL),
                        'fullname' => new external_value(PARAM_RAW, 'full name', VALUE_OPTIONAL),
                        'moduleid' => new external_value(PARAM_INT, 'module id', VALUE_OPTIONAL),
                        'completionstate' => new external_value(PARAM_INT, 'completion state', VALUE_OPTIONAL),
                        'name' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL),
                        'modifieddate' => new external_value(PARAM_RAW, 'modified date', VALUE_OPTIONAL),
                        'initialdate' => new external_value(PARAM_RAW, 'initial date', VALUE_OPTIONAL),
                        'assignurl' => new external_value(PARAM_RAW, 'download url', VALUE_OPTIONAL),
                        'courseurl' =>  new external_value(PARAM_RAW, 'course url', VALUE_OPTIONAL),
                        'gradeurl' =>  new external_value(PARAM_RAW, 'grade url', VALUE_OPTIONAL),
                    )
                ),'records', VALUE_OPTIONAL)
            ]);
    }
}