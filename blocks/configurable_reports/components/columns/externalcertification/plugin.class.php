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

/** CobaltLMS Reports
  * A Moodle block for creating customizable reports
  * @package blocks
  * @author:
  * @date: 2020
  */

require_once($CFG->dirroot.'/blocks/configurable_reports/plugin.class.php');

class plugin_externalcertification extends plugin_base{

	function init(){
		$this->fullname = get_string('report_externalcertification','block_configurable_reports');
		$this->type = 'undefined';
		$this->form = true;
		$this->reporttypes = array('externalcertification');
	}
	
	function summary($data){		
		return format_string($data->columname);
	}
	
	function colformat($data){
		$align = (isset($data->align))? $data->align : '';
		$size = (isset($data->size))? $data->size : '';
		$wrap = (isset($data->wrap))? $data->wrap : '';
		return array($align,$size,$wrap);
	}	
	
	function execute($data,$row,$user,$courseid,$starttime=0,$endtime=0){
		global $DB;
		return (isset($row->{$data->column}))? $row->{$data->column} : '';
	}
	
}
  //Ended by rajut for adding columns for manager reports
