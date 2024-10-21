<?php
namespace local_onlinetests\local;
class userdashboard_content extends \block_userdashboard\content{
	public function userdashboard_menu_content(){
		$returndata = array();
		$returndata['id'] = 'onlinetest_courses';
		$returndata['order'] = 7;
		$returndata['pluginname'] = 'local_onlinetests';
		$returndata['tabname'] = 'inprogress';
		$returndata['status'] = 'inprogress';
		$returndata['class'] = 'userdashboard_menu_link';
		$returndata['iconclass'] = 'fa fa-desktop';
		$returndata['label'] = get_string('onlineexams', 'block_userdashboard');
		$returndata['templatename'] = 'local_onlinetests/userdashboard_content';
		return $returndata;
	}
	public static function inprogress_onlinetests($filter_text='', $offset, $limit) {
        global $DB, $USER;
        $sqlquery = "SELECT a.*, ou.timecreated, ou.timemodified as joindates";
        $sql = " FROM {local_onlinetests} a, {local_onlinetest_users} ou
            WHERE a.id = ou.onlinetestid AND ou.userid = {$USER->id}
            AND a.visible = 1 AND ou.status = 0";
        if(!empty($filter_text)){
            $sql .= " AND a.name LIKE '%%{$filter_text}%%'";
        }
        $sql .= " ORDER BY ou.timecreated DESC";
        $inprogress_onlinetests = $DB->get_records_sql($sqlquery . $sql, array(), $offset, $limit);
        return $inprogress_onlinetests;

    }

    public static function inprogress_onlinetests_count($filter_text = ''){
        global $DB, $USER;
        $sql = "SELECT COUNT(a.id) 
            FROM {local_onlinetests} a 
            JOIN {local_onlinetest_users} ou ON a.id = ou.onlinetestid 
            WHERE  ou.userid = {$USER->id} AND a.visible = 1 AND ou.status = 0";
        if(!empty($filter_text)){
            $sql .= " AND a.name LIKE '%%{$filter_text}%%'";
        }
        $inprogressCount = $DB->count_records_sql($sql);
        return $inprogressCount;
    } 


    public static function completed_onlinetests($filter_text='', $offset, $limit){
        global $DB,$USER;
        $sqlquery = "SELECT a.*, ou.timecreated, ou.timemodified as joindates";

        $sql = " FROM {local_onlinetests} a, {local_onlinetest_users} ou
            WHERE a.id = ou.onlinetestid AND ou.userid = $USER->id
            AND a.visible = 1 AND ou.status = 1";
        if(!empty($filter_text)){
            $sql .= " AND a.name LIKE '%%{$filter_text}%%'";
        }
        $sql .= " ORDER BY ou.timemodified DESC";
        
        $completed_onlinetests = $DB->get_records_sql($sqlquery . $sql, array(), $offset, $limit);
        
        return $completed_onlinetests;
    }

    public static function completed_onlinetests_count($filter_text = ''){
        global $DB,$USER;
        $sql = " SELECT COUNT(a.id) 
            FROM {local_onlinetests} a
            JOIN {local_onlinetest_users} ou ON a.id = ou.onlinetestid 
            WHERE ou.userid = {$USER->id} AND a.visible = 1 AND ou.status = 1 ";
        if(!empty($filter_text)){
            $sql .= " AND a.name LIKE '%%{$filter_text}%%'";
        }
        $completedCount = $DB->count_records_sql($sql);
        return $completedCount;
    }
}