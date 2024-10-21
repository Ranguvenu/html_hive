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
 * @subpackage local_learningplan
 */
require_once('../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

global $DB, $USER;

function download_csv($data, $filename = 'report') {
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    $headers = ['Completed status', 'Employee status', 'Employee email', 'Completion Grade', 'Employee department', 'Course Name', 'Course Credits', 'Username', "Course Category", "Credit Category"];
    $csvexport->add_data($headers);

    foreach ($data as $row) {
        $csvexport->add_data([
            $row->completedstatus ?? '--',
            $row->employeestatus ?? '--',
            $row->employeeemail ?? '--',
            $row->completiongrade ?? '--',
            $row->employeedepartment ?? '--',
            $row->coursename ?? '--',
            $row->coursecredits ?? '--',
            $row->username ?? '--',
            $row->coursecategory ?? '--',
            $row->creditcategory ?? '--'
        ]);
    }
    $csvexport->download_file();
    exit();
}

function reportdata(){
    global $USER, $DB;
    $systemcontext = context_system::instance();

    $userid = false;
    $filter_course = optional_param('filter_courses', '', PARAM_RAW);
    $sql = "SELECT lc.id AS ccourseid, u.id AS uuserid,c.fullname,cc.timecompleted AS forstatus,ue.timecreated,cc.timecompleted AS fordate, lc.credits AS coursecredits,ccat.idnumber AS coursecategory,ccat.idnumber AS creditcategory, lc.grade, lu.grade, u.department, u.email, u.username
    FROM mdl_local_coursedetails lc
    JOIN mdl_course c ON c.id = lc.courseid
    JOIN mdl_enrol e ON e.courseid = lc.courseid
    JOIN mdl_user_enrolments ue ON ue.enrolid = e.id
    JOIN mdl_user u ON u.id = ue.userid
    JOIN mdl_local_userdata lu ON lu.userid = u.id
    JOIN mdl_local_course_types lct ON lct.id = c.open_identifiedas
    JOIN mdl_course_categories ccat ON ccat.id = c.category
    LEFT JOIN mdl_course_completions cc ON cc.course = lc.courseid
    WHERE ccat.idnumber IN ('Technical', 'Fractal') ";
    $sql .= $userid ? "AND u.id = :userid " : "";
    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
        $sql .= "";
    }else if(!is_siteadmin() && has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $sql .= " AND u.open_costcenterid = $USER->open_costcenterid ";
    }else if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
        $sql .= " AND u.open_costcenterid = $USER->open_costcenterid
            AND u.open_departmentid = $USER->open_departmentid";
    }else{
        $sql .= " AND u.open_costcenterid = $USER->open_costcenterid
            AND u.open_departmentid = $USER->open_departmentid";
    }
    $params = ['userid' => $userid];
    $records = $DB->get_records_sql($sql, $params);
    $reportarray = array();
    foreach($records as $record){
        $columns = new stdClass();
        $columns->completedstatus = $record->timecompleted ? get_string('completed', 'block_configurable_reports') : get_string('inprogress', 'block_configurable_reports');
        if ($record->deleted == 0 && $record->deleted == 0){
            $columns->employeestatus = get_string('active', 'block_configurable_reports');
        }else{
            $columns->employeestatus = get_string('inactive', 'block_configurable_reports');
        }
        $columns->completiondate  = $record->timecompleted;
        $columns->employeeemail  = $record->email;
        $columns->completiongrade  = $record->grade;
        $columns->employeedepartment  = $record->department ? $record->department : "--";
        $columns->coursename  = $record->fullname;
        $columns->coursecredits  = $record->coursecredits;
        $columns->username  = $record->username;
        $columns->coursecategory  = $record->coursecategory;
        $columns->enrolmentdate  = $record->timecreated;
        $columns->creditcategory  = $record->creditcategory;
        $columns->coursecategory  = $record->coursecategory;
        $reportarray[] = $columns;
    }
    return $reportarray;
}

$reportarray = reportdata();
download_csv($reportarray);


