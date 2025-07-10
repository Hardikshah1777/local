<?php
$capabilities = [
        'block/courseslist:myaddinstance' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => [
                        'editingteacher' => CAP_ALLOW
                ],
        ],

        'block/courseslist:addinstance' => [
                'riskbitmask' => RISK_SPAM | RISK_XSS,
                'captype' => 'write',
                'contextlevel' => CONTEXT_BLOCK,
                'archetypes' => [
                        'editingteacher' => CAP_ALLOW,
                ],
        ],
        'block/courseslist:view' => [
                'captype' => 'read',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => array(
                        'editingteacher' => CAP_ALLOW
                ),
        ]
];