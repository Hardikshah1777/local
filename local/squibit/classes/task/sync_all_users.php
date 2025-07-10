<?php

namespace local_squibit\task;

use core\task\adhoc_task;
use core\task\manager;
use local_squibit\observer;
use local_squibit\table\users;
use local_squibit\table\users_filterset;

class sync_all_users extends adhoc_task {

    protected $quecount = 100;

    protected $table;

    public function __construct() {
        $this->set_component('local_squibit');
        $this->table = new users('user');
        $this->table->set_filterset(new users_filterset);
    }

    public function execute() {
        global $DB, $USER;
        $customdata = $this->prepare()->get_custom_data();
        $table = $this->table;
        if (!isset($customdata->start)) {
            $customdata->start = 0;
        }

        $users = $DB->get_records_sql(
            "SELECT u.*,{$table->sql->fields} FROM {$table->sql->from} WHERE {$table->sql->where}",
            $table->sql->params, $customdata->start * $this->quecount, $this->quecount
        );

        foreach ($users as $user) {
            observer::get_synceduser($user, true, true);
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

    public function prepare() {
        global $DB;
        $customdata = $this->get_custom_data();
        if (empty($customdata)) {
            $customdata = (object) [];
        }
        if (!isset($customdata->start)) {
            $customdata->start = 0;
        }
        if (!isset($customdata->count)) {
            $table = $this->table;
            $customdata->count = $DB->count_records_sql(
                "SELECT COUNT(1) FROM {$table->sql->from} WHERE {$table->sql->where}",
                $table->sql->params
            );
            $this->set_custom_data($customdata);
        }
        return $this;
    }

    public function store_custom_data($customdata) {
        global $DB;
        $this->set_custom_data($customdata);
        $DB->set_field('task_adhoc', 'customdata', $this->get_custom_data_as_string(), ['id' => $this->get_id()]);
        return $this;
    }

}
