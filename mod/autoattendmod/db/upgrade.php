<?php  //$Id: upgrade.php,v 1.1.2.2 2006/10/26 17:43:08 stronk7 Exp $

// This file keeps track of upgrades to 
// the autoattendmod module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_autoattendmod_upgrade($oldversion=0)
{

    global $CFG, $THEME, $DB;

    $result = true;
    $dbman = $DB->get_manager();

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    // 2014060500
    if ($oldversion < 2014060500) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('homeroom',  XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2014060800
    if ($oldversion < 2014060800) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('namepattern',  XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'fullname', 'introformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2014061300
    if ($oldversion < 2014061300) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('feedback',  XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'homeroom');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2014120100
    if ($oldversion < 2014120100) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('emailenable',  XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'namepattern');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('allreports',  XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'emailenable');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2016010400
    if ($oldversion < 2016010400) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('emailkey', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'allreports');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('emailuser', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'emailkey');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2016011200
    if ($oldversion < 2016011200) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('backupblock', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'feedback');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('dbversion', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '2', 'backupblock');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2019081900
    if ($oldversion < 2019081900) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('summertime', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'emailuser');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('excelver',   XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'summertime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2023032300
    if ($oldversion < 2023032300) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('localhostname', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'homeroom');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    // 2024011102
    if ($oldversion < 2024011102) {
        $table = new xmldb_table('autoattendmod');
        //
        $field = new xmldb_field('dispinfo', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'localhostname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        //
        $field = new xmldb_field('predisptime', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '5', 'dispinfo');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return $result;
}

