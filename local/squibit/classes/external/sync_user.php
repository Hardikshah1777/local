<?php

namespace local_squibit\external;

use context_system;
use core_user;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_squibit\observer;
use local_squibit\utility;

class sync_user extends external_api {

    public static function execute_parameters() : external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'userid'),
        ]);
    }

    public static function execute(int $userid) {
        [
                'userid' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);

        $context = context_system::instance();
        self::validate_context($context);

        $result = false;

        if (!empty($userid)) {
            $user = core_user::get_user($userid);
            if (!empty($user)) {
                $response = observer::get_synceduser($user, true, true);
                $result = !empty($response) && $response->status == utility::STATUSES['success'];
            }
        }
        return compact('result');
    }

    public static function execute_returns() : external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
        ]);
    }

}
