<?php

namespace local_squibit\task;

use core\task\manager;
use local_squibit\observer;
use local_squibit\table\courses;
use local_squibit\table\courses_filterset;

class sync_all_courses extends sync_all_users {

    protected $quecount = 10;

    protected $table;

    public function __construct() {
        $this->set_component('local_squibit');
        $this->table = new courses('user');
        $this->table->set_filterset(new courses_filterset);
    }

    public function execute() {
        global $DB, $USER;
        $customdata = $this->prepare()->get_custom_data();
        $table = $this->table;
        if (!isset($customdata->start)) {
            $customdata->start = 0;
        }

        $courses = $DB->get_records_sql(
            "SELECT c.*,{$table->sql->fields} FROM {$table->sql->from} WHERE {$table->sql->where}",
            $table->sql->params, $customdata->start * $this->quecount, $this->quecount
        );

        foreach ($courses as $course) {
            observer::get_syncedcourse($course, true, null, false);
            $customdata->count--;
            $this->store_custom_data($customdata);
        }

        if ($customdata->count > 0) {
            $customdata->start++;
            $task = new self;
            $task->prepare()->set_userid($USER->id);
            $task->set_custom_data($customdata);
            $task->set_next_run_time(strtotime('+5 seconds'));
            manager::queue_adhoc_task($task);
        }
    }

}
