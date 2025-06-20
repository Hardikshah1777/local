<?php


function xmldb_evaluation_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022012703) {

        // Define table evaluation_level to be created.
        $table = new xmldb_table('evaluation_level');

        // Adding fields to table evaluation_level.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, null, null, null);

        // Adding keys to table evaluation_level.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for evaluation_level.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Evaluation savepoint reached.


        // Define field eid to be added to evaluation_level.
        $table = new xmldb_table('evaluation_level');
        $field = new xmldb_field('eid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'id');

        // Conditionally launch add field eid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012703, 'evaluation');
    }

    if ($oldversion < 2022012704) {

        // Define table evaluation_section to be created.
        $table = new xmldb_table('evaluation_section');

        // Adding fields to table evaluation_section.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table evaluation_section.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for evaluation_section.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012704, 'evaluation');
    }

    if ($oldversion < 2022012705) {

        // Define table evaluation_skill to be created.
        $table = new xmldb_table('evaluation_skill');

        // Adding fields to table evaluation_skill.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('sid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table evaluation_skill.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for evaluation_skill.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012705, 'evaluation');
    }

    if ($oldversion < 2022012706) {

        // Define table evaluation_user_skill_level to be created.
        $table = new xmldb_table('evaluation_user_skill_level');

        // Adding fields to table evaluation_user_skill_level.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('skillid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('levelid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table evaluation_user_skill_level.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for evaluation_user_skill_level.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012706, 'evaluation');
    }

    if ($oldversion < 2022012707) {

        // Define field intro to be added to evaluation.
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('intro', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'name');

        // Conditionally launch add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('introformat', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'intro');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012707, 'evaluation');
    }

    if ($oldversion < 2022012708) {

        // Define field agree to be added to evaluation_user.
        $table = new xmldb_table('evaluation_user');
        $field = new xmldb_field('agree', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'attempt');

        // Conditionally launch add field agree.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012708, 'evaluation');
    }

    if ($oldversion < 2022012712) {

        // Changing type of field pass on table evaluation_user to text.
        $table = new xmldb_table('evaluation_user');
        $field = new xmldb_field('pass', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'evaluationid');

        // Launch change of type for field pass.
        $dbman->change_field_type($table, $field);

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012712, 'evaluation');
    }

    if ($oldversion < 2022012713) {

        // Changing type of field pass on table evaluation_user to char.
        $table = new xmldb_table('evaluation_user');
        $field = new xmldb_field('pass', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'evaluationid');

        // Launch change of type for field pass.
        $dbman->change_field_type($table, $field);

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012713, 'evaluation');
    }


    if ($oldversion < 2022012716) {

        // Define field grade to be added to evaluation_level.
        $table = new xmldb_table('evaluation_level');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field grade.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012716, 'evaluation');
    }

    if ($oldversion < 2022012718) {

        // Define field validation to be added to evaluation_skill.
        $table = new xmldb_table('evaluation_skill');
        $field = new xmldb_field('validation', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'timemodified');

        // Conditionally launch add field validation.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012718, 'evaluation');
    }
    if ($oldversion < 2022012719) {

        // Changing type of field validation on table evaluation_skill to int.
        $table = new xmldb_table('evaluation_skill');
        $field = new xmldb_field('validation', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Launch change of type for field validation.
        $dbman->change_field_type($table, $field);

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012719, 'evaluation');
    }

    if ($oldversion < 2022012720) {

        // Define field deleted to be added to evaluation_skill.
        $table = new xmldb_table('evaluation_skill');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'validation');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012720, 'evaluation');
    }
    if ($oldversion < 2022012721) {

        // Define field deleted to be added to evaluation_section.
        $table = new xmldb_table('evaluation_section');
        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'saferskill');

        // Conditionally launch add field deleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012721, 'evaluation');
    }

    if ($oldversion < 2022012722) {

        // Define field sortorder to be added to evaluation_section.
        $table = new xmldb_table('evaluation_section');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field sortorder.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012722, 'evaluation');
    }

    if ($oldversion < 2022012724) {

        // Define table evaluaton_userinfo to be created.
        $table = new xmldb_table('evaluaton_userinfo');

        // Adding fields to table evaluaton_userinfo.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('evaluationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('infofiled', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('infovalue', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table evaluaton_userinfo.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fkevaluationid', XMLDB_KEY_FOREIGN, ['evaluationid'], 'evaluation', ['id']);

        // Conditionally launch create table for evaluaton_userinfo.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012724, 'evaluation');
    }

    if ($oldversion < 2022012725) {

        // Define table evaluation_userfields_info to be created.
        $table = new xmldb_table('evaluation_userfields_info');

        // Adding fields to table evaluation_userfields_info.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('evaluationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userfieldname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('userfieldvalue', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table evaluation_userfields_info.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fkevaluationid', XMLDB_KEY_FOREIGN, ['evaluationid'], 'evaluation', ['id']);

        // Conditionally launch create table for evaluation_userfields_info.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012725, 'evaluation');
    }

    if ($oldversion < 2022012726) {

        // Define field userfieldid to be added to evaluation_userfields_info.
        $table = new xmldb_table('evaluation_userfields_info');
        $field = new xmldb_field('userfieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'evaluationid');

        // Conditionally launch add field userfieldid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012726, 'evaluation');
    }

    if ($oldversion < 2022012727) {

        // Define field evaluationuserid to be added to evaluation_userfields_info.
        $table = new xmldb_table('evaluation_userfields_info');
        $field = new xmldb_field('evaluationuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        // Conditionally launch add field evaluationuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('evaluation_userfields_info');
        $key = new xmldb_key('fkevaluationuserid', XMLDB_KEY_FOREIGN, ['evaluationuserid'], 'evaluation_user', ['id']);

        $dbman->add_key($table, $key);

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012727, 'evaluation');
    }

    if ($oldversion < 2022012728) {

        // Define key fkuserfieldid (foreign) to be added to evaluation_userfields_info.
        $table = new xmldb_table('evaluation_userfields_info');
        $key = new xmldb_key('fkuserfieldid', XMLDB_KEY_FOREIGN, ['userfieldid'], 'evaluation_userinfo', ['id']);

        // Launch add key fkuserfieldid.
        $dbman->add_key($table, $key);

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012728, 'evaluation');
    }

    if ($oldversion < 2022012730) {

        // Define field comment to be added to evaluation_user_skill_level.
        $table = new xmldb_table('evaluation_user_skill_level');
        $field = new xmldb_field('comment', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'timemodified');

        // Conditionally launch add field comment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, 2022012730, 'evaluation');
    }


    return true;
}