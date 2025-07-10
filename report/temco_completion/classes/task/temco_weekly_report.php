<?php

namespace report_temco_completion\task;

use core\task\scheduled_task;
use core_user;
use report_temco_completion\table\temco_complete;
use report_temco_completion\temco_workbook;
use report_temco_completion\util;

class temco_weekly_report extends scheduled_task {

    public function get_name() {
        return get_string('temcoreport_task', util::COMPONENT);
    }

    public function execute() {
        $timestart = strtotime('last monday midnight');
        $timeend = strtotime('monday -1 second');

        $customdata = ['timestart' => $timestart, 'timeend' => $timeend];
        $temcoreport = new temco_complete('temco', $customdata);

        $filename = 'temco_completion.xlsx';
        $tempdir = make_temp_directory('temco_completion');
        $filepath = $tempdir . DIRECTORY_SEPARATOR . $filename;

        ob_start();
        $temcoreport->out(3000, false);
        util::attach_excel($temcoreport, $filepath);
        ob_end_clean();

        $touser = core_user::get_support_user();
        $touser->email = 'accounts@temco-services.co.uk';
        $fromuser = core_user::get_user(2);

        $subject = get_string('temo_completion:subject', util::COMPONENT);
        $message = get_string('temo_completion:body', util::COMPONENT);
        $touser->mailformat = 1;

        email_to_user($touser, $fromuser, $subject, $message, $message, $filepath, $filename);
        $touser->email = 'nigel@bhivelearning.com';
        email_to_user($touser, $fromuser, $subject, $message, $message, $filepath, $filename);
        remove_dir($tempdir);
    }

    public function get_run_if_component_disabled() {
        return true;
    }
}