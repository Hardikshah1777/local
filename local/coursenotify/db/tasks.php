<?php

/**
 * Task definition for local_coursenotify.
 *
 * @package   local_coursenotify
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
        array(
                'classname' => '\local_coursenotify\notifytask',
                'blocking' => 0,
                'minute' => '*/1',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'dayofweek' => '*',
        ),
        [
                'classname' => '\local_coursenotify\notifyenroltask',
                'blocking' => 0,
                'minute' => '0',
                'hour' => '1',
                'day' => '*',
                'month' => '*',
                'dayofweek' => '*',
        ],
        [
                'classname' => '\local_coursenotify\weeklymanagermail',
                'blocking' => 0,
                'minute' => '0',
                'hour' => '9',
                'day' => '*',
                'month' => '*',
                'dayofweek' => '6',
        ],
        [
                'classname' => '\local_coursenotify\weeklynoccmanagermail',
                'blocking' => 0,
                'minute' => '0',
                'hour' => '9',
                'day' => '*',
                'month' => '*',
                'dayofweek' => '6',
        ],
);
