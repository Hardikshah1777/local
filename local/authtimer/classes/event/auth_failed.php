<?php

namespace local_authtimer\event;

use context_user;
use core\event\base;
use local_authtimer\auth;
use moodle_url;

class auth_failed extends base {

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $description = "The user with id '{$this->userid}' failed to authenticate.";
        return $description;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('authfailed', auth::component);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'user';
    }

    /**
     * @param $userid
     * @return self
     */
    public static function create_from_userid($userid,$other = []) {
        $data = [
                'objectid' => $userid,
                'relateduserid' => $userid,
                'context' => context_user::instance($userid),
                'other' => !empty($other) ? $other : null,
        ];

        // Create user_created event.
        $event = self::create($data);
        return $event;
    }

    public function get_url() {
        return new moodle_url('/user/view.php', array('id' => $this->objectid));
    }
}
