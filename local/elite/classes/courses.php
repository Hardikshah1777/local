<?php

namespace local_elite;

use moodle_url;
use table_sql;

require_once($CFG->libdir . '/tablelib.php');

class courses extends table_sql
{
    public function showdata()
    {
        $col = [
            'id' => 'id',
            'shortname' => 'Course',
        ];
        $url = new moodle_url('/local/elite/course.php');
        $this->define_columns(array_keys($col));
        $this->define_headers(array_values($col));
        $this->sortable(false);
        $this->collapsible(false);
        $this->define_baseurl($url);

        $this->set_sql('*', '{course}', '1=1');
        $this->out(15, false);
    }
}