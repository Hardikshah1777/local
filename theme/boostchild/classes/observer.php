<?php

namespace theme_boostchild;

use core\event\user_enrolment_created;
use core_user;
use moodle_url;

class observer {
    public static function mailsendenroluser(user_enrolment_created $event){
        global $DB;
        $course =  get_course($event->courseid);
        $user = core_user::get_user($event->relateduserid);
        $support = core_user::get_support_user();

        $sql = "SELECT 1 FROM  {user_enrolments} WHERE userid = :userid AND enrolid IN (SELECT id FROM {enrol} WHERE courseid = :courseid) AND id <> :id";
        $courseparams = ['userid'=>$user->id,'id' => $event->objectid,'courseid' => $course->id];
        $checkenrolment = $DB->record_exists_sql($sql,$courseparams);
        if(!$checkenrolment){
            $url = (new moodle_url('/course/view.php',['id' => $course->id]))->out(false);
            $subject = get_string('userenrolsubject','theme_boostchild');
            $messagetext = get_string('userenroltext','theme_boostchild',[
                    'firstname' => $user->firstname,
                    'course' => $course->fullname,
                    'url' => $url
            ]);
//            email_to_user($user, $support, $subject, $messagetext, $messagetext);
        }
    }
}