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
 *
 * @package    local_externalcertificate
 * @copyright  2023 Shamala Kandula <shamala.kandula@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_externalcertificate\event;

defined('MOODLE_INTERNAL') || die();


class approve_externalcertificate extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_external_certificates';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $DB;       
        $requestinfo=$DB->get_record_sql("SELECT lec.*,lecc.coursename as mastercoursename FROM {local_external_certificates} as lec JOIN {local_external_certificates_courses} as lecc ON lec.coursename = lecc.id WHERE lec.id={$this->objectid} AND lec.status = 1");
                
        if($requestinfo){
            $endusername=$DB->get_record_sql("SELECT concat(firstname,' ',lastname) as fullname,id FROM {user} where id=:id",['id' => $requestinfo->userid]);
            $firstname=$DB->get_field_sql("SELECT concat(firstname,' ',lastname) FROM {user} where id=:id",['id' => $requestinfo->usercreated] );
        
                return "$firstname ($requestinfo->usercreated) approved '$requestinfo->mastercourse ($requestinfo->id)'
                external certificate created by user '$endusername->fullname($endusername->id)'";
            }else{
                return 'went wrong';
            }        
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventapprovecertificate', 'local_externalcertificate');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        global $DB;      
        // $url = new \mooddle_url('/local/externalcertificate/index.php');
        // $url->set_anchor('p'.$this->objectid);
        // return $url;
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        // The legacy log table expects a relative path to /local/forum/.
        $logurl = substr($this->get_url()->out_as_local_url(), strlen('/local/externalcertificate/'));

        return array($this->objectid, 'request', 'add', $logurl, $this->component, $this->componentid);
    }   

    public static function get_objectid_mapping() {
        return array('db' => 'local_external_certificates', 'restore' => 'local_external_certificates');
    }


}
