<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
        [
                'eventname'   => '\core\event\user_enrolment_created',
                'callback'    => 'local_coursenotify\enrolment_mail::enrolment_mailtouser',
        ],
        [
                'eventname'   => '\core\event\course_completed',
                'callback'    => 'local_coursenotify\coursecompletion_mail::coursecomp_mailtomanager',
        ],
];