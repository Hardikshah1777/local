<?php

namespace local_test1\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

class maillog_filterset extends filterset
{
    protected function get_required_filters(): array
    {
        return [
            'userid' => integer_filter::class,
        ];
    }

    protected function get_optional_filters(): array
    {
        return [
            'type' => string_filter::class,
            'timestart' => integer_filter::class,
            'timeend' => integer_filter::class,
        ];
    }
}