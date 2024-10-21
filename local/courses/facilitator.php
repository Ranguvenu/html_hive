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
 * @subpackage local_request
 */

use local_courses\form;
require_once(__DIR__ . '/../../config.php');
$courseid = required_param('courseid', PARAM_INT);

global $OUTPUT, $PAGE, $USER, $DB;
require_login();
$coursefullname = $DB->get_field('course','fullname',array('id' => $courseid));
$title = get_string('facilitatordetails' , 'local_courses').' - '.$coursefullname;

// Set up the page.
$url = new moodle_url("/local/courses/facilitator.php?courseid=".$courseid);
$sitecontext = context_system::instance();
$PAGE->set_context($sitecontext);

$PAGE->set_pagelayout('admin');
$PAGE->set_url($url);

$PAGE->set_title($title);
$PAGE->navbar->add(get_string("facilitator", 'local_courses'));
$PAGE->set_heading($title);
echo $OUTPUT->header();


if($courseid){
        $mform = new local_courses\form\course_facilitator_form($url, array('courseid' => $courseid), 'post', '', null, true, null);

      
 }
//$mform->set_data();
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/courses/courses.php');
} else if ($facilitator =  $mform->get_data()){
     if ($facilitator) {
                /*if ($facilitator->id > 0) {
                    $post->courseid       = $courseid;
                    $post->userid       = $facilitator->facilitatorname;
                    $post->credits      = $facilitator->credits;
                    $post->contenttype  = $facilitator->contenttype;
                    $post->classroomid  = $facilitator->facilitatorILTs;
                    $post->timemodified  = time();
                    $post->usermodified   = $USER->id; 
                    $DB->update_record('local_course_facilitators', $post);
                } else {*/
                    $post = new stdClass();
                    $post->courseid = $courseid;
                    $post->userid       = $facilitator->facilitatorname;
                    $post->credits      = $facilitator->credits;
                    $post->contenttype  = $facilitator->contenttype;
                    $post->classroomid  = $facilitator->facilitatorILTs;
                    $post->timecreated    = time();
                    $post->usercreated   = $USER->id;
                    $DB->insert_record('local_course_facilitators', $post);
               // }

        } 
} else{
    
     $mform->display();

}
        
    $facilitators = $DB->get_records_sql("SELECT lcf.*, u.id as userid, concat(u.firstname,' ',u.lastname)  as facilitatorname FROM {local_course_facilitators} as lcf
                                         JOIN {user} as u ON u.id = lcf.userid 
                                          WHERE courseid = {$courseid}");

    $table = new html_table();
    $table->id = 'facilitator_table';
    $table->align = ['left','center','center','center','center'];
    $table->head = array(get_string('facilitatorname', 'local_courses'), get_string('course', 'local_courses'), get_string('contenttype', 'local_courses'), get_string('classname', 'local_courses'), get_string('credits', 'local_courses'));
    $row = array();
    if(!empty($facilitators)) {
        foreach ($facilitators as  $facilitator) {
            if($facilitator->contenttype == 1){
                $contenttype = 'Project review and Viva';
            }else if($facilitator->contenttype == 2){
                $contenttype = 'Classroom Content Development';
            }else if($facilitator->contenttype == 3){
                $contenttype = 'eLearning Content Development';
            }else if($facilitator->contenttype == 4){
                $contenttype = 'Others';
            }else if($facilitator->contenttype == 5){
                $contenttype = 'Classroom Delivery';
            }

            if(!empty($facilitator->classroomid)) {
              $classname = $DB->get_field('local_classroom','name',array('id' => $facilitator->classroomid));
            }
            $table->data[] = new html_table_row(array($facilitator->facilitatorname,  $coursefullname, $contenttype, $classname, $facilitator->credits));
        }
    } else {
            $row[] = 'No Data Available';       
            $table->data[] = new html_table_row($row);
    }
echo html_writer::table($table);

echo $OUTPUT->footer();
