<?php

namespace local_squibit\output;

use local_squibit\utility;
use renderable;
use renderer_base;
use templatable;
use local_squibit\table\users;

class user_filterform implements renderable, templatable {

    /**
     * @var users
     */
    private $users;


    public function __construct(users $users) {
        $this->users = $users;
    }

    /**
     * @inheritDoc
     */

    public function export_for_template(renderer_base $output) {
        $filters = $this->users->get_filters();

        $context = [];
        $context['userid'] = !empty($filters['userid']) ? $filters['userid'] : '';
        $context['firstname'] = !empty($filters['firstname']) ? $filters['firstname'] : '';
        $context['lastname'] = !empty($filters['lastname']) ? $filters['lastname'] : '';
        $context['status'] = !empty($filters['status']) ? $filters['status'] : '';
        $statusoptions = [];
        foreach (utility::STATUSES as $statuskey => $statusvalue) {
            if(!empty($statusvalue)) {
                $statusoptions[] = [ 'value' => $statusvalue, 'key' => get_string($statuskey, 'local_squibit')];
            }
        }
        $context['statusoption'] = $statusoptions;

        return $context;
    }
}