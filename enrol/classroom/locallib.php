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
 * Strings for component 'enrol_self', language 'en'.
 *
 * @package    enrol_ Classroom
 * @copyright  2016 Niranjan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Check if the given password match a group enrolment key in the specified course.
 *
 * @param  int $courseid            course id
 * @param  string $enrolpassword    enrolment password
 * @return bool                     True if match
 * @since  Moodle 3.0
 */
function enrol_classroom_check_group_enrolment_key($courseid, $enrolpassword) {
    global $DB;

    $found = false;
    $groups = $DB->get_records('groups', array('courseid' => $courseid), 'id ASC', 'id, enrolmentkey');

    foreach ($groups as $group) {
        if (empty($group->enrolmentkey)) {
            continue;
        }
        if ($group->enrolmentkey === $enrolpassword) {
            $found = true;
            break;
        }
    }
    return $found;
}

class enrol_classroom_enrol_form extends moodleform {
    protected $instance;
    protected $toomany = false;

    /**
     * Overriding this function to get unique form id for multiple self enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    public function definition() {
        global $USER, $OUTPUT,$DB,$CFG;
        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('classroom');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'classroomheader', $heading);

        if ($instance->password) {
            // Change the id of self enrolment key input as there can be multiple self enrolment methods.
            $mform->addElement('passwordunmask', 'enrolpassword', get_string('password', 'enrol_classroom'),
                    array('id' => 'enrolpassword_'.$instance->id));
            $context = context_course::instance($this->instance->courseid);
            $keyholders = get_users_by_capability($context, 'enrol/classroom:holdkey', user_picture::fields('u'));
            $keyholdercount = 0;
            foreach ($keyholders as $keyholder) {
                $keyholdercount++;
                if ($keyholdercount === 1) {
                    $mform->addElement('static', 'keyholder', '', get_string('keyholder', 'enrol_classroom'));
                }
                $keyholdercontext = context_user::instance($keyholder->id);
                if ($USER->id == $keyholder->id || has_capability('moodle/user:viewdetails', context_system::instance()) ||
                        has_coursecontact_role($keyholder->id)) {
                    $profilelink = '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $keyholder->id . '&amp;course=' .
                    $this->instance->courseid . '">' . fullname($keyholder) . '</a>';
                } else {
                    $profilelink = fullname($keyholder);
                }
                $profilepic = $OUTPUT->user_picture($keyholder, array('size' => 35, 'courseid' => $this->instance->courseid));
                $mform->addElement('static', 'keyholder'.$keyholdercount, '', $profilepic . $profilelink);
            }

        } else {
            $mform->addElement('static', 'nokey', '', get_string('nopassword', 'enrol_classroom'));
        }
        $coursecostcenterid=$DB->get_field('local_coursedetails','costcenterid',array('courseid'=>$instance->id));
        $usercostcenter=$DB->get_field('local_userdata','costcenterid',array('userid'=>$USER->id));
        
        if($coursecostcenterid ==$usercostcenter)
           $this->add_action_buttons(false, get_string('enrolme', 'enrol_classroom'));
        else 
            $mform->addElement('static', 'notforyour','',get_string('notforyour', 'enrol_classroom'));
            

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    public function validation($data, $files) {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        if ($this->toomany) {
            $errors['notice'] = get_string('error');
            return $errors;
        }

        if ($instance->password) {
            if ($data['enrolpassword'] !== $instance->password) {
                if ($instance->customint1) {
                    // Check group enrolment key.
                    if (!enrol_classroom_check_group_enrolment_key($instance->courseid, $data['enrolpassword'])) {
                        // We can not hint because there are probably multiple passwords.
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_classroom');
                    }

                } else {
                    $plugin = enrol_get_plugin('classroom');
                    if ($plugin->get_config('showhint')) {
                        $hint = core_text::substr($instance->password, 0, 1);
                        $errors['enrolpassword'] = get_string('passwordinvalidhint', 'enrol_classroom', $hint);
                    } else {
                        $errors['enrolpassword'] = get_string('passwordinvalid', 'enrol_classroom');
                    }
                }
            }
        }

        return $errors;
    }
}
