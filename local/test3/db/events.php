<?php

defined( 'MOODLE_INTERNAL' ) || die();

$observers = array(
    array(
        'eventname' => '\core\event\course_completed',
        'callback' => 'local_test3\coursecompletion_mail::coursecompleted_mail',
    ),
);