<?php

namespace local_authtimer;

use core_user;
use function get_string;

class auth {
    const component = 'local_authtimer';
    const table = 'local_authtimer';
    const timediff = 1*60;
//    const timediff = HOURSECS;

    public static function get_nextslottime() {
        global $USER;
        $currentslot = self::get_currentslot();
        if (isset($USER->authtimers[$currentslot]) && !empty($USER->authtimers[$currentslot])) {
            $currentslot += 1;
        }
        $nextticktime = $USER->currentlogin + (self::timediff * $currentslot);

        return $nextticktime - time();
    }

    public static function get_currentslot() {
        global $USER;
        $USER->authtimers = $USER->authtimers ?? [true];
        return floor((time() - $USER->currentlogin) / self::timediff);
    }

    public static function skip_user($userorid = null) {
        global $USER, $DB;
        if (!$userorid) {
            $userorid = $USER;
        }
        $userid = is_object($userorid) ? $userorid->id: $userorid;
        if (!$userid) {
            return true;
        }
        if (is_siteadmin($userid) || \core\session\manager::is_loggedinas()) {
            return true;
        }
        $courseids = get_config(self::component, 'courses');
        if (!$courseids) {
            return true;
        }
        list($in,$params) = $DB->get_in_or_equal(explode(',', $courseids));
        $params = array_merge($params, [$userid,ENROL_INSTANCE_ENABLED,ENROL_USER_ACTIVE]);
        return !$DB->record_exists_sql("SELECT 1 FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid
WHERE e.courseid {$in} AND ue.userid = ? AND e.status = ? AND ue.status = ?",
                $params);
    }

    public static function required() {
        global $USER;
        if (!$currentslot = self::get_currentslot()) {
            return false;
        }
        return !isset($USER->authtimers[$currentslot]) || empty($USER->authtimers[$currentslot]);
    }

    public static function validate($code) {
        global $USER, $DB;

        if (!$currentslot = self::get_currentslot()) {
            return true;
        }

        $codeobj = self::get_current_code();

        $USER->authtimers[$currentslot] = $valid = ($codeobj->authcode === $code);

        if ($valid) {
            $DB->delete_records(self::table, ['id' => $codeobj->id]);
        }

        return $valid;
    }

    public static function get_current_code($generate = false)
    {
        global $USER, $DB;

        $params = ['userid' => $USER->id];
        $record = $DB->get_record( self::table, $params );
        if (!$record) {
            $record = (object)$params;
        }
        if (isset( $record->authcode ) && !$generate) {
            return $record;
        }

        $uniquecodefound = false;

        $param['userid'] = $USER->id;
        $userdata = $DB->get_records_sql( 'SELECT pd.id,pf.name,pd.userid,pd.data 
                                          FROM {user_info_field} pf 
                                          LEFT JOIN {user_info_data} pd on pd.fieldid = pf.id
                                          WHERE pd.userid = :userid', $param );
        $random = array_rand( $userdata );
        $randomValue = $userdata[$random];

        if (!empty($randomValue->data)){
            $code = $randomValue->data;
        }else {
            $code = random_string(10);
        }
        while (!$uniquecodefound) {
            if (!$DB->record_exists(self::table, array('authcode' => $code))) {
                $uniquecodefound = true;
            } else {
                if (!empty($randomValue->data)){
                    $code = $randomValue->data;
                }else {
                    $code = random_string(10);
                }
            }
        }

        $record->authcode = $code;
        $record->timemodified = time();
        if (isset($record->id)) {
            $DB->update_record(self::table, $record);
        } else {
            $record->id = $DB->insert_record(self::table, $record);
        }
        return $record;
    }

    public static function mail() {
        global $USER;

        $codeobj = self::get_current_code(true);

        $from = core_user::get_support_user();
        $subject = get_string('mailsub', self::component);
        $message = get_string('mailmes', self::component, [
                'authcode' => $codeobj->authcode,
                'fullname' => fullname($USER),
        ]);

        return email_to_user($USER, $from, $subject, $message, $message);
    }

    public function template(){
        global $USER, $DB, $OUTPUT;
        $temdata = new \stdClass();
        $userdata = $DB->get_record('local_authtimer', ['userid' => $USER->id]);
        $params['userid'] = $userdata->userid;
        $params['authcode'] = $userdata->authcode;
        $fielddata = $DB->get_record_sql('SELECT pd.id, pd.fieldid, pf.name  FROM {user_info_data} pd
                                         LEFT JOIN {user_info_field} pf ON pf.id = pd.fieldid
                                         WHERE pd.userid =:userid AND pd.data =:authcode', $params);
        $temdata->name = $fielddata->name;
        return $OUTPUT->render_from_template('local_authtimer/authmodal', $temdata);
    }
}
