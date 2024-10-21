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
 * The survey block
 *
 * @package    block
 * @subpackage  survey
 * @copyright 2017 Shivani M <shivani@eabyas.in>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey{
    function get_enrolled_surveys(){
        global $DB, $USER;
        $evaluation = $DB->get_records_sql("SELECT * FROM {local_evaluations} e
                                           JOIN {local_evaluation_users} eu ON e.id = eu.evaluationid
                                           WHERE eu.userid = $USER->id");
        return $evaluation;
        
    }
}