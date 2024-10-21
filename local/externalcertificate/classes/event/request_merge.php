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
 * @author revathi <revathi.m@eabyas.in>
 * @package BizLMS
 * @subpackage local_externalcertificate
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_externalcertificate\event;

defined('MOODLE_INTERNAL') || die();


class request_merge extends \core\event\base {
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
        $requestinfo=$DB->get_record_sql("SELECT lec.*,lecc.coursename as mastercoursename FROM {local_external_certificates} as lec JOIN {local_external_certificates_courses} as lecc ON lec.coursename = lecc.id WHERE lec.id={$this->objectid}");
        $endusername=$DB->get_record_sql("SELECT concat(firstname,' ',lastname) as fullname,id FROM {user} where id=".$requestinfo->userid);

        $firstname=$DB->get_field_sql("SELECT concat(firstname,' ',lastname) FROM {user} where id=".$this->other['requestuserid']);
       if($requestinfo->mastercoursename == $requestinfo->mastercourse){
            return " Request from '$firstname ({$this->other['requestuserid']})' for external certificate '$requestinfo->mastercourse ($requestinfo->id)' created new master data with ID '$requestinfo->mastercoursename ($requestinfo->coursename)' by user '$endusername->fullname($endusername->id)'.";
       }else if($requestinfo){     

            return " Request from '$firstname ({$this->other['requestuserid']})' for external certificate '$requestinfo->mastercourse ($requestinfo->id)' merged with master ID '$requestinfo->mastercoursename ($requestinfo->coursename)' by user '$endusername->fullname($endusername->id)'.";
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
        return get_string('eventrequestmerge', 'local_externalcertificate');
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
