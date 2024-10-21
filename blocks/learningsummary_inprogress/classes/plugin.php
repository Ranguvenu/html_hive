<?php
/**
 * Inprogress Courses list block plugin helper
 *
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_inprogress
 */

namespace block_learningsummary_inprogress;

defined('MOODLE_INTERNAL') || die;

/**
 * Class plugin
 * @author eabyas  <info@eabyas.in>
 * @package Bizlms
 * @subpackage block_learningsummary_inprogress
 */
abstract class plugin {
    /** @var string */
    const COMPONENT = 'block_learningsummary_inprogress';
    
    /** @var int */
    const INPROGRESSCOURSES = 1;  

    public static function get_inprogress_content($stable,$filtervalues,$data_object){
        global $CFG;
        
        $coursetype = $data_object->coursetype;
        require_once($CFG->dirroot.'/local/courses/lib.php');
        $inprogressdata = get_learningsummary_content($coursetype , $filtervalues,$data_object, $stable);
        return $inprogressdata;
    }

}
