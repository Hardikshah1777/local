<?php

use local_authtimer\auth;

global $USER;

function local_authtimer_before_footer() {
    global $PAGE;
    if (!auth::skip_user()) {
        $PAGE->requires->js_call_amd('local_authtimer/auth', 'init', [auth::get_nextslottime()]);
        $PAGE->requires->strings_for_js(['cannotsendmail', 'emailcodeinvalid', 'emailsent',], auth::component);
    }
}