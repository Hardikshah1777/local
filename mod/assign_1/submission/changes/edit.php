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

require_once(dirname(__FILE__) . '/../../../../config.php');

// Include function library.
require_once(dirname(__FILE__) . '/definitions.php');
require_once(dirname(__FILE__) . '/classes/edit_form.php');

// Globals.
global $DB, $CFG, $OUTPUT, $USER, $SITE, $PAGE;

// Check authorisation.
$change_id = required_param('id', PARAM_INT);
$change = $DB->get_record('assignsubmission_changes', array('id' => $change_id), '*', MUST_EXIST);
if ($USER->id != $change->author) {
    $home = new moodle_url('/');
    redirect($home, 'Only the author of a change can edit the message.', 5);
}

// Get corresponding data and set the context.
$submission = $DB->get_record('assign_submission', array('id' => $change->submission), '*', MUST_EXIST);
$assignment = $DB->get_record('assign', array('id' => $submission->assignment), '*', MUST_EXIST);
$module = $DB->get_record('modules', array('name' => 'assign'), 'id', MUST_EXIST);
$cm = $DB->get_record('course_modules', array(
    'module' => $module->id,
    'course' => $assignment->course,
    'instance' => $assignment->id
), '*', MUST_EXIST);
$PAGE->set_context(context_course::instance($assignment->course)); // Needed for a correct output handling.
require_login();

// Generate form.
$form = new assign_submission_changes_edit_form(null, array(
    'change' => $change,
    'submission' => $submission,
    'assignment' => $assignment,
    'cm' => $cm
));

$assign_url = new moodle_url('/mod/assign/view.php', array('id' => $cm->id));
if ($form->is_cancelled()) {
    redirect($assign_url); // Cancel --> redirect to the submission without saving.
}
// Evaluate form data if something was submitted already.
$data = $form->get_data();
if ($data) {
    $DB->update_record('assignsubmission_changes', (object) array(
        'id' => $change_id,
        'changes' => $data->changes['text']
    ));
    redirect($assign_url); // After changing the text --> redirect to the submission.
}

// Display form.
$PAGE->set_url("/mod/assign/submission/changes/edit.php", array('id' => $change_id));
$PAGE->set_title('Assign Submission Changes - Edit');
$PAGE->set_heading('Assign Submission Changes - Edit');
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
