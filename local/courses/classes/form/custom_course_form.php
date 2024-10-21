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
 * @subpackage local_courses
 */

namespace local_courses\form;
use local_users\functions\userlibfunctions as userlib;
use core;
use moodleform;
use context_system;
use context_course;
use context_coursecat;
use core_component;
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class custom_course_form extends moodleform {
    protected $course;
    protected $context;
    public $formstatus;
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $formdata = null) {
        $this->formstatus = array(
            'manage_course' => get_string('manage_course', 'local_courses'),
            'other_details' => get_string('courseother_details', 'local_courses')
        );
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $formdata);
    }
    /**
     * Form definition.
     */
    function definition() {
        global $DB,$OUTPUT,$CFG, $PAGE, $USER;

        $mform    = $this->_form;
        $course        = $this->_customdata['course']; // this contains the data of this form
        $course_id        = $this->_customdata['courseid']; // this contains the data of this form
        $category      = $this->_customdata['category'];
        $formstatus = $this->_customdata['form_status'];
		    $get_coursedetails = $this->_customdata['get_coursedetails'];
        $editoroptions = $this->_customdata['editoroptions'];
        $returnto = $this->_customdata['returnto'];
        $returnurl = $this->_customdata['returnurl'];
        $costcenterid = $this->_customdata['costcenterid'];
        $systemcontext   = context_system::instance();

        $formheaders = array_keys($this->formstatus);
        $formheader = $formheaders[$formstatus];

        if(empty($category)){
          $category = $CFG->defaultrequestcategory;
        }

        if (!empty($course->id)) {
          $coursecontext = context_course::instance($course->id);
          $context = $coursecontext;
          $categorycontext = context_coursecat::instance($category->id);
        } else {
          $coursecontext = null;
          $categorycontext = context_coursecat::instance($category);
          $context = $categorycontext;
        }

        $courseconfig = get_config('moodlecourse');

        $this->course  = $course;
        $this->context = $context;

        // Form definition with new course defaults.
        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);

        $mform->addElement('hidden', 'form_status', $formstatus);
        $mform->setType('form_status', PARAM_ALPHANUM);

        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_LOCALURL);
        $mform->setConstant('returnurl', $returnurl);

        $mform->addElement('hidden', 'getselectedclients');
        $mform->setType('getselectedclients', PARAM_BOOL);

        $defaultformat = $courseconfig->format;

        if(empty($course->id)){
          $courseid = 0;
        }else{
          $courseid = $id = $course->id;
        }

        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);
		$systemcontext = context_system::instance();
        $core_component = new core_component();
        if($formstatus == 0){
			    $selectdepartmentslist = array(null=>get_string('selectdept','local_courses'));

            if (is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)) {
                $organisation_select = [null => get_string('selectorg','local_courses')];
                if($id || $this->_ajaxformdata['open_costcenterid']){
                    $open_costcenter = (int) $this->_ajaxformdata['open_costcenterid'] ? (int)$this->_ajaxformdata['open_costcenterid'] : $get_coursedetails->open_costcenterid;
                    $organisations = $organisation_select + $DB->get_records_menu('local_costcenter', array('id' => $open_costcenter), '',  $fields='id, fullname'); 
                }else{
                    $open_costcenter = 0;
                    $organisations = $organisation_select;
                }
                $costcenteroptions = array(
                    'ajax' => 'local_costcenter/form-options-selector',
                    'data-contextid' => $systemcontext->id,
                    'data-action' => 'costcenter_organisation_selector',
                    'data-options' => json_encode(array('id' => $open_costcenter)),
                    'class' => 'organisationnameselect',
                    'data-class' => 'organisationselect',
                    'multiple' => false,
                );

               // $mform->addElement('autocomplete', 'open_costcenterid', get_string('organization','local_courses'), $organisations, $costcenteroptions);
                $mform->addElement('hidden', 'open_costcenterid', null, array('id' => 'id_open_costcenterid', 'data-class' => 'organisationselect'));
                $mform->addHelpButton('open_costcenterid', 'open_costcenteridcourse', 'local_courses');
                $mform->setType('open_costcenterid', PARAM_INT);
               // $mform->addRule('open_costcenterid', get_string('pleaseselectorganization','local_courses'), 'required', null, 'client');
                $mform->setConstant('open_costcenterid', $USER->open_costcenterid);


            } else if (has_capability('local/costcenter:manage_ownorganization',$systemcontext)){

                $mform->addElement('hidden', 'open_costcenterid', null, array('id' => 'id_open_costcenterid', 'data-class' => 'organisationselect'));
                $mform->setType('open_costcenterid', PARAM_INT);
                $mform->setConstant('open_costcenterid', $USER->open_costcenterid);
            
            } else if (has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
            
                $mform->addElement('hidden', 'open_costcenterid', null, array('id' => 'id_open_costcenterid', 'data-class' => 'organisationselect'));
                $mform->setType('open_costcenterid', PARAM_INT);
                $mform->setConstant('open_costcenterid', $USER->open_costcenterid);

                $mform->addElement('hidden', 'open_departmentid', $USER->open_departmentid,array('id' => 'id_open_departmentid', 'data-class' => 'departmentselect'));
                $mform->setType('open_departmentid', PARAM_INT);
                $mform->setConstant('open_departmentid', $USER->open_departmentid);

            } else {

                $mform->addElement('hidden', 'open_costcenterid', null, array('id' => 'id_open_costcenterid', 'data-class' => 'organisationselect'));
                $mform->setType('open_costcenterid', PARAM_INT);
                $mform->setConstant('open_costcenterid', $USER->open_costcenterid);

                $mform->addElement('hidden', 'open_departmentid', $USER->open_departmentid, array('id' => 'id_open_departmentid', 'data-class' => 'departmentselect'));
                $mform->setType('open_departmentid', PARAM_INT);
                $mform->setConstant('open_departmentid', $USER->open_departmentid);

                if($USER->open_subdepartment){
                    $mform->addElement('hidden', 'open_subdepartment', null,array('id' => 'id_open_subdepartment'));
                    $mform->setType('open_subdepartment', PARAM_INT);
                    $mform->setConstant('open_subdepartment', $USER->open_subdepartment);
                }
            }
           /* if(is_siteadmin($USER->id) || has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || has_capability('local/costcenter:manage_ownorganization',$systemcontext)){
                $department_select = [0 => get_string('all')];
                // if($id){
                //   $department_select = [0 => get_string('all')];
                // }else{
                //   $department_select = [0 => get_string('selectdept','local_courses')];
                // }
                if($id || $this->_ajaxformdata['open_departmentid']){
                    $open_department = (int)$this->_ajaxformdata['open_departmentid'] ? (int)$this->_ajaxformdata['open_departmentid'] : $get_coursedetails->open_departmentid;
                    $departments = $department_select + $DB->get_records_menu('local_costcenter', array('id' => $open_department), '',  $fields='id, fullname'); 
                }else{
                    $open_department = 0;
                    $departments = $department_select;
                }
                $departmentoptions = array(
                    'ajax' => 'local_costcenter/form-options-selector',
                    'data-contextid' => $systemcontext->id,
                    'data-action' => 'costcenter_department_selector',
                    'data-options' => json_encode(array('id' => $open_department)),
                    'class' => 'departmentselect',
                    'data-parentclass' => 'organisationselect',
                    'data-class' => 'departmentselect',
                    'multiple' => false,
                );
                $department_select = [0 => get_string('all')];
                $mform->addElement('autocomplete', 'open_departmentid', get_string('department'), $departments, $departmentoptions);
                $mform->addHelpButton('open_departmentid', 'open_departmentidcourse', 'local_courses');
                $mform->setType('open_departmentid', PARAM_INT);
            }
            if(is_siteadmin($USER->id) || 
                has_capability('local/costcenter:manage_multiorganizations',$systemcontext) || 
                has_capability('local/costcenter:manage_ownorganization',$systemcontext) || 
                has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
                $subdepartment_select = [0 => get_string('all')];
                if($id || $this->_ajaxformdata['open_subdepartment']){
                    $open_subdepartment = (int)$this->_ajaxformdata['open_subdepartment'] ? (int)$this->_ajaxformdata['open_subdepartment'] : $get_coursedetails->open_subdepartment;
                    $subdepartments = $subdepartment_select + $DB->get_records_menu('local_costcenter', array('id' => $open_subdepartment), '',  $fields='id, fullname'); 
                }else{
                    $open_subdepartment = 0;
                    $subdepartments = $subdepartment_select;
                }
                $subdepartmentoptions = array(
                    'ajax' => 'local_costcenter/form-options-selector',
                    'data-contextid' => $systemcontext->id,
                    'data-action' => 'costcenter_subdepartment_selector',
                    'data-options' => json_encode(array('id' => $open_subdepartment)),
                    'class' => 'subdepartmentselect',
                    'data-parentclass' => 'departmentselect',
                    'data-class' => 'subdepartmentselect',
                    'multiple' => false,
                );

                $mform->addElement('autocomplete', 'open_subdepartment', get_string('sub_departments', 'local_courses'), $subdepartments, $subdepartmentoptions);
                $mform->addHelpButton('open_subdepartment', 'open_subdepartmentcourse', 'local_courses');
                $mform->setType('open_subdepartment', PARAM_INT);
            }*/
            if(/*$get_coursedetails->open_subdepartment ||*/ (int)$this->_ajaxformdata['open_subdepartment']){
                $parentid = (int)$this->_ajaxformdata['open_subdepartment']; 
            }else if((int)$this->_ajaxformdata['open_departmentid']){
                $parentid = (int)$this->_ajaxformdata['open_departmentid'];
            }else if((int)$this->_ajaxformdata['open_costcenterid']){
                $parentid = (int)$this->_ajaxformdata['open_costcenterid'];
            }
            
            if($course->id){
                if(/*$get_coursedetails->open_subdepartment ||*/ (int)$this->_ajaxformdata['open_subdepartment']){
                    $parentid = (int)$this->_ajaxformdata['open_subdepartment']; 
                }else if((int)$this->_ajaxformdata['open_departmentid']){
                    $parentid = (int)$this->_ajaxformdata['open_departmentid'];
                }else if($costcenterid ){
                    $parentid = $costcenterid ;
                }
            }
            
            $mform->addElement('text','fullname', get_string('fullnamecourse','local_courses'),'maxlength="254" size="50"');
            $mform->addHelpButton('fullname', 'fullnamecourse');
            $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
            $mform->setType('fullname', PARAM_TEXT);
            if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
                $mform->hardFreeze('fullname');
                $mform->setConstant('fullname', $course->fullname);
            }

            $mform->addElement('text', 'shortname', get_string('shortnamecourse','local_courses'), 'maxlength="100" size="20"');
            $mform->addHelpButton('shortname', 'shortnamecourse');
            $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
            $mform->setType('shortname', PARAM_TEXT);
            if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
                $mform->hardFreeze('shortname');
                $mform->setConstant('shortname', $course->shortname);
            }

            if($parentid){
              $parentcategory = $DB->get_field('local_costcenter', 'category', array('id' => $parentid));
                $categorysql = "SELECT cc.id, cc.path 
                                FROM {course_categories} AS cc 
                                WHERE (cc.path LIKE '%/{$parentcategory}/%' ";
                if($parentcategory){
                    $categorysql .= " OR cc.id = {$parentcategory} ";
                }
                $categorysql .= " ) ORDER BY cc.sortorder ASC ";
                $displaylist = $DB->get_records_sql_menu($categorysql);
            }
            $selectcatlist = array(null=>get_string('selectcat','local_courses'));
            if( isset($displaylist) && !empty($displaylist) ){
              $findisplaylist = array();
              foreach ($displaylist as $key => $categorywise) {
                $explodepaths = explode('/',$categorywise);
                $countcat = count($explodepaths);
                if($countcat > 0){
                    $catpathnames = array();
                    for ($i=0; $i < $countcat; $i++) { 
                        if($i != 0){
                            $catpathnames[$i] = $DB->get_field('course_categories','name',array('id' => $explodepaths[$i]));
                        }
                    }
                    if(count($catpathnames) > 1){
                        $findisplaylist[$key] = implode(' / ',$catpathnames);
                    }else{
                        $findisplaylist[$key] = $catpathnames[1];
                    }
                    
                }
              }
              $categories = $selectcatlist+$findisplaylist;
            }else {
              $categories = $selectcatlist;
            }
       
            $categoryoptions = array(
              'ajax' => 'local_costcenter/form-options-selector',
              'data-contextid' => $systemcontext->id,
              'data-action' => 'costcenter_category_selector',
              'data-options' => json_encode(array('id' => $parentcategory)),
              'class' => 'categoryselect',
              'data-parentclass' => 'subdepartmentselect',
              'data-class' => 'categoryselect',
              'multiple' => false,
            );
            
            $mform->addElement('autocomplete', 'category', get_string('coursecategory','local_courses'), $categories, $categoryoptions);
            $mform->addHelpButton('category', 'coursecategory');
            $mform->addRule('category', get_string('pleaseselectcategory','local_courses'), 'required', null, 'client');
            $mform->setType('category', PARAM_INT);

            $coursetypes = array();
            $classroom_plugin_exist = $core_component::get_plugin_directory('local', 'classroom');
            $learningplan_plugin_exist = $core_component::get_plugin_directory('local', 'learningplan');
            //$program_plugin_exist = $core_component::get_plugin_directory('local', 'program');
            //$certification_plugin_exist = $core_component::get_plugin_directory('local', 'certification');
          
            $coursetypes['1'] = get_string('mooc','local_courses');
            if(!empty($classroom_plugin_exist)){
                $coursetypes['2'] = get_string('ilt','local_courses');
            }
            $coursetypes['3'] = get_string('elearning','local_courses');
            if(!empty($learningplan_plugin_exist)){
                $coursetypes['4'] = get_string('learningplan','local_courses');
            }
            /*
            if(!empty($program_plugin_exist)){
			 $coursetypes['5'] = get_string('program','local_courses');
            }
            if(!empty($certification_plugin_exist)){
                $coursetypes['6'] = get_string('certification','local_courses');
            } */
            $sql = "SELECT id, course_type FROM {local_course_types} WHERE id > 4 AND active = 1";
            $ctypes = $DB->get_records_sql_menu($sql, []);          
            $coursetypes = $coursetypes + $ctypes; 
          
            $select = $mform->addElement('autocomplete', 'open_identifiedas', get_string('type','local_courses'), $coursetypes);
            $mform->addHelpButton('open_identifiedas', 'open_identifiedascourse', 'local_courses');
            $mform->addRule('open_identifiedas', get_string('missingtype','local_courses'), 'required', null, 'client');
            $mform->setType('open_identifiedas',PARAM_INT);
            // $select->setMultiple(true);
            
         $grade_details = array();      
         $grade_details[-1]=get_string('all');

         if(is_siteadmin()){
       
           $grade_sql = "SELECT DISTINCT(open_grade), open_grade AS gradevalue FROM {user} AS u WHERE id > 2 AND suspended = 0 AND deleted = 0 AND open_grade IS NOT NULL GROUP BY open_grade ";
           $grade_details+= $DB->get_records_sql_menu($grade_sql,array());
         } else {

           $grade_sql = "SELECT DISTINCT(open_grade), open_grade AS gradevalue FROM {user} AS u WHERE open_costcenterid = $USER->open_costcenterid AND  id > 2 AND suspended = 0 AND deleted = 0 AND open_grade IS NOT NULL GROUP BY open_grade ";
           $grade_details+= $DB->get_records_sql_menu($grade_sql,array());
         }
       
        $selectgrade = $mform->addElement('autocomplete',  'open_grade',  get_string('empgrade', 'local_courses'),$grade_details);
        $mform->addHelpButton('open_grade', 'open_gradecourse', 'local_courses');
        $mform->addRule('open_grade', get_string('missinggrade','local_courses'), 'required', null, 'client');
        $mform->setType('open_grade', PARAM_RAW);
        $selectgrade->setMultiple(true);


        $open_ou_details = array();
        $open_ou_details[-1] = get_string('all');
        if(is_siteadmin()){
           
           $open_ou_sql = "SELECT DISTINCT(open_ouname), open_ouname AS ounamevalue FROM {user} AS u WHERE id > 2 AND suspended = 0 AND deleted = 0 AND open_ouname IS NOT NULL AND open_ouname != 'All' GROUP BY open_ouname ";
           $open_ou_details+= $DB->get_records_sql_menu($open_ou_sql,array());
        
        } else {
           
           $open_ou_sql = "SELECT DISTINCT(open_ouname), open_ouname AS ounamevalue FROM {user} AS u WHERE open_costcenterid = $USER->open_costcenterid AND  id > 2 AND suspended = 0 AND deleted = 0 AND open_ouname IS NOT NULL AND open_ouname != 'All' GROUP BY open_ouname ";
           $open_ou_details+= $DB->get_records_sql_menu($open_ou_sql,array());
         }
       
        $selectouname = $mform->addElement('autocomplete',  'open_ouname',  get_string('ou_name', 'local_courses'),$open_ou_details);
        $mform->addHelpButton('open_ouname', 'open_ouname', 'local_courses');
        $mform->addRule('open_ouname', get_string('missingouname','local_courses'), 'required', null, 'client');
        $mform->setType('open_ouname', PARAM_RAW);
        $selectouname->setMultiple(true);

        $levelselect = array(0 => 'Select Level');
            if(is_siteadmin()){
                $levels = $DB->get_records_menu('local_levels',  array('active'=>1),'sortorder', 'id,name');
            }else{
                $levels = $DB->get_records_menu('local_levels',  array('costcenterid' =>$USER->open_costcenterid,'active'=>1),'sortorder', 'id,name');
            }
            if($levels){
                $levelselect = $levelselect + $levels;
            }
            $mform->addElement('autocomplete',  'open_level', 'Level', $levelselect);
            $mform->addHelpButton('open_level', 'open_levelcourse', 'local_courses');
            $mform->addRule('open_level', get_string('missinglevel','local_courses'), 'required', null, 'client');
            $mform->setType('open_level', PARAM_INT);


            $course_providers_select = array(null => 'Select Course Providers');
            if(is_siteadmin()){
                $course_providers = $DB->get_records_menu('local_course_providers',  array('active'=>1),'id', 'id,course_provider');
            }else{
                $course_providers = $DB->get_records_menu('local_course_providers',  array('active'=>1),'id', 'id,course_provider');
            }
            if($course_providers){
                $course_providers_select = $course_providers_select + $course_providers;
            }
            $mform->addElement('autocomplete',  'open_courseprovider', get_string('courseprov_name','local_courses'), $course_providers_select);
            $mform->addRule('open_courseprovider', get_string('missingprovider','local_courses'), 'required', null, 'client');
            $mform->addHelpButton('open_courseprovider', 'open_courseprovidercourse', 'local_courses');
            $mform->setType('open_courseprovider', PARAM_INT);

            
            $credits = $DB->get_field('course', 'open_points', array('id' => $course->id));
            $mform->addElement('text', 'open_points', get_string('points','local_courses'), $credits);
            $mform->addHelpButton('open_points', 'open_pointscourse', 'local_courses');
            $mform->addRule('open_points', get_string('missingpoints','local_courses'), 'required', null, 'client');
            $mform->setType('open_points', PARAM_FLOAT); 

            $facilitatorcredits = $DB->get_field('course', 'open_facilitatorcredits', array('id' => $course->id));
            $mform->addElement('text', 'open_facilitatorcredits', get_string('open_facilitatorcredits','local_courses'), $facilitatorcredits);
            $mform->addHelpButton('open_facilitatorcredits', 'open_facilitatorcreditscourse', 'local_courses');
            $mform->addRule('open_facilitatorcredits', get_string('missingfacilitatorcredits','local_courses'), 'required', null, 'client');
            $mform->setType('open_facilitatorcredits', PARAM_FLOAT);

             // for display career track
            $careertracks = array();
            $alloption = array('All'=>'All');
            $careertracks['AI Engineer (Transition)'] = 'AI Engineer (Transition)';
            $careertracks['AI Engineer (Specialization)'] = 'AI Engineer (Specialization)';
            $careertracks['Data Engineer (Transition)'] = 'Data Engineer (Transition)';
            $careertracks['Data Engineer (Specialization)'] = 'Data Engineer (Specialization)';
            $careertracks['Data Scientist (Transition)'] = 'Data Scientist (Transition)';
            $careertracks['Data Scientist (Specialization)'] = 'Data Scientist (Specialization)';
            $careertracks['Decision Scientist (Transition)'] = 'Decision Scientist (Transition)';
            $careertracks['Decision Scientist (Specialization)'] = 'Decision Scientist (Specialization)';
            $careertracks['Designer (Transition)'] = 'Designer (Transition)';
            $careertracks[' Designer (Specialization)'] = ' Designer (Specialization)';
            $careertracks['Information Architect (Transition)'] = 'Information Architect (Transition)';
            $careertracks['Information Architect (Specialization)'] = 'Information Architect (Specialization)';
            $careertracks['GCF'] = 'GCF';
            $careertracks = $alloption + $careertracks;

            $careertrk = $mform->addElement('autocomplete', 'open_careertrack',get_string('open_career_track_tag','local_courses'), $careertracks, array('data-placeholder'=>'-- Select Career track --'));
            $mform->addHelpButton('open_careertrack', 'open_careertrackcourse', 'local_courses');
            $mform->addRule('open_careertrack', get_string('missingcareertrack','local_courses'), 'required', null, 'client');
            $mform->setType('open_careertrack', PARAM_TEXT);
            $mform->addRule('open_careertrack', null, 'required', null, 'client');
            $careertrk->setMultiple(true);

             $radioarray=array();
            $radioarray[] = $mform->createElement('static', '','','<b class=hours>Hours</b>');
            $radioarray[] = $mform->createElement('static', '','','<b class=minutes>Minutes(MM)</b>');
            $mform->addGroup($radioarray, 'duration_label', '', array(' '), false);

            $duration = $DB->get_field('course', 'duration', array('id' => $course->id)); 

            $radioarray=array();
            $radioarray[] = $mform->createElement('text', 'hours','',array('placeholder'=>'Hours'));
            $radioarray[] = $mform->createElement('static', '','','<b>:</b>');
            $radioarray[] = $mform->createElement('text', 'min','',array('placeholder'=>'Minutes(MM)'));
            $mform->addGroup($radioarray, 'duration', 'Course Duration', array(' '), false);
                        
            $templaterules['hours'][] = array( get_string('numbersonlyhours'), 'numeric', null, 'client');
            $templaterules['min'][] = array( get_string('numbersonlyminutes'), 'numeric', null, 'client');
            $mform->addGroupRule('duration', $templaterules);
            $mform->addRule('duration', null, 'required', null);
            
            $coursevisible = array();
            $coursevisible['0'] = get_string('hide');
            $coursevisible['1'] = get_string('show');

            $mform->addElement('select', 'visible', get_string('visible'), $coursevisible);
            $mform->addHelpButton('visible', 'visible');
            $mform->setDefault('visible', $courseconfig->visible);
            if (!empty($course->id)) {
                if (!has_capability('moodle/course:visibility', $coursecontext)) {
                    $mform->hardFreeze('visible');
                    $mform->setConstant('visible', $course->visible);
                }
            } else {
                if (!guess_if_creator_will_have_course_capability('moodle/course:visibility', $categorycontext)) {
                    $mform->hardFreeze('visible');
                    $mform->setConstant('visible', $courseconfig->visible);
                }
            }

             
            $startdate = $DB->get_field('course', 'startdate', array('id' => $course->id)); 
            $mform->addElement('date_selector', 'startdate', get_string('startdate','local_courses'),
             $startdate);        
            $mform->addHelpButton('startdate', 'startdate');
            $mform->setDefault('startdate', time() + 3600 * 24);
            
            $mform->addElement('date_selector', 'expirydate', get_string('expirydate',
                'local_courses'), array('optional'  => true));
            $mform->setType('expirydate', PARAM_RAW);

            $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
            $mform->addHelpButton('idnumber', 'idnumbercourse');
            $mform->setType('idnumber', PARAM_RAW);
            if (!empty($course->id) and !has_capability('moodle/course:changeidnumber', $coursecontext)) {
                $mform->hardFreeze('idnumber');
                $mform->setConstants('idnumber', $course->idnumber);
            }
            
            $mform->addElement('text', 'open_url',  get_string('open_url', 'local_courses'));
            $mform->addHelpButton('open_url', 'open_url','local_courses');
            $mform->setType('open_url', PARAM_URL);
      
            //for course format
            $courseformats = get_sorted_course_formats(true);
            $formcourseformats = array();
            foreach ($courseformats as $courseformat) {
              $formcourseformats[$courseformat] = get_string('pluginname', "format_$courseformat");
            }

            if (isset($course->format)) {
              $course->format = course_get_format($course)->get_format(); // replace with default if not found
              if (!in_array($course->format, $courseformats)) {
                  // this format is disabled. Still display it in the dropdown
                  $formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
                          get_string('pluginname', 'format_'.$course->format));
              }
            }

            $mform->addElement('select', 'format', get_string('format'), $formcourseformats);
            $mform->addHelpButton('format', 'format');
            $mform->setDefault('format', $defaultformat);

            
            $mform->addElement('hidden', 'mincompletiondays', 0);
            $mform->setType('mincompletiondays', PARAM_INT);

            $mform->addElement('text', 'open_coursecompletiondays', get_string('coursecompday','local_courses'));
            $mform->setType('open_coursecompletiondays', PARAM_INT);
            $mform->addHelpButton('open_coursecompletiondays', 'coursecompday', 'local_courses');
            // $mform->addRule(array('open_coursecompletiondays', 'mincompletiondays'), 
            //     get_string('coursecompletiondays_positive', 'local_courses'), 
            //     'compare', 'gt', 'client');
	    
            $manageselfenrol = array();
            $manageselfenrol[] = $mform->createElement('radio', 'selfenrol', '', get_string('yes'), 1, array());
            $manageselfenrol[] = $mform->createElement('radio', 'selfenrol', '', get_string('no'), 0, array());
            $mform->addGroup($manageselfenrol, 'selfenrol',
                get_string('need_self_enrol', 'local_courses'),
                array('&nbsp;&nbsp;'), false);
            $mform->addHelpButton('selfenrol', 'selfenrolcourse', 'local_courses');

            $manageautoenrol = array();
            $manageautoenrol[] = $mform->createElement('radio', 'autoenrol', '', get_string('yes'), 1, array());
            $manageautoenrol[] = $mform->createElement('radio', 'autoenrol', '', get_string('no'), 0, array());
            $mform->addGroup($manageautoenrol, 'autoenrol',
                get_string('need_auto_enrol', 'local_courses'),
                array('&nbsp;&nbsp;'), false);
            $mform->addHelpButton('autoenrol', 'autoenrolcourse', 'local_courses');

/*   			$manageapproval = array();
  			$manageapproval[] = $mform->createElement('radio', 'approvalreqd', '', get_string('yes'), 1, array());
  			$manageapproval[] = $mform->createElement('radio', 'approvalreqd', '', get_string('no'), 0, array());
  			$mform->addGroup($manageapproval, 'approvalreqd',
  				get_string('need_manage_approval', 'local_courses'),
  				array('&nbsp;&nbsp;'), false);
            $mform->addHelpButton('approvalreqd', 'approvalreqdcourse', 'local_courses');
            $mform->hideIf('approvalreqd', 'selfenrol', 'neq', '1'); */

            $mform->addElement('hidden', 'approvalreqd', 0, array());
            $mform->setType('approvalreqd', PARAM_INT);
            $mform->setConstant('approvalreqd', 0);

	
            $certificate_plugin_exist = $core_component::get_plugin_directory('tool', 'certificate');
            if($certificate_plugin_exist){
                $checkboxes = array();
                $checkboxes[] = $mform->createElement('advcheckbox', 'map_certificate', null, '', array(),array(0,1));
                $mform->addGroup($checkboxes, 'map_certificate', get_string('add_certificate', 'local_courses'), array(' '), false);
                $mform->addHelpButton('map_certificate', 'add_certificate', 'local_courses');


                $select = array(null => get_string('select_certificate','local_courses'));

                if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
                    $cert_templates = $DB->get_records_menu('tool_certificate_templates',array(),'name', 'id,name');
                }else{
                    $cert_templates = $DB->get_records_menu('tool_certificate_templates',array('costcenter'=>$USER->open_costcenterid),'name', 'id,name');
                }
                $certificateslist = $select + $cert_templates;

                $mform->addElement('select',  'open_certificateid', get_string('certificate_template','local_courses'), $certificateslist);
                $mform->addHelpButton('open_certificateid', 'certificate_template', 'local_courses');
                $mform->setType('open_certificateid', PARAM_INT);
                $mform->hideIf('open_certificateid', 'map_certificate', 'neq', 1);
            }

             $llp_sql = "SELECT ll.id,ll.fullname
                            FROM {course} as ll
                             WHERE ll.visible = 1 "; 
            if($id){
                $llp_sql .= " AND ll.id != {$id}";
            }
            //comment <Revathi>
            // $lp_ids = $DB->get_records_sql_menu($llp_sql);
            //  $prerequisites_f = $mform->addElement('autocomplete', 'open_prerequisites',get_string('prerequisites','local_courses'), $lp_ids, array('data-placeholder'=>'-- Select prerequisites --'));
            //     $mform->addHelpButton('open_prerequisites', 'prerequisitescourse', 'local_courses');
            //     $mform->setType('open_prerequisites', PARAM_TEXT);
            //     $prerequisites_f->setMultiple(true);
            // // }
            // $mform->hideIf('open_prerequisites', 'open_identifiedas', 'neq', '2');


            // Description.
            $mform->addElement('header', 'descriptionhdr', get_string('description'));
            $mform->setExpanded('descriptionhdr');

            $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
            $mform->addHelpButton('summary_editor', 'coursesummary');
            $mform->setType('summary_editor', PARAM_RAW);
            $summaryfields = 'summary_editor';

            if ($overviewfilesoptions = course_overviewfiles_options($course)) {
                $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('courseoverviewfiles'), null, $overviewfilesoptions);
                $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
                $summaryfields .= ',overviewfiles_filemanager';
            }

            if (!empty($course->id) and !has_capability('moodle/course:changesummary', $coursecontext)) {
                // Remove the description header it does not contain anything any more.
                $mform->removeElement('descriptionhdr');
                $mform->hardFreeze($summaryfields);
            }

            // Files and uploads.
            $mform->addElement('header', 'filehdr', get_string('filesanduploads'));

            if (!empty($course->legacyfiles) or !empty($CFG->legacyfilesinnewcourses)) {
                if (empty($course->legacyfiles)) {
                    //0 or missing means no legacy files ever used in this course - new course or nobody turned on legacy files yet
                    $choices = array('0'=>get_string('no'), '2'=>get_string('yes'));
                } else {
                    $choices = array('1'=>get_string('no'), '2'=>get_string('yes'));
                }
                $mform->addElement('select', 'legacyfiles', get_string('courselegacyfiles'), $choices);
                $mform->addHelpButton('legacyfiles', 'courselegacyfiles');
                if (!isset($courseconfig->legacyfiles)) {
                    // in case this was not initialised properly due to switching of $CFG->legacyfilesinnewcourses
                    $courseconfig->legacyfiles = 0;
                }
                $mform->setDefault('legacyfiles', $courseconfig->legacyfiles);
            }

             // Handle non-existing $course->maxbytes on course creation.
            $coursemaxbytes = !isset($course->maxbytes) ? null : $course->maxbytes;

            // Let's prepare the maxbytes popup.
            $choices = get_max_upload_sizes($CFG->maxbytes, 0, 0, $coursemaxbytes);
            $mform->addElement('select', 'maxbytes', get_string('maximumupload'), $choices);
            $mform->addHelpButton('maxbytes', 'maximumupload');
            $mform->setDefault('maxbytes', $courseconfig->maxbytes);

            // Completion tracking.
           // if (completion_info::is_enabled_for_site()) {
                $mform->addElement('header', 'completionhdr', get_string('completion', 'completion'));
                $mform->addElement('selectyesno', 'enablecompletion', get_string('enablecompletion', 'completion'));
                $mform->setDefault('enablecompletion', $courseconfig->enablecompletion);
                $mform->addHelpButton('enablecompletion', 'enablecompletion', 'completion');
            /*} else {
                $mform->addElement('hidden', 'enablecompletion');
                $mform->setType('enablecompletion', PARAM_INT);
                $mform->setDefault('enablecompletion', 0);
            }*/

            $mform->addElement('header','groups', get_string('groupsettingsheader', 'group'));
            $choices = array();
            $choices[NOGROUPS] = get_string('groupsnone', 'group');
            $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
            $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');
            $mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $choices);
            $mform->addHelpButton('groupmode', 'groupmode', 'group');
            $mform->setDefault('groupmode', $courseconfig->groupmode);

            $mform->addElement('selectyesno', 'groupmodeforce', get_string('groupmodeforce', 'group'));
            $mform->addHelpButton('groupmodeforce', 'groupmodeforce', 'group');
            $mform->setDefault('groupmodeforce', $courseconfig->groupmodeforce);
            
             //default groupings selector
            $options = array();
            $options[0] = get_string('none');
            $mform->addElement('select', 'defaultgroupingid', get_string('defaultgrouping', 'group'), $options);



        } elseif($formstatus == 1){
      
            $cost = $DB->get_field('course', 'open_cost', array('id' => $course->id));
            $mform->addElement('text', 'open_cost', get_string('cost','local_courses'), $cost);
            $mform->setType('open_cost', PARAM_INT);

            // $radioarray=array();
            // $radioarray[] = $mform->createElement('static', '','','<b class=hours>Hours</b>');
            // $radioarray[] = $mform->createElement('static', '','','<b class=minutes>Minutes(MM)</b>');
            // $mform->addGroup($radioarray, 'duration_label', '', array(' '), false);

            // $duration = $DB->get_field('course', 'duration', array('id' => $course->id)); 

            // $radioarray=array();
            // $radioarray[] = $mform->createElement('text', 'hours','',array('placeholder'=>'Hours'));
            // $radioarray[] = $mform->createElement('static', '','','<b>:</b>');
            // $radioarray[] = $mform->createElement('text', 'min','',array('placeholder'=>'Minutes(MM)'));
            // $mform->addGroup($radioarray, 'duration', 'Course Duration', array(' '), false);
                        
            // $templaterules['hours'][] = array( get_string('numbersonlyhours'), 'numeric', null, 'client');
            // $templaterules['min'][] = array( get_string('numbersonlyminutes'), 'numeric', null, 'client');
            // $mform->addGroupRule('duration', $templaterules);

            /*$mform->addElement('text', 'open_points', get_string('points','local_courses'));
            $mform->addHelpButton('open_points', 'open_pointscourse', 'local_courses');
            $mform->setType('open_points', PARAM_INT);

            $mform->addElement('text',  'open_cost', 'Cost');
            $mform->addHelpButton('open_cost', 'open_costcourse', 'local_courses');
            $mform->setType('open_cost', PARAM_INT);
            $mform->addRule('open_cost', get_string('numeric','local_users'), 'numeric', null, 'client');*/

            $skill_categories = $DB->get_records_menu('local_skill_categories',array('costcenterid'=>$course->open_costcenterid),'name','id,name');

            $skill_catslist = array(null=>get_string('select_skillcategory','local_courses')) + $skill_categories; 

            $mform->addElement('select', 'open_skillcategory',get_string('skillcategory','local_courses'), $skill_catslist, array());

            $skillselect = array(0 => get_string('select_skill','local_courses'));
            $skills = $DB->get_field('course', 'open_skill', array('id' => $course->id));
            if($skills){
                $sql = "SELECT id, name
                        FROM {local_skill}
                        WHERE id IN ($skills)";
                $selectedskills = $DB->get_records_sql_menu($sql);
                $skillselect = $skillselect + $selectedskills;
            }
            $select = $mform->addElement('select',  'open_skill', 'Skill', $skillselect);
            $mform->addHelpButton('open_skill', 'open_skillcourse', 'local_courses');
            $select->setMultiple(true);
            $mform->setType('open_skill', PARAM_RAW);

            /*$levelselect = array(0 => 'Select Level');
            $level ="SELECT cl.name FROM {local_course_levels} as cl 
                    JOIN {local_costcenter} as c ON c.id = cl.costcenterid";
            // $levels = $DB->get_records_sql_menu("SELECT id, name FROM {local_course_levels} WHERE costcenterid={$this->course->open_costcenterid} order by sortorder asc");
            $levels = $DB->get_records_menu('local_course_levels',  array('costcenterid' => $this->course->open_costcenterid),'sortorder', 'id,name');
            if(!empty($levels)){
                $levelselect = $levelselect+$levels;
            }
            $mform->addElement('select',  'open_level', 'Level', $levelselect);
            $mform->addHelpButton('open_level', 'open_levelcourse', 'local_courses');
            $mform->setType('open_level', PARAM_INT);

            $mform->addElement('date_selector', 'startdate', get_string('startdate','local_courses'),
             array());
            $mform->addHelpButton('startdate', 'startdate');
		
			$mform->addElement('date_selector', 'enddate', get_string('enddate','local_courses'), array('optional' => false));
            $mform->addHelpButton('enddate', 'enddate');*/
        }
        $mform->closeHeaderBefore('buttonar');
        if(empty($course)&&$course_id>0){
                 $course = get_course($course_id);
            }
      $this->set_data($course);
		$mform->disable_form_change_checker();
        // Finally set the current form data
        // if(empty($course)&&$course_id>0){
        //      $course = get_course($course_id);
        // }
        // if(!empty($course->open_certificateid)){
        //     $course->map_certificate = 1;
        // }
        // if(!empty($course->open_skill)){
        //     $courseskills = explode(',',$course->open_skill);
        //     $course->open_skill = $courseskills;
        //     $skillinfo = $DB->get_record('local_skill',array('id' =>$courseskills[0]),'id,category,name');
        //     $course->open_skillcategory = $skillinfo->category;
        // }
        // $courseduration = $DB->get_record('course', array('id'=>$course->id));
        //comment <Revathi>
        //  $course->hours = floor($course->duration / 3600);
        // $course->min = (($course->duration / 60) ) % 60;
        //End comment <Revathi>
        
		// $mform->disable_form_change_checker();
    }
     /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;
       
        $errors = parent::validation($data, $files);
		$form_data = data_submitted();
  
	    // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }  
		 if (isset($data['startdate']) && $data['startdate']
                && isset($data['enddate']) && $data['enddate']) {
            if ($data['enddate'] < $data['startdate']) {
                $errors['enddate'] = get_string('nosameenddate', 'local_courses');
            }
        }

     
        if (isset($data['category']) && $data['form_status'] == 0){
            if(empty($data['category'])){
                $errors['category'] = get_string('err_category', 'local_courses');
            }
        }

        if ($data['map_certificate'] == 1 && empty($data['open_certificateid'])){
            $errors['open_certificateid'] = get_string('err_certificate', 'local_courses');
        }
        if (isset($data['open_costcenterid']) && $data['form_status'] == 0){
            if($data['open_costcenterid'] == 0){
                $errors['open_costcenterid'] = get_string('pleaseselectorganization', 'local_courses');
            }
        }
        //comment <Revathi>
       
        $hour=$data['hours'];
        $min=$data['min'];
       if($data['form_status']==0){
            if(!empty($min)){
            if (strlen((string) $min)!="2"){
                $errors['duration'] = 'Minutes accepts two digits and It accepts up to 59 Minutes';
            }elseif ($min>59){
                $errors['duration'] = 'It accepts up to 59 Minutes only';
            }     
          }
            if( (empty($hour) || $hour == 0) && (empty($min) || $min == 0) ){
             $errors['duration'] = 'Hours cannot be empty and it will not accepts 0';
            }
            if(!empty($hour) && (isset($min) && $min =='')){
               $errors['duration'] = 'Minutes accepts two digits and It accepts up to 59 Minutes'; 
            }
            if ($data['open_courseprovider'] == null) {
                $errors['open_courseprovider'] = 'Please select the Course provider';
            }
       }
         //end comment <Revathi>

        if($data['expirydateenable'] == 1 && ($data['expirydate'] < $data['startdate'])){
            $errors['expirydate'] = get_string('expirydate_error','local_courses');
        } 

        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));
      
        return $errors;
    }
}
