<?php

namespace local_squibit\task;

use core\task\scheduled_task;
use core_user;
use local_squibit\syncreport_workbook;
use local_squibit\utility;
use stdClass;

class sync_daily_report extends scheduled_task {

    public function get_name() {
        return get_string('squibitdailyreport', utility::COMPONENT);
    }

    public function execute() {
        global $CFG;
        require_once $CFG->libdir.'/excellib.class.php';

        if (!utility::is_enabled()) {
            return false;
        }

        $settings = get_config(utility::COMPONENT);
        if (empty($settings->senderemail) || empty($settings->checksendmail)) {
            return false;
        }

        $emails = [];
        if (strpos($settings->senderemail, ',') !== false) {
            $emails = explode(',', $settings->senderemail);
        }else{
            $emails[] = $settings->senderemail;
        }

        $timestart = strtotime('midnight -1 day');
        $timeend = strtotime('midnight -1 second');

        $squibitusers = utility::get_sync_user_log($timestart, $timeend);
        $squibitcourses = utility::get_sync_course_log($timestart, $timeend);

        $failed = array_keys(utility::STATUSES)[2];

        $userheaders = ['firstname', 'lastname', 'email', 'status', 'date', 'response'];
        $courseheaders = ['coursename', 'status', 'date', 'response'];

        $time = userdate(time(), '%m-%d-%Y');
        $temp = make_temp_directory('squibitreport');

        // For Error Report
        $errorfilename = get_string('squibiterrorfilename', utility::COMPONENT, $time) . '.xlsx';
        $errorfilepath = $temp . DIRECTORY_SEPARATOR . $errorfilename;

        $errorexcel = new syncreport_workbook($errorfilename);
        utility::excel_generate_data($errorexcel, utility::SYNCTYPE[0], $failed, $userheaders, (object) $squibitusers['error']);
        utility::excel_generate_data($errorexcel, utility::SYNCTYPE[1], $failed, $courseheaders, (object) $squibitcourses['error']);
        $errorexcel->close();

        $touser = $from = core_user::get_support_user();

        $site = get_site();
        $a = new stdClass();
        $a->sitename = $site->fullname;
        $a->siteurl = $CFG->wwwroot;

        $errorsubject = get_string('mail:errorsubject', utility::COMPONENT, $a);
        $errormessage = get_string('mail:errorbody', utility::COMPONENT, $a);

        foreach ($emails as $email){
            if (validate_email($email)){
                $touser->email = $email;
                $touser->mailformat = 1;
                email_to_user($touser, $from, $errorsubject, $errormessage, $errormessage, $errorfilepath, $errorfilename);
            }
        }
        remove_dir($temp);
        return true;
    }
}