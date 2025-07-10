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
 * Web Services for behaviour plugin.
 *
 * @package    mod_behaviour
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../locallib.php');
require_once(dirname(__FILE__).'/structure.php');
require_once(dirname(__FILE__).'/../../../lib/sessionlib.php');
require_once(dirname(__FILE__).'/../../../lib/datalib.php');

/**
 * Class behaviour_handler
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behaviour_handler {
    /**
     * For this user, this method searches in all the courses that this user has permission to take behaviour,
     * looking for today sessions and returns the courses with the sessions.
     * @param int $userid
     * @return array
     */
    public static function get_courses_with_today_sessions($userid) {
        $usercourses = enrol_get_users_courses($userid);
        $behaviourinstance = get_all_instances_in_courses('behaviour', $usercourses);

        $coursessessions = array();

        foreach ($behaviourinstance as $behaviour) {
            $context = context_course::instance($behaviour->course);
            if (has_capability('mod/behaviour:takebehaviours', $context, $userid)) {
                $course = $usercourses[$behaviour->course];
                if (!isset($course->behaviour_instance)) {
                    $course->behaviour_instance = array();
                }

                $att = new stdClass();
                $att->id = $behaviour->id;
                $att->course = $behaviour->course;
                $att->name = $behaviour->name;
                $att->grade = $behaviour->grade;

                $cm = new stdClass();
                $cm->id = $behaviour->coursemodule;

                $att = new mod_behaviour_structure($att, $cm, $course, $context);
                $todaysessions = $att->get_today_sessions();

                if (!empty($todaysessions)) {
                    $course->behaviour_instance[$att->id] = array();
                    $course->behaviour_instance[$att->id]['name'] = $att->name;
                    $course->behaviour_instance[$att->id]['today_sessions'] = $todaysessions;
                    $coursessessions[$course->id] = $course;
                }
            }
        }

        return self::prepare_data($coursessessions);
    }

    /**
     * Prepare data.
     *
     * @param array $coursessessions
     * @return array
     */
    private static function prepare_data($coursessessions) {
        $courses = array();

        foreach ($coursessessions as $c) {
            $courses[$c->id] = new stdClass();
            $courses[$c->id]->shortname = $c->shortname;
            $courses[$c->id]->fullname = $c->fullname;
            $courses[$c->id]->behaviour_instances = $c->behaviour_instance;
        }

        return $courses;
    }

    /**
     * For this session, returns all the necessary data to take an behaviour.
     *
     * @param int $sessionid
     * @return mixed
     */
    public static function get_session($sessionid) {
        global $DB;

        $session = $DB->get_record('behaviour_sessions', array('id' => $sessionid));
        $session->courseid = $DB->get_field('behaviour', 'course', array('id' => $session->behaviourid));
        $session->statuses = behaviour_get_statuses($session->behaviourid, true, $session->statusset);
        $coursecontext = context_course::instance($session->courseid);
        $session->users = get_enrolled_users($coursecontext, 'mod/behaviour:canbelisted',
                                             $session->groupid, 'u.id, u.firstname, u.lastname');
        $session->behaviour_log = array();

        if ($behaviourlog = $DB->get_records('behaviour_log', array('sessionid' => $sessionid),
                                              '', 'studentid, statusid, remarks, id')) {
            $session->behaviour_log = $behaviourlog;
        }

        return $session;
    }

    /**
     * Update user status
     *
     * @param int $sessionid
     * @param int $studentid
     * @param int $takenbyid
     * @param int $statusid
     * @param int $statusset
     */
    public static function update_user_status($sessionid, $studentid, $takenbyid, $statusid, $statusset) {
        global $DB;

        $record = new stdClass();
        $record->statusset = $statusset;
        $record->sessionid = $sessionid;
        $record->timetaken = time();
        $record->takenby = $takenbyid;
        $record->statusid = $statusid;
        $record->studentid = $studentid;

        if ($behaviourlog = $DB->get_record('behaviour_log', array('sessionid' => $sessionid, 'studentid' => $studentid))) {
            $record->id = $behaviourlog->id;
            $DB->update_record('behaviour_log', $record);
        } else {
            $DB->insert_record('behaviour_log', $record);
        }

        if ($behavioursession = $DB->get_record('behaviour_sessions', array('id' => $sessionid))) {
            $behavioursession->lasttaken = time();
            $behavioursession->lasttakenby = $takenbyid;
            $behavioursession->timemodified = time();

            $DB->update_record('behaviour_sessions', $behavioursession);
        }
    }

    /**
     * For this behaviour instance, returns all sessions.
     *
     * @param int $behaviourid
     * @return mixed
     */
    public static function get_sessions($behaviourid) {
        global $DB;

        $sessions = $DB->get_records('behaviour_sessions', array('behaviourid' => $behaviourid), 'id ASC');

        $sessionsinfo = array();

        foreach ($sessions as $session) {
            $sessionsinfo[$session->id] = self::get_session($session->id);
        }

        return $sessionsinfo;
    }
}
