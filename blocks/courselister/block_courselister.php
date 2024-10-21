<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This courselister is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This courselister is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this courselister.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course list block.
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_courselister
 */

use block_courselister\output\blockview;
use block_courselister\plugin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_courselister
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_courselister
 */
final class block_courselister extends block_base {

    /**
     * Initialize the block title
     * @throws coding_exception
     */
    public function init() {
        global $DB;  
        $dbman = $DB->get_manager();
        if($dbman->table_exists('local_featured_courses')){
            if ($dbman->field_exists('local_featured_courses', 'title')) {
                $title = $DB->get_field('local_featured_courses','title', array());
                $this->title = (!empty($title)) ? $title :  get_string('pluginname', plugin::COMPONENT);
            }
        }else
            $this->title = get_string('pluginname', plugin::COMPONENT);
    }
    /**
     * Generate the block content
     * @return stdClass|null
     * @throws coding_exception
     */
    public function get_content() {

        global $OUTPUT, $PAGE,$CFG, $COURSE;

        $systemcontext = context_system::instance();
        //if(isset($this->config->coursetype)){
         // $this->config->coursetype = 3;
        //}
        
        //$PAGE->requires->js_call_amd('local_catalog/courseinfo', 'load', array());
        $PAGE->requires->js_call_amd('local_search/courseinfo','load',array());
        $PAGE->requires->js('/blocks/user_bookmarks/js/javascript_file.js');
        $PAGE->requires->js_call_amd('local_learningplan/courseenrol', 'load');
        $PAGE->requires->js_call_amd('local_request/requestconfirm', 'load', array());
        if (isloggedin() and ($this->content === null)) {
            if(get_user_preferences('auth_forcepasswordchange') || $COURSE->id != 1){
                return (object)[
                    'text' => '',
                    'footer' => ''
                ];
            }  
           
          
            $returnoutput='';

            $tabs=array();
            $filterdata = json_encode(array());

            $perPage = 3;
           
            $options = array('targetID' => 'myselectedcourses','perPage' => $perPage, 'cardClass' => 'pl-0 pr-4 col-md-4 col-sm-6', 'viewType' => 'card');

            $dataoptions['coursetype'] = 3 ;
            $options['methodName']='block_courselister_get_myselectedcourses';
            $options['templateName']='block_courselister/viewmyselectedcourses';
            
            $carddataoptions = json_encode($dataoptions);
            $cardoptions = json_encode($options);
            $cardparams = array(
                'targetID' => 'myselectedcourses',
                'options' => $cardoptions,
                'dataoptions' => $carddataoptions,
                'filterdata' => $filterdata,
                
            );           
 
            if((!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext))){// && ($this->config->coursetype==plugin::SELECTEDCOURSES)
                $cardparams['filtertype']= 'myselectedcourses';
                $tabs[] = array('active' => 'active','type' => 'myselectedcourses', 'filterform' => array(), 'canfilter' => true, 'show' => '','name' => 'selectedcourses','coursetype'=>'selectedcourses');
            }
             $fncardparams=$cardparams;
            if($tabs){
                $cardparams = $fncardparams+array(
                        'tabs' => $tabs,
                        'contextid' => $systemcontext->id,
                        'plugintype' => 'block',
                        'plugin_name' =>'courselister',
                        'cfg' => $CFG);
               $returnoutput.=$OUTPUT->render_from_template('block_courselister/block_courselister', $cardparams);
            }
          
            $this->content = (object)[
                'text' => $returnoutput,
                'footer' => ''
            ];
        }
        return $this->content;
    }
}
