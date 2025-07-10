<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => 'local_novicegroup\user_updated::add_to_group',
    ],
];