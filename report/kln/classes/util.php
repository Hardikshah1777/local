<?php

namespace report_kln;

use stdClass;

class util {

    const COMPONENT = 'report_kln';

    const INTERVALTIME = 10;

    /**
     * Store user course wise timespent store
     * @param $userid
     * @param $courseid
     * @param $timespent
     * @return bool|int
     */
    public static function handle_user_timetrack($userid = 0, $courseid = 0, $timespent = 0, $midnighttime = 0) {
        global $DB;
        if (empty($userid) || empty($courseid) || empty($timespent)) {
            return false;
        }

        $midnight = !empty($midnighttime) ? $midnighttime : strtotime('midnight');

        $timeobj = new stdClass();
        $timeobj->userid = $userid;
        $timeobj->courseid = $courseid;
        $timeobj->timecreated = $midnight;

        $objexist = $DB->get_record('report_kln_course_timespent', (array) $timeobj);
        if (!empty($objexist)) {
            $timeobj->id = $objexist->id;
            $timeobj->timespent = $objexist->timespent + $timespent;
            return $DB->update_record('report_kln_course_timespent', $timeobj);
        } else {
            $timeobj->timespent = $timespent;
            return $DB->insert_record('report_kln_course_timespent', $timeobj, false);
        }
    }

    /**
     * Store user login count
     * @param stdClass $user
     * @return void
     */
    public static function handle_user_login(stdClass $user, $currenttime = 0) {
        global $DB;

        $loginobj = new stdClass();
        $loginobj->userid = $user->id;

        $now = !empty($currenttime) ? $currenttime : time();
        $sqlparams['userid'] = $loginobj->userid;
        $sqlparams['starttime'] = strtotime('midnight', $now);
        $sqlparams['endtime'] = strtotime('midnight +1 day -1 second', $now);
        $where = "userid = :userid AND timecreated > :starttime AND timecreated <= :endtime ";
        $existlogincount = $DB->get_field_select('report_kln_user_login', 'MAX(logincount)', $where, $sqlparams);
        $daylastcount = !empty($existlogincount) ? $existlogincount : 0;

        $loginobj->logincount = $daylastcount + 1;
        $loginobj->timecreated = $now;

        $DB->insert_record('report_kln_user_login', $loginobj);
    }

    public static function get_courses() {
        global $DB;
        return $DB->get_records_select_menu('course', 'id > 1', [], '', 'id, fullname');
    }

    public static function get_users() {
        global $DB;
        return $DB->get_records_select_menu('user', 'id > 2 AND suspended = 0 AND deleted = 0', [], '', 'id, CONCAT(firstname, " ", lastname)');
    }

    public static function kln_format_time($totalsecs, $str = null) {

        $totalsecs = abs($totalsecs);

        if (!$str) {
            // Create the str structure the slow way.
            $str = new stdClass();
            $str->hour  = get_string('hour');
            $str->hours = get_string('hours');
            $str->min   = get_string('min');
            $str->mins  = get_string('mins');
            $str->sec   = get_string('sec');
            $str->secs  = get_string('secs');
        }

        $hours     = floor($totalsecs/HOURSECS);
        $remainder = $totalsecs - ($hours*HOURSECS);
        $mins      = floor($remainder/MINSECS);
        $secs      = $remainder - ($mins*MINSECS);

        $ss = ($secs == 1)  ? $str->sec  : $str->secs;
        $sm = ($mins == 1)  ? $str->min  : $str->mins;
        $sh = ($hours == 1) ? $str->hour : $str->hours;

        $ohours = '';
        $omins = '';
        $osecs = '';

        if ($hours) {
            $ohours = $hours .' '. $sh;
        }
        if ($mins) {
            $omins  = $mins .' '. $sm;
        }
        if ($secs) {
            $osecs  = $secs .' '. $ss;
        }

        if ($hours) {
            return trim($ohours .' '. $omins);
        }
        if ($mins) {
            return trim($omins .' '. $osecs);
        }
        if ($secs) {
            return $osecs;
        }
        return get_string('now');
    }
}