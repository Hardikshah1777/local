<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade
 *
 * @param int $oldversion
 */

function xmldb_block_temco_dashboard_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023091800.03) {

        // Define field duration to be added to course.
        $table = new xmldb_table('course');
        $field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'visible');

        // Conditionally launch add field duration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2023091800.03, 'temco_dashboard');
    }

    return true;
}