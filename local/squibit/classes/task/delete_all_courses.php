<?php

namespace local_squibit\task;

use core\task\adhoc_task;
use core\task\manager;
use local_squibit\observer;
use local_squibit\table\courses;
use local_squibit\table\courses_filterset;

class delete_all_courses extends adhoc_task {

    protected $quecount = 100;

    protected $coursetable;

    public function __construct() {
        $this->set_component('local_squibit');
        $this->coursetable = new courses('courses');
        $this->coursetable->set_filterset(new courses_filterset);
        $this->coursetable->sql->where .= ' AND squibit.created = 1';
    }

    public function execute() {
        global $DB, $USER;
        $coursetable = $this->coursetable;
        $customdata = $this->prepare()->get_custom_data();

        if (!isset($customdata->start)) {
            $customdata->start = 0;
        }

        $courses = $DB->get_records_sql(
                "SELECT c.*,{$coursetable->sql->fields} FROM {$coursetable->sql->from} WHERE {$coursetable->sql->where}",
                $coursetable->sql->params, $customdata->start * $this->quecount, $this->quecount
        );
        foreach ($courses as $course) {
            observer::delete_sync_course($course);
            $customdata->count--;
            $this->store_custom_data($customdata);
        }

        if ($customdata->count > 0) {
            $customdata->start++;
            $task = new self;
            $task->prepare()->set_userid($USER->id);
            $task->set_custom_data($customdata);
            $task->set_next_run_time(strtotime('+5 seconds'));
            manager::queue_adhoc_task($task, true);
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
            $table = $this->coursetable;
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