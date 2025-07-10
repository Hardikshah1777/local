<?php

namespace block_temco_dashboard\task;

use core\task\scheduled_task;
use core_user;
use stdClass;

class mailbefore_twodays extends scheduled_task
{
    public function get_name()
    {
        return get_string("coursecompletionreminderforstudent", "block_temco_dashboard");
    }

    public function execute()
    {
        global $DB;
        $users = $DB->get_records_sql('SELECT ue.id, e.courseid, c.fullname, c.duration, ue.timestart, ue.userid, u.firstname, u.lastname  FROM mdl_user u
                                    JOIN {user_enrolments} ue ON ue.userid = u.id
                                    JOIN {enrol} e ON e.id = ue.enrolid
                                    JOIN {course} c ON c.id = e.courseid
                                    LEFT JOIN {course_completions} cc ON cc.userid = ue.userid AND cc.course = e.courseid
                                    WHERE u.id > 2  AND suspended = 0 AND deleted = 0 AND c.visible = 1 AND (cc.timecompleted = 0 OR cc.timecompleted IS NULL)');
        foreach ($users as $user) {
            $touser = core_user::get_user($user->userid);
            $from = core_user::get_support_user();

            $a = new stdClass();
            $a->firstname = $touser->firstname;
            $a->coursename = $user->fullname;
            $a->url = $CFG->wwwroot.'/course/view.php?id='.$user->courseid;
            $durationtime = $user->timestart + $user->duration;
            $a->deadline = userdate($durationtime, get_string('strftimedate', 'langconfig'));

            $beforetwodaysmail = $durationtime - (DAYSECS * 2);
            $afterfivedaysmail = $durationtime + (DAYSECS * 5);

            if (time() > $beforetwodaysmail) {
                $subject = get_string('twodaymailsubject', 'block_temco_dashboard');
                $message = get_string('twodaymailbody', 'block_temco_dashboard', $a);
//                email_to_user($touser, $from, $subject, $message);
            }
            if (time() > $afterfivedaysmail){
                $subject1 = get_string('fivedaymailsubject', 'block_temco_dashboard');
                $message1 = get_string('fivedaymailbody', 'block_temco_dashboard', $a);
//                email_to_user($touser, $from, $subject1, $message1);
            }
        }
    }
}