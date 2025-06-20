<?php

namespace block_courseslist\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;

class courseusers_filterset extends filterset {

    protected function get_required_filters(): array {
        return [
                'id' => integer_filter::class,
                'action' => integer_filter::class,
        ];
    }
}