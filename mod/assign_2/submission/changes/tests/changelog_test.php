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

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

/**
 * Class assign_submission_changes_changelog_test.
 *
 * vendor/bin/phpunit assign_submission_changes_changelog_test mod/assign/submission/changes/tests/changelog_test.php
 *
 * Tests changelog generation.
 *
 * @copyright (c) 2017 Hendrik Wuerz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group assignsubmission_changes
 */
class assign_submission_changes_changelog_test extends advanced_testcase {

    /**
     * @var stdClass The course used for tests.
     */
    private $course;

    /**
     * @var stdClass The student used for tests.
     */
    private $student;

    /**
     * @var testable_assign The assignment used for tests.
     */
    private $assign;

    /**
     * @var mixed The 'file' plugin of submissions.
     */
    private $file_plugin;

    /**
     * @var mixed The 'changes' plugin of submissions (this one).
     */
    private $changes_plugin;

    /**
     * @var stdClass The submission of the user.
     */
    private $submission;

    /**
     * Checks that a changelog will be generated for updates.
     */
    public function test_changelog_generation() {
        global $DB;
        $this->resetAfterTest(true);

        // Set Admin settings.
        set_config('allow_changelog', 1, ASSIGNSUBMISSION_CHANGES_NAME);
        set_config('max_filesize', 1, ASSIGNSUBMISSION_CHANGES_NAME);

        $this->prepare_course(1, 1);

        // Ensure that an empty submission exists.
        $this->assertEquals(1, $DB->count_records('assign_submission')); // The submission was created successfully.
        $this->assertEquals(0, $DB->count_records('assignsubmission_file')); // Nothing was uploaded until now.

        // Upload a file in the submission.
        $this->upload(array('file.pdf'));

        // Check behaviour after initial upload.
        $this->assertEquals(1, $DB->count_records('assignsubmission_file'));  // The file was stored.
        $this->assertEquals(1, $DB->get_record('assignsubmission_file', array())->numfiles); // Exactly one file exists.
        $this->assertEquals(0, $DB->count_records('assignsubmission_changes')); // Initial upload --> No entries.

        // Update the file in the submission.
        $this->upload('file_v2.pdf');

        // Check behaviour after update.
        $this->assertEquals(1, $DB->count_records('assignsubmission_file'));  // The file was stored.
        $this->assertEquals(1, $DB->get_record('assignsubmission_file', array())->numfiles); // Exactly one file exists.
        $this->assertEquals(1, $DB->count_records('assignsubmission_changes')); // File was updates --> Changelog entry.
        $this->assertEquals(1, $DB->get_record('assignsubmission_file', array())->numfiles); // Exactly one file exists.

        // Ensure that the changelog contains the predecessor and the diff.
        $this->assertTrue($this->changelog_contains('file.pdf', '2, 4'));
    }

    /**
     * Test the different settings (admin and assign).
     * Ensures the correct behaviour in changelog and diff generation.
     * @dataProvider settings_testcase
     * @param array $data The settings of the admin and the assignment.
     * @param array $expected The expected behaviour for changelog and diff.
     */
    public function test_settings($data, $expected) {
        $this->resetAfterTest(true);

        // Set Admin settings.
        set_config('allow_changelog', $data['admin_changelog'], ASSIGNSUBMISSION_CHANGES_NAME);
        set_config('max_filesize', $data['admin_diff'], ASSIGNSUBMISSION_CHANGES_NAME);

        // Setup course and assignment.
        $this->prepare_course($data['assign_changelog'], $data['assign_diff']);

        // Upload and update a submission.
        $this->upload('file.pdf'); // Upload a file in the submission.
        $this->upload('file_v2.pdf'); // Update the file in the submission.

        // Ensure that the changelog and diff are correct.
        $this->assertEquals($expected['changelog'], $this->changelog_contains('file.pdf'));
        $this->assertEquals($expected['diff'], $this->changelog_contains('2, 4'));
    }

    /**
     * Data provider for the settings test.
     * @return array The testcases to check the settings.
     */
    public function settings_testcase() {
        $data = [
            [0, 0, 0, 0, false, false],
            [0, 0, 0, 1, false, false],
            [0, 0, 1, 0, false, false],
            [0, 0, 1, 1, false, false],
            [0, 1, 0, 0, false, false],
            [0, 1, 0, 1, false, false],
            [0, 1, 1, 0, false, false],
            [0, 1, 1, 1, false, false],
            [1, 0, 0, 0, false, false],
            [1, 0, 0, 1, false, false],
            [1, 0, 1, 0, true, false],
            [1, 0, 1, 1, true, false],
            [1, 1, 0, 0, false, false],
            [1, 1, 0, 1, false, false],
            [1, 1, 1, 0, true, false],
            [1, 1, 1, 1, true, true],
        ];

        $response = array();
        $index_name_mapping = [
            0 => 'admin_changelog',
            1 => 'admin_diff',
            2 => 'assign_changelog',
            3 => 'assign_diff',
            4 => 'changelog',
            5 => 'diff'
        ];
        foreach ($data as $testcase) {
            $testcase_response = array();
            for ($i = 0; $i < count($testcase); $i++) {
                $testcase_response[$index_name_mapping[$i]] = $testcase[$i];
            }
            $response[] = $testcase_response;
        }

        return $response;
    }

    /**
     * Ensures that the predecessor is given even when the similarity is low.
     * @dataProvider different_files_testcase
     * @param array $data The test data with the filenames.
     */
    public function test_different_files($data) {
        $this->resetAfterTest(true);

        // Set Admin settings.
        set_config('allow_changelog', 1, ASSIGNSUBMISSION_CHANGES_NAME);
        set_config('max_filesize', 1, ASSIGNSUBMISSION_CHANGES_NAME);

        // Setup course and assignment.
        $this->prepare_course(1, 1);

        // Upload and update a submission.
        $this->upload($data[0]); // Upload a file in the submission.
        $this->upload($data[1]); // Update the file in the submission.

        // Ensure that the changelog and diff are correct.
        $this->assertTrue($this->changelog_contains($data[0]));
        // Soft indicator: If a changelog is included, a new line would have been added.
        $this->assertFalse($this->changelog_contains('<br>'));
    }

    /**
     * Data provider for the different file test.
     * @return array The test data.
     */
    public function different_files_testcase() {
        return [
            'Different file types' => [['file.pdf', 'file.txt']],
            'No similarity' => [['file.pdf', 'other.pdf']]
        ];
    }

    /**
     * Test the correct detection of multiple updates at the same time.
     */
    public function test_multiple_files() {
        $this->resetAfterTest(true);

        // Set Admin settings.
        set_config('allow_changelog', 1, ASSIGNSUBMISSION_CHANGES_NAME);
        set_config('max_filesize', 1, ASSIGNSUBMISSION_CHANGES_NAME);

        // Setup course and assignment.
        $this->prepare_course(1, 1);

        // Upload and update a submission.
        $this->upload(array('file.pdf', 'other.pdf'));
        $this->upload(array('file_v2.pdf', 'other_v2.pdf')); // Update the submission.

        // Ensure that the changelog and diff are correct.
        $this->assertTrue($this->changelog_contains('file.pdf', '2, 4'));
        $this->assertTrue($this->changelog_contains('other.pdf', '6'));
    }

    /**
     * Creates a course and student.
     * @param int $changelog Whether the changelog detection should be enabled in the assignment (1) or not (0).
     * @param int $diff Whether the diff detection should be enabled in the assignment (1) or not (0).
     */
    private function prepare_course($changelog, $diff) {

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Enroll a student.
        $this->student = $this->getDataGenerator()->create_user(array('email' => 'student@example.com', 'username' => 'student'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id);
        $this->setUser($this->student);

        // Create assign in course.
        $instance = $this->getDataGenerator()->create_module('assign', array(
            'course' => $this->course->id,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 12,
            'assignsubmission_file_maxsizebytes' => 1000000,
            'assignsubmission_changes_enabled' => $changelog,
            'assignsubmission_changes_diff' => $diff
        ));
        $cm = get_coursemodule_from_instance('assign', $instance->id);
        $context = context_module::instance($cm->id);
        $this->assign = new testable_assign($context, $cm, $this->course);
        $this->file_plugin = $this->assign->get_submission_plugin_by_type('file'); // Get the sub-plugin for files...
        $this->changes_plugin = $this->assign->get_submission_plugin_by_type('changes'); // ... and for changes.

        // Create submission for student.
        $this->submission = $this->assign->get_user_submission($this->student->id, true);
    }

    /**
     * Uploads a new document to the student submission.
     * @param array|string $filenames The filename of the document which should be uploaded.
     */
    private function upload($filenames) {
        if (!is_array($filenames)) {
            $filenames = array($filenames);
        }
        $itemid = file_get_unused_draft_itemid();
        foreach ($filenames as $filename) {
            // Upload is stored in draft area first.
            $this->create_file(
                context_user::instance($this->student->id)->id,
                $filename,
                'user', 'draft', $itemid
            );
        }
        // Inform the sub-plugins to handle the upload.
        $this->changes_plugin->save($this->submission, new stdClass());
        $this->file_plugin->save($this->submission, (object)['files_filemanager' => $itemid]);
    }


    /**
     * Checks whether a changelog contains a substring. Use this for predecessor and diff detection.
     * @param string $needle The name of the substring which should be included in the changelog.
     * @param string $end The required ending of the changelog entry. If a diff detection was performed,
     * this should include the expected pages with changes.
     * @return bool Whether the substring was found or not.
     */
    private function changelog_contains($needle, $end = '') {
        global $DB;
        $records = $DB->get_records('assignsubmission_changes', array('submission' => $this->submission->id));
        foreach ($records as $record) { // Iterate all changelog entries to the submission.
            $found = strpos($record->changes, $needle) !== false; // Check whether this entry contains the needle.
            if ($found) { // The needle was included in this record.
                if ($end == '' || (substr($record->changes, -1 * strlen($end)) === $end)) {
                    return true; // The ending is correct.
                } else {
                    return false; // A fitting record was found, but the ending is wrong.
                }
            }
        }
        return false; // No hit in any record --> Needle not found.
    }

    /**
     * Creates a new stored file, based on the documents in the tests/res/ subdirectory.
     * @param int $contextid The ID of the context of the new file.
     * @param string $filename The name of the file. Must ne equal to the filename in tests/res.
     * @param string $component The component under which the file should be created.
     * @param string $filearea The file area under which the file should be created.
     * @param int $itemid The item ID under which the file should be created.
     * @param string $filepath The filepath of the file.
     * @return stored_file The file instance of moodle.
     */
    private function create_file($contextid, $filename = 'file.pdf', $component = 'mod_resource', $filearea = 'content',
                                       $itemid = 0, $filepath = '/') {

        $fs = get_file_storage();
        $file_info = array(
            'contextid' => $contextid,
            'filename' => $filename,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => $filepath,
            'timecreated' => time(),
            'timemodified' => time());

        $file = $fs->create_file_from_pathname($file_info, dirname(__FILE__) . '/res/' . $filename);

        return $file;
    }
}
