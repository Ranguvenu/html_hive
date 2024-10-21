<?php
require_once(dirname(__FILE__) . '/../../config.php');

global $DB, $PAGE,$CFG;
require_login();
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$configlist = array('hide_save'=>$CFG->hide_save,
	'hide_svg_download_on_Android'=>$CFG->hide_svg_download_on_Android,
	'hide_svg_download'=>$CFG->hide_svg_download,
	'hide_png_one_download'=>$CFG->hide_png_one_download,
	'hide_png_two_download'=>$CFG->hide_png_two_download, 
	'hide_gravatar'=>$CFG->hide_gravatar);
echo json_encode($configlist);
