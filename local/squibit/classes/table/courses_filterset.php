<?php

namespace local_squibit\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

class courses_filterset extends filterset {

    protected function get_optional_filters(): array {
        return [
                'courseid' => integer_filter::class,
                'fullname' => string_filter::class,
                'status' => integer_filter::class,
                'courseteacher' => integer_filter::class,
        ];
    }
}