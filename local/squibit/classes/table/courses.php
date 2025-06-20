<?php

namespace local_squibit\table;

use context;
use context_system;
use core_table\local\filter\filterset;
use html_writer;
use local_squibit\utility;
use moodle_url;
use coding_exception;

class courses extends users {

    public array $str = [];

    public function get_context(): context {
        return context_system::instance();
    }

    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/local/squibit/courses.php');
    }

    public function set_filterset(filterset $filterset): void {
        parent::set_filterset($filterset);
        $this->str['sync'] = get_string('sync', 'local_squibit');
        $this->str['timeformat'] = get_string('dateformat', 'local_squibit');
        foreach (array_keys(utility::STATUSES) as $status) {
            $this->str[$status] = get_string($status, 'local_squibit');
        }

        $cols = [
            'id' => get_string('courseid', 'local_squibit'),
            'coursename' => get_string('coursename', 'local_squibit'),
            'status' => get_string('status', 'local_squibit'),
            'action' => get_string('action', 'local_squibit'),
        ];

        $fields = 'c.id, c.fullname AS coursename, squibit.status, squibit.timemodified AS lastsync, squibit.courseid as courseid';
        $from = '{course} c LEFT JOIN {local_squibit_course} squibit ON squibit.courseid = c.id 
                JOIN {customfield_data} cd ON cd.instanceid = c.id AND cd.fieldid = (SELECT id FROM {customfield_field} cf WHERE cf.shortname = :cshortname )';
        $where = 'c.id <> :siteid';
        $params['siteid'] = SITEID;
        $params['deleted'] = 0;
        $params['cshortname'] = utility::COURSEENABLE;

        $filter = $this->get_filters();
        if (!empty($filter['courseid'])) {
            $where .= ' AND c.id = :courseid';
            $params['courseid'] = $filter['courseid'];
        }
        if (!empty($filter['fullname'])) {
            $where .= ' AND c.fullname LIKE "%'.$filter['fullname'].'%"';
        }

        if (!empty($filter['status'])){
            $where .= ' AND squibit.status = :filterstatus';
            $params['filterstatus'] = $filter['status'];
        }

        if (!empty($filter['courseteacher'])) {
            if ($filter['courseteacher'] == 1) {
                $where .= ' AND squibit.teacher = ""';
            } else if ($filter['courseteacher'] == 2) {
                $where .= ' AND squibit.teacher != ""';
            }
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
        $this->sortable(false);
        $this->collapsible(false);
    }

    public function col_id($data) {
        return $data->id;
    }

    public function col_action($data) {
        if ($data->status !== utility::STATUSES['success']) {
            return html_writer::tag('a', $this->str['sync'],
                ['class' => 'btn btn-secondary actionbutton', 'data-courseid' => $data->id, 'data-action' => 'synccourse']);
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
