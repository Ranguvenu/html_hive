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

namespace local_learningdashboard;

/**
 * Class api
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api
{
    /**
     * Returns Credits Information.
     *
     * @param  string $coursestatus Course status.
     * @param string $type Type of Credits Technical/Leadership.
     * @param string $viewtype View Type like learner/manager/admin.
     * @param object $stable table 
     * @param object $filterdata Filter data.
     * @return object
     */
    public static function creditsinfo($coursestatus = null, $type = null, $viewtype = 'learner', $stable = null, $filterdata = null) {
        global $DB, $USER, $CFG;
        $select = '';
        $groupby = '';
        $as = '';
        $params = [];
        $condition = '';
        $systemcontext = \context_system::instance();
        $technicalcategories = $CFG->local_learningdashboard_technical_categories;
        $leadershipcategories = $CFG->local_learningdashboard_leadership_categories;
        $all = $leadershipcategories.','.$technicalcategories;
        if($viewtype == 'manager') {
            $userids = api::teamusers();
            $selectsql = $select = " SELECT u.username, u.id as userid,concat(u.firstname, ' ', u.lastname) as employeename, GROUP_CONCAT(c.id) as courseids,SUM(c.open_points) as achievedpoints ";
            $countsql = " SELECT count(userid) FROM ( $select ";
            $groupby = " GROUP BY u.username,userid,employeename ";
            $condition = " AND u.id IN ({$userids})";
            $as =" ) as a";
        }else
        if($viewtype == 'admin'){
            $countsql = " SELECT count(DISTINCT u.id) ";
            $selectsql = " SELECT u.username,u.email,u.phone1, u.id as userid,concat(u.firstname, ' ', u.lastname) as employeename,u.open_doj ";
            if(!is_siteadmin()){
                $condition = " AND u.open_costcenterid = :costcenterid";
                $params['costcenterid'] = $USER->open_costcenterid;
            }
        }else{
            $countsql = " SELECT count(u.id) ";
            $selectsql = "SELECT GROUP_CONCAT(c.id) as courseids,SUM(c.open_points) as achievedpoints ";
        }
        $sql = " FROM {user} u 
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid AND  e.enrol = 'manual'
        JOIN {course} c ON c.id=e.courseid
        JOIN {course_categories} ccat ON ccat.id = c.category
        LEFT JOIN {course_completions} cc ON cc.course = c.id AND u.id=cc.userid
        WHERE 1  ";
        if($type == 'Technical'){
            $sql .= " AND ccat.id IN ($technicalcategories) ";
        }else if($type == 'Leadership'){
            $sql .= "AND ccat.id IN ($leadershipcategories) ";
        }else{
            $sql .= "AND ccat.id IN ($all) ";
        }
        if(is_siteadmin()){
            $sql .= "";
        }

        if($coursestatus == 'completed'){
            $sql .= " AND timecompleted IS NOT NULL ";
        }else if($coursestatus == 'pending'){
            $sql .= " AND timecompleted IS NULL ";
        }
        if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
            $condition .= " AND (concat(u.firstname, ' ', u.lastname) LIKE :search ";
            $condition .= " OR u.email LIKE :search1 ";
            $condition .= " OR u.phone1 LIKE :search2) ";
            $params += array('search' => '%'.$filterdata->search_query.'%', 'search1' => '%'.$filterdata->search_query.'%','search2' => '%'.$filterdata->search_query.'%');
        }
        $sql .= $condition;
        if($viewtype == 'learner'){
            $sql .= " AND u.id = :userid ";
            $params['userid'] = $USER->id;
            // echo $selectsql.$sql.$groupby;
            return $courses = $DB->get_record_sql($selectsql.$sql.$groupby, $params);
        }else{
            // echo $selectsql.$sql.$groupby;
            $courses = $DB->get_records_sql($selectsql.$sql.$groupby, $params, $stable->start, $stable->length);
            $count = $DB->count_records_sql($countsql.$sql.$groupby.$as, $params, $stable->start, $stable->length);
        }
        return ['count' => $count, 'courses' => $courses];
    }
    /**
     * Returns Target Credits Information.
     *
     * @param string $creditstype Type of Credits Technical/Leadership.
     * @param array $user User Information 
     * @return object
     */
    public static function targetcreditsinfo($creditstype, $user) {
        global $DB;
        // print_r($user);
        $sql = "SELECT SUM(credits) as credits FROM {local_learningdashboard_master} WHERE 1 ";
        if($creditstype){
            $sql .= " AND creditstype =:creditstype ";
        }
        $months = api::tenureinorg($user);
        if($months >= 10){
            $sql .= " AND  :months >=  startmonth  AND endmonth = 0 ";
        }else{
            $sql .= " AND  :months BETWEEN startmonth AND endmonth  ";
        }
        if($months > 3){
            $targetcredits = $DB->get_record_sql($sql,['months' => $months, 'creditstype' => $creditstype]);
        }else{
            $targetcredits['credits'] = '0';
        }
        return $targetcredits;
    }
    /**
     * Returns True when Logged in user is Supervisor then False.
     *
     * @return bool
     */
    public static function issupervisor(){
        global $DB,$USER;
        if($DB->record_exists('user', ['open_supervisorid' => $USER->id]))
            return true;
        else
            return false;
    }
    /**
     * Returns True when Logged in user is Admin then False.
     *
     * @return bool
     */
    public static function isadmin(){
        $systemcontext = \context_system::instance();
        if(!is_siteadmin() && has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            return true;
        }else{
            return false;
        }
    }
    /**
     * Returns Team Users ID's.
     *
     * @return string
     */
    public static function teamusers(){
        global $DB,$USER;
        $users = $DB->get_fieldset_select('user','id', 'open_supervisorid = :open_supervisorid', ['open_supervisorid' => $USER->id]);
        return implode(',',$users);
    }
    /**
     * Returns Tenure of the Organization for User.
     *
     * @param object $user User Information 
     * @return int
     */
    public static function tenureinorg($user){
        $joindate = date('Y-m-d', $user->open_doj);
        $today = date('Y-m-d');
        $date1=date_create($joindate);
        $date2=date_create($today);
        $diff=date_diff($date1,$date2);
        $months = $diff->format("%m");
        $years = $diff->format("%y");
        $months = $months + $years * 12;
        return $months;
    }
}
