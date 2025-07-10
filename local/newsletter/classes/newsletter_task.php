<?php

namespace local_newsletter;

use context_module;
use core\task\scheduled_task;
use core_user;
use csv_export_writer;
use moodle_url;

require_once $CFG->libdir.'/csvlib.class.php';

class newsletter_task extends scheduled_task {

    public function get_name() {
        return get_string('newslettertask', 'local_newsletter');
    }

    public function execute() {
       global $DB, $CFG;

        $mindate = strtotime('midnight');
        $maxdate = $mindate + (DAYSECS - 1);


        $sql = "SELECT * FROM {local_newsletter} ln WHERE IF(ln.remindermail = 0, ln.scheduledate > :mindate AND ln.scheduledate < :maxdate, ln.enddate > :todaydate AND ln.scheduledate < :todaydate1)";
        $newsletters = $DB->get_records_sql($sql, ['mindate' => $mindate, 'maxdate' => $maxdate, 'todaydate' => time(), 'todaydate1' => time()]);
        $fromuser = core_user::get_support_user();

        if (!empty($newsletters)) {
            foreach ($newsletters as $newsletter) {
                $context = context_module::instance($newsletter->activityid);
                $courseid = $context->get_course_context()->instanceid;
                $enrolledusers = get_enrolled_users($context, 'mod/feedback:view');
                $completedusers = [];
                $notcompletedusers = [];

                if (!empty($newsletter->remindermail)) {
                    list(,$cm) = get_course_and_cm_from_cmid($newsletter->activityid, 'feedback', $courseid);
                    $completedusers = $DB->get_records('feedback_completed', ['feedback' => $cm->instance], '', 'DISTINCT userid');
                }
                foreach ($enrolledusers as $enrolleduser) {

                    $cm = get_fast_modinfo($courseid, $enrolleduser->id)->cms[$newsletter->activityid] ?? null;

                    if (empty($cm) || !empty($cm->deletioninprogress) || !$cm->available) {
                        continue;
                    }
                    $touser = core_user::get_user($enrolleduser->id);
                    $activitylink = (string) new moodle_url('/mod/feedback/view.php', ['id' => $newsletter->activityid]);
                    $message = str_replace(
                            [
                                    '{:firstname:}',
                                    '{:lastname:}',
                                    '{:activitylink:}'
                            ],
                            [
                                    $enrolleduser->firstname,
                                    $enrolleduser->lastname,
                                    $activitylink,
                            ],
                            $newsletter->message
                    );

                    if (!empty($newsletter->remindermail)) {
                        if (!array_key_exists($enrolleduser->id, $completedusers) ) {
                            $notcompletedusers[] = $enrolleduser;
                            email_to_user($touser, $fromuser, $newsletter->subject, $message, $message);
                        }
                    } else {
                        email_to_user($touser, $fromuser, $newsletter->subject, $message, $message);
                    }
                }

                if (!empty($notcompletedusers)) {
                    $header = ['firstname', 'lastname', 'email'];

                    $csvwrite = new csv_export_writer('comma');
                    $csvwrite->set_filename('NotCompletedUserList');
                    $csvwrite->add_data(array_values($header));
                    foreach ($notcompletedusers as $notcompleteduser) {
                        $content = [];
                        $content[] = $notcompleteduser->firstname;
                        $content[] = $notcompleteduser->lastname;
                        $content[] = $notcompleteduser->email;
                        $csvwrite->add_data($content);
                    }

                    $admin = core_user::get_user(499);
                    email_to_user($admin, $fromuser, get_string('adminsubject', 'local_newsletter'),
                            get_string('adminmsg', 'local_newsletter'),
                            get_string('adminmsg', 'local_newsletter'),
                            $csvwrite->path, 'NotCompletedUserList.csv');
                }

            }
        }
    }
}