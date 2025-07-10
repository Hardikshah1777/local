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
 * External functions test for behaviour plugin.
 *
 * @package    mod_behaviour
 * @category   test
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_behaviour\external;

use externallib_advanced_testcase;
use mod_behaviour_structure;
use stdClass;
use behaviour_handler;
use external_api;
use mod_behaviour_external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/behaviour/classes/behaviour_webservices_handler.php');
require_once($CFG->dirroot . '/mod/behaviour/classes/structure.php');
require_once($CFG->dirroot . '/mod/behaviour/externallib.php');

/**
 * This class contains the test cases for webservices.
 *
 * @package    mod_behaviour
 * @category   test
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_behaviour
 */
class external_test extends externallib_advanced_testcase {
    /** @var core_course_category */
    protected $category;
    /** @var stdClass */
    protected $course;
    /** @var stdClass */
    protected $behaviour;
    /** @var stdClass */
    protected $teacher;
    /** @var array */
    protected $students;
    /** @var array */
    protected $sessions;

    /**
     * Setup class.
     */
    public function setUp(): void {
        global $DB;
        $this->category = $this->getDataGenerator()->create_category();
        $this->course = $this->getDataGenerator()->create_course(array('category' => $this->category->id));
        $att = $this->getDataGenerator()->create_module('behaviour', array('course' => $this->course->id));
        $cm = $DB->get_record('course_modules', array('id' => $att->cmid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $this->behaviour = new mod_behaviour_structure($att, $cm, $course);

        $this->create_and_enrol_users();

        $this->setUser($this->teacher);

        $session = new stdClass();
        $session->sessdate = time();
        $session->duration = 6000;
        $session->description = "";
        $session->descriptionformat = 1;
        $session->descriptionitemid = 0;
        $session->timemodified = time();
        $session->statusset = 0;
        $session->groupid = 0;
        $session->absenteereport = 1;
        $session->calendarevent = 0;

        // Creating session.
        $this->sessions[] = $session;

        $this->behaviour->add_sessions($this->sessions);
    }

    /** Creating 10 students and 1 teacher. */
    protected function create_and_enrol_users() {
        $this->students = array();
        for ($i = 0; $i < 10; $i++) {
            $this->students[] = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        }

        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
    }

    /** test behaviour_handler::get_courses_with_today_sessions */
    public function test_get_courses_with_today_sessions() {
        $this->resetAfterTest(true);

        // Just adding the same session again to check if the method returns the right amount of instances.
        $this->behaviour->add_sessions($this->sessions);

        $courseswithsessions = behaviour_handler::get_courses_with_today_sessions($this->teacher->id);
        $courseswithsessions = external_api::clean_returnvalue(mod_behaviour_external::get_courses_with_today_sessions_returns(),
            $courseswithsessions);

        $this->assertTrue(is_array($courseswithsessions));
        $this->assertEquals(count($courseswithsessions), 1);
        $course = array_pop($courseswithsessions);
        $this->assertEquals($course['fullname'], $this->course->fullname);
        $behaviourinstance = array_pop($course['behaviour_instances']);
        $this->assertEquals(count($behaviourinstance['today_sessions']), 2);
    }

    /** test behaviour_handler::get_courses_with_today_sessions multiple */
    public function test_get_courses_with_today_sessions_multiple_instances() {
        global $DB;
        $this->resetAfterTest(true);

        // Make another behaviour.
        $att = $this->getDataGenerator()->create_module('behaviour', array('course' => $this->course->id));
        $cm = $DB->get_record('course_modules', array('id' => $att->cmid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $second = new mod_behaviour_structure($att, $cm, $course);

        // Just add the same session.
        $secondsession = clone $this->sessions[0];

        $second->add_sessions([$secondsession]);

        $courseswithsessions = behaviour_handler::get_courses_with_today_sessions($this->teacher->id);
        $courseswithsessions = external_api::clean_returnvalue(mod_behaviour_external::get_courses_with_today_sessions_returns(),
            $courseswithsessions);

        $this->assertTrue(is_array($courseswithsessions));
        $this->assertEquals(count($courseswithsessions), 1);
        $course = array_pop($courseswithsessions);
        $this->assertEquals(count($course['behaviour_instances']), 2);
    }

    /** test behaviour_handler::get_session */
    public function test_get_session() {
        $this->resetAfterTest(true);

        $courseswithsessions = behaviour_handler::get_courses_with_today_sessions($this->teacher->id);
        $courseswithsessions = external_api::clean_returnvalue(mod_behaviour_external::get_courses_with_today_sessions_returns(),
            $courseswithsessions);

        $course = array_pop($courseswithsessions);
        $behaviourinstance = array_pop($course['behaviour_instances']);
        $session = array_pop($behaviourinstance['today_sessions']);

        $sessioninfo = behaviour_handler::get_session($session['id']);
        $sessioninfo = external_api::clean_returnvalue(mod_behaviour_external::get_session_returns(),
            $sessioninfo);

        $this->assertEquals($this->behaviour->id, $sessioninfo['behaviourid']);
        $this->assertEquals($session['id'], $sessioninfo['id']);
        $this->assertEquals(count($sessioninfo['users']), 10);
    }

    /** test get session with group */
    public function test_get_session_with_group() {
        $this->resetAfterTest(true);

        // Create a group in our course, and add some students to it.
        $group = new stdClass();
        $group->courseid = $this->course->id;
        $group = $this->getDataGenerator()->create_group($group);

        for ($i = 0; $i < 5; $i++) {
            $member = new stdClass;
            $member->groupid = $group->id;
            $member->userid = $this->students[$i]->id;
            $this->getDataGenerator()->create_group_member($member);
        }

        // Add a session that's identical to the first, but with a group.
        $midnight = usergetmidnight(time()); // Check if this test is running during midnight.
        $session = clone $this->sessions[0];
        $session->groupid = $group->id;
        $session->sessdate += 1; // Make sure it appears second in the list.
        $this->behaviour->add_sessions([$session]);

        $courseswithsessions = behaviour_handler::get_courses_with_today_sessions($this->teacher->id);

        // This test is fragile when running over midnight - check that it is still the same day, if not, run this again.
        // This isn't really ideal code, but will hopefully still give a valid test.
        if (empty($courseswithsessions) && $midnight !== usergetmidnight(time())) {
            $this->behaviour->add_sessions([$session]);
            $courseswithsessions = behaviour_handler::get_courses_with_today_sessions($this->teacher->id);
        }
        $courseswithsessions = external_api::clean_returnvalue(mod_behaviour_external::get_courses_with_today_sessions_returns(),
            $courseswithsessions);

        $course = array_pop($courseswithsessions);
        $behaviourinstance = array_pop($course['behaviour_instances']);
        $session = array_pop($behaviourinstance['today_sessions']);

        $sessioninfo = behaviour_handler::get_session($session['id']);
        $sessioninfo = external_api::clean_returnvalue(mod_behaviour_external::get_session_returns(),
            $sessioninfo);

        $this->assertEquals($session['id'], $sessioninfo['id']);
        $this->assertEquals($group->id, $sessioninfo['groupid']);
        $this->assertEquals(count($sessioninfo['users']), 5);
    }

    /** test update user status */
    public function test_update_user_status() {
        $this->resetAfterTest(true);

        $courseswithsessions = behaviour_handler::get_courses_with_today_sessions($this->teacher->id);
        $courseswithsessions = external_api::clean_returnvalue(mod_behaviour_external::get_courses_with_today_sessions_returns(),
            $courseswithsessions);

        $course = array_pop($courseswithsessions);
        $behaviourinstance = array_pop($course['behaviour_instances']);
        $session = array_pop($behaviourinstance['today_sessions']);

        $sessioninfo = behaviour_handler::get_session($session['id']);
        $sessioninfo = external_api::clean_returnvalue(mod_behaviour_external::get_session_returns(),
            $sessioninfo);

        $student = array_pop($sessioninfo['users']);
        $status = array_pop($sessioninfo['statuses']);
        $statusset = $sessioninfo['statusset'];

        $result = mod_behaviour_external::update_user_status($session['id'], $student['id'], $this->teacher->id,
            $status['id'], $statusset);
        $result = external_api::clean_returnvalue(mod_behaviour_external::update_user_status_returns(), $result);

        $sessioninfo = behaviour_handler::get_session($session['id']);
        $sessioninfo = external_api::clean_returnvalue(mod_behaviour_external::get_session_returns(),
            $sessioninfo);

        $log = array_pop($sessioninfo['behaviour_log']);
        $this->assertEquals($student['id'], $log['studentid']);
        $this->assertEquals($status['id'], $log['statusid']);
    }

    /** Test adding new behaviour record via ws. */
    public function test_add_behaviour() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        // Become a teacher.
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $this->setUser($teacher);

        // Check behaviour does not exist.
        $this->assertCount(0, $DB->get_records('behaviour', ['course' => $course->id]));

        // Create behaviour.
        $result = mod_behaviour_external::add_behaviour($course->id, 'test', 'test', NOGROUPS);
        $result = external_api::clean_returnvalue(mod_behaviour_external::add_behaviour_returns(), $result);

        // Check behaviour exist.
        $this->assertCount(1, $DB->get_records('behaviour', ['course' => $course->id]));
        $record = $DB->get_record('behaviour', ['id' => $result['behaviourid']]);
        $this->assertEquals($record->name, 'test');

        // Check group.
        $cm = get_coursemodule_from_instance('behaviour', $result['behaviourid'], 0, false, MUST_EXIST);
        $groupmode = (int)groups_get_activity_groupmode($cm);
        $this->assertEquals($groupmode, NOGROUPS);

        // Create behaviour with "separate groups" group mode.
        $result = mod_behaviour_external::add_behaviour($course->id, 'testsepgrp', 'testsepgrp', SEPARATEGROUPS);
        $result = external_api::clean_returnvalue(mod_behaviour_external::add_behaviour_returns(), $result);

        // Check behaviour exist.
        $this->assertCount(2, $DB->get_records('behaviour', ['course' => $course->id]));
        $record = $DB->get_record('behaviour', ['id' => $result['behaviourid']]);
        $this->assertEquals($record->name, 'testsepgrp');

        // Check group.
        $cm = get_coursemodule_from_instance('behaviour', $result['behaviourid'], 0, false, MUST_EXIST);
        $groupmode = (int)groups_get_activity_groupmode($cm);
        $this->assertEquals($groupmode, SEPARATEGROUPS);

        // Create behaviour with wrong group mode.
        $this->expectException('invalid_parameter_exception');
        $result = mod_behaviour_external::add_behaviour($course->id, 'test1', 'test1', 100);
    }

    /** Test remove behaviour va ws. */
    public function test_remove_behaviour() {
        global $DB;
        $this->resetAfterTest(true);

        // Become a teacher.
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $this->course->id, $teacherrole->id);
        $this->setUser($teacher);

        // Check behaviour exists.
        $this->assertCount(1, $DB->get_records('behaviour', ['course' => $this->course->id]));
        $this->assertCount(1, $DB->get_records('behaviour_sessions', ['behaviourid' => $this->behaviour->id]));

        // Remove behaviour.
        $result = mod_behaviour_external::remove_behaviour($this->behaviour->id);
        $result = external_api::clean_returnvalue(mod_behaviour_external::remove_behaviour_returns(), $result);

        // Check behaviour removed.
        $this->assertCount(0, $DB->get_records('behaviour', ['course' => $this->course->id]));
        $this->assertCount(0, $DB->get_records('behaviour_sessions', ['behaviourid' => $this->behaviour->id]));
    }

    /** Test add session to existing attendnace via ws. */
    public function test_add_session() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        // Become a teacher.
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $this->setUser($teacher);

        // Create behaviour with separate groups mode.
        $behavioursepgroups = mod_behaviour_external::add_behaviour($course->id, 'sepgroups', 'test', SEPARATEGROUPS);
        $behavioursepgroups = external_api::clean_returnvalue(mod_behaviour_external::add_behaviour_returns(),
                                                               $behavioursepgroups);

        // Check behaviour exist.
        $this->assertCount(1, $DB->get_records('behaviour', ['course' => $course->id]));

        // Create session and validate record.
        $time = time();
        $duration = 3600;
        $result = mod_behaviour_external::add_session($behavioursepgroups['behaviourid'],
            'testsession', $time, $duration, $group->id, true);
        $result = external_api::clean_returnvalue(mod_behaviour_external::add_session_returns(), $result);

        $this->assertCount(1, $DB->get_records('behaviour_sessions', ['id' => $result['sessionid']]));
        $record = $DB->get_record('behaviour_sessions', ['id' => $result['sessionid']]);
        $this->assertEquals($record->description, 'testsession');
        $this->assertEquals($record->behaviourid, $behavioursepgroups['behaviourid']);
        $this->assertEquals($record->groupid, $group->id);
        $this->assertEquals($record->sessdate, $time);
        $this->assertEquals($record->duration, $duration);
        $this->assertEquals($record->calendarevent, 1);

        // Create session with no group in "separate groups" behaviour.
        $this->expectException('invalid_parameter_exception');
        mod_behaviour_external::add_session($behavioursepgroups['behaviourid'], 'test', time(), 3600, 0, false);
    }

    /** Test add session group in no group - error. */
    public function test_add_session_group_in_no_group_exception() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        // Become a teacher.
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $this->setUser($teacher);

        // Create behaviour with no groups mode.
        $behaviournogroups = mod_behaviour_external::add_behaviour($course->id, 'nogroups',
                                                                 'test', NOGROUPS);
        $behaviournogroups = external_api::clean_returnvalue(mod_behaviour_external::add_behaviour_returns(),
            $behaviournogroups);

        // Check behaviour exist.
        $this->assertCount(1, $DB->get_records('behaviour', ['course' => $course->id]));

        // Create session with group in "no groups" behaviour.
        $this->expectException('invalid_parameter_exception');
        mod_behaviour_external::add_session($behaviournogroups['behaviourid'], 'test', time(), 3600, $group->id, false);
    }

    /** Test add sesssion to invalid group. */
    public function test_add_session_invalid_group_exception() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        // Become a teacher.
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);
        $this->setUser($teacher);

        // Create behaviour with visible groups mode.
        $behaviourvisgroups = mod_behaviour_external::add_behaviour($course->id, 'visgroups', 'test', VISIBLEGROUPS);
        $behaviourvisgroups = external_api::clean_returnvalue(mod_behaviour_external::add_behaviour_returns(),
                                                               $behaviourvisgroups);

        // Check behaviour exist.
        $this->assertCount(1, $DB->get_records('behaviour', ['course' => $course->id]));

        // Create session with invalid group in "visible groups" behaviour.
        $this->expectException('invalid_parameter_exception');
        mod_behaviour_external::add_session($behaviourvisgroups['behaviourid'], 'test', time(), 3600, $group->id + 100, false);
    }

    /** Test remove session via ws. */
    public function test_remove_session() {
        global $DB;
        $this->resetAfterTest(true);

        // Create behaviour with no groups mode.
        $behaviour = mod_behaviour_external::add_behaviour($this->course->id, 'test', 'test', NOGROUPS);
        $behaviour = external_api::clean_returnvalue(mod_behaviour_external::add_behaviour_returns(), $behaviour);

        // Create sessions.
        $result0 = mod_behaviour_external::add_session($behaviour['behaviourid'], 'test0', time(), 3600, 0, false);
        $result0 = external_api::clean_returnvalue(mod_behaviour_external::add_session_returns(), $result0);
        $result1 = mod_behaviour_external::add_session($behaviour['behaviourid'], 'test1', time(), 3600, 0, false);
        $result1 = external_api::clean_returnvalue(mod_behaviour_external::add_session_returns(), $result1);

        $this->assertCount(2, $DB->get_records('behaviour_sessions', ['behaviourid' => $behaviour['behaviourid']]));

        // Delete session 0.
        $result = mod_behaviour_external::remove_session($result0['sessionid']);
        $result = external_api::clean_returnvalue(mod_behaviour_external::remove_session_returns(), $result);
        $this->assertCount(1, $DB->get_records('behaviour_sessions', ['behaviourid' => $behaviour['behaviourid']]));

        // Delete session 1.
        $result = mod_behaviour_external::remove_session($result1['sessionid']);
        $result = external_api::clean_returnvalue(mod_behaviour_external::remove_session_returns(), $result);
        $this->assertCount(0, $DB->get_records('behaviour_sessions', ['behaviourid' => $behaviour['behaviourid']]));
    }

    /** Test session creates cal event. */
    public function test_add_session_creates_calendar_event() {
        global $DB;
        $this->resetAfterTest(true);

        // Create behaviour with no groups mode.
        $behaviour = mod_behaviour_external::add_behaviour($this->course->id, 'test', 'test', NOGROUPS);
        $behaviour = external_api::clean_returnvalue(mod_behaviour_external::add_behaviour_returns(), $behaviour);

        // Prepare events tracing.
        $sink = $this->redirectEvents();

        // Create session with no calendar event.
        $result = mod_behaviour_external::add_session($behaviour['behaviourid'], 'test0', time(), 3600, 0, false);
        $result = external_api::clean_returnvalue(mod_behaviour_external::add_session_returns(), $result);

        // Capture the event.
        $events = $sink->get_events();
        $sink->clear();

        // Validate.
        $this->assertCount(1, $events);
        $this->assertInstanceOf('\mod_behaviour\event\session_added', $events[0]);

        // Create session with calendar event.
        $result = mod_behaviour_external::add_session($behaviour['behaviourid'], 'test0', time(), 3600, 0, true);
        $result = external_api::clean_returnvalue(mod_behaviour_external::add_session_returns(), $result);

        // Capture the event.
        $events = $sink->get_events();
        $sink->clear();

        // Validate the event.
        $this->assertCount(2, $events);
        $this->assertInstanceOf('\core\event\calendar_event_created', $events[0]);
        $this->assertInstanceOf('\mod_behaviour\event\session_added', $events[1]);
    }

    /** Test get sessions. */
    public function test_get_sessions() {
        $this->resetAfterTest(true);

        $courseswithsessions = behaviour_handler::get_courses_with_today_sessions($this->teacher->id);
        $courseswithsessions = external_api::clean_returnvalue(mod_behaviour_external::get_courses_with_today_sessions_returns(),
            $courseswithsessions);

        foreach ($courseswithsessions as $course) {

            $behaviourinstances = $course['behaviour_instances'];

            foreach ($behaviourinstances as $behaviourinstance) {

                $sessionsinfo = $behaviourinstance['today_sessions'];

                foreach ($sessionsinfo as $sessioninfo) {

                    $sessions = behaviour_handler::get_sessions($sessioninfo['behaviourid']);
                    $sessions = external_api::clean_returnvalue(mod_behaviour_external::get_sessions_returns(),
                        $sessions);

                    foreach ($sessions as $session) {
                        $sessiontocompareagainst = behaviour_handler::get_session($session['id']);
                        $sessiontocompareagainst = external_api::clean_returnvalue(mod_behaviour_external::get_session_returns(),
                            $sessiontocompareagainst);

                        $this->assertEquals($this->behaviour->id, $session['behaviourid']);
                        $this->assertEquals($sessiontocompareagainst['id'], $session['id']);
                        $this->assertEquals(count($session['users']), count($sessiontocompareagainst['users']));
                    }
                }
            }
        }
    }
}
