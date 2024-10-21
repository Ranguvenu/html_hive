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


 * Learning Summary In-Progress block.
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_completed
 */

use block_learningsummary_completed\output\blockview;
use block_learningsummary_completed\plugin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_learningsummary_completed
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_completed
 */
final class block_learningsummary_completed extends block_base {

    /**
     * Initialize the block title
     * @throws coding_exception
     */
    public function init() {
      $this->title = get_string('pluginname', plugin::COMPONENT);
    }
    /**
     * Generate the block content
     * @return stdClass|null
     * @throws coding_exception
     */
    public function get_content() {

        global $OUTPUT, $PAGE,$CFG, $COURSE,$DB,$USER;

        $systemcontext = context_system::instance();
        if (isloggedin() and ($this->content === null)) {
            if(get_user_preferences('auth_forcepasswordchange') || $COURSE->id != 1){
                return (object)[
                    'text' => '',
                    'footer' => ''
                ];
            }        
           
            /** @var stdClass $config */
            //$this->config->coursetype = 1;//commentby revathi
            $returnoutput='';

            $tabs=array();
            $filterdata = json_encode(array());

            $perPage = 3;
           
            $options = array('targetID' => 'mycompletedcourses','perPage' => $perPage, 'cardClass' => 'col-md-4 col-sm-6', 'viewType' => 'card');

            $dataoptions['search_query'] = '' ;
            $dataoptions['id'] = 0 ;
            $dataoptions['coursetype'] = 'All' ;
            $dataoptions['blocktype'] = 'completed' ;
            $options['methodName']='block_learningsummary_completed_getcontent';
            $options['templateName']='block_learningsummary_completed/viewmycompletedcourses';
            
            $carddataoptions = json_encode($dataoptions);
            $cardoptions = json_encode($options);
            $cardparams = array(
                'targetID' => 'mycompletedcourses',
                'options' => $cardoptions,
                'dataoptions' => $carddataoptions,
                'filterdata' => $filterdata,
                
            );           
 
            if((!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext)) && ($this->config->coursetype==plugin::COMPLETEDCOURSES)){
                $cardparams['filtertype']= 'mycompletedcourses';
                $tabs[] = array('active' => 'active','type' => 'mycompletedcourses', 'filterform' => array(), 'canfilter' => true, 'show' => '','name' => 'completedcourses','coursetype'=>'completedcourses');
            }        
           
            $menulinks = array();
            $i = 2;
            $coursetypes = $DB->get_records('local_course_types',array('active' => 1),'id asc','id,course_type,shortname');
            foreach($coursetypes as $ctype){
              
                $returndata = array();
                $returndata['id'] = $ctype->id;
                $returndata['order'] = $i;
                $returndata['coursetype'] = $ctype->course_type;
                $menulinks[$returndata['order']-1 ] = $returndata;
                $i++;
            }  

            $returndata['id'] = 0;
            $returndata['order'] = 1;
            $returndata['coursetype'] = 'ALL';
            $menulinks[$returndata['order'] -1] = $returndata;

            ksort($menulinks);
            $menulinks = array_values($menulinks);
            $cardparams['links'] = $menulinks;
            $fncardparams=$cardparams;   
               
           
            if($tabs){
                $cardparams = $fncardparams+array(
                        'tabs' => $tabs,
                        'contextid' => $systemcontext->id,
                        'plugintype' => 'block',
                        'plugin_name' =>'learningsummary_completed',
                        'cfg' => $CFG);
               $returnoutput = $OUTPUT->render_from_template('block_learningsummary_completed/block_learningsummary_completed', $cardparams);
            }
       
            $this->content = (object)[
                'text' => $returnoutput,
                'footer' => ''
            ];
        }
        return $this->content;
    }
}
