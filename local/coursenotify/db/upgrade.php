<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_coursenotify_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019110102) {

        // Define field expirynotify to be added to local_coursenotify.
        $table = new xmldb_table('local_coursenotify');
        $field = new xmldb_field('expirynotify', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'threshold');

        // Conditionally launch add field expirynotify.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursenotify savepoint reached.
        upgrade_plugin_savepoint(true, 2019110102, 'local', 'coursenotify');
    }

    if ($oldversion < 2019110103) {

        // Changing nullability of field threshold on table local_coursenotify to not null.
        $table = new xmldb_table('local_coursenotify');
        $field = new xmldb_field('threshold', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'refdate');

        // Launch change of nullability for field threshold.
        $dbman->change_field_notnull($table, $field);

        $field = new xmldb_field('expirynotify', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'threshold');

        // Launch change of nullability for field expirynotify.
        $dbman->change_field_notnull($table, $field);

        // Coursenotify savepoint reached.
        upgrade_plugin_savepoint(true, 2019110103, 'local', 'coursenotify');
    }

    if ($oldversion < 2019110104) {

        // Define field timemodified to be added to local_coursenotify.
        $table = new xmldb_table('local_coursenotify');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursenotify savepoint reached.
        upgrade_plugin_savepoint(true, 2019110104, 'local', 'coursenotify');
    }

    if ($oldversion < 2019110105) {

        // Define field title to be added to local_coursenotify.
        $table = new xmldb_table('local_coursenotify');
        $field = new xmldb_field('title', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'courseid');

        // Conditionally launch add field title.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Coursenotify savepoint reached.
        upgrade_plugin_savepoint(true, 2019110105, 'local', 'coursenotify');
    }

    return true;
}
