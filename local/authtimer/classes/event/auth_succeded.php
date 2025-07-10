<?php

namespace local_authtimer\event;

use local_authtimer\auth;

class auth_succeded extends auth_failed {

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $description = "The user with id '{$this->userid}' succeded to authenticate.";
        return $description;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('authsucceded', auth::component);
    }

}
