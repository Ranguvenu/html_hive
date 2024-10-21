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
 * @package    local_fmsapi
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */

defined('MOODLE_INTERNAL') || die;
global $CFG,$OUTPUT;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/local/fmsapi/lib.php');
require_once($CFG->dirroot.'/local/courses/lib.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/completion/completion_completion.php');

class local_fmsapi_external extends external_api {

     /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function get_coursedetails_parameters() {
        //Course Name, Employee Name, Employee Email, Employee ID, Course Description
        return new external_function_parameters(
            array(
                'skillname' =>new external_value(PARAM_RAW, 'Skill Name',VALUE_REQUIRED),
                'empemail' => new external_value(PARAM_RAW, 'Employee Email'),
                'empid' => new external_value(PARAM_RAW, 'Employee ID',VALUE_REQUIRED),
                'empname' => new external_value(PARAM_RAW, 'Employee Name'),
            )
        );
    }

    public static function get_coursedetails($skillname,$empemail,$empid,$empname)//,$description)
    {
     
        global $DB, $CFG, $USER;
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::get_coursedetails_parameters(),
                                            ['skillname' => $skillname, 'empemail' => $empemail, 'empid' => $empid,'empname' => $empname]);
                                           
        $noofrecords = 0;
        $courses = array();
        $coursenames = array();
        $skillnames= '';
        $sql = array('0');
        
        //validate skill and user
        list($isvalid, $reason) = validate_input($skillname,$empid);

        if(!empty($reason) && $isvalid){ 
            
            $result = [
                'count' => $noofrecords,
                'message' => 'error',
                'reason' => $reason,
                'result' => $courses    
            ];            
            return $result;
        } 

        if (strpos($skillname, ',') !== false) { 
            $skillnames = explode(',',$skillname);
        }
        
        if(!empty($skillnames)){
            foreach($skillnames as $skill){
                $sql[] = "shortname = '$skill'";
            }  
        }else{
            $sql[] ="shortname = '$skillname'";
        }
             
      
        $skillids = "SELECT id FROM {local_skill} WHERE ".implode(" OR ", $sql);
        $skillids = $DB->get_fieldset_sql($skillids);       
        $array = array();
       
        if(count($skillids) > 0){
            foreach($skillids as $skill){ 
                $sql = "SELECT c.id,c.fullname,c.visible,c.summary,c.open_identifiedas,ct.course_type ,                   
                      c.open_identifiedas,GROUP_CONCAT(sk.name) as courseskills FROM {course} As c
                      LEFT JOIN {local_course_types} ct ON c.open_identifiedas = ct.id
                      LEFT JOIN {local_skill} sk ON FIND_IN_SET(sk.id, c.open_skill)
                      WHERE c.visible =1 AND FIND_IN_SET($skill,c.open_skill) > 0 AND c.open_identifiedas NOT IN (2)
                      GROUP BY c.id,c.fullname,c.visible,c.summary,c.open_identifiedas,ct.course_type";
                $mulcourse = $DB->get_records_sql($sql,array());
                if(!empty($mulcourse)){
                    $array[] = $mulcourse;               
                }
            }
        }
       
       // 
      
        $array = call_user_func_array("array_merge", $array);
        $array = array_unique($array, SORT_REGULAR);
       
        if(!empty($array) && count($array) > 0){ 
            $noofrecords = count($array);
            foreach($array as $res){ 
               
                $coursecode = empty($res->shortname) ? 'N/A' : $res->shortname;
                $coursename = $res->fullname;
                $coursetype = $res->course_type;
                $courseurl = $CFG->wwwroot.'/course/view.php?id='.$res->id;
                $description =  strip_tags($res->summary);
                $coursenames[] = $coursename;
                $courses[] = array('courseskills'=>$res->courseskills,'coursecode' => $coursecode, 'coursename' => $coursename, 'description' => $description,'coursetype' =>$coursetype, 'courseurl' => $courseurl); 
            
            }  
            $message = 'Success'; 
        }else{
            $message = ' No records found';
        } 
      
        $data = new stdClass();
        $data->coursename = implode(",",(array)$coursenames);          
        $data->employee_id = $empid;
        $data->employee_name = $empname;            
        $data->employee_email = $empemail;
        $data->course_description = ($description) ? $description : NULL;
        $data->message = $message;
        $data->requested_by =  $empid; 
        $data->requested_date = time();
        $data->usercreated = $USER->id;
        $data->timecreated = time();
        $data->skillkeyword = $skillname;
        
        $insert = $DB->insert_record('local_fmsapi_course_search', $data); 
        $result = [
            'count' => $noofrecords,
            'message' => $message,
            'result' => $courses

        ]; 
      
        return $result;
    }
 
    public static function get_coursedetails_returns()
    {
        return new external_single_structure(
            //for mutiple records as result
            array(
                'count' => new external_value(PARAM_TEXT, 'Number of courses', VALUE_OPTIONAL),
                'message' => new external_value(PARAM_TEXT, 'success message'),
                'reason' => new external_value(PARAM_TEXT, 'Reason if error message', VALUE_OPTIONAL),
                'result' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseskills' =>new external_value(PARAM_TEXT, 'Course skills', VALUE_OPTIONAL),
                            'coursecode' => new external_value(PARAM_TEXT, 'course shortname', VALUE_OPTIONAL),
                            'coursename' => new external_value(PARAM_TEXT, 'course fullname', VALUE_OPTIONAL),
                            'description' => new external_value(PARAM_TEXT, 'course description', VALUE_OPTIONAL),
                            'coursetype' => new external_value(PARAM_TEXT, 'course type', VALUE_OPTIONAL),
                            'courseurl' => new external_value(PARAM_TEXT, 'course url', VALUE_OPTIONAL)
                        )
                    )
                )
            )
       
        );
    }
    public static function get_skills_parameters(){
        return new external_function_parameters(
            array()
        );
    }
    public static function get_skills(){
        global $DB;
        $skills = array();
        $sql = "SELECT sk.id as skillid,sk.shortname as skillcode,sk.name as skillname,skc.name as skillcategoryname,skc.shortname as skillcategorycode 
                FROM {local_skill} sk JOIN {local_skill_categories} skc ON skc.id = sk.category ORDER BY skc.id asc";
        $skillres = $DB->get_records_sql($sql, array());
        if(count($skillres)>0){
            foreach($skillres as $skill){
                $skillcategorycode = strip_tags(trim($skill->skillcategorycode));
                $skillcategory = strip_tags(trim($skill->skillcategoryname));
                $skillcode = strip_tags(trim($skill->skillcode));
                $skillname = strip_tags(trim($skill->skillname));
                $skills[] = array('skillcategorycode' =>$skillcategorycode, 'skillcategory' => $skillcategory,'skillcode'=>$skillcode,'skillname' => $skillname); 
            }
        }
        $result = [
            'result' => $skills
        ];  
      
        return $result;
    }

    public static function get_skills_returns(){
        return new external_single_structure(
           array(
              'result' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'skillcategorycode' => new external_value(PARAM_RAW, 'Skill Category shortname',VALUE_REQUIRED),
                        'skillcategory' => new external_value(PARAM_RAW, 'Skill Category',VALUE_REQUIRED),
                        'skillcode' => new external_value(PARAM_RAW, 'Skill shortname',VALUE_REQUIRED),
                        'skillname' => new external_value(PARAM_RAW, 'Skill Name',VALUE_REQUIRED)
                    )
                )
            )
         )
      );
    }

    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function get_courseinfo_parameters() {
        //Course Name, Employee Name, Employee Email, Employee ID, Course Description
        return new external_function_parameters(
            array(
                'skillname' =>new external_value(PARAM_RAW, 'Skill Name',VALUE_REQUIRED),
                'empemail' => new external_value(PARAM_RAW, 'Employee Email'),
                'empid' => new external_value(PARAM_RAW, 'Employee ID',VALUE_REQUIRED),
                'empname' => new external_value(PARAM_RAW, 'Employee Name'),
            )
        );
    }

    public static function get_courseinfo($skillname,$empemail,$empid,$empname)//,$description)
    {
     
        global $DB, $CFG, $USER;
        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::get_coursedetails_parameters(),
                                            ['skillname' => $skillname, 'empemail' => $empemail, 'empid' => $empid,'empname' => $empname]);
                                           
        $noofrecords = 0;
        $courses = array();
        $coursenames = array();
        $skillnames= '';
        $sql = array('0');
        
        //validate skill and user
        list($isvalid, $reason) = validate_input($skillname,$empid);

        if(!empty($reason) && $isvalid){ 
            
            $result = [
                'count' => $noofrecords,
                'message' => 'error',
                'reason' => $reason,
                'result' => $courses    
            ];            
            return $result;
        } 

        if (strpos($skillname, ',') !== false) { 
            $skillnames = explode(',',$skillname);
        }
        
        if(!empty($skillnames)){
            foreach($skillnames as $skill){
                $sql[] = "shortname = '$skill'";
            }  
        }else{
            $sql[] ="shortname = '$skillname'";
        }
             
      
        $skillids = "SELECT id FROM {local_skill} WHERE ".implode(" OR ", $sql);
        $skillids = $DB->get_fieldset_sql($skillids);       
        $array = array();
       
        if(count($skillids) > 0){
            foreach($skillids as $skill){ 
                $sql = "SELECT c.id,c.fullname,c.visible,c.summary,c.open_identifiedas,ct.course_type ,                   
                      c.open_identifiedas,GROUP_CONCAT(sk.name) as courseskills FROM {course} As c
                      LEFT JOIN {local_course_types} ct ON c.open_identifiedas = ct.id
                      LEFT JOIN {local_skill} sk ON FIND_IN_SET(sk.id, c.open_skill)
                      WHERE c.visible =1 AND FIND_IN_SET($skill,c.open_skill) > 0 AND c.open_identifiedas NOT IN (2)
                      GROUP BY c.id,c.fullname,c.visible,c.summary,c.open_identifiedas,ct.course_type";
                $mulcourse = $DB->get_records_sql($sql,array());
                if(!empty($mulcourse)){
                    $array[] = $mulcourse;               
                }
            }
        }
       
       // 
      
        $array = call_user_func_array("array_merge", $array);
        $array = array_unique($array, SORT_REGULAR);
       
        if(!empty($array) && count($array) > 0){ 
            $noofrecords = count($array);
            foreach($array as $res){ 
               
                $coursecode = empty($res->shortname) ? 'N/A' : $res->shortname;
                $coursename = $res->fullname;
                $coursetype = $res->course_type;
                $courseurl = $CFG->wwwroot.'/course/view.php?id='.$res->id;
                $description =  strip_tags($res->summary);
                $coursenames[] = $coursename;
                $courses[] = array('courseskills'=>$res->courseskills,'coursecode' => $coursecode, 'coursename' => $coursename, 'description' => $description,'coursetype' =>$coursetype, 'courseurl' => $courseurl); 
            
            }  
            $message = 'Success'; 
        }else{
            $message = ' No records found';
        } 
      
        $data = new stdClass();
        $data->coursename = implode(",",(array)$coursenames);          
        $data->employee_id = $empid;
        $data->employee_name = $empname;            
        $data->employee_email = $empemail;
        $data->course_description = ($description) ? $description : NULL;
        $data->message = $message;
        $data->requested_by =  $empid; 
        $data->requested_date = time();
        $data->usercreated = $USER->id;
        $data->timecreated = time();
        $data->skillkeyword = $skillname;
        
        $insert = $DB->insert_record('local_fmsapi_course_search', $data); 
        $result = [
            'count' => $noofrecords,
            'message' => $message,
            'result' => $courses

        ]; 
      
        return $result;
    }
 
    public static function get_courseinfo_returns()
    {
        return new external_single_structure(
            //for mutiple records as result
            array(
                'count' => new external_value(PARAM_TEXT, 'Number of courses', VALUE_OPTIONAL),
                'message' => new external_value(PARAM_TEXT, 'success message'),
                'reason' => new external_value(PARAM_TEXT, 'Reason if error message', VALUE_OPTIONAL),
                'result' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseskills' =>new external_value(PARAM_TEXT, 'Course skills', VALUE_OPTIONAL),
                            'coursecode' => new external_value(PARAM_TEXT, 'course shortname', VALUE_OPTIONAL),
                            'coursename' => new external_value(PARAM_TEXT, 'course fullname', VALUE_OPTIONAL),
                            'description' => new external_value(PARAM_TEXT, 'course description', VALUE_OPTIONAL),
                            'coursetype' => new external_value(PARAM_TEXT, 'course type', VALUE_OPTIONAL),
                            'courseurl' => new external_value(PARAM_TEXT, 'course url', VALUE_OPTIONAL)
                        )
                    )
                )
            )
       
        );
    }
    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function get_courses_parameters(){
        return new external_function_parameters(
            array()
        );
    }
    public static function get_courses(){
        global $DB;
        $selectsql = "SELECT c.id, c.fullname, c.shortname, c.category,c.open_skill,cc.name categoryname FROM {course} c
        JOIN {course_categories} cc ON cc.id = c.category
        WHERE c.visible = 1";
        $courses = $DB->get_records_sql($selectsql,array());
        //LEFT JOIN {local_skill} sk ON FIND_IN_SET(sk.id, c.open_skill)
       
       
        if(count($courses)>0){
            foreach($courses as $course){
                $courseid = strip_tags(trim($course->id));
                $coursename = strip_tags(trim($course->fullname));
                $courseshortname = strip_tags(trim($course->shortname));
                $categoryname = strip_tags(trim($course->categoryname));
                $sql = "SELECT id,name FROM {local_skill} WHERE FIND_IN_SET(id, '".$course->open_skill."')";
                $skills = $DB->get_records_sql($sql);
                $skillsdata = '';
                foreach ($skills as $skill) {
                    if ($skillsdata == '') {
                        $skillsdata = $skill->name;
                    } else {
                        $skillsdata .= ','.$skill->name;
                    }
                }
                $image = course_thumbimage($course);                
                $courseslist[] = ['courseid' =>$courseid, 
                'coursename' => $coursename,
                'courseshortname'=>$courseshortname,
                'categoryname' => $categoryname,
                'skills' => $skillsdata,
                'courseimage' => $image
               ]; 
            }          
        }
        $result = [
            'result' => $courseslist
        ];  
      
        return $result;
    }
    public static function get_courses_returns()
    {
        return new external_single_structure(
            array(
               'result' => new external_multiple_structure(
                 new external_single_structure(
                     array(
                        'courseid' =>new external_value(PARAM_INT, 'Course id',VALUE_REQUIRED),
                        'coursename' => new external_value(PARAM_RAW, 'Course Name'),
                        'courseshortname' => new external_value(PARAM_RAW, 'Course short name',VALUE_REQUIRED),
                        'categoryname' => new external_value(PARAM_RAW, 'Category Name'),
                        'skills' => new external_value(PARAM_RAW, 'Skills Name'),
                        'courseimage' => new external_value(PARAM_URL, 'Course image'),
                     )
                 )
             )
          )
       );        
    }
    /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function course_enrolment_parameters(){
        return new external_function_parameters(
            array('courseid' =>new external_value(PARAM_INT, 'Course id',VALUE_REQUIRED),
            'employeeid' => new external_value(PARAM_RAW, 'Employee id'),
            )
        );
    }
    public static function course_enrolment($courseid, $employeeid){
        global $DB;
        $status = false;
        $message = '';
        $params = self::validate_parameters(
            self::course_enrolment_parameters(),
            [
                'courseid' => $courseid,
                'employeeid' => $employeeid,
            ]
        );
        $plugin = enrol_get_plugin('manual');
        $userid = $DB->get_field('user', 'id', ['open_employeeid' => $employeeid]);
        if ($userid) {
            $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));
            $course = $DB->get_record('course', array('id'=> $courseid));
            if ($course) {
                $sql = "SELECT e.id FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE e.courseid = :courseid and ue.userid = :userid";
                $userenrol = $DB->get_field_sql($sql,['courseid' => $courseid, 'userid' => $userid]);
                if (!$userenrol) {
                    if (empty($instance)) {
                        // Only add an enrol instance to the course if non-existent
                        $enrolid = $plugin->add_instance($course);
                        $instance = $DB->get_record('enrol', array('id' => $enrolid));
                    }
                    $timestart=$DB->get_field('course','startdate',array('id'=>$course->id));
                    $timeend=0;
                   
                    $plugin->enrol_user($instance, $userid,$instance->roleid,$timestart,$timeend);       
                    $notification = new \local_courses\notification();
                    $user = core_user::get_user($userid);
                    $type = 'course_enrol';
                    $notificationdata = $notification->get_existing_notification($course, $type);
                    
                    if ($notificationdata) {
                        $notification->send_course_email($course, $user, $type, $notificationdata);
                    }                   
                    $status = true;
                    $message = 'User enrolled';
                } else {
                    $status = false;
                    $message = 'User already enrolled to the course.';
                }                
            } else {
                $message = 'Invalid Course id';
            }            
        } else {
            $message = 'Incorrect Employee id';
        }
        return [
            'status' => $status,
            'message' => $message
       ];
    }
    public static function course_enrolment_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'message' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL)
            )
        );
    }
    public static function quiz_information_parameters(){
        return new external_function_parameters(
            array('courseid' =>new external_value(PARAM_RAW, 'Course id'))
        );
    }
    public static function quiz_information($courseid){
        global $DB;
        $params = self::validate_parameters(self::quiz_information_parameters(), ['courseid'=>$courseid]);
        $selectsql = "SELECT q.id, q.name, q.grade, gi.grademax, gi.grademin, gi.gradepass FROM `mdl_course_modules` cm
        JOIN mdl_course c ON c.id = cm.course
        JOIN `mdl_quiz` q ON q.course = c.id AND q.id =cm.instance
        JOIN `mdl_grade_items` gi ON gi.iteminstance = q.id WHERE c.id = :courseid AND gi.itemmodule = 'quiz'";
              
        $quizes = $DB->get_records_sql($selectsql,['courseid' => $courseid, 'course' => $courseid]);
        
        if(count($quizes)>0){
            foreach($quizes as $quiz){
                $quizid = strip_tags(trim($quiz->id));
                $quizname = strip_tags(trim($quiz->name));
                $quizgrade = strip_tags(trim($quiz->grade));
                $grademax = strip_tags(trim($quiz->grademax));
                $grademin = strip_tags(trim($quiz->grademin));
                $gradepass =strip_tags(trim($quiz->gradepass));

                $quizlist[] = ['quizid' =>$quizid, 
                'quizname' => $quizname,
                'quizgrade'=> $quizgrade,
                'grademax' => $grademax ,
                'grademin' => $grademin,
                'gradepass' => $gradepass,
               ]; 
            }          
        }else {
                  throw new moodle_exception('cannotgetcoursecontents', 'webservice', '', null,
                                            get_string('cannotgetcoursecontents', 'webservice'));

        }
        $result = [
            'result' => $quizlist
        ];  
      
        return $result;
    }

    public static function quiz_information_returns(){
        return new external_single_structure(
            array(
               'result' => new external_multiple_structure(
                 new external_single_structure(
                     array(
                        'quizid' =>new external_value(PARAM_INT, 'Quiz id',VALUE_REQUIRED),
                        'quizname' => new external_value(PARAM_RAW, 'Quiz Name'),
                        'quizgrade' => new external_value(PARAM_RAW, 'Quiz grade',VALUE_REQUIRED),
                        'grademax' =>new external_value(PARAM_RAW, 'Quiz max grade',VALUE_REQUIRED),
                        'grademin' => new external_value(PARAM_RAW, 'Quiz min grade'),
                        'gradepass' => new external_value(PARAM_RAW, 'Quiz pass grade',VALUE_REQUIRED),                        
                    )
                 )
             )
          )
       );        
    }
       /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function course_unenrolment_parameters(){
        return new external_function_parameters(
            array('courseid' =>new external_value(PARAM_INT, 'Course id',VALUE_REQUIRED),
            'employeeid' => new external_value(PARAM_RAW, 'Employee id'),
            )
        );
    }
    public static function course_unenrolment($courseid, $employeeid){
        global $DB;
        $status = false;
        $message = '';
        $params = self::validate_parameters(
            self::course_unenrolment_parameters(),
            [
                'courseid' => $courseid,
                'employeeid' => $employeeid,
            ]
        );
        $plugin = enrol_get_plugin('manual');
        $userid = $DB->get_field('user', 'id', ['open_employeeid' => $employeeid]);
        if ($userid) {
            $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'));
            $course = $DB->get_record('course', array('id'=> $courseid));
            if ($course) {               
                $plugin->unenrol_user($instance, $userid);        
                \core\notification::success(get_string('youunenrolledfromcourse', 'enrol', $course->fullname));

                $status = true;
                $message = 'User unenrolled';
            } else {
                $message = 'Invalid Course id';
            }            
        } else {
            $message = 'Incorrect Employee id';
        }
        return [
            'status' => $status,
            'message' => $message
       ];
    }
    public static function course_unenrolment_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'message' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL)
            )
        );
    }
    public static function custom_course_complete_parameters(){
        return new external_function_parameters(
            array('courseid' =>new external_value(PARAM_INT, 'Course id',VALUE_REQUIRED),
            'employeeid' => new external_value(PARAM_RAW, 'Employee id'),
            )
        );
    }
    public static function custom_course_complete($courseid, $employeeid){
        global $DB;
        $status = false;
        $message = '';
        $params = self::validate_parameters(
            self::custom_course_complete_parameters(),
            [
                'courseid' => $courseid,
                'employeeid' => $employeeid,
            ]
        );
        $plugin = enrol_get_plugin('manual');
        $userid = $DB->get_field('user', 'id', ['open_employeeid' => $employeeid]);
        if ($userid) {
            $course = $DB->get_record('course', array('id'=> $courseid));
            if(!empty($course) && !empty($userid) ) {
                $criterion = new \completion_criteria_role();
                               
                // $data=new \stdClass();
                // $data->criteria_role = array('3'=> 3);
                // $data->id = $courseid;
                // $criterion->update_config($data);            


                $completionsql="SELECT id from {course_completions} where userid={$userid} AND course={$course->id} and timecompleted is not NULL";
                $completionexist= $DB->get_field_sql($completionsql);
                
                if(empty($completionexist)){
                    $plugin = enrol_get_plugin('manual');
                    $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
                    
                    // echo "Below one is for Instance";                     
                    $roleid = 5;
                    if (empty($instance)) {
                        $enrolid = $plugin->add_instance($course);
                        $instance = $DB->get_record('enrol', array('id' => $enrolid));
                    }

                    if($userid && $instance){
                        if (!$enrol_manual = enrol_get_plugin('self')) {
                            $enrol_manual->enrol_user($instance, $userid, $roleid, 0,0);
                        }                        
                    } 
                $contextid= $DB->get_field('context','id',array('instanceid'=>$course->id,'contextlevel'=>50));

                $DB->set_field("user_enrolments", "timecreated",time(), array("enrolid" => $instance->id,'userid'=>$userid));
                $DB->set_field("role_assignments", "timemodified",time(), array("contextid" => $contextid,'userid'=>$userid));

                $ccompletion = new \completion_completion(array('course' => $course->id, 'userid' => $userid));
                                            // Mark course as complete and get triggered event.
                $ccompletion->mark_complete();
                $DB->set_field("course_completions", "timecompleted", time(), array("course" => $course->id,'userid'=>$userid));


            } else {
               // echo "Completion not exist, about to set the enrolment and update date";
                $instance = $DB->get_record('enrol', array('courseid' => $course->id,'enrol' =>'self'));
                $contextid= $DB->get_field('context','id',array('instanceid'=>$course->id,'contextlevel'=>50));
                $DB->set_field("user_enrolments", "timecreated", time(), array("enrolid" => $instance->id,'userid'=>$userid));
                $DB->set_field("role_assignments", "timemodified", time(), array("contextid" => $contextid,'userid'=>$userid));
                $DB->set_field("course_completions", "timecompleted", time(), array("course" => $course->id,'userid'=>$userid));

            }

        }            
            $status = true;
            $message = 'Course Completed';        
        } else {
            $message = 'Incorrect Employee id';
        }
        return [
            'status' => $status,
            'message' => $message
       ];
    }
    public static function custom_course_complete_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'message' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL)
            )
        );
    }

}

