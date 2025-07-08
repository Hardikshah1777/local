<?php
$string['autoattendmod:addinstance'] = 'Add a new auto attendance module';
$string['autoattendmod:view'] = 'View module';
$string['autoattendmod:viewreports'] = 'View reports';
$string['autoattendmod:receivemail'] = 'Receive email notification';

$string['use_item'] = 'use {$a}';
$string['username_manage'] = 'Management of Username';
$string['username_manage_help'] = 'You can select displied name pattern in this module.';

$string['pluginname'] = 'Auto Attendance Module';
$string['pluginadministration'] = 'Auto Attendance Module Admin';

$string['name'] = 'Name';
$string['description'] = 'Description';

$string['modulename'] = 'Auto Attendance';
$string['modulename_help'] = 'You can add only one module AutoAttendance per course. If you remove this module it don\'t affect on attendances! <br />
You can use this module only together with block AutoAttendance. Please add an autoattendand block before adding this module.<br />
This module need for semi-automatic attendance and include grades of attendances to main course\'s grades.';
$string['modulenameplural'] = 'Autoattend';
$string['modulenamesimple'] = 'Autoattend';

$string['misconfigured']  = 'Course is misconfigured';
$string['modidincorrect'] = 'Course Module ID was incorrect';
$string['modincorrect']   = 'Course module is incorrect<br />It is possible that Auto Attenndance Block is not installed';

$string['session_num'] = 'The number of sessions';

$string['autoattendmod_options'] = 'Options of Auto Attendance module';

$string['disp_info'] = 'Display information on the most soon or ongoing class in the block.';
$string['disp_info_title'] = 'Attendance Information in the block';
$string['disp_info_help'] = 'Display the key (case of semi-automatic) and number of attendees for the most soon or ongoing class in the block';
$string['pre_disptime'] ='Display of the most soon class information';
$string['pre_disptime_title'] ='Grace time before class start (m)';
$string['pre_disptime_help'] ='If the block displays information on the most soon or ongoing class, specify how many minutes before the class starts.';

$string['permit_homeroom_title'] = 'For Homeroom Module';
$string['permit_homeroom'] = 'Permit access from Homeroom Module';
$string['permit_homeroom_help'] = 'If you check off this, Homeroom module does not access this autoattend module/block';
$string['disp_localhostname_title'] = 'Display Local Hostname';
$string['disp_localhostname'] = 'Display hostname instead of local IP address';
$string['disp_localhostname_help'] = 'If the IP address is local, display local host name instead of IP address';
$string['feedback_title'] = 'Feedback Link';
$string['feedback_disp'] = 'Display Feedback link';
$string['feedback_disp_help'] = 'If you check off this, links to the Feedback are not displayed';
$string['email_enable_title'] = 'Notification Result to Teachers';
$string['email_enable'] = 'Enable e-mail notification of attenance data to Teachers';
$string['email_enable_help'] = 'If you check on this, when a session ended, attendance data is sent to teachers using e-mail. However, you must add "Teacher" to the autoattendmod:receivemail capability.';
$string['email_allrep_title'] = 'Send all Reports';
$string['email_allrep'] = 'Send all reports to teachers by e-mail';
$string['email_allrep_help'] = 'If e-mail is enable and you check on this, all days attendances reports are sent by e-mail. And you check off this, only one attendance report is sent';
$string['email_key_title'] = 'Notification Keyword to Teachers';
$string['email_key'] = 'Enable e-mail notification of attendance keyword to Teachers';
$string['email_key_help'] = 'If you check on this, when a session started, attendance keyword is sent to teachers using e-mail. However, you must add "Teacher" to the autoattendmod:receivemail capability.';
$string['email_user_title'] = 'Notification to Students';
$string['email_user'] = 'Enable e-mail notification of attendance to Students';
$string['email_user_help'] = 'If you check on this, when attendance of student changes, notification is sent to student using e-mail. However, you must add "Student" to the autoattendmod:receivemail capability.';
$string['backup_block_title'] = 'Backup of Attendance Data';
$string['backup_block'] = 'When this course is backupped, attendance data is backedupped that Autoattendance block maneged.';
$string['backup_block_help'] = 'In this version, Autoattendance block manages attendance data. Even if you delete a autoattendmod, attendamce data of the course remains on the database.';
$string['excelver_title'] = 'Use old Excel2007';
$string['excelver_disp']  = 'Use old Excel (Excel2007) format when downloading';
$string['excelver_disp_help'] = 'If you fail to download Excel Report, please check this.';
$string['summertime_title'] = 'Use Summer Time';
$string['summertime_disp']  = 'Summer Time can be set at sessions.';
$string['summertime_disp_help'] = 'You can set Summer Time st some sessions. ';

$string['firstinstallblock']  = 'Please install the Auto Attendance Block first.';
$string['firstinstanceblock'] = 'Please create an instance of Auto Attendance Block first in this course.';
$string['onlyonemodule'] = 'Only one Auto Attendance Module instance can be created per one course.';

