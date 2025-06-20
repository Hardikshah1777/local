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


class user_create extends local_coact_external {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'firstname' => new external_value(PARAM_NOTAGS, 'firstname', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'lastname' => new external_value(PARAM_NOTAGS, 'lastname', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'email' => new external_value(PARAM_EMAIL, 'email', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'username' => new external_value(PARAM_NOTAGS, 'username', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'cohortid' => new external_value(PARAM_INT, 'cohortid', VALUE_DEFAULT),
        ]);
    }

    public static function execute($firstname, $lastname, $email, $username, $cohortid) : array {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/user/lib.php';
        require_once $CFG->dirroot . '/cohort/lib.php';
        $params = self::validate_parameters(self::execute_parameters(), compact('firstname', 'lastname', 'email','username', 'cohortid'));
        $params = array_map('trim', $params);
        [

            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'username' => $username,
            'cohortid' => $cohortid

        ] = $params;

        $result['userid'] = 0;
        $result['message'] = get_string('userexists', 'local_coact');
        if ($user = core_user::get_user_by_email($email)) {
            $result['userid'] = $user->id;
        } elseif ($user = core_user::get_user_by_username($username)) {
            $result['userid'] = $user->id;
        }

        if (empty($result['userid'])) {
            $user = new stdClass;
            $user->auth = 'manual';
            $user->username = $username;
            $user->email = $email;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->password = '';
            $user->confirmed = 1;
            $user->lang = get_newuser_language();
            $user->id = user_create_user($user, false);

            if (!empty($cohortid) && !empty($user->id)) {
                cohort_add_member($cohortid, $user->id);
            }
            $result['userid'] = $user->id;
            $result['message'] = get_string('usercreatedsuccessfully', 'local_coact');
        }

        return $result;
    }

    public static function execute_returns() : external_single_structure {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'message'),
        ]);
    }

}
