<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This coursera is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This coursera is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this coursera.  If not, see <http://www.gnu.org/licenses/>.

/**
 * coursera local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_coursera
 */
use local_coursera\plugin;

require('../../config.php');
global $SITE, $PAGE, $OUTPUT;

require_login();

if(!is_siteadmin()) {           // No access to all but site admins.
    print_error('nopermissions', 'core');
}
$t = optional_param('test',-1, PARAM_INT);
$crud = optional_param('crud','r', PARAM_TEXT);
$context = context_system::instance();
$PAGE->set_context($context);
// $PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname',plugin::COMPONENT));
$PAGE->set_url(new moodle_url('/local/coursera/configsettings.php'));

$PAGE->set_pagetype('admin-' . $PAGE->pagetype);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname',plugin::COMPONENT));
$returnurl = new moodle_url('/local/courses/courses.php');
/* Hack to allow us to test the plugin from the web interface */
if (is_siteadmin() && ($t > -2)&& ($crud)){
    raise_memory_limit(MEMORY_EXTRA);
    @set_time_limit(HOURSECS);          // Allow one hour

    if (plugin::crud_coursera($t,$crud)) {
        echo $OUTPUT->notification('Successfully updated - check logs for details', 'success');
    }else{
        echo $OUTPUT->notification('Failed to update - check logs for details', 'error');
    }

} else {
    print_error('nopermissions', 'core');
}
$linkname = get_string('continue');
$continue = new single_button($returnurl, $linkname, 'post', true);
$continue->class= 'continuebutton';  // Seems a bit hackish, ahem.
echo $OUTPUT->render($continue);

echo $OUTPUT->footer();

