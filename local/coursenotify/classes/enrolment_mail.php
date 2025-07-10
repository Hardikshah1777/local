<?php

namespace local_coursenotify;

use core\event\user_enrolment_created;
use core_user;
use stdClass;

class enrolment_mail
{
    public static function enrolment_mailtouser(user_enrolment_created $event)
    {
        global $CFG, $OUTPUT;
        $touser = core_user::get_user($event->relateduserid);
        $from = core_user::get_support_user();
        $course = get_course($event->courseid);
        $url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;

        $data = new stdClass();
        $data->firstname = $touser->firstname;
        $data->lastname = $touser->lastname;
        $data->coursename = $course->fullname;
        $data->courseurl = $url;
        $data->siteurl = $CFG->wwwroot;
        $data->sitelogo = $CFG->wwwroot . '/local/coursenotify/pix/EU-logo.jpg';

        $subject = get_string('mail:subject', 'local_coursenotify');
        $message = get_string('mail:message', 'local_coursenotify', $data);

        if (!empty($course->visible)){
            //return email_to_user($touser, $from, $subject, $message);
        }
    }
}