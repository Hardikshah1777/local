<?php

namespace local_squibit;

use moodle_exception;
use stdClass;

class squibitapi {

    /**
     * @var int|null
     */
    protected static $userid;

    protected static $courseid;

    public static function set_userid(int $userid = 0) {
        self::$userid = $userid;
    }

    public static function isset_userid() : bool {
        return !is_null(self::$userid);
    }

    public static function set_courseid(int $courseid = 0) {
        self::$courseid = $courseid;
    }

    public static function isset_courseid() : bool {
        return !is_null(self::$courseid);
    }

    public static function create_conference_user($newuser, array $courseids = []) {
        $id = self::get_user_unique_id($newuser->id);

        $params['cuser_first_name'] = $newuser->firstname;
        $params['cuser_last_name'] = $newuser->lastname;
        $params['cuser_email'] = $newuser->email;
        $params['cuser_description'] = $newuser->description;
        $params['role'] = $newuser->role;
        if (!empty($newuser->country)) {
            $params['country_code'] = $newuser->country;
        }
        if (!empty($newuser->phone1)) {
            $params['cuser_contact_number'] = $newuser->phone1;
        } else if (!empty($newuser->phone2)) {
            $params['cuser_contact_number'] = $newuser->phone2;
        }
        if (!empty($courseids)) {
            $params['courses'] = $courseids;
        }

        if (empty($params['cuser_contact_number'])) {
            unset($params['country_code']);
        }

        $params['cuser_unique_id'] = $id;

        self::set_userid($newuser->id);
        return self::call_api('post', '/create-conference-user', $params);
    }

    public static function update_conference_user($user, array $courseids = []) {
        $id = self::get_user_unique_id($user->id);

        $params['cuser_first_name'] = $user->firstname;
        $params['cuser_last_name'] = $user->lastname;
        $params['cuser_email'] = $user->email;
        $params['cuser_description'] = $user->description;
        $params['role'] = $user->role;
        $params['cuser_unique_id'] = $id;
        $params['previous_role_id'] = $user->previousroleid;

        if (!empty($user->country)) {
            $params['country_code'] = $user->country;
        }
        if (!empty($user->phone1)) {
            $params['cuser_contact_number'] = $user->phone1;
        } else if (!empty($user->phone2)) {
            $params['cuser_contact_number'] = $user->phone2;
        }
        if (!empty($courseids)) {
            $params['courses'] = $courseids;
        }

        if (empty($params['cuser_contact_number'])) {
            unset($params['country_code']);
        }

        self::set_userid($user->id);
        return self::call_api('post', '/update-conference-user/' . $id, $params);
    }

    public static function delete_conference_user($userid) {
        $id = self::get_user_unique_id($userid);
        self::set_userid($userid);
        return self::call_api('get', '/delete-conference-user/' . $id, ['user_unique_id' => $id]);
    }

    public static function create_course(stdClass $course, array $teachers = [], array $students = []) {
        global $USER;
        self::set_userid($USER->id);
        $params['course_name'] = $course->fullname;
        $params['course_id'] = $course->id;
        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $params['course_teacher'][] = $teacher;
            }
        }
        if (!empty($students)) {
            foreach ($students as $student) {
                $params['course_student'][] = $student;
            }
        }
        self::set_courseid($course->id);
        return self::call_api('post', '/create-course', $params);
    }

    public static function update_course(stdClass $course, array $teachers = [], array $students = []) {
        global $USER;
        self::set_userid($USER->id);
        $params['course_name'] = $course->fullname;
        $params['course_id'] = $course->id;

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $params['course_teacher'][] = $teacher;
            }
        }
        if (!empty($students)) {
            foreach ($students as $student) {
                $params['course_student'][] = $student;
            }
        }
        self::set_courseid($course->id);
        return self::call_api('post', '/update-course/' . $course->id, $params);
    }

    public static function delete_course($id) {
        global $USER;
        self::set_userid($USER->id);
        self::set_courseid($id);
        return self::call_api('get', '/delete-course/' . $id, ['course_id' => $id]);
    }

    public static function get_user_unique_id($userid) {
        return str_pad($userid, 6, 0, STR_PAD_LEFT);
    }

    public static function get_token(bool $includeprefix = true): ?string {
        $now = time();
        $settings = get_config('local_squibit');
        if (empty($settings->host) || empty($settings->apikey) ||
            empty($settings->email) || empty($settings->password) ||
            empty($settings->status)) {
            return null;
        }
        if (!empty($settings->tokenresponse)) {
            $tokenresponse = json_decode($settings->tokenresponse);
            if (empty($tokenresponse->expires_at)) {
                return null;
            }
            if (json_last_error() === JSON_ERROR_NONE) {
                $expirytime = strtotime($tokenresponse->expires_at);
                if ($expirytime > $now) {
                    if ($includeprefix) {
                        return "{$tokenresponse->token_type} {$tokenresponse->access_token}";
                    }
                    return $tokenresponse->access_token;
                }
            }
        }
        $responseinfo = self::call_api('post', 'login', [
            'email' => trim($settings->email),
            'password' => trim($settings->password),
        ], [
            'Authorization' => trim($settings->apikey)
        ], false);
        if (empty($responseinfo) || empty($responseinfo['access_token'])) {
            return null;
        }
        unset($responseinfo['data']);
        set_config('tokenresponse', json_encode($responseinfo), 'local_squibit');
        return self::get_token($includeprefix);
    }

    public static function call_api(string $method, string $url, ?array $params = null,
        ?array $headers = null, bool $includetoken = true): array {
        global $DB;

        $now = time();
        $url = trim($url, DIRECTORY_SEPARATOR);
        $method = strtolower($method);
        $settings = get_config('local_squibit');
        $host = trim($settings->host, DIRECTORY_SEPARATOR);
        $endpoint = $host . DIRECTORY_SEPARATOR . $url;
        $settings = get_config('local_squibit');

        if (!utility::is_enabled()) {
            throw new moodle_exception('syncdisabled', 'local_squibit');
        }

        if (!method_exists(curl::class, $method)) {
            throw new moodle_exception('methodnotallowed', 'local_squibit', '', $method);
        }

        $formattedheaders[] = 'Accept: application/json';

        if ($includetoken) {
            if (!$token = self::get_token()) {
                return [];
            }
            $formattedheaders[] = 'Authorization: ' . $token;
        }

        if (!is_null($headers)) {
            $isnatural = self::is_natural($headers);
            foreach ($headers as $key => $header) {
                if (!$isnatural) {
                    $header = "{$key}: {$header}";
                }
                $formattedheaders[] = $header;
            }
        }

        if ($method === 'get') {
            if (empty($params)) {
                $params = null;
            }
        } else {
            if (!empty($params)) {
                $params = format_postdata_for_curlcall($params);
            } else {
                $params = null;
            }
        }

        $curl = new curl();
        $curl->setHeader($formattedheaders);

        $response = call_user_func_array([$curl, $method], [$endpoint, $params]);

        $error = $curl->get_errno();
        $curlinfo = $curl->get_info();

        if (!is_null($params)) {
            $paramsarr = [];
            if (is_array($params)) {
                $paramsarr = $params;
            }
            if (is_string($params)) {
                parse_str($params, $paramsarr);
            }
            $params = json_encode($paramsarr, JSON_PRETTY_PRINT);
        }

        $rawresponse = str_replace("\r\n", "\n", join("", $curl->get_raw_response()));
        $response = json_encode(json_decode($response), JSON_PRETTY_PRINT);
        $rawresponse = strtoupper($method) . " " . str_replace($host, '', $endpoint) . "\n{$response}";

        $log = (object) ['timecreated' => $now];
        $log->userid = self::isset_userid() ? self::$userid : 0;
        $log->courseid = self::isset_courseid() ? self::$courseid : 0;
        $log->response = $rawresponse;
        $log->code = $curlinfo['http_code'];
        $log->parameter = $params;
        $log->timecreated = time();
        $DB->insert_record('local_squibit_log', $log);
        self::set_userid();

        if (empty($error) && $curlinfo['http_code'] == 200) {
            return json_decode($response, true);
        } else {
            event\api_failed::create([
                'other' => [
                    'header' => $curl->header,
                    'responsecode' => $curlinfo['http_code'],
                    'rawresponse' => $rawresponse,
                ]
            ])->trigger();
            return [];
        }
    }

    public static function is_natural(array $array) : bool {
        $keys = array_keys($array);
        return array_keys($keys) === $keys;
    }

    public static function get_roles() {
        return self::call_api('get', '/list-permission-groups');
    }

}
