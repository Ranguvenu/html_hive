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
 * cron File for HRMS sync 
 *
 * @package    local
 * @subpackage  users
 * @copyright  2020 Anilkumar Cheguri
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use local_users\hrmssync;
require_once(dirname(__FILE__) . '/../../config.php');
set_time_limit(-1);
global $CFG;
// require_once($CFG->dirroot . '/local/users/lib.php');
// require_once($CFG->dirroot . '/local/users/parsecsv.lib.php');
$hrmssync = new local_users\hrmssync\userssync();
$hrmssync->users_sync();


