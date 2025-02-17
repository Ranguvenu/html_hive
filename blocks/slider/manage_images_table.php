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
 * If You like my plugin please send a small donation https://paypal.me/limsko Thanks!
 *
 * @package   block_slider
 * @copyright 2015-2020 Kamil Łuczak    www.limsko.pl     kamil@limsko.pl
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Class manage_images
 */
class manage_images extends table_sql {

    /**
     * manage_images constructor.
     * @param $uniqueid
     * @throws coding_exception
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Define the list of columns to show.
        $columns = array('slide_order', 'slide_link', 'slide_title', 'slide_desc', 'slide_image', 'slide_video', 'manage');
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = array(
                get_string('slide_order', 'block_slider'),
                get_string('slide_url', 'block_slider'),
                get_string('slide_title', 'block_slider'),
                get_string('slide_desc', 'block_slider'),
                get_string('slide_image', 'block_slider'),
                get_string('slide_video', 'block_slider'),
                get_string('manage_slides', 'block_slider'),
        );
        $this->define_headers($headers);
    }

    /**
     * Column with slide image.
     *
     * @param $values
     * @return string
     */
    public function col_slide_image($values) {
        global $CFG, $context;
        // If the data is being downloaded than we don't want to show HTML.
        // $url = $CFG->wwwroot . '/pluginfile.php/' . $context->id . '/block_slider/slider_slides/' . $values->id . '/' .$values->slide_image;
        $url = slider_img_path($values->slide_image);
        // print_r($url); die;

        if ($values->status == 0) {
            return html_writer::empty_tag('img', array('src' => $url, 'class' => 'img-thumbnail', 'style' => 'width:50%'));
        } else {
            return html_writer::empty_tag('img', array('src' => $url, 'class' => 'img-thumbnail', 'style' => 'width:50%; opacity: 0.2;'));
        }
    }

    public function col_slide_video($values) {
        global $CFG, $context;
        // If the data is being downloaded than we don't want to show HTML.
        // $url = $CFG->wwwroot . '/pluginfile.php/' . $context->id . '/block_slider/slider_slides/' . $values->id . '/' .$values->slide_video;
        $url = slider_video_path($values->slide_video);
        // print_r($url); die;
        // return html_writer::empty_tag('video', array('src' => $url, 'class' => 'img-thumbnail'));

        if ($values->slide_video && $url) {
            if ($values->status == 0) {
                return html_writer::start_tag('video', [
                    'controls'  => "1",
                    'class'     => "img-thumbnail",
                    'style'     => "width: 50%",
                    'muted'     => 'muted',
                    'autoplay'  => 'autoplay',
                    'loop'      => 'loop',
                ])
                .html_writer::tag('source', null, ['src' => $url])
                .html_writer::end_tag('video');
            } else {
                return html_writer::start_tag('video', [
                    'controls'  => "1",
                    'class'     => "img-thumbnail",
                    'style'     => "width: 50%; opacity: 0.2",
                    'muted'     => 'muted',
                    // 'autoplay'  => 'autoplay',
                    'loop'      => 'loop',
                ])
                .html_writer::tag('source', null, ['src' => $url])
                .html_writer::end_tag('video');
            }
        } else {
            return null;
            // return 'video not uploaded.';
        }
    }

    /**
     * Column with manage buttons.
     *
     * @param $values
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function col_manage($values) {
        $editurl = new moodle_url('/blocks/slider/manage_images.php', array('id' => $values->id, 'sliderid' => $values->sliderid));
        $editbtn = html_writer::tag('a', '<i class="fa fa-pencil-square-o" aria-hidden="true"></i>', array('href' => $editurl, 'class' => 'btn btn-primary', 'title' => get_string('edit', 'block_slider')));

        $deleteurl = new moodle_url('/blocks/slider/delete_image.php', array('id' => $values->id, 'sliderid' => $values->sliderid));
        $deletebtn = html_writer::tag('a', '<i class="fa fa-trash" aria-hidden="true"></i>', array('href' => $deleteurl, 'class' => 'btn btn-primary', 'title' => get_string('delete', 'block_slider')));

        $actionurl = new moodle_url('/blocks/slider/change_status.php', array('id' => $values->id, 'sliderid' => $values->sliderid));
        // $actionbtn = html_writer::tag('a', get_string('show_hide', 'block_slider'), array('href' => $actionurl, 'class' => 'btn btn-primary'));


        if ($values->status == 1) {
            $actionbtn = html_writer::tag('a', '<i class="fa fa-eye-slash" aria-hidden="true"></i>', array('href' => $actionurl, 'class' => 'btn btn-primary', 'title' => get_string('show', 'block_slider')));
        }
        if ($values->status == 0) {
            $actionbtn = html_writer::tag('a', '<i class="fa fa-eye" aria-hidden="true"></i>', array('href' => $actionurl, 'class' => 'btn btn-primary', 'title' => get_string('hide', 'block_slider')));
        }

        return "<p>$editbtn</p><p>$deletebtn</p><p>$actionbtn</p>";
    }
}
