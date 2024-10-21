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
 * @package   Bizlms
 * @subpackage  training_calendar
 * @author eabyas  <info@eabyas.in>
**/
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/training_calendar/lib.php');
$instance = optional_param('instance',0,PARAM_INT);
$plugin = optional_param('plugin','',PARAM_RAW);
global $DB, $USER, $CFG;
$dbman = $DB->get_manager();
if ($dbman->table_exists($plugin)) {
	$table = $CFG->prefix.''.$plugin;
	$itemsql = "SELECT * from {$table} where id = ? ";
	$get_item = $DB->get_record_sql($itemsql, array($instance));
	if ($get_item->status ==1){
        $return = 1;
        if($plugin=='local_classroom'){
			  $waitlist = $DB->get_field('local_classroom_waitlist','id',array('classroomid' => $instance,'userid'=>$USER->id,'enrolstatus'=>0));
                if($waitlist > 0){
                    $return = 4;
                }else{
					$exists=$DB->record_exists_sql("SELECT id FROM {local_request_records} WHERE compname LIKE 'classroom' AND componentid = {$instance} AND status LIKE 'PENDING'");
		
					if($get_item->approvalreqd==1&&!$exists){
		               
                       $return = 2;
					}elseif($get_item->approvalreqd==1&&$exists){
						
						$return = 3;
					}
				}
        }
    }
	else{
            $return = 0;
    }
} else {
	$return = 0;

}
echo json_encode($return);
