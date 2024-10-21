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
 * File containing the form definition to post in the forum.
 *
 * @package   local_forum
 * @copyright Sreenivas 2018
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');

class local_forum_post_form extends moodleform {

    /**
     * Returns the options array to use in filemanager for forum attachments
     *
     * @param stdClass $forum
     * @return array
     */
    public static function attachment_options($forum) {
        global $COURSE, $PAGE, $CFG;
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes, $forum->maxbytes);
        return array(
            'subdirs' => 0,
            'maxbytes' => $maxbytes,
            'maxfiles' => $forum->maxattachments,
            'accepted_types' => '*',
            'return_types' => FILE_INTERNAL | FILE_CONTROLLED_LINK
        );
    }

    /**
     * Returns the options array to use in forum text editor
     *
     * @param context_module $context
     * @param int $postid post id, use null when adding new post
     * @return array
     */
    public static function editor_options($context, $postid) {
        global $COURSE, $PAGE, $CFG;
        // TODO: add max files and max size support
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
        return array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $maxbytes,
            'trusttext'=> true,
            'return_types'=> FILE_INTERNAL | FILE_EXTERNAL,
            'subdirs' => file_area_contains_subdirs($context, 'local_forum', 'post', $postid)
        );
    }

    /**
     * Form definition
     *
     * @return void
     */
    function definition() {
        global $CFG, $OUTPUT;

        $mform =& $this->_form;
        $context = $this->_customdata['context'];
        $forum = $this->_customdata['forum'];
        $post = $this->_customdata['post'];
        $subscribe = $this->_customdata['subscribe'];
        $edit = $this->_customdata['edit'];
        $thresholdwarning = $this->_customdata['thresholdwarning'];

        $mform->addElement('header', 'general', '');//fill in the data depending on page params later using set_data

        // If there is a warning message and we are not editing a post we need to handle the warning.
        if (!empty($thresholdwarning) && !$edit) {
            // Here we want to display a warning if they can still post but have reached the warning threshold.
            if ($thresholdwarning->canpost) {
                $message = get_string($thresholdwarning->errorcode, $thresholdwarning->module, $thresholdwarning->additional);
                $mform->addElement('html', $OUTPUT->notification($message));
            }
        }

        $mform->addElement('text', 'subject', get_string('subject', 'local_forum'), 'size="48"');
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('editor', 'message', get_string('message', 'forum'), null, self::editor_options($context, (empty($post->id) ? null : $post->id)));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        $manageactivities = has_capability('moodle/course:manageactivities', $context);

        if (\local_forum\subscriptions::is_forcesubscribed($forum)) {
            $mform->addElement('checkbox', 'discussionsubscribe', get_string('discussionsubscription', 'local_forum'));
            $mform->freeze('discussionsubscribe');
            $mform->setDefaults('discussionsubscribe', 0);
            $mform->addHelpButton('discussionsubscribe', 'forcesubscribed', 'local_forum');

        } else if (\local_forum\subscriptions::subscription_disabled($forum) && !$manageactivities) {
            $mform->addElement('checkbox', 'discussionsubscribe', get_string('discussionsubscription', 'local_forum'));
            $mform->freeze('discussionsubscribe');
            $mform->setDefaults('discussionsubscribe', 0);
            $mform->addHelpButton('discussionsubscribe', 'disallowsubscription', 'local_forum');

        } else {
            $mform->addElement('checkbox', 'discussionsubscribe', get_string('discussionsubscription', 'local_forum'));
            $mform->addHelpButton('discussionsubscribe', 'discussionsubscription', 'local_forum');
        }

        if (local_forum_can_create_attachment($forum, $context)) {
            $mform->addElement('filemanager', 'attachments', get_string('attachment', 'local_forum'), null, self::attachment_options($forum));
            $mform->addHelpButton('attachments', 'attachment', 'local_forum');
        }

        if (!$post->parent && has_capability('local/forum:pindiscussions', $context)) {
            $mform->addElement('checkbox', 'pinned', get_string('discussionpinned', 'local_forum'));
            $mform->addHelpButton('pinned', 'discussionpinned', 'local_forum');
        }

        if (empty($post->id) && $manageactivities) {
            $mform->addElement('checkbox', 'mailnow', get_string('mailnow', 'local_forum'));
        }

        if (!empty($CFG->local_forum_enabletimedposts) && !$post->parent && has_capability('local/forum:viewhiddentimedposts', $context)) {
            $mform->addElement('header', 'displayperiod', get_string('displayperiod', 'local_forum'));

            $mform->addElement('date_time_selector', 'timestart', get_string('displaystart', 'local_forum'), array('optional' => true));
            $mform->addHelpButton('timestart', 'displaystart', 'local_forum');

            $mform->addElement('date_time_selector', 'timeend', get_string('displayend', 'local_forum'), array('optional' => true));
            $mform->addHelpButton('timeend', 'displayend', 'local_forum');

        } else {
            $mform->addElement('hidden', 'timestart');
            $mform->setType('timestart', PARAM_INT);
            $mform->addElement('hidden', 'timeend');
            $mform->setType('timeend', PARAM_INT);
            $mform->setConstants(array('timestart' => 0, 'timeend' => 0));
        }

     /*    if (core_tag_tag::is_enabled('local_forum', 'forum_posts')) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));

            $mform->addElement('tags', 'tags', get_string('tags'),
                array('itemtype' => 'forum_posts', 'component' => 'local_forum'));
        } */

        //-------------------------------------------------------------------------------
        // buttons
        if (isset($post->edit)) { // hack alert
            $submit_string = get_string('savechanges');
        } else {
            $submit_string = get_string('posttoforum', 'local_forum');
        }

        $this->add_action_buttons(true, $submit_string);

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'forum');
        $mform->setType('forum', PARAM_INT);

        $mform->addElement('hidden', 'discussion');
        $mform->setType('discussion', PARAM_INT);

        $mform->addElement('hidden', 'parent');
        $mform->setType('parent', PARAM_INT);

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('hidden', 'reply');
        $mform->setType('reply', PARAM_INT);
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    function validation($data, $files) {
        //print_object($data);
        $errors = parent::validation($data, $files);
        if (($data['timeend']!=0) && ($data['timestart']!=0) && $data['timeend'] <= $data['timestart']) {
            $errors['timeend'] = get_string('timestartenderror', 'local_forum');
        }
        if (empty($data['message']['text'])) {
            $errors['message'] = get_string('erroremptymessage', 'local_forum');
        }
        if (empty($data['subject'])) {
            $errors['subject'] = get_string('erroremptysubject', 'local_forum');
        }
        return $errors;
    }
}
