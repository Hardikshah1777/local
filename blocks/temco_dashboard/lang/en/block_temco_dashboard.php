<?php

$string['pluginname'] = 'Temco dashboard';
$string['temco_dashboard:myaddinstance'] = 'Temco dashboard';
$string['temco_dashboard:addinstance'] = 'Add Temco dashboard';
$string['temco_dashboard:view'] = 'View Temco dashboard';
$string['userid'] = 'ID';
$string['employeeid'] = 'Employee ID';
$string['fullname'] = 'Fullname';
$string['cohortname'] = 'Location';
$string['coursename'] = 'Course';
$string['duedate'] = 'Due date';
$string['completiondate'] = 'Completion date';
$string['duration'] = 'Duration';


$string['emailtask'] = 'Send Email to students';
$string['sendreminder'] = 'Hey your course has duedate of {$a}';

$string['coursecompletionreminderforstudent'] = 'Send course completion reminder mail to students';

$string['adminmailsubject'] = 'Course completion';
$string['adminmailbody'] = '<p>Dear Head Office team,</p>
<p>{$a->firstname} {$a->lastname} has completed {$a->coursename} on {$a->completiontime}</p>';

$string['twodaymailsubject'] = 'Course completion reminder';
$string['twodaymailbody'] = '<p>Dear {$a->firstname}</p>
<p>This is a quick reminder that the deadline for completing {$a->coursename} is in on {$a->deadline}. I understand that things can get busy, and we want to encourage you to make sure you\'re on track to finish the course by the deadline.</p>
<p>Please feel to contact your manager or supervisor if you have any issues.</p>
<p>Kind regards</p>
<p>Temco Academy Team</p>';

$string['fivedaymailsubject'] = 'Course completion reminder';
$string['fivedaymailbody'] = '<p>Dear {$a->firstname}</p>
<p>This is a quick reminder that the deadline for completing {$a->coursename} is past due. Please login to {$a->url}. to complete your training</p>
<p>Please feel to contact your manager or supervisor if you have any issues.</p>
<p>Kind regards</p>
<p>Temco Academy Team</p>';
