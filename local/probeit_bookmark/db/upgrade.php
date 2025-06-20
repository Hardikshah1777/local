<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_probeit_bookmark_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 20250121000.01) {

        // Define table local_probeit_bookmark to be created.
        $table = new xmldb_table('local_probeit_bookmark');

        // Adding fields to table local_probeit_bookmark.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('link', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_probeit_bookmark.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_probeit_bookmark.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Probeit_bookmark savepoint reached.
        upgrade_plugin_savepoint(true, 20250121000.01, 'local', 'probeit_bookmark');
    }

    return true;
}