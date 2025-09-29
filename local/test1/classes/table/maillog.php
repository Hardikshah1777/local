<?php

namespace local_test1\table;

use moodle_url;
use pix_icon;
use table_sql;
require_once($CFG->libdir . '/tablelib.php');

class maillog extends table_sql
{
    public function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
    }

    public function col_name($row) {
        $user = \core_user::get_user($row->userid);
        return fullname($user);
    }

    public function col_mailer($row) {
        $user = \core_user::get_user($row->mailer);
        return fullname($user);
    }

    public function col_type($row) {
        return $row->type ? $row->type : '-';
    }

    public function col_sendtime($row) {
        if (!$this->is_downloading()) {
            $calendarlink = \html_writer::link(new moodle_url('/calendar/view.php', ['view' => 'month', 'time' => userdate($row->sendtime)]), userdate($row->sendtime),
                ['class' => 'text-body text-decoration-none', 'target' => '_blank']);
        }else{
            $calendarlink = userdate($row->sendtime);
        }
        return $calendarlink;
    }

    public function col_resendtime($row)
    {
        if (!empty($row->resendtime)) {
            $timestemps = explode(', ', $row->resendtime);
            $calendarlink = '';
            foreach ($timestemps as $timestemp) {
                $resendtime = userdate($timestemp) . " ,<br><br> ";
                if (!$this->is_downloading()) {
                    $calendarlink .= \html_writer::link( new moodle_url( '/calendar/view.php', ['view' => 'month', 'time' => $timestemp] ), $resendtime,
                        ['class' => 'text-body text-decoration-none', 'target' => '_blank'] );
                }else{
                    $calendarlink .= $resendtime;
                }
            }
            return $calendarlink;
        }else{
            return '-';
        }
    }

    public function col_email($row) {
        $user = \core_user::get_user($row->userid);
        return $user->email;
    }

    public function col_action($row) {
        global $OUTPUT;

        $icon = $OUTPUT->action_link('#', new pix_icon('t/hide', get_string('view')),
            null, ['data-user' => json_encode($row), 'class' => 'viewmail']);

        $resend= $OUTPUT->action_link('#', new pix_icon('e/restore_last_draft', get_string('resend', 'local_test1')),
            null, ['data-user' => json_encode($row), 'class' => 'resendmail']);

        return $icon . $resend;
    }
}