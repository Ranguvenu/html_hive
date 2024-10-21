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


defined('MOODLE_INTERNAL') || die();
/**
 * Event observer for local_users. Dont let other user to view unauthorized users
 */
class local_users_observer extends \core\event\user_profile_viewed {
    /**
     * Triggered via user_profile_viewed event.
     *
     * @param \core\event\user_profile_viewed $event
     */
    public static function user_profile_viewed(\core\event\user_profile_viewed $event) {
        global $DB, $CFG, $USER, $COURSE;
        $related_userid  = $event->data['relateduserid'];
        $systemcontext = context_system::instance();
        if (($related_userid != $USER->id) AND (!(is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations',$systemcontext)))) {
            if((has_capability('local/users:create',$systemcontext))){
                echo $user_costcenter = $DB->get_field('user', 'open_costcenterid', array('id'=>$related_userid));
                echo $manager_costcenter = $DB->get_field('user', 'open_costcenterid', array('id'=>$USER->id));
                if ($user_costcenter != $manager_costcenter) {
                    redirect($CFG->wwwroot.'/local/users/index.php');
                die;
                }
            } else {
                redirect($CFG->wwwroot.'/local/users/index.php');
                die;
            }
        }
    }
}
