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

require_once(dirname(__FILE__) . '/../../../../../local/changeloglib/classes/backup_lib.php');
require_once(dirname(__FILE__) . '/../../../../../local/changeloglib/classes/diff_detector.php');
require_once(dirname(__FILE__) . '/../../../../../local/changeloglib/classes/pdftotext.php');
require_once(dirname(__FILE__) . '/../../../../../local/changeloglib/classes/update_detector.php');

/**
 * Wrapper to access changelog functions.
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_changes_changelog {

    /**
     * Provides an update detector for the passed submission file data.
     * Wrapper around changeloglib plugin to be used for submissions.
     * @param stored_file[] $files The files of which a predecessor should be found.
     * @param int $user_id The user ID whoes submissions should be checked.
     * @param int $context_id The context of the submission.
     * @return local_changeloglib_update_detector The update detector.
     */
    public static function get_update_detector($files, $user_id, $context_id) {

        $new_files = array();
        foreach ($files as $file) {
            array_push($new_files, new local_changeloglib_new_file_wrapper($file, array()));
        }
        $context = $context_id;
        $scope = $user_id;
        $further_candidates = array();

        $detector = new local_changeloglib_update_detector($new_files, $context, $scope, $further_candidates);
        $detector->set_ensure_mime_type(false);
        // Get the predecessor even if it is completely different. This is needed to have a full changelog.
        $detector->set_min_similarity(0);

        return $detector;
    }

    /**
     * Creates a backup of the passed course module.
     * Wrapper around changeloglib plugin to be used for course modules.
     * @param stdClass $submission The submission which should be backuped. Must contain id and userid.
     * @param stdClass $context The context of this submission. Must contain an ID.
     *                          Under this context the file will be taken and stored.
     */
    public static function backup_submission($submission, $context) {

        // Get information to access the submission and create a copy of it.
        $data = array(); // Not needed.
        $context_id_from = $context->id;
        $component_from = 'assignsubmission_file';
        $filearea_from = 'submission_files';
        $itemid_from = $submission->id;
        $context_id_to = $context->id;
        $scope_id = $submission->userid;

        // Backup this course module using the changeloglib plugin.
        local_changeloglib_backup_lib::backup($data,
            $context_id_from, $component_from, $filearea_from, $itemid_from,
            $context_id_to, $scope_id);
    }

    /**
     * Wrapper around locallib delete method be be accessed via this interface.
     * This method will be called to delete old submissions when new files are saved.
     * @param int $context The context of the submission.
     * @param int $user_id The user whose backups in the above context should be deleted.
     */
    public static function delete_previous_backups($context, $user_id) {
        return local_changeloglib_backup_lib::clean_up_selected($context, $user_id);
    }
}
