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
 * @package   Bizlms
 * @subpackage  classroom
 * @author eabyas  <info@eabyas.in>
**/

namespace local_classroom\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
use context_system;
use local_classroom\local\querylib;
use moodleform;
use core_component;

class classroom_form extends moodleform {
    public $formstatus;
    public function __construct($action = null, $customdata = null, $method = 'post',
        $target = '', $attributes = null, $editable = true, $formdata = null) {
        $this->formstatus = array(
            'manage_classroom' => get_string('manage_classroom', 'local_classroom'),
            'location_date' => get_string('location_date', 'local_classroom'),
            'classroom_misc' => get_string('assign_course', 'local_classroom'),
            'target_audience' => get_string('target_audience', 'local_users'),
            'prerequisites' => get_string('prerequisites', 'local_classroom')
        );
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $formdata);
    }

    public function definition() {
        global $CFG, $USER, $PAGE, $DB;
        $querieslib = new querylib();
        $mform = &$this->_form;
        $renderer = $PAGE->get_renderer('local_classroom');
        $context = context_system::instance();
        $formstatus = $this->_customdata['form_status'];
        $id = $this->_customdata['id'] > 0 ? $this->_customdata['id'] : 0;
        $formheaders = array_keys($this->formstatus);
        $formheader = $formheaders[$formstatus];


        $mform->addElement('hidden', 'id', $id, array('id' => 'classroomid'));
        $mform->setType('id', PARAM_INT);

        $core_component = new core_component();
        if ($formstatus == 0) {
            $querieslib = new querylib();
            $mform->addElement('text', 'name', get_string('classroom_name', 'local_classroom'), array());
            if (!empty($CFG->formatstringstriptags)) {
                $mform->setType('name', PARAM_TEXT);
            } else {
                $mform->setType('name', PARAM_CLEANHTML);
            }
            $mform->addRule('name', null, 'required', null, 'client');

            if (is_siteadmin() || ((has_capability('local/classroom:manage_multiorganizations', context_system::instance()) ||has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) ) {

                $costcenters = array();
                $costcenterslist = $this->_ajaxformdata['costcenter'];
                if (!empty($costcenterslist)) {
                    $costcenterslist = $costcenterslist;
                } else if ($id > 0) {
                    $costcenterslistsql = "SELECT cc.id
                                             FROM {local_costcenter} cc
                                             JOIN {local_classroom} c ON c.costcenter = cc.id
                                             AND cc.parentid = 0 AND cc.visible = 1 AND
                                             c.id = :classroomid";
                    $costcenterslist = $DB->get_field_sql($costcenterslistsql, array('classroomid' => $id));
                } else {
                     $costcenterslistsql = "SELECT cc.id
                                             FROM {local_costcenter} cc
                                             WHERE cc.parentid = 0 AND cc.visible = 1";
                    $costcenterslist = $DB->get_field_sql($costcenterslistsql, array('classroomid' => $id));

                }
              /*  if (!empty($costcenterslist)) {
                    $costcenterslist = $DB->get_records_menu('local_costcenter',
                        array('visible' => 1, 'parentid' => 0, 'id' => $costcenterslist),
                        'id', 'id, fullname');
                    $costcenters = array(null => get_string('select_costcenter',
                        'local_classroom')) + $costcenterslist;
                }*/


                $options = array(
                    'ajax' => 'local_classroom/form-options-selector',
                    'data-contextid' => $context->id,
                    'data-action' => 'classroom_costcenter_selector',
                    'data-options' => json_encode(array('id' => $id, 'depth' => 1, 'parnetid' => 0)),
                    'class' => 'organizationselect',
                    'data-class' => 'organizationselect'
                );

                //$mform->addElement('autocomplete', 'costcenter',
                  //  get_string('costcenter', 'local_classroom'), $costcenters, $options);
                $mform->addElement('hidden', 'costcenter', $costcenterslist,
                    array( 'data-class' => 'organizationselect'));
                //$mform->addRule('costcenter', get_string('errororganization', 'local_users'), 'required', null, 'client');
               // $mform->addRule('costcenter', null, 'required', null, 'client');
                $mform->setType('costcenter', PARAM_INT);
            } else {
                $mform->addElement('hidden', 'costcenter', $USER->open_costcenterid,
                    array( 'data-class' => 'organizationselect'));//get_string('costcenter', 'local_classroom')
                $mform->setType('costcenter', PARAM_INT);
                // $mform->setDefault('costcenter', $USER->open_costcenterid);
            }

            // $type = array(1 => get_string('classroom', 'local_classroom'),
            //     2 => get_string('learningplan', 'local_classroom'),
            //     3 => get_string('certification', 'local_classroom'),
            // );
            // $mform->addElement('hidden', 'type', get_string('type', 'local_classroom'), $type,
            //  array());
            // $mform->addRule('type', null, 'required', null, 'client');
            // $mform->setType('type', PARAM_INT);

             //*OPEN LMSOL-333 Employee_Search_Classroom*//
             
                $manageapproval = array();
                $manageapproval[] = $mform->createElement('radio', 'approvalreqd', '', get_string('yes'), 1, $attributes);
                $manageapproval[] = $mform->createElement('radio', 'approvalreqd', '', get_string('no'), 0, $attributes);
                $mform->addGroup($manageapproval, 'approvalreqd',
                    get_string('need_manage_approval', 'local_classroom'),
                    array('&nbsp;&nbsp;'), false);
                $mform->addHelpButton('approvalreqd','need_manager_approval','local_classroom');
            
            // $visible_options = array(1 => get_string('show'), 0 => get_string('hide'));
            // $mform->addElement('select', 'visible', get_string('visible'), $visible_options, array());
            // $mform->setDefault('visible', 1);
            $mform->addElement('hidden',  'visible',  1);
            $mform->setType('visible', PARAM_INT);
            //*OPEN LMSOL-333 Employee_Search_Classroom*//

            $allowmultisession = array();
            $allowmultisession[] = $mform->createElement('radio', 'allow_multi_session', '',
             get_string('induction', 'local_classroom'), 0, $attributes);
            $allowmultisession[] = $mform->createElement('radio', 'allow_multi_session', '',
             get_string('technical', 'local_classroom'), 1, $attributes);
            $mform->addGroup($allowmultisession, 'radioar',
                get_string('allow_multi_session', 'local_classroom'), array('&nbsp;&nbsp;'),
                 false);
            $mform->addHelpButton('radioar','allow_multiple_sessions','local_classroom');
            //$mform->addRule('radioar', null, 'required');

            $mform->addElement('text', 'capacity', get_string('capacity', 'local_classroom'),
             array());
            $mform->setType('capacity', PARAM_RAW);
            $mform->addHelpButton('capacity','capacity_check','local_classroom');

            $mform->addElement('text', 'open_url',  get_string('open_url', 'local_classroom'));
            $mform->addHelpButton('open_url', 'open_url','local_classroom');
            $mform->setType('open_url', PARAM_URL);
            
            if (is_siteadmin() || ((has_capability('local/classroom:viewwaitinglist_userstab', context_system::instance())))){
                    $allowwaitinglistusers = array();
                    $allowwaitinglistusers[] = $mform->createElement('radio', 'allow_waitinglistusers', '',
                     get_string('yes'), 1, $attributes);
                    $allowwaitinglistusers[] = $mform->createElement('radio', 'allow_waitinglistusers', '',
                     get_string('no'), 0, $attributes);
                    $mform->addGroup($allowwaitinglistusers, 'allow_waitinglistusers',
                        get_string('allow_waitinglistusers', 'local_classroom'), array('&nbsp;&nbsp;'),
                         false);
                   $mform->addHelpButton('allow_waitinglistusers','allowuserswaitinglist','local_classroom');
             }
           
           /* $mform->addElement('text', 'open_points', get_string('points','local_classroom'));
            $mform->addHelpButton('open_points', 'open_pointsclassroom', 'local_classroom');
            $mform->setType('open_points', PARAM_INT);*/

            $trainers = array();
            $trainerslist = $this->_ajaxformdata['trainers'];
            if (!empty($trainerslist)) {
                $trainerslist = $trainerslist;
            } else if ($id > 0) {
                $trainerslist = $DB->get_records_menu('local_classroom_trainers',
                    array('classroomid' => $id), 'id', 'id, trainerid');
            }
            if (!empty($trainerslist)) {
                $trainers = $querieslib->get_user_department_trainerslist(false, false,
                    $trainerslist);
            }
            $options = array(
                'ajax' => 'local_classroom/form-options-selector',
                'multiple' => true,
                'data-contextid' => $context->id,
                'data-action' => 'classroom_trainer_selector',
                'data-options' => json_encode(array('id' => $id,'organizationselect' => 'organizationselect')),
                'class' => 'trainerselect',
            );
             
            $mform->addElement('autocomplete', 'trainers', get_string('trainers', 'local_classroom'), $trainers, $options);
            $mform->addHelpButton('trainers','traning','local_classroom');


            $mform->addElement('date_time_selector', 'startdate', get_string('startdate',
                'local_classroom'), array('optional' => false));
            $mform->addRule('startdate', null, 'required', null, 'client');

            $mform->addElement('date_time_selector', 'enddate', get_string('enddate',
                'local_classroom'), array('optional' => false));
            $mform->addRule('enddate', null, 'required', null, 'client');
            // tags
           // $mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'classroom', 'component' => 'local_classroom'));

            //certificate
            $certificate_plugin_exist = $core_component::get_plugin_directory('local', 'certificates');
            if($certificate_plugin_exist){
                $checkboxes = array();
                $checkboxes[] = $mform->createElement('advcheckbox', 'map_certificate', null, '', array(),array(0,1));
                $mform->addGroup($checkboxes, 'map_certificate', get_string('add_certificate', 'local_classroom'), array(' '), false);
                $mform->addHelpButton('map_certificate', 'add_certificate', 'local_classroom');


                $select = array(null => get_string('select_certificate','local_classroom'));

                if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context)){
                    $cert_templates = $DB->get_records_menu('local_certificate',array(),'name', 'id,name');
                }else{
                    $cert_templates = $DB->get_records_menu('local_certificate',array('costcenter'=>$USER->open_costcenterid),'name', 'id,name');
                }
                $certificateslist = $select + $cert_templates;

                $mform->addElement('select',  'certificateid', get_string('certificate_template','local_classroom'), $certificateslist);
                $mform->addHelpButton('certificateid', 'certificate_template', 'local_classroom');
                $mform->setType('certificateid', PARAM_INT);
                $mform->hideIf('certificateid', 'map_certificate', 'neq', 1);
            }
        } else if ($formstatus == 1) {

            $allowmultisession = array();
            $allowmultisession[] = $mform->createElement('radio', 'institute_type', '',
                get_string('internal', 'local_classroom'), 1, $attributes);
            $allowmultisession[] = $mform->createElement('radio', 'institute_type', '',
                get_string('external', 'local_classroom'), 2, $attributes);
            $mform->addGroup($allowmultisession, 'radioar', get_string('clrm_location_type',
                'local_classroom'), array(' '), false);
              $mform->addHelpButton('radioar', 'classroom_locationtype', 'local_classroom');


            $classroomlocations =  array(null => get_string('select_institutions',
                        'local_classroom'));
            $instituteid = $this->_ajaxformdata['instituteid'];

            if (!empty($instituteid)) {
                $instituteid = $instituteid;
            } else if ($id > 0) {
                $instituteid = $DB->get_field('local_classroom', 'instituteid',
                    array('id' => $id));
            }
            if (!empty($instituteid)) {
                $classroomlocations =$DB->get_records_menu('local_location_institutes',
                 array('id' => $instituteid), 'id', 'id, fullname');

            }
            $options = array(
                'ajax' => 'local_classroom/form-options-selector',
                'data-contextid' => $context->id,
                'data-action' => 'classroom_institute_selector',
                'data-options' => json_encode(array('id' => $id)),
                'data-institute_type' => 'institute_type'
            );

            $mform->addElement('autocomplete', 'instituteid', get_string('classroom_location',
             'local_classroom'),$classroomlocations, $options);
            //$mform->addRule('instituteid', null, 'required', null, 'client');

            $mform->addElement('date_time_selector', 'nomination_startdate',
                get_string('nomination_startdate', 'local_classroom'),
                array('optional' => true));

            $mform->addElement('date_time_selector', 'nomination_enddate',
             get_string('nomination_enddate', 'local_classroom'),
             array('optional' => true));

        } else if ($formstatus == 2) {
            $mform->addElement('filepicker', 'classroomlogo', get_string('classroomlogo','local_classroom'), null,
                array('maxbytes' => 2048000, 'accepted_types' => '.jpg'));
            $mform->addHelpButton('classroomlogo', 'bannerimage', 'local_classroom');

            $editoroptions = array(
                'noclean' => false,
                'autosave' => false
            );
            $mform->addElement('editor', 'cr_description', get_string('description',
                'local_classroom'), null, $editoroptions);
            $mform->setType('cr_description', PARAM_RAW);
            $mform->addHelpButton('cr_description', 'description', 'local_classroom');

        }else if ($formstatus == 3) {
            // OL-1042 Add Target Audience to Classrooms//
            // ((has_capability('local/classroom:manage_multiorganizations', context_system::instance()) && has_capability('local/costcenter:manage_multiorganizations', context_system::instance()))) &&
            if (is_siteadmin() || 
                (has_capability('local/classroom:manageclassroom', $context) 
                    && has_capability('local/classroom:manageclassroom', $context)
                    && !has_capability('local/classroom:manage_owndepartments',$context) 
                    && !has_capability('local/costcenter:manage_owndepartments',$context))) {
                $departments = array();
                $departmentslist = $this->_ajaxformdata['department'];

                $params = array();
                if (!empty($departmentslist)) {
                    $departmentslist = $departmentslist;
                } else if ($id > 0) {
                    $departmentlist = $DB->get_field('local_classroom', 'department', array('id' => $id));
                    $departmentslist = explode(', ', $departmentlist);
                }
                if (!empty($departmentslist)) {
                    if (is_array($departmentslist)){
                        $departmentslist=implode(',',$departmentslist);
                    }
                    $organisation = $DB->get_field('local_classroom', 'costcenter', array('id' => $id));
                    $params['visible'] = 1;
                    $params['depth'] = 2;
                    $params['parentid'] = $organisation;
                    $departmentlistsql = "SELECT id, fullname
                                            FROM {local_costcenter}
                                           WHERE visible = :visible AND depth = :depth AND parentid = :parentid";
                    if(!empty($departmentslist)) {
                        $departmentlistsql .= " AND id in ($departmentslist)";
                    }
                    $departmentlist = $DB->get_records_sql_menu($departmentlistsql, $params);
                    $departments = array(-1 => get_string('all')) + $departmentlist;
                }

                $options = array(
                    'ajax' => 'local_classroom/form-options-selector',
                    'multiple' => False,
                    'data-contextid' => $context->id,
                    'data-action' => 'classroom_costcenter_selector',
                    'data-options' => json_encode(array('id' => $id, 'depth' => 2,
                        'organizationselect' => '.organizationselect', 'department' => true,
                    'organizationselect' => 'organizationselect')),
                    'class' => 'departmentselect'
                );

                $mform->addElement('autocomplete', 'department', get_string('department',
                    'local_classroom'), $departments, $options);
                $mform->setType('department', PARAM_RAW);
                
     //        }elseif (is_siteadmin() || ((!has_capability('local/classroom:manage_multiorganizations', context_system::instance()) &&! has_capability('local/costcenter:manage_multiorganizations', context_system::instance())) && has_capability('local/classroom:manageclassroom', $context)&&(has_capability('local/classroom:manage_owndepartments',$context)||has_capability('local/costcenter:manage_owndepartments',$context)))) {
            
     //            	$options = array(
					// 	'class' => 'department_select'
					// );
					// $costcenter = $DB->get_field('local_classroom','costcenter',array('id'=>$id));
					// $costcenter_department = $DB->get_field('local_classroom','department',array('id'=>$id));
                   
					// if(empty($costcenter_department)){
					// 	$departmentslist=array($USER->open_departmentid=>$USER->open_departmentid);
					// }else{
					// 	if($costcenter_department!='-1'){
					// 		$departmentslist=explode(',',$costcenter_department);
					// 		$departmentslist=array_combine($departmentslist,$departmentslist);
					// 	}else{
     //                        $departmentslist=array('-1'=>'-1');
     //                    }
					
					// }
					
					// $mform->addElement('autocomplete', 'department', get_string('department'),$departmentslist,$options);
					// if(empty($costcenter_department)){
					//  $mform->setDefault('department', $USER->open_departmentid);
					// }
     //                if($costcenter_department=='-1'){
					//  $mform->setDefault('department',-1);
					// }
            }else{
                // $departmentlist = $DB->get_field('local_classroom', 'department', array('id' => $id)); echo $departmentlist;
                $mform->addElement('hidden', 'department', $USER->open_departmentid, array('id' => 'id_department'));//, array('id' => 'id_department')
                $mform->setConstant('department', $USER->open_departmentid);
                $mform->setType('department', PARAM_RAW);
            }
            /*if(is_siteadmin() || has_capability('local/classroom:manageclassroom', $context)){
                $departments = array();
                $subdepartment = $this->_ajaxformdata['subdepartment'];

                $params = array();
                if (!empty($subdepartment)) {
                    $subdepartmentslist = $subdepartment;
                } else if ($id > 0) {
                    $subdepartmentlist = $DB->get_field('local_classroom', 'subdepartment', array('id' => $id));
                    $subdepartmentslist = explode(', ', $subdepartmentlist);
                }
                if (!empty($subdepartmentslist)) {
                    if (is_array($subdepartmentslist)){
                        $subdepartmentslist=implode(',',$subdepartmentslist);
                    }
                    // $organisation = $DB->get_field('local_classroom', 'costcenter', array('id' => $id));
                    $departments = $DB->get_field('local_classroom', 'department', array('id' => $id));
                    
                    $subdepartmentlistsql = "SELECT id, fullname
                                            FROM {local_costcenter}
                                           WHERE 1 = 1 ";
                    if(!empty($subdepartmentslist)) {
                        $arr_subdepartmentslist = explode(',', $subdepartmentslist);
                        list($subsql, $subparam) = $DB->get_in_or_equal($arr_subdepartmentslist, SQL_PARAMS_NAMED);
                        $subdepartmentlistsql .= " AND id $subsql ";
                        $params = $params + $subparam;
                    }else{
                        $subdepartmentlistsql .= " AND visible = :visible AND depth = :depth 
                        AND parentid IN (:parentid)";
                        $params['visible'] = 1;
                        $params['depth'] = 3;
                        $params['parentid'] = $departments;
                    }
                    
                    $subdepartmentlist = $DB->get_records_sql_menu($subdepartmentlistsql, $params);
                    $subdepartments = array(-1 => get_string('all')) + $subdepartmentlist;
                }else{
                    $subdepartments = array(-1 => get_string('all'));
                }

                $options = array(
                    'ajax' => 'local_classroom/form-options-selector',
                    'multiple' => False,
                    'data-contextid' => $context->id,
                    'data-action' => 'classroom_subdepartment_selector',
                    'data-options' => json_encode(array('id' => $id, 'depth' => 3,
                        'organizationselect' => '.organizationselect', 'subdepartment' => true)),
                    'class' => 'subdepartmentselect'
                );
                //'organizationselect' => 'organizationselect'

                $mform->addElement('autocomplete', 'subdepartment', get_string('subdepartment',
                    'local_costcenter'), $subdepartments, $options);
                $mform->setType('subdepartment', PARAM_INT);
            }*/
			$users_plugin_exist = $core_component::get_plugin_directory('local','users');
			if ($users_plugin_exist) {
				require_once($CFG->dirroot . '/local/users/lib.php');
				$functionname ='globaltargetaudience_elementlist';
				 if(function_exists($functionname)) {
                    //$functionname($mform,array('group','hrmsrole','designation','location'));
                    $functionname($mform,array('location','grade'));
                    // $mform->addRule('location', null, 'required', null, 'client');

                }
			}
 
          // OL-1042 Add Target Audience to Classrooms//
        }else if($formstatus == 4){
            // $location_details[null]=get_string('all');
            // $location_sql = "SELECT u.city, u.city AS locationvalue FROM {user} AS u WHERE u.id > 2 $main_sql AND u.city IS NOT NULL GROUP BY u.city    ";
             $llp_sql = "SELECT ll.id,ll.fullname
                                FROM {course} as ll
                                 WHERE ll.visible = 1 "; 
                // if($id){
                //     $llp_sql .= " AND ll.id != {$id}";
                // }
                // $lp_ids = $DB->get_records_sql_menu($llp_sql);
            $location_details = $DB->get_records_sql_menu($llp_sql,$params);
            $selectlocation = $mform->addElement('autocomplete',  'open_prerequisites',  get_string('prerequisite_courses', 'local_classroom'),$location_details);
            $mform->setType('open_prerequisites', PARAM_RAW);
             $mform->addHelpButton('open_prerequisites','prerequisites','local_classroom');
            $selectlocation->setMultiple(true);
        }
        $mform->disable_form_change_checker();
    }
    public function validation($data, $files) {
        global $CFG, $DB, $USER;

        $errors = parent::validation($data, $files);

         $data_db = $DB->get_record_sql('SELECT id,startdate,enddate,capacity FROM {local_classroom}
                WHERE id = ' . $data['id']);
        if (isset($data['startdate']) && $data['startdate'] &&
                isset($data['enddate']) && $data['enddate']) {
            if ($data['enddate'] <= $data['startdate']) {
                $errors['enddate'] = get_string('enddateerror', 'local_classroom');
            }
        }
        
       
        if(isset($data['name']) &&empty(trim($data['name']))){
            $errors['name'] = get_string('valnamerequired','local_classroom');
        }
        
        if(isset($data['institute_type'])&&$data['institute_type']!=0&&$data['instituteid']==0){
            $errors['instituteid'] = get_string('vallocationrequired','local_classroom');
        }elseif(isset($data['institute_type'])&&$data['institute_type']!=0&&$data['instituteid']!=0){
            $institutessql = "SELECT id
                                FROM {local_location_institutes}
                               WHERE institute_type = :institute_type and id=:instituteid";

            $params['institute_type'] = $data['institute_type'];           
            $params['instituteid'] = $data['instituteid'];  

            $institutes = $DB->record_exists_sql($institutessql, $params);
            if(!$institutes){
                $errors['instituteid'] = get_string('vallocation','local_classroom');
            }
        }
        
	    if(isset($data['capacity']) &&!empty(trim($data['capacity']))){
	    		if(!is_numeric(trim($data['capacity']))){
	    			$errors['capacity'] = get_string('numeric','local_classroom');
	    		}
                if(is_numeric(trim($data['capacity']))&&trim($data['capacity'])<0){
	    			$errors['capacity'] = get_string('positive_numeric','local_classroom');
	    		}
		}
        
		
        if($data['id']>0){
            $countfields = "SELECT COUNT(DISTINCT u.id) ";
            $params['classroomid'] = $data['id'];
            $params['confirmed'] = 1;
            $params['suspended'] = 0;
            $params['deleted'] = 0;
            $sql = " FROM {user} AS u
                    JOIN {local_classroom_users} AS cu ON cu.userid = u.id
                     WHERE u.id > 2 AND u.confirmed = :confirmed AND u.suspended = :suspended
                        AND u.deleted = :deleted AND cu.classroomid = :classroomid";
            $allocatedseats     =$DB->count_records_sql($countfields . $sql, $params);

            //$allocatedseats=$DB->count_records('local_classroom_users',array('classroomid'=>$data['id'])) ;
            if($data['capacity']!=NULL&&trim($data['capacity'])<$allocatedseats){
                        $errors['capacity'] = get_string('capacity_enroll_check','local_classroom');
            }
        }
        if ((isset($data['nomination_startdate']) && $data['nomination_startdate'])||
                 (isset($data['nomination_enddate']) && $data['nomination_enddate'])&&$data['id']>0) {

            $data['startdate']= $data_db->startdate;
            $data['enddate']= $data_db->enddate;

            if ($data['nomination_startdate'] > $data['startdate']) {
                $errors['nomination_startdate'] = get_string('nomination_startdateerror', 'local_classroom');
            }
            if ($data['nomination_enddate'] > $data['startdate']) {
                $errors['nomination_enddate'] = get_string('nomination_enddateerror', 'local_classroom');
            }
            elseif ($data['nomination_enddate'] <= $data['nomination_startdate']&&$data['nomination_enddate']>0&&$data['nomination_startdate']>0) {
                $errors['nomination_enddate'] = get_string('nomination_error', 'local_classroom');
            }
        }
        if($data['allow_waitinglistusers']>0 && empty(trim($data['capacity']))){
             $errors['capacity'] = get_string('capacity_waiting_check','local_classroom');
        }

        if ($data['map_certificate'] == 1 && empty($data['certificateid'])){
            $errors['certificateid'] = get_string('err_certificate', 'local_classroom');
        }

        return $errors;
    }

    public function set_data($components) {
        global $DB;
        $context = context_system::instance();
        if ($components->form_status == 0) {
            $data = $DB->get_record('local_classroom', array('id' => $components->id));
            //populate tags
            //$data->tags = \local_tags_tag::get_item_tags_array('local_classroom', 'classroom', $components->id);
            $params = array();
            $params['classroomid'] = $components->id;

            $sql = " SELECT ct.trainerid AS crtrid, ct.trainerid
                      FROM {local_classroom} c
                      JOIN {local_classroom_trainers} ct ON ct.classroomid = c.id
                     WHERE c.id = :classroomid";
            $trainers = $DB->get_records_sql_menu($sql, $params);
           // $data->trainers = $trainers; //comment <revathi>
            if(!empty($data->certificateid)){
                $data->map_certificate = 1;
            }

       } else if ($components->form_status == 1) {
         
            $data = $DB->get_record_sql('SELECT id, institute_type, instituteid,
                nomination_startdate, nomination_enddate,startdate,enddate FROM {local_classroom}
                WHERE id = ' . $components->id);
            if($data->instituteid==0){
                $data->instituteid=null;
            }

        } else if ($components->form_status == 2) {
            $data = $DB->get_record_sql('SELECT id, description, approvalreqd,
             classroomlogo, nomination_startdate, nomination_enddate
             FROM {local_classroom} WHERE id = ' . $components->id);
            $data->cr_description['text'] = $data->description;
            $draftitemid = file_get_submitted_draft_itemid('classroomlogo');
            file_prepare_draft_area($draftitemid, $context->id, 'local_classroom', 'classroomlogo', $data->classroomlogo, null);
            $data->classroomlogo = $draftitemid;
        }else if ($components->form_status == 3) {
             // OL-1042 Add Target Audience to Classrooms//
          /*  $data = $DB->get_record_sql('SELECT id, open_group, subdepartment,open_hrmsrole,
             open_designation, open_location,department
             FROM {local_classroom} WHERE id = ' . $components->id);*/
             $data = $DB->get_record_sql('SELECT id, open_location,open_grade,department
             FROM {local_classroom} WHERE id = ' . $components->id);
             
            // if($data->subdepartment == -1){
            //     // $data->subdepartment = null;   
            // }else{
            $data->department = (!empty($data->department)) ? array_diff(explode(',',$data->department), array('')) :array(NULL=>NULL);
          /*  $data->subdepartment = (!empty($data->subdepartment)) ? array_diff(explode(',',$data->subdepartment), array('')) :array(NULL=>NULL);
            // }
            $data->open_group =(!empty($data->open_group)) ? array_diff(explode(',',$data->open_group), array('')) :array(NULL=>NULL);
            $data->open_hrmsrole =(!empty($data->open_hrmsrole)) ? array_diff(explode(',',$data->open_hrmsrole), array('')) :array(NULL=>NULL);
            $data->open_designation =(!empty($data->open_designation)) ? array_diff(explode(',',$data->open_designation), array('')) :array(NULL=>NULL);*/
            $data->open_location =(!empty($data->open_location)) ? array_diff(explode(',',$data->open_location), array('')) :array(NULL=>NULL);
            $data->open_grade =(!empty($data->open_grade)) ? array_diff(explode(',',$data->open_grade), array('')) :array(NULL=>NULL);
             // OL-1042 Add Target Audience to Classrooms//
        }else if ($components->form_status == 4) {
                $data = $DB->get_record_sql('SELECT id, open_prerequisites
             FROM {local_classroom} WHERE id = ' . $components->id);
            $data->open_prerequisites =(!empty($data->open_prerequisites)) ? array_diff(explode(',',$data->open_prerequisites), array('')) :array(NULL=>NULL);
             // OL-1042 Add Target Audience to Classrooms//
        }
        
        parent::set_data($data);
    }
}
