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
 * Simple slider block for Moodle
 *
 * @package   block_slider
 * @copyright 2015-2020 Kamil Łuczak    www.limsko.pl     kamil@limsko.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}


require_once(__DIR__ . '/../../config.php');
$context = context_system::instance();


/**
 * Block slider file function.
 *
 * @param $course
 * @param $birecordorcm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 * @throws required_capability_exception
 */

/**
 * Function for deleting slide with their images.
 *
 * @param $slide object
 * @return bool
 * @throws dml_exception
 */
function block_slider_delete_slide($slide) {
    global $DB;
    block_slider_delete_image($slide->sliderid, $slide->id, $slide->slide_image);
    $DB->delete_records('slider_slides', array('id' => $slide->id));
    return true;
}

/**
 * Deletes images in selected slider.
 *
 * @param $sliderid int Slider ID number
 * @param $slideid int Slide ID
 * @param $slideimage string Slide image name
 * @throws dml_exception
 */
function block_slider_delete_image($sliderid, $slideid, $slideimage = null) {
    global $DB;
    $fs = get_file_storage();
    $context = context_block::instance($sliderid);
    if (!$slideimage) {
        $slideimage = $DB->get_field('slider_slides', 'slide_image', array('sliderid' => $sliderid, 'id' => $slideid));
    }
    if ($file = $fs->get_file($context->id, 'block_slider', 'slider_slides', $slideid, '/', $slideimage)) {
        $file->delete();
    }
}

function block_slider_delete_video($sliderid, $slideid, $slidevideo = null) {
    global $DB;
    $fs = get_file_storage();
    $context = context_block::instance($sliderid);
    if (!$slidevideo) {
        $slidevideo = $DB->get_field('slider_slides', 'slide_video', array('sliderid' => $sliderid, 'id' => $slideid));
    }
    if ($file = $fs->get_file($context->id, 'block_slider', 'slider_slides', $slideid, '/', $slidevideo)) {
        $file->delete();
        $slidevideo->delete();
    } else {
        print_r('Not deleted.');
    }
}

/**
 * Get settings for BXSlider JS.
 *
 * @param $config
 * @param $sliderid
 * @return array
 */
function bxslider_get_settings($config, $sliderid) {
    $bxpause = isset($config->interval) ? $config->interval : 5000;
    $bxeffect = isset($config->bx_effect) ? $config->bx_effect : 'fade';
    $bxspeed = isset($config->bx_speed) ? $config->bx_speed : 500;
    $bxcaptions = isset($config->bx_captions) ? $config->bx_captions : 0;
    $bxresponsive = isset($config->bx_responsive) ? $config->bx_responsive : 1;
    $bxpager = isset($config->bx_pager) ? $config->bx_pager : 1;
    $bxcontrols = isset($config->bx_controls) ? $config->bx_controls : 1;
    $bxauto = isset($config->bx_auto) ? $config->bx_auto : 1;
    $bxstopautoonclick = isset($config->bx_stopAutoOnClick) ? $config->bx_stopAutoOnClick : 0;
    $bxusecss = isset($config->bx_useCSS) ? $config->bx_useCSS : 0;
    return array($sliderid, $bxpause, $bxeffect, $bxspeed, boolval($bxcaptions), boolval($bxresponsive), boolval($bxpager),
            boolval($bxcontrols), boolval($bxauto), boolval($bxstopautoonclick), boolval($bxusecss));
}

function block_slider_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
   
    if (($filearea !== 'slide_image') && ($filearea !== 'slide_video')) {
        return false;
    }

    $itemid = array_shift($args);

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();

    $file = $fs->get_file($context->id, 'block_slider', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }
    send_file($file, $filename, 0, $forcedownload, $options);
}

// for slider image url
function slider_img_path($itemid) {
        global $DB;
        $imgurl = false;
        $sql = "SELECT * FROM {files} WHERE itemid = :slide_image AND component = 'block_slider' AND filearea = 'slide_image' AND filename != '.' ORDER BY id DESC";
        $imgdata = $DB->get_record_sql($sql, array('slide_image' => $itemid), 1);
      
    if (!empty($imgdata)) {
        // code...
        $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);

        $imgurl = $imgurl->out();
    }
    return $imgurl;
}

// for slider video url
function slider_video_path($itemid) {
        global $DB;
        $imgurl = false;
        $sql = "SELECT * FROM {files} WHERE itemid = :slide_video AND component = 'block_slider' AND filearea = 'slide_video' AND filename != '.' ORDER BY id DESC";
        $imgdata = $DB->get_record_sql($sql, array('slide_video' => $itemid), 1);
      
    if (!empty($imgdata)) {
        // code...
        $imgurl = moodle_url::make_pluginfile_url($imgdata->contextid, $imgdata->component, $imgdata->filearea, $imgdata->itemid, $imgdata->filepath, $imgdata->filename);

        $imgurl = $imgurl->out();
    }
    return $imgurl;
}
