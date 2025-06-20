<?php

namespace local_coursenotify;

use context_system;
use moodle_url;

!defined('LOCAL_COURSENOTIFY_BEFORE') && define('LOCAL_COURSENOTIFY_BEFORE',1);
!defined('LOCAL_COURSENOTIFY_AFTER') && define('LOCAL_COURSENOTIFY_AFTER',-1);
!defined('LOCAL_COURSENOTIFY_IMMEDIATE') && define('LOCAL_COURSENOTIFY_IMMEDIATE',0);
!defined('LOCAL_COURSENOTIFY_STARTDATE') && define('LOCAL_COURSENOTIFY_STARTDATE',1);
!defined('LOCAL_COURSENOTIFY_ENDDATE') && define('LOCAL_COURSENOTIFY_ENDDATE',-1);
!defined('LOCAL_COURSENOTIFY_STUDENT') && define('LOCAL_COURSENOTIFY_STUDENT',1);
!defined('LOCAL_COURSENOTIFY_BOTH') && define('LOCAL_COURSENOTIFY_BOTH',2);

class utility
{
    public static $component = 'local_coursenotify';
    public static $filearea = 'message';
    public static $perpage = 30;
    public static function get_editoroptions(){
        global $CFG;
        return array(
            'maxfiles'   => -1,
            'maxbytes'   => $CFG->maxbytes,
            'forcehttps' => false,
            'context'    => context_system::instance(),
        );
    }
    public static function get_editlink($courseid,$id = 0){
        $url = new moodle_url('/local/coursenotify/edit.php',array('courseid'=>$courseid));
        if(!empty($id)) $url->param('id',$id);
        return $url;
    }
    public static function get_beforeafteropt(){
        return array(
            LOCAL_COURSENOTIFY_BEFORE => get_string('before',utility::$component),
            LOCAL_COURSENOTIFY_AFTER => get_string('after',utility::$component),
        );
    }
    public static function get_refdateopt(){
        return array(
            LOCAL_COURSENOTIFY_STARTDATE => get_string('startdate',utility::$component),
            LOCAL_COURSENOTIFY_ENDDATE => get_string('enddate',utility::$component),
        );
    }
    public static function get_expirynotifyopt(){
        return array(
            LOCAL_COURSENOTIFY_STUDENT => get_string('student',utility::$component),
            LOCAL_COURSENOTIFY_BOTH => get_string('both',utility::$component),
        );
    }
}
