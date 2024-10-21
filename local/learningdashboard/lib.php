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

/**
 * Callback implementations for Learning Dashboard
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_learningdashboard\api;

/**
 * Serve the table for courses
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return array
 */
function local_learningdashboard_output_fragment_coursespopup($args) {
    global $DB, $CFG, $PAGE, $OUTPUT, $USER;
    $coursedata = array();
    $technicalcategories = $CFG->local_learningdashboard_technical_categories;
    $leadershipcategories = $CFG->local_learningdashboard_leadership_categories;
    $sql = "SELECT c.id,c.fullname, c.open_points, cc.name as categoryname, CASE WHEN cc.id IN({$technicalcategories}) THEN 'Technical' WHEN cc.id IN({$leadershipcategories}) THEN 'Leadership' END as creditstype FROM {course} c
            JOIN {course_categories} cc ON cc.id = c.category
            WHERE 1 ";
    $courseidsarr = explode(',', $args['courseids']);
    list($concatsql, $params) = $DB->get_in_or_equal($courseidsarr, SQL_PARAMS_NAMED);
    $sql .= " AND c.id $concatsql ";
    $courses = $DB->get_records_sql($sql, $params);
    foreach ($courses as $key => $course) {
        $coursedata['courseid'] = $course->id;
        $coursedata['coursename'] = $course->fullname;
        $coursedata['coursecategory'] = $course->creditstype;
        $coursedata['achievedcredits'] = $course->open_points;
        $coursedata['creditstype'] = 'Technical';
        $title = $args['ismanager'] ? get_string('popuptittle', 'local_learningdashboard', $args['userfullname']) : get_string('popuptittle', 'local_learningdashboard', $args['creditstype']);
        $coursedataarr[] = $coursedata;
    }
    $finaladmindashboard = [
        'coursedetails' => $coursedataarr,
        'ismanager' => $args['ismanager'],
        'creditsstatus' => ($args['creditsstatus'] == 'completed') ? get_string('achievedcredits', 'local_learningdashboard') : get_string('pendingcredits', 'local_learningdashboard')
    ];
    $args['regid'] = 1;
    $output = $OUTPUT->render_from_template('local_learningdashboard/coursespopup', $finaladmindashboard);
    return $output;
}
/**
 * My Learning data for Learner/Manager
 *
 * @param array $coursestatus Course status.
 * @return array
 */
function mylearningsdata($coursestatus) {
    global $CFG, $OUTPUT, $PAGE, $USER;
    $admindashboardarr = [];
    $admindashboard = array();
    $creditstypearray = ['Technical', 'Leadership'];
    $completedtab = $coursestatus == 'completed' ? 1 : 0;
    $pendingtab = $coursestatus == 'pending' ? 1 : 0;
    $totaltargetcredits = 0;
    $totalachievedcredits = 0;
    $supervisor = api::issupervisor();
    foreach ($creditstypearray as $type) {
        $targetcreditsinfo = api::targetcreditsinfo($type, $USER);
        $courses = api::creditsinfo($coursestatus, $type);
        // print_r($courses);
        $admindashboard['targetcredits'] = $targetcreditsinfo->credits ? $targetcreditsinfo->credits : 0;
        if ($coursestatus == 'pending')
            $admindashboard['pendingcredits'] = $courses->achievedpoints ? $courses->achievedpoints : 0;
        else
            $admindashboard['achievedcredits'] = $courses->achievedpoints ? $courses->achievedpoints : 0;
        $admindashboard['creditstype'] =  $type . ' Credits';
        $admindashboard['courseids'] = $courses->courseids;
        $admindashboard['coursestatus'] = $coursestatus;
        $coursecount = !empty($courses->courseids) ? count(explode(',', $courses->courseids)) : 0;
        $admindashboard['coursecount'] =  $coursecount;
        $admindashboard['issupervisor'] =  $supervisor;
        $totaltargetcredits += $targetcreditsinfo->credits;
        $totalachievedcredits += $courses->achievedpoints;
        $admindashboardarr[] = $admindashboard;
    }
    if ($totaltargetcredits) {
        $completedperc = $totalachievedcredits / $totaltargetcredits * 100;
    } else {
        $completedperc = 0;
    }
    $completedperc = ($completedperc >= 100) ? 100 : round($completedperc); 
    $pendingperc = 100 - $completedperc;
    $sales = new \core\chart_series('Completed', [round($pendingperc), round($completedperc)]);
    $labels = ['Target credits',  ucfirst($coursestatus) . ' credits'];
    $chart2 = new \core\chart_pie();
    $chart2->set_title($pendingperc . '%   <br/>Completed');
    $chart2->set_doughnut(true);
    $chart2->add_series($sales);
    $chart2->set_legend_options(['position' => 'bottom', 'reverse' => true]);
    $chart2->set_labels($labels);
    $sales->set_smooth(true);
    $sales->set_fill('origin');
    $sales->set_fill('-1');
    $sales->set_fill('end');
    $CFG->chart_colorset = ['#3498DB', '#F4D03F'];
    $OUTPUT->header();
    $PAGE->start_collecting_javascript_requirements();
    $o = '';
    ob_start();
    $o .= $OUTPUT->render_chart($chart2, false);
    $o .= ob_get_contents();
    ob_end_clean();
    $data = $o;
    $jsfooter = $PAGE->requires->get_end_code();
    $output = [];
    $output['error'] = false;
    $output['javascript'] = $jsfooter;

    $PAGE->requires->js('/local/learningdashboard/js/highcharts.js', true);
    $status = $coursestatus == 'pending' ? 'Achievable' : 'Completed';
    $graphdata = json_encode([
        "data" => [
            "Completed" => $completedperc,
            "Pending" => $pendingperc
        ],
        'completion_percentage' => $completedperc,
        'status' => $status
    ]);


    $PAGE->requires->js_call_amd('local_learningdashboard/graph', 'init', [$graphdata]);
    $labelone = $CFG->local_learningdashboard_label_one;
    $urlone = $CFG->local_learningdashboard_url_one;
    $labeltwo = $CFG->local_learningdashboard_label_two;
    $urltwo = $CFG->local_learningdashboard_url_two;

    $urls = [
        'labelone' => $labelone,
        'urlone' => $urlone,
        'labeltwo' => $labeltwo,
        'urltwo' => $urltwo,
    ];
    return ['learnerdata' => $admindashboardarr, 'completedtab' => $completedtab, 'pendingtab' => $pendingtab, 'data' => json_encode($output), 'islearnerview' => true, 'graphdata' => $graphdata] + $urls;
}
/**
 * Course categories list
 *
 * @return array
 */
function course_categories_list() {
    global $DB;
    $categorysql = "SELECT cc.id, cc.name
                          FROM {course_categories} AS cc ";
    $categorylist = $DB->get_records_sql_menu($categorysql);
    return $categorylist;
}
/**
 * Teams Learning data for Manager
 *
 * @param array $stable Course status.
 * @param array $filterdata Fileter data.
 * @return array
 */
function teamslearningsdata($stable, $filterdata) {
    $admindashboardarr = [];
    $admindashboard = array();
    $totaltargetcredits = 0;
    $totalachievedcredits = 0;
    $systemcontext = \context_system::instance();
    if (api::isadmin()) {
        $viewtype = 'admin';
        $adminview = true;
        $coursestatus = null;
    } else {
        $viewtype = 'manager';
        $coursestatus = 'completed';
    }
    $teamsstatus = api::creditsinfo(null, null, $viewtype, $stable, $filterdata);
    foreach ($teamsstatus['courses'] as $teamuser) {
        $user = \core_user::get_user_by_username($teamuser->username);
        $targetcreditsinfo = api::targetcreditsinfo($teamuser->creditstype, $user);
        $admindashboard['targetcredits'] = $targetcreditsinfo->credits ? $targetcreditsinfo->credits : 0;
        $admindashboard['achievedcredits'] = $teamuser->achievedpoints ? $teamuser->achievedpoints : 0;
        $admindashboard['courseids'] = $teamuser->courseids;
        $admindashboard['coursecount'] = count(explode(',', $teamuser->courseids)) > 0 ? count(explode(',', $teamuser->courseids)) : '0';
        if($targetcreditsinfo->credits == 0){
            $admindashboard['coursestatus'] = 'N/A';
        }else{
            $admindashboard['coursestatus'] = ($teamuser->achievedpoints >= $targetcreditsinfo->credits) ? 'Completed' : 'Pending';
        }
        $admindashboard['status'] = $coursestatus;
        $admindashboard['username'] = $teamuser->username;
        $admindashboard['employeename'] = $teamuser->employeename;
        $admindashboard['email'] = $teamuser->email;
        $admindashboard['mobile'] = $teamuser->phone1 ? $teamuser->phone1 : 'N/A';
        $admindashboard['startdate'] = $teamuser->open_doj ? date('d-M-Y', $teamuser->open_doj) : 'N/A';
        $totaltargetcredits += $targetcreditsinfo->credits;
        $totalachievedcredits += $teamuser->achievedpoints;
        $admindashboardarr[] = $admindashboard;
    }
    return ['records' => $admindashboardarr, 'isadminview' => $adminview, 'count' => $teamsstatus['count']];
}
