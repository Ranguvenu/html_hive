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
 * @package   local
 * @subpackage  fmsapi
 * @author eabyas  <info@eabyas.in>
 **/


// List of observers.
defined('MOODLE_INTERNAL') || die();

$observers = [
    // [
    //     'eventname' => '\core\event\course_created', 
    //     'callback' => 'local_prisemforce_observer::coursecreated',
    // ],
    [
        'eventname' => '\core\event\course_updated', 
        'callback' => 'local_prisemforce_observer::courseupdated',
    ],
    [
        'eventname' => '\core\event\course_deleted', 
        'callback' => 'local_prisemforce_observer::coursedeleted',
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback' => 'local_prisemforce_observer::coursecompleted',
    ],
    [
        'eventname' => '\core\event\user_enrolment_created', 
        'callback' => 'local_prisemforce_observer::enrolledcreated',
    ],
    [
        'eventname' => '\local_externalcertificate\event\approve_externalcertificate',
        'callback' => 'local_prisemforce_observer::externalcertificate_approved',
    ], 
];
