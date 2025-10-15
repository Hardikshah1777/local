<?php


namespace local_test1\table;


use core_table\local\filter\filterset;
use core_table\local\filter\string_filter;

class userlist_filterset extends filterset
{
    protected function get_required_filters(): array
    {
        return [
            'search' => string_filter::class,
        ];
    }
}