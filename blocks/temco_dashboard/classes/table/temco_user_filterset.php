<?php

namespace block_temco_dashboard\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

class temco_user_filterset extends filterset {

    protected function get_optional_filters(): array {
        return [
                'cohortid' => integer_filter::class,
                'idnumber' => string_filter::class,
                'fullname' => string_filter::class,
        ];
    }
}