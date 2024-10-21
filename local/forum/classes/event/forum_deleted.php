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
 * The local_forum post created event.
 *
 * @package    local_forum
 * @copyright  2018 sreenivasula@eabyas.in
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_forum\event;
use stdclass;
defined('MOODLE_INTERNAL') || die();

class forum_deleted extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_forum';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $stringHelper=new stdClass();
        $stringHelper->userid=$this->userid;
        $stringHelper->objectid=$this->objectid;
        return get_string("strforumdeleted",'local_forum',$stringHelper);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventforumdeleted', 'local_forum');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        $url = new \moodle_url('/local/forum/view.php', array('f' => $this->objectid));
        $url->set_anchor('p'.$this->objectid);
        return $url;
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        // The legacy log table expects a relative path to /local/forum/.
        $logurl = substr($this->get_url()->out_as_local_url(), strlen('/local/forum/'));

        return array($this->objectid, 'forum', 'add post', $logurl, $this->objectid, $this->contextinstanceid);
    }

    

    public static function get_objectid_mapping() {
        return array('db' => 'local_forum', 'restore' => 'local_forum');
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['forumid'] = array('db' => 'local_forum', 'restore' => 'local_forum');

        return $othermapped;
    }
}
