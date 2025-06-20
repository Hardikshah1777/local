<?php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    [
        'classname' => '\report_temco_completion\task\temco_weekly_report',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '8',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '1',
    ],
    [
        'classname' => '\report_temco_completion\task\temco_scorm_weekly_report',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '8',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '1',
    ],
);
