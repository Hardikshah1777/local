<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines mobile handlers.
 *
 * @package   mod_behaviour
 * @copyright 2018 Dan Marsdenb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_behaviour' => [
        'handlers' => [
            'view' => [
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/behaviour/pix/icon.png',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_view_activity',
                'styles' => [
                    'url' => $CFG->wwwroot . '/mod/behaviour/mobilestyles.css',
                    'version' => 22
                ]
            ]
        ],
        'lang' => [ // Language strings that are used in all the handlers.
            ['pluginname', 'behaviour'],
            ['sessionscompleted', 'behaviour'],
            ['pointssessionscompleted', 'behaviour'],
            ['percentagesessionscompleted', 'behaviour'],
            ['sessionstotal', 'behaviour'],
            ['pointsallsessions', 'behaviour'],
            ['percentageallsessions', 'behaviour'],
            ['maxpossiblepoints', 'behaviour'],
            ['maxpossiblepercentage', 'behaviour'],
            ['submitbehaviour', 'behaviour'],
            ['strftimeh', 'behaviour'],
            ['strftimehm', 'behaviour'],
            ['behavioursuccess', 'behaviour'],
            ['behaviour_no_status', 'behaviour'],
            ['behaviour_already_submitted', 'behaviour'],
            ['somedisabledstatus', 'behaviour'],
            ['invalidstatus', 'behaviour'],
            ['preventsharederror', 'behaviour'],
            ['closed', 'behaviour'],
            ['subnetwrong', 'behaviour'],
            ['enterpassword', 'behaviour'],
            ['incorrectpasswordshort', 'behaviour'],
            ['behavioursuccess', 'behaviour'],
            ['setallstatuses', 'behaviour']
        ],
    ]
];
