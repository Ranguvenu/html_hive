<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This percipiosync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This percipiosync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this percipiosync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * percipiosync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_percipiosync
 */
namespace local_percipiosync\output;

use local_percipiosync\plugin;

use context_system;
use renderable;
use renderer_base;
use stdClass;
use templatable;
use moodle_url;
use moodle_exception;
use context_helper;

defined('MOODLE_INTERNAL') || die();


final class percipiosyncview implements renderable{

    public function display_sync_history($filter = false){
        global $USER,$OUTPUT;

        $systemcontext = context_system::instance();

        $options = array('targetID' => 'display_synchistory','perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        
        $options['methodName']='local_percipiosync_synchistory_view';
        $options['templateName']='local_percipiosync/synchistory'; 
        $options = json_encode($options);

        $dataoptions = json_encode(array('userid' =>$USER->id,'contextid' => $systemcontext->id));
        $filterdata = json_encode(array());

        $context = [
                'targetID' => 'display_synchistory',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];

        if($filter){
            return  $context;
        }else{
            return  $OUTPUT->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }
    /**
     * [display_sync statics description]
     * @method display_sync statics
     * @param  $filter default false
     * @author  sarath
     */
    public function display_sync_historystatics($filter = false){
        global $USER,$OUTPUT;

        $systemcontext = context_system::instance();

        $options = array('targetID' => 'display_synchistorystatics','perPage' => 10, 'cardClass' => 'tableformat', 'viewType' => 'table');
        
        $options['methodName']='local_percipiosync_synchistorystatics_view';
        $options['templateName']='local_percipiosync/synchistorystatistics'; 
        $options = json_encode($options);

        $dataoptions = json_encode(array('userid' =>$USER->id,'contextid' => $systemcontext->id));
        $filterdata = json_encode(array());

        $context = [
                'targetID' => 'display_synchistorystatics',
                'options' => $options,
                'dataoptions' => $dataoptions,
                'filterdata' => $filterdata
        ];

        if($filter){
            return  $context;
        }else{
            return  $OUTPUT->render_from_template('local_costcenter/cardPaginate', $context);
        }
    }
    	/*
	* return count  of sync errors 
	* @return  [type] int count of sync errors
	*/
	public static function manage_percipiohistory_count($stable,$filterdata){
	    global $DB,$USER;
	    $systemcontext = context_system::instance();
	    $params = array();
	    $countsql = " SELECT count(id) ";
	    $selectsql="SELECT * ";
	    $fromsql = " FROM {local_percipiosync_modules} ls where 1=1";
	    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
	        $fromsql .=" ";
	    } else {
	        $fromsql .=" AND usercreated = :usercreated ";
	        $params['usercreated'] = $USER->id;
	    }
	    if(isset($filterdata->search_query) && trim($filterdata->search_query) != ''){
        	$fromsql .= " AND ls.module LIKE :search";
        	$params['search']= '%'.trim($filterdata->search_query).'%';
	    }
	    if($filterdata->percipiosyncstatus > -1 || !empty($filterdata->percipiosyncstatus)){
	        $filterstatus = explode(',', $filterdata->percipiosyncstatus);
	        list($filterstatussql, $filterstatusparams) = $DB->get_in_or_equal($filterstatus, SQL_PARAMS_NAMED, 'param', true, false);
	        $fromsql .= " AND ls.status $filterstatussql";
	        $params=array_merge($params,$filterstatusparams);
	    }
	    $filterdata = (array) $filterdata;
	    if($filterdata['percipiosyncdatetime[enabled]']==true){
            
            $percipiosyncdate_year=$filterdata['percipiosyncdatetime[year]'];
            $percipiosyncdate_month=$filterdata['percipiosyncdatetime[month]'];
            $percipiosyncdate_day=$filterdata['percipiosyncdatetime[day]'];

            $filter_percipiosyncdate_start=mktime(0, 0, 0, $percipiosyncdate_month, $percipiosyncdate_day, $percipiosyncdate_year);
            $filter_percipiosyncdate_end=$filter_percipiosyncdate_start+86399;

            $fromsql.=" AND ls.timecreated BETWEEN :filter_percipiosyncdate_start AND :filter_percipiosyncdate_end";
            $params['filter_percipiosyncdate_start'] = $filter_percipiosyncdate_start;
            $params['filter_percipiosyncdate_end'] = $filter_percipiosyncdate_end;
        }

	    $count = $DB->count_records_sql($countsql.$fromsql,$params);
	    $fromsql .= " ORDER BY id DESC";

	    $synchistory = $DB->get_records_sql($selectsql.$fromsql,$params,$stable->start,$stable->length);

	    return array('count' => $count,'synchistory' => $synchistory);
	}
	/*
	* return data of sync errors 
	* @return  [type] char data of sync errors
	*/
	public static function manage_percipiohistory_content($stable,$filterdata){
	    global $DB;
	    $data=array();
	    $totalsynchistory = self::manage_percipiohistory_count($stable,$filterdata);
	    $synchistory = $totalsynchistory['synchistory'];
	    foreach($synchistory as $history) {
	        $list=array();
	        $list['moduletype']=$history->moduletype;
	        $list['modules']=$history->module;
	        $list['status']=plugin::percipiosyncstatus[$history->status];
	        $list['statusmessage']=$history->statusmessage;
	        $usercreated = $DB->get_record('user', array('id'=>$history->usercreated));
	        $list['usercreated']= $usercreated->firstname. ' '. $usercreated->lastname;
	        $list['timecreated']= date("d/m/Y h:i:s a",$history->timecreated);
	        $data[]=$list;
	    }
	    return $data;
	}

	/*
	* return count  of sync statistics 
	* @return  [type] int count of sync statistics
	*/
	public static function manage_synchistorystatistics_count($stable,$filterdata){
	    global $DB,$USER;
	    $systemcontext = context_system::instance();
	    $params = array();
	    $countsql = " SELECT COUNT(DISTINCT (FROM_UNIXTIME(ls.timecreated,'%Y-%m-%d'))) ";
	    $selectsql="SELECT FROM_UNIXTIME(ls.timecreated,'%Y-%m-%d') as excuteddate ";
	    $fromsql = " FROM {local_percipiosync_modules} as ls where ls.moduletype IS NOT NULL and ls.modulecrud IS NOT NULL ";
	    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
	        $fromsql .=" ";
	    } else {
	        $fromsql .=" AND ls.usercreated = :usercreated ";
	        $params['usercreated'] = $USER->id;
	    }
	    $filterdata = (array) $filterdata;
	    if($filterdata['percipiosyncdatetime[enabled]']==true){
            
            $percipiosyncdate_year=$filterdata['percipiosyncdatetime[year]'];
            $percipiosyncdate_month=$filterdata['percipiosyncdatetime[month]'];
            $percipiosyncdate_day=$filterdata['percipiosyncdatetime[day]'];

            $filter_percipiosyncdate_start=mktime(0, 0, 0, $percipiosyncdate_month, $percipiosyncdate_day, $percipiosyncdate_year);
            $filter_percipiosyncdate_end=$filter_percipiosyncdate_start+86399;

            $fromsql.=" AND ls.timecreated BETWEEN :filter_percipiosyncdate_start AND :filter_percipiosyncdate_end";
            $params['filter_percipiosyncdate_start'] = $filter_percipiosyncdate_start;
            $params['filter_percipiosyncdate_end'] = $filter_percipiosyncdate_end;
        }

	    $fromsql .= " group by FROM_UNIXTIME(ls.timecreated,'%Y-%m-%d') ORDER BY FROM_UNIXTIME(ls.timecreated,'%Y-%m-%d') DESC";

		$count = $DB->get_field_sql($countsql.$fromsql,$params);

		$synchistorystatistics = $DB->get_records_sql($selectsql.$fromsql,$params,$stable->start,$stable->length);
	    
	    return array('count' => $count,'synchistorystatistics' => $synchistorystatistics);
	}


	/*
    * return data of sync statistics 
	* @return  [type] char data of sync statistics
	*/
	public static function manage_synchistorystatistics_content($stable,$filterdata){
	    global $DB,$USER;
	    $countsql = " SELECT count(ls.id) ";
	    $fromsql = " FROM {local_percipiosync_modules} as ls where 1=1 ";
	    if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations',$systemcontext)){
	        $fromsql .=" ";
	    } else {
	        $fromsql .=" AND ls.usercreated = $USER->id ";
	    }

	    $data=array();
	    $totalerrorsstatstics = self::manage_synchistorystatistics_count($stable,$filterdata);
	    $syncstatstics = $totalerrorsstatstics['synchistorystatistics'];
	    foreach($syncstatstics as $syncstatstic) {
	        $list=array();
	        $list['newcoursescount']= $DB->count_records_sql("SELECT COUNT(DISTINCT(moduleid)) FROM {local_percipiosync_modules} WHERE moduletype LIKE '%course%' AND modulecrud LIKE '%create%' and FROM_UNIXTIME(timecreated,'%Y-%m-%d')=:excuteddate AND moduleid <> 0 ",array('excuteddate'=>$syncstatstic->excuteddate));

	        $list['updatedcoursescount']= $DB->count_records_sql("SELECT COUNT(DISTINCT(moduleid)) FROM {local_percipiosync_modules} WHERE moduletype LIKE '%course%' AND modulecrud LIKE '%update%' and FROM_UNIXTIME(timecreated,'%Y-%m-%d')=:excuteddate AND moduleid <> 0 ",array('excuteddate'=>$syncstatstic->excuteddate));

	        $list['courseserrorscount']= $DB->count_records_sql($countsql.$fromsql." and  ls.moduletype='course' and FROM_UNIXTIME(ls.timecreated,'%Y-%m-%d')=:excuteddate and ls.status in (0,2) ",array('excuteddate'=>$syncstatstic->excuteddate));

	        $list['timecreated']= $syncstatstic->excuteddate;

	        $data[]=$list;
	    }
	    return $data;
	}
}
