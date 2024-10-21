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
 * The local_forum discussion_subscription deleted event.
 *
 * @package    local_forum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_forum\event;
use stdclass;
defined('MOODLE_INTERNAL') || die();

/**
 * The local_forum discussion_subscription deleted event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int forumid: The id of the forum which the discussion is in.
 *      - int discussion: The id of the discussion which has been unsubscribed from.
 * }
 */
class discussion_subscription_deleted extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_forum_discussion_subs';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $stringHelper=new stdClass();
        $stringHelper->userid=$this->userid;
        $stringHelper->relateduserid=$this->relateduserid;
        $stringHelper->discussion=$this->other['discussion'];
        $stringHelper->contextinstanceid=$this->contextinstanceid;
        return get_string("strdiscussionsubscriptiondeleted",'local_forum',$stringHelper);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdiscussionsubscriptiondeleted', 'local_forum');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/forum/subscribe.php', array(
            'id' => $this->other['forumid'],
            'd' => $this->other['discussion'],
        ));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['forumid'])) {
            throw new \coding_exception('The \'forumid\' value must be set in other.');
        }

        if (!isset($this->other['discussion'])) {
            throw new \coding_exception('The \'discussion\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return array('db' => 'local_forumforum_discussion_subs', 'restore' => 'local_forumforum_discussion_sub');
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['forumid'] = array('db' => 'local_forumforum', 'restore' => 'local_forumforum');
        $othermapped['discussion'] = array('db' => 'local_forumforum_discussions', 'restore' => 'local_forumforum_discussion');

        return $othermapped;
    }
}
