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
 * @subpackage local_learningplan
 */

namespace local_learningplan\event;

defined('MOODLE_INTERNAL') || die();

class self_unenrolled extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_learningplan_user';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $dataobj = new \stdClass();
        
        $dataobj->relateduserid = $this->data['relateduserid'];
        $dataobj->lpathid = $this->data['objectid'];
        return get_string('self_unenrolled_log','local_learningplan',$dataobj);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('self_unenrolled', 'local_learningplan');
    }
}
