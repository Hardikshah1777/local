<?php

namespace mod_progressreport;

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

    public function col_progressreport($row){
        global $OUTPUT,$DB;

        $sql = "SELECT MAX(attempt) as attempt FROM {progressreport_user} WHERE userid = :userid AND progressreportid = :progresssreportid";
        $attemptrec = $DB->get_record_sql($sql,array('userid' => $row->userid,'progresssreportid' => $row->progressid));

        if(!empty($attemptrec->attempt)){
            $save = $DB->get_record('progressreport_user',['progressreportid' => $row->progressid, 'userid' => $row->userid, 'attempt' => $attemptrec->attempt]);
            $sql = "SELECT MAX(confirm) as confirm FROM {progressreport_user} WHERE userid = :userid AND progressreportid = :progresssreportid";
            $confirmrec = $DB->get_record_sql($sql,array('userid' => $row->userid,'progresssreportid' => $row->progressid));

            if($save == 1 && empty($save->confirm)){
                if(!empty($confirmrec->confirm)){
                    $completeurl = new moodle_url('/mod/progressreport/progressreportuser.php',['userid'=>$row->userid,'progressreportid'=>$row->progressid,'id'=>$row->cmid]);
                    $completeexit = $OUTPUT->single_button($completeurl, get_string('completeprogressreport','mod_progressreport'), 'get');
                }
                $completeurl = new moodle_url('/mod/progressreport/editprogressreport_form.php',['userid'=>$row->userid,'progressreportid'=>$row->progressid,'id'=>$row->cmid,'progressuserid' => $save->id]);
                $complete = $OUTPUT->single_button($completeurl, get_string('continueprogressreport','mod_progressreport'), 'get');
                return($completeexit.$complete);
            }else{
                $completeurl = new moodle_url('/mod/progressreport/progressreportuser.php',['userid'=>$row->userid,'progressreportid'=>$row->progressid,'id'=>$row->cmid]);
                $complete = $OUTPUT->single_button($completeurl, get_string('completeprogressreport','mod_progressreport'), 'get');

                $newurl = new moodle_url('/mod/progressreport/progressreport_form.php',['id' => $row->cmid, 'progressreportid' => $row->progressid, 'userid' => $row->userid]);
                $newprogressreport = $OUTPUT->single_button($newurl, get_string('newprogressreport','mod_progressreport'), 'get');
                return($complete . $newprogressreport);
            }

        }else{
            $newurl = new moodle_url('/mod/progressreport/progressreport_form.php',['id' => $row->cmid, 'progressreportid' => $row->progressid, 'userid' => $row->userid]);
            $newprogressreport = $OUTPUT->single_button($newurl, get_string('newprogressreport','mod_progressreport'), 'get');
            return ($newprogressreport);
        }

    }
}