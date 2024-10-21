<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/empcredits/ilp_startendform.php');//Instantiate simplehtml_form
require_once($CFG->dirroot.'/blocks/empcredits/lib.php');
$id = optional_param('id',0,PARAM_INT);


global $CFG, $PAGE, $OUTPUT,$DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('incourse');
$PAGE->set_title('Start & End Dates for Credits');
$PAGE->navbar->add('Start & End Dates for Credits');
$PAGE->set_url($CFG->wwwroot.'/blocks/empcredits/ilp_startend.php');

require_login();
$renderer = $PAGE->get_renderer('block_empcredits');
$mform = new ilp_startendform(null,array('id'=>$id));
echo $OUTPUT->header();
echo html_writer:: tag('h2', get_string('ilpstartenddates','block_empcredits'), array('class'=>'tmhead2'));
if ($mform->is_cancelled()) {
    

}else if ($fromform =  $mform->get_data()) {
        insert_ilp_startend_dates($fromform);
        redirect('ilp_startend.php');
}

//================edit===============
if($id>0){
    $edit=edit_ilpdates($id);
    $mform->set_data($edit);
    $mform->display();
}

  echo $renderer->ilp_strartend_view_table();


echo $OUTPUT->footer();



