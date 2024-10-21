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
 * Feedback external API
 *
 * @package    block_training_calendar
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die;
 
require_once($CFG->libdir.'/externallib.php');
use \local_classroom\classroom as classroom;

/**
 * Feedback external functions
 *
 * @package    local_onlinetests
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class block_training_calendar_external extends external_api {

// public static function managecontentpopuptabs_parameters(){
//     return new external_function_parameters([
//                 'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
//                 'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
//                 'offset' => new external_value(PARAM_INT, 'Number of items to skip from the begging of the result set',
//                     VALUE_DEFAULT, 0),
//                 'limit' => new external_value(PARAM_INT, 'Maximum number of results to return',
//                     VALUE_DEFAULT, 0),
//                  'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
//         ]);
// }
   
// public static function managecontentpopuptabs(  
//         $options,
//         $dataoptions,
//         $offset = 0,
//         $limit = 0,
//         $filterdata 
//     ){
//      global $OUTPUT, $CFG, $DB,$USER,$PAGE;
//         $sitecontext = context_system::instance();
//         require_login();
//         $PAGE->set_url('/block/training_calendar/view.php', array());
//         $PAGE->set_context($sitecontext);
//         // Parameter validation.
//         $params = self::validate_parameters(
//             self::managecontentpopuptabs_parameters(),
//             [
//                 'options' => $options,
//                 'dataoptions' => $dataoptions,
//                 'offset' => $offset,
//                 'limit' => $limit,
//                 'filterdata' => $filterdata
//             ]
//         );
//         $offset = $params['offset'];
//         $limit = $params['limit'];
//         $decodedata = json_decode($params['dataoptions']);
//         $filtervalues = json_decode($filterdata);

//         $stable = new \stdClass();
//         $stable->thead = true;
       
//         $stable->thead = false;
//         $stable->start = $offset;
//         $stable->length = $limit;
//         $result_certi = certification_details($stable,$filtervalues);
//         //print_object($result_certi);
//         //print_object($result_skill['data']);
//         //$data=$result_skill['data'];
//         $totalcount = $result_certi['count'];
//         if($totalcount>0){
//             $data=$result_certi['data'];
//             $status='';
//         }else{
//             $data=array();  //No data available in table
//             $status='No sessions created yet';
//         }
        
//         return [
//             'totalcount' => $totalcount,
//             'records' =>$data,
//             'options' => $options,
//             'dataoptions' => $dataoptions,
//             'filterdata' => $filterdata,
//             'status'=>$status,
//         ];
//         // $data_object = (json_decode($dataoptions));
//         // $offset = $params['offset'];
//         // $limit = $params['limit'];
//         // $filtervalues = json_decode($filterdata);

//         // $stable = new \stdClass();
//         // $stable->objtype = isset($data_object->objtype) ? $data_object->objtype : 'exams' ;
//         // $stable->thead = true;

//         // $exams=competency::$getobjtype($stable,$filtervalues);
//         // $totalcount=$exams[''.$stable->objtype.'count'];
//         // $stable->thead = false;
//         // $stable->start = $offset;
//         // $stable->length = $limit;

//         // $data = array();

//         // if($totalcount>0){

//         //     $setobjtype='list_objectives_'.$stable->objtype.'info';

//         //     $renderer = $PAGE->get_renderer('local_competency');

//         //     $data = array_merge($data,$renderer->$setobjtype($stable,$filtervalues));
//         // }
//         // return [
//         //     'totalcount' => $totalcount,
//         //     'records' =>$data,
//         //     'options' => $options,
//         //     'dataoptions' => $dataoptions,
//         //     'filterdata' => $filterdata,
//         //     'nodata' => get_string('nocompetency'.$stable->objtype.'','local_competency')
//         // ];
// }

// public static function managecontentpopuptabs_returns(){
//             return new external_single_structure([
//             'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
//             'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
//             'totalcount' => new external_value(PARAM_INT, 'total number of competencies in result set'),
//             'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
//             'nodata' => new external_value(PARAM_RAW, 'The data for the service'),
//             'records' => new external_multiple_structure(
//                             new external_single_structure(
//                                 array(
//                                     'competencypcid' => new external_value(PARAM_INT, ' competency pc id'),
//                                     'id' => new external_value(PARAM_INT, ' id'),
//                                     'name' => new external_value(PARAM_RAW, ' name'),
//                                     'code' => new external_value(PARAM_RAW, ' code'),
//                                     'delete' => new external_value(PARAM_RAW, 'competency pc actions',VALUE_OPTIONAL),
//                                     'objectiveurl' => new external_value(PARAM_RAW, ' objective url'),
//                                 )
//                             )
//             )
//         ]);
// }
public static function maduledescription_parameters(){
    return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'classroomid', 0),
                'contextid' => new external_value(PARAM_INT, 'contextid', 0)
            )
        );
}

public static function maduledescription($id, $contextid){
    global $OUTPUT, $CFG, $DB,$USER,$PAGE;
        $sitecontext = context_system::instance();
        require_login();
        $PAGE->set_url('/block/training_calendar/view.php', array());
        $PAGE->set_context($sitecontext);
        // Parameter validation.
        $params = self::validate_parameters(
            self::maduledescription_parameters(),
            [
                'id' => $id,
                'contextid' => $contextid
            ]
        );

         $classroom = $DB->get_record('local_classroom', array('id' => $id));
         if($classroom->description){
            $description =$classroom->description;
         }else{
            $description = 'N/A';
         }
         return $description;
}
public static function maduledescription_returns(){
    // return new external_single_structure([
    //         'description' => new external_value(PARAM_RAW, 'The description for the module'),
    //     ]);
    return new external_value(PARAM_RAW, 'The description for the module');
}

// public static moduletargetlearners_parameters(){
//      return new external_function_parameters(
//             array(
//                 'id' => new external_value(PARAM_INT, 'classroomid', 0),
//                 'contextid' => new external_value(PARAM_INT, 'contextid', 0)
//             )
//         );
// }
// public static moduletargetlearners(){
//     global $OUTPUT, $CFG, $DB,$USER,$PAGE;
//         $sitecontext = context_system::instance();
//         require_login();
//         $PAGE->set_url('/block/training_calendar/view.php', array());
//         $PAGE->set_context($sitecontext);
//         // Parameter validation.
//         $params = self::validate_parameters(
//             self::maduledescription_parameters(),
//             [
//                 'id' => $id,
//                 'contextid' => $contextid
//             ]
//         );

//          $classroom = $DB->get_record('local_classroom', array('id' => $id));
//          if($classroom->description){
//             $description =$classroom->description;
//          }else{
//             $description = 'N/A';
//          }
//          return $description;
// }
// public static moduletargetlearners_returns(){
//         return new external_value(PARAM_RAW, 'The description for the module');
// }

public static function moduletargetlearners_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'classroomid'),
            'contextid' => new external_value(PARAM_INT, 'The context id', false),
                
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
    public static function moduletargetlearners($id,$contextid) {
        global $DB, $PAGE;
        
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::moduletargetlearners_parameters(),
            [
                 'id' => $id,
                'contextid' => $contextid
               
            ]
        );

        $targetaudience = (new classroom)->classroomtarget_audience_tab($id);
        $return = [
            'records' => $targetaudience,
            'classroomid' => $id,
        ];
        return $return;


    }

    public static function moduletargetlearners_returns() {
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
    public static function moduleprerequisites_parameters(){
          return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'classroomid'),
            'contextid' => new external_value(PARAM_INT, 'The context id', false),
                
        ]);
    }
    public static function moduleprerequisites($id,$contextid){
         global $DB, $PAGE,$USER;
        
        $context = context::instance_by_id(1, MUST_EXIST);
        self::validate_context($context);
        $params = self::validate_parameters(
            self::moduleprerequisites_parameters(),
            [
                 'id' => $id,
                'contextid' => $contextid
               
            ]
        );
        $classroom = $DB->get_record('local_classroom', array('id' => $id));
        if($classroom->open_prerequisites){
             $prerequisiteslist = $DB->get_records_sql_menu("SELECT c.id,c.fullname  FROM {course} c WHERE  c.id IN ({$classroom->open_prerequisites}) ");
             $coursestatus = array();
          $coursename = array();
                foreach ($prerequisiteslist as $key => $course) {

                    $userid=$USER->id;
                    $completionss=$DB->get_field_sql("SELECT cc.id FROM {course_completions} cc WHERE cc.course = $key AND userid = $userid AND cc.timecompleted IS NOT NULL");

                   if($completionss) {

                         $coursecompleted = true;

                    } else {

                         $coursecompleted = false;
                    }

                    $coursename['coursename'] = $course;
                    $systemcontext = context_system::instance();
                     if(!has_capability('local/classroom:view', $systemcontext) || !is_siteadmin()){
                            
                             $coursename['show'] = true;
                        }
                    if(!has_capability('local/classroom:view', $systemcontext) || !is_siteadmin()){



                           $coursename['status'] = $coursecompleted;
                        }
                        $coursename['courseid'] = $key;
                    // $coursename['course'] = $config->wwwroot;
                    $coursestatus[] = $coursename;
                }  
        }else{
            $coursestatus[] = '';
        }
     
        $return = [
            'records' => $coursestatus,
            'classroomid' => $id,
        ];
        return $return;
    }
    public static function moduleprerequisites_returns(){
         return new external_single_structure([
            'classroomid' => new external_value(PARAM_INT, 'classroomid'),
            'records' => new external_multiple_structure(
                 new external_single_structure(
                array(
                    'courseid' => new external_value(PARAM_INT, 'courseid'),
                    'coursename' => new external_value(PARAM_RAW, 'coursename'),
                    'status' => new external_value(PARAM_BOOL, 'status',VALUE_OPTIONAL),
                    'show' => new external_value(PARAM_BOOL, 'show',VALUE_OPTIONAL),
                )
            )
            )
        ]);
    }

}
