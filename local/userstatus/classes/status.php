<?php

namespace local_userstatus;

class status {
    const inprogess = 1;
    const assessment1 = 2;
    const remediation1 = 3;
    const remediation2 = 4;
    const feedbackletter = 5;
    const internalmoderation = 6;
    const externalmoderation = 7;
    const externalmoderationfollowup = 8;
    const certification = 9;
    const dbtable = 'user_status';
    const component = 'local_userstatus';
    const templatetable = 'local_userstatus_templates';
    const notemplate = 0;
    public static function init(){
        global $DB, $CFG;
        $dbman = $DB->get_manager();
        if(!$dbman->table_exists(static::dbtable)){
            require_once ($CFG->libdir . '/ddllib.php');
            // Define table user_status to be created.
            $table = new \xmldb_table('user_status');

            // Adding fields to table user_status.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('statusid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            // Adding keys to table user_status.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fkuserid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_key('fkcourseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

            // Conditionally launch create table for user_status.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
                $DB->reset_caches();
            }
        }
    }
    public static function get_options(){
        return [
            static::inprogess => 'In progress',
            static::assessment1 => 'Assessment',
            static::remediation1 => 'Remediation 1',
            static::remediation2 => 'Remediation 2',
            static::feedbackletter => 'Feedback letter',
            static::internalmoderation => 'Internal moderation',
            static::externalmoderation => 'External moderation',
            static::externalmoderationfollowup => 'External moderation follow up',
            static::certification => 'Certification',
        ];
    }
    public static function get_statushtml($userid,$courseid){
        global $DB;
        static::init();
        $statusid = $DB->get_field(static::dbtable,'statusid',[
                'userid' => $userid,
                'courseid' => $courseid,
                ]);
        if(empty($statusid)){
            $statusid = static::inprogess;
        }
        return self::get_statushtml_by_id($statusid);
    }
    public static function get_statushtml_by_id($statusid){
        $statusname = self::get_options()[$statusid];
        return <<<HTML
<span class="badge" data-statusid="{$statusid}">{$statusname}</span>
HTML;
    }
    public static function set_status($userid,$courseid,$statusid = self::inprogess){
        global $DB;
        static::init();
        $params = [
                'userid' => $userid,
                'courseid' => $courseid,
        ];
        if(!$record = $DB->get_record(static::dbtable,$params)){
            $record = (object) $params;
        }
        $record->statusid = array_key_exists($statusid,static::get_options())?
                $statusid:static::inprogess;
        $record->timemodified = time();
        if($record->id){
            $DB->update_record(static::dbtable,$record);
        } else {
            $record->id = $DB->insert_record(static::dbtable,$record);
        }
        return $record->id;
    }

    public static function manage_templates(\context $context) {
        return has_capability('moodle/course:bulkmessaging', $context);
    }

    public static function get_sortstatus() {
        return [
                static::assessment1 => 'Assessment',
                static::certification => 'Certification',
                static::externalmoderation => 'External moderation',
                static::externalmoderationfollowup => 'External moderation follow up',
                static::feedbackletter => 'Feedback letter',
                static::inprogess => 'In progress',
                static::internalmoderation => 'Internal moderation',
                static::remediation1 => 'Remediation 1',
                static::remediation2 => 'Remediation 2',
        ];
    }
}
