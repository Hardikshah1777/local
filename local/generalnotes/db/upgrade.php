<?php


function xmldb_local_generalnotes_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020121801) {

        // Define table local_generalnotes to be created.
        $table = new xmldb_table('local_generalnotes');

        // Adding fields to table local_generalnotes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('commenter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_generalnotes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fkuserid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('fkcommenter', XMLDB_KEY_FOREIGN, ['commenter'], 'user', ['id']);

        // Conditionally launch create table for local_generalnotes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Generalnotes savepoint reached.
        upgrade_plugin_savepoint(true, 2020121801, 'local', 'generalnotes');
    }

    return true;
}
