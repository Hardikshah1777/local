<?php

use report_kln\util;

function report_kln_before_footer() {
    global $PAGE, $USER;

    // If course page then include timespent js
    if (strpos($PAGE->bodyclasses, 'pagelayout-course') !== false &&
        ($PAGE->context->contextlevel == CONTEXT_COURSE && $PAGE->context->instanceid !== SITEID)) {
        $courseid = $PAGE->context->instanceid;
        $PAGE->requires->js_call_amd('report_kln/index', 'initTimer', [$USER->id, $courseid, util::INTERVALTIME]);
    }
}