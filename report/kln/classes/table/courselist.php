<?php

namespace report_kln\table;

use context;
use context_system;
use core_table\local\filter\filterset;
use moodle_url;
use report_kln\util;

class courselist extends dynamictable {

    public function set_filterset(filterset $filterset): void {
        parent::set_filterset($filterset);

        $filters = (object) $this->get_filters();
        $column = [
            'coursename' => get_string('courselist:courses', util::COMPONENT),
            'timespent' => get_string('courselist:timespent', util::COMPONENT),
        ];

        $timewhere = '';
        $params = [];

        if (!empty($filters->starttime)) {
            $timewhere .= ' AND kct.timecreated > :kcttimestart ';
            $params['kcttimestart'] = $filters->starttime;
        }

        if (!empty($filters->endtime)) {
            $timewhere .= ' AND kct.timecreated < :kctendtime ';
            $params['kctendtime'] = $filters->endtime;
        }


        $fields = <<<SQL
course.id, course.fullname as coursename, tab1.timespent
SQL;

        $from = <<<SQL
{course} course
JOIN (
    SELECT kct.courseid, SUM(kct.timespent) AS timespent
    FROM {report_kln_course_timespent} kct
    WHERE kct.userid > 2 {$timewhere}
    GROUP BY kct.courseid
) tab1 ON tab1.courseid = course.id
SQL;

        $where = <<<SQL
course.id > 1
SQL;

        if (!empty($filters->courseid)) {
            $where .= ' AND course.id = :courseid';
            $params['courseid'] = $filters->courseid;
        }

        if (empty($filters->starttime) && empty($filters->endtime)) {
            $where .= ' AND 1 = 0';
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->define_columns(array_keys($column));
        $this->define_headers(array_values($column));
        $this->sortable(false);
        $this->collapsible(false);
    }

    public function get_context(): context {
        return context_system::instance();
    }

    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/report/kln/courses.php');
    }

    public function col_timespent($row) {
        return !empty($row->timespent) ? format_time($row->timespent) : '';
    }
}