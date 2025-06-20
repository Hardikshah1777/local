<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_userstatus_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020120701) {

        // Define table local_userstatus_templates to be created.
        $table = new xmldb_table('local_userstatus_templates');

        // Adding fields to table local_userstatus_templates.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_userstatus_templates.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fkuserid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes to table local_userstatus_templates.
        $table->add_index('name', XMLDB_INDEX_NOTUNIQUE, ['name']);

        // Conditionally launch create table for local_userstatus_templates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Studentfiles savepoint reached.
        upgrade_plugin_savepoint(true, 2020120701, 'local', 'userstatus');
    }

    if ($oldversion < 2020120702) {

        // Changing nullability of field subject on table local_userstatus_templates to null.
        $table = new xmldb_table('local_userstatus_templates');
        $field = new xmldb_field('subject', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');

        // Launch change of nullability for field subject.
        $dbman->change_field_notnull($table, $field);

        // Userstatus savepoint reached.
        upgrade_plugin_savepoint(true, 2020120702, 'local', 'userstatus');
    }

    return true;
}
