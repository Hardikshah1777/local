<?php

namespace local_squibit\external;

use context_system;
use core\task\manager;
use local_squibit\task\sync_all_courses AS task;

class sync_all_courses extends sync_all_users {

    public static function execute(string $action = self::ACTIONS[2]) {
        global $USER;
        $context = context_system::instance();
        empty(AJAX_SCRIPT) || self::validate_context($context);

        ['action' => $action] = self::validate_parameters(self::execute_parameters(), compact('action'));

        $pending = false;
        $count = 0;

        if ($action == self::ACTIONS[0]) {
            $adhoctasks = manager::get_adhoc_tasks(task::class);
            foreach ($adhoctasks as $adhoctask) {
                $count += $adhoctask->get_custom_data()->count;
                break;
            }
            $pending = !empty($count);
        } else if ($action == self::ACTIONS[1]) {
            $pending = true;
            $task = new task;
            $task->prepare()->set_userid($USER->id);
            manager::reschedule_or_queue_adhoc_task($task);
            $count = $task->get_custom_data()->count;
        }

        return compact('pending', 'count');
    }

}
