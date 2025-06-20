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
 * Privacy Subsystem implementation for assignsubmission_onlinepoodll.
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_onlinepoodll\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/onlinepoodll/locallib.php');

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \mod_assign\privacy\assign_plugin_request_data;
use \mod_assign\privacy\useridlist;
use assignsubmission_onlinepoodll\constants;

//3.3 user_provider not backported so we use this switch to avoid errors when using same codebase for 3.3 and higher
if (interface_exists('\mod_assign\privacy\assignsubmission_user_provider')) {
    interface the_user_provider extends \mod_assign\privacy\assignsubmission_user_provider{}
} else {
    interface the_user_provider {};
}

/**
 * Privacy Subsystem for assignsubmission_onlinepoodll
 *
 * @copyright  2018 Justin Hunt https://poodll.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class provider implements metadataprovider,
    \mod_assign\privacy\assignsubmission_provider,
    the_user_provider
{

    use \core_privacy\local\legacy_polyfill;
    use \mod_assign\privacy\submission_legacy_polyfill;

    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function _get_metadata($collection) {
        $collection->link_subsystem('core_files', 'privacy:metadata:filepurpose');
        return $collection;
    }

    /**
     * This is covered by mod_assign provider and the query on assign_submissions.
     *
     * @param  int $userid The user ID that we are finding contexts for.
     * @param  contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function _get_context_for_userid_within_submission($userid, $contextlist) {
        // This is already fetched from mod_assign.
    }

    /**
     * This is also covered by the mod_assign provider and it's queries.
     *
     * @param  \mod_assign\privacy\useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function _get_student_user_ids($useridlist) {
        // No need.
    }

    /**
     * If you have tables that contain userids and you can generate entries in your tables without creating an
     * entry in the assign_submission table then please fill in this method.
     *
     * @param  userlist $userlist The userlist object
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        // Not required.
    }

    /**
     * Export all user data for this plugin.
     *
     * @param  submission_request_data $exportdata Data used to determine which context and user to export and other useful
     * information to help with exporting.
     */
    public static function _export_submission_user_data($exportdata) {
        // We currently don't show submissions to teachers when exporting their data.
        $context = $exportdata->get_context();
        if ($exportdata->get_user() != null) {
            return null;
        }
        $user = new \stdClass();
        $assign = $exportdata->get_assign();
        $plugin = $assign->get_plugin_by_type('assignsubmission', 'onlinepoodll');

        $files = $plugin->get_files($exportdata->get_pluginobject(), $user);
        foreach ($files as $file) {
            $userid = $exportdata->get_pluginobject()->userid;
            writer::with_context($exportdata->get_context())->export_file($exportdata->get_subcontext(), $file);

            // Plagiarism data.
            $coursecontext = $context->get_course_context();
            \core_plagiarism\privacy\provider::export_plagiarism_user_data($userid, $context, $exportdata->get_subcontext(), [
                'cmid' => $context->instanceid,
                'course' => $coursecontext->instanceid,
                'userid' => $userid,
                'file' => $file
            ]);
        }
    }

    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     *
     * @param  submission_request_data $requestdata Information useful for deleting user data.
     */
    public static function _delete_submission_for_context($requestdata) {
        global $DB;

        \core_plagiarism\privacy\provider::delete_plagiarism_for_context($requestdata->get_context());

        $fs = get_file_storage();
        $fs->delete_area_files($requestdata->get_context()->id, constants::M_COMPONENT, constants::M_FILEAREA);

        // Delete records from assignsubmission_file table.
        $DB->delete_records(constants::M_TABLE, ['assignment' => $requestdata->get_assign()->get_instance()->id]);
    }



    /**
     * A call to this method should delete user data (where practical) using the userid and submission.
     *
     * @param  submission_request_data $exportdata Details about the user and context to focus the deletion.
     */
    public static function _delete_submission_for_userid($deletedata) {
        global $DB;

        \core_plagiarism\privacy\provider::delete_plagiarism_for_user($deletedata->get_user()->id, $deletedata->get_context());

        $submissionid = $deletedata->get_pluginobject()->id;

        $fs = get_file_storage();
        $fs->delete_area_files($deletedata->get_context()->id, constants::M_COMPONENT, constants::M_FILEAREA,
            $submissionid);

        $DB->delete_records(constants::M_TABLE, ['assignment' => $deletedata->get_assign()->get_instance()->id,
            'submission' => $submissionid]);
    }

    /**
     * Deletes all submissions for the submission ids / userids provided in a context.
     * assign_plugin_request_data contains:
     * - context
     * - assign object
     * - submission ids (pluginids)
     * - user ids
     * @param  assign_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function delete_submissions(assign_plugin_request_data  $deletedata) {
        global $DB;

        \core_plagiarism\privacy\provider::delete_plagiarism_for_users($deletedata->get_userids(), $deletedata->get_context());
        if (empty($deletedata->get_submissionids())) {
            return;
        }

        $fs = get_file_storage();
        list($sql, $params) = $DB->get_in_or_equal($deletedata->get_submissionids(), SQL_PARAMS_NAMED);
        $fs->delete_area_files_select($deletedata->get_context()->id,
            constants::M_COMPONENT, constants::M_FILEAREA, $sql, $params);

        $params['assignid'] = $deletedata->get_assignid();
        $DB->delete_records_select(constants::M_TABLE, "assignment = :assignid AND submission $sql", $params);
    }
}