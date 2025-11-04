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
 * Group observers.
 *
 * @package    mod_quiz
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz;
use mod_quiz\event\attempt_submitted;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Group observers class.
 *
 * @package    mod_quiz
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_observers {

    /**
     * Flag whether a course reset is in progress or not.
     *
     * @var int The course ID.
     */
    protected static $resetinprogress = false;

    /**
     * A course reset has started.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function course_reset_started($event) {
        self::$resetinprogress = $event->courseid;
    }

    /**
     * A course reset has ended.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function course_reset_ended($event) {
        if (!empty(self::$resetinprogress)) {
            if (!empty($event->other['reset_options']['reset_groups_remove'])) {
                quiz_process_group_deleted_in_course($event->courseid);
            }
            if (!empty($event->other['reset_options']['reset_groups_members'])) {
                quiz_update_open_attempts(array('courseid' => $event->courseid));
            }
        }

        self::$resetinprogress = null;
    }

    /**
     * A group was deleted.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function group_deleted($event) {
        if (!empty(self::$resetinprogress)) {
            // We will take care of that once the course reset ends.
            return;
        }
        quiz_process_group_deleted_in_course($event->courseid);
    }

    /**
     * A group member was removed.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function group_member_added($event) {
        quiz_update_open_attempts(array('userid' => $event->relateduserid, 'groupid' => $event->objectid));
    }

    /**
     * A group member was deleted.
     *
     * @param \core\event\base $event The event.
     * @return void
     */
    public static function group_member_removed($event) {
        if (!empty(self::$resetinprogress)) {
            // We will take care of that once the course reset ends.
            return;
        }
        quiz_update_open_attempts(array('userid' => $event->relateduserid, 'groupid' => $event->objectid));
    }

    public static function quizattempt_mail(attempt_submitted $event)
    {
        global $DB;

        $touser = core_user::get_user($event->relateduserid);
        $from = core_user::get_support_user();
        $course = get_course($event->courseid);

        $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
        $quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
        $usermarks = number_format($attempt->sumgrades, 2);
        $totalmarks = number_format($quiz->sumgrades, 2);
        $gradinginfo = grade_get_grades($course->id, 'mod', 'quiz', $quiz->id, $touser->id);
        if (!empty($gradinginfo->items)) {
            $item = $gradinginfo->items[0];
            if (!empty($item->grades[$touser->id])) {
                $usergrade = quiz_format_grade($quiz, $item->grades[$touser->id]->grade);
                $totalgrade = quiz_format_grade($quiz, $item->grademax);
            }
        }
        $reviewurl = new moodle_url('/mod/quiz/review.php', ['attempt' => $attempt->id]);
        $a = new stdClass();
        $a->fullname = fullname($touser);
        $a->coursename = $course->shortname;
        $a->quizname = $quiz->name;
        $a->usermarks = $usermarks;
        $a->totalmarks = $totalmarks;
        $a->usergrade = $usergrade;
        $a->totalgrade = $totalgrade;
        $a->submittime = userdate(time());
        $a->reviewurl = $reviewurl->out(false);
        $from->mailformat = 1;

        $subject = get_string('quizsubmit:subject', 'quiz', $a);
        $message = get_string('quizsubmit:message', 'quiz', $a);
        email_to_user($from, $from, $subject, $message);
    }
}
