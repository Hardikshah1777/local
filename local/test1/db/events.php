<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback'  => 'local_test1\usercourseinvoice::user_course_invoice_delete',
    ],

];