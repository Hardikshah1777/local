<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_registration_upgrade($oldversion)
{
    global $CFG, $DB;
    $dbman = $DB->get_manager();
 if ($oldversion < 2023071701) {

    // Define field userid to be added to local_registration.
    $table = new xmldb_table('local_registration');
    $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'visible');

    // Conditionally launch add field userid.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Registration savepoint reached.
    upgrade_plugin_savepoint(true, 2023071701, 'local', 'registration');
}
if ($oldversion < 2023071701.01) {

    // Define key userid (foreign) to be added to local_registration.
    $table = new xmldb_table('local_registration');
    $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

    // Launch add key userid.
    $dbman->add_key($table, $key);

    // Registration savepoint reached.
    upgrade_plugin_savepoint(true, 2023071701.01, 'local', 'registration');
}
if ($oldversion <2023071701.02) {

    // Changing type of field couponcode on table local_registration to char.
    $table = new xmldb_table('local_registration');
    $field = new xmldb_field('couponcode', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'id');

    // Launch change of type for field couponcode.
    $dbman->change_field_type($table, $field);

    // Registration savepoint reached.
    upgrade_plugin_savepoint(true, 2023071701.02, 'local', 'registration');
}  if ($oldversion < 2023071701.03) {

    // Define field courseid to be added to local_registration.
    $table = new xmldb_table('local_registration');
    $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');

    // Conditionally launch add field courseid.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Registration savepoint reached.
    upgrade_plugin_savepoint(true, 2023071701.03, 'local', 'registration');
}
if ($oldversion <2023071701.04) {

    // Define key courseid (foreign) to be added to local_registration.
    $table = new xmldb_table('local_registration');
    $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

    // Launch add key courseid.
    $dbman->add_key($table, $key);

    // Registration savepoint reached.
    upgrade_plugin_savepoint(true, 2023071701.04, 'local', 'registration');
}
if ($oldversion < 2023071701.05) {

    // Define field duration to be added to local_registration.
    $table = new xmldb_table('local_registration');
    $field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'courseid');

    // Conditionally launch add field duration.
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Registration savepoint reached.
    upgrade_plugin_savepoint(true, 2023071701.05, 'local', 'registration');
}
    if ($oldversion < 2023071701.06) {

        // Define table local_registration_users to be created.
        $table = new xmldb_table('local_registration_users');

        // Adding fields to table local_registration_users.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('couponid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_registration_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('couponid', XMLDB_KEY_FOREIGN, ['couponid'], 'local_registration', ['id']);

        // Conditionally launch create table for local_registration_users.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Registration savepoint reached.
        upgrade_plugin_savepoint(true, 2023071701.06, 'local', 'registration');
    }
    if ($oldversion < 2023071701.07) {

        // Define field users to be added to local_registration.
        $table = new xmldb_table('local_registration');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'duration');

        // Conditionally launch add field users.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Registration savepoint reached.
        upgrade_plugin_savepoint(true, 2023071701.07, 'local', 'registration');
    }

    if ($oldversion < 2023071701.08) {

        // Define field timeused to be added to local_registration_users.
        $table = new xmldb_table('local_registration_users');
        $field = new xmldb_field('timeused', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid');

        // Conditionally launch add field timeused.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Registration savepoint reached.
        upgrade_plugin_savepoint(true, 2023071701.08, 'local', 'registration');
    }
    if ($oldversion < 2023071701.09) {

        // Define field groupid to be added to local_registration.
        $table = new xmldb_table('local_registration');
        $field = new xmldb_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'type');

        // Conditionally launch add field groupid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $key = new xmldb_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'group', ['id']);
        $dbman->add_key($table, $key);
        // Registration savepoint reached.
        upgrade_plugin_savepoint(true, 2023071701.09, 'local', 'registration');
    }
}