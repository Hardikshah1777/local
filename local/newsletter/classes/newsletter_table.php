<?php

namespace local_newsletter;

use confirm_action;
use core_table\external\dynamic\get;
use html_writer;
use moodle_url;
use pix_icon;
use table_sql;

global $CFG;

require_once($CFG->libdir . '/tablelib.php');
class newsletter_table extends table_sql {
    public function col_scheduledate($row) {
        $scheduledate = userdate($row->scheduledate,get_string('strftimedatetime', 'langconfig'));
        return $scheduledate;
    }

    public function col_remindermail($row) {
        if (empty($row->remindermail)) {
            return get_string('no');
        }else {
            return get_string('yes');
        }
    }

    public function col_action($row) {
        global $OUTPUT;

        $deleteurl = new moodle_url('/local/newsletter/index.php',['id' => $row->id]);
        $confirm = new confirm_action(get_string('deletemsg', 'local_newsletter'));
        $deletebutton = $OUTPUT->action_link($deleteurl, get_string('delete'), $confirm, null);

        $editurl = new moodle_url('/local/newsletter/addnewsletter.php', ['id' => $row->id]);
        $editbutton = $OUTPUT->action_link($editurl, get_string('edit'), null, null);

        return $editbutton . ' | ' .$deletebutton;
    }
}