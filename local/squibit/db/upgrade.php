<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_squibit_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023081400.01) {

        // Define table local_squibit_users to be created.
        $table = new xmldb_table('local_squibit_users');

        // Adding fields to table local_squibit_users.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roleid', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_squibit_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_squibit_users.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_squibit_log to be created.
        $table = new xmldb_table('local_squibit_log');

        // Adding fields to table local_squibit_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('code', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
        $table->add_field('parameter', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_squibit_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_squibit_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.01, 'local', 'squibit');
    }

    if ($oldversion < 2023081400.02) {

        // Define table local_squibit_course to be created.
        $table = new xmldb_table('local_squibit_course');

        // Adding fields to table local_squibit_course.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('teacher', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table local_squibit_course.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_squibit_course.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.02, 'local', 'squibit');
    }

    if ($oldversion < 2023081400.03) {

        // Define field course to be added to local_squibit_users.
        $table = new xmldb_table('local_squibit_users');
        $field = new xmldb_field('course', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'roleid');

        // Conditionally launch add field course.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.03, 'local', 'squibit');
    }

    if ($oldversion < 2023081400.04) {
        // Define key uniquserid (unique) to be added to local_squibit_users.
        $table = new xmldb_table('local_squibit_users');
        $key = new xmldb_key('uniquserid', XMLDB_KEY_UNIQUE, ['userid']);

        // Launch add key uniquserid.
        $dbman->add_key($table, $key);

        // Define key uniqcourseid (unique) to be added to local_squibit_course.
        $table = new xmldb_table('local_squibit_course');
        $key = new xmldb_key('uniqcourseid', XMLDB_KEY_UNIQUE, ['courseid']);

        // Launch add key uniqcourseid.
        $dbman->add_key($table, $key);

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.04, 'local', 'squibit');
    }

    if ($oldversion < 2023081400.09) {

        // Define field created to be added to local_squibit_course.
        $table = new xmldb_table('local_squibit_course');
        $field = new xmldb_field('created', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'status');

        // Conditionally launch add field created.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.09, 'local', 'squibit');
    }

    if ($oldversion < 2023081400.10) {

        // Define field created to be added to local_squibit_users.
        $table = new xmldb_table('local_squibit_users');
        $field = new xmldb_field('created', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'status');

        // Conditionally launch add field created.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.10, 'local', 'squibit');
    }

    if ($oldversion < 2023081400.12) {

        // Define field courseid to be added to local_squibit_log.
        $table = new xmldb_table('local_squibit_log');
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'userid');

        // Conditionally launch add field courseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.12, 'local', 'squibit');
    }

    if ($oldversion < 2023081400.14) {
        $category = $DB->get_record_sql('SELECT * FROM {customfield_category} WHERE id > 0 LIMIT 1');
        if (!empty($category)) {
            $catid = $category->id;
        } else {
            $name = 'Squibit';
            $data = new \stdClass();
            $data->name = $name;
            $data->component = 'core_course';
            $data->area = 'course';
            $data->contextid = context_system::instance()->id;
            $data->itemid = 0;
            $data->sortorder = 0;
            $data->timecreated = $data->timemodified = time();

            $cat = \core_customfield\category_controller::create(0, $data);
            $cat->save();
            $catid = $DB->get_field('customfield_category', 'id', ['name' => $name]);
        }

        $record = new stdClass();
        $record->name = get_string(\local_squibit\utility::COURSEENABLE,'local_squibit');
        $record->shortname = \local_squibit\utility::COURSEENABLE;
        $record->type = 'checkbox';
        $record->description = '';
        $record->descriptionformat = 1;
        $record->timecreated = time();
        $record->timemodified = time();
        $record->categoryid = $catid;
        $customdata = [
                'required' => 0,
                'uniquevalues' => 0,
                'checkbydefault' => 0,
                'locked' => 0,
                'visibility' => 2,
        ];
        $record->configdata = $customdata;

        $field = \core_customfield\field_controller::create(0, $record);
        \core_customfield\api::save_field_configuration($field, $record);

        // Squibit savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400.14, 'local', 'squibit');
    }


    return true;
}

