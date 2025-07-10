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

require_once(dirname(__FILE__) . '/definitions.php');
require_once(dirname(__FILE__) . '/classes/changelog.php');

/**
 * Class assign_submission_changes
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_changes extends assign_submission_plugin {

    /**
     * The name of the component where the file submission stores the files.
     * See get_form_elements in mod/assign/submission/file/locallib.php .
     */
    const ASSIGNSUBMISSION_FILE_COMPONENT = 'assignsubmission_file';

    /**
     * Get the name of this plugin.
     * @return string The name of the plugin
     */
    public function get_name() {
        return get_string('pluginname', ASSIGNSUBMISSION_CHANGES_NAME);
    }

    /**
     * Extend the settings menu of an submission for teachers.
     * @param MoodleQuickForm $mform The form which is used to render the settings.
     */
    public function get_settings(MoodleQuickForm $mform) {

        $allow_changelog = get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'allow_changelog');
        $max_filesize_for_diff = get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'max_filesize');

        $default_diff = $this->get_config('diff');

        // Fallback: No settings were defined for this submission --> use system defaults.
        if ($default_diff === false) {
            $default_diff = get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'diff');
        }

        // Display setting Diff.
        if ($allow_changelog && $max_filesize_for_diff > 0) {
            $name = get_string('diff', ASSIGNSUBMISSION_CHANGES_NAME);
            $mform->addElement('checkbox', 'assignsubmission_changes_diff', $name, '', 0);
            $mform->setDefault('assignsubmission_changes_diff', $default_diff);
            $mform->addHelpButton('assignsubmission_changes_diff', 'diff', 'assignsubmission_changes');
            $mform->disabledIf('assignsubmission_changes_diff', 'assignsubmission_changes_enabled', 'notchecked');
        }
    }

    /**
     * Handles the values of the form elements from get_settings(...).
     * @param stdClass $data The form data.
     * @return bool Whether saving was successful. Is always true.
     */
    public function save_settings(stdClass $data) {
        $this->set_config(
            'diff',
            isset($data->assignsubmission_changes_diff) ? $data->assignsubmission_changes_diff : 0);
        return true;
    }

    /**
     * Will be called when a student saves his submission. Creates a backup of the old file to be able to detect updates.
     * @param stdClass $submission The current submission.
     * @param stdClass $data The form data (unused).
     * @return bool Whether saving was successful. Is always true.
     */
    public function save(stdClass $submission, stdClass $data) {
        assign_submission_changes_changelog::backup_submission($submission, $this->assignment->get_context());
        return true;
    }

    /**
     * Prints a string in the grading overview table for teachers and the view of the submission for students.
     * @param stdClass $submission The current submission.
     * @param bool $showviewlink Whether a link to the detailed infos should be rendered.
     * @return string The HTML code containing the summary of the changes.
     */
    public function view_summary(stdClass $submission, & $showviewlink) {

        // Check whether the current user views the grades (no student).
        if ($this->assignment->can_view_grades()) {

            $showviewlink = true; // Generates a link to the view page.

            $grading = $this->get_last_grading($submission->assignment, $submission->userid);

            // If there are no submissions -> no content will be displayed
            // If there is a submission, but no grading --> content will be displayed.
            if ($submission->timemodified > $grading->timemodified) {
                return '<span style="background-color: yellow;">'
                    . get_string('ungraded_changes', ASSIGNSUBMISSION_CHANGES_NAME)
                    . '</span>';
            }

            return get_string('no_ungraded_changes', ASSIGNSUBMISSION_CHANGES_NAME);

        } else {  // User is a student and submits / views his solution.

            $changes = $this->get_changes($submission->id, $submission->userid);

            // Print all new changes.
            if (empty($changes)) {
                return get_string('no_changes', ASSIGNSUBMISSION_CHANGES_NAME);
            } else {
                $showviewlink = count($changes) > 1; // Only show more link if there is more.
                return '<ul>'
                    . $this->map_change_to_string($changes[0])
                    . '</ul>';
            }
        }
    }

    /**
     * Prints a string to the view page.
     * Can be accessed by teachers via the grading overview table.
     * @param stdClass $submission The currently viewed submission.
     * @return string The HTML text to be printed.
     */
    public function view(stdClass $submission) {
        // Check whether the current user views the grades (no student).
        if ($this->assignment->can_view_grades()) {
            return $this->get_full_grading_changelog_history($submission);
        } else { // User is a student and submits / views his solution.
            return $this->get_changelog_history($submission);
        }
    }

    /**
     * Generates a HTML string containing a list with all changes of the passed submission grouped by the grading.
     * All changes before the last grading are separated from the changes after the last grading.
     * @param stdClass $submission The submission whose changelog should be displayed.
     * @return string The HTML string with the changes.
     */
    private function get_full_grading_changelog_history(stdClass $submission) {
        $output = '';

        // Get the last grading.
        $grading = $this->get_last_grading($submission->assignment, $submission->userid);
        if ($grading->timemodified == 0) {
            $output .= get_string('no_last_grading', ASSIGNSUBMISSION_CHANGES_NAME);
        } else {
            $last_grading = $this->time_elapsed_string('@'.$grading->timemodified);
            $output .= get_string('last_grading', ASSIGNSUBMISSION_CHANGES_NAME)
                . userdate($grading->timemodified)
                . ' (' . $last_grading . ')';
        }
        $output .= '<br><br>';

        // Get uploads of the student.
        $changes = $this->get_changes($submission->id, $submission->userid);
        $new_changes = array();
        $old_changes = array();
        foreach ($changes as $change) {
            if ($change->timestamp > $grading->timemodified) {
                $new_changes[] = $change;
            } else {
                $old_changes[] = $change;
            }
        }

        // Print all new changes.
        if (empty($new_changes)) {
            $output .= get_string('no_new_changes', ASSIGNSUBMISSION_CHANGES_NAME) . '<br><br>';
        } else {
            $output .= get_string('new_changes_prefix', ASSIGNSUBMISSION_CHANGES_NAME) . '<ul>';
            $output .= implode('', array_map(array($this, 'map_change_to_string'), $new_changes));
            $output .= '</ul>';
        }

        // Print all old changes.
        if (empty($old_changes)) {
            $output .= get_string('no_old_changes', ASSIGNSUBMISSION_CHANGES_NAME) . '<br><br>';
        } else {
            $output .= get_string('old_changes_prefix', ASSIGNSUBMISSION_CHANGES_NAME) . '<ul>';
            $output .= implode('', array_map(array($this, 'map_change_to_string'), $old_changes));
            $output .= '</ul>';
        }

        return $output;
    }

    /**
     * Generates a HTML string containing a list with all changes of the passed submission.
     * @param stdClass $submission The submission whose changelog should be displayed.
     * @return string The HTML string containing the <ul> with changes or an string indicating that no changes exist.
     */
    private function get_changelog_history(stdClass $submission) {
        $changes = $this->get_changes($submission->id, $submission->userid);

        // Print all changes.
        if (empty($changes)) {
            return get_string('no_changes', ASSIGNSUBMISSION_CHANGES_NAME);
        } else {
            return '<ul>'
                . implode('', array_map(array($this, 'map_change_to_string'), $changes))
                . '</ul>';
        }
    }

    /**
     * Get the last grading for the passed user for the passed assignment.
     * If no grading exists, a dummy entry will be returned.
     * @param int $assignment The assignment to be checked.
     * @param int $user_id The user to be checked.
     * @return stdClass The last grading.
     */
    private function get_last_grading($assignment, $user_id) {
        global $DB;

        $record = $DB->get_record('assign_grades', array(
            'assignment' => $assignment,
            'userid' => $user_id
        ));

        if ($record === false || $record->grade < 0) {
            return (object) array(
                'id' => -1,
                'assignment' => $assignment,
                'userid' => $user_id,
                'timecreated' => 0,
                'timemodified' => 0,
                'grader' => -1,
                'grade' => -1,
                'attemptnumber' => 0
            );
        }

        return $record;
    }

    /**
     * Get all changed of the passed submission from the user.
     * @param int $submission_id The submission which should be checked.
     * @param int $user_id The user who should be checked.
     * @return stdClass[] All changes of the user for the submission.
     */
    private function get_changes($submission_id, $user_id) {
        global $DB;
        return array_values($DB->get_records('assignsubmission_changes', array(
            'submission' => $submission_id,
            'author' => $user_id
        ), 'timestamp DESC'));
    }

    /**
     * Creates a <li> string of the passed change.
     * @param stdClass $change The change which should be printed.
     * @return string A printable <li>...</li> string containing the information of the change.
     */
    private function map_change_to_string($change) {
        return '<li>'
            . userdate($change->timestamp)
            . ' (' . $this->time_elapsed_string('@'.$change->timestamp) . ')<br>'
            . $change->changes
            . $this->generate_edit_link($change)
            . '</li>';
    }

    /**
     * Generates a link to the edit page for this change if the current user is the author of this change (= has edit rights)
     * @param stdClass $change The change for which a edit link should be generated.
     * @return string The HTML code with the edit link or an empty string if the current user can not edit the text.
     */
    private function generate_edit_link($change) {
        global $USER;

        if ($USER->id == $change->author) { // The current user can edit the text.
            $edit_url = new moodle_url('/mod/assign/submission/changes/edit.php', array('id' => $change->id));
            return ' <a href="' . $edit_url . '">'
                . '[' . get_string('edit_form_link', ASSIGNSUBMISSION_CHANGES_NAME) . ']'
                . '</a>';
        }

        return ''; // The current user can not edit the text --> generate no link.
    }

    /**
     * Converts the passed date in a time-ago-string.
     * Taken from https://stackoverflow.com/a/18602474 .
     * @param string $datetime The date which should be converted.
     * @param bool $full Whether the result should be exact or not.
     * @return string The time-ago-string.
     */
    private function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    /**
     * This plugin does not allow to submit anything.
     * @return boolean
     */
    public function allow_submissions() {
        return false;
    }

    /**
     * Automatically enable or disable this plugin based on the configuration
     * @return bool
     */
    public function is_enabled() {
        return parent::is_enabled()

            // Admin enabled the functionality it globally.
            && get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'allow_changelog');

    }

    /**
     * Automatically hide the setting for the submission plugin if the admin has disabled the functionality.
     * @return bool
     */
    public function is_configurable() {
        return get_config(ASSIGNSUBMISSION_CHANGES_NAME, 'allow_changelog');
    }

    /**
     * Will be called as soon as an assignment becomes deleted. Removes all stored data from the database.
     * @return bool Whether the deletion was successful. Is always true.
     */
    public function delete_instance() {
        global $DB;

        // Get all submissions for this assignment.
        $submissions = $DB->get_records('assign_submission', array(
            'assignment' => $this->assignment->get_instance()->id
        ));

        // Delete the changelog of all submissions.
        foreach ($submissions as $submission) {
            // Delete all created changelogs in this assignment.
            $DB->delete_records('assignsubmission_changes', array(
                'submission' => $submission->id
            ));
        }

        return parent::delete_instance();
    }
}
