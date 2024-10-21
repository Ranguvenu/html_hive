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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
  require_once($CFG->dirroot.'/local/forum/lib.php');
	$settings = new admin_settingpage('local_forum', get_string('pluginname', 'local_forum'));
	$ADMIN->add('localplugins', $settings);
   	$settings->add(new admin_setting_configselect('local_forum_displaymode', get_string('displaymode', 'local_forum'),
                       get_string('configdisplaymode', 'local_forum'), LOCAL_FORUM_MODE_NESTED, local_forum_get_layout_modes()));
    $settings->add(new admin_setting_configcheckbox('local_forum_enabletimedposts', get_string('timedposts', 'local_forum'),
                      get_string('configenabletimedposts', 'local_forum'), 1));

    // Less non-HTML characters than this is short
    $settings->add(new admin_setting_configtext('local_forum_shortpost', get_string('shortpost', 'local_forum'),
                       get_string('configshortpost', 'local_forum'), 300, PARAM_INT));

    // More non-HTML characters than this is long
    $settings->add(new admin_setting_configtext('local_forum_longpost', get_string('longpost', 'local_forum'),
                       get_string('configlongpost', 'local_forum'), 600, PARAM_INT));

    // Number of discussions on a page
    $settings->add(new admin_setting_configtext('local_forum_manydiscussions', get_string('manydiscussions', 'local_forum'), get_string('configmanydiscussions', 'local_forum'), 100, PARAM_INT));

    if (isset($CFG->maxbytes)) {
        $maxbytes = 0;
        if (isset($CFG->local_forum_maxbytes)) {
            $maxbytes = $CFG->local_forum_maxbytes;
        }
        $settings->add(new admin_setting_configselect('local_forum_maxbytes', get_string('maxattachmentsize', 'local_forum'), get_string('configmaxbytes', 'local_forum'), 512000, get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes)));
    }

    // Default number of attachments allowed per post in all forums
    $settings->add(new admin_setting_configtext('local_forum_maxattachments', get_string('maxattachments', 'local_forum'),
                       get_string('configmaxattachments', 'forum'), 9, PARAM_INT));

    // Default Subscription mode setting.
    $options = local_forum_get_subscriptionmode_options();
    $settings->add(new admin_setting_configselect('local_forum_subscription', get_string('subscriptionmode', 'local_forum'),
        get_string('configsubscriptiontype', 'local_forum'), LOCAL_FORUM_CHOOSESUBSCRIBE, $options));


    // Default Read Tracking setting.
    $options = array();
    $options[LOCAL_FORUM_TRACKING_OPTIONAL] = get_string('trackingoptional', 'local_forum');
    $options[LOCAL_FORUM_TRACKING_OFF] = get_string('trackingoff', 'local_forum');
    $options[LOCAL_FORUM_TRACKING_FORCED] = get_string('trackingon', 'local_forum');
    $settings->add(new admin_setting_configselect('local_forum_trackingtype', get_string('trackingtype', 'local_forum'),
                       get_string('configtrackingtype', 'local_forum'), LOCAL_FORUM_TRACKING_OPTIONAL, $options));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('local_forum_trackreadposts', get_string('trackforum', 'local_forum'),
                       get_string('configtrackreadposts', 'local_forum'), 1));

    // Default whether user needs to mark a post as read.
    $settings->add(new admin_setting_configcheckbox('local_forum_allowforcedreadtracking', get_string('forcedreadtracking', 'local_forum'),
                       get_string('forcedreadtracking_desc', 'local_forum'), 0));

    // Default number of days that a post is considered old
    $settings->add(new admin_setting_configtext('local_forum_oldpostdays', get_string('oldpostdays', 'local_forum'),
                       get_string('configoldpostdays', 'local_forum'), 14, PARAM_INT));

    // Default whether user needs to mark a post as read
    $settings->add(new admin_setting_configcheckbox('local_forum_usermarksread', get_string('usermarksread', 'local_forum'),
                       get_string('configusermarksread', 'local_forum'), 0));

    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = sprintf("%02d",$i);
    }
    // Default time (hour) to execute 'clean_read_records' cron
    $settings->add(new admin_setting_configselect('local_forum_cleanreadtime', get_string('cleanreadtime', 'local_forum'),
                       get_string('configcleanreadtime', 'forum'), 2, $options));

    // Default time (hour) to send digest email
    $settings->add(new admin_setting_configselect('local_digestmailtime', get_string('digestmailtime', 'local_forum'),
                       get_string('configdigestmailtime', 'local_forum'), 17, $options));
}
