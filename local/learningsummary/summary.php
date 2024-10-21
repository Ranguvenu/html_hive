<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/local/learningsummary/lib.php');

global $PAGE, $CFG;
$systemcontext = \context_system::instance();
$PAGE->set_context($systemcontext);

$PAGE->set_url('/local/learningsummary/index.php');
$PAGE->set_title(get_string('pluginname','local_learningsummary'));
$PAGE->set_heading(get_string('pluginname','local_learningsummary'));
$PAGE->requires->js('/blocks/user_bookmarks/js/javascript_file.js');
/** @var stdClass $config */
$blocktypesarray = array('inprogress', 'completed');

echo $OUTPUT->header(); 

foreach($blocktypesarray as $btype){ 
    $tabs=array();
    $returnoutput='';
    $blocktype = $btype;
    $renderer = $PAGE->get_renderer('local_learningsummary');
    $cardparams = $renderer->get_learningsummary_content($blocktype); 

    $filtervalues = json_decode($cardparams['filterdata']);
    $data_object = json_decode($cardparams['dataoptions']);

    $stable = new \stdClass();
    $stable->thead = false;

    $totalcourses = get_learningsummary_data($filtervalues,$data_object, $stable);  
    $totalcount = $totalcourses['allcoursecount']; 

    $menulinks = get_coursetypes($blocktype);
    $cardparams['links'] = $menulinks;   
    $cardparams['filtertype']= 'my'.$blocktype.'courses';
    $tabs[] = array('active' => 'active','type' => 'my'.$blocktype.'courses', 'filterform' => array(), 'canfilter' => true, 'show' => '','name' => 'my'.$blocktype.'courses','coursetype'=>''.$blocktype.'courses');
     
    $fncardparams=$cardparams; 

    if($tabs){
        $cardparams = $fncardparams+array(
                'tabs' => $tabs,
                'contextid' => $systemcontext->id,
                'plugintype' => 'block',
                'plugin_name' =>'learningsummary_'.$blocktype,
                'cfg' => $CFG,
                'totalcount' => $totalcount);
        echo  $returnoutput = $OUTPUT->render_from_template('local_learningsummary/learningsummary_'.$blocktype, $cardparams);
    }
}
include_once($CFG->dirroot . '/blocks/user_bookmarks/index.php');
include_once($CFG->dirroot . '/blocks/empcredits/index.php');
echo $OUTPUT->footer();