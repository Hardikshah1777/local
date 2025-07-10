<?php

defined('MOODLE_INTERNAL') || die();

$tasks = [
        [
            'classname' => '\local_newsletter\newsletter_task',
            'blocking' => 0,
            'minute' => '0',
            'hour' => '1',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*',
        ]
];
