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
 * mod_behaviour Data provider.
 *
 * @package    mod_behaviour
 * @copyright  2018 Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_behaviour\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\{writer, transform, helper, contextlist, approved_contextlist, approved_userlist, userlist};
use stdClass;

/**
 * Data provider for mod_behaviour.
 *
 * @copyright 2018 Cameron Ball <cameron@cameron1729.xyz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\metadata\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'behaviour_log',
            [
                'sessionid' => 'privacy:metadata:sessionid',
                'studentid' => 'privacy:metadata:studentid',
                'statusid' => 'privacy:metadata:statusid',
                'statusset' => 'privacy:metadata:statusset',
                'timetaken' => 'privacy:metadata:timetaken',
                'takenby' => 'privacy:metadata:takenby',
                'remarks' => 'privacy:metadata:remarks',
                'ipaddress' => 'privacy:metadata:ipaddress'
            ],
            'privacy:metadata:behaviourlog'
        );

        $collection->add_database_table(
            'behaviour_sessions',
            [
                'groupid' => 'privacy:metadata:groupid',
                'sessdate' => 'privacy:metadata:sessdate',
                'duration' => 'privacy:metadata:duration',
                'lasttaken' => 'privacy:metadata:lasttaken',
                'lasttakenby' => 'privacy:metadata:lasttakenby',
                'timemodified' => 'privacy:metadata:timemodified'
            ],
            'privacy:metadata:behavioursessions'
        );

        $collection->add_database_table(
            'behaviour_warning_done',
            [
                'notifyid' => 'privacy:metadata:notifyid',
                'userid' => 'privacy:metadata:userid',
                'timesent' => 'privacy:metadata:timesent'
            ],
            'privacy:metadata:behaviourwarningdone'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of behaviour, that is any behaviour where a student has had their
     * behaviour taken or has taken behaviour for someone else.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        return (new contextlist)->add_from_sql(
            "SELECT ctx.id
                 FROM {course_modules} cm
                 JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                 JOIN {behaviour} a ON cm.instance = a.id
                 JOIN {behaviour_sessions} asess ON asess.behaviourid = a.id
                 JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                 JOIN {behaviour_log} al ON asess.id = al.sessionid AND (al.studentid = :userid OR al.takenby = :takenbyid)",
            [
                'modulename' => 'behaviour',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
                'takenbyid' => $userid
            ]
        );
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $sql = "SELECT al.studentid
                 FROM {course_modules} cm
                 JOIN {modules} m ON cm.module = m.id AND m.name = 'behaviour'
                 JOIN {behaviour} a ON cm.instance = a.id
                 JOIN {behaviour_sessions} asess ON asess.behaviourid = a.id
                 JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                 JOIN {behaviour_log} al ON asess.id = al.sessionid
                 WHERE ctx.id = :contextid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'contextid'    => $context->id,
        ];

        $userlist->add_from_sql('studentid', $sql, $params);

        $sql = "SELECT al.takenby
                 FROM {course_modules} cm
                 JOIN {modules} m ON cm.module = m.id AND m.name = 'behaviour'
                 JOIN {behaviour} a ON cm.instance = a.id
                 JOIN {behaviour_sessions} asess ON asess.behaviourid = a.id
                 JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                 JOIN {behaviour_log} al ON asess.id = al.sessionid
                 WHERE ctx.id = :contextid";

        $userlist->add_from_sql('takenby', $sql, $params);

    }
    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if (!$context instanceof context_module) {
            return;
        }

        if (!$cm = get_coursemodule_from_id('behaviour', $context->instanceid)) {
            return;
        }

        // Delete all information recorded against sessions associated with this module.
        $DB->delete_records_select(
            'behaviour_log',
            "sessionid IN (SELECT id FROM {behaviour_sessions} WHERE behaviourid = :behaviourid",
            [
                'behaviourid' => $cm->instance
            ]
        );

        // Delete all completed warnings associated with a warning associated with this module.
        $DB->delete_records_select(
            'behaviour_warning_done',
            "notifyid IN (SELECT id from {behaviour_warning} WHERE idnumber = :behaviourid)",
            ['behaviourid' => $cm->instance]
        );
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            if (!$cm = get_coursemodule_from_id('behaviour', $context->instanceid)) {
                continue;
            }

            $behaviourid = (int)$DB->get_record('behaviour', ['id' => $cm->instance])->id;
            $sessionids = array_keys(
                $DB->get_records('behaviour_sessions', ['behaviourid' => $behaviourid])
            );

            self::delete_user_from_session_behaviour_log($userid, $sessionids);
            self::delete_user_from_sessions($userid, $sessionids);
            self::delete_user_from_behaviour_warnings_log($userid, $behaviourid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        // Prepare SQL to gather all completed IDs.
        $userids = $userlist->get_userids();
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete records where user was marked as attending.
        $DB->delete_records_select(
            'behaviour_log',
            "studentid $insql",
            $inparams
        );

        // Get list of warning_done records and check if this user is set in thirdpartyusers.
        foreach ($userids as $userid) {
            $sql = 'SELECT DISTINCT w.*
                FROM {behaviour_warning} w
                JOIN {behaviour_warning_done} d ON d.notifyid = w.id AND d.userid = ?';
            $warnings = $DB->get_records_sql($sql, array($userid));
            if (!empty($warnings)) {
                behaviour_remove_user_from_thirdpartyemails($warnings, $userid);
            }

        }

        $DB->delete_records_select(
            'behaviour_warning_done',
            "userid $insql",
            $inparams
        );

        // Now for teachers remove relation for marking.
        $DB->set_field_select(
            'behaviour_log',
            'takenby',
            2,
            "takenby $insql",
            $inparams);

    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $params = [
            'modulename' => 'behaviour',
            'contextlevel' => CONTEXT_MODULE,
            'studentid' => $contextlist->get_user()->id,
            'takenby' => $contextlist->get_user()->id
        ];

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT
                    al.*,
                    asess.id as session,
                    asess.description,
                    ctx.id as contextid,
                    a.name as behaviourname,
                    a.id as behaviourid,
                    statuses.description as statusdesc, statuses.grade as statusgrade
                    FROM {course_modules} cm
                    JOIN {behaviour} a ON cm.instance = a.id
                    JOIN {behaviour_sessions} asess ON asess.behaviourid = a.id
                    JOIN {behaviour_log} al on (al.sessionid = asess.id AND (studentid = :studentid OR al.takenby = :takenby))
                    JOIN {context} ctx ON cm.id = ctx.instanceid
                    JOIN {behaviour_statuses} statuses ON statuses.id = al.statusid
                    WHERE (ctx.id {$contextsql})";

        $behaviours = $DB->get_records_sql($sql, $params + $contextparams);

        self::export_behaviour_logs(
            get_string('behaviourstaken', 'mod_behaviour'),
            array_filter(
                $behaviours,
                function(stdClass $behaviour) use ($contextlist) : bool {
                    return $behaviour->takenby == $contextlist->get_user()->id;
                }
            )
        );

        self::export_behaviour_logs(
            get_string('behaviourslogged', 'mod_behaviour'),
            array_filter(
                $behaviours,
                function(stdClass $behaviour) use ($contextlist) : bool {
                    return $behaviour->studentid == $contextlist->get_user()->id;
                }
            )
        );

        self::export_behaviours(
            $contextlist->get_user(),
            $behaviours,
            self::group_by_property(
                $DB->get_records_sql(
                    "SELECT
                     *,
                     a.id as behaviourid
                      FROM {behaviour_warning_done} awd
                      JOIN {behaviour_warning} aw ON awd.notifyid = aw.id
                      JOIN {behaviour} a on aw.idnumber = a.id
                      WHERE userid = :userid",
                    ['userid' => $contextlist->get_user()->id]
                ),
                'notifyid'
            )
        );
    }

    /**
     * Delete a user from session logs.
     *
     * @param int $userid The id of the user to remove.
     * @param array $sessionids Array of session ids from which to remove the student from the relevant logs.
     */
    private static function delete_user_from_session_behaviour_log(int $userid, array $sessionids) {
        global $DB;

        // Delete records where user was marked as attending.
        list($sessionsql, $sessionparams) = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(
            'behaviour_log',
            "(studentid = :studentid) AND sessionid $sessionsql",
            ['studentid' => $userid] + $sessionparams
        );

        // Get every log record where user took the behaviour.
        $behaviourtakenids = array_keys(
            $DB->get_records_sql(
                "SELECT * from {behaviour_log}
                 WHERE takenby = :takenbyid AND sessionid $sessionsql",
                ['takenbyid' => $userid] + $sessionparams
            )
        );

        if (!$behaviourtakenids) {
            return;
        }

        // Don't delete the record from the log, but update to site admin taking behaviour.
        list($behaviourtakensql, $behaviourtakenparams) = $DB->get_in_or_equal($behaviourtakenids, SQL_PARAMS_NAMED);
        $DB->set_field_select(
            'behaviour_log',
            'takenby',
            2,
            "id $behaviourtakensql",
            $behaviourtakenparams
        );
    }

    /**
     * Delete a user from sessions.
     *
     * Not much user data is stored in a session, but it's possible that a user id is saved
     * in the "lasttakenby" field.
     *
     * @param int $userid The id of the user to remove.
     * @param array $sessionids Array of session ids from which to remove the student.
     */
    private static function delete_user_from_sessions(int $userid, array $sessionids) {
        global $DB;

        // Get all sessions where user was last to mark behaviour.
        list($sessionsql, $sessionparams) = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED);
        $sessionstaken = $DB->get_records_sql(
            "SELECT * from {behaviour_sessions}
            WHERE lasttakenby = :lasttakenbyid AND id $sessionsql",
            ['lasttakenbyid' => $userid] + $sessionparams
        );

        if (!$sessionstaken) {
            return;
        }

        // Don't delete the session, but update last taken by to the site admin.
        list($sessionstakensql, $sessionstakenparams) = $DB->get_in_or_equal(array_keys($sessionstaken), SQL_PARAMS_NAMED);
        $DB->set_field_select(
            'behaviour_sessions',
            'lasttakenby',
            2,
            "id $sessionstakensql",
            $sessionstakenparams
        );
    }

    /**
     * Delete a user from the behaviour waring log.
     *
     * @param int $userid The id of the user to remove.
     * @param int $behaviourid The id of the behaviour instance to remove the relevant warnings from.
     */
    private static function delete_user_from_behaviour_warnings_log(int $userid, int $behaviourid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/behaviour/lib.php');

        // Get all warnings because the user could have their ID listed in the thirdpartyemails column as a comma delimited string.
        $warnings = $DB->get_records(
            'behaviour_warning',
            ['idnumber' => $behaviourid]
        );

        if (!$warnings) {
            return;
        }

        behaviour_remove_user_from_thirdpartyemails($warnings, $userid);

        // Delete any record of the user being notified.
        list($warningssql, $warningsparams) = $DB->get_in_or_equal(array_keys($warnings), SQL_PARAMS_NAMED);
        $DB->delete_records_select(
            'behaviour_warning_done',
            "userid = :userid AND notifyid $warningssql",
            ['userid' => $userid] + $warningsparams
        );
    }

    /**
     * Helper function to group an array of stdClasses by a common property.
     *
     * @param array $classes An array of classes to group.
     * @param string $property A common property to group the classes by.
     */
    private static function group_by_property(array $classes, string $property) : array {
        return array_reduce(
            $classes,
            function (array $classes, stdClass $class) use ($property) : array {
                $classes[$class->{$property}][] = $class;
                return $classes;
            },
            []
        );
    }

    /**
     * Helper function to transform a row from the database in to session data to export.
     *
     * The properties of the "dbrow" are very specific to the result of the SQL from
     * the export_user_data function.
     *
     * @param stdClass $dbrow A row from the database containing session information.
     * @return stdClass The transformed row.
     */
    private static function transform_db_row_to_session_data(stdClass $dbrow) : stdClass {
        return (object) [
            'name' => $dbrow->behaviourname,
            'session' => $dbrow->session,
            'takenbyid' => $dbrow->takenby,
            'studentid' => $dbrow->studentid,
            'status' => $dbrow->statusdesc,
            'grade' => $dbrow->statusgrade,
            'sessiondescription' => $dbrow->description,
            'timetaken' => transform::datetime($dbrow->timetaken),
            'remarks' => $dbrow->remarks,
            'ipaddress' => $dbrow->ipaddress
        ];
    }

    /**
     * Helper function to transform a row from the database in to warning data to export.
     *
     * The properties of the "dbrow" are very specific to the result of the SQL from
     * the export_user_data function.
     *
     * @param stdClass $warning A row from the database containing warning information.
     * @return stdClass The transformed row.
     */
    private static function transform_warning_data(stdClass $warning) : stdClass {
        return (object) [
            'timesent' => transform::datetime($warning->timesent),
            'thirdpartyemails' => $warning->thirdpartyemails,
            'subject' => $warning->emailsubject,
            'body' => $warning->emailcontent
        ];
    }

    /**
     * Helper function to export behaviour logs.
     *
     * The array of "behaviours" is actually the result returned by the SQL in export_user_data.
     * It is more of a list of sessions. Which is why it needs to be grouped by context id.
     *
     * @param string $path The path in the export (relative to the current context).
     * @param array $behaviours Array of behaviours to export the logs for.
     */
    private static function export_behaviour_logs(string $path, array $behaviours) {
        $behavioursbycontextid = self::group_by_property($behaviours, 'contextid');

        foreach ($behavioursbycontextid as $contextid => $sessions) {
            $context = context::instance_by_id($contextid);
            $sessionsbyid = self::group_by_property($sessions, 'sessionid');

            foreach ($sessionsbyid as $sessionid => $sessions) {
                writer::with_context($context)->export_data(
                    [get_string('session', 'behaviour') . ' ' . $sessionid, $path],
                    (object)[array_map([self::class, 'transform_db_row_to_session_data'], $sessions)]
                );
            };
        }
    }

    /**
     * Helper function to export behaviours (and associated warnings for the user).
     *
     * The array of "behaviours" is actually the result returned by the SQL in export_user_data.
     * It is more of a list of sessions. Which is why it needs to be grouped by context id.
     *
     * @param stdClass $user The user to export behaviours for. This is needed to retrieve context data.
     * @param array $behaviours Array of behaviours to export.
     * @param array $warningsmap Mapping between an behaviour id and warnings.
     */
    private static function export_behaviours(stdClass $user, array $behaviours, array $warningsmap) {
        $behavioursbycontextid = self::group_by_property($behaviours, 'contextid');

        foreach ($behavioursbycontextid as $contextid => $behaviour) {
            $context = context::instance_by_id($contextid);

            // It's "safe" to get the behaviourid from the first element in the array - since they're grouped by context.
            // i.e., module context.
            // The reason there can be more than one "behaviour" is that the behaviours array will contain multiple records
            // for the same behaviour instance if there are multiple sessions. It is not the same as a raw record from the
            // behaviours table. See the SQL in export_user_data.
            $warnings = array_map([self::class, 'transform_warning_data'], $warningsmap[$behaviour[0]->behaviourid] ?? []);

            writer::with_context($context)->export_data(
                [],
                (object)array_merge(
                    (array) helper::get_context_data($context, $user),
                    ['warnings' => $warnings]
                )
            );
        }
    }
}
