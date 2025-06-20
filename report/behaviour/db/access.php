<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
        'report/behaviour:view' => [
                'captype' => 'read',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
];
