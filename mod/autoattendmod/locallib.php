<?php

defined('MOODLE_INTERNAL') || die;


//
require_once(dirname(__FILE__).'/lib.php');


/*
 function autoattendmod_get_course_module($courseid)
 function autoattendmod_get_namepattern($courseid)

 function autoattendmod_get_disp_info($courseid)
 function autoattendmod_get_predisp_time($courseid)

 function autoattendmod_disp_feedback($courseid)
 function autoattendmod_is_email_enable($courseid)
 function autoattendmod_is_email_allreports($courseid)
 function autoattendmod_is_email_key($courseid)
 function autoattendmod_is_email_user($courseid)
 function autoattendmod_use_summtertime($courseid)
 function autoattendmod_is_old_excel($courseid)
 function autoattendmod_is_backup_block($courseid)
 function autoattendmod_disp_localhostname($courseid)

 function autoattendmod_get_receivemail_users($context)
 function autoattendmod_send_email_teachers($courseid, $subject, $posttext, $posthtml, $attachdata, $attachname)
 function autoattendmod_send_email_user($courseid, $user, $subject, $posttext, $posthtml, $attachdata, $attachname)
*/


function  autoattendmod_get_course_module($courseid)
{
    global $DB;

    if ($courseid==0) return false;

    $mod = $DB->get_field('modules', 'id', array('name'=>'autoattendmod'));
    if ($mod<=0) return false;

    $select = 'course=? AND module=? AND instance>0 AND deletioninprogress=0';
    $params = array($courseid, $mod);
    $module = $DB->get_record_select('course_modules', $select, $params);

    return $module;
}


//
function  autoattendmod_get_idnumber($courseid)
{
    if ($courseid==0) return '';

    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return '';
  
    return $mod->idnumber;
}


//
function  autoattendmod_get_namepattern($courseid)
{
    global $DB;

    if ($courseid==0) return 'fullname';
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 'fullname';
  
    $pattern = $DB->get_field('autoattendmod', 'namepattern', array('id'=>$mod->instance));
    if (!$pattern) $pattern = 'fullname';

    return $pattern;
}



function  autoattendmod_get_disp_info($courseid)
{
    global $DB;

    $ret = 1;
    if ($courseid==0) return $ret;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return $ret;
  
    $ret = $DB->get_field('autoattendmod', 'dispinfo', array('id'=>$mod->instance));
    if (!$ret) $ret = 1;

    return $ret;
}


function  autoattendmod_get_predisp_time($courseid)
{
    global $DB;

    $ret = 5;
    if ($courseid==0) return $ret;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return $ret;
  
    $ret = $DB->get_field('autoattendmod', 'predisptime', array('id'=>$mod->instance));
    if (!$ret) $ret = 5;

    return $ret;
}


//
function  autoattendmod_disp_feedback($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'feedback', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}


//
function  autoattendmod_is_email_enable($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'emailenable', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}


//
function  autoattendmod_is_email_allreports($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'allreports', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}


//
function  autoattendmod_is_email_key($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'emailkey', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}


//
function  autoattendmod_is_email_user($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'emailuser', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}


//
function  autoattendmod_use_summertime($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'summertime', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}


//
function  autoattendmod_is_old_excel($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'excelver', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}


//
function  autoattendmod_is_backup_block($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'backupblock', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}



//
function  autoattendmod_disp_localhostname($courseid)
{
    global $DB;

    if ($courseid==0) return 0;
    $mod = autoattendmod_get_course_module($courseid);
    if (!$mod) return 0;

    $ret = $DB->get_field('autoattendmod', 'localhostname', array('id'=>$mod->instance));
    if (!$ret) $ret = 0;

    return $ret;
}



////////////////////////////////////////////////////////////////////////////////////////
//
// E-Mail
//

// メール受信可能なユーザ
function  autoattendmod_get_receivemail_users($context)
{
    $ret = get_users_by_capability($context, 'mod/autoattendmod:receivemail', '', 'lastname', '', '', false, '', false);
    return $ret;
}


//
function  autoattendmod_send_email_teachers($courseid, $subject, $posttext, $posthtml, $attachdata, $attachname)
{
    global $CFG;
    require_once($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php');

    $ccontext = context_course::instance($courseid);
    $teachers = autoattendmod_get_receivemail_users($ccontext);

    if ($teachers) {
        foreach ($teachers as $teacher) {
            if (jbxl_is_teacher($teacher->id, $ccontext, false)) {
                $htmlmail = '';
                if ($teacher->mailformat==1) {
                    $htmlmail = $posthtml;
                }
                email_to_user($teacher, 'AutoAttendanceModule', $subject, $posttext, $htmlmail, $attachdata, $attachname);
            }
        }
    }
}


//
function  autoattendmod_send_email_user($courseid, $user, $subject, $posttext, $posthtml, $attachdata, $attachname)
{
    email_to_user($user, 'AutoAttendanceModule', $subject, $posttext, $posthtml, $attachdata, $attachname);
}



////////////////////////////////////////////////////////////////////////////////////////
//
//
//

function  autoattendmod_get_event($cm, $action, $params='', $info='')
{
    global $CFG;
    require_once($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_tools.php');
    require_once($CFG->dirroot.'/blocks/autoattend/jbxl/jbxl_moodle_tools.php');

    $ver = jbxl_get_moodle_version();

    $event = null;
    if (!is_array($params)) $params = array();

    if (floatval($ver)>=2.7) {
        $params = array(
            'context' => context_module::instance($cm->id),
            'other' => array('params' => $params, 'info'=> $info),
        );
        $event = \mod_autoattendmod\event\view_log::create($params);
    }

    // for Legacy add_to_log()        
    else {
        $file = 'view.php';
        $param_str = jbxl_get_url_params_str($params);
        //
        $event = new stdClass();
        $event->courseid = $cm->course;
        $event->name     = 'autoattendmod'; 
        $event->action   = $action;
        $event->url      = $file.$param_str;
        $event->info     = $info;
    }
    
    return $event;
}


