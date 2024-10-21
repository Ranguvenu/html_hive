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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/forum/lib.php');
require_once($CFG->libdir . '/rsslib.php');

$id = optional_param('id', 0, PARAM_INT);             // forum id
$delete = optional_param('delete', 0, PARAM_INT);
$subscribe = optional_param('subscribe', null, PARAM_INT);  // Subscribe/Unsubscribe all forums

$url = new moodle_url('/local/forum/index.php', array('id' => $id));
if ($subscribe !== null) {
    require_sesskey();
    $url->param('subscribe', $subscribe);
}
$PAGE->set_url($url);

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

if(!(is_siteadmin() || has_capability('local/forum:viewdiscussion', $context))){
    redirect(new moodle_url('/my'));        
}

$PAGE->requires->js_call_amd('local_forum/newforum', 'load', array());
unset($SESSION->fromdiscussion);

$params = array(
    'context' => context_system::instance()
);
$PAGE->requires->jquery();
// $PAGE->requires->js('/local/onlinetests/js/jquery.dataTables.min.js',true);
// $PAGE->requires->css('/local/onlinetests/css/jquery.dataTables.css');
$PAGE->requires->js_call_amd('theme_epsilon/quickactions', 'quickactionsCall');
$PAGE->requires->js_call_amd('local_forum/newforum', 'load_datatable', array('forumtable'));
$current_langcode = current_language();  /* $SESSION->lang;*/
$stringman = get_string_manager();
$strings = $stringman->load_component_strings('local_forum', $current_langcode);   /*'en'*/
$PAGE->requires->strings_for_js(array_keys($strings), 'local_forum');
if(has_capability('local/forum:editanypost',$context)){
    $strforums = get_string('manageforum','local_forum');
}else{
    $strforums = get_string('discussion_forum','local_forum');
}
// $strforums       = get_string('forums', 'local_forum');
$CFG->local_forum_enabletimedposts = true;
// Output the page.
$PAGE->navbar->add($strforums);
$PAGE->set_title("$strforums");
$PAGE->set_heading($strforums);
echo $OUTPUT->header();
if (is_siteadmin() OR has_capability('local/forum:viewdiscussion', $context)) {
	echo "<ul class='course_extended_menu_list'>";
    if (!isguestuser() && isloggedin()) {
		// Show the subscribe all options only to non-guest, enrolled users.
		$subscriptionlink = new moodle_url('/local/forum/index.php', [
			'sesskey'   => sesskey(),
		]);
		
		 // Unsubscribe all.
		/*$subscriptionlink->param('subscribe', 0);
		echo "<li>  
		<div class = 'coursebackup course_extended_menu_itemcontainer'>                 
			<a href='".$subscriptionlink."' title='".get_string('allunsubscribe', 'local_forum')."' class='course_extended_menu_itemlink helplink'><i class='icon fa fa-ban' aria-hidden='true'></i></a>                    
		</div>
		</li>";*/

		// Subscribe all.
		/*$subscriptionlink->param('subscribe', 1);
		echo "<li>  
		<div class = 'coursebackup course_extended_menu_itemcontainer'>                 
			<a href='".$subscriptionlink."' title='".get_string('allsubscribe', 'local_forum')."' class='course_extended_menu_itemlink helplink'><i class='icon fa fa-play' aria-hidden='true'></i></a>                   
		</div>
		</li>";*/

	}
	if (is_siteadmin() OR has_capability('local/forum:addinstance', $context)) {
		echo "<li>	
				<div class = 'coursebackup course_extended_menu_itemcontainer'>					
					<a id='extended_menu_createusers' title='".get_string('forum:addinstance', 'local_forum')."' class='course_extended_menu_itemlink' data-action='createforummodal' onclick ='(function(e){ require(\"local_forum/newforum\").init({selector:\"createforummodal\", context:$context->id, form_status:0}) })(event)' ><i class='icon fa fa-plus' aria-hidden='true'></i></a>                    
				</div>
			</li>";
	}

    echo"</ul>";
}

$renderer = $PAGE->get_renderer('local_forum');
echo $renderer->get_forums_list();

echo $OUTPUT->footer();
// echo html_writer::script(' $(document).ready(function() {
// 	var table = $("#forumtable").dataTable({
//         "language": {
//             paginate: {
//                 "previous": "<",
//                 "next": ">"
//             }
//         },
//         "order": []
//     });
// });
// ');

/**
 * Get the content of the forum subscription options for this forum.
 *
 * @param   stdClass    $forum      The forum to return options for
 * @return  string
 */
function local_forum_index_get_forum_subscription_selector($forum,$context) {
    global $OUTPUT, $PAGE, $USER, $DB;

    if ($forum->cansubscribe || $forum->issubscribed) {
        if (!has_capability('local/forum:editanypost', $context)) {
            $forum->maildigest = $DB->get_field('local_forum_digests', 'maildigest',array('userid'=>$USER->id, 'forum'=>$forum->id));
            if (empty($forum->maildigest))
            $forum->maildigest = null;
        }
        
        if ($forum->maildigest === null) {
            $forum->maildigest = -1;
        }

        $renderer = $PAGE->get_renderer('local_forum');
        return $OUTPUT->render($renderer->render_digest_options($forum, $forum->maildigest));
    } else {
        // This user can subscribe to some forums. Add the empty fields.
        return '';
    }
};
