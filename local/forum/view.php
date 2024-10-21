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
 * @subpackage local_forum
 */


    require_once('../../config.php');
    require_once('lib.php');
    require_once($CFG->libdir.'/completionlib.php');
    global $DB, $CFG, $USER;
    $id          = optional_param('id', 0, PARAM_INT);       // Course Module ID
    $f           = optional_param('f', 0, PARAM_INT);        // Forum ID
    $mode        = optional_param('mode', 0, PARAM_INT);     // Display mode (for single forum)
    $showall     = optional_param('showall', '', PARAM_INT); // show all discussions on one page
    $changegroup = optional_param('group', -1, PARAM_INT);   // choose the current group
    $page        = optional_param('page', 0, PARAM_INT);     // which page to show
    $search      = optional_param('search', '', PARAM_CLEAN);// search string

    $params = array();
    if ($id) {
        $params['id'] = $id;
    } else {
        $params['f'] = $f;
    }
    if ($page) {
        $params['page'] = $page;
    }
    if ($search) {
        $params['search'] = $search;
    }
    $PAGE->set_url('/local/forum/view.php', $params);

    if ($id) {
        if (! $forum = $DB->get_record("local_forum", array("id" => $id))) {
            print_error('invalidforumid', 'forum');
        }
        if ($forum->type == 'single') {
            $PAGE->set_pagetype('mod-forum-discuss');
        }
        // move require_course_login here to use forced language for course
        // fix for MDL-6926
        //require_course_login($course, true, $cm);
        require_login();
        $strforums = get_string("modulenameplural", "local_forum");
        $strforum = get_string("modulename", "local_forum");
    } else if ($f) {

        if (! $forum = $DB->get_record("local_forum", array("id" => $f))) {
            print_error('invalidforumid', 'forum');
        }
        require_login();
        $strforums = get_string("modulenameplural", "local_forum");
        $strforum = get_string("modulename", "local_forum");
    } else {
        print_error('missingparameter');
    }

    //if (!$PAGE->button) {
    //    $PAGE->set_button(local_forum_search_form($course, $search));
    //}

    $context = context_system::instance();
    $PAGE->set_context($context);
    if(!(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $context))){
        if($forum->costcenterid != $USER->open_costcenterid){
            redirect(new moodle_url('/local/forum/index.php'));
        }else if(!has_capability('local/costcenter:manage_ownorganization', $context)){
            if(!($forum->departmentid == '' || $forum->departmentid == 0 ) && $forum->departmentid != $USER->open_departmentid){
                redirect(new moodle_url('/local/forum/index.php'));
            }
        }
    }

    if (!empty($CFG->enablerssfeeds) && !empty($CFG->forum_enablerssfeeds) && $forum->rsstype && $forum->rssarticles) {
        require_once("$CFG->libdir/rsslib.php");

        $rsstitle = format_string($forum->name, true, array('context' => context_system::instance()));
        rss_add_http_header($context, 'local_forum', $forum, $rsstitle);
    }

/// Print header.

    $PAGE->set_title($forum->name);
    $PAGE->set_pagelayout('incourse');
    $PAGE->add_body_class('forumtype-'.$forum->type);
    $PAGE->navbar->add($strforums, new moodle_url('index.php'));
    $PAGE->navbar->add($forum->name);
    
    $PAGE->set_heading($forum->name);

    // Some capability checks.
    $courselink = new moodle_url('/local/forum/index.php');

    if (!has_capability('local/forum:viewdiscussion', $context)) {
        notice(get_string('noviewdiscussionspermission', 'forum'), $courselink);
    }

    // Mark viewed and trigger the course_module_viewed event.
    local_forum_view($forum, $context);

    echo $OUTPUT->header();

    //echo $OUTPUT->heading(format_string($forum->name), 2);
    if (!empty($forum->intro) && $forum->type != 'single' && $forum->type != 'teacher') {
        $options = new stdClass;
        $options->para    = false;
        $options->trusted = 1;
        $options->context = $context;
        $forum_intro = format_text($forum->intro, $forum->introformat, $options);
        echo $OUTPUT->box($forum_intro, 'generalbox', 'intro');
    }


    $SESSION->fromdiscussion = qualified_me();   // Return here if we post or set subscription etc


    /// Print settings and things across the top

    // If it's a simple single discussion forum, we need to print the display
    // mode control.
    if ($forum->type == 'single') {
        $discussion = NULL;
        $discussions = $DB->get_records('local_forum_discussions', array('forum'=>$forum->id), 'timemodified ASC');
        if (!empty($discussions)) {
            $discussion = array_pop($discussions);
        }
        if ($discussion) {
            if ($mode) {
                set_user_preference("forum_displaymode", $mode);
            }
            $displaymode = get_user_preferences("forum_displaymode", $CFG->forum_displaymode);
            local_forum_print_mode_form($forum->id, $displaymode, $forum->type);
        }
    }

    if (!empty($forum->blockafter) && !empty($forum->blockperiod)) {
        $a = new stdClass();
        $a->blockafter = $forum->blockafter;
        $a->blockperiod = get_string('secondstotime'.$forum->blockperiod);
        echo $OUTPUT->notification(get_string('thisforumisthrottled', 'forum', $a));
    }

    if ($forum->type == 'qanda' && !has_capability('moodle/course:manageactivities', $context)) {
        echo $OUTPUT->notification(get_string('qandanotify','forum'));
    }

    switch ($forum->type) {
        case 'single':
            if (!empty($discussions) && count($discussions) > 1) {
                echo $OUTPUT->notification(get_string('warnformorepost', 'forum'));
            }
            if (! $post = local_forum_get_post_full($discussion->firstpost)) {
                print_error('cannotfindfirstpost', 'forum');
            }
            if ($mode) {
                set_user_preference("forum_displaymode", $mode);
            }

            $canreply    = local_forum_user_can_post($forum, $discussion, $USER, $context);
            $canrate     = has_capability('local/forum:rate', $context);
            $displaymode = get_user_preferences("forum_displaymode", $CFG->forum_displaymode);

            echo '&nbsp;'; // this should fix the floating in FF
            local_forum_print_discussion($forum, $discussion, $post, $displaymode, $canreply, $canrate);
            break;

        case 'eachuser':
            echo '<p class="mdl-align">';
            if (local_forum_user_can_post_discussion($forum, null, -1)) {
                print_string("allowsdiscussions", "forum");
            } else {
                echo '&nbsp;';
            }
            echo '</p>';
            if (!empty($showall)) {
                local_forum_print_latest_discussions($forum, 0, 'header', '', -1, -1, -1, 0);
            } else {
                local_forum_print_latest_discussions($forum, -1, 'header', '', -1, -1, $page, $CFG->local_forum_manydiscussions);
            }
            break;

        case 'teacher':
            if (!empty($showall)) {
                local_forum_print_latest_discussions($forum, 0, 'header', '', -1, -1, -1, 0);
            } else {
                local_forum_print_latest_discussions($forum, -1, 'header', '', -1, -1, $page, $CFG->local_forum_manydiscussions);
            }
            break;

        case 'blog':
            echo '<br />';
            if (!empty($showall)) {
                local_forum_print_latest_discussions($forum, 0, 'plain', 'd.pinned DESC, p.created DESC', -1, -1, -1, 0);
            } else {
                local_forum_print_latest_discussions($forum, -1, 'plain', 'd.pinned DESC, p.created DESC', -1, -1, $page,
                    $CFG->local_forum_manydiscussions);
            }
            break;

        default:
            echo '<br />';
            if (!empty($showall)) {
                local_forum_print_latest_discussions($forum, 0, 'header', '', -1, -1, -1, 0, $cm);
            } else {
                local_forum_print_latest_discussions($forum, -1, 'header', '', -1, -1, $page, $CFG->local_forum_manydiscussions);
            }
            break;
    }

    // Add the subscription toggle JS.
    $PAGE->requires->yui_module('moodle-local_forum-subscriptiontoggle', 'Y.M.local_forum.subscriptiontoggle.init');

    echo $OUTPUT->footer();
