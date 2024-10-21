<?php

function xmldb_block_user_bookmarks_upgrade($oldversion = 0) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2014090502) {

        // Define table block_user_bookmarks to be created.
        $table = new xmldb_table('block_user_bookmarks');

        // Adding fields to table block_user_bookmarks.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);

        // Adding keys to table block_user_bookmarks.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_user_bookmarks.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // User_bookmarks savepoint reached.
        upgrade_block_savepoint(true, 2014090502, 'user_bookmarks');
    }


    if ($oldversion < 2022122501) {
        $table = new xmldb_table('block_custom_userbookmark');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('learningtype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('description', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $dbman->create_table($table);

            upgrade_plugin_savepoint(true, 2022122501, 'block', 'user_bookmarks');
        }
    }

    if ($oldversion < 2022122501) {

        $table = new xmldb_table('block_custom_userbookmark');
        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022122501, 'block', 'user_bookmarks');

    }
    return true;
}