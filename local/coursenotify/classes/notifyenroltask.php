<?php

namespace local_coursenotify;

use core\task\scheduled_task;
use core_user;
use stdClass;

class notifyenroltask  extends scheduled_task {

    public function get_name() {
        return get_string('notifyenroltask', utility::$component);
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        global $DB, $CFG, $OUTPUT;

        $minexpiry = strtotime('-1 week 0:0');
        $maxexpiry = $minexpiry + (DAYSECS - 1);

        $usersql = "SELECT ue.id,ue.userid,c.id as courseid, c.fullname FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        JOIN {course} c ON c.id = e.courseid 
        WHERE ue.timecreated > :minexpiry AND ue.timecreated < :maxexpiry AND e.status = :enrolstatus AND ue.status = :uestatus";
        $users = $DB->get_records_sql($usersql,
                ['minexpiry' => $minexpiry, 'maxexpiry' => $maxexpiry, 'enrolstatus' => ENROL_INSTANCE_ENABLED, 'uestatus' => ENROL_USER_ACTIVE]);

        if (!empty($users)) {
            foreach ($users as $userdata) {
                $touser = core_user::get_user($userdata->userid);
                $fromuser = core_user::get_support_user();
                $courseurl = $CFG->wwwroot.'/course/view.php?id='.$userdata->courseid;

                $messages = new stdClass();
                $messages->lastname = $touser->lastname;
                $messages->firstname = $touser->firstname;
                $messages->coursename = $userdata->fullname;
                $messages->courseurl = $courseurl;
                $messages->siteurl = $CFG->wwwroot;
                $messages->sitelogo = $CFG->wwwroot.'/local/coursenotify/pix/EU-logo.jpg';

                $subject = get_string('mail:subjectreminder','local_coursenotify');
                $message = get_string('mail:message','local_coursenotify', $messages);
//                email_to_user($touser,$fromuser,$subject,$message,$message);
            }
        }
    }
}