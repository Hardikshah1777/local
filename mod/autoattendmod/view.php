<?php  

// $Id: view.php,v 1.2 2006/02/01 12:00:47 dlnsk Exp $

/// This page prints a particular instance of autoattendmod
/// (Replace autoattendmod with the name of your module)


require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once($CFG->dirroot.'/blocks/autoattend/locallib.php');


$modid = optional_param('id', 0, PARAM_INT);    // Course Module ID, or
$attid = optional_param('at', 0, PARAM_INT);    // autoattendmod ID

if (($formdata = data_submitted()) and !confirm_sesskey()) {
    jbxl_print_error('invalidsesskey');
}

$urlparams['id'] = $modid;
$urlparams['at'] = $attid;
$PAGE->set_url('/mod/autoattendmod/view.php', $urlparams);


if ($modid) {
    //
    $cm = $DB->get_record('course_modules', array('id'=>$modid));
    if (!$cm) {
        jbxl_print_error('modidincorrect', 'autoattendmod');
    }
    $course = $DB->get_record('course', array('id'=>$cm->course));
    if (!$course) {
        jbxl_print_error('misconfigured', 'autoattendmod');
    }
    $autoattendmod = $DB->get_record('autoattendmod', array('id'=>$cm->instance));
    if (!$autoattendmod) {
        jbxl_print_error('modincorrect', 'autoattendmod');
    }
    //
    $event = autoattendmod_get_event($cm, 'view', $urlparams);
    jbxl_add_to_log($event);
} 

//
else if ($attid) {
    //
    $autoattendmod = $DB->get_record('autoattendmod', array('id'=>$attid));
    if (!$autoattendmod) {
        jbxl_print_error('modidincorrect', 'autoattendmod');
    }
    $course = $DB->get_record('course', array('id'=>$autoattendmod->course));
    if (!$course) {
        jbxl_print_error('misconfigured', 'autoattendmod');
    }
    $cm = get_coursemodule_from_instance('autoattendmod', $autoattendmod->id, $course->id);
    if (!$cm) {
        jbxl_print_error('modincorrect', 'autoattendmod');
    }
}

//
else {
    jbxl_print_error('modincorrect', 'autoattendmod');
}


require_login($course->id, true, $cm);

$context   = jbxl_get_course_context($course->id);
$isstudent = jbxl_is_student($USER->id, $context);

// for Student
if ($isstudent) {
    $userid = $USER->id;
    $classinfo = autoattend_get_user_class($userid, $course->id);
    //
    if ($classinfo->classid>=0) {
        $ntime = time();
        $sessions = autoattend_get_nowopen_sessions($course->id, $userid, 'S', $ntime);                // get semiauto sessions
        if ($sessions) {
            foreach ($sessions as $session) {
                $session = autoattend_update_session_state($course->id, $session, $ntime, false);    // not regist student
                if ($session) {
                     if ($session->classid==0 or $session->classid==$classinfo->classid) {
                        // email key
                        if ($session->prv_state!='O' and $session->state=='O' and $session->method=='S') {
                            if (autoattend_is_email_enable($course->id)) {
                                autoattend_email2teachers_key($session);
                            }
                        }
                        //
                        $student = $DB->get_record('autoattend_students', array('attsid'=>$session->id, 'studentid'=>$userid));
                        if (empty($student)) {
                            $student = autoattend_add_user_insession($session->id, $userid);
                        }
                        if ($student and $student->status=='Y') {
                            redirect($CFG->wwwroot.'/blocks/autoattend/semiautoattend.php?course='.$course->id.'&amp;attsid='.$session->id);
                        }
                    }
                }
            }
        }
    }
}

redirect($CFG->wwwroot.'/blocks/autoattend/index.php?course='.$course->id);

