<?php

namespace local_novicegroup;

use core\event\user_enrolment_created;
use core_user;

class user_updated
{
    public static function add_to_group(user_enrolment_created $event)
    {
        global $CFG, $OUTPUT, $DB;
        require_once(__DIR__ . "/../../../group/lib.php");
        $touser = core_user::get_user($event->relateduserid);
        $course = get_course($event->courseid);

        $data = $DB->get_field('user_info_data', 'data', array('fieldid' => 3, 'userid' => $touser->id));
        $group = $DB->get_record('groups', array('name' => $data, 'courseid' => $course->id));

        if (!empty($group)) {
            groups_add_member($group->id, $touser);
        }

        return;
    }
}