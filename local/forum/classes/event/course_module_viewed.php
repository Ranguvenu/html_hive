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

namespace local_forum\event;
use stdclass;
defined('MOODLE_INTERNAL') || die();
class course_module_viewed extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_forum';
    }
    
    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $stringHelpers = new stdClass();
        $stringHelpers->userid = $this->userid;
        $stringHelpers->objectid = $this->objectid;
        // return "The user {$this->userid} has viewed the forum {$this->objectid}.";
        return get_string('user_viewed_forum', 'local_forum', $stringHelpers);
    }
    
    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('forumviewed', 'local_forum');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/forum/view.php', array('f' => $this->objectid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->objectid, 'local_forum', 'view forum', 'view.php?f=' . $this->objectid,
            $this->objectid, $this->contextinstanceid);
    }

    public static function get_objectid_mapping() {
        return array('db' => 'local_forum', 'restore' => 'local_forum');
    }
}

