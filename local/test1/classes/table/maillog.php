<?php

namespace local_test1\table;

use table_sql;
require_once($CFG->libdir . '/tablelib.php');

class maillog extends table_sql
{
    public function __construct($uniqueid)
    {
        parent::__construct( $uniqueid );
    }

    public function col_name($row) {
        $user = \core_user::get_user($row->userid);
        return fullname($user);
    }

    public function col_mailer($row) {
        $user = \core_user::get_user($row->mailer);
        return fullname($user);
    }

    public function col_type($row){
        return $row->type ? $row->type : '-';
    }

    public function col_sendtime($row){
        return $row->sendtime ? userdate($row->sendtime) : '-';
    }
}