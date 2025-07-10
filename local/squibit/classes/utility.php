<?php

namespace local_squibit;

class utility {

    const STATUSES = [
        'pending' => 0,
        'success' => 1,
        'failed' => 2,
    ];

    const CAPS = [
        'manage' => 'local/squibit:manage',
    ];

    const SYNCTYPE = [
        0 => 'User',
        1 => 'Course',
    ];

    const COMPONENT = 'local_squibit';

    const PROFILE = 'squibit_role';

    const DEFAULTPROFILE = 'None';

    const COURSEENABLE = 'courseenable';

    public static function is_enabled(): bool {
        if (self::get_map_roleid() <= 0) {
            return false;
        }
        return !empty(get_config(self::COMPONENT, 'status'));
    }

    public static function get_syncedusers(): int {
        global $DB;
        $from = '{local_squibit_users} su';
        $where = 'deleted = 0 AND status = :success';
        $from .= ' LEFT JOIN {user_info_data} d ON d.userid = su.userid AND d.fieldid = :profid';
        $where .= ' AND (d.id IS NULL OR ' . $DB->sql_like('d.data', ':none', false, false, true) . ')';
        $profid = $DB->get_field('user_info_field', 'id', ['shortname' => self::PROFILE]);
        $params['success'] = self::STATUSES['success'];
        $params['profid'] = empty($profid) ? 0 : $profid;
        $params['none'] = self::DEFAULTPROFILE;
        $userscount = $DB->get_field_sql("SELECT COUNT(1) FROM {$from} WHERE {$where}", $params);
        return !empty($userscount) ? $userscount : 0;
    }

    public static function get_pendingusers(): int {
        global $CFG, $DB;
        $userids = $CFG->siteadmins .','.$CFG->siteguest;
        $from = '{user} u LEFT JOIN {local_squibit_users} squibit ON squibit.userid = u.id ';
        $where = 'u.deleted = 0 AND u.id NOT IN ('.$userids.') AND COALESCE(squibit.status, 0) <> :success';
        $from .= ' LEFT JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = :profid';
        $where .= ' AND (d.id IS NULL OR ' . $DB->sql_like('d.data', ':none', false, false, true) . ')';
        $profid = $DB->get_field('user_info_field', 'id', ['shortname' => self::PROFILE]);
        $params['success'] = self::STATUSES['success'];
        $params['profid'] = empty($profid) ? 0 : $profid;
        $params['none'] = self::DEFAULTPROFILE;
        $userscount = $DB->get_field_sql( "SELECT COUNT(1) FROM {$from} WHERE {$where}", $params);
        return !empty($userscount) ? $userscount : 0;
    }

    public static function get_totalusers(): int {
        global $CFG, $DB;
        $userids = $CFG->siteadmins .','.$CFG->siteguest;
        $from = '{user} u';
        $where = 'u.deleted = 0 AND u.id NOT IN ('.$userids.')';
        $from .= ' LEFT JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = :profid';
        $where .= ' AND (d.id IS NULL OR ' . $DB->sql_like('d.data', ':none', false, false, true) . ')';
        $profid = $DB->get_field('user_info_field', 'id', ['shortname' => self::PROFILE]);
        $params['profid'] = empty($profid) ? 0 : $profid;
        $params['none'] = self::DEFAULTPROFILE;
        $userscount = $DB->get_field_sql( "SELECT COUNT(1) FROM {$from} WHERE {$where}", $params);
        return !empty($userscount) ? $userscount : 0;
    }

    public static function get_syncedcourses(): int {
        global $DB;
        $coursescount = $DB->count_records_select('local_squibit_course',
            'deleted = 0 AND status = ? AND courseid IN (SELECT c.id FROM {course} c JOIN {customfield_data} cd ON cd.instanceid = c.id AND cd.fieldid = (SELECT id FROM {customfield_field} cf WHERE cf.shortname = ? ))',
            [self::STATUSES['success'], self::COURSEENABLE]);
        return !empty($coursescount) ? $coursescount : 0;
    }

    public static function get_pendingcourses(): int {
        global $DB;
        $from = '{course} c LEFT JOIN {local_squibit_course} squibit ON squibit.courseid = c.id JOIN {customfield_data} cd ON cd.instanceid = c.id AND cd.fieldid = (SELECT id FROM {customfield_field} cf WHERE cf.shortname = ? )';
        $where = 'c.id <> ? AND COALESCE(squibit.status, 0) <> ?';
        $coursescount = $DB->get_field_sql( "SELECT COUNT(1) FROM {$from} WHERE {$where}",
            [self::COURSEENABLE, SITEID, self::STATUSES['success']]);
        return !empty($coursescount) ? $coursescount : 0;
    }

    public static function get_totalcourses(): int {
        global $DB;
        $from = '{course} c JOIN {customfield_data} cd ON cd.instanceid = c.id AND cd.fieldid = (SELECT id FROM {customfield_field} cf WHERE cf.shortname = ? )';
        $where = 'c.id <> ?';
        $coursescount = $DB->get_field_sql( "SELECT COUNT(1) FROM {$from} WHERE {$where}",
            [self::COURSEENABLE, SITEID, self::STATUSES['success']]);
        return !empty($coursescount) ? $coursescount : 0;
    }

    public static function get_sync_user_log($timestart, $timeend) {
        global $CFG, $DB;
        $userids = $CFG->siteadmins .','.$CFG->siteguest;
        $usersql = '
                    SELECT log.id, log.response, log.userid, u.firstname, u.lastname, u.email, squibit.status, log.timecreated AS lastsync
                    FROM {local_squibit_log} log JOIN {user} u ON u.id = log.userid
                    LEFT JOIN {local_squibit_users} squibit ON squibit.userid = u.id
                    WHERE u.deleted = :deleted AND log.timecreated > :timestart AND log.timecreated < :timeend AND u.id NOT IN ('.$userids.')';
        $userparams['deleted'] = 0;
        $userparams['timestart'] = $timestart;
        $userparams['timeend'] = $timeend;
        $squibitusers = $DB->get_records_sql($usersql, $userparams);

        $successuser = $erroruser = [];
        foreach ($squibitusers as $squibituser) {
            if (!empty($squibituser->lastsync)) {
                $squibituser->lastsync = userdate($squibituser->lastsync, '%m-%d-%Y, %I:%M %p');
            }
            if ($squibituser->status == utility::STATUSES['success']) {
                $successuser[] = $squibituser;
            } else if ($squibituser->status == utility::STATUSES['failed']) {
                $erroruser[] = $squibituser;
            }
        }
        return ['success' => $successuser, 'error' => $erroruser];
    }

    public static function get_sync_course_log($timestart, $timeend) {
        global $CFG, $DB;
        $coursesql = '
                    SELECT log.id, log.response, log.courseid, c.fullname AS coursename, squibit.status, log.timecreated AS lastsync
                    FROM {local_squibit_log} log JOIN {course} c ON c.id = log.courseid
                    LEFT JOIN {local_squibit_course} squibit ON squibit.courseid = c.id
                    JOIN {customfield_data} cd ON cd.instanceid = c.id AND cd.fieldid = (SELECT id FROM {customfield_field} cf WHERE cf.shortname = :cshortname )
                    WHERE c.id <> :siteid AND log.timecreated > :timestart AND log.timecreated < :timeend';
        $courseparams['siteid'] = SITEID;
        $courseparams['deleted'] = 0;
        $courseparams['timestart'] = $timestart;
        $courseparams['timeend'] = $timeend;
        $courseparams['cshortname'] = self::COURSEENABLE;
        $squibitcourses = $DB->get_records_sql($coursesql, $courseparams);

        $successcourse = $errorcourse = [];
        foreach ($squibitcourses as $squibitcourse) {
            if (!empty($squibitcourse->lastsync)) {
                $squibitcourse->lastsync = userdate($squibitcourse->lastsync, '%m-%d-%Y, %I:%M %p');
            }
            if ($squibitcourse->status == utility::STATUSES['success']) {
                $successcourse[] = $squibitcourse;
            } else if ($squibitcourse->status == utility::STATUSES['failed']) {
                $errorcourse[] = $squibitcourse;
            }
        }
        return ['success' => $successcourse, 'error' => $errorcourse];
    }

    public static function excel_generate_data($excel, string $name, string $datastatus = null, array $headers, object $alldata) {
        if (!empty($datastatus)) {
            $excelname = $name . ' '. $datastatus;
        } else {
            $excelname = $name;
        }

        $excelwriter = $excel->add_worksheet($excelname);
        if ($name == self::SYNCTYPE[0]) {
            $excelwriter->set_column(0, 1, 10);
            $excelwriter->set_column(2, 2, 20);
            $excelwriter->set_column(3, 3, 10);
            $excelwriter->set_column(4, 4, 25);
            $excelwriter->set_column(5, 5, 50);
        } else if ($name == self::SYNCTYPE[1]) {
            $excelwriter->set_column(0, 1, 10);
            $excelwriter->set_column(2, 2, 25);
            $excelwriter->set_column(3, 3, 50);
        }

        $rowindex = 0;
        foreach ($headers as $i => $userheader) {
            $excelwriter->write_string($rowindex, $i, get_string($userheader, self::COMPONENT));
        }
        $rowindex++;

        foreach ($alldata as $data) {
            $column = 0;

            $status = array_keys(self::STATUSES)[$data->status];
            $response = $data->response;
            if (!empty($data->response)) {
                $string = explode("\n", $data->response);
                unset($string[0]);
                $jsondecode = json_decode(implode("\n", $string));
                $response = $jsondecode->message;
                if (!empty($jsondecode->errors)){
                    $response .= ' : ';
                    $key = array_keys((array)$jsondecode->errors);
                    $response .= implode(', ', str_replace('_', ' ', $key));
                }
            } else {
                $response = '';
            }

            if ($name == self::SYNCTYPE[0]) {
                $values = [$data->firstname, $data->lastname, $data->email, $status, $data->lastsync, $response];
            } else if ($name == self::SYNCTYPE[1]) {
                $values = [$data->coursename, $status, $data->lastsync, $response];
            }


            foreach ($values as $value) {
                if (!empty($value)) {
                    $excelwriter->write_string($rowindex, $column, $value);
                } else {
                    $excelwriter->write_string($rowindex, $column, '');
                }
                $column++;
            }
            $rowindex++;
        }
        return true;
    }

    public static function getprofilefield(string $fieldname = self::PROFILE) {
        global $CFG, $DB;
        $field = $DB->get_record('user_info_field', ['shortname' => $fieldname]);
        if (empty($field)) {
            return null;
        }
        require_once($CFG->dirroot . '/user/profile/field/' . $field->datatype . '/field.class.php');
        $classname = 'profile_field_' . $field->datatype;
        return new $classname($field->id, 0, $field);
    }

    public static function get_rolemapping() {
        $rolemappings = get_config(self::COMPONENT, 'rolemapping');
        if (empty($rolemappings)) {
            $rolemappings = [];
        } else {
            $rolemappings = json_decode($rolemappings, true);
        }
        return $rolemappings;
    }

    public static function get_map_roleid() : int {
        $teacherrole = get_config(self::COMPONENT, 'teacherrole');
        if ($teacherrole > 0) {
            return $teacherrole;
        }
        return -2;
    }
}
