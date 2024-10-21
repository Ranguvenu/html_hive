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
 * @package    block_empcredits
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die;
 
require_once($CFG->libdir.'/externallib.php');
// use \local_classroom\classroom as classroom;

/**
 * Feedback external functions
 *
 * @package    block_empcredits
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class block_empcredits_external extends external_api {


public static function learninganalyticsyear_parameters(){
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

public static function learninganalyticsyear($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ){
      global $OUTPUT, $CFG, $DB, $USER, $PAGE;
   
        $sitecontext = context_system::instance();
        require_login();
        $PAGE->set_url('/local/learningsummary/index.php', array());
        $PAGE->set_context($sitecontext);
        // Parameter validation.
        $params = self::validate_parameters(
            self::learninganalyticsyear_parameters(),
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
        $stable = new \stdClass();
        $stable->search =  json_decode($filterdata)->search_query;
        $stable->thead = true;
        $stable->start = $offset;
        $stable->length = $limit;
        // $filterdata

        $renderer = $PAGE->get_renderer('block_empcredits');
        $sessions = $renderer->get_courses_info_tabs(true,$stable);
       
        $totalcount = $sessions['recordcount'];
        $results = $sessions['results'];
        $totalrecords = array();
        $finalgrade = '';
        foreach ($results as $record) { 
                $data = array();
        
                if($record->moduletypeshort == 'learning_path'){
                    $url = new moodle_url('/local/learningplan/view.php', array('id'=>$record->id));
                 }else{
                    $url = new moodle_url('/course/view.php', array('id'=>$record->id));
                    $finalgrade = $DB->get_field_sql("SELECT gd.finalgrade FROM {grade_items} gi
                    JOIN {grade_grades} gd ON gi.id = gd.itemid 
                    WHERE gi.courseid = :courseid AND gd.userid = :userid",  array('courseid' => $record->id,'userid' => $USER->id));  
                } 
                $data['name'] = html_writer::link($url,$record->fullname, array('target'=>'_blank'));                
                            
                
                if(!empty($record->skill)){
                    $skillsql = "SELECT GROUP_CONCAT(sk.name) FROM {local_skill} sk WHERE sk.id IN ($record->skill) ";
                    $skill = $DB->get_field_sql($skillsql);                       
                }else{
                    $skill = 'N/A';
                }
                if(!empty($record->skillcategory)){ 
                    $skillcategory = $DB->get_field('local_skill_categories' , 'name', array('id' => $record->skillcategory));              
                }else{
                    $skillcategory = 'N/A';
                }
                if($record->duration){
                            $hours = floor($record->duration/3600);
                            $minutes = ($record->duration/60)%60;
                            $c_duration =$hours.':'. $minutes;

                    if ($hours < 1) {
                        $credits = '0.5';
                    } elseif (($hours >= 1 && $hours <= 4) || ($hours == 4 && $minutes <= 59)) {
                        $credits = '1';
                    } elseif (($hours >= 5 && $hours <= 8) || ($hours == 8 && $minutes <= 59)) {
                        $credits = '2';
                    } elseif (($hours >= 9 && $hours <= 12) || ($hours == 12 && $minutes <= 59)) {
                        $credits = '3';
                    }else {
                        $credits = '4';
                    }

                }else{
                    $c_duration = 'NA';
                    $credits = 'N/A';
                }

                $data['credit'] = $credits;
                if(!empty($record->course_provider)){
                    $provider = $DB->get_field('local_course_providers' , 'course_provider', array('id' => $record->course_provider));                    
                }else{
                    $provider = 'N/A';
                }

                $data['category'] = ($record->category) ? $record->category : 'N/A';
                $data['moduletype'] = $record->moduletype;                  
                $data['course_provider'] =  $provider ;
                $data['open_grade'] = ($record->open_grade) ? $record->open_grade : 'N/A';
                $data['enroleddate'] = ($record->enroleddate) ? date('d-m-Y ',$record->enroleddate) :'N/A';
                $data['finalgrade'] = ($finalgrade) ? number_format((float)$finalgrade, 2, '.', '') : 'N/A';
                $data['skillcategory']  = $skillcategory;
                $data['skill']  =  $skill;
              
                $data['completeddate'] = ($record->timecompleted) ? date('d-m-Y ',$record->timecompleted):'N/A';
                $data['duration'] =  $c_duration;
                $totalrecords[] = $data;
                // $table->data[] = $data;
            }
       
        $return = [
            'totalcount' => $totalcount,
            'records' => $totalrecords,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
        return $return;
}
public static function learninganalyticsyear_returns(){
     return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'category' => new external_value(PARAM_RAW, 'category'),
                                    'moduletype' => new external_value(PARAM_RAW, 'moduletype'),
                                    'course_provider' => new external_value(PARAM_RAW, 'course_provider'),
                                    'open_grade' => new external_value(PARAM_RAW, 'open_grade'),
                                    'enroleddate' => new external_value(PARAM_RAW, 'enroleddate'),
                                    'finalgrade' => new external_value(PARAM_RAW, 'finalgrade'),
                                    'skillcategory' => new external_value(PARAM_RAW, 'skillcategory'),
                                    'skill' => new external_value(PARAM_RAW, 'skill'),
                                    'completeddate' => new external_value(PARAM_RAW, 'completion date'),
                                    'duration' => new external_value(PARAM_RAW, 'duration',VALUE_OPTIONAL),
                                    'credit' => new external_value(PARAM_RAW, 'credit'),

                                )
                            )
            )
        ]);
}


public static function learninganalyticsallccdata_parameters(){
   
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

public static function learninganalyticsallccdata($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ){
    global  $DB, $USER, $PAGE;

        $sitecontext = context_system::instance();
        require_login();
        $PAGE->set_url('/local/learningsummary/index.php', array());
        $PAGE->set_context($sitecontext);
        // Parameter validation.
        $params = self::validate_parameters(
            self::learninganalyticsallccdata_parameters(),
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
        $stable = new \stdClass();
        $stable->search =  json_decode($filterdata)->search_query;
        $stable->thead = true;
        $stable->start = $offset;
        $stable->length = $limit;
        $stable->filterdata = json_decode($filterdata);;
        
        $renderer = $PAGE->get_renderer('block_empcredits');
        $sessions = $renderer->get_courses_info_tabs(false,$stable);
        $totalcount = $sessions['recordcount'];
        $results = $sessions['results'];
        $totalrecords = array();
        $finalgrade = '';
        foreach ($results as $record) { 
                $data = array();
        
                if($record->moduletypeshort == 'learning_path'){
                    $url = new moodle_url('/local/learningplan/view.php', array('id'=>$record->id));
                            
                }else{
                    $url = new moodle_url('/course/view.php', array('id'=>$record->id));
                    $finalgrade = $DB->get_field_sql("SELECT gd.finalgrade FROM {grade_items} gi
                    JOIN {grade_grades} gd ON gi.id = gd.itemid 
                    WHERE gi.courseid = :courseid AND gd.userid = :userid",  array('courseid' => $record->id,'userid' => $USER->id));             
					
                } 
                $data['name'] = html_writer::link($url,$record->fullname, array('target'=>'_blank')); 
                
                if(!empty($record->skill)){
                    $skillsql = "SELECT GROUP_CONCAT(sk.name) FROM {local_skill} sk WHERE sk.id IN ($record->skill) ";
                    $skill = $DB->get_field_sql($skillsql);                       
                }else{
                    $skill = 'N/A';
                }
                if(!empty($record->skillcategory)){ 
                    $skillcategory = $DB->get_field('local_skill_categories' , 'name', array('id' => $record->skillcategory));              
                }else{
                    $skillcategory = 'N/A';
                }

                if(!empty($record->course_provider)){
                    $provider = $DB->get_field('local_course_providers' , 'course_provider', array('id' => $record->course_provider));                    
                }else{
                    $provider = 'N/A';
                }
                if($record->duration){
                            $hours = floor($record->duration/3600);
                            $minutes = ($record->duration/60)%60;
                            $c_duration =$hours.':'. $minutes;
                }else{
                            $c_duration = 'NA';
                }
               
                $data['category'] = ($record->category) ? $record->category : 'N/A';
                $data['moduletype'] = $record->moduletype;                  
                $data['course_provider'] =  $provider ;
                $data['open_grade'] = ($record->open_grade) ? $record->open_grade : 'N/A';
                $data['enroleddate'] = ($record->enroleddate) ? date('d-m-Y ',$record->enroleddate) :'N/A';
                $data['finalgrade'] = ($finalgrade) ? number_format($finalgrade, 2, '.', '') : 'N/A';
                $data['skillcategory']  = $skillcategory; 
                $data['skill']  = $skill;
                
                $data['completeddate'] = date('d-m-Y ',$record->timecompleted);
                $data['duration'] =  $c_duration;
                $totalrecords[] = $data;
                // $table->data[] = $data;
            }
    //    print_r($totalrecords);exit;
        $return = [
            'totalcount' => $totalcount,
            'records' => $totalrecords,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
        return $return;
}
public static function learninganalyticsallccdata_returns(){
    return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'category' => new external_value(PARAM_RAW, 'category'),
                                    'moduletype' => new external_value(PARAM_RAW, 'moduletype'),
                                    'course_provider' => new external_value(PARAM_RAW, 'course_provider'),
                                    'open_grade' => new external_value(PARAM_RAW, 'open_grade'),
                                    'enroleddate' => new external_value(PARAM_RAW, 'enroleddate'),
                                    'finalgrade' => new external_value(PARAM_RAW, 'finalgrade'),
                                    'skillcategory' => new external_value(PARAM_RAW, 'skillcategory'),
                                    'skill' => new external_value(PARAM_RAW, 'skill'),
                                    'completeddate' => new external_value(PARAM_RAW, 'completion date'),
                                    'duration' => new external_value(PARAM_RAW, 'duration',VALUE_OPTIONAL),

                                )
                            )
            )
        ]);
}



public static function learninganalyticscertdata_parameters(){
  
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

public static function learninganalyticscertdata($contextid, $options,
        $dataoptions,
        $offset = 0,
        $limit = 0,
        $filterdata
    ){
  
    global $OUTPUT, $CFG, $DB, $USER, $PAGE;
        $sitecontext = context_system::instance();
        require_login();
        $PAGE->set_url('/local/learningsummary/index.php', array());
        $PAGE->set_context($sitecontext);
        // Parameter validation.
        $params = self::validate_parameters(
            self::learninganalyticscertdata_parameters(),
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

        $filtervalues = json_decode($filterdata);
        $decodeddataoptions = json_decode($dataoptions);
        $stable = new \stdClass();
        $stable->search = json_decode($filterdata)->search_query;
        $stable->thead = false;
        $stable->start = $offset;
        $stable->length = $limit;
        $renderer = $PAGE->get_renderer('block_empcredits');
        $results = $renderer->get_certificates_view_tabs($stable,$filtervalues);
        $totalcount = $results['recordcount'];
        $totalrecords = $results['results'];
       
        $return = [
            'totalcount' => $totalcount,
            'filterdata' => $filterdata,
            'records' => $totalrecords,
            'options' => $options,
            'dataoptions' => $dataoptions,
        ];
        return $return;
}
public static function learninganalyticscertdata_returns(){
    return new external_single_structure([
            'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
            'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
            'totalcount' => new external_value(PARAM_INT, 'totalcount'),
             'filterdata' => new external_value(PARAM_RAW, 'filter data', VALUE_OPTIONAL),
            'records' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'name' => new external_value(PARAM_RAW, 'name'),
                                    'learningtype' => new external_value(PARAM_RAW, 'learningtype'),
                                    'skill' => new external_value(PARAM_RAW, 'skill'),
                                    'uploadeddate' => new external_value(PARAM_RAW, 'uploadeddate'),
                                    'approveddate' => new external_value(PARAM_RAW, 'approveddate'),
                                    'download' => new external_value(PARAM_RAW, 'download'),
                                )
                            )
            )
        ]);
}
}
