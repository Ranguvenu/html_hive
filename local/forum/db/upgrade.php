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
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_forum_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    if ($oldversion < 2022111502) {
        $table = new xmldb_table('local_forum_like');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('forumid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('discussionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('likearea', XMLDB_TYPE_CHAR, null, null, null, null, '0');
            $table->add_field('likestatus', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');        
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $result = $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2022111502, 'local', 'forum');
    } 
    if ($oldversion < 2022111503) {
        $table = new xmldb_table('local_forum_like');
        $field = new xmldb_field('postid', XMLDB_TYPE_INTEGER, '10', null, null, null,'0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
      
        upgrade_plugin_savepoint(true, 2022111503, 'local', 'forum');
    } 
    if ($oldversion < 2022111504) {
        $table = new xmldb_table('local_forum');
        $field = new xmldb_field('courseid', XMLDB_TYPE_CHAR, '255', null, null, null,'0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }      
        upgrade_plugin_savepoint(true, 2022111504, 'local', 'forum');
    } 
    
    return true;
}
