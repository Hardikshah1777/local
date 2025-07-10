<?php

namespace local_squibit\table;

use context;
use context_system;
use core_table\dynamic;
use core_table\local\filter\filterset;
use html_writer;
use local_squibit\utility;
use moodle_url;
use table_sql;
use coding_exception;

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

class users extends table_sql implements dynamic {

    public array $str = [];

    public function get_context(): context {
        return context_system::instance();
    }

    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/local/squibit/users.php');
    }

    public function set_filterset(filterset $filterset): void {
        global $CFG, $DB;
        parent::set_filterset($filterset);
        $this->str['sync'] = get_string('sync', 'local_squibit');
        $this->str['timeformat'] = get_string('dateformat', 'local_squibit');
        foreach (array_keys(utility::STATUSES) as $status) {
            $this->str[$status] = get_string($status, 'local_squibit');
        }

        $cols = [
            'id' => get_string('userid', 'local_squibit'),
            'firstname' => get_string('firstname', 'local_squibit'),
            'lastname' => get_string('lastname', 'local_squibit'),
            'email' => get_string('email', 'local_squibit'),
            'status' => get_string('status', 'local_squibit'),
            'action' => get_string('action', 'local_squibit'),
        ];

        $profid = $DB->get_field('user_info_field', 'id', ['shortname' => utility::PROFILE]);
        $userids = $CFG->siteadmins .','.$CFG->siteguest;
        $fields = 'u.id, squibit.userid,u.firstname, u.lastname, u.email, squibit.status, squibit.timemodified AS lastsync';
        $from = '{user} u LEFT JOIN {local_squibit_users} squibit ON squibit.userid = u.id ';
        $from .= ' LEFT JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = :profid';
        $where = 'u.deleted = :deleted AND u.id NOT IN ('.$userids.')';
        $where .= ' AND (d.id IS NULL OR ' . $DB->sql_like('d.data', ':none', false, false, true) . ')';
        $params['deleted'] = 0;
        $params['profid'] = empty($profid) ? 0 : $profid;
        $params['none'] = utility::DEFAULTPROFILE;

        $filter = $this->get_filters();
        if (!empty($filter['userid'])){
            $where .= ' AND u.id = :filteruserid';
            $params['filteruserid'] = $filter['userid'];
        }
        if (!empty($filter['firstname'])){
            $where .= ' AND u.firstname LIKE "%'.$filter['firstname'].'%"';
        }
        if (!empty($filter['lastname'])){
            $where .= ' AND u.lastname LIKE "%'.$filter['lastname'].'%"';
        }
        if (!empty($filter['status'])){
            $where .= ' AND squibit.status = :filterstatus';
            $params['filterstatus'] = $filter['status'];
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
        $this->sortable(false);
        $this->collapsible(false);
    }

    public function col_id($data) {
        return str_pad($data->id, 6, 0, STR_PAD_LEFT);
    }

    public function col_status($data) {
        if (!empty($data->status)) {
            $statusconst = array_search($data->status, utility::STATUSES);
            $status = empty($this->str[$statusconst]) ? '' : $this->str[$statusconst];
            if (!empty($data->lastsync) && $statusconst == array_keys(utility::STATUSES)[1]) {
                $status = html_writer::div(
                    html_writer::div($status, 'bold').
                    html_writer::div(userdate($data->lastsync, $this->str['timeformat']), 'small')
                );
            }
            return $status;
        }
        return '';
    }

    public function col_action($data) {
        if ($data->status !== utility::STATUSES['success']) {
            return html_writer::tag('a', $this->str['sync'],
                ['class' => 'btn btn-secondary actionbutton', 'data-userid' => $data->id, 'data-action' => 'syncuser']);
        }
        return '';
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

}
