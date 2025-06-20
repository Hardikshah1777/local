<?php

namespace block_courseslist\table;

use core_table\local\filter\filterset;

class allcourse_filterset extends filterset {

    protected function get_optional_filters(): array {
        return [];
    }
}