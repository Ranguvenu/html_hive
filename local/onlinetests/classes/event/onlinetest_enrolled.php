<?php
/**
 * This file is part of eAbyas
 *
 * Copyright eAbyas Info Solutons Pvt Ltd, India
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author eabyas  <info@eabyas.in>
 * @package BizLMS
 * @subpackage local_onlinetest
 */
namespace local_onlinetests\event;

defined('MOODLE_INTERNAL') || die();


class onlinetest_enrolled extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_onlinetests';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' enrolled the user with id '$this->relateduserid' to the online exam of id '$this->objectid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventonlinetestenrolled', 'local_onlinetests');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        $url = new \moodle_url('/local/onlinetests/index.php');
        $url->set_anchor('p'.$this->objectid);
        return $url;
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        // The legacy log table expects a relative path to /local/onlinetests/.
        $logurl = substr($this->get_url()->out_as_local_url(), strlen('/local/onlinetests/'));

        return array($this->objectid, 'onlinetests', 'add post', $logurl, $this->objectid, $this->contextinstanceid);
    }

    

    public static function get_objectid_mapping() {
        return array('db' => 'local_onlinetests', 'restore' => 'local_onlinetests');
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['onlinetestid'] = array('db' => 'local_onlinetests', 'restore' => 'local_onlinetests');

        return $othermapped;
    }
}
