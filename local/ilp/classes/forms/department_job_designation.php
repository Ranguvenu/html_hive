<?php 
namespace local_ilp\forms;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once $CFG->dirroot.'/local/filterclass.php';

use moodleform;
use custom_filter;
use context_system;

class department_job_designation extends moodleform {

 public function definition() {
        global $DB,$CFG, $PAGE,$USER;

        $mform = $this->_form;
        $customdata = $this->_customdata['data'];
        $data = $customdata['filterdata'];
        //$mform->addElement('header', 'createprogramme',get_string('cancelmonth','local_trainingcalendar'));
        
        //$mform->addElement('hidden', 'id', 'id');
        //$mform->setType('id', PARAM_ALPHANUM);
        
        /*$select_zone =$mform->addElement('select','zone','Zone',get_zone_filter(),array('class'=>'zone','data-placeholder'=>'--Select Zone--'));
        $mform->setType('zone',PARAM_RAW);
        $select_zone->setMultiple(true);
        
        $select_costcenter = $mform->addElement('select','costcenter','Department',get_costcenter_filter(),array('class'=>'costcenter','data-placeholder'=>'--Select Department--'));
        $mform->setType('costcenter',PARAM_RAW);
        $select_costcenter->setMultiple(true);
        
        
        $select_category = $mform->addElement('select','category','Category', get_category_filter(),array('class'=>'category','data-placeholder'=>'--Select Category--'));
        $mform->setType('category',PARAM_RAW);
        $select_category->setMultiple(true);
        
        $select_jobfunction =$mform->addElement('select','jobfunction','Jobfunction',get_skilletlist_filter(),array('class'=>'jobfunction','data-placeholder'=>'--Select Job --'));
        $mform->setType('jobfunction',PARAM_RAW);
        $select_jobfunction->setMultiple(true);
        */
        $costcenter=$DB->get_field('user','open_costcenterid',array('id'=>$USER->id));
        $filter_form=new custom_filter();
        $functions = array();
        //==========Employee ID=========
        if(!empty($data)){
          if(!empty($data->empnumber) && is_array($data->empnumber)){
            $employee_id=$filter_form->get_allusers_employeeids($costcenter,$data->empnumber);
          }
        }else{
         $employee_id = array();
        }
        $select_empnumber = $mform->addElement('autocomplete','empnumber','Employee ID',$employee_id,array('class'=>'empnumber','data-placeholder'=>'--Select Employee ID--'));
        $mform->setType('empnumber',PARAM_RAW);
        $select_empnumber->setMultiple(true);
        
        //==============Email===========
        if(!empty($data)){
            if(!empty($data->email) && is_array($data->email)){
                $email = $filter_form->get_all_users_emails($costcenter,$data->email);
            }
        }else{
         $email= array();
        }
        $select_email = $mform->addElement('autocomplete','email','Email Address',$email,array('class'=>'email','data-placeholder'=>'--Select Email--'));
        $mform->setType('email',PARAM_RAW);
        $select_email->setMultiple(true);
        
        //========Organization============
        $systemcontext = context_system::instance();
        if (is_siteadmin($USER) || has_capability('local/costcenter:assign_multiple_departments_manage', $systemcontext)) {
         
         if(!empty($data)){
            if(!empty($data->organization) && is_array($data->organization)){
                $organization=$filter_form->get_allcostcenters($data->organization);
            }
         }else{
          $organization= array();
         }
         $select_organization = $mform->addElement('autocomplete','organization','Organization',$organization,array('class'=>'email','data-placeholder'=>'--Select Organization--'));
         $mform->setType('organization',PARAM_RAW);
         $select_organization->setMultiple(true);
        }
          
         if(!empty($data)){
            if(!empty($data->band) && is_array($data->band)){
                $band=$filter_form->get_allband_users($costcenter,$data->band);
            }
         }else{
          $band= array();
         }
        $select_band =$mform->addElement('autocomplete','band','Band',$band,array('class'=>'designation','data-placeholder'=>'--Select Band--'));
        $mform->setType('band',PARAM_RAW);
        $select_band->setMultiple(true);
  
         if(!empty($data)){
             if(!empty($data->department) && is_array($data->department)){
                 $department=$filter_form->get_alldepartments($costcenter,$data->department);
             }
         }else{
          $department= array();
         }
        $dept = $mform->addElement('autocomplete', 'department', get_string('department', 'local_costcenter'),  $department, array('class' => 'department','data-placeholder'=>'--Select Department--'));
        $mform->setType('department', PARAM_INT);
        $dept->setMultiple(true);
        
        if(!empty($data)){
            if(!empty($data->subdepartment) && is_array($data->subdepartment)){
                $sub_department=$filter_form->get_allsubdepartments($costcenter,$data->subdepartment);
            }
        }else{
          $sub_department= array();
         }
        $sub_dept = $mform->addElement('autocomplete', 'subdepartment', 'Sub Department',  $sub_department, array('class' => 'subdepartment','data-placeholder'=>'--Select Sub Department--'));
        $mform->setType('subdepartment', PARAM_INT);
        $sub_dept->setMultiple(true);
        
        if(!empty($data)){
            if(!empty($data->sub_sub_department) && is_array($data->sub_sub_department)){
                $sub_sub_department=$filter_form->get_allsub_sub_departments($costcenter,$data->sub_sub_department);
            }
        }else{
          $sub_sub_department= array();
         }
        $sub_sub_dept = $mform->addElement('select', 'sub_sub_department', 'Sub Sub Department',  $sub_sub_department, array('class' => 'sub_subdepartment','data-placeholder'=>'--Select Sub Sub Department--'));
        $mform->setType('sub_sub_department', PARAM_INT);
        $sub_sub_dept->setMultiple(true);
        
        if(!empty($data)){
            if(!empty($data->designation) && is_array($data->designation)){
                $designation = $filter_form->get_employeedesignation($costcenter,$data->designation);
            }
        }else{
          $designation= array();
         }
        $select_designation =$mform->addElement('autocomplete','designation','Designation',$designation,array('class'=>'designation','data-placeholder'=>'--Select Designation--'));
        $mform->setType('designation',PARAM_RAW);
        $select_designation->setMultiple(true);
        

        //$select_supervisor =$mform->addElement('select','supervisor','Supervisor',get_supervisoridlist_filter($category='employee'),array('class'=>'supervisor','data-placeholder'=>'--Select Supervisor--'));
        //$mform->setType('supervisor',PARAM_RAW);
        //$select_supervisor->setMultiple(true);
        //
        //
        //
        //$select_branch =$mform->addElement('select','location','Location',get_branch_filter(),array('class'=>'branch','data-placeholder'=>'--Select Branch--'));
        //$mform->setType('location',PARAM_RAW);
        //$select_branch->setMultiple(true);
        
        // added by anil--- for extra filters
        //for department filter
       
        
        //for grade filter
      /*  $grades = $mform->addElement('select', 'grade', get_string('grade'),  get_alluser_grades(), array('class'=>'grade','data-placeholder'=>'--Select Grade--'));
        $mform->setType('grade', PARAM_RAW);
        $grades->setMultiple(true); */
        
        // for ou name
        /*$ouname = $mform->addElement('select', 'ou_name', get_string('ou_name', 'local_users'), get_users_ounames() , array('class'=>'ou_name','data-placeholder'=>'--Select Ou Name--'));
        $mform->setType('ou_name', PARAM_RAW);
        $ouname->setMultiple(true); */
        
        //$select_role =$mform->addElement('select','role','Role',get_role_filter(),array('class'=>'role','data-placeholder'=>'--Select Role--'));
        //$mform->setType('role',PARAM_RAW);
        //$select_role->setMultiple(true);
        
        // ********* commented by anil*********//
        //$select_level =$mform->addElement('select','level','Level',get_level_filter(),array('class'=>'level','data-placeholder'=>'--Select Level--'));
        //$mform->setType('level',PARAM_RAW);
        //$select_level->setMultiple(true);
        
        $this->add_action_buttons(true, get_string('filter'));

    
    }

    
}