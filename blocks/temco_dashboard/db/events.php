<?php

defined( 'MOODLE_INTERNAL' ) || die();

$observers = array(
    array(
        'eventname' => '\core\event\course_completed',
        'callback' => 'block_temco_dashboard\coursecompletion_mail::coursecomp_mailtoadmin',
    ),
);