<?php

namespace local_authtimer;

use context;
use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;

class external extends external_api {
    public static function authenticate($contextid, $authcode) {
        global $USER;
        $params = self::validate_parameters(self::authenticate_parameters(), [
                'contextid' => $contextid,
                'authcode' => $authcode,
        ]);
        $contextid = $params['contextid'];
        $authcode = $params['authcode'];
        $context = context::instance_by_id($contextid);
        self::validate_context($context);

        $nexttick = 0;
        if ($success = auth::validate($authcode)) {
            get_string('verified', auth::component);
            $nexttick = auth::get_nextslottime();
            event\auth_succeded::create_from_userid($USER->id)->trigger();
        } else {
            get_string('wrongcode', auth::component);
            event\auth_failed::create_from_userid($USER->id)->trigger();
        }
        return compact('success', 'nexttick');
    }

    public static function authenticate_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'Context ID', VALUE_REQUIRED),
                'authcode' => new external_value(PARAM_RAW, 'Code', VALUE_REQUIRED),
        ]);
    }

    public static function authenticate_returns() {
        return new external_single_structure([
                'success' => new external_value(PARAM_BOOL, 'success'),
                'nexttick' => new external_value(PARAM_INT, 'nexttick'),
        ]);
    }

    public static function mail($contextid) {

        $params = self::validate_parameters(self::mail_parameters(), [
                'contextid' => $contextid,
        ]);
        $contextid = $params['contextid'];

        $context = context::instance_by_id($contextid);
        self::validate_context($context);

        return [
                'success' => auth::mail(),
        ];
    }

    public static function mail_parameters() {
        return new external_function_parameters([
                'contextid' => new external_value(PARAM_INT, 'Context ID', VALUE_REQUIRED),
        ]);
    }

    public static function mail_returns() {
        return new external_single_structure([
                'success' => new external_value(PARAM_BOOL, 'success'),
        ]);
    }
}
