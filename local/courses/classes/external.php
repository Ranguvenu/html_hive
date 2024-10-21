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
 * @package    local_courses
 * @category   external
 * @copyright  eAbyas <www.eabyas.in>
 */

defined('MOODLE_INTERNAL') || die;

use \local_courses\form\custom_course_form as custom_course_form;
use \local_courses\action\insert as insert;
use \local_courses\form\level_form as level_form;
use tool_certificate\customfield\issue_handler;



require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/local/courses/lib.php');

class local_courses_external extends external_api {

     /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_course_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the course'),
                'form_status' => new external_value(PARAM_INT, 'Form position', 0),
                'id' => new external_value(PARAM_INT, 'Course id', 0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create course form.
     *
     * @param int $contextid The context id for the course.
     * @param int $form_status form position.
     * @param int $id course id -1 as default.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new course id.
     */
    public static function submit_create_course_form($contextid, $form_status, $id, $jsonformdata) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_course_form_parameters(),
                                            ['contextid' => $contextid, 'form_status'=>$form_status,  'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        if ($id) {
            $course = get_course($id);
            $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
        }else{
            $course = null;
        }

        //$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);
        $overviewfilesoptions = course_overviewfiles_options($course);
        if (!empty($course)) {
            // Add context for editor.
                $editoroptions['context'] = $coursecontext;
                $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
                $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
                if ($overviewfilesoptions) {
                    file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
                }
            $get_coursedetails=$DB->get_record('course',array('id'=>$course->id));
        } else {
          // Editor should respect category context if course context is not set.
          $editoroptions['context'] = $catcontext;
          $editoroptions['subdirs'] = 0;
          $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
          if ($overviewfilesoptions) {
            file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
          }
        }

        // The last param is the ajax submitted data.
        $mform = new local_courses\form\custom_course_form(null, array('form_status' => $form_status,'courseid'=>$data['id']), 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        // print_r('_');
        //print_r($validateddata);
        if ($validateddata) {
            $formheaders = array_keys($mform->formstatus);
            $category_id=$data['category'];
            $open_departmentid=(int)$data['open_departmentid'];
            $open_subdepartment=(int)$data['open_subdepartment'];
            if ($validateddata->id <= 0) {
               
              if (in_array("-1", $validateddata->open_grade)){
                $validateddata->open_grade = "-1";
              }else {
                $validateddata->open_grade=implode(',',$validateddata->open_grade);
              }
              if (in_array("-1", $validateddata->open_ouname)){
                $validateddata->open_ouname = "-1";
              }else {
               $validateddata->open_ouname = implode(',',$validateddata->open_ouname);
              }
              if (in_array("All", $validateddata->open_careertrack)){
                  $validateddata->open_careertrack = "All";
                }else {
                    $validateddata->open_careertrack=implode(',',$validateddata->open_careertrack);
                }
                $validateddata->category = $category_id;
                $validateddata->open_departmentid = $open_departmentid;
                $validateddata->open_subdepartment = $open_subdepartment;
                if($validateddata->open_prerequisites){
                    $validateddata->open_prerequisites=implode(',', $validateddata->open_prerequisites);
                }
               
                $hours = $validateddata->hours * 3600 ;
                $minutes = $validateddata->min * 60;
                $validateddata->duration = $hours + $minutes;  
               

              $courseid = create_course($validateddata, $editoroptions);
              // postcourse::local_postcourse($courseid->id);


                // Update course tags.
               /* if (isset($validateddata->tags)) {
                    $coursecontext = context_course::instance($courseid->id, MUST_EXIST);
                    local_tags_tag::set_item_tags('local_courses', 'courses', $courseid->id, $coursecontext, $validateddata->tags, 0, $data['open_costcenterid'], $validateddata->open_departmentid );
                }*/
                // if(class_exists('\block_trending_modules\lib')){
                //     $trendingclass = new \block_trending_modules\lib();
                //     if(method_exists($trendingclass, 'trending_modules_crud')){
                //       $trendingclass->trending_modules_crud($courseid->id, 'local_courses');
                //     }
                // }
                $coursedata = $courseid;
                $enrol_status = $validateddata->selfenrol;
                // print_r($coursedata);
                // exit;
                insert::add_enrol_method_tocourse($coursedata,$enrol_status);

                $auto_enrol_status = $validateddata->autoenrol;
                insert::add_enrol_method_tocourse($coursedata,$auto_enrol_status,'auto');

               
            } elseif($validateddata->id > 0) { 
                if($form_status == 0){             
                    if (in_array("-1", $validateddata->open_grade)){
                        $validateddata->open_grade = "-1";
                    } else {
                        $validateddata->open_grade=implode(',',$validateddata->open_grade);
                    }

                    if (in_array("-1", $validateddata->open_ouname)){
                        $validateddata->open_ouname = "-1";
                    } else {
                        $validateddata->open_ouname=implode(',',$validateddata->open_ouname);
                    }
                    
                    if (in_array("All", $validateddata->open_careertrack)){
                        $validateddata->open_careertrack = "All";
                    }else {
                        $validateddata->open_careertrack=implode(',',$validateddata->open_careertrack);
                    }
                }
              if($form_status == 0){
                $courseid =new stdClass();
                $courseid->id=$data['id'];
                $validateddata->category = $category_id;
                $validateddata->open_departmentid = ($open_departmentid!='null') ? $open_departmentid : 0 ;
                $validateddata->open_subdepartment = ($open_subdepartment != 'null') ? $open_subdepartment : 0;
                 if($validateddata->open_prerequisites){
                  $validateddata->open_prerequisites=implode(',', $validateddata->open_prerequisites);
                }
                 $hours = $validateddata->hours * 3600 ;
                $minutes = $validateddata->min * 60;
                $validateddata->duration = $hours + $minutes;    
                update_course($validateddata, $editoroptions);
                // if(class_exists('\block_trending_modules\lib')){
                //   $trendingclass = new \block_trending_modules\lib();
                //   if(method_exists($trendingclass, 'trending_modules_crud')){
                //     $trendingclass->trending_modules_crud($courseid->id, 'local_courses');
                //   }
                // }

                if($validateddata->map_certificate == 1){
                    $validateddata->open_certificateid = $validateddata->open_certificateid;
                }else{
                    $validateddata->open_certificateid = null;
                }
                 // Update course tags.
                /* if (isset($validateddata->tags)) {
                    $coursecontext = context_course::instance($courseid->id, MUST_EXIST);
                    local_tags_tag::set_item_tags('local_courses', 'courses', $courseid->id, $coursecontext, $validateddata->tags, 0, $validateddata->open_departmentid);
                }*/
                $coursedata = $DB->get_record('course',array('id' => $data['id']));
                insert::add_enrol_method_tocourse($coursedata, $coursedata->selfenrol);

                $auto_enrol_status = $validateddata->autoenrol;
                insert::add_enrol_method_tocourse($coursedata,$coursedata->autoenrol,'auto');


              }else{
                
                $data=(object)$data;
                //$data->open_points=$validateddata->open_points;
                //$data->startdate=$validateddata->startdate;
               // $data->enddate=$validateddata->enddate;
                $data->open_cost = $validateddata->open_cost;
                      
               
                $courseid =new stdClass();
                $courseid->id=$data->id;
                $data->open_skill = !empty($data->open_skill) ? implode(',',$data->open_skill) : null;
                $DB->update_record('course', $data);
               
                $event = \core\event\course_updated::create(array(
                    'objectid' => $data->id,
                    'context' => context_course::instance($data->id),
                    'other' => array('shortname' => $data->shortname,
                                     'fullname' => $data->fullname,
                                     'updatedfields' => $data)
                ));

                $event->trigger();
              }
            }
            $next = $form_status + 1;
            $nextform = array_key_exists($next, $formheaders);
            if ($nextform !== false) {
                $form_status = $next;
                $error = false;
            } else {
                $form_status = -1;
                $error = true;
            }
            $enrolid = $DB->get_field('enrol', 'id' ,array('courseid' => $courseid->id ,'enrol' => 'manual'));
            $existing_method = $DB->get_record('enrol',array('courseid'=> $courseid->id  ,'enrol' => 'self'));
            $courseenrolid = $DB->get_field('course','selfenrol',array('id'=> $courseid->id));
            if($courseenrolid == 1){
                $existing_method->status = 0;
                $existing_method->customint6 = 1;
            }else{
                $existing_method->status = 1;
            }
            $DB->update_record('enrol', $existing_method);

        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
        $return = array(
            'courseid' => $courseid->id,
            'enrolid' => $enrolid,
            'form_status' => $form_status);

        return $return;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_course_form_returns() {
       return new external_single_structure(array(
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'enrolid' => new external_value(PARAM_INT, 'manual enrol id for the course'),
            'form_status' => new external_value(PARAM_INT, 'form_status'),
        ));
    }

         /**
     * Describes the parameters for submit_create_course_form webservice.
     * @return external_function_parameters
     */
    public static function submit_create_category_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the category'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the create category form.
     *
     * @param int $contextid The context id for the category.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new category id.
     */
    public static function submit_create_category_form($contextid, $jsonformdata) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        //require_once($CFG->libdir.'/coursecatlib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_course_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        $id = $data['id'];
        if ($id) {
            $coursecat = core_course_category::get($id, MUST_EXIST, true);
        }

        // The last param is the ajax submitted data.
        $mform = new local_courses\form\coursecategory_form(null, array(), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
        if ($validateddata) {
            if ($validateddata->id > 0) {
                if ((int)$validateddata->parent !== (int)$coursecat->parent && !$coursecat->can_change_parent($validateddata->parent)) {
                    print_error('cannotmovecategory');
                }
                $category = $coursecat->update($validateddata, $mform->get_description_editor_options());
            } else {
                $category = core_course_category::create($validateddata, $mform->get_description_editor_options());
            }

        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }

        return $category->id;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_create_category_form_returns() {
        return new external_value(PARAM_INT, 'category id');
    }

    /**
     * Describes the parameters for delete_category_form webservice.
     * @return external_function_parameters
     */
    public static function submit_delete_category_form_parameters() {
        return new external_function_parameters(
            array(
                //'evalid' => new external_value(PARAM_INT, 'The evaluation id '),
                'contextid' => new external_value(PARAM_INT, 'The context id for the category'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create category form, encoded as a json array'),
                'categoryid' => new external_value(PARAM_INT, 'The category id for the category')
            )
        );
    }

    /**
     * Submit the delete category form.
     *
     * @param int $contextid The context id for the category.
     * @param int $categoryid The id for the category.
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @return int new category id.
     */
    public static function submit_delete_category_form($contextid, $jsonformdata, $categoryid) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->libdir.'/coursecatlib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_create_course_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);

        $data = array();
        parse_str($serialiseddata, $data);

        $warnings = array();
        if ($categoryid) {
            $category = coursecat::get($categoryid);
            $context = context_coursecat::instance($category->id);
        }else {
            $category = coursecat::get_default();
            $categoryid = $category->id;
            $context = context_coursecat::instance($category->id);
        }

        // The last param is the ajax submitted data.
        $mform = new local_courses\form\deletecategory_form(null, $category, 'post', '', null, true, $data);
        $validateddata = $mform->get_data();
        if ($validateddata) {
            // The form has been submit handle it.
                if ($validateddata->fulldelete == 1 && $category->can_delete_full()) {
                    $continueurl = new moodle_url('/local/courses/index.php');
                    if ($category->parent != '0') {
                        $continueurl->param('categoryid', $category->parent);
                    }
                    $deletedcourses = $category->delete_full(false);
                } else if ($validateddata->fulldelete == 0 && $category->can_move_content_to($validateddata->newparent)) {
                    $deletedcourses = $category->delete_move($validateddata->newparent, false);
                } else {
                    // Some error in parameters (user is cheating?)
                    $mform->display();
                }

        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }

            return true;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_delete_category_form_returns() {
        return new external_value(PARAM_INT, '');
    }

          /**
     * Describes the parameters for departmentlist webservice.
     * @return external_function_parameters
     */
    public static function departmentlist_parameters() {
        return new external_function_parameters(
            array(
                'orgid' => new external_value(PARAM_INT, 'The id for the costcenter / organization'),
                'depid' => new external_value(PARAM_INT, 'The id for the department'),
                'flag' => new external_value(PARAM_INT, 'falg'),
            )
        );
    }

    /**
     * departments list
     *
     * @param int $orgid id for the organization.
     * @param int $depid id for the organization.
     * @param int $flag id for the organization.
     * @return array
     */
    public static function departmentlist($orgid, $depid, $flag) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/local/courses/lib.php');
        if (!empty($depid) && $flag) {
            $sql  = "SELECT category FROM {local_costcenter} WHERE  id = ?";
            $costcentercategory = $DB->get_field_sql($sql, array($depid));
            if ($costcentercategory)
               $allcategories = $DB->get_records_sql_menu("SELECT id,name from {course_categories} where (path like '%/$costcentercategory/%' OR id =$costcentercategory) AND visible=1");
           $departmentlist = array();
           $levelslist = array();
        } else if (!empty($orgid)) {
            $sql  = "SELECT id,fullname FROM {local_costcenter} WHERE parentid IN ($orgid) ORDER BY id DESC";
            $departmentlist = $DB->get_records_sql_menu($sql);

            $sql  = "SELECT id,name FROM {local_course_levels} WHERE costcenterid = $orgid ORDER BY sortorder ASC";
            $levelslist = $DB->get_records_sql_menu($sql);

            $categorylib = new local_courses\catslib();
            $systemcontext = context_system::instance();
            if(is_siteadmin() OR has_capability('local/costcenter:manage_ownorganizations',$systemcontext)){
                $orgcategories = $categorylib->get_categories($orgid);
                $orgcategoryids = implode(',',$orgcategories);
                $sql = "SELECT c.id,c.name FROM {course_categories} as c WHERE c.visible = 1 AND c.id IN ($orgcategoryids)";
            } else if(has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
                $orgcategories = $categorylib->get_categories($USER->open_costcenterid);
                $orgcategoryids = implode(',',$orgcategories);
                $sql = "SELECT c.id,c.name FROM {course_categories} as c WHERE c.visible = 1 AND c.id IN ($orgcategoryids)";
            } elseif(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
                $deptcategories = $categorylib->get_categories($USER->open_departmentid);
                $deptcategoryids = implode(',',$deptcategories);
                $sql = "SELECT c.id,c.name FROM {course_categories} as c WHERE c.visible = 1 AND c.id IN ($deptcategoryids)";
            }
            $sql .= " ORDER BY c.id DESC";
            $allcategories = $DB->get_records_sql_menu($sql);

        } else if($flag){
            $parentcategory = $DB->get_field('local_costcenter','category',array('id'=>$USER->open_costcenterid));
            if(is_siteadmin())
                $allcategories = $DB->get_records_sql_menu("select id,name from {course_categories} where visible=1");
            else
                $allcategories = $DB->get_records_sql_menu("select id,name from {course_categories}
                where (path like '/$parentcategory/%' or path like '%/$parentcategory' or path like '%/$parentcategory/%')
                AND visible=1");
            $departmentlist = array();
            $levelslist = array();
        }
        $return = array(
            'departments' => json_encode($departmentlist),
            'categories' => json_encode($allcategories),
            'levels' => json_encode($levelslist)
            );
        return $return;
    }

  /**
   * Returns description of method result value
   *
   * @return external_description
   */
  public static function departmentlist_returns() {
    return new external_function_parameters(
      array(
        'departments' => new external_value(PARAM_RAW, 'Department and categorylist '),
        'categories' => new external_value(PARAM_RAW, 'Department and categorylist '),
        'levels' => new external_value(PARAM_RAW, 'LevelL and categorylist ')
      )
    );
  }

  /** Describes the parameters for delete_course webservice.
   * @return external_function_parameters
  */
  public static function delete_course_parameters() {
    return new external_function_parameters(
      array(
        'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
        'id' => new external_value(PARAM_INT, 'ID of the record', 0),
        'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
        'name' => new external_value(PARAM_RAW, 'name', false),
      )
    );
  }

  /**
   * Deletes course
   *
   * @param int $action
   * @param int $confirm
   * @param int $id course id
   * @param string $name
   * @return int new course id.
   */
  public static function delete_course($action, $id, $confirm, $name) {
    global $DB;
    try {
        if ($confirm) {
            
            $corcat = $DB->get_field('course','category',array('id' => $id));
         
            $category = $DB->get_record('course_categories',array('id'=>$corcat));
           
            delete_course($id,false);
            // if(class_exists('\block_trending_modules\lib')){
            //     $trendingclass = new \block_trending_modules\lib();
            //     if(method_exists($trendingclass, 'trending_modules_crud')){
            //         $course_object = new stdClass();
            //         $course_object->id = $id;
            //         $course_object->module_type = 'local_courses';
            //         $course_object->delete_record = True;
            //         $trendingclass->trending_modules_crud($course_object, 'local_courses');
            //     }
            // }
            $category->coursecount = $category->coursecount-1;
            $DB->update_record('course_categories',$category);
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

  /**
   * Returns description of method result value
   * @return external_description
   */

  public static function delete_course_returns() {
      return new external_value(PARAM_BOOL, 'return');
  }

  /* Describes the parameters for global_filters_form_option_selector webservice.
  * @return external_function_parameters
  */
  public static function global_filters_form_option_selector_parameters() {
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
    $searchanywhere = new external_value(
        PARAM_BOOL,
        'find a match anywhere, or only at the beginning'
    );
    $page = new external_value(
        PARAM_INT,
        'Page number'
    );
    $perpage = new external_value(
        PARAM_INT,
        'Number per page'
    );
    return new external_function_parameters(array(
      'query' => $query,
      'action' => $action,
      'options' => $options,
      'searchanywhere' => $searchanywhere,
      'page' => $page,
      'perpage' => $perpage,
    ));
  }

  /**
   * Creates filter elements
   *
   * @param string $query
   * @param int $action
   * @param array $options
   * @param string $searchanywhere
   * @param int $page
   * @param int $perpage
   * @param string $jsonformdata The data from the form, encoded as a json array.
   * @return string filter form element
  */
  public static function global_filters_form_option_selector($query, $action, $options, $searchanywhere, $page, $perpage) {
    global $CFG, $DB, $USER;
    $params = self::validate_parameters(self::global_filters_form_option_selector_parameters(), array(
        'query' => $query,
        'action' => $action,
        'options' => $options,
        'searchanywhere' => $searchanywhere,
        'page' => $page,
        'perpage' => $perpage
    ));
    $query = $params['query'];
    $action = $params['action'];
    $options = $params['options'];
    $searchanywhere=$params['searchanywhere'];
    $page=$params['page'];
    $perpage=$params['perpage'];

    if (!empty($options)) {
        $formoptions = json_decode($options);
    }
    if ($action) {
      $return = array();
      
      if($action === 'categories' || $action === 'elearning' || $action === 'featuredcourses' || $action === 'featuredlpaths'){
          $filter = 'courses';
      } else if($action === 'email' || $action === 'employeeid' || $action === 'username' || $action === 'users'){
          $filter = 'users';
      } else if($action === 'organizations' || $action === 'departments' || $action === 'subdepartment'){
          $filter = 'costcenter';
      } else{
          $filter = $action;
      }
      $core_component = new core_component();
      $courses_plugin_exist = $core_component::get_plugin_directory('local', $filter);
      if ($courses_plugin_exist) {
          require_once($CFG->dirroot . '/local/' . $filter . '/lib.php');
          $functionname = $action.'_filter';
          if($action === 'featuredcourses'){
           $return=$functionname($query, $action, $options, $searchanywhere, $page, $perpage);
          }else if($action === 'featuredlpaths'){
            $return=$functionname($query, $action, $options, $searchanywhere, $page, $perpage);
          }else 
            $return=$functionname('',$query,$searchanywhere, $page, $perpage);
        }
     
      return json_encode($return);
    }
  }

  /**
   * Returns description of method result value
   *
   * @return external_description
   */
  public static function global_filters_form_option_selector_returns() {
      return new external_value(PARAM_RAW, 'data');
  }


  /** Describes the parameters for delete_course webservice.
   * @return external_function_parameters
  */
  public static function courses_view_parameters() {
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
  public static function courses_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
    global $DB, $PAGE;
    require_login();
    $PAGE->set_url('/local/courses/courses.php', array());
    $PAGE->set_context($contextid);
    // Parameter validation.
    $params = self::validate_parameters(
        self::courses_view_parameters(),
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
    $data = get_listof_courses($stable, $filtervalues);
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

  public static function courses_view_returns() {
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
                                  'points' => new external_value(PARAM_RAW, 'points', VALUE_OPTIONAL),
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
                      'request_view' => new external_value(PARAM_INT, 'request_view', VALUE_OPTIONAL),
                      'report_view' => new external_value(PARAM_INT, 'report_view', VALUE_OPTIONAL),
                      'grade_view' => new external_value(PARAM_INT, 'grade_view', VALUE_OPTIONAL),
                      'delete' => new external_value(PARAM_INT, 'delete', VALUE_OPTIONAL),
                      'update' => new external_value(PARAM_INT, 'update', VALUE_OPTIONAL),
                      'enrol' => new external_value(PARAM_INT, 'enrol', VALUE_OPTIONAL),
                      'actions' => new external_value(PARAM_INT, 'actions', VALUE_OPTIONAL),
                      'nocourses' => new external_value(PARAM_BOOL, 'nocourses', VALUE_OPTIONAL),
                      'totalcourses' => new external_value(PARAM_INT, 'totalcourses', VALUE_OPTIONAL),
                      'length' => new external_value(PARAM_INT, 'length', VALUE_OPTIONAL),
                  ), 'records', VALUE_OPTIONAL
              )

      ]);
  }

  /** Describes the parameters for delete_course webservice.
   * @return external_function_parameters
  */
  public static function categories_view_parameters() {
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
   * lists all categories
   *
   * @param array $options
   * @param array $dataoptions
   * @param int $offset
   * @param int $limit
   * @param int $contextid
   * @param array $filterdata
   * @return array categories list.
  */
  public static function categories_view($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata) {
    global $DB, $PAGE;
    require_login();
    $PAGE->set_url('/local/courses/index.php', array());
    $PAGE->set_context($contextid);
    // Parameter validation.
    $params = self::validate_parameters(
      self::categories_view_parameters(),
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
    $filtervalues->parentid = $filteroptions->parentid;
    $stable = new \stdClass();
    $stable->thead = false;
    $stable->start = $offset;
    $stable->length = $limit;
    $records = get_listof_categories($stable, $filtervalues);
    $totalcount = $records['totalrecords'];
    $data = $records['records'];
    return [
        'totalcount' => $totalcount,
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
  public static function categories_view_returns() {
    return new external_single_structure([
      'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
      'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
      'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
      'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
      'records' => new external_multiple_structure(
          new external_single_structure(
              array(
              'parentname_str' => new external_value(PARAM_RAW, 'parentname_str'),
              'categoryname_str' => new external_value(PARAM_RAW, 'categoryname_str'),
              'categoryidnumber_idnumber' => new external_value(PARAM_RAW, 'categoryidnumber_idnumber'),
              'categoryidnumber' => new external_value(PARAM_RAW, 'categoryidnumber'),
              'result' => new external_value(PARAM_RAW, 'result'),
              'catcount' => new external_value(PARAM_RAW, 'catcount'),
              //'categoryname' => new external_value(PARAM_RAW, 'categoryname'),
              'catlisticon' => new external_value(PARAM_RAW, 'catlisticon'),
              'catgoryid' => new external_value(PARAM_INT, 'catgoryid'),
              'actions' => new external_value(PARAM_RAW, 'actions'),
              'contextid' => new external_value(PARAM_INT, 'contextid'),
              'show' => new external_value(PARAM_BOOL, 'show'),
              'showsubcategory' => new external_value(PARAM_BOOL, 'showsubcategory'),
              'visible_value' => new external_value(PARAM_INT, 'visible_value'),
              'delete_enable' => new external_value(PARAM_BOOL, 'visible_value'),
              'sesskey' => new external_value(PARAM_RAW, 'sesskey'),
              'unabletodelete_reason' => new external_value(PARAM_RAW, 'unabletodelete_reason', VALUE_OPTIONAL),
              )
          )
      )

    ]);
  }
    public static function get_users_course_status_information_parameters() {
        return new external_function_parameters(
            array('status' => new external_value(PARAM_RAW, 'status of course', true),
                'searchterm' => new external_value(PARAM_RAW, 'searchterm', VALUE_OPTIONAL, ''),
                'page' => new external_value(PARAM_INT, 'page', VALUE_OPTIONAL, 0),
                'perpage' => new external_value(PARAM_INT, 'perpage', VALUE_OPTIONAL, 10)
            )
        );
    }
    public static function get_users_course_status_information($status, $searchterm = "", $page = 0, $perpage = 10) {
        global $USER, $DB,$CFG;
        require_once($CFG->dirroot.'/local/ratings/lib.php');
        $result = array();
        if ($status == 'completed') {
            list($user_course_info,$total) = block_userdashboard\lib\elearning_courses::completed_coursenames($searchterm, true, $page, $perpage);
        } else if ($status == 'inprogress') {
            list($user_course_info, $total) = block_userdashboard\lib\elearning_courses::inprogress_coursenames($searchterm, true, $status, false, '', $page, $perpage);
        } else if($status == 'enrolled') {
            list($user_course_info, $total) = block_userdashboard\lib\elearning_courses::inprogress_coursenames($searchterm, true, $status, false, '', $page, $perpage);
        }
        foreach ($user_course_info as $userinfo) {
            $context = context_course::instance($userinfo->id, IGNORE_MISSING);
            list($userinfo->summary,$userinfo->summaryformat) =
                external_format_text($userinfo->summary ,$userinfo->summaryformat , $context->id, 'course', 'summary', null);
                $progress = null;
            // Return only private information if the user should be able to see it.
            if ($userinfo->enablecompletion) {
                $progress = \core_completion\progress::get_course_progress_percentage($userinfo, $userid);
            }
            $modulerating = $DB->get_field('local_ratings_likes', 'module_rating', array('module_id' => $userinfo->id, 'module_area' => 'local_courses'));
            if(!$modulerating){
                 $modulerating = 0;
            }
            $likes = $DB->count_records('local_like', array('likearea'=> 'local_courses', 'itemid'=>$userinfo->id, 'likestatus'=>'1'));
            $dislikes = $DB->count_records('local_like', array('likearea'=> 'local_courses', 'itemid'=>$userinfo->id, 'likestatus'=>'2'));
            $avgratings = get_rating($userinfo->id, 'local_courses');
            $avgrating = $avgratings->avg;
            $ratingusers = $avgratings->count;
            $result[] = array(
                'id' => $userinfo->id,
                'fullname' => $userinfo->fullname,
                'shortname' => $userinfo->shortname,
                'summary' => $userinfo->summary,
                'summaryformat' => $userinfo->summaryformat,
                'startdate' => $userinfo->startdate,
                'enddate' => $userinfo->enddate,
                'timecreated' => $userinfo->timecreated,
                'timemodified' => $userinfo->timemodified,
                'visible' => $userinfo->visible,
                'idnumber' => $userinfo->idnumber,
                'format' => $userinfo->format,
                'showgrades' => $userinfo->showgrades,
                'lang' => clean_param($userinfo->lang,PARAM_LANG),
                'enablecompletion' => $userinfo->enablecompletion,
                'category' => $userinfo->category,
                'progress' => $progress,
                'rating' => $modulerating,
                'avgrating' => $avgrating,
                'ratingusers' => $ratingusers,
                'likes' => $likes,
                'dislikes' => $dislikes
            );
        }
        if ($total > $perpage) {
            $maxPages = ceil($total/$perpage);
        } else {
            $maxPages = 1;
        }
        return array('modules' => $result, 'total' => $total);
    }
    public static function get_users_course_status_information_returns(){
        return new external_single_structure(
            array(
                'modules' => new external_multiple_structure(
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
                            'rating' => new external_value(PARAM_RAW, 'Course rating'),
                            'avgrating' => new external_value(PARAM_FLOAT, 'Course Avg rating'),
                            'ratingusers' => new external_value(PARAM_INT, 'Course rating users'),
                            'likes' => new external_value(PARAM_INT, 'Course Likes'),
                            'dislikes' => new external_value(PARAM_INT, 'Course Dislikes'),
                        )
                    )
                ),
                'total' => new external_value(PARAM_INT, 'Total Pages')
            )
        );
    }
    public static function course_update_status_parameters(){
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for survey'),
                'id' => new external_value(PARAM_INT, 'The survey id for wellness'),
                'params' => new external_value(PARAM_RAW, 'optional parameter for default application'),
            )
        );
    }
    public static function course_update_status($contextid, $id, $params){
        global $DB;
        $params = self::validate_parameters(self::course_update_status_parameters(),
                                    ['contextid' => $contextid,'id' => $id, 'params' => $params]);
        $context = \context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($context);
        $course = $DB->get_record('course', array('id' => $id), 'id, visible');
        $course->visible = $course->visible ? 0 : 1;
        $course->timemodified = time();
        $return = $DB->update_record('course', $course);
        if(class_exists('\block_trending_modules\lib')){
            $dataobject = new stdClass();
            $dataobject->update_status = True;
            $dataobject->id = $id;
            $dataobject->module_type = 'local_courses';
            $dataobject->module_visible = $course->visible;
            $class = (new \block_trending_modules\lib())->trending_modules_crud($dataobject, 'local_courses');
        }
        return $return;
    }
    public static function course_update_status_returns(){
        return new external_value(PARAM_BOOL, 'Status');
    }
    public static function get_recently_enrolled_courses_parameters(){
        return new external_function_parameters(
            array(
            )
        );
    }
    public static function get_recently_enrolled_courses(){
        global $DB,$USER;
        $result = array();
        list($enrolledcourses, $enrolledcoursescount) = block_userdashboard\lib\elearning_courses::inprogress_coursenames('', true, 'enrolled', true,'recentlyaccessed');
        if(empty($enrolledcourses)){
            list($enrolledcourses, $enrolledcoursescount) = block_userdashboard\lib\elearning_courses::inprogress_coursenames('', true, 'inprogress', true);
            $header = 'Recently Enrolled Courses';
        }
        else {
            $header = 'Recently Accessed Courses';
        }
        foreach ($enrolledcourses as $userinfo) {

            $context = context_course::instance($userinfo->id, IGNORE_MISSING);
            list($userinfo->summary,$userinfo->summaryformat) =
                external_format_text($userinfo->summary ,$userinfo->summaryformat , $context->id, 'course', 'summary', null);
                $progress = null;
            // Return only private information if the user should be able to see it.
            if ($userinfo->enablecompletion) {
                $progress = \core_completion\progress::get_course_progress_percentage($userinfo, $userid);
            }
            $result[] = array(
                'id' => $userinfo->id,
                'fullname' => $userinfo->fullname,
                'shortname' => $userinfo->shortname,
                'summary' => $userinfo->summary,
                'summaryformat' => $userinfo->summaryformat,
                'startdate' => $userinfo->startdate,
                'enddate' => $userinfo->enddate,
                'timecreated' => $userinfo->timecreated,
                'timemodified' => $userinfo->timemodified,
                'visible' => $userinfo->visible,
                'idnumber' => $userinfo->idnumber,
                'format' => $userinfo->format,
                'showgrades' => $userinfo->showgrades,
                'lang' => clean_param($userinfo->lang,PARAM_LANG),
                'enablecompletion' => $userinfo->enablecompletion,
                'category' => $userinfo->category,
                'progress' => $progress,
            );
        }
        if(empty($result)){
                $header = 'Recently Enrolled Courses';
            }
       return array('mycourses' => $result,'heading' => $header);
    }
    public static function get_recently_enrolled_courses_returns(){
        return new external_single_structure(
            array(
                'mycourses' => new external_multiple_structure(
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
                            'progress' => new external_value(PARAM_FLOAT, 'Progress percentage')
                        )
                    )
                 ),
                'heading' => new external_value(PARAM_RAW, 'Heading')
            )
        );
    }
    public static function data_for_courses_parameters(){
        $filter = new external_value(PARAM_TEXT, 'Filter text');
        $filter_text = new external_value(PARAM_TEXT, 'Filter name',VALUE_OPTIONAL);
        $filter_offset = new external_value(PARAM_INT, 'Offset value',VALUE_OPTIONAL);
        $filter_limit = new external_value(PARAM_INT, 'Limit value',VALUE_OPTIONAL);
        $coursestype = new external_value(PARAM_TEXT, 'coursestype',VALUE_OPTIONAL);
        $params = array(
            'filter' => $filter,
            'filter_text' => $filter_text,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit,
            'coursestype' => $coursestype
        );
        return new external_function_parameters($params);
    }
    public static function data_for_courses($filter, $filter_text='', $filter_offset = 0, $filter_limit = 0, $coursestype = null){
        global $PAGE;

        $params = self::validate_parameters(self::data_for_courses_parameters(), array(
            'filter' => $filter,
            'filter_text' => $filter_text,
            'filter_offset' => $filter_offset,
            'filter_limit' => $filter_limit,
            'coursestype' => $coursestype
        ));

        $PAGE->set_context(\context_system::instance());
        $renderable = new local_courses\output\userdashboard($params['filter'], $params['filter_text'], $params['filter_offset'], $params['filter_limit'], $params['coursestype']);
        $output = $PAGE->get_renderer('local_courses');
        $data= $renderable->export_for_template($output, $params['coursestype']);
        return $data;

    }
    public static function data_for_courses_returns(){
        $return  = new external_single_structure(array(
            'total' => new external_value(PARAM_INT, 'Number of enrolled courses.', VALUE_OPTIONAL),           
            'inprogresscount'=>  new external_value(PARAM_INT, 'Number of inprogress course count.'),  
            'completedcount'=>  new external_value(PARAM_INT, 'Number of complete course count.'), 
            'courses_view_count'=>  new external_value(PARAM_INT, 'Number of courses count.'), 
            'enableslider'=>  new external_value(PARAM_INT, 'Flag for enable the slider.'),
            'inprogress_elearning_available'=>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
            'course_count_view'=>  new external_value(PARAM_TEXT, 'to add course count class'),
            'functionname' => new external_value(PARAM_TEXT, 'Function name'),
            'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
            'elearningtemplate' => new external_value(PARAM_INT, 'template name',VALUE_OPTIONAL),
            'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
            'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, true),
            'moduledetails' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        // 'inprogress_coursename' => new external_value(PARAM_RAW, 'Course name'),
                        'lastaccessdate' => new external_value(PARAM_RAW, 'Last access Time'),
                        'course_image_url' => new external_value(PARAM_RAW, 'Course Image'),
                        'coursesummary' => new external_value(PARAM_RAW, 'Course Summary'),
                        'progress' => new external_value(PARAM_RAW, 'Course Progress'),
                        'progress_bar_width' => new external_value(PARAM_RAW, 'Course Progress bar width'),
                        'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                        'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                        'course_url' => new external_value(PARAM_RAW, 'Course Url'),
                        'inprogress_coursename_fullname' => new external_value(PARAM_RAW, 'Course Url'),
                        'rating_element' => new external_value(PARAM_RAW, 'Ratings'),
                        'element_tags' => new external_value(PARAM_RAW, 'Course Tags'),
                        // 'indexClass' => new external_value(PARAM_TEXT, 'Index Card Class'),
                        'index' => new external_value(PARAM_INT, 'Index of Card'),
                       )
                    )
            ),
            'viewMoreCard' => new external_value(PARAM_BOOL, 'More info card to display', false),
            'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
            'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
            'index' => new external_value(PARAM_INT, 'number of courses count'),
            'filter_text' => new external_value(PARAM_TEXT, 'filtertext content',VALUE_OPTIONAL),
            'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
            'templatename' => new external_value(PARAM_TEXT, 'Templatename for tab content'),
            'pluginname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_courses'),
            'tabname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_courses'),
            'status' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_courses'),
         ));
        return $return;
    }
    public static function data_for_courses_paginated_parameters(){
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
    public static function data_for_courses_paginated($options, $dataoptions, $offset = 0, $limit = 0, $contextid, $filterdata){
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
        $coursetype = $decodedoptions['coursetype'];
        $renderable = new local_courses\output\userdashboard($filter, $filter_text, $filter_offset, $filter_limit, $coursetype);
        $output = $PAGE->get_renderer('local_courses');
        $data = $renderable->export_for_template($output, $coursetype);
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
    public static function data_for_courses_paginated_returns(){
        return new external_single_structure([
        'options' => new external_value(PARAM_RAW, 'The paging data for the service'),
        'dataoptions' => new external_value(PARAM_RAW, 'The data for the service'),
        'totalcount' => new external_value(PARAM_INT, 'total number of challenges in result set'),
        'filterdata' => new external_value(PARAM_RAW, 'The data for the service'),
        'records' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'total' => new external_value(PARAM_INT, 'Number of enrolled courses.', VALUE_OPTIONAL),           
                        'inprogresscount'=>  new external_value(PARAM_INT, 'Number of inprogress course count.'),  
                        'completedcount'=>  new external_value(PARAM_INT, 'Number of complete course count.'), 
                        'courses_view_count'=>  new external_value(PARAM_INT, 'Number of courses count.'), 
                        
                        'inprogress_elearning_available'=>  new external_value(PARAM_INT, 'Flag to check enrolled course available or not.'),
                        'course_count_view'=>  new external_value(PARAM_TEXT, 'to add course count class'),
                        'functionname' => new external_value(PARAM_TEXT, 'Function name'),
                        'subtab' => new external_value(PARAM_TEXT, 'Sub tab name'),
                        'elearningtemplate' => new external_value(PARAM_INT, 'template name',VALUE_OPTIONAL),
                        'nodata_string' => new external_value(PARAM_TEXT, 'no data message'),
                        'enableflow' => new external_value(PARAM_BOOL, "flag for flow enabling", VALUE_DEFAULT, false),
                        'moduledetails' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                // 'inprogress_coursename' => new external_value(PARAM_RAW, 'Course name'),
                                'lastaccessdate' => new external_value(PARAM_RAW, 'Last access Time'),
                                'course_image_url' => new external_value(PARAM_RAW, 'Course Image'),
                                'coursesummary' => new external_value(PARAM_RAW, 'Course Summary'),
                                'progress' => new external_value(PARAM_RAW, 'Course Progress'),
                                'progress_bar_width' => new external_value(PARAM_RAW, 'Course Progress bar width'),
                                'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                                'course_fullname' => new external_value(PARAM_RAW, 'Course Fullname'),
                                'course_url' => new external_value(PARAM_RAW, 'Course Url'),
                                'inprogress_coursename_fullname' => new external_value(PARAM_RAW, 'Course Url'),
                                'rating_element' => new external_value(PARAM_RAW, 'Ratings'),
                                'element_tags' => new external_value(PARAM_RAW, 'Course Tags'),
                                // 'indexClass' => new external_value(PARAM_TEXT, 'Index Card Class'),
                                'index' => new external_value(PARAM_INT, 'Index of Card'),
                             )
                        )
                    ),
                // 'viewMoreCard' => new external_value(PARAM_BOOL, 'More info card to display', false),
                'menu_heading' => new external_value(PARAM_TEXT, 'heading string of the dashboard'),
                'filter' => new external_value(PARAM_TEXT, 'filter for display data'),
                'index' => new external_value(PARAM_INT, 'number of courses count'),
                'filter_text' => new external_value(PARAM_TEXT, 'filtertext content',VALUE_OPTIONAL),
                'view_more_url' => new external_value(PARAM_URL, 'view_more_url for tab'),
                'templatename' => new external_value(PARAM_TEXT, 'Templatename for tab content'),
                'pluginname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_courses'),
                'tabname' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_courses'),
                'status' => new external_value(PARAM_TEXT, 'Pluginname for tab content', VALUE_DEFAULT, 'local_courses'),
               ) 
            ) 
        )
    ]);
    }


    /**
     * Describes the parameters for submit_featured_courses_form webservice.
    * @return external_function_parameters
    */
    public static function submit_featured_courses_form_parameters() {
        return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the featured course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create featured course form, encoded as a json array')
            )
        );
    }

    /**
     * Submit the featured courses form.
    *
    * @param int $contextid The context id for the category.
    * @param string $jsonformdata The data from the form, encoded as a json array.
    * @return int new featured course id.
    */
    public static function submit_featured_courses_form($contextid, $jsonformdata) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot.'/course/lib.php');
        //require_once($CFG->libdir.'/coursecatlib.php');
        require_once($CFG->dirroot . '/local/courses/lib.php');

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::submit_featured_courses_form_parameters(),
                                            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
      
        $data = array();

        parse_str($serialiseddata, $data);
         
        // The last param is the ajax submitted data.
        $mform = new local_courses\form\featuredcourse_form(null, array('contextid'=>$contextid), 'post', '', null, true, $data);
        
        $validateddata = $mform->get_data();
       
        if ($validateddata) {
            $data = new stdClass();
            $data->featured_course_ids = implode(',',$validateddata->course);
            $data->featured_lpath_ids = implode(',',$validateddata->learningpaths);
            $data->id = $validateddata->id;
            $data->title = $validateddata->title;
            $data->open_costcenterid =  $USER->open_costcenterid;
           
            if($validateddata->id>0){
                $data->usermodified = $USER->id;
                $data->timemodified = time();
                $featuredupdate = $DB->update_record('local_featured_courses',$data);
            } else{
                $data->usercreated = $USER->id;
                $data->timecreated = time();
                $featuredinsert = $DB->insert_record('local_featured_courses',$data);
                
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }

    }
    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function submit_featured_courses_form_returns() {
        return new external_value(PARAM_INT, 'featured course id');
    }

    /**
    * Describes the parameters for unenrol_course form webservice.
    * @return external_function_parameters
    */
    public static function unenrol_course_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'The course id for the unenrol course'),
                'confirmStatus' => new external_value(PARAM_INT, 'The unrolment  status for the unenrol course'),
                'contextid' => new external_value(PARAM_INT, 'The context id for the unenrol course'),
                'enrolid' => new external_value(PARAM_INT, 'The enrol id for the unenrol course'),
                'reason' => new external_value(PARAM_TEXT, 'The reason for the unenrol course'),
            
            )
        );
    }

    public function unenrol_course($courseid, $confirmStatus, $contextid, $enrolid , $reason ){
        global $DB,$USER;
        $instance = $DB->get_record_sql("SELECT * FROM {enrol} WHERE id = :id AND enrol IN ('self','manual') AND status = :status", array('id'=>$enrolid, 'status' => 0));    

        $course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);
        $context = context_course::instance($course->id, MUST_EXIST);
        
        if( $confirmStatus == 0){
            $status = 0;
        }
        if (!is_enrolled($context)) {
            $url = new moodle_url('/');
        }
        
        $plugin = enrol_get_plugin($instance->enrol);
        
        // Security defined inside following function.
        if (!$plugin->get_unenrolself_link($instance)) {
            $url = new moodle_url('/course/view.php', array('id'=>$course->id));
            $status = 1;
        }        
        $instance->reason = $reason;
        if ($confirmStatus == 1 and confirm_sesskey()) {
            // inserting record with unenrolment reason in user_enrolments_log
            $data = new stdClass();          
            $data->enrolid = $enrolid;
            $data->courseid = $instance->courseid;
            $data->userid = $USER->id;            
            $data->enrolstatus =  $confirmStatus;
            $data->unenrol_reason =  $reason;
            $data->time = time();
            $DB->insert_record('user_enrolments_log',$data);
            
            $plugin->unenrol_user($instance, $USER->id);        
            \core\notification::success(get_string('youunenrolledfromcourse', 'enrol', $course->fullname));
             $status = 0;
        }
        return $status;
    }

    public static function unenrol_course_returns() {
        return new external_value(PARAM_INT, 'course id');
    }
     /**
     * Describes the parameters for submit_create_coursetype_form webservice.
     * @return external_function_parameters
     */
    public static function submit_course_type_form_parameters()
    {
        return new external_function_parameters(
            array(
                //'coursetype_id' => new external_value(PARAM_INT, 'The course id for the unenrol course'),
                'contextid' => new external_value(PARAM_INT, 'The context id for the unenrol course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create featured course form, encoded as a json array')
            )
        );
    }

    public static function submit_course_type_form($contextid, $jsonformdata)
    {
        global $DB, $CFG, $USER;

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::submit_course_type_form_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        $data = array();

        parse_str($serialiseddata, $data);
        $warnings = array();

        // The last param is the ajax submitted data.
        $mform = new local_courses\form\coursetype_form(null, array('contextid' => $contextid), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();
        $systemcontext          = context_system::instance();
       
        if ($validateddata) {
            $data = new stdClass();
            $data->course_type = $validateddata->coursetype;
            if(empty($validateddata->coursetypeshortname)){
                $data->shortname = trim($data->course_type);
            }else{
                $data->shortname = $validateddata->coursetypeshortname;
            }
            
            $data->shortname = strtolower(str_replace(' ', '', trim($data->shortname)));
            $data->course_image = $validateddata->course_image;
            file_save_draft_area_files($validateddata->course_image, $systemcontext->id, 'local_courses', 'course_image', $validateddata->course_image);

            if ($validateddata->id > 0) {
                $data->id = $validateddata->id;
                $data->usermodified = $USER->id;
                $data->timemodified = time();
             
                $coursetypeeupdate = $DB->update_record('local_course_types', $data);
            } else {

                $data->usercreated = $USER->id;
                $data->timecreated = time();
                
                $coursetypeinsert = $DB->insert_record('local_course_types', $data);
	                 
	 }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
    }

    public static function submit_course_type_form_returns()
    {
        return new external_value(PARAM_INT, 'course type id');
    }

    /** Describes the parameters for delete_coursetype webservice.
     * @return external_function_parameters
     */
    public static function delete_coursetype_parameters()
    {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'coursetypeid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'name' => new external_value(PARAM_RAW, 'name', false),
            )
        );
    }

    /**
     * Deletes course type
     *
     * @param int $action
     * @param int $confirm
     * @param int $id coursetype id
     * @param string $name
     * @return int .
     */
    public static function delete_coursetype($action, $id, $confirm, $name)
    {
        global $DB;
        try {
            if ($confirm) {
                $DB->delete_records('local_course_types', array('id'=>$id));
                $courses = $DB->get_records('course', array('open_coursetype' => $id));
                if(!empty($courses)){
                    foreach ($courses as $course) {
                        // Set Course type value to default for course
                        $toupdate = new stdClass();
                        $toupdate->id = $course->id;
                        $toupdate->open_coursetype = 0;
                        $DB->update_record('course', $toupdate);
                    }
                }
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

    public static function delete_coursetype_returns()
    {
        return new external_value(PARAM_BOOL, 'return');
    }

    /** Describes the parameters for coursetype_status webservice.
     * @return external_function_parameters
     */
    public static function coursetype_update_status_parameters(){
        return new external_function_parameters(
             array(
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),          
                'contextid' => new external_value(PARAM_INT, 'The context id for course type'),
                'coursetypeid' => new external_value(PARAM_INT, 'The course type id'),
                'status' => new external_value(PARAM_RAW, 'The status of course type'),
                'name' => new external_value(PARAM_RAW, 'Course type name'),
            )   
        );
    }

      /**
     * Changes the status of course type
     *
     * @param int $action
     * @param int $confirm
     * @param int $id coursetype id
     * @param string $name
    */
    public static function coursetype_update_status($confirm, $contextid, $coursetypeid, $status, $name){
        global $DB,$USER;
        
        /* $params = self::validate_parameters(self::coursetype_update_status_parameters(),
                                    ['contextid' => $contextid,'id' => $coursetypeid, 'status' => $status ,'name' => $name]);
         */$systemcontext = \context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($systemcontext);
        $coursetype = $DB->get_record('local_course_types', array('id' => $coursetypeid), 'id, active');
        $coursetype->active = $coursetype->active ? 0 : 1;
        $coursetype->timemodified = time();
        $return = $DB->update_record('local_course_types', $coursetype);
        
        return $return;
    }


    public static function coursetype_update_status_returns(){
        return new external_value(PARAM_BOOL, 'Status');
    }

     /**
     * Describes the parameters for submit_create_course_provider_form webservice.
     * @return external_function_parameters
     */
    public static function submit_course_provider_form_parameters()
    {
        return new external_function_parameters(
            array(
                //'coursetype_id' => new external_value(PARAM_INT, 'The course id for the unenrol course'),
                'contextid' => new external_value(PARAM_INT, 'The context id for the unenrol course'),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create featured course form, encoded as a json array')
            )
        );
    }

    public static function submit_course_provider_form($contextid, $jsonformdata)
    {
        global $DB, $CFG, $USER;

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::submit_course_provider_form_parameters(),
            ['contextid' => $contextid, 'jsonformdata' => $jsonformdata]
        );

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        $serialiseddata = json_decode($params['jsonformdata']);
        $data = array();

        parse_str($serialiseddata, $data);
        $warnings = array();

        // The last param is the ajax submitted data.
        $mform = new local_courses\form\courseprovider_form(null, array('contextid' => $contextid), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();

        if ($validateddata) {
            $data = new stdClass();
            $data->course_provider = $validateddata->courseprov;
            if(empty($validateddata->courseprovshortname)){
                $data->shortname = trim($data->course_provider);
            }else{
                $data->shortname = $validateddata->courseprovshortname;
            }
            // Check if the course provider name already exists.
           /*  if (!empty($data->course_provider)) {
                if ($DB->record_exists('local_course_providers', array('course_provider' => $data->course_provider))) {
                    throw new moodle_exception(get_string('courseprovexists','local_courses'), '', '', $data->course_provider);
                }
            } */
            if ($validateddata->id > 0) {
                $data->id = $validateddata->id;
                $data->usermodified = $USER->id;
                $data->timemodified = time();
                $courseproveupdate = $DB->update_record('local_course_providers', $data);
            } else {
                $data->usercreated = $USER->id;
                $data->timecreated = time();
                $courseprovinsert = $DB->insert_record('local_course_providers', $data);
            }
        } else {
            // Generate a warning.
            throw new moodle_exception('Error in submission');
        }
    }

    public static function submit_course_provider_form_returns()
    {
        return new external_value(PARAM_INT, 'course provider id');
    }


    /** Describes the parameters for delete_courseprovider webservice.
     * @return external_function_parameters
     */
    public static function delete_courseprovider_parameters()
    {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_ACTION, 'Action of the event', false),
                'courseprovid' => new external_value(PARAM_INT, 'ID of the record', 0),
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),
                'name' => new external_value(PARAM_RAW, 'name', false),
            )
        );
    }

    /**
     * Deletes course provider
     *
     * @param int $action
     * @param int $confirm
     * @param int $id courseprovider id
     * @param string $name
     * @return int .
     */
    public static function delete_courseprovider($action, $id, $confirm, $name)
    {
        global $DB;
        try {
            if ($confirm) {
                $DB->delete_records('local_course_providers', array('id'=>$id));
                $courses = $DB->get_records('course', array('open_courseprovider' => $id));
                if(!empty($courses)){
                    foreach ($courses as $course) {
                        // Set Course provider value to default for course
                        $toupdate = new stdClass();
                        $toupdate->id = $course->id;
                        $toupdate->open_courseprovider = 0;
                        $DB->update_record('course', $toupdate);
                    }
                }
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

    public static function delete_courseprovider_returns()
    {
        return new external_value(PARAM_BOOL, 'return');
    }

    /** Describes the parameters for courseprovider_status webservice.
     * @return external_function_parameters
     */
    public static function courseprovider_update_status_parameters(){
        return new external_function_parameters(
             array(
                'confirm' => new external_value(PARAM_BOOL, 'Confirm', false),          
                'contextid' => new external_value(PARAM_INT, 'The context id for course provider'),
                'courseprovid' => new external_value(PARAM_INT, 'The course provider id'),
                'status' => new external_value(PARAM_RAW, 'The status of course provider'),
                'name' => new external_value(PARAM_RAW, 'Course provider name'),
            )   
        );
    }

      /**
     * Changes the status of course provider
     *
     * @param int $action
     * @param int $confirm
     * @param int $id courseprovider id
     * @param string $name
    */
    public static function courseprovider_update_status($confirm, $contextid, $courseprovid, $status, $name){
        global $DB,$USER;
        
        $systemcontext = \context_system::instance();
        // We always must call validate_context in a webservice.
        self::validate_context($systemcontext);
        $courseprov = $DB->get_record('local_course_providers', array('id' => $courseprovid), 'id, active');
        $courseprov->active = $courseprov->active ? 0 : 1;
        $courseprov->timemodified = time();
        $return = $DB->update_record('local_course_providers', $courseprov);
        
        return $return;
    }


    public static function courseprovider_update_status_returns(){
        return new external_value(PARAM_BOOL, 'Status');
    }

    // submint and updade records for levels.......
    public static function submit_level_parameters() {

          return new external_function_parameters(
            array(
                'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),
                'id' => new external_value(PARAM_INT,'Class id',0),
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create class form, encoded as a json array')
            )
        );
    }

    public static  function submit_level($contextid, $id, $jsonformdata) {

        global $CFG, $DB, $USER;
        $params=self::validate_parameters(
            self::submit_level_parameters(),
            array('contextid'=>$contextid,'id'=>$id,'jsonformdata'=>$jsonformdata)
        );
        $context=context::instance_by_id($params['contextid'],'MUST_EXIST');

        self::validate_context($context);

        $serialiseddata=json_decode($params['jsonformdata']);
        $data=array();
        parse_str($serialiseddata, $data);
        $warnings = array();
        $mform = new local_courses\form\level_form(null, array('id'=>$id), 'post', '', null, true, $data);

        $validateddata = $mform->get_data();

        if ($validateddata) {

            if($validateddata->id > 0) {

                $updaterec = new \stdClass();
                $updaterec->id              = $validateddata->id;
                $updaterec->name            = $validateddata->level;
                $updaterec->value           = "";
                $updaterec->timemodified    = time();
                $updaterec->usermodified    = $USER->id;
                $updaterec->costcenterid    = $USER->open_costcenterid;
                $updaterec->sortorder = 0;

                $courseupdaterecord = $DB->update_record('local_levels', $updaterec);

            } else  {

               $createrec = new \stdClass();
               $createrec->name             = $validateddata->level;
               $createrec->value            = "";
               $createrec->timecreated      = time();
               $createrec->usercreated      = $USER->id;
               $createrec->costcenterid     = $USER->open_costcenterid;
               $createrec->sortorder = 0;
               

               $insertrecord = $DB->insert_record('local_levels', $createrec);

            }
            } else {
            // Generate a warning.
           throw new moodle_exception('Error in submission');
        }
        $return = array(
            'id' => $id,
            'contextid' => $contextid,
        );

        return $return;
    }

    public static function submit_level_returns() {

         return new external_single_structure(array(
            'id' => new external_value(PARAM_INT, ' id'),
            'contextid' => new external_value(PARAM_INT, 'The context id for the system',0),

        ));
    }

    // for delete level............
    public static function delete_level_parameters() {
        return new external_function_parameters(
            array(
                'id'  => new external_value(PARAM_INT, 0, 'id'),
            )
        );
    }
    public static function delete_level($id) {
        global $DB;
       
        $params = self::validate_parameters (
            self::delete_level_parameters(),array('id'=>$id)
        );
        $context = context_system::instance();
        self::validate_context($context);
        if($id) {
            $delete = $DB->delete_records('local_levels',array('id'=>$id));
        } else {
            throw new moodle_exception('Error');
        }
    }


    public static function delete_level_returns(){

        return null;
    }


    // for change lavel status.................

    public static function change_level_status_parameters() {
        return new external_function_parameters(
            array(
                'id'  => new external_value(PARAM_INT, 'id',0),
            )
        );
    }

    public static function change_level_status($id) {
        global $DB;
    
        $params = self::validate_parameters (
            self::change_level_status_parameters(),array('id'=>$id)
        );
        
        $context = context_system::instance();
        self::validate_context($context);

        if($id) {
            $dbdata = $DB->get_record('local_levels', array('id'=>$id));
            // print_r($dbdata->active);die;

            if($dbdata->active == 0) {
                $result = $DB->execute('UPDATE {local_levels} SET active = 1 WHERE id = '.$id);
                if($result == true) {
                    \core\notification::add(get_string('noti2','local_courses'), \core\output\notification::NOTIFY_SUCCESS);
                }
            } else {
                $result = $DB->execute('UPDATE {local_levels} SET active = 0 WHERE id = '.$id);
                if($result == true) {
                    \core\notification::add(get_string('noti1','local_courses'), \core\output\notification::NOTIFY_SUCCESS);
                }
            }

        } else {
              throw new moodle_exception('Error');
        }
    }

    public static function change_level_status_returns() {
        return new external_value(PARAM_BOOL, 'Status');
    }

    public static function getallcourses_parameters(){
        return new external_function_parameters(
            array()
        );
    }

    public static function getallcourses(){
        global $DB,$CFG;
        $courses = array();
        $coursessql = "SELECT c.id,c.fullname,c.shortname,c.open_identifiedas,c.open_courseprovider,c.category,c.expirydate,ct.course_type,
                        cp.course_provider,c.open_url FROM {course} c
                        JOIN {local_course_types} ct ON ct.id = c.open_identifiedas 
                        JOIN {local_course_providers} cp ON cp.id = c.open_courseprovider 
                        WHERE c.visible = :visible c.id in (20,22)";
        $coursesres = $DB->get_records_sql($coursessql, array('visible' => 1));
        $noofrecords = count($coursesres);
        if(count($coursesres)>0){
            foreach($coursesres as $course){
                $coursecode = empty($course->shortname) ? 'N/A' : $course->shortname;
                $coursename = $course->fullname;
                $learningtype = $course->course_type;
                $courseprovider = !empty($course->course_provider) ? $course->course_provider : 'N/A';
                $courseurl = $CFG->wwwroot.'/course/view.php?id='.$course->id;//!empty($course->open_url) ? $course->open_url : 'N/A';//
                $description =  strip_tags($course->summary);
                $imageurl = course_thumbimage($course);
                $courses[] = array('coursecode' => $coursecode, 'coursename' => $coursename, 'learningtype' =>$learningtype, 'courseprovider' => $courseprovider , 'courseurl' => $courseurl, 'description' => $description ,'imageurl' => $imageurl); 
            
            }
        }
        $result = [
            'count' => $noofrecords,
            'result' => $courses
        ];  
      
        return $result;
    }

    public static function getallcourses_returns(){
        return new external_single_structure(
           array(
                'count' => new external_value(PARAM_TEXT, 'Number of courses', VALUE_OPTIONAL),
                'result' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'coursecode' => new external_value(PARAM_TEXT, 'course shortname', VALUE_OPTIONAL),
                            'coursename' => new external_value(PARAM_TEXT, 'course fullname', VALUE_OPTIONAL),
                            'learningtype' => new external_value(PARAM_TEXT, 'learning type', VALUE_OPTIONAL),
                            'courseprovider' => new external_value(PARAM_TEXT, 'course provider', VALUE_OPTIONAL),  
                            'courseurl' => new external_value(PARAM_TEXT, 'course url', VALUE_OPTIONAL),                         
                            'description' => new external_value(PARAM_TEXT, 'course description', VALUE_OPTIONAL),
                            'imageurl' => new external_value(PARAM_RAW, 'course image url', VALUE_OPTIONAL)
                        )
                    )
                )
            )
        );
    }
   public static function reissue_course_certificate_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_RAW, 'The id for the issued certificate'),
                'fullname' => new external_value(PARAM_RAW, 'Full Name of the user'),
                'userid' => new external_value(PARAM_RAW, 'The id for the user'),
                'moduleid' => new external_value(PARAM_RAW, 'The id for the module'),
            )
        );
    }
    public static function reissue_course_certificate($id,$fullname,$userid,$moduleid) {
        global $DB;
        $old = $DB->get_record('tool_certificate_issues', array('id' => $id));
        $DB->delete_records('tool_certificate_issues', array('id' => $id));
        $get_course = $DB->get_record('course', array('id'=>$moduleid));
        $issue = new \stdClass();
        $issue->userid = $old->userid;
        $issue->templateid = $get_course->open_certificateid;
        $issue->code = $old->code; //\tool_certificate\certificate::generate_code($issue->userid);
        $issue->emailed = $old->emailed;
        $issue->timecreated = $old->timecreated;
        $issue->expires = $old->expires;
        $issue->component = 'tool_certificate';
        $issue->courseid = $old->courseid;
        $issue->moduletype = 'course';
        $issue->moduleid = $moduleid;
        $data['userfullname'] = fullname($DB->get_record('user', ['id' => $userid]));
        $issue->data = json_encode($data);
        $issue->id = $DB->insert_record('tool_certificate_issues', $issue);
        issue_handler::create()->save_additional_data($issue, $data);
        return true;
    }
    public static function reissue_course_certificate_returns() {
        return new external_value(PARAM_RAW, 'data');
    }

}
