<?php
/**
 * Learning Summary In-Progress block.
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_inprogress
 */

use block_learningsummary_inprogress\plugin;

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_learningsummary_inprogress
 *
 * @author eabyas  <info@eabyas.in>
 * @package fractal
 * @subpackage block_learningsummary_inprogress
 */
final class block_learningsummary_inprogress extends block_base {

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

        global $OUTPUT, $CFG, $COURSE,$DB;

        $systemcontext = context_system::instance();
        
        if (isloggedin() and ($this->content === null)) {
            if(get_user_preferences('auth_forcepasswordchange') || $COURSE->id != 1){
                return (object)[
                    'text' => '',
                    'footer' => ''
                ];
            }        
           
            /** @var stdClass $config */
           // $this->config->coursetype = 1;//comment by revathi
            $returnoutput='';

            $tabs=array();
            $filterdata = json_encode(array());

            $perPage = 3;
           
            $options = array('targetID' => 'myinprogresscourses','perPage' => $perPage, 'cardClass' => 'col-md-4 col-sm-6', 'viewType' => 'card');

            $dataoptions['search_query'] = '' ;
            $dataoptions['id'] = 0 ;
            $dataoptions['coursetype'] = 'All' ;
            $dataoptions['blocktype'] = 'inprogress' ;
            $options['methodName']='block_learningsummary_inprogress_getcontent';
            $options['templateName']='block_learningsummary_inprogress/viewmyinprogresscourses';
            
            $carddataoptions = json_encode($dataoptions);
            $cardoptions = json_encode($options);
            $cardparams = array(
                'targetID' => 'myinprogresscourses',
                'options' => $cardoptions,
                'dataoptions' => $carddataoptions,
                'filterdata' => $filterdata,
                
            );           
           
            if((!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext)) && ($this->config->coursetype==plugin::INPROGRESSCOURSES)){
                $cardparams['filtertype']= 'myinprogresscourses';
                $tabs[] = array('active' => 'active','type' => 'myinprogresscourses', 'filterform' => array(), 'canfilter' => true, 'show' => '','name' => 'myinprogresscourses','coursetype'=>'inprogresscourses');
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
                        'plugin_name' =>'learningsummary_inprogress',
                        'cfg' => $CFG);
               $returnoutput = $OUTPUT->render_from_template('block_learningsummary_inprogress/block_learningsummary_inprogress', $cardparams);
            }
        
            $this->content = (object)[
                'text' => $returnoutput,
                'footer' => ''
            ];
        }
        return $this->content;
    }
}
