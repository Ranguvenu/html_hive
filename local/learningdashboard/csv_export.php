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

use local_learningdashboard;
require_once('../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

function download_csv($data, $view = false) {
    $filename = 'report';
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    if ($view == 'self') {
        $headers = ['Course Name', 'Completed status', "Enrollment Date", "Completion Date", 'Credits', "Course Category", "Credit Category", "Learning Type"];

        $csvexport->add_data($headers);

        foreach ($data as $row) {
            $csvexport->add_data([
                $row->coursename ?? '--',
                $row->completedstatus ?? '--',
                $row->enrolmentdate ?? '--',
                $row->completiondate ?? '--',
                $row->coursecredits ?? '--',
                $row->coursecategory ?? '--',
                $row->creditcategory ?? '--',
                $row->learningtype ?? '--',
            ]);
        }

    } else {
        $headers = ['Course Name', 'Name', 'Email ID', 'Department', 'Grade', 'Course Completion status', "Enrollment Date", "Completion Date", 'Credits', "Course Category", "Credit Category", "Learning Type"];

        $csvexport->add_data($headers);

        foreach ($data as $row) {
            $csvexport->add_data([
                $row->coursename ?? '--',
                $row->username ?? '--',
                $row->employeeemail ?? '--',
                $row->employeedepartment ?? '--',
                $row->completiongrade ?? '--',
                $row->completedstatus ?? '--',
                $row->enrolmentdate ?? '--',
                $row->completiondate ?? '--',
                $row->coursecredits ?? '--',
                $row->coursecategory ?? '--',
                $row->creditcategory ?? '--',
                $row->learningtype ?? '--',
            ]);
        }
    }
    $csvexport->download_file();
}

function reportdata($viewtype){
    global $DB, $CFG, $USER;
    $technicalcategories = $CFG->local_learningdashboard_technical_categories;
    $leadershipcategories = $CFG->local_learningdashboard_leadership_categories;
    $all = $technicalcategories . ', ' . $leadershipcategories;
    if ($viewtype == 'manager') {
        $userids = local_learningdashboard\api::teamusers();
    } elseif ($viewtype == 'admin') {
        if (!is_siteadmin()) {
            $params['costcenterid'] = $USER->open_costcenterid;
        }
    }
    $sql = "";
    $sql .= ($viewtype == 'self' || $viewtype == 'student') ? " SELECT c.id, " : " SELECT u.id,";
    $sql = " SELECT ue.id,
                u.id AS uuserid,
                c.fullname,
                cc.timecompleted AS coursecompleted,
                ue.timecreated,
                c.open_points  AS coursecredits,
                ccat.idnumber AS coursecategoryid,
                lc.fullname AS department,
                u.open_grade,
                u.email,
                u.username,
                ccat.name AS coursecategory,
                lct.course_type AS learningtype,
                CASE
                    WHEN ccat.id IN ($leadershipcategories) THEN 'Leadership'
                    WHEN ccat.id IN ($technicalcategories) THEN 'Technical'
                END AS creditcategory
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid AND  e.enrol = 'manual'
            JOIN {course} c ON c.id=e.courseid
            JOIN {course_categories} ccat ON ccat.id = c.category
            JOIN {local_course_types} lct ON lct.id = c.open_identifiedas
            JOIN {local_costcenter} lc ON lc.id = u.open_costcenterid
            LEFT JOIN {course_completions} cc ON cc.course = c.id AND u.id=cc.userid
            WHERE ccat.id IN ($all) ";
    $sql .= ($viewtype == 'self' || $viewtype == 'student') ? " AND u.id = :userid " : "";
    $params['userid'] = ($viewtype == 'self' || $viewtype == 'student') ? $USER->id : false;
    if ($viewtype == 'manager') {
        $sql .= " AND u.id IN ($userids) ";
    } elseif ($viewtype == 'admin') {
        if (!is_siteadmin()) {
            $sql .= " AND u.open_costcenterid = :costcenterid ";
        }
    }

    $records = $DB->get_records_sql($sql, $params);

    $reportarray = array();
    foreach($records as $record){
        $columns = new stdClass();
        $columns->completedstatus = $record->coursecompleted ? get_string('completed', 'block_configurable_reports') : get_string('inprogress', 'block_configurable_reports');
        if ($record->deleted == 0 && $record->deleted == 0){
            $columns->employeestatus = get_string('active', 'block_configurable_reports');
        }else{
            $columns->employeestatus = get_string('inactive', 'block_configurable_reports');
        }
        $columns->completiondate  = $record->coursecompleted ? date('d-m-Y', $record->coursecompleted) : "--";

        $columns->employeeemail  = $record->email;
        $columns->completiongrade  = $record->grade;
        $columns->employeedepartment  = $record->department ? $record->department : "--";
        $columns->coursename  = $record->fullname;
        $columns->coursecredits  = $record->coursecredits;
        $columns->username  = $record->username;
        $columns->coursecategory  = $record->coursecategory;
        $columns->enrolmentdate  = date('d-m-Y', $record->timecreated);
        $columns->creditcategory  = $record->creditcategory;
        $columns->coursecategory  = $record->coursecategory;
        $columns->learningtype  = $record->learningtype;
        $reportarray[] = $columns;
    }
    return $reportarray;
}
$view = optional_param('view', null, PARAM_TEXT);
$isreportingmanager =  local_learningdashboard\api::teamusers();

if ((!$isreportingmanager || empty($isreportingmanager) || !isset($isreportingmanager)) && $view != 'admin') {
    $view = 'self';
}
$reportarray = reportdata($view);
download_csv($reportarray, $view);


