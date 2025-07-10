<?php

namespace mod_meltassessment;

use moodle_url;
use table_sql;
require_once $CFG->libdir . '/tablelib.php';
class users_table extends table_sql{
    public function start_html() {
        $oldvalue = $this->use_pages;
        $this->use_pages = false;
        parent::start_html();
        $this->use_pages = $oldvalue;
    }

	public function col_fullname($row){
        $fullname = $row->firstname.' '.$row->lastname;
        return $fullname;
    }

    public function col_meltassessment($row){
        global $OUTPUT,$DB;

        $sql = "SELECT MAX(attempt) as attempt FROM {meltassessment_user} WHERE userid = :userid AND meltassessmentid = :meltassessmentid";
        $attemptrec = $DB->get_record_sql($sql,array('userid' => $row->userid,'meltassessmentid' => $row->meltassessmentid));

        if(!empty($attemptrec->attempt)){
            $save = $DB->get_record('meltassessment_user',['meltassessmentid' => $row->meltassessmentid, 'userid' => $row->userid, 'attempt' => $attemptrec->attempt]);
            $sql = "SELECT MAX(confirm) as confirm FROM {meltassessment_user} WHERE userid = :userid AND meltassessmentid = :meltassessmentid";
            $confirmrec = $DB->get_record_sql($sql,array('userid' => $row->userid,'meltassessmentid' => $row->meltassessmentid));

            if($save == 1 && empty($save->confirm)){
                if(!empty($confirmrec->confirm)){
                    $completeurl = new moodle_url('/mod/meltassessment/meltassessmentuser.php',['userid'=>$row->userid,'meltassessmentid'=>$row->meltassessmentid,'id'=>$row->cmid]);
                    $completeexit = $OUTPUT->single_button($completeurl, get_string('completemeltassessment','mod_meltassessment'), 'get');
                }
                $completeurl = new moodle_url('/mod/meltassessment/editmeltassessment_form.php',['userid'=>$row->userid,'meltassessmentid'=>$row->meltassessmentid,'id'=>$row->cmid,'meltassessmentuserid' => $save->id]);
                $complete = $OUTPUT->single_button($completeurl, get_string('continuemeltassessment','mod_meltassessment'), 'get');
                return($completeexit.$complete);
            }else{
                $completeurl = new moodle_url('/mod/meltassessment/meltassessmentuser.php',['userid'=>$row->userid,'meltassessmentid'=>$row->meltassessmentid,'id'=>$row->cmid]);
                $complete = $OUTPUT->single_button($completeurl, get_string('completemeltassessment','mod_meltassessment'), 'get');

                $newurl = new moodle_url('/mod/meltassessment/meltassessment_form.php',['id' => $row->cmid, 'meltassessmentid' => $row->meltassessmentid, 'userid' => $row->userid]);
                $newmeltassessment = $OUTPUT->single_button($newurl, get_string('newmeltassessment','mod_meltassessment'), 'get');
                return($complete . $newmeltassessment);
            }

        }else{
            $newurl = new moodle_url('/mod/meltassessment/meltassessment_form.php',['id' => $row->cmid, 'meltassessmentid' => $row->meltassessmentid, 'userid' => $row->userid]);
            $newmeltassessment = $OUTPUT->single_button($newurl, get_string('newmeltassessment','mod_meltassessment'), 'get');
            return ($newmeltassessment);
        }

    }
}