<?php
defined( 'MOODLE_INTERNAL' ) || die();

function xmldb_local_test2_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024081500.01) {
        // Define table local_test2 to be created.
        $table = new xmldb_table('local_test2');
        // Adding fields to table local_test2.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('city', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_test2.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_test2.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Test2 savepoint reached.
        upgrade_plugin_savepoint(true, 2024081500.01, 'local', 'test2');
    }

    if ($oldversion < 2024081500.02) {

        // Define field timeupdated to be added to local_test2.
        $table = new xmldb_table('local_test2');
        $field = new xmldb_field('timeupdated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');

        // Conditionally launch add field timeupdated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Test2 savepoint reached.
        upgrade_plugin_savepoint(true, 2024081500.02, 'local', 'test2');
    }


    return true;
}