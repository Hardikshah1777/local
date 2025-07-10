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


class user_update extends local_coact_external {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'firstname' => new external_value(PARAM_NOTAGS, 'firstname', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'lastname' => new external_value(PARAM_NOTAGS, 'lastname', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'email' => new external_value(PARAM_EMAIL, 'email', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'username' => new external_value(PARAM_NOTAGS, 'username', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    public static function execute($firstname, $lastname, $email, $username) : array {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/user/lib.php';
        $params = self::validate_parameters(self::execute_parameters(), compact('firstname', 'lastname', 'email','username'));
        $params = array_map('trim', $params);
        [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'username' => $username

        ] = $params;


        if ($user = core_user::get_user_by_username($username)) {
            $result['userid'] = $user->id;
        }
        else if ($user = core_user::get_user_by_email($email)) {
            $result['userid'] = $user->id;
        }
        if(empty($result['userid'])){
            $result['message'] = get_string('usernotexists', 'local_coact');
        }
        if (!empty($result['userid'])) {
            $user = new stdClass;
            $user->id = $result['userid'];
            $user->username = $username;
            $user->email = $email;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
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
