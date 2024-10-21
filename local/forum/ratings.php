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
 * @package   Bizlms
 * @subpackage  local_forum
 * @author eabyas  <info@eabyas.in>
**/
define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $USER, $CFG;

$forumid = $_REQUEST['forumid'];
$discussionid = $_REQUEST['discussionid'];
$parentid = $_REQUEST['parentid'];
$postid = $_REQUEST['postid'];
$likearea = $_REQUEST['likearea'];
$action = $_REQUEST['action'];

$l = (isset($action) && $action) ? 2 : 1 ;
$data = new stdClass();
$data->forumid = $forumid;
$data->discussionid = $discussionid;
$data->parentid = $parentid;
$data->userid = $USER->id;
$data->postid = $postid;
$data->likearea = $likearea;
$data->likestatus = $l;
$existdata = $DB->get_record('local_forum_like',array('userid' => $data->userid, 'likearea' => $data->likearea, 'forumid'=>$data->forumid, 'discussionid'=>$data->discussionid, 'parentid'=>$data->parentid,'postid'=>$data->postid));
if($existdata = $DB->get_record('local_forum_like',array('userid' => $data->userid, 'likearea' => $data->likearea, 'forumid'=>$data->forumid, 'discussionid'=>$data->discussionid, 'parentid'=>$data->parentid,'postid'=>$data->postid))){
	$updatedata = new stdClass();
	$updatedata->id = $existdata->id;
	$updatedata->likestatus = $data->likestatus;
	$updatedata->timemodified = time();
	$result = $DB->update_record('local_forum_like', $updatedata);
}
else{
	$data->timecreated = time();
	$data->timemodified = time();
	$result = $DB->insert_record('local_forum_like', $data);
}
$return = new stdClass();
$return->like = $DB->count_records('local_forum_like', array('likearea'=>$likearea, 'forumid'=>$forumid,'discussionid'=>$data->discussionid, 'parentid'=>$data->parentid, 'postid'=>$data->postid, 'likestatus'=>'1'));
$return->dislike = $DB->count_records('local_forum_like', array('likearea'=>$likearea, 'forumid'=>$forumid, 'discussionid'=>$data->discussionid, 'parentid'=>$data->parentid, 'postid'=>$data->postid, 'likestatus'=>'2'));

echo json_encode($return);
