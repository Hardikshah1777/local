<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade script for the scorm module.
 *
 * @package    mod_scorm
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 */
function xmldb_scorm_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle v3.6.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2018123100) {

        // Remove un-used/large index on element field.
        $table = new xmldb_table('scorm_scoes_track');
        $index = new xmldb_index('element', XMLDB_INDEX_UNIQUE, ['element']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Scorm savepoint reached.
        upgrade_mod_savepoint(true, 2018123100, 'scorm');
    }

    // Automatically generated Moodle v3.7.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.8.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2021052501) {
        $table = new xmldb_table('scorm');
        $field = new xmldb_field('displayactivityname');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2021052501, 'scorm');
    }

    // Automatically generated Moodle v4.0.0 release upgrade line.
    // Put any upgrade step following this.
    if ($oldversion < 2022041900.01) {

        // Define field reportstatus to be added to scorm.
        $table = new xmldb_table('scorm');
        $field = new xmldb_field('reportstatus', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'autocommit');

        // Conditionally launch add field reportstatus.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Scorm savepoint reached.
        upgrade_mod_savepoint(true, 2022041900.01, 'scorm');
    }
    if ($oldversion < 2022041900.02) {

        // Define field idnumber to be added to scorm.
        $table = new xmldb_table('scorm');
        $field = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'reportstatus');

        // Conditionally launch add field idnumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Scorm savepoint reached.
        upgrade_mod_savepoint(true, 2022041900.02, 'scorm');
    }

    if ($oldversion < 2022041900.03) {

        // Define field retakeduration to be added to scorm.
        $table = new xmldb_table('scorm');
        $field = new xmldb_field('retakeduration', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'idnumber');

        // Conditionally launch add field retakeduration.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Scorm savepoint reached.
        upgrade_mod_savepoint(true, 2022041900.03, 'scorm');
    }


    return true;
}
