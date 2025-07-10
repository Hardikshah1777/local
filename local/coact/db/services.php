<?php

$functions = [

    'local_coact_user_create' => [
        'classname' => 'local_coact\external\user_create',
        'description' => 'Create user in moodle',
        'type' => 'read',
    ],
    'local_coact_user_update' => [
        'classname' => 'local_coact\external\user_update',
        'description' => 'Update user in moodle',
        'type' => 'read',
    ],
    'local_coact_user_status' => [
        'classname' => 'local_coact\external\user_status',
        'description' => 'Update user active/suspend status in moodle',
        'type' => 'read',
    ],
    'local_coact_available_cohort' => [
        'classname' => 'local_coact\external\available_cohort',
        'description' => 'list of available cohorts',
        'type' => 'read',
    ],


];
$services['COACT'] = [
    'shortname' => 'local_coact',
    'functions' => array_keys($functions),
    'enabled' => 1,
    'restrictedusers' => 1,
    'downloadfiles' => 0,
    'uploadfiles' => 0
];