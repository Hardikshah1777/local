<?php

namespace mod_test\table;
use moodle_url;
use table_sql;
require_once $CFG->libdir . '/tablelib.php';

class alltestmod extends table_sql
{
    public function __construct($uniqueid)
    {
        parent::__construct( $uniqueid );
    }

    public function col_shortname($row) {
        $curl = new moodle_url('/course/view.php', ['id' => $row->courseid]);
        return \html_writer::link($curl, $row->shortname, ['class' => 'text-decoration-none','target' => '_blank']);
    }

    public function col_timecreated($row) {
        return $row->timecreated ? userdate($row->timecreated) : '-';
    }
}