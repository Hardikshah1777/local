<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
        [
            'eventname'   => '\core\event\user_created',
            'callback'    => 'local_squibit\observer::squibit_user_created',
        ],
        [
            'eventname'   => '\core\event\user_updated',
            'callback'    => 'local_squibit\observer::squibit_user_updated',
        ],
        [
            'eventname'   => '\core\event\user_deleted',
            'callback'    => 'local_squibit\observer::squibit_user_deleted',
        ],
        [
            'eventname'   => '\core\event\course_updated',
            'callback'    => 'local_squibit\observer::squibit_course_updated',
        ],
        [
            'eventname'   => '\core\event\course_deleted',
            'callback'    => 'local_squibit\observer::squibit_course_deleted',
        ],
        [
            'eventname'   => '\core\event\user_enrolment_created',
            'callback'    => 'local_squibit\observer::squibit_enrolment_created',
        ],
        [
            'eventname'   => '\core\event\user_enrolment_deleted',
            'callback'    => 'local_squibit\observer::squibit_enrolment_deleted',
        ],
];