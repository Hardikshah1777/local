<?php

namespace local_squibit\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

class users_filterset extends filterset {

    protected function get_optional_filters(): array {
        return [
                'userid' => integer_filter::class,
                'firstname' => string_filter::class,
                'lastname' => string_filter::class,
                'status' => integer_filter::class,
        ];
    }
}