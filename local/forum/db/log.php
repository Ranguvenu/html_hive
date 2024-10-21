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



defined('MOODLE_INTERNAL') || die();

global $DB; // TODO: this is a hack, we should really do something with the SQL in SQL tables.

$logs = array(
    array('module' => 'local_forum', 'action' => 'add', 'mtable' => 'local_forum', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'update', 'mtable' => 'local_forum', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'add discussion', 'mtable' => 'local_forum_discussions', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'add post', 'mtable' => 'local_forum_posts', 'field' => 'subject'),
    array('module' => 'local_forum', 'action' => 'update post', 'mtable' => 'local_forum_posts', 'field' => 'subject'),
    array('module' => 'local_forum', 'action' => 'user report', 'mtable' => 'user',
          'field'  => $DB->sql_concat('firstname', "' '", 'lastname')),
    array('module' => 'local_forum', 'action' => 'move discussion', 'mtable' => 'local_forum_discussions', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'view subscribers', 'mtable' => 'local_forum', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'view discussion', 'mtable' => 'local_forum_discussions', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'view local_forum', 'mtable' => 'local_forum', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'subscribe', 'mtable' => 'local_forum', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'unsubscribe', 'mtable' => 'local_forum', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'pin discussion', 'mtable' => 'local_forum_discussions', 'field' => 'name'),
    array('module' => 'local_forum', 'action' => 'unpin discussion', 'mtable' => 'local_forum_discussions', 'field' => 'name'),
);