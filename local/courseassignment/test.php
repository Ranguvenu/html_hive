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


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
global $DB,$USER;
$params = array();
$assignstatus = 0;
$sql = "SELECT RAND(),MAX(cal.id) as logid ,cal.courseid,cal.moduleid,cal.userid,s.id as assignid,MAX(cal.actiontakenon) 
            FROM mdl_course_completion_action_log cal 
            JOIN mdl_course c on cal.courseid = c.id 
            JOIN mdl_course_modules cm on c.id = cm.course and cal.moduleid = cm.id
            JOIN mdl_modules m on cm.module= m.id 
            JOIN mdl_user u ON u.id = cal.userid
            JOIN mdl_assign_submission s on s.assignment = cm.instance AND s.status = 'submitted' and cal.userid = s.userid
            JOIN mdl_assignsubmission_file asf ON s.id = asf.submission AND s.assignment = asf.assignment
            WHERE c.visible = 1 AND c.open_costcenterid = :costcenterid AND m.name = :modulename 
            GROUP BY cal.courseid,cal.moduleid,cal.userid,s.id 
            ORDER BY MAX(cal.id) asc ";
$params['costcenterid'] = $USER->open_costcenterid;
$params['modulename'] = 'assign';
$result = $DB->get_records_sql($sql,$params);
foreach($result as $res){
    $logsql = "SELECT method FROM mdl_course_completion_action_log WHERE id = $res->logid ";
    $method = $DB->get_field_sql($logsql);
    if($method == 'approve'){
        $assignstatus = 1;
    }else if($method == 'reject'){
        $assignstatus = 2;
    }else if($method == 'reset'){
        $assignstatus = 3;
    }
    $toupdate = new stdClass();
    $toupdate->id = $res->assignid;
    $toupdate->assignstatus = $assignstatus;
    $DB->update_record('assign_submission', $toupdate);
    
}
echo "done";
die;