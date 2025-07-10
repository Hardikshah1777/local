<?php

namespace mod_evaluation;

use moodle_url;
use table_sql;

class users_table extends table_sql {
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
    public function col_evaluation($row){
        global $USER,$DB;
        $evaluationurl = new moodle_url('/mod/evaluation/userview.php',['id'=>$row->id,'moduleid'=>$USER->moduleid,'attempt'=>$row->attempt]);
        $value = '<a class="btn btn-primary" href="'.$evaluationurl.'">'.get_string('viewevaluation','mod_evaluation').'</a>';

        return $value;
    }
}