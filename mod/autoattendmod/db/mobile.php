<?php
defined('MOODLE_INTERNAL') || die();

$addons = [
    "mod_autoattendmod" => [
        "handlers" => [ // Different places where the add-on will display content.
            'autoattendmod' => [ // Handler unique name (can be anything).
                'displaydata' => [
                    'title' => 'AutoAttendance Module',
                    'icon'  => $CFG->wwwroot.'/mod/autoattendmod/pix/icon.gif',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',  // Delegate (where to display the link to the add-on).
                'method' => 'mobile_course_view',          // Main function -> classes/output/mobile.php
                'styles' => [
                    'url' => '',
                    'version' => '1.00'
                ]
            ]
        ],
        'lang' => [ // Language strings that are used in all the handlers.
            ['pluginname', 'autoattendmod'], // matching value in  lang/en/autoattendmod
        ],
    ]
];

