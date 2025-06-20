<?php

namespace local_test3;

use \core\event\course_completed;
use core_user;
use stdClass;

class coursecompletion_mail
{
    public static function coursecompleted_mail(course_completed $event)
    {
        global $DB;

        $touser = core_user::get_user($event->relateduserid);
        $from = core_user::get_support_user();

        $course = get_course($event->courseid);
        $a = new stdClass();
        $a->firstname = $touser->firstname;
        $a->lastname = $touser->lastname;
        $a->coursename = $course->fullname;

        $subject = get_string('coursecompletedsubject', 'local_test3');
        $message = get_string('coursecompletedbody', 'local_test3', $a);
        email_to_user($touser, $from, $subject, $message, $message);
    }
}