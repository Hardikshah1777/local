<?php

function xmldb_tool_mfa_upgrade($oldversion)
{
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020021401.01) {

        // Define table tool_mfa_skipusers to be created.
        $table = new xmldb_table('tool_mfa_skipusers');

        // Adding fields to table tool_mfa_skipusers.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '15', null, null, null, null);

        // Adding keys to table tool_mfa_skipusers.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for tool_mfa_skipusers.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mfa savepoint reached.
        upgrade_plugin_savepoint(true, 2020021401.01, 'tool', 'mfa');
    }
    if ($oldversion < 2020021401.02) {

        // Define table tool_mfa_secrets to be created.
        $table = new xmldb_table('tool_mfa_secrets');

        // Adding fields to table tool_mfa_secrets.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('factor', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('secret', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '15', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expiry', XMLDB_TYPE_INTEGER, '15', null, XMLDB_NOTNULL, null, null);
        $table->add_field('revoked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sessionid', XMLDB_TYPE_CHAR, '100', null, null, null, null);

        // Adding keys to table tool_mfa_secrets.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes to table tool_mfa_secrets.
        $table->add_index('factor', XMLDB_INDEX_NOTUNIQUE, ['factor']);
        $table->add_index('expiry', XMLDB_INDEX_NOTUNIQUE, ['expiry']);

        // Conditionally launch create table for tool_mfa_secrets.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mfa savepoint reached.
        upgrade_plugin_savepoint(true, 2020021401.02, 'tool', 'mfa');
    }
    if ($oldversion < 2020021401.03) {

        // Define table tool_mfa_auth to be created.
        $table = new xmldb_table('tool_mfa_auth');

        // Adding fields to table tool_mfa_auth.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastverified', XMLDB_TYPE_INTEGER, '15', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table tool_mfa_auth.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for tool_mfa_auth.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Mfa savepoint reached.
        upgrade_plugin_savepoint(true, 2020021401.03, 'tool', 'mfa');
    }


    return true;
}