<?php

namespace report_kln\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;

class userslist_filterset extends filterset {

    protected function get_required_filters(): array {
        return [
            'starttime' => integer_filter::class,
            'endtime' => integer_filter::class,
            'userid' => integer_filter::class,
            'courseid' => integer_filter::class,
        ];
    }

    public function get_singular_filters(): array {
        return array_keys($this->get_required_filters());
    }

}