<?php

namespace mod_meltassessment;

use moodle_url;
use table_sql;
require_once $CFG->libdir . '/tablelib.php';

class meltassessmentuser_table extends table_sql {
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
        global $OUTPUT,$DB;

        $sql = "SELECT MAX(attempt) as attempt FROM {meltassessment_user} WHERE userid = :userid AND meltassessmentid = :meltassessmentid";
        $attemptrec = $DB->get_record_sql($sql,array('userid' => $row->userid,'meltassessmentid' => $row->meltassessmentid));

        if(!empty($attemptrec->attempt)){

            $viewurl = new moodle_url('/mod/meltassessment/meltassessmentuser.php',['id' => $row->cmid,'attempt'=>$row->attempt,'meltassessmentuserid'=>$row->id]);
            $view = $OUTPUT->single_button($viewurl, get_string('view'), 'get');

            $pdfurl = new moodle_url('/mod/meltassessment/pdf.php',['id' => $row->id]);
            $download = $OUTPUT->single_button($pdfurl, get_string('download'), 'get');

            return($view.$download);
        }
    }
}