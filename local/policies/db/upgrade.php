<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_policies_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();


    if ($oldversion < 2022082601) {

        // Define field categoryid to be added to local_policies.
        $table = new xmldb_table('local_policies');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'name');

        // Conditionally launch add field categoryid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Define table local_policycategories_table to be created.
        $table = new xmldb_table('local_policycategories_table');

        // Adding fields to table local_policycategories_table.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_policycategories_table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_policycategories_table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Policies savepoint reached.
        upgrade_plugin_savepoint(true, 2022082601, 'local', 'policies');
    }

    if ($oldversion < 2022082602) {

        // Define field maxstudent to be added to course.
        $table = new xmldb_table('course');
        $field = new xmldb_field('maxstudent', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'idnumber');

        // Conditionally launch add field maxstudent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // plugin savepoint reached.
        upgrade_plugin_savepoint(true, 2022082602, 'local', 'policies');
    }

    return true;
}