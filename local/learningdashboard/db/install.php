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
 * Install script for Learning Dashboard
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    local_learningdashboard
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Executed on installation of Learning Dashboard
 *
 * @return bool
 */
function xmldb_local_learningdashboard_install() {
    global $CFG,$DB,$USER;
	$dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
    $time = time();
    $creditsmaster_data = array(
        array('creditstype' => 'Technical','startmonth' => '4','endmonth' => '6','credits' => 2,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('creditstype' => 'Technical','startmonth' => '7','endmonth' => '9','credits' => 4,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('creditstype' => 'Technical','startmonth' => '10','endmonth' => '0','credits' => 6,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('creditstype' => 'Leadership','startmonth' => '4','endmonth' => '6','credits' => 2,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('creditstype' => 'Leadership','startmonth' => '7','endmonth' => '9','credits' => 2,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        array('creditstype' => 'Leadership','startmonth' => '10','endmonth' => '0','credits' => 2,'usercreated' => '2','timecreated' => $time,'usermodified' => 2,'timemodified' => NULL),
        );
    foreach($creditsmaster_data as $data){
        unset($data['timecreated']);
        if(!$DB->record_exists('local_learningdashboard_master',  $data)){
            $data['timecreated'] = $time;
            $DB->insert_record('local_learningdashboard_master', $data);
        }
    }
    return true;
}
