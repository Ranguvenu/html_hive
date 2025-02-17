<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds new instance of enrol_auto to specified course
 * or edits current instance.
 *
 * @package     enrol_auto
 * @author      Eugene Venter <eugene@catalyst.net.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('edit_form.php');

$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/auto:config', $context);

$PAGE->set_url('/enrol/auto/edit.php', array('courseid' => $course->id, 'id' => $instanceid));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id' => $course->id));
if (!enrol_is_enabled('auto')) {
    redirect($return);
}

/** @var enrol_auto_plugin $plugin */
$plugin = enrol_get_plugin('auto');

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'auto', 'id' => $instanceid), '*', MUST_EXIST);

} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id' => $course->id)));

    $instance = (object)$plugin->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    $instance->status   = ENROL_INSTANCE_ENABLED; // Do not use default for automatically created instances here.
}

$mform = new enrol_auto_edit_form(null, array($instance, $plugin, $context));

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {

    if ($instance->id) {
        $reset = ($instance->status != $data->status);

        $instance->status         = $data->status;
        $instance->name           = $data->name;
        $instance->customint2     = $data->customint2;
        $instance->customint3     = $data->customint3;
        $instance->customtext1    = empty($data->customtext1) ? '' : $data->customtext1;
        $instance->customtext2    = empty($data->customtext2) ? '' : implode(',', array_keys($data->customtext2));
        $instance->roleid         = $data->roleid;
        $instance->timemodified   = time();
        
    // OL-1042 Add Target Audience to courses//
        $data=data_submitted();
        $instance->open_group =(!empty($data->open_group)) ? implode(',',array_filter($data->open_group)) :NULL;
        $instance->open_hrmsrole =(!empty($data->open_hrmsrole)) ? implode(',',array_filter($data->open_hrmsrole)) :NULL;
        $instance->open_designation =(!empty($data->open_designation)) ? implode(',',array_filter($data->open_designation)) :NULL;
        // $instance->open_location =(!empty($data->open_location)) ? implode(',',array_filter($data->open_location)) :NULL;
        $instance->open_country =(!empty($data->open_country)) ? implode(',',array_filter($data->open_country)) :NULL;
        $instance->open_ouname =(!empty($data->open_ouname)) ? implode(',',array_filter($data->open_ouname)) :NULL;
        
        if(is_array ($data->department)){
        
            $instance->department = !empty($data->department) ? implode(',', $data->department) : -1;
        }else{
            $instance->department = !empty($data->department) ? $data->department : -1;
        }
    // OL-1042 Add Target Audience to courses//
        $DB->update_record('enrol', $instance);

        if ($reset) {
            $context->mark_dirty();
        }

    } else {
        $fields = array(
            'status'          => $data->status,
            'name'            => $data->name,
            'customint2'      => $data->customint2,
            'customint3'      => $data->customint3,
            'customtext1'     => empty($data->customtext1) ? '' : $data->customtext1,
            'customtext2'     => empty($data->customtext2) ? '' : implode(',', array_keys($data->customtext2)),
            'roleid'          => $data->roleid
        );
    // OL-1042 Add Target Audience to courses//
    
        $fields['open_group'] =(!empty($data->open_group)) ? implode(',',array_filter($data->open_group)) :NULL;
        $fields['open_hrmsrole'] =(!empty($data->open_hrmsrole)) ? implode(',',array_filter($data->open_hrmsrole)) :NULL;
        $fields['open_designation'] =(!empty($data->open_designation)) ? implode(',',array_filter($data->open_designation)) :NULL;
        // $fields['open_location'] =(!empty($data->open_location)) ? implode(',',array_filter($data->open_location)):NULL;
        $fields['open_country'] =(!empty($data->open_country)) ? implode(',',array_filter($data->open_country)):NULL;
        $fields['open_ouname'] =(!empty($data->open_ouname)) ? implode(',',array_filter($data->open_ouname)):NULL;
        
        if(is_array ($data->department)){
        
            $fields['department'] = !empty($data->department) ? implode(',', $data->department) : -1;
        }else{
            $fields['department'] = !empty($data->department) ? $data->department : -1;
        }
    // OL-1042 Add Target Audience to courses//
        $plugin->add_instance($course, $fields);
    }
   $return = new moodle_url('/local/courses/courses.php');
    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_auto'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_auto'));
$mform->display();
echo $OUTPUT->footer();
