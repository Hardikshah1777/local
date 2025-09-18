<?php

function xmldb_report_kln_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025091500.01) {

        // Define table report_kln_course_timespent to be created.
        $table = new xmldb_table('report_kln_course_timespent');

        // Adding fields to table report_kln_course_timespent.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timespent', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_kln_course_timespent.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for report_kln_course_timespent.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table report_kln_user_login to be created.
        $table = new xmldb_table('report_kln_user_login');

        // Adding fields to table report_kln_user_login.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('logincount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table report_kln_user_login.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for report_kln_user_login.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Kln savepoint reached.
        upgrade_plugin_savepoint(true, 2025091500.01, 'report', 'kln');
    }

    if ($oldversion < 2025091500.07) {

        $frequency = HOURSECS / 2;
        $logs = $DB->get_recordset_select('logstore_standard_log', '', [], 'id ASC');

        $daymidtimes = [];
        foreach ($logs as $log) {
            if ($log->courseid == SITEID || $log->contextlevel < CONTEXT_COURSE) {
                continue;
            }

            $userid     = $log->userid;
            $courseid   = $log->courseid;
            $midnight   = strtotime('midnight', $log->timecreated);

            if (!isset($daymidtimes[$midnight][$courseid][$userid]) &&
                $log->contextlevel == CONTEXT_COURSE &&
                strpos($log->eventname, '\core\event\course_viewed') !== false
            ) {
                $daymidtimes[$midnight][$courseid][$userid] = (object)[
                    'userid'  => $userid,
                    'spendtime'  => 0,
                    'validity' => $log->timecreated,
                ];
            }

            $courseuser = $daymidtimes[$midnight][$courseid][$userid];
            if ($log->contextlevel == CONTEXT_COURSE && $courseuser->validity + $frequency > $log->timecreated) {
                $courseuser->spendtime += ($log->timecreated - $courseuser->validity);
            }
            if (strpos($log->eventname, '\core\event\course_viewed') !== false) {
                $courseuser->validity = $log->timecreated;
            }
        }
        $logs->close();

        foreach ($daymidtimes as $daywisemidtime => $courses) {
            foreach ($courses as $courseid => $users) {
                foreach ($users as $userobj) {
                    if (!empty($userobj->userid) && !empty($courseid) && !empty($userobj->spendtime)) {
                        \report_kln\util::handle_user_timetrack($userobj->userid, $courseid, $userobj->spendtime, $daywisemidtime);
                    }
                }
            }
        }

        $select = " action = 'loggedin' AND objectid > 1";
        $params = [];
        $loginevents = $DB->get_records_select('logstore_standard_log', $select, $params);

        foreach ($loginevents as $loginevent) {
            $currentuser = core_user::get_user($loginevent->objectid);
            \report_kln\util::handle_user_login($currentuser, $loginevent->timecreated);
        }

        upgrade_plugin_savepoint(true, 2025091500.07, 'report', 'kln');
    }

    return true;
}
