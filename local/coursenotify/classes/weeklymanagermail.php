<?php

namespace local_coursenotify;

use core\task\scheduled_task;
use core_user;

class weeklymanagermail extends scheduled_task {

    public function get_name() {
        return get_string('weeklycoursecompleteduser', 'local_coursenotify');
    }

    public function execute() {
        global $DB, $CFG;
        require $CFG->dirroot . '/local/coursenotify/classes/weeklyemailmanager.php';
        $startdate = strtotime('midnight -7 day');
        $enddate = strtotime('midnight -1 second');

        $filename = 'course_completion_' . time() . '.xlsx';
        $courses = $DB->get_records_sql('SELECT c.id,c.fullname FROM {course} c WHERE c.visible = 1 AND c.id > 1');
        $users = $DB->get_records_sql('SELECT * FROM {course_completions} cc WHERE cc.timecompleted >' . $startdate .' AND cc.timecompleted < '.$enddate);
        $filepath = '';

        $tempdir = make_temp_directory('excelreport');

        $filepath = $tempdir . '/' . $filename;

        $excel = new weeklyemailmanager($filename);

        foreach ($courses as $course => $key) {
            $header = 0;
            $i = 0;
            $row = 1;
            foreach ($users as $user) {
                if ($user->timecompleted > $startdate) {
                    if ($user->course === $key->id) {
                        if($header == 0){
                            $excelwrite = $excel->add_worksheet($key->fullname);
                            $excelwrite->write(0, $i++, 'Firstname');
                            $excelwrite->write(0, $i++, 'Lastname');
                            $excelwrite->write(0, $i++, 'Email');
                            $excelwrite->write(0, $i++, 'Grade');
                            $excelwrite->write(0, $i++, 'Completed');
                            $header++;
                        }
                        $i = 0;
                        $gradeparams = [];
                        $gradeparams['courseid'] = $user->course;
                        $gradeparams['course'] = 'course';
                        $gradeparams['userid'] = $user->userid;
                        $gradesql =
                                'Select gg.id,gg.*,gi.* from {grade_grades} gg JOIN {grade_items} gi  ON gi.id = gg.itemid  Where gi.courseid =:courseid and gi.itemtype =:course AND gg.userid =:userid ';
                        $gradeinfo = $DB->get_record_sql($gradesql, $gradeparams);
                        if ($gradeinfo) {
                            $per = ($gradeinfo->finalgrade / $gradeinfo->rawgrademax) * 100 . '%';
                        } else {
                            $per = '-';
                        }
                        $getuser = core_user::get_user($user->userid);
                        $firstname = $getuser->firstname;
                        $lastname = $getuser->lastname;
                        $email = $getuser->email;
                        $timecompleted = userdate($user->timecompleted, get_string('strftimedate', 'core_langconfig'));
                        $excelwrite->write_string($row, $i++, $firstname);
                        $excelwrite->write_string($row, $i++, $lastname);
                        $excelwrite->write_string($row, $i++, $email);
                        $excelwrite->write_string($row, $i++, $per);
                        $excelwrite->write_string($row, $i++, $timecompleted);
                        $row++;
                    }
                }
            }
            $excel->close($filepath);
        }

        $touser = core_user::get_user(2);
        $fromuser = core_user::get_support_user();
        $subject = get_string('weeklycompletedsubject', 'local_coursenotify');
        $message = get_string('weeklycompletedmsg', 'local_coursenotify');
//        email_to_user($touser, $fromuser, $subject, $message, $message, $filepath, $filename);
    }
}