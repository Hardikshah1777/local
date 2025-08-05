<?php


namespace local_test1\task;

class test1task extends \core\task\scheduled_task
{
    public function get_name() {
        return get_string('test1task', 'local_test1');
    }

    public function execute() {
        global $USER;
        mtrace('cron working local/test1/classes/task/test1task.php');
        email_to_user($USER, $USER,
            get_string('test1task', 'local_test1'),
            get_string('test1task', 'local_test1')
        );
        return true;
    }
}