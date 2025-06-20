<?php

$string['pluginname'] = 'Course Notification';
$string['coursenotify:editnotification'] = 'Edit Course notfication';
/*edit*/
$string['edittitle'] = 'Edit Course notification';
$string['insertmsg'] = 'Course notification added successfully';
$string['updatemsg'] = 'Course notification updated successfully';
/*index*/
$string['indextitle'] = 'Add / Edit Course notification';
$string['add'] = '+ Add';
/*lib*/
$string['coursenotification'] = 'Course notifications';
/*notificationlist*/
$string['th:title'] = 'Title';
$string['th:subject'] = 'Subject';
$string['th:threshold'] = 'Threshold';
$string['th:status'] = 'Status';
$string['th:action'] = 'Action';
$string['editmessage'] = 'Edit Course notification for \'{$a}\'';
$string['immediately'] = 'Immediately';
/*editform*/
$string['formheader'] = 'Edit Course notification';
$string['title'] = 'Title';
$string['subject'] = 'Subject';
$string['status'] = 'Status';
$string['message'] = 'Message';
$string['threshold'] = 'Threshold';
$string['expirynotify'] = 'Notify user';
/*notifytask*/
$string['notifytask'] = 'Send Course notifications';
$string['teachersubject'] = 'Enrolment expiry notification';
$string['teachermessage'] =
        'Dear {$a->teachername}, {$a->studentname} has enrolment in "{$a->coursename}" expiring on {$a->expirydate}';
/*utility*/
$string['before'] = 'before';
$string['after'] = 'after';
$string['startdate'] = 'start date';
$string['enddate'] = 'end date';
$string['student'] = 'Student';
$string['both'] = 'Student and Teacher';

// Enrol User mail notify
$string['mail:subject'] = 'Assigned Training(s)';
$string['mail:subjectreminder'] = 'Reminder to Complete Training(s)';
$string['mail:message'] = '<p style="text-align: center;margin: 0 !important;padding: 0 !important;">
<img src="{$a->sitelogo}" alt="EU"  style="width: 17%;" /></p>
<p style="text-align: center;margin: 0 !important;padding: 0 !important;"><strong style="font-size: 15px;">Elite Parking Services</strong></p>
<p>Dear {$a->firstname} {$a->lastname}, </p><p>You’ve been assigned new training(s). Please use the link below to complete your assigned training(s) by (1 week out from date assigned). </p>
<p><a href="{$a->courseurl}" target="_blank">{$a->coursename}</a></p>
<p>You can also access and complete your assigned training(s) by logging in to Elite University and navigating to "My eCourses" located on the top menu bar of your home page.</p>
<p><a href="{$a->siteurl}" target="_blank">{$a->siteurl}</a></p><p>Thank you, </p>
<p>Elite Parking Services </p><p><img src="{$a->sitelogo}" alt="EU"  style="width: 11%;" /></p>';

// task enrol user after 1 week
$string['notifyenroltask'] = 'Notify enrol task';

// Course completaion mail
$string['ccsubject'] = 'Course Completion';
$string['ccmsgbody'] = '<p>{$a->firstname}<br> Complete {$a->coursename} with <b>{$a->per}</b></p>';

// Weekly mail Course completed
$string['weeklycoursecompleteduser'] = 'Weekly course completed userlist - Admin mail';
$string['weeklycompletedsubject'] = 'Users course completion';
$string['weeklycompletedmsg'] = 'Last week users course completion report';

// Course not completed userlist
$string['weeklycoursenotcompleteduser'] = 'Weekly course uncompleted userlist - Admin mail';
$string['weeklynotcompletedsubject'] = 'Courses not completed by users';
$string['weeklynotcompletedmsg'] = 'List of not completed courses by users';
