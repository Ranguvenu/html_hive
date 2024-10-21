<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
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


$string['pluginname'] = 'Learning Path';
$string['addnew_learningplans'] = 'Create Learning path';
$string['browseall_learningplans'] = 'Browse all Learning Path';
$string['my_learningplans'] = 'My Learning Paths';
/*capabilities strings added*/
$string['learningplan:manage'] = 'Learning Path:Manage';
$string['learningplan:view'] = 'Learning Path:view';
$string['learningplan:create'] = 'Learning Path:create';
$string['learningplan:delete'] = 'Learning Path:delete';
$string['learningplan:update'] = 'Learning Path:update';
$string['learningplan:publishplan'] = 'Learning Path:publishplan';
$string['learningplan:assignhisusers'] = 'Learning Path:assignhisusers';
$string['learningplan:assigncourses'] = 'Learning Path:assigncourses';

$string['add_learningplan'] = 'Create New +';
//$string['learning_plan_type'] = 'Learning Path type';
$string['credits'] = 'Credit Points';
$string['learning_plan_name'] = 'Name';
$string['planobjective'] = 'Path Objective';
$string['planstartdate'] = 'Start Date';
$string['planenddate'] = 'End Date';
$string['planlocation'] = 'Location';
$string['missing_plan_shortname'] = 'Shortname Required';
$string['nolearningplans'] = 'No Learning Paths to show';
$string['invalid_learningplan_id'] = 'Invalid Learning Path';
$string['assign_courses'] = 'Assign Courses';
$string['assign_users'] = 'Assign Users';
$string['assigned_courses'] = 'Courses';
$string['assigned_users'] = 'Users';
$string['nolearningplancourses'] = 'No Courses assigned this Learning Path';
$string['nolearningplanusers'] = 'No Users assigned this Learning Path';
$string['startdategreaterenddate'] = 'End date cannot be lesser than Start date';
$string['shortnametakenlp'] = 'The shortname has been already used for <b>{$a->name}</b> Learning Path.';
$string['target_audience'] = 'Target Audience';
$string['username'] = 'Employee Name';
$string['start_date'] = 'Enrolled Date';
$string['completion_date'] = 'Completion Date';
$string['learning_plan_status'] = 'Status';
$string['learning_plan_actions'] = 'Actions';
$string['delete_confirm'] = 'Are you sure to DELETE the "<b>{$a->name}</b>" Learning Path
                            <br/><b>Note: </b>All the Users and Courses will be deleted
                            related to the Learning Path.';
$string['unassign_courses_confirm'] = 'Are you sure you want to un-assign "<b>{$a->fullname}</b>" from the Learning Path.';
$string['unassign_users_confirm'] = 'Are you sure you want to un-assign "<b>{$a->fullname}</b>" from the Learning Path.';
$string['unassign'] = 'Yes';
$string['user_not_assigned_to_lp'] = 'You are not assigned to this Learning Path';
$string['learningplan_overview'] = 'Learning Path Overview';
$string['mylearningplan']='MY LEARNING PATH';
$string['learningplan']='LEARNING PATHS';
$string['availabellep']='Learning Path';
$string['browselistlep']='Browse List of Path';
$string['approval']='Learning Path Approval';
$string['managelep']='Manage Learning Path';
$string['unameexists']='Shortname already exists';
$string['numeric']='Credit Points should allow only numerics';
$string['points_help'] = 'Enter a number here. This will the total number of point users achieve for completing the learning path.';
/****  Added By Shivani M  *****/
$string['browse_learningplans'] = 'Browse Learning Paths';
$string['add_learningplans'] = 'Add Learning Path';
$string['edit_learningplans'] = 'Update Learning Path';
$string['lep_header'] = 'Learning Path Course Completion Details';
$string['delete_notify'] = 'Are you sure, you want to request enrollment to <b>"{$a->name}"</b> ?';
$string['enroll_notify'] = 'Are you sure, you want to enroll To <b>"{$a->name}"</b> ?';
$string['deleteconfirm'] = 'Are you sure to delete "<b>{$a->name}</b>" learning path. Once this is deleted it cannot be reverted';
$string['adnewlearningplan'] = '<i class="fa fa-map popupstringicon" aria-hidden="true"></i> Create Learning Path <div class= "popupstring">Here you will create learning paths</div>';
$string['editlearningplan'] = '<i class="fa fa-map popupstringicon" aria-hidden="true"></i> Update Learning Path <div class= "popupstring">Here you will update learning path</div>';
$string['confirmhidden'] = 'Activate Learning Path';
$string['confirmvisible'] = 'Inactivate Learning Path';
$string['activeconfirmvisible'] = 'Are you sure, Do you want to inactivate "<b>{$a->name}</b>" Learning Path';
$string['activeconfirmhidden'] = 'Are you sure, Do you want to activate "<b>{$a->name}</b>" Learning Path';
$string['enrolcourses'] = 'Assign courses';
$string['selectcourses'] = 'Select';
$string['manage_learningplan'] = 'Manage learning path';
$string['view_learningplan'] = 'View learning path';
$string['create_learningplan'] = 'Create learning path';
$string['employeesearch'] = 'Employee search';
$string['add_remove_users'] = 'Add or Remove users';
$string['generaldetails'] = 'General details';
$string['otherdetails'] = 'Target audience';
$string['open_group'] = 'Group';
$string['open_band'] = 'Band';
$string['open_hrmsrole'] = 'Role';
$string['open_branch'] = 'Branch';
$string['open_designation'] = 'Designation';
$string['learningplan_enrol_users'] = 'Are you sure you want to enroll {$a->count} users to the learning path "{$a->planname}" ?';
$string['provide_valid_shortname'] = 'Missing Learningpath shortname ';
$string['provide_valid_name'] = 'Missing Learningpath name';
$string['publish'] = 'Auto enroll';
$string['enrol'] = 'Enroll';
$string['learningplan_self_enrol'] = '<div class="pl-15 pr-15 pb-15">Are you sure do you want to enrol to {$a->planname} learning path?</div>';
$string['target_audience_tab'] = 'Target audience';
$string['audience_department'] = '<p>This learningpath will eligible below-listed target audience.</p>
<p> <b>Departments :</b> {$a}</p>';
$string['audience_subdepartment'] = '<p> <b>Sub Department :</b> {$a}</p>';
$string['audience_group'] = '<p> <b>Groups :</b> {$a}</p>';
$string['audience_hrmsrole'] = '<p> <b>HRMS Roles :</b> {$a}</p>';
$string['audience_designation'] = '<p> <b>Designations :</b> {$a}</p>';
$string['audience_location'] = '<p> <b>Locations :</b> {$a}</p>';
$string['audience_grade'] = '<p> <b>Grades :</b> {$a}</p>';
$string['need_manage_approval'] = 'Need Manager Approval';
$string['need_manager_approval_help'] = "Select 'Yes', if you want to enforce manager approval workflow. All the enrollments into Learning path will be sent as requests to corresponding reporting manager's approval / rejection. If manager approves the request, users will be enrolled, if rejected users will not be enrolled.";
$string['learning_path_name'] = 'Learning Path Name';
$string['descript_help'] = 'Enter Learning path description. This will be displayed in the list of Learning path.';
$string['select_all'] = 'Select All';
$string['remove_all'] = 'Un Select ';
$string['not_enrolled_users'] = '<b>Not Enrolled Users ({$a})</b>';
$string['enrolled_users'] = '<b> Enrolled Users ({$a})</b>';
$string['remove_selected_users'] = '<b> Un Enroll Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['remove_all_users'] = '<b> Un Enroll All Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['add_selected_users'] = '<i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i><b> Enroll Users</b>';
$string['add_all_users'] = ' <i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i> <b> Enroll All Users </b>';

$string['enrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully enrolled to this <b>"{$a->learningplan}"</b> learningplan .';
$string['unenrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully un enrolled from this <b>"{$a->learningplan}"</b> learningplan .';

$string['enrollusers'] = 'Learningplan <b>"{$a}"</b> enrollment is in process...';

$string['un_enrollusers'] = 'Learningplan <b>"{$a}"</b> un enrollment is in process...';
$string['click_continue'] = 'Click on continue';
$string['lp_sequence']= 'Required Sequence';
$string['sequence_help'] = 'Select

* Yes - if you want enforce sequential access to (mandatory) courses of the Learning Path.

* No - if you want let users freely access any course of Learning Path.';


$string['learningpaths_help'] = 'Search ans select the Learning Path summary file';

$string['courses'] = 'Courses';
$string['classrooms']= 'Classrooms';
$string['programs'] = 'Programs';
$string['certifications']= 'Certifications';
$string['moduletype'] = 'Moduletype';
$string['manage_br_learningplan'] = 'Manage <br/> learning paths';
$string['noplansforuser'] = 'No Learning Path Available';
$string['learningplancompleted'] = 'Completed';
$string['learningplanpending'] = 'Pending';
$string['le_enrol_users'] = 'Enroll Users';
$string['le_credits_defaultzero'] = '<span class="label_credits small">(Default 0)</span>';
$string['sequence'] = 'sequence';
$string['need_manager_approval'] = 'need_manager_approval';
$string['points'] = 'Credits';
$string['descript'] = 'descript';
$string['learningpaths'] = 'learningpaths';
$string['deleted_learningplan'] = 'Deleted Learningplan';
$string['learningplan:visible'] = 'learningpaths visible';
$string['missing_plan_learningplan'] = 'Learningplan Required';
$string['learningplan'] = 'Learningpath Code';
$string['open_pointslearningpath'] = 'Credits';
$string['open_pointslearningpath_help'] = 'Credits for the Learning Path default (0)';
$string['enrolusers'] = 'Enroll Users';
$string['enableplugin'] = 'Currently Learningplan enrolment method is disabled.<a href="{$a}" target="_blank"> <u>Click here</u></a> to enable the Enrolment method';
$string['manageplugincapability'] = 'Currently Learningplan enrolment method is disabled. Please contact the Site administrator.';
$string['employee_id'] = 'Employee Id';
$string['supervisorname'] = 'Supervisor';
$string['certificate'] = 'Certificate';
$string['add_certificate'] = 'Add Certificate';
$string['add_certificate_help'] = 'If you want to issue a certificate when user completes this learning path, please enable here and select the template in next field (Certificate template)';
$string['unableto_download_msg'] = "Still you didn't complete this learningplan so you cannot download the certificate";
$string['select_certificate'] = 'Select Certificate';
$string['certificate_template'] = 'Certificate template';
$string['certificate_template_help'] = 'Select Certificate template for this learning path';
$string['err_certificate'] = 'Missing Certificate template';
$string['eventlearningplanusercompleted'] = 'Learning path completed by user';
$string['messageprovider:learningplan_completion'] = 'Learningplan_completion';
$string['messageprovider:learningplan_enrol'] = 'Learningplan enrol';
$string['messageprovider:learningplan_unenrol'] = 'Learningplan unenrol';
$string['inprogress_learningplan'] = 'Inprogress Learningplan';
$string['completed_learningplan'] = 'Completed Learningplan';
$string['self_unenrolled_log'] = 'The user with id "{$a->relateduserid}" self unernolled from the Lenaring Path with id "{$a->lpathid}"';
$string['self_unenrolled'] = 'User Self-unenrolled from LPath';
$string['self_unenrolmsg'] = '<div class="pl-15 pr-15 pb-15">Are you sure, you want to Un-enroll from the <b>{$a->path}</b> ?</div>';
$string['learningpath_completion'] = 'Learning Path Completions';
$string['open_url'] = 'URL';
$string['open_url_help'] = 'The URL of the Learningplan';
$string['lp_mandatory_course_completion_msg'] = 'Awaiting Completion validation by System';
$string['lp_optional_course_completion_msg'] = 'Awaiting Completion validation by System';
 
$string['uname'] = 'User';
$string['reason'] = 'Reason';

$string['email'] = 'E-mail';
$string['undate'] = 'Unenrol date';
$string['unenrolled_courses'] = "Unenrolled Courses";
$string['course_self_enrol_license'] =  'As you don’t have an existing Percipio license, please register for the license in the first week of every month when the registration opens. Reach out to faahelpdesk@fractal.ai for any further queries/assistance.';
$string['course_self_enrol_license_udemy'] = 'As you don’t have an existing Udemy license, please register for the license in the first week of every month when the registration opens. Reach out to faahelpdesk@fractal.ai for any further queries/assistance.';
$string['csv'] = "csv";
$string['back'] = "Back";
$string['sample'] = "sample";
$string['help'] = "Help";
$string['continue'] = "Continue";
$string['userbulkenroll'] = "Users Bulk Enrollment";
$string['errorscount_msg'] = 'Total {$a} errors occured in the enrolled.';
$string['alredyenrolled'] = 'User alredy enrolled at line {$a}.';
$string['invalidid'] = 'Invalid user at line {$a}.';
$string['addedtrainee_msg'] = 'Total {$a} new userd enrolled to the learningpath.';
$string['usersfile_syncstatus'] = 'User Enrolled status';
$string['uploaddoc_help'] = 'Upload only CSV file';
$string['employeeidhelp'] = '<b>Note:-</b><br> employeeid : Insert employee id in the file who are alredy available in the system.<br><br>';
