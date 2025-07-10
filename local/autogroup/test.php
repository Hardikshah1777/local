<?php
require_once ('../../config.php');
require_once ('../../group/lib.php');
global $DB;
$userid = 121;
$data = $DB->get_field('user_info_data','data',array('fieldid' => 3, 'userid' => $userid));


$courses = enrol_get_users_courses($userid);

foreach ($courses as $course) {
    $group = $DB->get_record('groups',array('name' => $data, 'courseid' => $course->id));

    if(!empty($group)){
        groups_add_member($group->id,$userid);
    }
}

