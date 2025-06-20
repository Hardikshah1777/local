<?php

$capabilities = array(
        'block/temco_dashboard:myaddinstance' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                ),
        ],

        'block/temco_dashboard:addinstance' => [
                'riskbitmask' => RISK_SPAM,
                'captype' => 'write',
                'contextlevel' => CONTEXT_BLOCK,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                ),
        ],
        'block/temco_dashboard:view' => [
                'captype' => 'read',
                'contextlevel' => CONTEXT_BLOCK,
                'archetypes' => array(
                    'manager' => CAP_ALLOW
                ),
        ]
);