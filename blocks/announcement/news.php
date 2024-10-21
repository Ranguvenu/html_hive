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
 * @subpackage blocks_announcement
 */
global $DB,$CFG, $USER, $OUTPUT, $PAGE;
require_once(dirname(__FILE__) . '/../../config.php');
use \blocks_announcement\form\announcement_form as announcement_form;
require_once($CFG->dirroot . '/blocks/announcement/lib.php');
$delete = optional_param('delete', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$courseid = 1;
require_login();
//$coursecontext = context_course::instance($courseid);
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$pageurl = new moodle_url('/blocks/announcement/news.php',array('id'=>$id));
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('pluginname', 'block_announcement'));
$PAGE->navbar->add(get_string('pluginname', 'block_announcement'));
if(isguestuser($USER->id)){
   print_error('nopermission');
}
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('block_announcement');
    $announcements_sql = $DB->get_record_sql("SELECT id,courseid,usermodified,name,description FROM {block_announcement} WHERE id = $id");
                $data = '';
                $course = $DB->get_record('course', array('id' => $announcements_sql->courseid, 'visible' => 1));
                //if(!$course){
                //    continue;
                //}
                $user = $DB->get_record('user', array('id' => $announcements_sql->usermodified, 'confirmed' => 1, 'deleted' => 0, 'suspended' => 0));
                //if(!$user){
                //    continue;
                //}
                $data .= html_writer::tag('h3', $announcements_sql->name, array('class' => 'createnews'));
                $url = new moodle_url('/my/', array());
                $out = html_writer::link($url, '<< Back', array());
                $data .= html_writer::div($out, 'delnews pull-right text-right mt-10 mb-10  p-10 mr-20 clear');
                $data .= html_writer::div(($announcements_sql->description), 'addnews')."</br>";
                $return = '<input type="submit" id="submit_news"  value="Back" />';
                
            echo $data;
echo $OUTPUT->footer();

