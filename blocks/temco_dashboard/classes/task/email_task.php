<?php
/**
 * {Example_task} class definition
 *
 * @package     local/yourplugin
 * @author      Your Name
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_temco_dashboard\task;

use core\task\scheduled_task;

class email_task extends scheduled_task
{

    /**
     * Get scheduled task name.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name()
    {
        return get_string("emailtask", "block_temco_dashboard");
    }

    public static function get_course_progress($course)
    {
        return \core_completion\progress::get_course_progress_percentage($course);
    }
    /**
     * Execute the scheduled task.
     */
    public function execute()
    {
        global $DB, $USER;

        $fields = 'u.*, tab.userid,
        tab.courseid,
        CONCAT(u.firstname," ",u.lastname) as uname,
        c.fullname as coursename,
        cc.timecompleted as completiondate,
        tab.duedate,
        u.idnumber,
        c.duration';

        $from = 'SELECT u.* FROM {user_enrolments} ue
        JOIN {enrol} e ON e.id = ue.enrolid GROUP BY ue.userid, e.courseid ) tab
        JOIN {user} u ON u.id = tab.userid
        JOIN {course} c ON c.id = tab.courseid AND c.id = 30
        LEFT JOIN {course_completions} cc ON cc.userid = tab.userid AND cc.course = tab.courseid AND cc.timecompleted = 0';

        $where = 'u.suspended = :suspended AND u.deleted = :deleted';

        $params_array = ['suspended' => 0, 'deleted' => 0]; // Replace with actual values

        $users = $DB->get_records_sql("SELECT $fields FROM $from WHERE $where", $params_array);

        $course = $users->coursename;

        $recorddate = $DB->get_record_sql("SELECT $fields FROM $from WHERE $where", $params_array);
        $duedate = $recorddate->duedate + $recorddate->duration;
        $a = userdate($duedate);
        $subject = 'Course Completion Reminder Mail';
        $message = get_string('sendreminder', 'block_temco_dashboard', userdate($duedate));

        $from = $USER;

        $course = $DB->get_record('course', ['id' => 30]);

        foreach ($users as $user) {
            $userid = $user->id;
            $progress = self::get_course_progress($course);

            if ($progress < 100) {
                email_to_user($user, $from, $subject, $message);
            }
        }
    }
}
