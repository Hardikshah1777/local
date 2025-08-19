<?php

namespace local_test1\task;

class test1task extends \core\task\scheduled_task
{
    public function get_name() {
        return get_string('test1task', 'local_test1');
    }

    public function can_run(): bool {
        return true;
        //return $this->is_component_enabled() || $this->get_run_if_component_disabled();
    }

    public function execute() {
        global $USER, $CFG;
//        mtrace('cron working by cmd: php admin/tool/task/cli/schedule_task.php --execute="\local_test1\task\test1task"');
        $mailtxt = get_string('test1task', 'local_test1');
        $attechment = \html_writer::img($CFG->wwwroot.'/local/test1/pix/test1.jpg','Test1 image', ['width' => '50%']);
        email_to_user($USER, $USER, $mailtxt, $mailtxt, $mailtxt, $attechment, 'test1.jpg');
        return true;
    }
}