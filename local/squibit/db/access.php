<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/squibit:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
        ],
    ],
];
