<?php

use core\task\scheduled_task;

namespace local_test1\task;

class test1task extends \core\task\scheduled_task
{
    public function get_name() {
        return get_string('test1task', 'local_test1');
    }

    public function execute() {
        mtrace('cron working');
        return true;
    }
}