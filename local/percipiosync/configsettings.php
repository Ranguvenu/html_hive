<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This percipiosync is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This percipiosync is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this percipiosync.  If not, see <http://www.gnu.org/licenses/>.

/**
 * percipiosync local settings
 * @author eabyas  <info@eabyas.in>
 * @package    eabyas
 * @subpackage local_percipiosync
 */

require('../../config.php');

global $SITE, $PAGE, $OUTPUT;
use local_percipiosync\plugin;

require_once('classes/settingsform.php');

require_login();

if(!is_siteadmin()) {
    print_error('nopermissions');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname',plugin::COMPONENT));
$PAGE->set_url(new moodle_url('/local/percipiosync/configsettings.php'));

$PAGE->set_pagetype('admin-' . $PAGE->pagetype);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname',plugin::COMPONENT));

$settings = plugin::get_plugin_settings();
$defaultsettings = plugin::get_default_settings();

if (!empty($settings['coursemappings'])) {
    $settings['coursemappings'] = unserialize($settings['coursemappings']);
}


$formdata = array_merge($settings);
$mappingfields = plugin::get_mapping_fields();
$formdata['coursemappingfields'] = $mappingfields['coursefields'];
$formdata['coursecustomfields'] = plugin::get_course_customfields();

// die(html_writer::tag('pre', print_r($formdata, true)));

$form = new local_percipiosync_form(null, $formdata);

if ($form->is_submitted() && $form->is_validated()) {
    $data = (array) $form->get_data();

    // Settings setup
    foreach (array_keys($defaultsettings) as $setting) {
        if ($data[$setting]) {
            set_config($setting, $data[$setting],plugin::COMPONENT);
        } else {
            // Set it to default.
            set_config($setting, $defaultsettings[$setting],plugin::COMPONENT);
        }
    }

    /* Now we need to store the field mappings */
    $coursefields = array();
    $progamfields = array();
    foreach ($data as $fld => $val) {
        if (strpos($fld, 'course_') === 0) {
            $realfldname = str_replace('course_', '', $fld);
            if ($val !== 'course_none') {       // Not mapped does not count
                $coursefields[$realfldname] = $val;
            }
        } 

    }
    set_config('coursemappings', serialize($coursefields),plugin::COMPONENT);

    echo $OUTPUT->notification(get_string('savesuccess',plugin::COMPONENT), 'success');

    $returnurl = new moodle_url('/my/index.php');
    echo html_writer::tag('p', $OUTPUT->single_button($returnurl, get_string('continue')));

} else {

    $form->display();
}

echo $OUTPUT->footer();

