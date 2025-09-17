<?php
defined( 'MOODLE_INTERNAL' ) || die();

function xmldb_local_test1_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023112700) {

        // Define table local_test1_mail_log to be created.
        $table = new xmldb_table( 'local_test1_mail_log');

        // Adding fields to table local_test1_mail_log.
        $table->add_field( 'id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null );
        $table->add_field( 'type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null );
        $table->add_field( 'mailer', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null );
        $table->add_field( 'userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null );
        $table->add_field( 'subject', XMLDB_TYPE_CHAR, '255', null, null, null, null );
        $table->add_field( 'body', XMLDB_TYPE_CHAR, '255', null, null, null, null );
        $table->add_field( 'resend', XMLDB_TYPE_INTEGER, '10', null, null, null, 0);
        $table->add_field( 'sendtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null );
        $table->add_field( 'resendtime', XMLDB_TYPE_INTEGER, '10', null, null, null, '0' );

        // Adding keys to table local_test1_mail_log.
        $table->add_key( 'primary', XMLDB_KEY_PRIMARY, ['id'] );

        // Conditionally launch create table for local_test1_mail_log.
        if (!$dbman->table_exists( $table )) {
            $dbman->create_table( $table );
        }

        // Test1 savepoint reached.
        upgrade_plugin_savepoint( true, 2023112700, 'local', 'test1' );
    }

    if ($oldversion < 2023112700.01) {

        // Define table courseuserinvoice to be created.
        $table = new xmldb_table('courseuserinvoice');

        // Adding fields to table courseuserinvoice.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('invoice', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table courseuserinvoice.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for courseuserinvoice.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Test1 savepoint reached.
        upgrade_plugin_savepoint( true, 2023112700.01, 'local', 'test1' );
        //upgrade_main_savepoint(true, 2022041903.00);
    }

    return true;
}