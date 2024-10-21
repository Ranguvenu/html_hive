<?php
defined('MOODLE_INTERNAL') || die();
function xmldb_enrol_auto_uninstall() {
	global $DB;
	$dbman = $DB->get_manager();
    $table = new xmldb_table('enrol');
	if ($dbman->table_exists($table)) {
		$sql = 'ALTER TABLE `mdl_enrol`
  			DROP `department`,DROP `open_group`,DROP `open_hrmsrole`,DROP `open_designation`,
  			DROP `open_location`';
  		$DB->execute($sql);
	}
}
