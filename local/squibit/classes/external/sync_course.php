<?php

namespace local_squibit\external;

use context_system;
use external_function_parameters;
use external_value;
use local_squibit\observer;
use local_squibit\utility;

class sync_course extends sync_user {

    public static function execute_parameters() : external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ]);
    }

    public static function execute(int $courseid) {
        global $DB;
        [
                'courseid' => $courseid,
        ] = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);

        $context = context_system::instance();
        self::validate_context($context);

        $result = false;

        if (!empty($courseid)) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!empty($course)) {
                $response = observer::get_syncedcourse($course, true, null, true);
                $result = !empty($response) && $response->status == utility::STATUSES['success'];
            }
        }
        return compact('result');
    }

}
