<?php
global $OUTPUT, $CFG, $COURSE,$DB,$PAGE;
require_once($CFG->dirroot.'/local/learningsummary/lib.php');
$systemcontext = context_system::instance();   
    
/** @var stdClass $config */
$returnoutput='';
$tabs=array();
$PAGE->requires->js('/blocks/user_bookmarks/js/javascript_file.js');

$blocktype = 'completed';
$renderer = $PAGE->get_renderer('local_learningsummary');
$cardparams = $renderer->get_learningsummary_content($blocktype); 

$filtervalues = json_decode($cardparams['filterdata']);
$data_object = (json_decode($cardparams['dataoptions']));

$stable = new \stdClass();
$stable->thead = false;

$totalcourses = get_learningsummary_data($filtervalues,$data_object, $stable);  
$totalcount = $totalcourses['allcoursecount']; 

$menulinks = get_coursetypes($blocktype);
$cardparams['links'] = $menulinks;
$cardparams['blocktype'] = 'completed' ;
if((!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext)) ){
    $cardparams['filtertype']= 'my'.$blocktype.'courses';
    $tabs[] = array('active' => 'active','type' => 'my'.$blocktype.'courses', 'filterform' => array(), 'canfilter' => true, 'show' => '','name' => 'my'.$blocktype.'courses','coursetype'=>''.$blocktype.'courses');
}    
$fncardparams=$cardparams;                  

if($tabs){
    $cardparams = $fncardparams+array(
            'tabs' => $tabs,
            'contextid' => $systemcontext->id,
            'plugintype' => 'block',
            'plugin_name' =>'learningsummary_completed',
            'cfg' => $CFG,
            'totalcount' => $totalcount);
    echo  $returnoutput = $OUTPUT->render_from_template('block_learningsummary_completed/block_learningsummary_completed', $cardparams);
}