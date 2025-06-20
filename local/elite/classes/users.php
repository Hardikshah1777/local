<?php

namespace local_elite;

use moodle_url;
use table_sql;

require_once($CFG->libdir . '/tablelib.php');

class users extends table_sql
{
    public function showdata()
    {
        $col = [
            'id' => get_string('no'),
            'fullname' => get_string('fullname'),
            'email' => get_string('email'),
            'city' => get_string('city'),
        ];
        $url = new moodle_url('/local/elite/index.php');
        $this->define_columns(array_keys($col));
        $this->define_headers(array_values($col));
        $this->sortable(false);
        $this->collapsible(false);
        $this->define_baseurl($url);

        $this->set_sql('*', '{user}', 'id > 2');
        $this->out(5, false);
    }
}