<?php

namespace mod_meltassessment;

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
    public function col_meltassessment($row){

        $meltassessmenturl = new moodle_url('/mod/meltassessment/meltassessmentuser.php',['id'=>$row->cmid,'attempt'=>$row->attempt,'meltassessmentuserid' => $row->id]);
        $value = '<a class="btn btn-primary" href="'.$meltassessmenturl.'">'.get_string('viewmeltassessment','mod_meltassessment').'</a>';

        return $value;
    }
}