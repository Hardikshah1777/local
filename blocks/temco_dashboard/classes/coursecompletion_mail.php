<?php

namespace block_temco_dashboard;

use \core\event\course_completed;
use core_user;
use stdClass;

class coursecompletion_mail
{
    public static function coursecomp_mailtoadmin(course_completed $event)
    {
        global $DB;
        $touser = core_user::get_user($event->relateduserid);
        $admins = get_admins();
        $from = core_user::get_support_user();
        $completiondate = $DB->get_record('course_completions', ['userid' => $event->relateduserid, 'course'=> $event->courseid]);

        $course = get_course($event->courseid);
        $a = new stdClass();
        $a->firstname = $touser->firstname;
        $a->lastname = $touser->lastname;
        $a->coursename = $course->fullname;
        $a->completiontime = userdate($completiondate->timecompleted, get_string( 'strftimedate', 'langconfig'));
        $subject = get_string('adminmailsubject', 'block_temco_dashboard');
        $message = get_string('adminmailbody', 'block_temco_dashboard', $a);
        foreach ($admins as $admin) {
            //email_to_user($admin, $from, $subject, $message);
        }
    }
}