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
 * Version details.
 *
 * @package    local_video
 * @copyright  akshat.c@eabyas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$context = context_system::instance();

function local_video_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        // send_file_not_found();
        return false;
    }
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'video') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);

    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    $filedata = get_file_storage();
    $file = $filedata->get_file($context->id, 'local_video', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        // code...
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}


function img_path($itemid = 0) {
    global $DB;
    $context = context_system::instance();

    if ($itemid > 0) {
        // code...
        $sql = "SELECT * FROM {files} WHERE itemid = :video AND component = 'local_video' AND filearea = 'video' AND filename != '.' ORDER BY id DESC";
        $imgdata = $DB->get_record_sql($sql, array('video' => $itemid), 1);
    }

    if (!empty($imgdata)) {
        // code...
        $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);

        $imgurl = $imgurl->out();
    } else {
        return false;
    }

    return $imgurl;
}


function local_video_leftmenunode(){
    $systemcontext = context_system::instance();
    $videonode = '';
    if(has_capability('local/video:view',$systemcontext) || is_siteadmin()){
        $videonode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_video', 'class'=>'pull-left user_nav_div video'));
        $video_url = new moodle_url('/local/video/index.php');
        $video = html_writer::link($video_url, '<span class="demo_video_icon left_menu_icons"></span><span class="user_navigation_link_text">'.get_string('pluginname','local_video').'</span>',array('class'=>'user_navigation_link'));
        $videonode .= $video;
        $videonode .= html_writer::end_tag('li');
    }
    return array('20' => $videonode);
}
