<?php

namespace local_squibit;

use context_course;
use core\event\course_deleted;
use core\event\course_updated;
use core\event\user_created;
use core\event\user_deleted;
use core\event\user_enrolment_created;
use core\event\user_enrolment_deleted;
use core\event\user_updated;
use stdClass;

class observer {

    public static function fill_userrecord(stdClass $user, stdClass $squibituser) {
        foreach (['userid' => 'id', 'username', 'firstname', 'lastname', 'email', 'roleid' => 'role'] as $mapfield => $field) {
            if (is_numeric($mapfield)) {
                $mapfield = $field;
            }
            $squibituser->$mapfield = $user->$field;
        }
        return $squibituser;
    }

    public static function get_usersyncescourses($userid) : array {
        global $DB;
        $usercourseids = array_keys(enrol_get_users_courses($userid));
        if (!empty($usercourseids)) {
            $syncedcourseids = $DB->get_records_list('local_squibit_course', 'courseid', $usercourseids, '', 'courseid,created');
            $syncedcourseids = array_filter($syncedcourseids, function($synccourse) {
                return !empty($synccourse->created);
            });
            $usercourseids = array_intersect($usercourseids, array_column($syncedcourseids, 'courseid'));
        }
        return $usercourseids;
    }

    /**
     * @param stdClass $user
     * @param bool $sync
     * @return stdClass|bool
     */
    public static function get_synceduser(stdClass $user, bool $sync = false, bool $allowfailed = false) {
        global $DB;
        $squibituser = $DB->get_record('local_squibit_users', ['userid' => $user->id]);
        $user->role = self::get_user_roleid($user->id);
        if ($user->role == -1) {
            return false;
        }
        if (empty($user->role)) {
            // return false;
        }
        if (empty($squibituser) || empty($squibituser->created)) {
            $usercourseids = self::get_usersyncescourses($user->id);
            $response = squibitapi::create_conference_user($user, $usercourseids);
            if (empty($squibituser)) {
                $squibituser = new stdClass;
            }
            $squibituser = self::fill_userrecord($user, $squibituser);
            $squibituser->deleted = 0;
            $squibituser->status = utility::STATUSES['success'];
            $squibituser->course = join(',', $usercourseids);
            $squibituser->timecreated = $squibituser->timemodified = time();
            if (empty($response)) {
                $squibituser->status = utility::STATUSES['failed'];
            }
            if (empty($squibituser->id)) {
                $squibituser->created = $squibituser->status == utility::STATUSES['success'];
                $squibituser->id = $DB->insert_record('local_squibit_users', $squibituser);
            } else if (empty($DB->get_field('local_squibit_users', 'created', ['id' => $squibituser->id]))) {
                $squibituser->created = $squibituser->status == utility::STATUSES['success'];
                $squibituser->timemodified = time();
                $DB->update_record('local_squibit_users', $squibituser);
            }
        } else if ($sync && ($allowfailed || $squibituser->status == utility::STATUSES['success'])) {
            // Role changed.
            if (!empty($squibituser->roleid) && $squibituser->roleid != $user->role) {
                $user->previousroleid = $user->role = $squibituser->roleid;
                $response = squibitapi::update_conference_user($user);
                if (empty($response)) {
                    $squibituser->status = utility::STATUSES['failed'];
                    $squibituser->timemodified = time();
                    $DB->update_record('local_squibit_users', $squibituser);
                    return false;
                }
                $response = squibitapi::delete_conference_user($squibituser->userid);
                if (empty($response)) {
                    $squibituser->status = utility::STATUSES['failed'];
                    $squibituser->timemodified = time();
                    $DB->update_record('local_squibit_users', $squibituser);
                    return false;
                } else {
                    $squibituser->created = 0;
                    $squibituser->timemodified = time();
                    $DB->update_record('local_squibit_users', $squibituser);
                }
                return self::get_synceduser($user);
            }
            $user->previousroleid = $user->role;
            $usercourseids = self::get_usersyncescourses($user->id);
            $response = squibitapi::update_conference_user($user, $usercourseids);
            if (!empty($response)) {
                $squibituser = self::fill_userrecord($user, $squibituser);
                $squibituser->deleted = 0;
                $squibituser->status = utility::STATUSES['success'];
                $squibituser->course = join(',', $usercourseids);
                $squibituser->timemodified = time();
                if (empty($squibituser->created)) {
                    $squibituser->created = 1;
                }
                $DB->update_record('local_squibit_users', $squibituser);
            } else {
                $squibituser->status = utility::STATUSES['failed'];
                $squibituser->timemodified = time();
                $DB->update_record('local_squibit_users', $squibituser);
            }
        } else if (!empty($squibituser) && $squibituser->status == utility::STATUSES['success']) {
            return $squibituser;
        }
        if (empty($response) || $squibituser->status == utility::STATUSES['failed']) {
            return false;
        }
        return $squibituser;
    }

    public static function get_syncedcourse(stdClass $course, bool $sync = false, ?int $teacherid = null, bool $allowfailed = false) {
        global $DB;
        $squibitcourse = $DB->get_record('local_squibit_course', ['courseid' => $course->id]);
        $teacherids = self::get_course_teachers($course->id);
        if (!empty($teacherid) && !in_array($teacherid, $teacherids)) {
            $teacherids[] = $teacherid;
        }

        if (empty($teacherids) && empty($sync)) {
            return false;
        }

        $teacherids = array_map([squibitapi::class, 'get_user_unique_id'], $teacherids);
        $studentids = array_diff(self::get_course_users($course->id), $teacherids);

        if (empty($squibitcourse) || empty($squibitcourse->created) || !empty($sync)) {
            if (empty($squibitcourse) || empty($squibitcourse->created)) {
                $response = squibitapi::create_course($course, $teacherids, $studentids);
            } else {
                $response = squibitapi::update_course($course, $teacherids, $studentids);
            }
            if (!empty($response)) {
                $squibitcourse = self::upsert_course($course->id, $course->fullname, $teacherids, utility::STATUSES['success']);
            } else {
                $squibitcourse = self::upsert_course($course->id, $course->fullname, $teacherids, utility::STATUSES['failed']);
                return false;
            }
        } else if (!empty($squibitcourse) && $squibitcourse->status == utility::STATUSES['success']) {
            return $squibitcourse;
        }

        if (empty($response) || $squibitcourse->status == utility::STATUSES['failed']) {
            return false;
        }
        return $squibitcourse;
    }

    public static function get_course_teachers($courseid) : array {
        global $DB;
        $teacherids = [];
        $coursecontext = context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext, '', 0, 'u.id,u.firstname,u.lastname');
        $moderatorroleid = utility::get_map_roleid();
        foreach ($users as $user) {
            $role = self::get_user_roleid($user->id);
            if ($role == $moderatorroleid &&
                $DB->record_exists('local_squibit_users', ['userid' => $user->id, 'status' => 1, 'deleted' => 0])) {
                $teacherids[] = squibitapi::get_user_unique_id($user->id);
            }
        }
        return $teacherids;
    }

    public static function get_course_users($courseid) : array {
        global $DB;
        $studentids = [];
        $coursecontext = context_course::instance($courseid);
        $users = get_enrolled_users($coursecontext, '', 0, 'u.id,u.firstname,u.lastname');
        foreach ($users as $user) {
            $role = self::get_user_roleid($user->id);
            if ($role > 0 &&
                $DB->record_exists('local_squibit_users', ['userid' => $user->id, 'status' => 1, 'deleted' => 0])) {
                $studentids[] = squibitapi::get_user_unique_id($user->id);
            }
        }
        return $studentids;
    }

    public static function upsert_course(int $courseid, string $name, array $teacher, int $status) {
        global $DB;
        $squibitcourse = new stdClass;
        $squibitcourse->courseid = $courseid;
        $squibitcourse->name = $name;
        $squibitcourse->teacher = join(',', $teacher);
        $squibitcourse->deleted = 0;
        $squibitcourse->status = $status;
        $squibitcourse->id = $DB->get_field('local_squibit_course', 'id', ['courseid' => $courseid]);
        $squibitcourse->timemodified = time();
        if (empty($squibitcourse->id)) {
            $squibitcourse->created = $status == utility::STATUSES['success'];
            $squibitcourse->timecreated = $squibitcourse->timemodified;
            $squibitcourse->id = $DB->insert_record('local_squibit_course', $squibitcourse);
        } else {
            if (!$DB->get_field('local_squibit_course', 'created', ['courseid' => $courseid])) {
                $squibitcourse->created = $status == utility::STATUSES['success'];
            }
            $DB->update_record('local_squibit_course', $squibitcourse);
        }
        return $squibitcourse;
    }

    /**
     *
     * @param user_created|user_updated $event
     * @return void
     */
    public static function squibit_user_created($event) {
        if (!utility::is_enabled()) {
            return;
        }
        $user = $event->get_record_snapshot($event->objecttable, $event->objectid);
        self::get_synceduser($user);
    }

    public static function squibit_user_updated(user_updated $event) {
        if (!utility::is_enabled()) {
            return;
        }
        $user = $event->get_record_snapshot($event->objecttable, $event->objectid);
        self::get_synceduser($user, true, true);
    }

    public static function squibit_user_deleted(user_deleted $event) {
        global $DB;
        if (!utility::is_enabled()) {
            return;
        }
        $record = $DB->get_record('local_squibit_users', ['userid' => $event->objectid]);
        if (empty($record) || !empty($record->deleted) || $record->status == utility::STATUSES['failed']) {
            return;
        }

        $response = squibitapi::delete_conference_user($event->objectid);
        if (!empty($response)) {
            $record->status = utility::STATUSES['success'];
            $record->deleted = 1;
            $record->timemodified = time();
            $DB->update_record('local_squibit_users', $record);
        } else {
            $record->status = utility::STATUSES['failed'];
            $record->timemodified = time();
            $DB->update_record('local_squibit_users', $record);
        }
    }

    public static function squibit_course_updated(course_updated $event) {
        global $DB;
        if (!utility::is_enabled()) {
            return;
        }
        $course = get_course($event->courseid);
        $teacherids = self::get_course_teachers($course->id);

        if (!empty($teacherids)) {
            $squibitcourse = $DB->get_record('local_squibit_course', ['courseid' => $event->courseid]);
            if (empty($squibitcourse) || empty($squibitcourse->created)) {
                $response = squibitapi::create_course($course, $teacherids);
            } else {
                $response = squibitapi::update_course($course, $teacherids);
            }
            if (!empty($response)) {
                self::upsert_course($course->id, $course->fullname, $teacherids, utility::STATUSES['success']);
            } else {
                self::upsert_course($course->id, $course->fullname, $teacherids, utility::STATUSES['failed']);
            }
        }
    }

    public static function squibit_course_deleted(course_deleted $event) {
        global $DB;
        if (!utility::is_enabled()) {
            return;
        }
        $squibitcourse = $DB->get_record('local_squibit_course', ['courseid' => $event->courseid]);
        if (!empty($squibitcourse) && empty($squibitcourse->deleted)) {
            $response = squibitapi::delete_course($event->courseid);
            if (!empty($response)) {
                $squibitcourse->status = utility::STATUSES['success'];
                $squibitcourse->deleted = 1;
                $squibitcourse->timemodified = time();
                $DB->update_record('local_squibit_course', $squibitcourse);
            } else {
                $squibitcourse->status = utility::STATUSES['failed'];
                $squibitcourse->timemodified = time();
                $DB->update_record('local_squibit_course', $squibitcourse);
            }
        }
    }

    public static function squibit_enrolment_created(user_enrolment_created $event) {
        if (!utility::is_enabled()) {
            return;
        }
        $course = get_course($event->courseid);
        $user = $event->get_record_snapshot('user', $event->relateduserid);
        $squibituser = self::get_synceduser($user);
        if (empty($squibituser)) {
            return false;
        }
        $role = self::get_user_roleid($user->id);
        $teacherid = $role == utility::get_map_roleid() ? $user->id : null;
        $squibitcourse = self::get_syncedcourse($course, false, $teacherid);

        if (empty($squibitcourse)) {
            return false;
        }

        self::get_synceduser($user, true);
    }

    public static function squibit_enrolment_deleted(user_enrolment_deleted $event) {
        if (!utility::is_enabled()) {
            return;
        }
        $course = get_course($event->courseid);
        $user = $event->get_record_snapshot('user', $event->relateduserid);
        if (is_enrolled(context_course::instance($course->id))) {
            return false;
        }

        $squibituser = self::get_synceduser($user, true);
        if (empty($squibituser)) {
            return false;
        }

        $role = self::get_user_roleid($user->id);
        if ($role == utility::get_map_roleid()) {
            self::get_syncedcourse($course, true);
        }
    }

    // Function for get squibit_role profile field for user.
    public static function get_user_roleid($userid) {
        global $DB;
        $role = $DB->get_field_sql(
            'SELECT udata.data FROM {user_info_data} udata
            JOIN {user_info_field} uinfo ON uinfo.id = udata.fieldid
            WHERE uinfo.shortname = :fieldname AND udata.userid = :userid',
            ['fieldname' => utility::PROFILE, 'userid' => $userid]);
        $role = strtolower($role ?? '');
        if ($role == strtolower(utility::DEFAULTPROFILE)) {
            return -1;
        }
        $rolemapping = utility::get_rolemapping();
        if (array_key_exists($role, $rolemapping)) {
            return $rolemapping[$role];
        }
        return null;
    }

    public static function delete_sync_user(stdClass $user) {
        global $DB;
        $squibituser = $DB->get_record('local_squibit_users', ['userid' => $user->id]);
        $user->role = self::get_user_roleid($user->id);
        if (0 && $user->role == -1) {
            return false;
        }
        $record = '';
        if (!empty($squibituser->created)) {
            $response = squibitapi::delete_conference_user($squibituser->userid);
            if (!empty($response)) {
                $record = $DB->delete_records('local_squibit_users', ['id' => $squibituser->id]);
            }
        }
        return $record;
    }

    public static function delete_sync_course(stdClass $course) {
        global $DB;
        $squibitcourse = $DB->get_record('local_squibit_course', ['courseid' => $course->id]);

        $record = '';
        if (!empty($squibitcourse->created)) {
            $response = squibitapi::delete_course($course->id);
            if (!empty($response)) {
                $record = $DB->delete_records('local_squibit_course', ['id' => $squibitcourse->id]);
            }
        }
        return $record;
    }
}
