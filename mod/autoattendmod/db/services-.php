<?php

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_autoattendmod_get_attendances' => array(
        'classname'     => 'mod_autoattendmod_external',
        'methodname'    => 'get_attendances',
        'description'   => 'Returns a list of autoattendmod instances...',
        'type'          => 'read',
        'capabilities'  => 'mod/autoattendmod:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ),
    'mod_autoattendmod_get_attendances_handler' => array(
        'classname'     => 'mod_autoattendmod_external',
        'methodname'    => 'get_attendances_handler',
        'description'   => 'Returns a list of autoattendmod instances...',
        'type'          => 'read',
        'capabilities'  => 'mod/autoattendmod:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ),
);


$services = array(
    'AutoAttendanceModule' => array(
        'functions' => array(
            'mod_autoattendmod_get_attendances',
            'mod_autoattendmod_get_attendances_handler',
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'autoattendmod'
    )
);
