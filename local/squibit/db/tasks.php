<?php

/**
 * Task definition for local_squibit.
 *
 * @package   local_squibit
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
        [
            'classname' => '\local_squibit\task\sync_weekly_report',
            'blocking' => 0,
            'minute' => '0',
            'hour' => '0',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '6',
        ],
        [
            'classname' => '\local_squibit\task\sync_daily_report',
            'blocking' => 0,
            'minute' => '0',
            'hour' => '0',
            'day' => '*',
            'month' => '*',
            'dayofweek' => '*',
        ],
);
