<?php

function send_email_task()
{
    global $DB;

    $fields = 'u.*, 
    tab.userid';

    $from = '( SELECT ue.userid, e.courseid, MAX(ue.timestart) as duedate FROM {user_enrolments} ue
    JOIN {enrol} e ON e.id = ue.enrolid GROUP BY ue.userid, e.courseid ) tab
    JOIN {user} u ON u.id = tab.userid
    JOIN {course} c ON c.id = tab.courseid AND c.id = 30
    LEFT JOIN {course_completions} cc ON cc.userid = tab.userid AND cc.course = tab.courseid AND cc.timecompleted = 0';

    $where = 'u.suspended = :suspended AND u.deleted = :deleted';

    $params_array = ['suspended' => 0, 'deleted' => 0]; // Replace with actual values

    $records = $DB->get_record_sql("SELECT $fields FROM $from WHERE $where", $params_array);

  
    $course = $records->coursename;
    $duedate = $records->duedate + $records->duration;

    $subject = 'Course Completion Reminder Mail';
    $message = get_string('sendreminder', 'block_temco_dashboard', $duedate);

    $user = '';
    $from = get_admin();
    $subject = '';
    $messagetext = '';
    foreach($records as $user){
    email_to_user($user, $from, $subject, $message);
    }
}
