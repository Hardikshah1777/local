<?php

namespace local_squibit\external;

use core\task\manager;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_squibit\task\delete_all_users AS usertask;
use local_squibit\task\delete_all_courses AS coursetask;

class sync_all_reset extends external_api {

    public static function execute_parameters() : external_function_parameters {
        return new external_function_parameters([
                'action' => new external_value(PARAM_TEXT, 'action'),
        ]);

    }

    public static function execute(string $action) {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['action' => $action]);

        $result = false;
        $usercount = $coursecount = 0;

        $usertask = new usertask;
        $coursetask = new coursetask;

        $courserecords = manager::get_adhoc_tasks(coursetask::class);
        $userrecords = manager::get_adhoc_tasks(usertask::class);
        if (!empty($courserecords) || !empty($userrecords)) {
            foreach ($courserecords as $courserecord) {
                $coursecount += $courserecord->get_custom_data()->count;
                break;
            }
            foreach ($userrecords as $userrecord) {
                $usercount += $userrecord->get_custom_data()->count;
                break;
            }
            $result = false;
        } else if (isset($params['action']) && !empty($params['action'])){


            $coursetask->prepare()->set_userid($USER->id);
            manager::reschedule_or_queue_adhoc_task($coursetask);
            $coursecount = $coursetask->get_custom_data()->count;

            $usertask->prepare()->set_userid($USER->id);
            manager::reschedule_or_queue_adhoc_task($usertask);
            $usercount = $usertask->get_custom_data()->count;
            $result = true;
        }

        return ['result' => $result, 'usercount' => $usercount, 'coursecount' => $coursecount];
    }

    public static function execute_returns() : external_single_structure {
        return new external_single_structure([
                'result' => new external_value(PARAM_BOOL, 'result'),
                'usercount' => new external_value(PARAM_INT, 'user count remaining'),
                'coursecount' => new external_value(PARAM_INT, 'course count remaining'),
        ]);
    }

}