<?php


defined('MOODLE_INTERNAL') || die();

function xmldb_local_newsletter_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022113001) {

        // Define field enddate to be added to local_newsletter.
        $table = new xmldb_table('local_newsletter');
        $field = new xmldb_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timecreated');

        // Conditionally launch add field enddate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Newsletter savepoint reached.
        upgrade_plugin_savepoint(true, 2022113001, 'local', 'newsletter');
    }

    return true;
}