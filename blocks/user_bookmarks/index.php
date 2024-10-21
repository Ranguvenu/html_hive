<?php
global $OUTPUT, $CFG, $COURSE,$DB;
$systemcontext = context_system::instance();   
    
/** @var stdClass $config */
$returnoutput='';
$bookmarktabs=array();
$renderer = $PAGE->get_renderer('block_user_bookmarks');
$cardparams = $renderer->get_usersbookmarks(); 

$filtervalues = json_decode($cardparams['filterdata']);
$data_object = (json_decode($cardparams['dataoptions']));

if((!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext)) ){
    $cardparams['filtertype']= 'mybookmarked_courses';
    $bookmarktabs[] = array('active' => 'active','type' => 'mybookmarked_courses', 'filterform' => array(), 'canfilter' => true, 'show' => '','name' => 'mymybookmarked_courses');
}    

$fncardparams=$cardparams;                  

if($bookmarktabs){
    $cardparams = $fncardparams+array(
            'bookmarktabs' => $bookmarktabs,
            'contextid' => $systemcontext->id,
            'plugintype' => 'block',
            'plugin_name' =>'user_bookmarks',
            'cfg' => $CFG);
          
    echo  $returnoutput = $OUTPUT->render_from_template('block_user_bookmarks/block_userbookmarks', $cardparams);
}



























// global $OUTPUT, $CFG, $COURSE, $DB, $USER;
// require_once($CFG->dirroot.'/local/learningsummary/lib.php');
// $systemcontext = context_system::instance();   
// // $PAGE->requires->css('/blocks/user_bookmarks/style.css');



// $menulinks = get_coursetypes($blocktype);
// $cardparams['links'] = $menulinks;
// $cardparams['blocktype'] = 'completed' ;
// if((!is_siteadmin() && !has_capability('local/costcenter:manage_ownorganization',$systemcontext) && !has_capability('local/costcenter:manage_owndepartments',$systemcontext)) ){
//     $cardparams['filtertype']= 'my'.$blocktype.'courses';
   
// }    
// $fncardparams=$cardparams;                  


// $cardparams = $fncardparams+array(
//     'contextid'     => $systemcontext->id,
//     'plugintype'    => 'block',
//     'plugin_name'   => 'user_bookmarks',
//     'cfg'           => $CFG,
//     'result'        => $row,
// );


// $renderer = $PAGE->get_renderer('user_bookmarks');
// $renderer->get_users_bookmarks();

// // echo $renderer->get_users_bookmarks();

// // echo  $returnoutput = $OUTPUT->render_from_template('block_user_bookmarks/user_bookmarks', $cardparams);
// // echo $OUTPUT->paging_bar(100, 0, 10, 'http://localhost/fractal_upgrade/local/learningsummary/index.php');

