<?php
require_once('../../config.php');

require_once($CFG->dirroot . '/local/externalcertificate/lib.php');
	
require_login();


global $CFG, $PAGE, $OUTPUT, $DB,$USER;
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('local_externalcertificate/external_certificates','load',array());

$systemcontext = \context_system::instance();
 if(!is_siteadmin() && !(has_capability('local/externalcertificate:manage', $systemcontext) && has_capability('local/externalcertificate:view', $systemcontext))){
  print_error('nopermissiontoviewpage');
}
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/externalcertificate/master_certificatedata.php');
$PAGE->set_title(get_string('addnewmasterextcourse','local_externalcertificate'));
$PAGE->set_heading(get_string('addnewmasterextcourse', 'local_externalcertificate'));
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('local_externalcertificate');
$filterparams = $renderer->display_masterexternalcertificates(true);
   echo '<button class="course_extended_menu_itemlink btn btn-primary pull-right" style="float:right; cursor: pointer;" onclick="(function(e){
                require(\'local_externalcertificate/external_certificates\').mastercertificate_form({
                    contextid:1, id:0, callback:\'mastercertificate_form\', component:\'local_externalcertificate\', pluginname:\'local_externalcertificate\'
                })
            })(event)" title="'.get_string('addnewmastercourse', 'local_externalcertificate').'">
            '. get_string('addnewmastercourse', 'local_externalcertificate') .'
            </button>';
echo $OUTPUT->render_from_template('local_costcenter/global_filter', $filterparams);
echo $renderer->display_masterexternalcertificates();
echo $OUTPUT->footer();
