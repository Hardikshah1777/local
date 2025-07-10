<?php

$functions = [
        'local_squibit_sync_all_users' => [
                'classname' => 'local_squibit\external\sync_all_users',
                'methodname'  => 'execute',
                'description' => 'Sync all users',
                'type' => 'write',
                'ajax' => true,
        ],
        'local_squibit_sync_user' => [
                'classname'   => 'local_squibit\external\sync_user',
                'methodname'  => 'execute',
                'description' => 'Sync the user with squibit api',
                'type'        => 'write',
                'ajax'        => true,
        ],
        'local_squibit_sync_all_courses' => [
                'classname' => 'local_squibit\external\sync_all_courses',
                'methodname'  => 'execute',
                'description' => 'Sync all courses',
                'type' => 'write',
                'ajax' => true,
        ],
        'local_squibit_sync_course' => [
                'classname'   => 'local_squibit\external\sync_course',
                'methodname'  => 'execute',
                'description' => 'Sync the course with squibit api',
                'type'        => 'write',
                'ajax'        => true,
        ],
        'local_squibit_sync_all_reset' => [
                'classname'   => 'local_squibit\external\sync_all_reset',
                'methodname'  => 'execute',
                'description' => 'All sync records reset',
                'type'        => 'write',
                'ajax'        => true,
        ]
];
