<?php

namespace local_coursenotify;

use core\task\scheduled_task;
use local_coursenotify\utility;
use core_user;
use context_course;
use core_course_list_element;

class notifytask extends scheduled_task
{
    public function get_name()
    {
        return get_string('notifytask', utility::$component);
    }

    public function execute()
    {
        global $DB, $CFG;
        require_once $CFG->libdir . '/filelib.php';
        $sql = "SELECT ue.*, e.courseid, c.fullname, 
                    u.firstname,u.lastname,u.email, 
                    n.id as notificationid,n.subject,n.message,n.expirynotify
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.status = :enabled AND e.enrol = :ename)
                  JOIN {course} c ON (c.id = e.courseid AND c.visible = 1)                
                  JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0 AND u.suspended = 0)
                  JOIN {local_coursenotify} n ON n.courseid = c.id AND n.status = 1 
                  LEFT JOIN {local_coursenotify_track} t ON t.notificationid = n.id AND t.userid = u.id AND t.timesent > ue.timecreated
                 WHERE ue.status = :active AND t.id IS NULL 
                AND (
                    (n.beforeafter = :notifyimmediate AND ue.timecreated >= n.timecreated)
                        OR
                    (n.beforeafter != :notifyimmediate2 AND (
                        (n.refdate = :notifystartdate AND ue.timestart < (:now1 + n.beforeafter * n.threshold) AND ABS(ue.timestart - :now3) <= (n.threshold + :mailthreshold1) AND ue.timestart > 0)
                         OR 
                        (n.refdate = :notifyenddate AND ue.timeend < (:now2 + n.beforeafter * n.threshold) AND ABS(ue.timeend - :now4) <= (n.threshold + :mailthreshold2) AND ue.timeend > 0)
                        )
                    )
                )
            ORDER BY ue.enrolid ASC, u.firstname ASC, u.lastname ASC";
        /*"AND (
           (n.beforeafter = :notifybefore AND (
            (n.refdate = :notifystartdate AND ue.timestart < (:now1 + n.threshold) AND ue.timestart > 0)
            OR
            (n.refdate = :notifyenddate AND ue.timeend < (:now2 + n.threshold) AND ue.timeend > 0)
            ))
            OR
           (n.beforeafter = :notifyafter AND (
            (n.refdate = :notifystartdate2 AND ue.timestart < (:now3 - n.threshold) AND ue.timestart > 0)
            OR
            (n.refdate = :notifyenddate2 AND ue.timeend < (:now4 - n.threshold) AND ue.timeend > 0)
            ))
        )".*/
        $params = array(
            'enabled' => ENROL_INSTANCE_ENABLED,
            'active' => ENROL_USER_ACTIVE,
            'ename' => 'manual',
        );
        $params['now1'] = $params['now2'] = $params['now3'] = $params['now4'] = time();
        $params['notifybefore'] = LOCAL_COURSENOTIFY_BEFORE;
        $params['notifyafter'] = LOCAL_COURSENOTIFY_AFTER;
        $params['notifystartdate'] = LOCAL_COURSENOTIFY_STARTDATE;
        $params['notifyenddate'] = LOCAL_COURSENOTIFY_ENDDATE;
        $params['notifyimmediate'] = $params['notifyimmediate2'] = LOCAL_COURSENOTIFY_IMMEDIATE;
        $params['mailthreshold1'] = $params['mailthreshold2'] = 2 * DAYSECS;

       // echo $sql;
        $rs = $DB->get_recordset_sql($sql, $params);
        $notificationtrack = array();
        $teachersarr = array();
        $support = core_user::get_support_user();
        $courses = get_courses('all', 'c.sortorder ASC', 'c.id,c.fullname');
        foreach ($rs as $ue) {
            mtrace('sending notification ' . $ue->notificationid . ' to user ' . $ue->userid . ' in course ' . $ue->courseid);
            $coursecontext = context_course::instance($ue->courseid);
            if (empty($notificationtrack[$ue->notificationid]))
                $notificationtrack[$ue->notificationid] = file_rewrite_pluginfile_urls($ue->message, 'pluginfile.php',
                    $coursecontext->id, utility::$component, utility::$filearea, $ue->notificationid);
            $notification = $notificationtrack[$ue->notificationid];
            $user = (object)array(
                'id' => $ue->userid,
                'firstname' => $ue->firstname,
                'lastname' => $ue->lastname,
                'email' => $ue->email,
                'mailformat' => FORMAT_HTML,
            );
            if($notification) $notification .= '<br/><br/>';
            if (email_to_user($user, $support, $ue->subject, $notification, $notification)) {
                if ($ue->expirynotify == LOCAL_COURSENOTIFY_BOTH) {
                    if (empty($teachersarr[$ue->courseid])) {
                        $list = new core_course_list_element($courses[$ue->courseid]);
                        $teacherids = array();
                        foreach ($list->get_course_contacts() as $contactarr) {
                            $teacherids[] = $contactarr['user']->id;
                        }
                        $teachersarr[$ue->courseid] = $DB->get_records_list('user', 'id', $teacherids);
                    }
                    $subject = get_string('teachersubject', utility::$component);
                    $a = array(
                        'studentname' => fullname($user),
                        'coursename' => $courses[$ue->courseid]->fullname,
                        'expirydate' => userdate($ue->timeend, get_string('strftimedate')),
                    );
                    mtrace('sending notification ' . $ue->notificationid . ' to teacher ' . join(',',array_keys($teachersarr[$ue->courseid])) . ' in course ' . $ue->courseid);
                    foreach ($teachersarr[$ue->courseid] as $teacher) {
                        $a['teachername'] = fullname($teacher);
                        $message = get_string('teachermessage', utility::$component, $a);
                        if($message) $message .= '<br/><br/>';
                        $teacher->mailformat = FORMAT_HTML;
                        email_to_user($teacher, $support, $subject, $message, $message);
                    }
                }
                $record = array(
                    'notificationid' => $ue->notificationid,
                    'userid' => $ue->userid,
                );
                if($existing = $DB->get_record('local_coursenotify_track',$record)){
                    $existing->timesent = time();
                    $DB->update_record('local_coursenotify_track',$existing);
                }else{
                    $record['timesent'] = time();
                    $DB->insert_record('local_coursenotify_track',(object) $record);
                }
            }
        }
        $rs->close();
    }
}
