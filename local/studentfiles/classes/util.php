<?php

namespace local_studentfiles;

class util {
    const component = 'local_studentfiles';
    const filearea = 'history';
    const dbtable = 'local_studentfiles';
    const managecap = 'local/studentfiles:manage';
    const notemplate = 0;
    const feedbacktemplate = -1;
    const certificatetemplate = -2;
    const placeholders =[
            'placeholder:firstname',
            'placeholder:lastname',
            'placeholder:fileurl',
    ];
    const templatetable = 'local_studentfiles_templates';
    private static $placeholders = [];
    private static $teacherroleid = null;

    public static function get_string($stringid, $a = null, $component = self::component){
        return \get_string($stringid,$component,$a);
    }

    public static function store($userid,$filename){
        global $DB;
        $record = new \stdClass();
        $record->userid = $userid;
        $record->filename = $filename;
        $record->timecreated = time();
        $record->id = $DB->insert_record(self::component,$record);
        return $record->id;
    }

    public static function notify($user,$mailsubject,$mailbody,$fileurl = null) {
        $support = \core_user::get_support_user();
        if(!self::$placeholders){
            self::$placeholders = array_map(__CLASS__."::get_string",self::placeholders);
        }
        $mailbody = str_replace(
                self::$placeholders,
                [$user->firstname,$user->lastname,$fileurl],
                $mailbody
        );
        return email_to_user($user, $support, $mailsubject, $mailbody, text_to_html($mailbody));
    }

    public static function user_can_access($currentuserid = null){
        global $USER;
        $userid = $currentuserid ?? $USER->id;
        if(is_siteadmin($userid)) {
            return true;
        }
        $courses = get_user_capability_course(self::managecap);
        return !empty($courses);
    }
}
