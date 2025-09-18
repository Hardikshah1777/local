<?php

namespace report_kln;

use core\event\user_loggedin;

class observer {

    /**
     * @param user_loggedin $event
     * @return void
     */
    public static function user_login($event) {
        $user = $event->get_record_snapshot($event->objecttable, $event->objectid);
        util::handle_user_login($user);
    }
}