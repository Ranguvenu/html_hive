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

function xmldb_local_externalcertificate_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
  
    if ($oldversion < 2022111500) {
        $table = new xmldb_table('local_external_certificates');

        $field = new xmldb_field('institute_provider', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('category', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('duration', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('url', XMLDB_TYPE_TEXT, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('certificate_issuing_authority', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('skill', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('description', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('issueddate', XMLDB_TYPE_INTEGER, '20', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('validedate', XMLDB_TYPE_INTEGER, '20', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        $field = new xmldb_field('reason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022111500, 'local', 'externalcertificate');
    }

    if ($oldversion < 2022111500.03) {
        $table = new xmldb_table('local_external_certif_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('extcertid', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('reason', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

            $dbman->create_table($table);

            upgrade_plugin_savepoint(true, 2022111500.03, 'local', 'externalcertificate');
        }
    }
 
    if ($oldversion < 2022111500.05) {
    
        $time = time();
        $initcontent = array('name' => 'External Certificate', 'shortname' => 'externalcertificate', 'parent_module' => '0', 'usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL, 'pluginname' => 'externalcertificate');
        $parentid = $DB->get_field('local_notification_type', 'id', array('shortname' => 'externalcertificate'));
        if (!$parentid) {
            $parentid = $DB->insert_record('local_notification_type', $initcontent);
        }
        $notification_type_data = array(

            array('name' => 'Certificate Uploaded', 'shortname' => 'certificate_uploaded', 'parent_module' => $parentid, 'usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL, 'pluginname' => 'externalcertificate'),
            array('name' => 'Certificate Approved', 'shortname' => 'certificate_approved', 'parent_module' => $parentid, 'usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL, 'pluginname' => 'externalcertificate'),
            array('name' => 'Certificate Declined', 'shortname' => 'certificate_declined', 'parent_module' => $parentid, 'usercreated' => '2', 'timecreated' => $time, 'usermodified' => 2, 'timemodified' => NULL, 'pluginname' => 'externalcertificate'),
        );
        foreach ($notification_type_data as $notification_type) {
            unset($notification_type['timecreated']);
            if (!$DB->record_exists('local_notification_type',  $notification_type)) {
                $notification_type['timecreated'] = $time;
                $DB->insert_record('local_notification_type', $notification_type);
            }
        }
        upgrade_plugin_savepoint(true, 2022111500.05, 'local', 'externalcertificate');
    
    } 

    if ($oldversion < 2022111500.06) {
        $table = new xmldb_table('local_external_certificates');
        $field = new xmldb_field('expiry', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2022111500.06, 'local', 'externalcertificate');
    }

    if ($oldversion < 2022111500.07) {
        $table = new xmldb_table('local_external_certificates');
        $field = new xmldb_field('authority_type', XMLDB_TYPE_CHAR, '255', null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2022111500.07, 'local', 'externalcertificate');
    }

    if ($oldversion <  2022111500.08) {

        $table = new xmldb_table('local_external_certificates');
        $field = new xmldb_field('skill', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        upgrade_plugin_savepoint(true, 2022111500.08, 'local', 'external_certificates');
    }

     if ($oldversion < 2022111500.09) {
        $table = new xmldb_table('local_external_certificates_courses');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);       
            $table->add_field('coursename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('coursecode', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);      
            $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $dbman->create_table($table);
            upgrade_plugin_savepoint(true, 2022111500.09, 'local', 'externalcertificate');
        }
    }

    if ($oldversion < 2022111500.10) {
        $table1 = new xmldb_table('local_external_certificates');
        $field = new xmldb_field('mastercourse', XMLDB_TYPE_CHAR, 255, null, null, null);
        if (!$dbman->field_exists($table1, $field)) {
            $dbman->add_field($table1, $field);
        } 
        // $field2 = new xmldb_field('coursename', XMLDB_TYPE_CHAR, 255, null, null, null, null);
        // if ($dbman->field_exists($table, $field2)) {
        //     $dbman->change_field_type($table, $field2);
        // }
        upgrade_plugin_savepoint(true, 2022111500.10, 'local', 'externalcertificate');        
    }

    return true;
}
