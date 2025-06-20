<?php

namespace report_behaviour\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

class behaviour_sessions_filterset extends filterset {

    protected function get_optional_filters(): array {
        return [
                'courseid' => integer_filter::class,
                'cmid' => integer_filter::class,
                'timeselected' => string_filter::class,
                'isweekend' => integer_filter::class

        ];
    }
}