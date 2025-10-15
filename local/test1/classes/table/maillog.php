<?php

namespace local_test1\table;

use coding_exception;
use context;
use context_system;
use core_table\dynamic;
use core_table\local\filter\filterset;
use moodle_url;
use pix_icon;
use table_sql;
require_once($CFG->libdir . '/tablelib.php');

class maillog extends table_sql implements dynamic
{
    public function set_filterset(filterset $filterset): void
    {
        global $CFG, $DB;
        parent::set_filterset($filterset);

        $filter = $this->get_filters();

        $col = [
            'name' => get_string('name','local_test1'),
            'email' => get_string('email1','local_test1'),
            'mailer' => get_string('mailer','local_test1'),
            'type' => get_string('type','local_test1'),
            'sendtime' => get_string('sendtime','local_test1'),
            'resendtime' => get_string('resendtime','local_test1'),
            'action' => get_string('action','local_test1'),
        ];

        $this->define_headers(array_values($col));
        $this->define_columns(array_keys($col));
        $this->sortable(true,'sendtime', SORT_DESC);
        $this->no_sorting('name');
        $this->no_sorting('email');
        $this->no_sorting('action');
        $this->showdownloadbuttonsat = [TABLE_P_BOTTOM];
        $this->is_downloadable(true);

        $this->collapsible(false);
        if ($filter['userid'] <= 10) {
            $this->attributes = ['style' => 'background-image: url(' . $CFG->wwwroot . '/local/test1/pix/test1.jpg); background-repeat: no-repeat; background-size: cover;'];
        }

        $params['userid'] = $filter['userid'];

        $where = '';
        if (!empty($filter['type'])) {
            $where .= " AND (" . $DB->sql_like('ml.type', ':type', false). " ) ";
            $params['type'] .= $filter['type'];
        }

        if (!empty($filter['timestart'])) {
            $where .= ' AND sendtime >= :timestart';
            $params['timestart'] .= $filter['timestart'];
        }

        if (!empty($filter['timeend'])) {
            $where .= ' AND sendtime <= :timeend';
            $params['timeend'] .= $filter['timeend'];
        }


        $this->set_sql('ml.id,u.firstname, u.lastname, u.email, ml.userid as userid, ml.mailer, ml.type, ml.subject, ml.body, ml.sendtime, ml.resendtime',
            '{local_test1_mail_log} ml
                       JOIN {user} u ON u.id = ml.userid',
            'ml.userid = :userid '.$where, $params);

    }

    public function get_context(): context
    {
        return context_system::instance();
    }

    public function get_filters() {
        $filters = [];

        if (!$this->filterset instanceof filterset) {
            throw new coding_exception('Unknown filterset class');
        }

        foreach ($this->filterset->get_filters() as $filter) {
            $filters[$filter->get_name()] = !isset($filters[$filter->get_name()]) ?
                $filter->current() :  $filter->get_filter_values();
        }
        return $filters;
    }

    public function guess_base_url(): void
    {
        $filters = $this->get_filters();
        $this->baseurl = new moodle_url('/local/test1/maillog.php', $filters);
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
            $calendarlink = \html_writer::link(new moodle_url('/calendar/view.php', ['view' => 'month', 'time' => $row->sendtime]), userdate($row->sendtime),
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