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
 * @package Bizlms 
 * @subpackage local_classroom
 */

$string['pluginname'] = 'ILTs';
$string['browse_classrooms'] = 'Manage ILTs';
$string['my_classrooms'] = 'My ILTs';
$string['classrooms'] = 'View ILTs';
$string['costcenter'] = 'Organization';
$string['department'] = 'Department';
$string['shortname'] = 'Short Name';
$string['shortname_help'] = 'Please enter the partner short name';
$string['classroom_offline'] = 'Offline Courses';
$string['classroom_online'] = 'Online Courses';
$string['classroom_type'] = 'ILT Type';
$string['course'] = 'Course';
$string['assign_test'] = 'Assign Test';
$string['course_tests'] = 'Quizs';
$string['trainers'] = 'Trainers';
$string['description'] = 'Description';
$string['traning_help'] = 'Search and select trainers who will be part of the ILT training.';
$string['description_help'] = 'Enter ILT description. This will be displayed in the list of ILTs.';
$string['internal'] = 'Internal';
$string['external'] = 'External';
$string['institute_type'] = 'Location Type';
$string['institutions'] = 'Institutions';
$string['startdate'] = 'Start Date';
$string['enddate'] = 'End Date';
$string['create_classroom'] = 'Create ILTs';
$string['classroom_header'] = 'View ILTs';
$string['select_course'] = '--Select Course--';
$string['select_quiz'] = '--Select Quiz--';
$string['select_trainers'] = '--Select Trainer--';
$string['select_institutions'] = '--Select Location--';
$string['classroom_name'] = 'Name';
$string['allow_multi_session'] = 'Allow Multiple Session';
$string['allow_multiple_sessions_help'] = '
1.Add Induction option which will create 5 sessions every day ( 10 am to 11 am; 11 am to 12 pm; 12 pm to 1 pm; 2 pm to 4 pm; 4 pm to 6 pm).
2.Add Technical option which will create 3 hour session every day 10 am to 1 pm';
$string['allow_multiple_sessions'] = "allow_multiple_session";
$string['fixed'] = 'Fixed';
$string['custom'] = 'Custom';
$string['need_manage_approval'] = 'Need Manager Approval';
$string['need_manager_approval_help'] = "Select 'Yes', if you want to enforce manager approval workflow. All the enrollments into ILT training will be sent as requests to corresponding reporting manager's approval / rejection. If manager approves the request, users will be enrolled, if rejected users will not be enrolled.";
$string['capacity'] = 'Capacity';
$string['capacity_check_help'] = 'Total number of users who can participate in the ILT';
$string['need_manager_approval'] = 'need_manager_approval';
$string['manage_classroom'] = 'Manage ILTs';
$string['manage_classrooms'] = 'Manage ILTs';
$string['assign_course'] = 'Other Details';
$string['session_management'] = 'Session Management';
$string['location_date'] = 'Location & Date';
$string['certification'] = 'Certification';
$string['learningplan'] = 'Learning Plan';
$string['classroom'] = 'ILT';
$string['capacity_positive'] = 'Capacity must be greater than zero (0).';
$string['capacity_limitexceeded'] = 'Capacity cannot exceed {$a}';
$string['missingclassroom'] = 'Missed ILT data';
$string['courseduration'] = 'Course Duration';
$string['session_name'] = 'Session Name';
$string['session_type'] = 'Session Type';
$string['clrm_location_type'] = 'ILT location Type';
$string['classroom_locationtype_help'] = 'Select

* Internal- if you want to search and select an Internal locations like Training Room, Conference Room etc that are internal to your organization and where the training is planned to happen.

* External - if you want to search and select an External locations like Ball Rooms of a hotel, Training room of training institute etc that are external to your organization and where the training is planned to happen. ';
$string['classroom_location'] = 'ILT location';
$string['location_room'] = 'ILT location Room';
$string['nomination_startdate'] = 'Nomination start date';
$string['nomination_enddate'] = 'Nomination End date';
$string['type'] = 'Type';
$string['select_category'] = '--Select Category--';
$string['deleteconfirm'] = 'Are you sure you want to delete this ILT?';
$string['deleteallconfirm'] = 'Are you sure you want to delete?';
$string['deletecourseconfirm'] = 'Are you sure you want to un-assign?';
$string['createclassroom'] = '<span class="classroom_icon_wrap">
        </span> Create ILT <div class="popupstring">Here you will create ILT  </div>';
$string['updateclassroom']= '<span class="classroom_icon_wrap">
        </span> Update ILT <div class="popupstring">Here you will update ILT </div>';
$string['save_continue'] = 'Save & Continue';
$string['enddateerror'] = 'End Date should greater than Start Date.';
$string['sessionexisterror']='There are other sessions have in this time .';
$string['nomination_startdateerror'] = 'Nomination Start Date Schedule should be less than Class Room Start Date .';
$string['nomination_enddateerror'] = 'Nomination End Date Schedule should be less than Class Room Start Date .';
$string['nomination_error'] = 'Nomination End Date should greater than Nomination Start Date.';
$string['cs_timestart'] = 'Session Start Date';
$string['showentries'] = 'View response';
$string['cs_timefinish'] = 'Session End Date';
$string['select_room'] = '--Select ROOM--';
$string['select_costcenter'] = '--Select Organization--';
$string['select_department'] = 'All Departments';
$string['classroom_active_action'] = 'Are you sure you want to publish this ILT?';
$string['classroom_release_hold_action'] = 'Are you sure you want to release this ILT?';
$string['classroom_hold_action'] = 'Are you sure you want to hold this ILT?';
$string['classroom_close_action'] = 'Are you sure you want to cancel this ILT?';
$string['classroom_cancel_action'] = 'Are you sure you want to cancel this ILT?';
$string['classroom_complete_action'] = 'Are you sure you want to complete this ILT?';
$string['classroom_activate'] = 'Are you sure you want to publish this ILT?';
$string['classroom'] = 'ILT';
$string['learningplan'] = 'Learning Plan';
$string['certificate'] = 'Certificate';
$string['completed'] = 'Completed';
$string['pending'] = 'Pending';
$string['attendace'] = 'Mark Attendance';
$string['attended_sessions'] = 'Attended Sessions';
$string['attended_sessions_users'] = 'Attended Users';
$string['attended_hours'] = 'Attended Hours';
$string['supervisor'] = 'Reporting To';
$string['employee'] = 'Employee Name';
$string['room'] = 'Room';
$string['status'] = 'Status';
$string['trainer'] = 'Trainer';
$string['faculty'] = 'Trainer';
$string['addcourse'] = 'Add Courses';
$string['selfenrol'] = 'Self Enrol';
// Capability strings.
$string['classroom:createclassroom'] = 'Create ILT';
$string['classroom:viewclassroom'] = 'View ILT';
$string['classroom:editclassroom'] = 'Edit ILT';
$string['classroom:deleteclassroom'] = 'Delete ILT';
$string['classroom:manageclassroom'] = 'Manage ILT';
$string['classroom:createsession'] = 'Create Session';
$string['classroom:viewsession'] = 'View Session';
$string['classroom:editsession'] = 'Edit Session';
$string['classroom:deletesession'] = 'Delete Session';
$string['classroom:managesession'] = 'Manage Session';
$string['classroom:assigntrainer'] = 'Assign Trainer';
$string['classroom:managetrainer'] = 'Manage Trainer';
$string['classroom:addusers'] = 'Add users';
$string['classroom:removeusers'] = 'Remove users';
$string['classroom:manageusers'] = 'Manage users';

$string['classroom:cancel'] = 'Cancel ILT';
$string['classroom:classroomcompletion'] = 'ILT Completion Setting';
$string['classroom:complete'] = 'Complete ILT';
$string['classroom:createcourse'] = 'Assign ILT Course';
$string['classroom:createfeedback'] = 'Assign ILT Feedback';
$string['classroom:deletecourse'] = 'Un Assign ILT Course';
$string['classroom:deletefeedback'] = 'Un Assign ILT Feedback';
$string['classroom:editcourse'] = 'Edit ILT Course';
$string['classroom:editfeedback'] = 'Edit ILT Feedback';
$string['classroom:hold'] = 'Hold ILT';
$string['classroom:managecourse'] = 'Manage ILT Course';
$string['classroom:managefeedback'] = 'Manage ILT Feedback';
$string['classroom:publish'] = 'Publish ILT';
$string['classroom:release_hold'] = 'Release Hold ILT';
$string['classroom:takemultisessionattendance'] = 'ILT Multisession Attendance';
$string['classroom:manage_owndepartments'] = 'Manage Own Department ILT.';
$string['classroom:manage_multiorganizations'] = 'Manage Multi Organizations ILT.';

$string['classroom:takesessionattendance'] = 'ILT Session Attendance';
$string['classroom:trainer_viewclassroom'] = 'Trainer View ILT';
$string['classroom:viewcourse'] = 'View ILT Course';
$string['classroom:viewfeedback'] = 'View ILT Feedback';
$string['classroom:viewusers'] = 'View ILT Users';
$string['classroom:view_activeclassroomtab'] = 'View Active ILTs Tab';
$string['classroom:view_allclassroomtab'] = 'View All ILTs Tab';
$string['classroom:view_cancelledclassroomtab'] = 'View Cancelled ILTs Tab';
$string['classroom:view_completedclassroomtab'] = 'View Completed ILTs Tab';
$string['classroom:view_holdclassroomtab'] = 'View Hold ILTs Tab';
$string['classroom:view_newclassroomtab'] = 'View New ILTs Tab';
// Room Strings.
$string['institute_name'] = 'Location Name';
$string['building'] = 'Building Name';
$string['roomname'] = 'Room Name';
$string['address'] = 'Address';
$string['capacity'] = 'Capacity';
$string['capacity_help'] = 'Capacity help';

// Session Strings.
$string['onlinesession'] = 'Online Session';
$string['onlinesession_help'] = 'If checked this option and submitted online session will be created.';
$string['addasession'] = 'Add one more Session';
$string['addsession'] = '<i class="fa fa-graduation-cap popupstringicon" aria-hidden="true"></i> Create a Session <div class="popupstring"></div>';
$string['session_dates'] = 'Session Dates';
$string['attendance_status'] = 'Attendance Status';
$string['sessiondatetime'] = 'Session Date and Time';
$string['session_details'] = 'Session Details';
$string['cs_capacity_number'] = 'Capacity must be numeric and positive';
$string['select_cr_room'] = 'Select a room';
// Empty Message Strings.
$string['noclassrooms'] = 'ILTs not available';
$string['nosessions'] = 'Sessions not available';
$string['noclassroomusers'] = 'ILT users not available';
$string['noclassroomcourses'] = 'Courses not assigned';
// Classroom Users.
$string['select_all'] = 'Select All';
$string['remove_all'] = 'Un Select ';
$string['not_enrolled_users'] = '<b>Not Enrolled Users ({$a})</b>';
$string['enrolled_users'] = '<b> Enrolled Users ({$a})</b>';
$string['remove_selected_users'] = '<b> Un Enroll Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['remove_all_users'] = '<b> Un Enroll All Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['add_selected_users'] = '<i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i><b> Enroll Users</b>';
$string['add_all_users'] = ' <i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i> <b> Enroll All Users </b>';


$string['addusers'] = 'Add Users';
$string['addusers_help'] = 'Add users Help';
$string['potusers'] = 'Potential users';
$string['potusersmatching'] = 'Potential users matching \'{$a}\'';
$string['extusers'] = 'Existing users';
$string['extusersmatching'] = 'Existing users matching \'{$a}\'';



// Classroom Evaluations.
$string['noclassroomevaluations'] = 'ILT feedbacks not available!';
$string['training_feeddback'] = 'Training feedback';
$string['trainer_feedback'] = 'Trainer feedback';

// Classroom Status Tabs.
$string['allclasses'] = 'All';
$string['newclasses'] = 'New';
$string['activeclasses'] = 'Active';
$string['holdclasses'] = 'Hold';
$string['completedclasses'] = 'Completed';
$string['cancelledclasses'] = 'Cancelled';
$string['sessions'] = 'Sessions';
$string['courses'] = 'Courses';
$string['users'] = 'Users';

$string['activate'] = 'Activate';
$string['classroomstatusmsg'] = 'Are you sure you want to activate the ILT?';
$string['viewclassroom_assign_users']='Assign Users';
$string['assignusers']="Assign Users";
$string['continue']='Continue';
$string['assignusers']="Assign Users";
$string['assignusers_heading']='Enroll users to ILT <b>\'{$a}\'</b>';
$string['session_attendance_heading']='Attendance for ILT <b>\'{$a}\'</b>';

$string['online_session_type']='Online session type';
$string['online_session_type_desc']="online session type for online sessions on ILT.";
$string['online_session_plugin_info']='Online session type plugins not found.';
$string['select_session_type']='Select session type';
$string['join']='Join';
$string['view_classroom'] = 'view ILT';

$string['addcourses'] = '<i class="fa fa-graduation-cap popupstringicon" aria-hidden="true"></i> Assign course <div class="popupstring"></div>';
$string['completion_status'] = 'Completion Status';
$string['completion_status_per'] = 'Completion Status (%)';
$string['type'] = 'Type';
$string['trainer'] = 'Trainer';
$string['submitted'] = 'Submitted';
$string['classroom_self_enrolment'] = '<div class="pl-15 pr-15 pb-15">Are you sure you want to enrol this "<b>{$a}</b>" classroom?</div>';
$string['classroom_enrolrequest_enrolment'] = '<div class="pl-15 pr-15 pb-15">Are you sure you want to enrolment request this "<b>{$a}</b>" ILT?</div>';
$string['alert_capacity_check'] = "<div class='alert alert-danger text-center'>
                                All seats are filled.
                            </div>";
$string['updatesession'] = '<i class="fa fa-graduation-cap popupstringicon" aria-hidden="true"></i> Update Session <div class="popupstring"></div>';

$string['addnewsession'] = 'Add a new session';
$string['createinstitute'] = 'Create Location';
$string['employeeid'] = 'Employee ID';
$string['classroom_info'] = 'ILT Info';
$string['classroom_info'] = 'ILT Info';
$string['sessionstartdateerror1'] = 'Session start date should greater than ILT start date.';
$string['sessionstartdateerror2'] = 'Session start date should less than ILT end date.';
$string['sessionenddateerror1'] = 'Session end date should greater than ILT start date.';
$string['sessionenddateerror2'] = 'Session end date should less than ILT end date.';
$string['confirmation'] = 'Confirmation';
$string['unassign'] = 'Un-assign';
$string['roomid'] = 'List out the rooms from ILT.If you find rooms as empty, Please assign location for the classroom.';
$string['roomid_help'] = 'List out the rooms from ILT.';
$string['classroomcompletion'] = 'ILT Completion Criteria';
$string['classroom_anysessioncompletion'] = 'ILT is complete when ANY of the below sessions are complete';
$string['classroom_allsessionscompletion'] = 'ILT is complete when ALL sessions are complete';
$string['classroom_anycoursecompletion'] = 'ILT is complete when ANY of the below courses are complete';
$string['classroom_allcoursescompletion'] = 'ILT is complete when ALL courses are complete';
$string['classroom_completion_settings'] = 'ILT completion settings';
$string['sessiontracking'] = 'Sessions requirements';
$string['session_completion'] = 'Select Session';
$string['coursetracking'] = 'Courses requirements';
$string['course_completion'] = 'Select Course completions';
$string['classroom_donotsessioncompletion'] = 'Do not indicate sessions ILT completion';
$string['classroom_donotcoursecompletion'] = 'Do not indicate courses ILT completion';
$string['select_courses']='Select courses';
$string['select_sessions']='Select sessions';
$string['eventclassroomcreated'] = 'Local ILT created';
$string['eventclassroomupdated'] = 'Local ILT updated';
$string['eventclassroomcancel'] = 'Local ILT cancelled';
$string['eventclassroomcompleted'] = 'Local ILT completed';
$string['eventclassroomcompletions_settings_created'] = 'Local ILT completion settings added.';
$string['eventclassroomcompletions_settings_updated'] = 'Local ILT completion settings updated';
$string['eventclassroomcourses_created'] = 'Local ILT course added';
$string['eventclassroomcourses_deleted'] = 'Local ILT course removed';
$string['eventclassroomcourses_deleted'] = 'Local ILT course removed';
$string['eventclassroomdeleted'] = 'Local ILT deleted';
$string['eventclassroomhold'] = 'Local ILT holded';
$string['eventclassroompublish'] = 'Local ILT published';
$string['eventclassroomsessions_created'] = 'Local ILT sessions created';
$string['eventclassroomsessions_deleted'] = 'Local ILT sessions deleted';
$string['eventclassroomsessions_updated'] = 'Local ILT sessions updated';
$string['eventclassroomusers_created'] = 'Local ILT users enrolled';
$string['eventclassroomusers_deleted'] = 'Local ILT users un enrolled';
$string['eventclassroomusers_updated'] = 'Local ILT users updated';
$string['eventclassroomfeedbacks_created'] = 'Local ILT feedbacks created';
$string['eventclassroomfeedbacks_updated'] = 'Local ILT feedbacks updated';
$string['eventclassroomfeedbacks_deleted'] = 'Local ILT feedbacks deleted';
$string['eventclassroomattendance_created_updated'] = 'Local ILT sessions attendance present/absent';
$string['publish'] = 'Publish';
$string['release_hold'] = 'Release Hold';
$string['cancel'] = 'Cancel';
$string['hold'] = 'Hold';
$string['mark_complete'] = 'Mark Complete';
$string['enroll'] = 'Enrol';
$string['valnamerequired'] = 'Missing ILT name';
$string['numeric'] = 'Only numeric values';
$string['positive_numeric'] = 'Only positive numeric values';
$string['capacity_enroll_check'] = 'Capacity must be greater than allocated seats.';
$string['vallocationrequired'] = 'Please select location in the selected location type.';
$string['vallocation'] = 'Please select only the location in the selected location type.';

$string['new_classroom'] = 'New';
$string['active_classroom'] = 'Active';
$string['cancel_classroom'] = 'Cancelled';
$string['hold_classroom'] = 'Hold';
$string['completed_classroom'] = 'Completed';
$string['completed_user_classroom'] = 'You have not completed this ILT';
$string['classroomlogo'] = 'Banner Image';
$string['bannerimage_help'] = 'Search and select a banner image for the ILT training';
$string['completion_settings_tab'] = 'Completion Criteria';
$string['target_audience_tab'] = 'Target Audience';
$string['requested_users_tab'] = 'Requested Users';
$string['waitinglist_users_tab'] = 'Waiting List Users';


$string['classroom_completion_tab_info'] = 'No ILT criteria found.';

$string['classroom_completion_tab_info_allsessions'] = 'This ILT will completed when the below listed <b> all sessions </b> should be completed.';

$string['classroom_completion_tab_info_anysessions'] = 'This ILT will completed when the below listed <b> any sessions </b> should be completed.';

$string['classroom_completion_tab_info_allsessions_allcourses'] =  'This ILT will completed when the below listed <b>all courses </b> and <b> all sessions </b> should be completed.';

$string['classroom_completion_tab_info_allsessions_anycourses'] =  'This ILT will completed when the below listed <b>any courses </b> and <b> all sessions </b> should be completed.';

$string['classroom_completion_tab_info_anysessions_allcourses'] =  'This ILT will completed when the below listed <b>all courses </b> and <b> any sessions </b> should be completed.';

$string['classroom_completion_tab_info_anysessions_anycourses'] =  'This ILT will completed when the below listed <b>any courses </b> and <b> any sessions </b> should be completed.';

$string['classroom_completion_tab_info_allcourses'] = 'This ILT will completed when the below listed <b> all courses </b> should be completed.';

$string['classroom_completion_tab_info_anycourses'] = 'This ILT will completed when the below listed <b> any courses </b> should be completed.';

$string['audience_department'] = '<p>This ILT will eligible below-listed target audience.</p>
<p> <b>Departments :</b> {$a}</p>';
$string['audience_group'] = '<p> <b>Groups :</b> {$a}</p>';
$string['audience_hrmsrole'] = '<p> <b>HRMS Roles :</b> {$a}</p>';
$string['audience_designation'] = '<p> <b>Designations :</b> {$a}</p>';
$string['audience_location'] = '<p> <b>Locations :</b> {$a}</p>';
$string['audience_grade'] = '<p> <b>Grades :</b> {$a}</p>';
$string['no_trainer_assigned'] = 'No trainers assigned';
$string['requestforenroll'] = 'Request';
$string['requestavail'] = 'Requested users not available';
$string['nocoursedesc'] = 'No description provided';
$string['enrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully enrolled to this <b>"{$a->classroom}"</b> ILT .';
$string['unenrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully un enrolled from this <b>"{$a->classroom}"</b> ILT .';

$string['enrollusers'] = 'ILT <b>"{$a}"</b> enrollment is in process...';

$string['un_enrollusers'] = 'ILT <b>"{$a}"</b> un enrollment is in process...';
$string['click_continue'] = 'Click on continue';
$string['manage_br_classrooms'] = 'Manage <br/> ILTs';
$string['noclassroomsavailiable'] = 'No ILTs Availiable';
$string['employeerolestring'] = 'Employee';
$string['trainerrolestring'] = 'Trainer';
$string['taskclassroomreminder'] = 'ILT Reminder';
$string['unenrollclassroom'] = 'Are you sure you want to un enroll this "<b>{$a}</b>" ILT?';
$string['unenroll'] = 'Un Enroll';
$string['eventclassroomusers_waitingcreated'] = 'Local ILT users waiting list added';
$string['sortorder'] = 'Waiting Order';
$string['enroltype'] = 'Type';
$string['waitingtime'] = 'Date And Time';
$string['allow_waitinglistusers'] = 'Allow Users Waiting List';
$string['allowuserswaitinglist_help'] ='Allow users to join waiting list post the set the capacity for the Classroom training is full.';
$string['classroom:viewwaitinglist_userstab'] = 'Allow Users Waiting List';
$string['classroomwaitlistinfo'] = '<div class="p-2 text-center"><b>This "{$a->classroom}" ILT is presently reserved</b>. <br/><br/>Thank you for your application request. You are placed on waiting list with order "{$a->classroomwaitinglistno}" and will be informed via email in case enrols to ILT when availiable.</div>';
$string['otherclassroomwaitlistinfo'] = '<div class="p-2 text-center"><b>This "{$a->classroom}" ILT is presently reserved</b>. <br/><br/>Thank you for your application request.<b>"{$a->username}"</b> is placed on waiting list with order "{$a->classroomwaitinglistno}" and will be informed via email in case user enrols to ILT when availiable.</div>';
$string['capacity_waiting_check'] = 'Capacity is required to allow a users waiting list.';
$string['submit'] = 'Submit';
$string['capacity_check'] ='capacity_check';
$string['allowuserswaitinglist'] = 'allowuserswaitinglist';
$string['traning'] = 'traning';
$string['classroom_locationtype'] = 'classroom_locationtype';
$string['bannerimage'] = 'bannerimage';

$string['messageprovider:classroomenrolment'] = 'ILT Enrolment';
$string['classroomenrolmentsub'] = 'ILT Enrolment';
$string['classroomenrolment'] = '<p>You have been enrolled to the ILT "{$a->name}"!</p>
<p>You can view more information on "{$a->classroomurl}" page.</p>';
$string['tagarea_classroom'] = 'ILT';
$string['enrolled'] = 'Enrolled';
$string['deleted_classroom'] = 'Deleted ILT';
$string['points'] = 'Points';
$string['open_pointsclassroom'] = 'points';
$string['open_pointsclassroom_help'] = 'Points for the ILT default(0)';
$string['enrolusers'] = 'Enroll Users';
$string['enableplugin'] = 'Currently ILT enrolment method is disabled.<a href="{$a}" target="_blank"> <u>Click here</u></a> to enable the Enrolment method';
$string['manageplugincapability'] = 'Currently Classroom enrolment method is disabled. Please contact the Site administrator.';
$string['attendance'] = 'Attendance';
$string['add_certificate'] = 'Add Certificate';
$string['add_certificate_help'] = 'If you want to issue a certificate when user completes this ILT, please enable here and select the template in next field (Certificate template)';
$string['select_certificate'] = 'Select Certificate';
$string['certificate_template'] = 'Certificate template';
$string['certificate_template_help'] = 'Select Certificate template for this ILT';
$string['err_certificate'] = 'Missing Certificate template';
$string['eventclassroomusercompleted'] = 'ILT completed by user';
$string['downloadcertificate'] = 'Certificate';
$string['download_certificate'] = 'Download Certificate';
$string['unableto_download_msg'] = "Still you didn't complete this ILT so you cannot download the certificate";
$string['pluginname'] = 'ILTs';
$string['messageprovider:classroom_cancel'] = 'Classroom_cancel';
$string['messageprovider:classroom_complete'] = 'Classroom_complete';
$string['messageprovider:classroom_enrol'] = 'Classroom_enrol';
$string['messageprovider:classroom_enrolwaiting'] = 'Classroom_enrolwaiting';
$string['messageprovider:classroom_hold'] = 'Classroom_hold';
$string['messageprovider:classroom_invitation'] = 'Classroom_invitation';
$string['messageprovider:classroom_reminder'] = 'Classroom_reminder';
$string['messageprovider:classroom_unenroll'] = 'Classroom_unenroll';
$string['notassigned'] = 'N/A';
$string['inprogress_classroom'] = 'Inprogress ILT';
$string['completed_classroom'] = 'Completed ILT';
$string['induction'] = 'Induction';
$string['technical'] = 'Technical';
$string['attachment'] = 'Attachment';
$string['uploadusers'] = 'Upload Users';
$string['bulk_enrolment'] = 'Bulk Enrolments';
$string['usernotexistserror'] = 'User with Employee Id "{$a}" , is not exists in the System';
$string['alreadyenrollederror'] = 'User with Employee Id "{$a}" , is already enrolled to this ILT';
$string['orgmismatcherror'] = 'Oranization of the user with Employee Id "{$a}" , is not same with the ILT Organization';
$string['capacityfullerror'] = 'This Classroom Capacity ({$a}) has been reached';
$string['errorscountmsg'] = 'Errors count: {$a}';
$string['enrolledcountmsg'] = 'Enrolled users count: {$a}';
$string['notenrolled'] = 'Not Enrolled';
$string['prerequisites'] = 'Pre-requisites';
$string['prerequisite_courses'] = 'Pre-requisite Courses';
$string['prerequisites_help'] = 'Please select prerequisites';
$string['open_url'] = 'URL';
$string['open_url_help'] = 'The URL of the Classroom';
$string['upload_attendance'] = 'Upload Session Attendance';
$string['uploadattendance'] = 'Upload Attendance';
$string['uploadsessionattendance'] = 'Upload Session Attendance';
$string['bulk_session_attendance'] = 'Bulk Session Attendance';
$string['session'] = 'Session';
$string['sample'] = 'Sample';
$string['back_url'] = 'Back to View Page';
$string['help_manual'] = 'Help Manual';
$string['helpmanual'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['back_upload'] = 'Back to Upload Attendance';
$string['help_1'] = '<table class="generaltable" border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;"><b class="pad-md-l-50 hlep1-dh">Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>employee_id</td><td>Enter the employee id, avoid additional spaces.</td></tr>
<tr><td>employee_email</td><td>Enter valid email(Must and Should).</td></tr>
<tr><td>attendance_status</td><td>Enter Session Attendance Status either as (Present/Absent/NA),avoid additional spaces.</td></tr></table>
';
$string['classroom_usernotexistserror'] = 'User with Employee Id "{$a}" , is not exists in the Classroom';
$string['session_attachment'] = 'Session Attendance Attachment';
$string['help_2'] = '<table class="generaltable" border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;"><b class="pad-md-l-50 hlep1-dh">Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>session_id</td><td>Enter the Session id, avoid additional spaces.</td></tr>
<tr><td>employee_id</td><td>Enter the employee id, avoid additional spaces.</td></tr>
<tr><td>employee_email</td><td>Enter valid email(Must and Should).</td></tr>
<tr><td>attendance_status</td><td>Enter Session Attendance Status either as (Present/Absent/NA),avoid additional spaces.</td></tr></table>
';
$string['session'] = 'Session';
$string['empfile_syncstatus'] = 'Session Attendance File Sync Status';
$string['updatedusers_msg'] = 'Total <b>{$a}</b> session Attendance details updated.';
$string['errorscount_msg'] = 'Total <b>{$a}</b> errors occured in the session attendance detail upload.';
$string['filenotavailable'] = 'File with session attendance data is not available.';
$string['mandatory_fields'] = 'Missing Fields of <b>{$a->field}</b> uploaded excelsheet at line <b>{$a->linenumber}</b>.';
$string['sessionid_validation'] = 'This Session ID <b>{$a->sessionid}</b> is not exist of uploaded excelsheet at line <b>{$a->linenumber}</b>.';
$string['employeeid_validation'] = 'This Employee ID <b>{$a->employeeid}</b> is not valid of uploaded excelsheet at line <b>{$a->linenumber}</b>.';
$string['employee_email_validate'] = 'This Email ID <b>{$a->email}</b> is not valid of uploaded excelsheet at line <b>{$a->linenumber}</b>.';
$string['attendance_status_validate'] = 'The Attendance Status <b>{$a->attendance_status}</b> is not valid of uploaded excelsheet at line <b>{$a->linenumber}</b>';

$string['taskclassroomfeedbackreminder'] = 'ILT Feedback Reminder';
$string['calander_subject'] = '{$a->classroomname}-{$a->sessionname}';
$string['calander_subject_create'] = '{$a->classroomname}';
$string['valsessionnamerequired'] = 'Missing Session Name';
$string['classroom_self_enrolment_prerequisite'] = 'You have not completed the prerequisites, please try after completions of prerequisites.';
$string['notcompleted'] = 'Not Completed';
$string['sessionid'] = 'Session ID';
$string['sessionid_validation1'] = 'This Session ID <b>{$a->sessionid}</b> is invalid of uploaded excelsheet at line <b>{$a->linenumber}</b>. Please Enter only integer values';
$string['uname'] = 'User';
$string['reason'] = 'Reason';
$string['undate'] = 'Unenrol date';
$string['start_now'] = 'Start now';
$string['iltname'] = 'ILT Name';
$string['seats_allocated'] = 'Seats allocated';
$string['user_completions'] = 'User_completions';
$string['users_waiting_list'] = 'Users waiting list';
$string['grade'] = 'Grade';
$string['email'] = 'E-mail';

$string['outlook_event_credential'] = 'Outlook event Credentials';
$string['outlook_event_credential_desc'] = "UserID for triggering Outlook event for ILTs";
$string['enter_userid'] = "UserId";
$string['unenrol_classroom'] = 'Unenroll users';
$string['show_more'] = 'show more';
$string['show_less'] = 'show less';
