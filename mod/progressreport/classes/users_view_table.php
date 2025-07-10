<?php

namespace mod_progressreport;

use moodle_url;
use table_sql;

class users_view_table extends table_sql {
    public function start_html() {
        $oldvalue = $this->use_pages;
        $this->use_pages = false;
        parent::start_html();
        $this->use_pages = $oldvalue;
    }
    public function col_date($row){
        $a = $row->timemodified;
        $t = gmdate('d M Y', $a);
        return $t;
    }
    public function col_progressreport($row){

        $progressurl = new moodle_url('/mod/progressreport/progressuser.php',['id'=>$row->cmid,'attempt'=>$row->attempt,'progressreportuserid' => $row->id]);
        $value = '<a class="btn btn-primary" href="'.$progressurl.'">'.get_string('viewprogressreport','mod_progressreport').'</a>';

        return $value;
    }
}