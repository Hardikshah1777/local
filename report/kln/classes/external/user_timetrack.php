<?php

namespace report_kln\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use report_kln\util;

class user_timetrack extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT,'userid'),
            'courseid' => new external_value(PARAM_INT,'courseid'),
            'timespent' => new external_value(PARAM_INT,'Time')
        ]);
    }

    public static function execute($userid, $courseid, $timespent) {
        [
            'userid' => $userid,
            'courseid' => $courseid,
            'timespent' => $timespent
        ] = self::validate_parameters(self::execute_parameters(), compact('userid', 'courseid', 'timespent'));

        $result = util::handle_user_timetrack($userid, $courseid, $timespent);
        return compact('result');
    }

    public static function execute_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'result')
        ]);
    }
}