<?php

require_once ('../../../config.php');
require_once(__DIR__ . "/../../../group/lib.php");
/*class user_updated
{
    public static function add_to_group(user_enrolment_created $event)
    {*/
global $CFG, $OUTPUT, $DB;
        $event = new stdClass();
        $event->relateduserid = 114;
        $touser = core_user::get_user($event->relateduserid);
        $event->courseid = 31;
        $course = get_course($event->courseid);


        $data = $DB->get_field('user_info_data','data',array('fieldid' => 3, 'userid' => $touser->id));
        $group = $DB->get_record('groups',array('name' => $data, 'courseid' => $course->id));

        if(!empty($group)){
            groups_add_member($group->id,$touser);
        }

/*        return ;
    }
}*/