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
 * Configurable Reports
 * A Moodle block for creating Configurable Reports
 * @package blocks
 * @author: Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date: 2009
 

**/
function block_configurable_reports_leftmenunode(){
    $systemcontext = context_system::instance();
    $reportsnode = '';
    if(has_capability('block/configurable_reports:managereports', $systemcontext) || is_siteadmin()){
        $reportsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_browsereports', 'class'=>'pull-left user_nav_div browsereports'));
        $report_url = new moodle_url('/blocks/configurable_reports/managereport.php?courseid=1');

       // if(has_capability('block/configurable_reports:managereports', $systemcontext) || is_siteadmin())) {
            $report_label = get_string('cpreports','block_configurable_reports');
      //  }
        $reports = html_writer::link($report_url, '<span class="reports_icon left_menu_icons"></span><span class="user_navigation_link_text">'.$report_label.'</span>',array('class'=>'user_navigation_link'));
        $reportsnode .= $reports;
        $reportsnode .= html_writer::end_tag('li');
    }

    return array('12' => $reportsnode);
}


