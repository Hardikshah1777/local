<?php

namespace report_kln\table;

use context;
use context_system;
use core_table\local\filter\filterset;
use html_writer;
use moodle_url;
use report_kln\util;

class userslist extends dynamictable {

    protected $courses = null;

    public function set_filterset(filterset $filterset): void {
        parent::set_filterset($filterset);

        $filters = (object) $this->get_filters();
        $column = [
            'studentname' => get_string('userlist:studentname', util::COMPONENT),
            'email' => get_string('userlist:email', util::COMPONENT),
            'timespent' => get_string('userlist:timespent', util::COMPONENT),
            'courses' => get_string('userlist:courses', util::COMPONENT),
        ];

        $timewhere = '';
        $params = [];

        if (!empty($filters->starttime)) {
            $timewhere .= ' AND kct.timecreated >= :kcttimestart ';
            $params['kcttimestart'] = $filters->starttime;
        }

        if (!empty($filters->endtime)) {
            $timewhere .= ' AND kct.timecreated <= :kctendtime ';
            $params['kctendtime'] = $filters->endtime;
        }

        if (!empty($filters->courseid)) {
            $timewhere .= ' AND kct.courseid = :kctcourseid';
            $params['kctcourseid'] = $filters->courseid;
        }

        $fields = <<<SQL
u.*, tab1.timespent, tab1.courses
SQL;

        $from = <<<SQL
{user} u
JOIN (
    SELECT kct.userid, SUM(kct.timespent) AS timespent, GROUP_CONCAT(DISTINCT kct.courseid) as courses
    FROM {report_kln_course_timespent} kct
    WHERE 1 = 1 {$timewhere}
    GROUP BY kct.userid
) tab1 ON tab1.userid = u.id
SQL;

        $where = <<<SQL
u.id > 2 AND u.suspended = 0 AND u.deleted = 0 
SQL;

        if (!empty($filters->courseid)) {
            $from .= ' JOIN {user_enrolments} ue ON ue.userid = u.id JOIN {enrol} e ON e.id = ue.enrolid ';
            $where .= ' AND e.courseid = :courseid';
            $params['courseid'] = $filters->courseid;
        }

        if (!empty($filters->userid)) {
            $where .= ' AND u.id = :userid';
            $params['userid'] = $filters->userid;
        }

        if (empty($filters->starttime) && empty($filters->endtime)) {
            $where .= ' AND 1 = 0';
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->define_columns(array_keys($column));
        $this->define_headers(array_values($column));
        $this->sortable(false);
        $this->collapsible(false);
        $this->get_courses();
    }

    public function get_context(): context {
        return context_system::instance();
    }

    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/report/kln/index.php');
    }

    public function get_courses() {
        if (is_null($this->courses)) {
            $this->courses = util::get_courses();
        }
        return $this->courses;
    }

    public function col_studentname($row) {
        $viewurl = new moodle_url('/user/profile.php', ['id' => $row->id]);
        return html_writer::link($viewurl, "{$row->firstname} {$row->lastname}");
    }

    public function col_timespent($row) {
        return !empty($row->timespent) ? util::kln_format_time($row->timespent) : '';
    }

    public function col_courses($row) {
        $courseids = explode(',', $row->courses);

        $coursenames = [];
        foreach ($courseids as $courseid) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
            $coursenames[] = html_writer::link($courseurl, $this->courses[$courseid]);
        }
        return join(',<br>', $coursenames);
    }

}