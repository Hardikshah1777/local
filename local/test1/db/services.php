<?php

$functions = [
    'local_nodeapi_get_user_info' => [
        'classname' => 'local_nodeapi_external',
        'methodname' => 'get_user_info',
        'classpath' => 'local/test1/externallib.php',
        'description' => 'Return basic user info for a given user id',
        'type' => 'read',
        'capabilities' => 'moodle/user:viewdetails'
    ],
];

$services = [
    'My Custom API Service' => [
        'functions' => ['local_nodeapi_get_user_info'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ]
];
