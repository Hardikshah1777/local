<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_studentfiles_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020120701) {

        // Define table local_studentfiles to be created.
        $table = new xmldb_table('local_studentfiles');

        // Adding fields to table local_studentfiles.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_studentfiles.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fkuserid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for local_studentfiles.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // studentfiles savepoint reached.
        upgrade_plugin_savepoint(true, 2020120701, 'local', 'studentfiles');
    }

    if ($oldversion < 2021031700) {

        // Define table local_studentfiles_templates to be created.
        $table = new xmldb_table('local_studentfiles_templates');

        // Adding fields to table local_studentfiles_templates.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_studentfiles_templates.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fkuserid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes to table local_studentfiles_templates.
        $table->add_index('name', XMLDB_INDEX_NOTUNIQUE, ['name']);

        // Conditionally launch create table for local_studentfiles_templates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Studentfiles savepoint reached.
        upgrade_plugin_savepoint(true, 2021031700, 'local', 'studentfiles');
    }

    return true;
}
