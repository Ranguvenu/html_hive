<?php
/**
 *
 *
 * @author eabyas  <info@eabyas.in>

 * @copyright 2020 Fortech inc
 * @subpackage local_learningsummary
 */

namespace local_learningsummary\output;

use plugin_renderer_base;
use moodle_exception;

defined('MOODLE_INTERNAL') || die;

class renderer extends plugin_renderer_base {

    protected function render_summary(summary $widget) {
        $context = $widget->export_for_template($this);
        return $context;
    }

    public function get_learningsummary_content($blocktype){
       
        $filterdata = json_encode(array());

        $options = array('targetID' => 'my'.$blocktype.'courses','perPage' => 3, 'cardClass' => 'col-md-4 col-sm-6', 'viewType' => 'card');

        $dataoptions['search_query'] = '' ;
        $dataoptions['id'] = 0 ;
        $dataoptions['coursetype'] = 'All' ;
        $dataoptions['blocktype'] = $blocktype ;
        $options['methodName']='local_learningsummary_getcontent';
        $options['templateName']='block_learningsummary_'.$blocktype.'/viewmy'.$blocktype.'courses';

        $carddataoptions = json_encode($dataoptions);
        $cardoptions = json_encode($options);

        $context = array(
            'targetID' => 'my'.$blocktype.'courses',
            'options' => $cardoptions,
            'dataoptions' => $carddataoptions,
            'filterdata' => $filterdata, 
        );   

        return  $context;
    }

}