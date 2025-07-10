<?php
// This file is part of AssignSubmission_Changes plugin for Moodle - http://moodle.org/
//
// AssignSubmission_Changes is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// AssignSubmission_Changes is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with AssignSubmission_Changes.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Assign Submission Changes
 *
 * @package   assignsubmission_changes
 * @author    Hendrik Wuerz <hendrikmartin.wuerz@stud.tu-darmstadt.de>
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/../definitions.php');
require_once(dirname(__FILE__) . '/changelog.php');

/**
 * Class assign_submission_changes_observer
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_changes_observer {

    /**
     * Detect changes in the updated submission.
     * @param \core\event\base $event The update event of the submission.
     */
    public static function submission_updated(\core\event\base $event) {

        // Check whether the admin has disabled the changelog.
        // This check is required because the function might be enabled while creating the submission, but is
        // disabled now.
        $admin_allow_changelog = get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'allow_changelog');
        if (!$admin_allow_changelog) {
            return;
        }

        $user_id = $event->relateduserid;
        $context_id = $event->contextid;
        $submission_id = $event->other['submissionid'];
        $assignment = self::get_assignment($submission_id);

        // Get the submitted files. For these files a predecessor should be found.
        $fs = get_file_storage();
        $area_files = $fs->get_area_files(
            $context_id,
            'assignsubmission_file',
            'submission_files',
            $submission_id,
            'sortorder DESC, id ASC',
            false);

        // A submission can have multiple uploads. Find predecessors for all of them.
        self::generate_changelog($area_files, $user_id, $context_id, $assignment, $submission_id);

        // Delete all previous backup files.
        // Until now only the current upload is relevant as predecessors for further uploads.
        $deleted_backups = assign_submission_changes_changelog::delete_previous_backups($context_id, $user_id);

        // All backups which are not used until now are deleted files --> Add them to the changelog.
        foreach ($deleted_backups as $file) {
            $changelog_entry = $file->get_filename()
                . get_string('was_deleted', ASSIGNSUBMISSION_CHANGES_NAME);
            self::store_changelog($submission_id, $user_id, $changelog_entry);
        }
    }

    /**
     * Checks whether the passed file is an update.
     * Generates a changelog if a predecessor was found and stores it in the database.
     * The changelog includes the differences of the files, if this function is activated.
     * @param stored_file[] $files The files which should be checked as an update.
     * @param int $user_id The user who owns the file and has submitted the assignment.
     * @param int $context_id The context of this submission.
     * @param int $assignment The assignment ID corresponding to this submission.
     * @param int $submission_id The submission under which the file is stored.
     */
    private static function generate_changelog($files, $user_id, $context_id, $assignment, $submission_id) {

        // Setup predecessor detection.
        $update_detector = assign_submission_changes_changelog::get_update_detector($files, $user_id, $context_id);
        $distribution = $update_detector->map_backups();

        // Loop all mappings of new files to backups.
        foreach ($distribution->mappings as $mapping) {

            // The changelog which will be generated for this file.
            $changelog_entry = false;

            // The new file (the successor).
            $file = $mapping->file_wrapper->get_file();

            if ($mapping->predecessor === null) { // No predecessor found --> New file.
                $changelog_entry = $file->get_filename()
                    . get_string('was_added', ASSIGNSUBMISSION_CHANGES_NAME);

            } else if ($mapping->predecessor->get_backup()->get_file() instanceof stored_file
                && $mapping->has_changed()) { // A valid predecessor was found.

                // The stored_file instance of the predecessor.
                $predecessor = $mapping->predecessor->get_backup()->get_file();

                // Add the predecessor filename to the changelog.
                $changelog_entry = $file->get_filename()
                    . get_string('is_an_update', ASSIGNSUBMISSION_CHANGES_NAME)
                    . $predecessor->get_filename();

                // Check whether the diff is enabled for this submission.
                $max_filesize_for_diff = get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'max_filesize');
                if ($max_filesize_for_diff > 0 // Only for performance --> Avoid the next checks.
                    && self::get_config($assignment, 'diff') == 1 // Diff must be enabled for this assignment.
                    && $predecessor->get_filesize() <= $max_filesize_for_diff * 1024 * 1024
                    && $file->get_filesize() <= $max_filesize_for_diff * 1024 * 1024
                    && local_changeloglib_pdftotext::is_installed()
                    && local_changeloglib_diff_detector::is_command_line_diff_installed()) {

                    $diff = self::generate_diff($predecessor, $file);

                    if ($diff !== false) { // After diff generation the predecessor was not rejected.
                        $changelog_entry .= $diff;

                    } else { // There are to many diffs. The predecessor is not an update.
                        $changelog_entry = $file->get_filename()
                            . get_string('replaces', ASSIGNSUBMISSION_CHANGES_NAME)
                            . $predecessor->get_filename();
                    }
                }
            }

            // This backup was used --> Do not use it again for another file.
            $mapping->delete_found_predecessor();

            // Insert the changelog in the database if it was generated.
            if ($changelog_entry !== false) {
                self::store_changelog($submission_id, $user_id, $changelog_entry);
            }
        }
    }

    /**
     * Stores the passed changelog in the database.
     * @param int $submission_id The ID of the submission.
     * @param int $user_id The ID of the user who performed the update.
     * @param string $changelog_entry The changelog string.
     */
    private static function store_changelog($submission_id, $user_id, $changelog_entry) {
        global $DB;
        $DB->insert_record('assignsubmission_changes', (object) array(
            'submission' => $submission_id,
            'author' => $user_id,
            'changes' => $changelog_entry,
            'timestamp' => time()
        ));

    }

    /**
     * Detects differences between the predecessor and the file.
     * @param stored_file $predecessor The predecessor of the file.
     * @param stored_file $file The current version of the submission.
     * @return string The detected differences between the predecessor and the file.
     */
    private static function generate_diff($predecessor, $file) {

        $diff_output = '';
        $predecessor_txt_file = local_changeloglib_pdftotext::convert_to_txt($predecessor);
        $file_txt_file = local_changeloglib_pdftotext::convert_to_txt($file);

        // Only continue if valid text files could be generated.
        if ($predecessor_txt_file !== false && $file_txt_file !== false) {
            $diff_detector = new local_changeloglib_diff_detector($predecessor_txt_file, $file_txt_file);

            if ($diff_detector->has_acceptable_amount_of_changes()) {
                $diff = $diff_detector->get_info();
                if (strlen($diff) > 50) {
                    $changed_pages = count(explode(', ', $diff));
                    $diff_output .= '<br>' . get_string('long_diff', ASSIGNSUBMISSION_CHANGES_NAME, $changed_pages);
                } else {
                    $diff_output .= '<br>'
                        . get_string('diff_prefix', ASSIGNSUBMISSION_CHANGES_NAME)
                        . $diff;
                }
            } else {
                return false;
            }
        }

        // Delete auto generated text files.
        if ($predecessor_txt_file) {
            unlink($predecessor_txt_file);
        }
        if ($file_txt_file) {
            unlink($file_txt_file);
        }

        return $diff_output;
    }

    /**
     * Get the ID of the assignment of the passed submission ID.
     * @param int $submission The ID of the submission.
     * @return int The ID of the corresponding assignment.
     */
    private static function get_assignment($submission) {
        global $DB;

        return $DB->get_record('assign_submission', array(
            'id' => $submission
        ), 'assignment')->assignment;
    }

    /**
     * Get the config with the passed name for the passed assignment.
     * In the assignment settings each sub plugin can be configured (see locallib.php get_settings(...)).
     * @param int $assignment The ID of the assignment whose settings should be fetched.
     * @param string $name The name of the config property which should be fetched.
     *              Must be identically to the name in locallib.php get_settings(...).
     * @return string The setting stored for the assignment.
     */
    private static function get_config($assignment, $name) {
        global $DB;

        return $DB->get_record('assign_plugin_config', array(
            'assignment' => $assignment,
            'plugin' => 'changes',
            'subtype' => 'assignsubmission',
            'name' => $name
        ), 'value')->value;
    }

}