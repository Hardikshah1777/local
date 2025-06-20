<?php
/**
 * Schedule tasks
 *
 * @package     blocks/temco_dashboard
 * @author      Your Name
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 $tasks = [
    array(
        'classname' => '\block_temco_dashboard\task\email_task',
        'blocking' => 0,
        'minute' => 0,
        'hour' => 12,
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
     ),

    array(
        'classname' => '\block_temco_dashboard\task\mailbefore_twodays',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    )
];