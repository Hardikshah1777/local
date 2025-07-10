<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'report/temco_completion:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        ),
    )
);
