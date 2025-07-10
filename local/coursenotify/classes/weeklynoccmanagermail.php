<?php

namespace local_coursenotify;

use core\task\scheduled_task;
use core_user;

class weeklynoccmanagermail extends scheduled_task {

    public function get_name() {
        return get_string('weeklycoursenotcompleteduser', 'local_coursenotify');
    }

    public function execute() {
        global $DB;

        $courses = $DB->get_records_sql('SELECT c.id,c.fullname FROM {course} c WHERE c.visible = 1 AND c.id > 1');

        $filename = 'Userlist_' . time() . '.xlsx';
        $tempdir = make_temp_directory('excelreport');
        $filepath = $tempdir . '/' . $filename;

        $excel = new weeklyemailmanager($filename);

        foreach ($courses as $course) {
            $sql =
                    'SELECT ue.*,e.courseid FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid WHERE e.courseid = :courseid AND ue.status = 0 AND e.status = 0 AND ue.userid > 2';
            $users = $DB->get_records_sql($sql, ['courseid' => $course->id]);
            $header = 0;
            $i = 0;
            $row = 1;
            foreach ($users as $user) {
                if ($course->id === $user->courseid) {
                    $recordparam['courseid'] = $user->courseid;
                    $recordparam['userid'] = $user->userid;
                    $sql = 'SELECT * FROM {course_completions} cc WHERE cc.course = :courseid AND cc.userid = :userid';
                    $record = $DB->get_record_sql($sql, $recordparam);
                    if (empty($record->timecompleted)) {
                        if ($header == 0) {
                            $excelwriter = $excel->add_worksheet($course->fullname);
                            $excelwriter->write(0, $i++, 'Firstname');
                            $excelwriter->write(0, $i++, 'Lastname');
                            $excelwriter->write(0, $i++, 'Email');
                            $header++;
                        }
                        $i = 0;
                        $getuser = core_user::get_user($user->userid);
                        $excelwriter->write($row, $i++, $getuser->firstname);
                        $excelwriter->write($row, $i++, $getuser->lastname);
                        $excelwriter->write($row, $i++, $getuser->email);
                        $row++;
                    }
                }
            }
            $excel->close($filepath);
        }
        $touser = core_user::get_user(2);
        $from = core_user::get_support_user();
        $subject = get_string('weeklynotcompletedsubject', 'local_coursenotify');
        $msg = get_string('weeklynotcompletedmsg', 'local_coursenotify');
        email_to_user($touser, $from, $subject, $msg, $msg, $filepath, $filename);
    }
}