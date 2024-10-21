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
 * @subpackage local_request
 */


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/local/courses/filters_form.php');
$component = optional_param('component', null, PARAM_RAW);
$courseid = optional_param('courseid', '', PARAM_INT);
global $OUTPUT, $PAGE, $USER;

//$pagecontextid = optional_param('pagecontextid',1, PARAM_INT);  // Reference to the context we came from.
//$search = optional_param('search', '', PARAM_RAW);

require_login(); 
$title = get_string('viewrequest', 'local_request');

// Set up the page.
$url = new moodle_url("/local/request/requestview.php");
$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);

$PAGE->set_pagelayout('admin');
$PAGE->set_url($url);

$PAGE->set_title($title);
$PAGE->navbar->add(get_string("pluginname", 'local_request'));
$PAGE->set_heading($title);
$PAGE->requires->js_call_amd('local_request/requestconfirm', 'load', array());

$output = $PAGE->get_renderer('local_request');
echo $output->header();
    $list_comp = array();
    $sorting = false;
    $usercontext = context_system::instance();
    if(has_capability('local/request:viewrecord',$usercontext) || is_siteadmin()){    
    $mform = new filters_form($CFG->wwwroot . '/local/request/index.php', array('filterlist'=>array('request')));
    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/local/request/index.php');
    } else if ($filterdata =  $mform->get_data()){
        // $filterdata =  $mform->get_data();
        if($filterdata){
            $collapse = false;
            $show = 'show';
        } else{
            $collapse = true;
            $show = '';
        }
        if(!empty($filterdata->request)){
            $listid_comp =  implode(',', $filterdata->request);
            $list_comp = $DB->get_fieldset_sql("SELECT compname  FROM {local_request_records} WHERE id IN ($listid_comp)");
            
            $listid =  "'".implode("','", $list_comp)."'";
            $list = $DB->get_records_sql("SELECT *  FROM {local_request_records} WHERE compname IN ($listid)");
        }else{
            $collapse = true;
            $show = '';
            $list=null;
        }
        $sorting = $filterdata->sorting;
    }else{
        $collapse = true;
        $show = '';
        $list=null;
    }

    // $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';
    // print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
    // $mform->display();
    // print_collapsible_region_end(); 
        echo '<a class="btn-link btn-sm" title="Filter" href="javascript:void(0);" data-toggle="collapse" data-target="#local_request-filter_collapse" aria-expanded="false" aria-controls="local_request-filter_collapse">
                <i class="m-0 fa fa-sliders fa-2x" aria-hidden="true"></i>
              </a>';
        echo  '<div class="collapse '.$show.'" id="local_request-filter_collapse">
                    <div id="filters_form" class="card card-body p-2">';
                        $mform->display();
        echo        '</div>
                </div>';
        //$renderable = new local_request\output\requestview();
    //  $data = $renderable->export_for_template($output);
     //  echo   $res= $output->render_from_template('local_request/requestview', $data);
       
       // $list=null;
       echo $output->render_requestview(new local_request\output\requestview($list, $list_comp, $sorting,true));
    }
    else{
       
        print_error('permission denied');
    } 
        



echo $output->footer();
