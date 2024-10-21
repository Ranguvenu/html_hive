 <?php
require_once(dirname(__FILE__).'/../../config.php');
global $PAGE,$CFG;
$PAGE->set_url($CFG->wwwroot.'/blocks/nps/nps_info.php');
$PAGE->set_title(get_string('npsinfo','block_nps'));
$PAGE->set_context(context_system::instance());
require_login();
$PAGE->set_pagelayout('standard');
$renderer = $PAGE->get_renderer('block_nps');

//============Js files===============
$PAGE->requires->jQuery();
$PAGE->requires->js('/blocks/nps/js/jquery.dataTables.js',true);
$PAGE->requires->js('/blocks/nps/js/custom.js');
$PAGE->requires->css($CFG->dirroot.'/blocks/nps/css/jquery.dataTables.css');
$PAGE->requires->css($CFG->dirroot.'/blocks/nps/styles.css');

echo $OUTPUT->header();
$head=html_writer::tag('h2',get_string('nps','block_nps'),array('class'=>'tmhead2'));
echo $OUTPUT->heading($head);

echo $renderer->nps_view();

  
echo $OUTPUT->footer();