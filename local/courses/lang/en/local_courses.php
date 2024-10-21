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
$string['pluginname'] = 'Courses';
$string['organization']='Organization';
$string['mooc'] = 'MOOC';
$string['ilt'] = 'ILT';
$string['elearning'] = 'E-Learning';
$string['learningplan'] = 'Learning Path';
$string['type'] = 'Learning Type';
$string['category'] = 'Category';
$string['enrolled'] = 'Enrollments';
$string['completed'] = 'Completions';
$string['manual_enrolment'] = 'Manual Enrollment';
$string['add_users']='<< Add Users';
$string['remove_users']='Remove Users >>';
$string['employeesearch']='Filter';
$string['agentsearch']='Agent Search';
$string['empnumber']='Employee ID';
$string['email']='Email';
$string['band'] = 'Band';
$string['departments']='Departments';
$string['sub_departments']='Sub Departments';
$string['sub-sub-departments']='Sub Sub Departments';
$string['designation'] = 'Designation';
$string['im:already_in'] = 'The user "{$a}" was already enroled to this course';
$string['im:enrolled_ok'] = 'The user "{$a}" has successfully enroled to this course ';
$string['im:error_addg'] = 'Error in adding group {$a->groupe}  to course {$a->courseid} ';
$string['im:error_g_unknown'] = 'Error, unkown group {$a} ';
$string['im:error_add_grp'] = 'Error in adding grouping {$a->groupe} to course {$a->courseid}';
$string['im:error_add_g_grp'] = 'Error in adding group {$a->groupe} to grouping {$a->groupe}';
$string['im:and_added_g'] = ' and added to Moodle\'s  group  {$a}';
$string['im:error_adding_u_g'] = 'Error in adding to group  {$a}';
$string['im:already_in_g'] = ' already in group {$a}';
$string['im:stats_i'] = '{$a} enroled &nbsp&nbsp';
$string['im:stats_g'] = '{$a->nb} group(s) created : {$a->what} &nbsp&nbsp';
$string['im:stats_grp'] = '{$a->nb} grouping(s) created : {$a->what} &nbsp&nbsp';
$string['im:err_opening_file'] = 'error opening file {$a}';
$string['im:user_notcostcenter'] = '{$a->user} not assigned to {$a->csname} costcenter';
$string['mass_enroll'] = 'Bulk enrolments';
$string['mass_enroll_info'] =
"<p>
With this option you are going to enrol a list of known users from a file with one account per line
</p>
<p>
<b> The firstline </b> the empty lines or unknown accounts will be skipped. </p>
<p>
<b>The first one must contains a unique email of the target user </b>
</p>";
$string['firstcolumn'] = 'First column contains';
$string['creategroups'] = 'Create group(s) if needed';
$string['creategroupings'] = 'Create  grouping(s) if needed';
$string['enroll'] = 'Enrol them to my course';
$string['im:user_unknown'] = 'The user with an email "{$a}" doesn\'t exists in the System';
$string['points'] = 'Course credits';
$string['open_facilitatorcredits'] = 'Facilitator Credits';
$string['createnewcourse'] = '<i class="icon popupstringicon fa fa-book" aria-hidden="true"></i>Create Course <div class="popupstring">Here you can create course</div>';
$string['editcourse'] = '<i class="icon popupstringicon fa fa-book" aria-hidden="true"></i>Update Course <div class="popupstring">Here you can update course</div>';
$string['description']   = 'User with Username "{$a->userid}"  created the course  "{$a->courseid}"';
$string['desc']   = 'User with Username "{$a->userid}" has updated the course  "{$a->courseid}"';
$string['descptn']   = 'User with Username "{$a->userid}" has deleted the course with courseid  "{$a->courseid}"';
$string['usr_description']   = 'User with Username "{$a->userid}" has created the user with Username  "{$a->user}"';
$string['usr_desc']   = 'User with Username "{$a->userid}" has updated the user with Username  "{$a->user}"';
$string['usr_descptn']   = 'User with Username "{$a->userid}" has deleted the user with userid  "{$a->user}"';
$string['ilt_description']   = 'User with Username "{$a->userid}"  created the ilt  "{$a->f2fid}"';
$string['ilt_desc']   = 'User with Username "{$a->userid}" has updated the ilt "{$a->f2fid}"';
$string['ilt_descptn']   = 'User with Username "{$a->userid}" has deleted the ilt "{$a->f2fid}"';
$string['coursecompday'] = 'Course Completion Days';
$string['coursecreator'] = 'Course Creator';
$string['coursecode'] = 'Course Code';
$string['addcategory'] = '<i class="fa fa-book popupstringicon" aria-hidden="true"></i><i class="fa fa-book secbook popupstringicon cat_pop_icon" aria-hidden="true"></i> Create New Category <div class= "popupstring"></div>';
$string['editcategory'] = '<i class="fa fa-book popupstringicon" aria-hidden="true"></i><i class="fa fa-book secbook popupstringicon cat_pop_icon" aria-hidden="true"></i> Update Category <div class= "popupstring"></div>';
$string['coursecat'] = 'Course Categories';
$string['deletecategory'] = 'Delete Category';
$string['cost'] = 'Cost(Rs/-)';
$string['top'] = 'Top';
$string['parent'] = 'Parent';
$string['actions'] = 'Actions';
$string['count'] = 'Number of Courses';
$string['categorypopup'] = 'Category {$a}';
$string['missingtype'] = 'Missing Type';
$string['missinggrade'] = 'Missing Grade';
$string['missinglevel'] = 'Missing Level';
$string['missingprovider'] = 'Missing Course Provider';
$string['missingpoints'] = 'Missing Credits';
$string['missingfacilitatorcredits'] = 'Missing Facilitator Credits';
$string['missingcareertrack'] = 'Missing Career Track';
$string['numbersonlyhours'] = 'Hours';
$string['numbersonlyminutes'] = 'Minutes(MM)';
$string['idnumbercourse'] = 'Course ID number';
$string['catalog'] = 'Catalog';
$string['nocoursedesc'] = 'No description provided';
$string['apply'] = 'Apply';
$string['open_costcenterid'] = 'Costcenter';
$string['uploadcoursespreview'] = 'Upload courses preview';
$string['uploadcoursesresult'] = 'Upload courses results';
$string['uploadcourses'] = 'Upload courses';
$string['coursefile'] = 'File';
$string['csvdelimiter'] = 'CSV delimiter';
$string['encoding'] = 'Encoding';
$string['rowpreviewnum'] = 'Preview rows';
$string['preview'] = 'Preview';
$string['courseprocess'] = 'Course process';
$string['shortnametemplate'] = 'Template to generate a shortname';
$string['templatefile'] = 'Restore from this file after upload';
$string['reset'] = 'Reset course after upload';
$string['defaultvalues'] = 'Default course values';
$string['enrol'] = 'Enrol';
$string['courseexistsanduploadnotallowedwithargs'] = 'Course is already exists with shortname "{$a}", please choose other unique shortname.';
$string['canonlycreatecourseincategoryofsameorganisation'] = 'You can only create the course under your assigned organisation';
$string['canonlycreatecourseincategoryofsameorganisationwithargs'] = 'Cannot create a course under the category "{$a}"';
$string['createcategory'] = 'Create New Category';
$string['manage_course'] = 'Manage Course';
$string['manage_courses'] = 'Manage Courses';
$string['leftmenu_browsecategories'] = 'Manage Categories';
$string['courseother_details'] = 'Other Details';
$string['view_courses'] = 'view courses';
$string['deleteconfirm'] = 'Are you sure, you want to delete "<b>{$a->name}</b>" course?</br> Once deleted, it can not be reverted.';
$string['department'] = 'Department';
$string['coursecategory'] = ' Course category';
$string['fullnamecourse'] = 'Full name';
$string['coursesummary'] = 'Course Summary';
$string['courseoverviewfiles'] = 'Course summary files';
$string['startdate'] = 'Course Start Date';
$string['enddate'] = 'End Date';
$string['program'] = 'Program';
$string['certification'] = 'Certification';
$string['create_newcourse'] = 'Create new Course';
$string['userenrolments'] = 'User enrolments';
$string['certificate'] = 'Certificate';
$string['points_positive'] = 'Points must be greater than 0';
$string['coursecompletiondays_positive'] ='Completion days must be greater than 0';
$string['enrolusers'] = 'Enrol Users';
$string['grader'] = 'Grader';
$string['empgrade'] = 'Employee grade';
$string['activity'] = 'Activity';
$string['courses'] = 'Courses';
$string['nocategories'] = 'No categories available';
$string['nosameenddate'] = '"End date" should not be less than "Start date"';
$string['coursemanual'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['help_1'] = '<table border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>fullname</td><td>Fullname of the course.</td></tr>
<tr><td>course-code</td><td>course-code of the course.</td></tr>
<tr><td>category_code</td><td>Enter the category code(you can find this code in Manage Categories page).</td></tr>
<tr><td>coursetype</td><td>Type of the course(Comma seperated)(Ex:classroom,e-learning,certification,learningpath,program).</td></tr>
<tr><td>completiondays</td><td>Enter the number of completion days for the course</td></tr>
<tr><td>Duration in hours</td><td>Enter Duration in hours.<br>Duration should be numeric and cannot be zero.</td></tr>
<tr><td>Duration in minutes</td><td>Enter Duration in minutes.<br>Duration should be numeric and less than 59.</td></tr>
';
$string['help_2'] = '</td></tr>
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Normal Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>Summary</td><td>Summary of the course.</td></tr>
<tr><td>Cost</td><td>Cost of the course.</td></tr>
<tr><td>Department</td><td>Shortname of the department.</td></tr>
<tr><td>subdepartment</td><td>Shortname of the Sub Department.</td></tr>
<tr><td>Points</td><td>Points for the course.</td></tr>
</table>';
$string['back_upload'] = 'Back to upload courses';
$string['manual'] = 'Help manual';
$string['enrolledusers'] = 'Enrolled users';
$string['notenrolledusers'] = 'Not enrolled users';
$string['finishbutton'] = 'Finish';
$string['updatecourse'] = 'Update Course';
$string['course_name'] = 'Course Name';
$string['shortnamecourse'] = 'Short name';
$string['completed_users'] = 'Completed Users';
$string['course_filters'] = 'Course Filters';
$string['back'] = 'Back';
$string['sample'] = 'Sample';
$string['selectdept'] = '--Select Department--';
$string['selectsubdept'] = '--Select Sub Department--';
$string['selectorg'] = '--Select Organization--';
$string['selectcat'] = '--Select Category--';
$string['select_cat'] = '--Select Categories--';
$string['reset'] = 'Reset';
$string['err_category'] = 'Please select Category';
$string['availablelist'] = '<b>Available Users ({$a})</b>';
$string['selectedlist'] = 'Selected users';
$string['status'] = 'Status';
$string['select_all'] = 'Select All';
$string['remove_all'] = 'Un Select ';
$string['not_enrolled_users'] = '<b>Not Enrolled Users ({$a})</b>';
$string['enrolled_users'] = '<b> Enrolled Users ({$a})</b>';
$string['remove_selected_users'] = '<b> Un Enroll Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['remove_all_users'] = '<b> Un Enroll All Users </b><i class="fa fa-arrow-right" aria-hidden="true"></i><i class="fa fa-arrow-right" aria-hidden="true"></i>';
$string['add_selected_users'] = '<i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i><b> Enroll Users</b>';
$string['add_all_users'] = ' <i class="fa fa-arrow-left" aria-hidden="true"></i><i class="fa fa-arrow-left" aria-hidden="true"></i> <b> Enroll All Users </b>';$string['course_status_popup'] = 'Activity status for {$a}';
$string['auto_enrol'] = 'Auto Enroll';
$string['need_manage_approval'] = 'Need Manager Approval';
$string['costcannotbenonnumericwithargs'] ='Cost should be in numeric but given "{$a}"';
$string['pointscannotbenonnumericwithargs'] ='Points should be in numeric but given "{$a}"';
$string['need_self_enrol'] = 'Need Self Enroll';
$string['need_auto_enrol'] = 'Need Auto Enroll';
$string['enrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully enrolled to this <b>"{$a->course}"</b> course .';
$string['unenrolluserssuccess'] = '<b>{$a->changecount}</b> Employee(s) successfully un enrolled from this <b>"{$a->course}"</b> course .';

$string['enrollusers'] = 'Course <b>"{$a}"</b> enrollment is in process...';

$string['un_enrollusers'] = 'Course <b>"{$a}"</b> un enrollment is in process...';
$string['click_continue'] = 'Click on continue';
$string['bootcamp']= 'XSeeD';
$string['manage_br_courses'] = 'Manage <br/> courses';
$string['nocourseavailiable'] = 'No Courses Available';
$string['taskcoursenotification'] = 'Course Notification Task';
$string['taskcoursereminder'] = 'Course Reminder Task';
$string['pleaseselectorganization'] = 'Please Select Organization';
$string['pleaseselectcategory'] = 'Please Select Category';
$string['enablecourse'] = 'Are you sure to activate course <b>{$a}</b>';
$string['disablecourse'] = 'Are you sure to inactivate course <b>{$a}</b>';
$string['courseconfirm'] = 'Confirm';
$string['open_costcenteridcourse_help'] = 'Organisation for the course';
$string['open_departmentidcourse_help'] = 'Department for the course';
$string['open_identifiedascourse_help'] = 'Type of the course (multi select)';
$string['open_gradecourse_help'] = 'Grade for the course (multi select)';
$string['open_pointscourse_help'] = 'Credits for the course';
$string['open_facilitatorcreditscourse_help'] = 'Facilitator Credits for the course';
$string['open_careertrackcourse_help'] = 'Career Track for the course';
$string['selfenrolcourse_help'] = 'Check yes if required self enrollment to the course';
$string['autoenrolcourse_help'] = 'Check yes if required auto enrollment to the course';
$string['approvalrequiredcourse_help'] = 'Check yes if required to enable request manager for enrolling to the course';
$string['open_costcourse_help'] = 'Cost of the course';
$string['open_skillcourse_help'] = 'Skill achieved on completion of course';
$string['open_levelcourse_help'] = 'Level achieved on completion of course';
$string['open_costcenteridcourse'] = 'Organisation';
$string['open_departmentidcourse'] = 'Department';
$string['open_identifiedascourse'] = 'Type';
$string['open_gradecourse'] = 'Grade';
$string['open_pointscourse'] = 'Credits';
$string['open_facilitatorcreditscourse'] = 'Facilitator Credits';
$string['open_careertrackcourse'] = 'Career Track';
$string['selfenrolcourse'] = 'self enrollment';
$string['autoenrolcourse'] = 'auto enrollment';
$string['approvalrequiredcourse'] = 'request manager for enrolling';
$string['open_costcourse'] = 'Cost';
$string['open_skillcourse'] = 'Skill ';
$string['open_levelcourse'] = 'Level';
$string['open_career_track_tag'] = 'Career Track';
$string['notyourorg_msg'] = 'You have tried to view this activity is not belongs to your Organization';
$string['notyourdept_msg'] = 'You have tried to view this activity is not belongs to your Department';
$string['notyourorgcourse_msg'] = 'You have tried to view this course is not belongs to your Organization';
$string['notyourdeptcourse_msg'] = 'You have tried to view this course is not belongs to your Department';
$string['notyourorgcoursereport_msg'] = 'You have tried to view this Grader report is not your Organization course, so you cann\'t access this page';
$string['need_manager_approval '] = 'need_manager_approval';
$string['categorycode'] = 'Category Code';
$string['categorycode_help'] = 'The Category Code of a course category is only used when matching the category against external systems and is not displayed anywhere on the site. If the category has an official code name it may be entered, otherwise the field can be left blank.';

$string['categories'] = 'Sub Categories :  ';
$string['makeactive'] = 'Make Active';
$string['makeinactive'] = 'Make Inactive';
$string['courses:bulkupload'] = 'Bulk upload';
$string['courses:create'] = 'Create course';
$string['courses:delete'] = 'Delete  course';
$string['courses:grade_view'] = 'Grade view';
$string['courses:manage'] = 'Manage courses';
$string['courses:report_view'] = 'Report view';
$string['courses:unenrol'] = 'Unenrol course';
$string['courses:update'] = 'Update course';
$string['courses:view'] = 'View course';
$string['courses:visibility'] = 'Course visibility';
$string['courses:enrol'] = 'Course enrol';

$string['reason_linkedtocostcenter'] = 'As this Course category is linked with the Organization/Department, you can not delete this category';
$string['reason_subcategoriesexists'] = 'As we have sub-categories in this Course category, you can not delete this category';
$string['reason_coursesexists'] = 'As we have courses in this Course category, you can not delete this category';
$string['reason'] = 'Reason';
$string['completiondayscannotbeletter'] = 'Cannot create course with completion days as {$a} ';
$string['completiondayscannotbeempty'] = 'Cannot create course without completion days.';
$string['tagarea_courses'] = 'Courses';
$string['subcategories'] = 'Subcategories';
$string['tag'] = 'Tag';
$string['tag_help'] = 'tag';
$string['open_subdepartmentcourse_help'] = 'Subdepartment of the course';
$string['open_subdepartmentcourse'] = 'Subdepartment';
$string['suspendconfirm'] = 'Confirmation';
$string['activeconfirm'] = 'Are you sure to make category active ?';
$string['inactiveconfirm'] = 'Are you sure to make category inactive ?';
$string['yes'] = 'Confirm';
$string['no'] = 'Cancel';
$string['add_certificate'] = 'Add Certificate';
$string['add_certificate_help'] = 'If you want to issue a certificate when user completes this course, please enable here and select the template in next field (Certificate template)';
$string['select_certificate'] = 'Select Certificate';
$string['certificate_template'] = 'Certificate template';
$string['certificate_template_help'] = 'Select Certificate template for this course';
$string['err_certificate'] = 'Missing Certificate template';
$string['download_certificate'] = 'Download Certificate';
$string['unableto_download_msg'] = "Still this user didn't completed the course, so you cann't download the certificate";

$string['completionstatus'] = 'Completion Status';
$string['completiondate'] = 'Completion Date';
$string['nousersmsg'] = 'No users Available';
$string['employeename'] = 'Employee Name';
$string['completed'] = 'Completed';
$string['notcompleted'] = 'Not Completed';
$string['messageprovider:course_complete'] = 'Course_complete';
$string['messageprovider:course_enrol'] = 'Course_unenroll';
$string['messageprovider:course_notification'] = 'Course_notification';
$string['messageprovider:course_reminder'] = 'Course_reminder';
$string['messageprovider:course_unenroll'] = 'Course_unenroll';
$string['completed_courses'] = 'Completed Courses';
$string['inprogress_courses'] = 'Inprogress Courses';
$string['modulesettings'] = 'Module settings';
$string['facilitator'] = 'Facilitator';
$string['facilitatordetails'] = 'Manage Facilitators';
$string['facilitatorcredits'] = 'Facilitator Credits';
$string['facilitatorname'] = 'Facilitator Name';
$string['contenttype'] = 'Content Type';
$string['credits'] = 'Credits';
$string['facilitatorILTs'] = 'ILTs';
$string['course'] = 'Course Name';
$string['classname'] = 'Class Name';
$string['creditserror'] = 'Please enter credits less than or equal to facilitator credits';
$string['mooc'] ='MOOC';
$string['skillcategory'] ='Skill Category';
$string['select_skillcategory'] ='Select Skill Category';
$string['select_skill'] ='Select Skill';
// featured course strings
$string['title'] = "Block Title";
$string['err_courses'] = 'Please select Courses';
$string['err_title'] = 'Please enter title';
$string['featured_course'] = 'Featured Courses form';
$string['add_featuredcourse'] = 'Add Featured Courses';
$string['unenrolcourse'] = 'Unenrol Course';
$string['unenrol_reason'] = 'Specify the reason below for un-enroling:';
// course types strings
$string['open_coursetypecourse'] = 'Course type';
$string['open_coursetypecourse_help'] = 'Select the Course type';
$string['course_type'] = 'Course Type';
$string['course_type_shortname'] = 'Shortname';
$string['viewcourse_type'] = 'Add/View Course type';
$string['add_coursetype'] = 'Add Course type';
$string['edit_coursetype'] = 'Edit Course type';
$string['listtype']	='LIST';
$string['listicon'] ='icon fa fa-bars fa-fw';
$string['name'] = 'Name';
$string['enablecoursetype'] = 'Are you sure to activate course type <b>{$a}</b>';
$string['disablecoursetype'] = 'Are you sure to inactivate course type <b>{$a}</b>';
$string['statusconfirm'] = 'Are you sure you want to {$a->status} "{$a->name}"';
$string['coursetypeexists'] = 'Course type already created ({$a})';
$string['deletecoursetypeconfirm'] = 'Are you sure, you want to delete "<b>{$a->name}</b>" course type?</br> Once deleted, it can not be reverted. </br><b>Note : </b> Courses assigned to this course type will be reverted to default.';
$string['err_coursetype'] = 'Please enter Course type';
$string['err_coursetypeshortname'] = 'Please enter shortname';
// course providers strings
$string['open_courseprovidercourse'] = 'Course provider';
$string['open_courseprovidercourse_help'] = 'Select the Course provider';
$string['course_prov'] = 'Course Provider';
$string['course_prov_shortname'] = 'Shortname';
$string['viewcourse_prov'] = 'Add/View Course provider';
$string['add_courseprov'] = 'Add Course provider';
$string['edit_courseprov'] = 'Edit Course provider';
$string['listtype']	='LIST';
$string['listicon'] ='icon fa fa-bars fa-fw';
$string['courseprov_name'] = 'Course provider';
$string['enablecourseprov'] = 'Are you sure to activate course provider <b>{$a}</b>';
$string['disablecourseprov'] = 'Are you sure to inactivate course provider <b>{$a}</b>';
$string['statusconfirm'] = 'Are you sure you want to {$a->status} "{$a->name}"';
$string['courseprovexists'] = 'Course provider already created ({$a})';
$string['deletecourseproviderconfirm'] = 'Are you sure, you want to delete "<b>{$a->name}</b>" course provider?</br> Once deleted, it can not be reverted. </br><b>Note : </b> Courses assigned to this course provider will be reverted to default.';
$string['err_courseprov'] = 'Please enter Course Provider';
$string['prerequisites'] = 'Pre-requisites';
$string['submitted_assignments'] = 'Assignments Submitted';
$string['open_url'] = 'URL';
$string['open_url_help'] = 'The URL of the course';
$string['coursecompday_help'] = 'The Completion Days of the Course';

$string['uname'] = 'User';
$string['reason'] = 'Reason';
$string['undate'] = 'Unenrol date';
$string['learningtype'] = 'Learning Type';
$string['unenrolled_courses'] = "Unenrolled Courses";

$string['timeupload'] = 'Bulkupload Completion date';
$string['bulkuploadtime'] = 'Bulkupload Completion date';
$string['coursemanual'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['help1'] = '<table class="generaltable" border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>Employeeid</td><td>Enter employeeid.</td></tr>
<tr><td>coursecode</td><td>Enter coursecode.</td></tr>
<tr><td>completion_date</td><td>Enter completion date (please enter completion date in integer formate). <br>Date formate : Date : Month : Year<br>Example : 3-oct-2022.</td></tr>
</table>
';
$string['backupload'] = 'Back to upload completion date';
$string['empfile_syncstatus'] ='completion date file sync status';
$string['addedusers_msg'] ='Total {$a} new completion date added to the system.';
$string['errorscount_msg'] ='Total {$a} errors occured in the sync update.';
$string['button'] ='Continue';
$string['uploaduploadtime'] = 'Bulkupload completion date';
$string['filenotavailable'] = 'Please fill and upload sheet';
$string['uploadtime'] = 'Upload completion date';
$string['pluginnam'] = 'Upload completion date';
$string['uploadcompletion'] = "Upload completion date";
$string['taskcoursecompletionreminder'] = "Course completion Reminder";
$string['taskcoursecompletionfrequency'] = "Course completion Frequency Task"; 

$string['uploaduploadcredits']= 'Upload Credits';
$string['levels'] = 'Levels';
$string['heading2'] = 'Levels List';
$string['add_levels'] = 'Add New Level';
$string['update_level'] = 'Update Level';
$string['level_name'] = 'Level Name';
$string['already_available'] = 'Level name : (<b>{$a}</b>) is already available in your Database.';
$string['del_level'] = 'Delete Level';
$string['edit_level'] = 'Edit Level';
$string['hide'] = 'Hide/ Unhide Level';
$string['deletelevel'] = 'Are you sure, you want to delete "<b>{$a->name}</b>" level type?</br> Once deleted, it can not be reverted.';
$string['hide_level'] = 'Hide Level';
$string['noti1'] = 'Your level successfully Inactive.';
$string['noti2'] = 'Your level successfully Active.';
$string['expirydate'] = 'Expiry Date';
$string['expirydate_help'] = 'Course Expiry Date';
$string['expirydateenable'] = 'Enable Expiry Date';
$string['expirydateenable_help'] = 'Course Expiry Date Enable';
$string['course_expiry_user'] = '<center>Your Course "<b>{$a->coursename}</b>" has been Expired! Please Contact the Site Administrator</center>';
$string['expirydate_error'] = 'Expiry Date Should not be Less than as Course Start Date';
$string['course_expiry'] = ' <b>{$a}</b>';
$string['back_url'] = 'Back to Dashboard';
$string['course_expiry_users'] = '<center>Your Course "<b>{$a}</b>" has been Expired! Please Contact the Site Administrator</center>'; 
$string['ou_name'] = 'OU Name';
$string['sme_users'] = 'SME Users';
$string['selectcourse'] = 'Select Course';
$string['selectlpaths'] = 'Select Learning Paths';
$string['missingouname'] = 'Missing OU Name';
$string['coursecreditscannotbenonnumericwithargs'] ='Course Credits should be in numeric but given "{$a}"';
$string['facilitatorcreditscannotbenonnumericwithargs'] = 'Facilitator Credits should be in numeric but given "{$a}"';
$string['coursecreditscannotbeempty'] = 'Cannot create course without course credits.';
$string['facilitatorcreditscannotbeempty'] = 'Cannot create course without Facilitator credits';
$string['grade'] = "Cannot create course with empty employee grades";
$string['grades'] = "Cannot create course with unknown employee grades";
$string['level'] = "Cannot create course with empty level";
$string['leveldata'] = "Cannot create course with unknown level";
$string['Course_provider'] = "Cannot create course with empty Course provider";
$string['Course_providers'] = "Cannot create course with unknown Course providers";


$string['creditsuploadss'] = 'Download sample Excel sheet and fill the field values in the format specified below.';

$string['help_3'] = '<table class="generaltable" border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>Coursecode</td><td>&nbsp;Enter coursecode.</td></tr>
<tr><td>Credits</td><td>&nbsp;Enter credits.</td></tr>
<tr><td>Levels</td><td>&nbsp;Enter Levelcode.<br>(Example : L1, L2, L3, L4).</td></tr>
<tr><td>Duration in hours</td><td>Enter Duration in hours.<br>Duration should be numeric and cannot be zero.</td></tr>
<tr><td>Duration in minutes</td><td>Enter Duration in minutes.<br>Duration should be numeric and less than 59.</td></tr>
</table>
';
$string['backtocredits'] = 'Back to upload credits';
$string['creditsuploadss'] = 'Download sample Excel sheet and fill the field values in the format specified below.';
$string['empfile_syncstatuss'] ='Credits file sync status';
$string['addedcredits_msg'] ='Total {$a} new credits added to the system.';
$string['errorscount_msgs'] ='Total {$a} errors occured in the sync update.';
$string['filenotavailable'] = 'Uploaded sheet is empty. Please upload a valid file.';
$string['uploadcredits'] = 'Credits upload';
$string['image'] = 'Upload thumbnail image';
$string['nodesc'] = 'Description is not available';
$string['expireddate'] = 'Expiry Date:';
$string['coursetype_image'] = 'Image';
$string['email'] = 'E-mail';


$string['durationshouldmatchformat'] = 'Duration should match the given format hours:minutes(02:30)';
$string['durationcannotbeempty'] = 'Duration cannot be empty.';
$string['durationcannotbezero'] = 'Duration cannot be Zero.';
$string['durationhoursshouldmatchformat'] = 'Duration in hours should be numeric and cannot be zero or negitive number';
$string['durationminutesshouldmatchformat'] = 'Duration in minutes should be numeric and less than 59';
$string['durationminutescannotbeempty'] = 'Duration in minutes cannot be empty';
$string['durationhourscannotbeempty'] = 'Duration in hours cannot be empty';
$string['reissue'] = 'Re-Issue ?';
$string['reissue_certificate'] = 'Are you sure, you want to re issue certificate of "{$a->fullname}" ?';
$string['upload_course_category'] = 'Update Course Categories';

$string['course_category_help_1'] = '<table border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>course_name</td><td>Name of the course.</td></tr>
<tr><td>course_code</td><td>Code of the course.</td></tr>
<tr><td>existing_category</td><td>Enter the existing course category code(you can find this code in Manage Categories page).</td></tr>
<tr><td>new_category</td><td> Enter the new category code(you can find this code in Manage Categories page).</td></tr>
</td></tr> </table>';
$string['validsheet'] = 'Please upload valid file. {$a} in uploaded sheet';
$string['course_name_missing'] = 'Missing Course Name';
$string['course_code_missing'] = 'Missing Course Code';
$string['existing_category_missing'] = 'Missing Existing Category';
$string['new_category_missing'] = 'Missing New Category';
$string['new_category_notexist'] = 'New Category Not Exists';
$string['existing_category_notexist'] = 'Existing Category Not Exists';
$string['course_code_notexist'] = 'Course Code Not Exists';
$string['uploadorgsheet'] = '{$a->count} records uploaded successfully.';
$string['notuploadorgsheet'] = '0 records updated';
$string['categorycode_mismatched'] = ' Given category code not matched with the course';
$string['upload_course_completion'] = 'Remove Course Completions';
$string['course_completion_help'] = '<table border="1">
<tr><td></td><td style="text-align:left;border-left:1px solid white;padding-left:50px;"><b>Mandatory Fields</b></td><tr>
<th>Field</th><th>Restriction</th>
<tr><td>email</td><td>Enter user email.</td></tr>
<tr><td>course_code</td><td>Enter Shortname of course.</td></tr>
<tr><td>time completed</td><td> Enter the completed time of the course. (Please Use this format 01-01-2010)</td></tr>
</td></tr> </table>';
$string['shortname_notexist'] = 'Shortname Not Exist';
$string['course_shortname_missing'] = 'Missing Course Shortname';
$string['email_notexist'] = 'Email Not Exist';
$string['email_missing'] = 'Missing Email';
$string['timestarted_missing'] = 'Missing Time Started';
$string['timecompleted_missing'] = 'Missing Time Completed';
