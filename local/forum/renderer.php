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

class local_forum_renderer extends plugin_renderer_base {

    /**
     * Returns the navigation to the previous and next discussion.
     *
     * @param mixed $prev Previous discussion record, or false.
     * @param mixed $next Next discussion record, or false.
     * @return string The output.
     */
    public function neighbouring_discussion_navigation($prev, $next) {
        $html = '';
        if ($prev || $next) {
            $html .= html_writer::start_tag('div', array('class' => 'discussion-nav clearfix'));
            $html .= html_writer::start_tag('ul');
            if ($prev) {
                $url = new moodle_url('/local/forum/discuss.php', array('d' => $prev->id));
                $html .= html_writer::start_tag('li', array('class' => 'prev-discussion'));
                $html .= html_writer::link($url, format_string($prev->name),
                    array('aria-label' => get_string('prevdiscussiona', 'local_forum', format_string($prev->name))));
                $html .= html_writer::end_tag('li');
            }
            if ($next) {
                $url = new moodle_url('/local/forum/discuss.php', array('d' => $next->id));
                $html .= html_writer::start_tag('li', array('class' => 'next-discussion'));
                $html .= html_writer::link($url, format_string($next->name),
                    array('aria-label' => get_string('nextdiscussiona', 'local_forum', format_string($next->name))));
                $html .= html_writer::end_tag('li');
            }
            $html .= html_writer::end_tag('ul');
            $html .= html_writer::end_tag('div');
        }
        return $html;
    }

    /**
     * This method is used to generate HTML for a subscriber selection form that
     * uses two user_selector controls
     *
     * @param user_selector_base $existinguc
     * @param user_selector_base $potentialuc
     * @return string
     */
    public function subscriber_selection_form(user_selector_base $existinguc, user_selector_base $potentialuc) {
        $output = '';
        $formattributes = array();
        $formattributes['id'] = 'subscriberform';
        $formattributes['action'] = '';
        $formattributes['method'] = 'post';
        $output .= html_writer::start_tag('form', $formattributes);
        $output .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));

        $existingcell = new html_table_cell();
        $existingcell->text = $existinguc->display(true);
        $existingcell->attributes['class'] = 'existing';
        $actioncell = new html_table_cell();
        $actioncell->text  = html_writer::start_tag('div', array());
        $actioncell->text .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'subscribe', 'value'=>$this->page->theme->larrow.' '.get_string('add'), 'class'=>'actionbutton'));
        $actioncell->text .= html_writer::empty_tag('br', array());
        $actioncell->text .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'unsubscribe', 'value'=>$this->page->theme->rarrow.' '.get_string('remove'), 'class'=>'actionbutton'));
        $actioncell->text .= html_writer::end_tag('div', array());
        $actioncell->attributes['class'] = 'actions';
        $potentialcell = new html_table_cell();
        $potentialcell->text = $potentialuc->display(true);
        $potentialcell->attributes['class'] = 'potential';

        $table = new html_table();
        $table->attributes['class'] = 'subscribertable boxaligncenter';
        $table->data = array(new html_table_row(array($existingcell, $actioncell, $potentialcell)));
        $output .= html_writer::table($table);

        $output .= html_writer::end_tag('form');
        return $output;
    }

    /**
     * This function generates HTML to display a subscriber overview, primarily used on
     * the subscribers page if editing was turned off
     *
     * @param array $users
     * @param object $forum
     * @return string
     */
    public function subscriber_overview($users, $forum ) {
        $output = '';
        if (!$users || !is_array($users) || count($users)===0) {
            $output .= $this->output->heading(get_string("nosubscribers", "forum"));
        } else {
            $strparams = new stdclass();
            $strparams->name = format_string($forum->name);
            $strparams->count = count($users);
            $output .= $this->output->heading(get_string("subscriberstowithcount", "forum", $strparams));
            $table = new html_table();
            $table->cellpadding = 5;
            $table->cellspacing = 5;
            $table->tablealign = 'center';
            $table->data = array();
            foreach ($users as $user) {
                $info = array($this->output->user_picture($user, array('courseid'=>1)), fullname($user));
                $table->data[] = $info;
            }
            $output .= html_writer::table($table);
        }
        return $output;
    }

    /**
     * This is used to display a control containing all of the subscribed users so that
     * it can be searched
     *
     * @param user_selector_base $existingusers
     * @return string
     */
    public function subscribed_users(user_selector_base $existingusers) {
        $output  = $this->output->box_start('subscriberdiv boxaligncenter');
        $output .= html_writer::tag('p', get_string('forcesubscribed', 'forum'));
        $output .= $existingusers->display(true);
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Generate the HTML for an icon to be displayed beside the subject of a timed discussion.
     *
     * @param object $discussion
     * @param bool $visiblenow Indicicates that the discussion is currently
     * visible to all users.
     * @return string
     */
    public function timed_discussion_tooltip($discussion, $visiblenow) {
        $dates = array();
        if ($discussion->timestart) {
            $dates[] = get_string('displaystart', 'local_forum').': '.userdate($discussion->timestart);
        }
        if ($discussion->timeend) {
            $dates[] = get_string('displayend', 'local_forum').': '.userdate($discussion->timeend);
        }

        $str = $visiblenow ? 'timedvisible' : 'timedhidden';
        $dates[] = get_string($str, 'local_forum');

        $tooltip = implode("\n", $dates);
        $calender_data = $this->pix_icon('i/calendar', $tooltip, 'moodle', array('class' => 'smallicon timedpost'));
        
        return $calender_data;
    }

    /**
     * Display a forum post in the relevant context.
     *
     * @param \local_forum\output\forum_post $post The post to display.
     * @return string
     */
    public function render_forum_post_email(\local_forum\output\forum_post_email $post) {
        $data = $post->export_for_template($this, $this->target === RENDERER_TARGET_TEXTEMAIL);
        return $this->render_from_template('local_forum/' . $this->forum_post_template(), $data);
    }

    /**
     * The template name for this renderer.
     *
     * @return string
     */
    public function forum_post_template() {
        return 'forum_post';
    }

    /**
     * Create the inplace_editable used to select forum digest options.
     *
     * @param   stdClass    $forum  The forum to create the editable for.
     * @param   int         $value  The current value for this user
     * @return  inplace_editable
     */
    public function render_digest_options($forum, $value) {
        $options = local_forum_get_user_digest_options();
        $editable = new \core\output\inplace_editable(
            'local_forum',
            'digestoptions',
            $forum->id,
            true,
            $options[$value],
            $value
        );

        $editable->set_type_select($options);

        return $editable;
    }

    /**
     * Render quick search form.
     *
     * @param \local_forum\output\quick_search_form $form The renderable.
     * @return string
     */
    public function render_quick_search_form(\local_forum\output\quick_search_form $form) {
        return $this->render_from_template('local_forum/quick_search_form', $form->export_for_template($this));
    }

    /**
     * Render big search form.
     *
     * @param \local_forum\output\big_search_form $form The renderable.
     * @return string
     */
    public function render_big_search_form(\local_forum\output\big_search_form $form) {
        return $this->render_from_template('local_forum/big_search_form', $form->export_for_template($this));
    }

    /**
     * This method is used to render forum list
     * @return table structure
     */
    public function get_forums_list(){
        global $CFG,$OUTPUT, $USER, $DB;
        require_once($CFG->dirroot . '/local/forum/lib.php');
        $id = optional_param('id', 0, PARAM_INT);             // forum id
        $delete = optional_param('delete', 0, PARAM_INT);
        $subscribe = optional_param('subscribe', null, PARAM_INT);  // Subscribe/Unsubscribe all forums
        $context = context_system::instance();

        $strforums       = get_string('forums', 'local_forum');
        $strforum        = get_string('forum', 'local_forum');
        $strdescription  = get_string('description');
        $strdiscussions  = get_string('discussions', 'local_forum');
        $strsubscribed   = get_string('subscribed', 'local_forum');
        $strunreadposts  = get_string('unreadposts', 'local_forum');
        $strtracking     = get_string('tracking', 'local_forum');
        $strmarkallread  = get_string('markallread', 'local_forum');
        $strtrackforum   = get_string('trackforum', 'local_forum');
        $strnotrackforum = get_string('notrackforum', 'local_forum');
        $strsubscribe    = get_string('subscribe', 'local_forum');
        $strunsubscribe  = get_string('unsubscribe', 'local_forum');
        $stryes          = get_string('yes', 'local_forum');
        $strno           = get_string('no', 'local_forum');
        $strrss          = get_string('rss');
        $stremaildigest  = get_string('emaildigest');

        // delete forum
        if ($delete) {
            local_forum_delete_instance($id);
            redirect('index.php');
        }

                // Start of the table for General Forums.
        $generaltable = new html_table();
        $generaltable->id = 'forumtable';
        $generaltable->head  = array ('');

        if ($usetracking = local_forum_tp_can_track_forums()) {
            $untracked = local_forum_tp_get_untracked_forums($USER->id, $course->id);
        }

        // Fill the subscription cache for this course and user combination.
        $table = new html_table();

        // Parse and organise all the forums.  Most forums are course modules but
        // some special ones are not.  These get placed in the general forums
        // category with the forums in section 0.
        $forums = local_get_forums($context);
        $generalforums  = array();
        $learningforums = array();
        $showsubscriptioncolumns = false;
        foreach ($forums as $forum) {   
            if (!has_capability('local/forum:viewdiscussion', $context)) {
                // User can't view this one - skip it.
                continue;
            }

            // Determine whether subscription options should be displayed.
            $forum->cansubscribe = \local_forum\subscriptions::is_subscribable($forum);
            $forum->cansubscribe = $forum->cansubscribe || has_capability('local/forum:managesubscriptions', $context);
            $forum->issubscribed = \local_forum\subscriptions::is_subscribed($USER->id, $forum, null, null);

            $showsubscriptioncolumns = $showsubscriptioncolumns || $forum->issubscribed || $forum->cansubscribe;
            $generalforums[$forum->id] = $forum;
        }

        // Do course wide subscribe/unsubscribe if requested
        if (!is_null($subscribe)) {
            if (isguestuser() or !$showsubscriptioncolumns) {
                // There should not be any links leading to this place, just redirect.
                redirect( new moodle_url('/local/forum/index.php', array('id' => $id)),
                    get_string('subscribeenrolledonly', 'local_forum'), null, \core\output\notification::NOTIFY_ERROR
                );
            }
            // Can proceed now, the user is not guest and is enrolled
            //foreach ($modinfo->get_instances_of('forum') as $forumid => $cm) {
            foreach ($forums as $forum) {
                $cansub = false;

                if (has_capability('local/forum:viewdiscussion', $context)) {
                    $cansub = true;
                }
                if (!\local_forum\subscriptions::is_forcesubscribed($forum)) {
                    $subscribed = \local_forum\subscriptions::is_subscribed($USER->id, $forum, null);
                    //$canmanageactivities = has_capability('moodle/course:manageactivities', $coursecontext, $USER->id);
                    if ((\local_forum\subscriptions::is_subscribable($forum)) && $subscribe && !$subscribed && $cansub) {
                        \local_forum\subscriptions::subscribe_user($USER->id, $forum, $context, true);
                    } else if (!$subscribe && $subscribed) {
                        \local_forum\subscriptions::unsubscribe_user($USER->id, $forum, $context, true);
                    }
                }
            }
            $returnto = local_forum_go_back_to(new moodle_url('/local/forum/index.php'));
            $shortname = format_string($forum->name);
            if ($subscribe) {
                redirect(
                    $returnto,
                    get_string('nowallsubscribed', 'local_forum', $shortname),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    $returnto,
                    get_string('nowallunsubscribed', 'local_forum', $shortname),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
        }

        if ($generalforums) {
            // Process general forums.
            foreach ($generalforums as $forum) {
                $row = array();
                //$cm      = $modinfo->instances['forum'][$forum->id];
                $context = context_system::instance();

                //$count = local_forum_count_discussions($forum, $cm, $course);
                $count = local_forum_count_discussions($forum);

                if ($usetracking) {
                    if ($forum->trackingtype == LOCAL_FORUM_TRACKING_OFF) {
                        $unreadlink  = '-';
                        $trackedlink = '-';

                    } else {
                        if (isset($untracked[$forum->id])) {
                                $unreadlink  = '-';
                        } else if ($unread = local_forum_tp_count_forum_unread_posts($forum->id)) {
                            $unreadlink = '<span class="unread"><a href="view.php?f='.$forum->id.'">'.$unread.'</a>';
                            $icon = $OUTPUT->pix_icon('t/markasread', $strmarkallread);
                            $unreadlink .= '<a title="'.$strmarkallread.'" href="markposts.php?f='.
                                           $forum->id.'&amp;mark=read&amp;sesskey=' . sesskey() . '">' . $icon . '</a></span>';
                        } else {
                            $unreadlink = '<span class="read">0</span>';
                        }

                        if (($forum->trackingtype == LOCAL_FORUM_TRACKING_FORCED) && ($CFG->local_forum_allowforcedreadtracking)) {
                            $trackedlink = $stryes;
                        } else if ($forum->trackingtype === LOCAL_FORUM_TRACKING_OFF || ($USER->trackforums == 0)) {
                            $trackedlink = '-';
                        } else {
                            $aurl = new moodle_url('/local/forum/settracking.php', array(
                                    'id' => $forum->id,
                                    'sesskey' => sesskey(),
                                ));
                            if (!isset($untracked[$forum->id])) {
                                $trackedlink = $OUTPUT->single_button($aurl, $stryes, 'post', array('title' => $strnotrackforum));
                            } else {
                                $trackedlink = $OUTPUT->single_button($aurl, $strno, 'post', array('title' => $strtrackforum));
                            }
                        }
                    }
                }

                //$description = \local_costcenter\lib::strip_tags_custom($forum->intro);
                $options = new stdClass;
                $options->para    = false;
                $options->trusted = 1;
                $options->context = $context;
                $description = format_text($forum->intro, $forum->introformat, $options);
                $description_string = strlen($description) > 510 ? substr($description, 0, 510)."..." : $description;
                $forumname = format_string($forum->name, true);
                $style = '';
                $forumlink = "<a href=\"view.php?f=$forum->id\" $style>".format_string($forum->name,true)."</a>";
                $discussionlink = "<a class='pull-right' href=\"view.php?f=$forum->id\" $style>".$count."</a>";

                //$row = array ($forumlink, $forum->intro, $discussionlink);
                $line['forumlink'] = $forumlink;
                $line['description'] = $description_string;
                $line['discussionlink'] = $discussionlink;
                //$line['forumimage'] = $OUTPUT->image_url('forumimgnew', 'local_forum');
                $postuser = new stdClass();
                //$postuserfields = explode(',', user_picture::fields());
                //$postuser = username_load_fields_from_object($postuser, $forum, null, $postuserfields);
                $postuser->id = $forum->usermodified;
        
                $line['forumimage'] = $OUTPUT->user_picture($postuser, array('link' => false));
                $line['forumuserid'] = $forum->usermodified;
                $line['forumuser'] = $DB->get_field_sql("select concat(firstname,' ',lastname) from {user} where id=?", array($forum->usermodified));;
                $line['discussionicon'] = $OUTPUT->image_url('discussionicon', 'local_forum');
                if ($usetracking) {
                    $line['unreadlink'] = $unreadlink;
                    $line['trackedlink'] = $trackedlink;    // Tracking.
                }

                if ($showsubscriptioncolumns) {
                    $line['subscribe'] = local_forum_get_subscribe_link($forum, $context, array('subscribed' => $stryes,
                        'unsubscribed' => $strno, 'forcesubscribed' => $stryes,
                        'cantsubscribe' => '-'), false, false, true);
                    $line['emaildigest'] = local_forum_index_get_forum_subscription_selector($forum, $context);
                }
                $edit = $delete = $actions = false;
                if (has_capability('local/forum:editanypost', $context)) {
                    $edit = true;
                }
                
                if (has_capability('local/forum:deleteanypost', $context)) {
                    $delete = true;
                }
                
                if (has_capability('local/forum:addinstance', $context)){
                    $actions = true;
                }
                $line['edit'] = $edit;
                $line['delete'] = $delete;
                $line['actions'] = $actions;  
                $line['forumid'] = $forum->id;
                $line['contextid'] = $context->id;  
            
             $row[] = $this->render_from_template('local_forum/forumslist', $line);         
             $data[] = $row;
                
            }
            $generaltable->data = $data;
            
        }

        if ($generalforums) {
            $result = html_writer::table($generaltable);
        } else {
            $result = '<div class="alert-box alert alert-info text-center mt-15 clear-both">'.get_string('noforums', 'local_forum').'</div>';
        }
        return $result;
    }

    /**
     * This method is used to render discussion topics list
     * @return table structure
     */
    public function get_forumtopics_list(&$post, $local_forum, $group = -1, $datestring = "", $cantrack = true, $local_forumtracked = true, $canviewparticipants = true, $context = null, $canviewhiddentimedposts = false) {

        global $CFG, $DB, $OUTPUT, $PAGE,$USER;
        $topiccontext = array();
        static $rowcount;
        static $strmarkalldread;

        if (!isset($rowcount)) {
            $rowcount = 0;
            $strmarkalldread = get_string('markalldread', 'local_forum');
        } else {
            $rowcount = ($rowcount + 1) % 2;
        }

        $post->subject = format_string($post->subject,true);

        $timeddiscussion = !empty($CFG->local_forum_enabletimedposts) && ($post->timestart || $post->timeend);
        $timedoutsidewindow = '';
        if ($timeddiscussion && ($post->timestart > time() || ($post->timeend != 0 && $post->timeend < time()))) {
            $timedoutsidewindow = ' dimmed_text';
        }

        $topicclass = 'topic starter';
        if (LOCAL_FORUM_DISCUSSION_PINNED == $post->pinned) {
            $topicclass .= ' pinned';
        }
        if (LOCAL_FORUM_DISCUSSION_PINNED == $post->pinned) {
            echo $OUTPUT->pix_icon('i/pinned', get_string('discussionpinned', 'local_forum'), 'local_forum');
        }
        $canalwaysseetimedpost = $USER->id == $post->userid || $canviewhiddentimedposts;
        if ($timeddiscussion && $canalwaysseetimedpost) {

            $cal_info = $PAGE->get_renderer('local_forum')->timed_discussion_tooltip($post, empty($timedoutsidewindow));
        
        }
        $topicname = \local_costcenter\lib::strip_tags_custom($post->subject);
        $topicname_string = strlen($topicname) > 75 ? substr($topicname, 0, 75)."..." : $topicname;

        $topiccontext['discussionid'] = $post->discussion;
        $topiccontext['topicname'] = $topicname_string;
        $topiccontext['discussionicon'] = $OUTPUT->image_url('discussionicon', 'local_forum');
        $topiccontext['configpath'] = $CFG->wwwroot;


        // Picture
        $postuser = new stdClass();
        $postuserfields = explode(',', user_picture::fields());
        $postuser = username_load_fields_from_object($postuser, $post, null, $postuserfields);
        $postuser->id = $post->userid;
        $topiccontext['postuser'] = $OUTPUT->user_picture($postuser, array('size' => 65, 'class' => 'w-full postuserimg'));
        // User name
        $topiccontext['postuser_name'] = fullname($postuser, has_capability('moodle/site:viewfullnames', $context));
        $topiccontext['postuserid'] = $post->userid;

        if (has_capability('local/forum:viewdiscussion', $context)) {   // Show the column with replies
            $topiccontext['repliescountlink'] = $post->replies;

            if ($cantrack) {
                //echo '<td class="replies">';
                if ($local_forumtracked) {
                    if ($post->unread > 0) {
                        echo '<span class="unread">';
                        echo '<a href="'.$CFG->wwwroot.'/local/forum/discuss.php?d='.$post->discussion.'#unread">';
                        echo $post->unread;
                        echo '</a>';
                        echo '<a title="'.$strmarkalldread.'" href="'.$CFG->wwwroot.'/local/forum/markposts.php?f='.
                             $local_forum->id.'&amp;d='.$post->discussion.'&amp;mark=read&amp;returnpage=view.php&amp;sesskey=' . sesskey() . '">' .
                             $OUTPUT->pix_icon('t/markasread', $strmarkalldread) . '</a>';
                        echo '</span>';
                    } else {
                        echo '<span class="read">';
                        echo $post->unread;
                        echo '</span>';
                    }
                } else {
                    echo '<span class="read">';
                    echo '-';
                    echo '</span>';
                }
                //echo "</td>\n";
            }
        }

        //echo '<td class="lastpost">';
        $usedate = (empty($post->timemodified)) ? $post->modified : $post->timemodified;  // Just in case
        $parenturl = '';
        $usermodified = new stdClass();
        $usermodified->id = $post->usermodified;
        $usermodified = username_load_fields_from_object($usermodified, $post, 'um');

        // In QA local_forums we check that the user can view participants.
        if ($local_forum->type !== 'qanda' || $canviewparticipants) {
            $topiccontext['lastpostby'] = fullname($usermodified);
            $topiccontext['lastpostuserid'] = $post->usermodified;
            $parenturl = (empty($post->lastpostid)) ? '' : '&amp;parent='.$post->lastpostid;
        }
        $topiccontext['lastpostdatelink'] = $post->discussion.$parenturl;
        $topiccontext['lastpostdate'] = userdate($usedate, $datestring);

        // is_guest should be used here as this also checks whether the user is a guest in the current course.
        // Guests and visitors cannot subscribe - only enrolled users.
        if ((isloggedin()) && has_capability('local/forum:viewdiscussion', $context)) {
            // Discussion subscription.
            if (\local_forum\subscriptions::is_subscribable($local_forum)) {
                $topiccontext['subscriptions'] = local_forum_get_discussion_subscription_icon($local_forum, $post->discussion);
            }
        }

        $discussion = $DB->get_record('local_forum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
        $post->parent = $post->discussion;
        $ownpost = (isloggedin() && $USER->id == $post->userid);
        $context = context_system::instance();
        $link  = false;
        $postread = !empty($post->postread);
        $reply = local_forum_user_can_post($local_forum, $discussion, $USER, $context);
        $local_forumtracked = local_forum_tp_is_tracked($local_forum);
        // get latest post in a discussion
        $query = "SELECT  *  
                    FROM {local_forum_posts} 
                    WHERE parent <> 0 AND discussion=? ORDER BY modified DESC ";
        $latest_post = $DB->get_record_sql($query, array($post->discussion));
        if ($latest_post) {
            $latest_post_user = $DB->get_field_sql("select concat(firstname,'',lastname) from {user} where id=?", array($latest_post->userid));
            $postuser = new stdClass();
            $postuserfields = explode(',', user_picture::fields());
            $postuser = username_load_fields_from_object($postuser, $post, null, $postuserfields);
            $postuser->id = $post->userid;
            $options = new stdClass;
            $options->para    = false;
            $options->trusted = $latest_post->messagetrust;
            $options->context = $context;
            $userpic = $OUTPUT->user_picture($postuser);
            $topiccontext['latestpostuser'] = $latest_post_user;
            $topiccontext['latestpostsubject'] = $OUTPUT->user_picture($postuser).$latest_post->subject;
            $topiccontext['latestposthas_cotent'] = $latest_post->subject;
            $latest_post_message = \local_costcenter\lib::strip_tags_custom($latest_post->message);
            $topiccontext['latestpostdesc'] = format_text($latest_post_message, $latest_post->messageformat, $options);
            $topiccontext['latestpostuserid'] = $latest_post->userid;
            $usedate = (empty($latest_post->timemodified)) ? $latest_post->modified : $latest_post->timemodified;
            $topiccontext['latestpost_date'] = userdate($usedate, $datestring);
        }
        $topiccontext['data'] = $cal_info;
        $topiccontext['postid'] = $post->id;
        $topiccontext['posteduser'] = $USER->id == $post->userid ? TRUE : FALSE;
       
            
        return $this->render_from_template('local_forum/topicslist', $topiccontext);

    }

    /**
     * Renders html to print list of forums tagged with particular tag
     *
     * @param int $tagid id of the tag
     * @param bool $exclusivemode if set to true it means that no other entities tagged with this tag
     *             are displayed on the page and the per-page limit may be bigger
     * @param int $fromctx context id where the link was displayed, may be used by callbacks
     *            to display items in the same context first
     * @param int $ctx context id where to search for records
     * @param bool $rec search in subcontexts as well
     * @param array $displayoptions
     * @return string empty string if no courses are marked with this tag or rendered list of courses
     */
  /* public function tagged_forums($tagid, $exclusivemode, $ctx, $rec, $displayoptions, $count = 0, $sort = 0) {
    global $CFG, $DB, $USER;
    $systemcontext = context_system::instance();
    if ($count > 0)
    $sql =" select count(f.id) from {local_forum} f ";
    else
    $sql =" select f.* from {local_forum} f ";

    $where = " where f.id IN (SELECT t.itemid FROM {tag_instance} t WHERE t.tagid = :tagid AND t.itemtype = :itemtype AND t.component = :component)";

    if (is_siteadmin())
    $where .= " AND 1=1 ";
    elseif (has_capability('local/forum:addnews',$systemcontext))
        $where .= depsql($systemcontext); // get records department wise
    else
    $where .= " AND f.id IN (select forum from {local_forum_subscriptions} where userid = $USER->id)";

    $joinsql = $groupby = $orderby = '';
    if (!empty($sort)) {
      switch($sort) {
        case 'highrate':
        if ($DB->get_manager()->table_exists('local_rating')) {
          $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = f.id AND r.ratearea = 'local_forum' ";
          $groupby .= " group by f.id ";
          $orderby .= " order by AVG(rating) desc ";
        }        
        break;
        case 'lowrate':  
        if ($DB->get_manager()->table_exists('local_rating')) {  
          $joinsql .= " LEFT JOIN {local_rating} as r ON r.moduleid = f.id AND r.ratearea = 'local_forum' ";
          $groupby .= " group by f.id ";
          $orderby .= " order by AVG(rating) asc ";
        }
        break;
        case 'latest':
        $orderby .= " order by f.timemodified desc ";
        break;
        case 'oldest':
        $orderby .= " order by f.timemodified asc ";
        break;
        default:
        $orderby .= " order by f.timemodified desc ";
        break;
        }
    }

    $params = array('tagid' => $tagid, 'itemtype' => 'forum', 'component' => 'local_forum');

    if ($count > 0) {
      $records = $DB->count_records_sql($sql.$where, $params);
      return $records;
    } else {
      $records = $DB->get_records_sql($sql.$joinsql.$where.$groupby.$orderby, $params);
    }
    $tagfeed = new local_tags\output\tagfeed(array(), 'forums');
    $img = $this->output->pix_icon('i/course', '');
    foreach ($records as $key => $value) {
      $url = $CFG->wwwroot.'/local/forum/view.php?f='.$value->id.'';
      $imgwithlink = html_writer::link($url, $img);
      $modulename = html_writer::link($url, $value->name);
      $forumdetails = get_forum_details($value->id);
      $details = $this->render_from_template('local_forum/tagview', $forumdetails);
      $tagfeed->add($imgwithlink, $modulename, $details);
    }
    return $this->output->render_from_template('local_tags/tagfeed', $tagfeed->export_for_template($this->output));
  }*/
}
 