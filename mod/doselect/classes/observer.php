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
 * Event observers used in this plugin
 *
 * @package    mod_doselect
 * @copyright  Anilkumar Cheguri <anil@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
/**
 * Event observer for local_moduleevents.
 */
class mod_doselect_observer {
    /**
     * Triggered when 'course_module_completion_updated' event is triggered.
     *
     * @param  core\event\course_module_viewed $event
     */
    public static function insert_doselect_completion_mail(\core\event\course_module_completion_updated $event){
        global $DB, $CFG, $USER;
        if($event && !is_siteadmin($USER)){

            $sql = "SELECT cm.id, d.name
                    FROM {course_modules} cm 
                    JOIN {doselect} d ON d.id = cm.instance 
                    JOIN {modules} m ON m.id = cm.module 
                    WHERE cm.id = :cmid AND m.name = 'doselect' ";

            $doselect = $DB->get_record_sql($sql, array('cmid' =>$event->contextinstanceid));

            if($doselect){
                $fields = 'id,firstname,lastname,email';
                $fromuser = $DB->get_record('user',array('id'=>2), $fields);
                $touser = $DB->get_record('user',array('id'=>$event->relateduserid), $fields);

                $bodydata = new stdClass();

                $bodydata->employee = $touser->firstname.' '.$touser->lastname;
                $bodydata->activity = $doselect->name;

                $activityurl = new moodle_url('/mod/doselect/view.php',array('id'=>$doselect->id));
                $clickherelink = html_writer::link($activityurl, 'Click here', array('target'=>'_blank'));

                $bodydata->clickhere = $clickherelink;
                $bodydata->picture3 = $CFG->wwwroot.'/mod/facetoface/pix/fractal_logo.png';;

                $data = new stdClass();
                
                $data->from_emailid = $fromuser->email;
                $data->from_userid = $fromuser->id;
                $data->to_emailid = $touser->email;
                $data->to_userid = $touser->id;
                $data->subject = 'Successfully completed @ '.$doselect->name;
                $data->body_html = get_string('completionbody','doselect', $bodydata);
                $data->created_date = time();
                $data->sentby_id = $fromuser->id;
                $data->sentby_name = $fromuser->firstname.' '.$fromuser->lastname;
                $data->time_created = time();
                $data->courseid = $event->contextinstanceid;

                $sql = "SELECT id 
                        FROM {local_email_logs}
                        WHERE to_userid = :userid AND courseid = :cmid AND 
                        subject LIKE '%Successfully completed @%' AND status = 0 ";

                $exists = $DB->get_record_sql($sql, array('userid'=>$touser->id, 'cmid'=>$event->contextinstanceid));
                if($exists){
                    $data->id = $exists->id;
                    $DB->update_record('local_email_logs',$data);
                }else{
                    $DB->insert_record('local_email_logs',$data);
                }
                
            }
        }
    }
}