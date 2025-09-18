<?php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_kln_user_timetrack' => [
        'classname' => 'report_kln\external\user_timetrack',
        'methodname' => 'execute',
        'description' => 'User course wise timespent store',
        'type' => 'write',
        'ajax' => true,
    ]
];
