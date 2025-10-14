<?php

namespace local_test1;

use core\event\user_enrolment_deleted;

class usercourseinvoice {

    public static function user_course_invoice_delete(user_enrolment_deleted $event) {
        global $DB;
        $userid = $event->relateduserid;
        $courseid = $event->courseid;
        if ($DB->record_exists('courseuserinvoice', ['courseid' => $courseid, 'userid' => $userid])) {
            $DB->delete_records( 'courseuserinvoice', ['courseid' => $courseid, 'userid' => $userid] );
        }
    }
}
