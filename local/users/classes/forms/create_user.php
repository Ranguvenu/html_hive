<?php
namespace local_users\forms;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/costcenter/lib.php');
use moodleform;
use context_system;
use costcenter;
use events;
use context_user;
use local_users\functions\userlibfunctions as userlib;

class create_user extends moodleform {
	public $formstatus;
	public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true, $formdata = null) {

	 	$this->formstatus = array(
	 		'generaldetails' => get_string('generaldetails', 'local_users'),
			'otherdetails' => get_string('otherdetails', 'local_users'),
			'contactdetails' => get_string('contactdetails', 'local_users'),
			);
	 	parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $formdata);
	}
    public function definition() {
        global $USER, $CFG, $DB, $PAGE;
		$systemcontext = context_system::instance();
        $costcenter = new costcenter();
        $mform = $this->_form;
        
        $form_status = $this->_customdata['form_status'];
        $id = $this->_customdata['id'];
        $editoroptions = $this->_customdata['editoroptions'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $admin = $this->_customdata['admin'];
        $open_positionid = $this->_customdata['open_positionid'];
        $open_domainid = $this->_customdata['open_domainid'];
        if($form_status == 0){

	  //       if (is_siteadmin($USER->id) || has_capability('local/users:manage',$systemcontext)) {
			// 	$sql="select id,fullname 
			// 			from {local_costcenter} 
			// 			where visible = :visible and parentid=:parentid ";
	  //           $costcenters = $DB->get_records_sql($sql,array('visible' => 1,'parentid' => 0));
	  //       } 

			// if (is_siteadmin($USER) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
			// 	$organizationlist=array(null=>'--Select Department--');
			// 	foreach ($costcenters as $scl) {
			// 		$organizationlist[$scl->id]=$scl->fullname;
			// 	}
			// 	$mform->addElement('select', 'open_costcenterid', get_string('department', 'local_users'), $organizationlist);
			// 	$mform->addRule('open_costcenterid', get_string('errororganization', 'local_users'), 'required', null, 'client');	 
			// } else if(has_capability('local/costcenter:manage_ownorganization', $systemcontext)|| has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
			// 	$user_dept=$DB->get_field('user','open_costcenterid', array('id'=>$USER->id));
			// 	$mform->addElement('hidden', 'open_costcenterid', null);
			// 	$mform->setType('open_costcenterid', PARAM_ALPHANUM);
			// 	$mform->setConstant('open_costcenterid', $user_dept);
			// }
			$mform->addElement('hidden', 'auth', 'oidc');
			$mform->setType('auth', PARAM_TEXT);
			$mform->setConstant('auth', 'oidc');

			$orgid = $DB->get_field('local_costcenter','id', array('shortname'=>'Fractal'));

			$mform->addElement('hidden', 'open_costcenterid', $orgid);
			$mform->setType('open_costcenterid', PARAM_INT);
			$mform->setConstant('open_costcenterid', $orgid);

			$sql = "SELECT lc.id, lc.fullname
					FROM {local_costcenter} lc
					WHERE lc.parentid = :orgid AND lc.visible = :visible";
			$departs = $DB->get_records_sql_menu($sql, array('orgid'=>$orgid, 'visible'=>1));
			$departmentslist = array(null => '--Select Department--') + $departs;
			$mform->addElement('select', 'open_departmentid', get_string('department', 'local_users'), $departmentslist);
			$mform->addRule('open_departmentid', null, 'required', null, 'client');	

	        $count = ($costcenters);//count<revathi>
	        $mform->addElement('hidden', 'count', $count);
	        $mform->setType('count', PARAM_INT);

	        $mform->addElement('text', 'username', get_string('username', 'local_users'));
	        $mform->addRule('username', get_string('usernamerequired', 'local_users'), 'required', null, 'client');
	        $mform->setType('username', PARAM_RAW);

	        $options = array('Mr.'=>'Mr.','Mrs.'=>'Mrs.');
	        $mform->addElement('select', 'open_salutation', get_string('salutation', 'local_users'), $options);
	        $mform->addRule('open_salutation', null, 'required', null, 'client');
	        $mform->setType('open_salutation', PARAM_RAW);
			
			// $mform->addElement('passwordunmask', 'password', get_string('password'), 'size="20"');
			// $mform->addHelpButton('password', 'newpassword');
			// $mform->setType('password', PARAM_RAW);
			// if ($id <= 0){
			// 	$mform->addRule('password', get_string('passwordrequired', 'local_users'), 'required', null, 'client');
			// }

			$mform->addElement('text', 'firstname', get_string('firstname', 'local_users'));
	        $mform->addRule('firstname', get_string('errorfirstname', 'local_users'), 'required', null, 'client');
	        $mform->setType('firstname', PARAM_RAW);

	        $mform->addElement('text', 'lastname', get_string('lastname', 'local_users'));
	        $mform->addRule('lastname', get_string('errorlastname', 'local_users'), 'required', null, 'client');
	        $mform->setType('lastname', PARAM_RAW);

	        $mform->addElement('text', 'email', get_string('email', 'local_users'));
	        $mform->addRule('email', get_string('erroremail','local_users'), 'required', null, 'client');
	        $mform->addRule('email', get_string('emailerror', 'local_users'), 'email', null, 'client');
	        $mform->setType('email', PARAM_RAW);

	   //      if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', $systemcontext) || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
		  // 		$departmentslist = array('--Select Department--');
		  // 		if($id > 0){
		  // 			$existing_costcenter = $DB->get_field('user', 'open_costcenterid',array('id' => $id));
		  // 		}
		  // 		if($id > 0 && $existing_costcenter && !isset($this->_ajaxformdata['open_costcenterid'])){
		  // 			$open_costcenterid = $existing_costcenter;
				// } else{
		  // 			$open_costcenterid = $this->_ajaxformdata['open_costcenterid'];
				// }
				// if(!empty($open_costcenterid) && is_siteadmin()){
				// 	$departments = userlib::find_departments_list($open_costcenterid);
				// 	foreach($departments as $depart){
				// 		$departmentslist[$depart->id]=$depart->fullname;
				// 	}
				// }else if(!is_siteadmin() && has_capability('local/costcenter:view', $systemcontext)){
				// 	$departments = userlib::find_departments_list($USER->open_costcenterid);
				// 	foreach($departments as $depart){
				// 		$departmentslist[$depart->id]=$depart->fullname;
				// 	}
				// }
				// $mform->addElement('select', 'open_departmentid', get_string('department'),$departmentslist);
				// $mform->addHelpButton('open_departmentid', 'department','local_users');
		  //       $mform->addRule('open_departmentid', get_string('departmentrequired', 'local_users'), 'required', null, 'client');
		  //   }else /*if(has_capability('local/costcenter:manage_owndepartments', $systemcontext))*/{
    //     		$departmentid = $DB->get_field('user', 'open_departmentid', array('id' => $USER->id));
		  //   	$mform->addElement('hidden', 'open_departmentid');
		  //   	$mform->setType('open_departmentid', PARAM_INT);
				// $mform->setConstant('open_departmentid', $departmentid);
		  //   }


        	$mform->addElement('text', 'open_employeeid', get_string('serviceid', 'local_users'));
        	$mform->addRule('open_employeeid',  get_string('employeeidrequired','local_users'),  'required',  '',  'client');
        	$mform->addRule('open_employeeid',  get_string('open_employeeiderror','local_users'),  'alphanumeric',  'extraruledata',  'client');
	        $mform->setType('open_employeeid', PARAM_RAW);

	        $radioarray = array();
	        $radioarray[] = & $mform->createElement('radio', 'open_gender', '', get_string('male', 'local_users'), 'Male');
	        $radioarray[] = & $mform->createElement('radio', 'open_gender', '', get_string('female', 'local_users'), 'Female');
	        $mform->addGroup($radioarray, 'gender', get_string('gender', 'local_users'), array(' '), false);
	        if($id <= 0){
	        	$mform->setDefault('open_gender', 'Male');
	        }
	        

	  //       if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
			// 	$reporting= userlib::find_supervisor_list($USER->open_costcenterid,$id);
			// }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
			// 	$reporting = userlib::find_dept_supervisor_list($USER->open_departmentid,$id);
			// }else if($id>0){
			// 	$costcenterid = $DB->get_field('user','open_costcenterid',array('id' => $id)); 
			// 	$reporting= userlib::find_supervisor_list($costcenterid,$id);
			// }
			if($id > 0){
				$supervisors = userlib::find_supervisor_list($orgid,$id);
			}else{
				$superviserlist = userlib::find_supervisor_list($orgid);
				$supervisors = $superviserlist == null ? [] : $superviserlist;
			}

			$selectrm = array(null => '--Select Reporting To--');
			// print_r($supervisors);
			// exit;
			$reportingmanager = $selectrm + $supervisors;
           	$mform->addElement('select', 'open_supervisorid', get_string('supervisor','local_users'), $reportingmanager, array('id'=>'open_supervisorid'));
	        $mform->setType('open_supervisorid', PARAM_INT);

	        $selectfrm = array(null=>'--Select Functional Reporting To--');
	        $funcitonalreportingto = $selectfrm + $supervisors;
	        $mform->addElement('select', 'open_functionalreportingto', get_string('func_reporting','local_users'), $funcitonalreportingto,array('id'=>'open_supervisorid'));
	        $mform->setType('open_functionalreportingto', PARAM_INT);

	        $options = array(
						    'startyear' => 1970, 
						    'stopyear'  => date('Y', time()),
						    'timezone'  => 99,
						    'optional'  => false
						);

	        $mform->addElement('date_selector', 'open_dob', get_string('userdob','local_users'), $options);

			$mform->addElement('date_selector', 'open_doj', get_string('doj','local_users'), $options);

	  	}
		else if($form_status ==1){

			$mform->addElement('text', 'open_subdepart', get_string('subdepartment','local_users'));
			$mform->setType('open_subdepart', PARAM_RAW);

			$mform->addElement('select', 'lang', get_string('preferredlanguage', 'local_users'), get_string_manager()->get_list_of_translations());
	        $mform->setDefault('lang', $CFG->lang);

	        $mform->addElement('text', 'open_designation', get_string('designation', 'local_users'));
	        $mform->setType('open_designation', PARAM_RAW);
	        $mform->addElement('text', 'open_grade', get_string('grade', 'local_users'));
	        $mform->setType('open_group', PARAM_RAW);

	        $mform->addElement('text', 'open_ouname', get_string('ouname', 'local_users'));
	        $mform->setType('open_ouname', PARAM_RAW);

	        $mform->addElement('text', 'open_costcenter', get_string('costcentertext', 'local_users'));
	        $mform->setType('open_costcenter', PARAM_RAW);

	        $mform->addElement('text', 'open_calendar', get_string('calendarname', 'local_users'));
	        $mform->setType('open_calendar', PARAM_RAW);

	        $mform->addElement('text', 'open_careertrack', get_string('careertrack', 'local_users'));
	        $mform->setType('open_careertrack', PARAM_RAW);
			

		}else if ($form_status == 2){

			$user = $DB->get_record('user',array('id'=>$id));
			
			$mform->addElement('text', 'city', get_string('open_location','local_users'));
	        $mform->setType('city', PARAM_RAW);

	     //    $purpose = user_edit_map_field_purpose($user->id, 'country');
		    // $choices = get_string_manager()->get_list_of_countries();
		    // $choices = array('' => get_string('selectacountry') . '...') + $choices;
		    // $mform->addElement('select', 'country', get_string('selectacountry'), $choices, $purpose);
		    // if (!empty($CFG->country)) {
		    //     $mform->setDefault('country', core_user::get_property_default('country'));
		    // }
	        $mform->addElement('text', 'open_country', get_string('open_country','local_users'));
	        $mform->setType('open_country', PARAM_RAW);
	        $mform->addElement('text', 'phone1', get_string('contactno', 'local_users'));
	        $mform->addRule('phone1', get_string('numeric','local_users'), 'numeric', null, 'client');
	        $mform->addRule('phone1', get_string('phoneminimum', 'local_users'), 'minlength', 10, 'client');
	        $mform->addRule('phone1', get_string('phonemaximum', 'local_users'), 'maxlength', 15, 'client');
	        $mform->setType('phone1', PARAM_RAW);      

	        $mform->addElement('textarea', 'address', get_string('address', 'local_users'));
	       	
	        $mform->addElement('static', 'currentpicture', get_string('currentpicture'));
	        $mform->addElement('checkbox', 'deletepicture', get_string('delete'));
	        $mform->setDefault('deletepicture', 0);
	        $mform->addElement('filepicker', 'imagefile', get_string('newpicture'), null, array('accepted_types' => array('.jpg', '.jpeg', '.png')));
	        $mform->addHelpButton('imagefile', 'newpicture');
		}
		// end of form status = 2 condition
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id',  $id);
        $mform->addElement('hidden', 'form_status');
        $mform->setType('form_status', PARAM_INT);
        $mform->setDefault('form_status',  $form_status);
        $mform->disable_form_change_checker();

    }

    public function definition_after_data() {
        global $USER, $CFG, $DB, $OUTPUT;
        $mform = & $this->_form;
        $form_status = $this->_customdata['form_status'];
        if ($userid = $mform->getElementValue('id')) {
            $user = $DB->get_record('user', array('id' => $userid));
        } else {
            $user = false;
        }
        // print picture
        if (empty($USER->newadminuser)) {
            if ($user) {
                $context = context_user::instance($user->id, MUST_EXIST);
                $fs = get_file_storage();
                $hasuploadedpicture = ($fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.png') || $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.jpg'));

                if (!empty($user->picture) && $hasuploadedpicture) {
                    $imagevalue = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 64,'link' => false));
                } else {
                    $imagevalue = get_string('none');
                }
            } else {
                $imagevalue = get_string('none');
            }
            if($form_status == 2){
	            $imageelement = $mform->getElement('currentpicture');
	            $imageelement->setValue($imagevalue);
			}
            if ($user && $mform->elementExists('deletepicture') && !$hasuploadedpicture) {
                $mform->removeElement('deletepicture');
            }
        }
    }

   public function validation($data, $files) {
        $errors = array();
        global $DB, $CFG;
		$sub_data=data_submitted();
		$errors = parent::validation($data, $files);
        $email = $data['email'];
        $employeeid = $data['open_employeeid'];
        $id = $data['id'];
        $uname = $data['username'];
        $form_status = $data['form_status'];
        if($form_status == 0){// as these fields are in only form part 1(form_status=0)
        	$username = $data['username'];
        	$firstname = $data['firstname'];
        	$lastname = $data['lastname'];
        	if(empty(trim($username))){
        		$errors['username'] = get_string('valusernamerequired','local_users');
        	}
        	if(empty(trim($firstname))){
        		$errors['firstname'] = get_string('valfirstnamerequired','local_users');
        	}
        	if(empty(trim($lastname))){
        		$errors['lastname'] = get_string('vallastnamerequired','local_users');
        	}

		    if ($user = $DB->get_record('user', array('email' => $data['email']), '*', IGNORE_MULTIPLE)) {
	            if (empty($data['id']) || $user->id != $data['id']) {
	                $errors['email'] = get_string('emailexists', 'local_users');
	            }
	        }
	        
	        // if (!empty($data['password'])) {
         //        $errmsg = ''; // Prevent eclipse warning.
         //        if (!check_password_policy($data['password'], $errmsg)) {
         //            $errors['password'] = $errmsg;
         //        }
         //    }

	        // if (ctype_upper($uname)) {
	        if(strtolower($uname)!=$uname){
	        	$errors['username'] = get_string('lowercaseunamerequired', 'local_users');
	        }
	        if ($user = $DB->get_record('user', array('username' => $data['username']), '*', IGNORE_MULTIPLE)) {
	            if (empty($data['id']) || $user->id != $data['id']) {
	                $errors['username'] = get_string('unameexists', 'local_users');
	            }
	        }
	        if($user = $DB->get_record('user', array('open_employeeid' => $employeeid))){
	        	if (empty($data['id']) || $user->id != $data['id']) {
	                $errors['open_employeeid'] = get_string('open_employeeidexist', 'local_users');
	            }
	        }
	    }
	    if($form_status == 2){// as these fields are in only form part 3(form_status=2)
	    	$phone = $data['phone1'];
	    	if($phone){
	    		if(!is_numeric($phone)){
	    			$errors['phone1'] = get_string('numeric','local_users');
	    		}
		    	else if(($phone<999999999 || $phone>10000000000) && $phone){

		    		$errors['phone1'] = get_string('phonenumvalidate', 'local_users');
		    	}
		    }
	    }
        
        return $errors;
    }
}

