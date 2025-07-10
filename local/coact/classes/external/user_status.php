<?php
namespace local_coact\external;

use context_course;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use core_user;
use stdClass;
use local_coact_external;


class user_status extends local_coact_external {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'username' => new external_value(PARAM_NOTAGS, 'username', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'status' => new external_value(PARAM_INT, 'status', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    public static function execute($username,$status) : array {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/user/lib.php';
        $params = self::validate_parameters(self::execute_parameters(), compact('username', 'status'));
        $params = array_map('trim', $params);
        [
            'username' => $username,
            'status' => $status

        ] = $params;


        if ($user = core_user::get_user_by_username($username)) {
            $result['userid'] = $user->id;
        }
        if(empty($result['userid'])){
            $result['message'] = get_string('usernotexists', 'local_coact');
        }
        if (!empty($result['userid'])) {
            $user = new stdClass;
            $user->id = $result['userid'];
            $user->suspended = $status;
            $user->lang = get_newuser_language();
            user_update_user($user, false);
            $result['userid'] = $user->id;
            $result['message'] = get_string('userupdatedsuccessfully', 'local_coact');
        }
        return $result;
    }

    public static function execute_returns() : external_single_structure {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'message'),
        ]);
    }

}
