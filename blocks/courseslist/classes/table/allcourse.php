<?php

namespace block_courseslist\table;

use context_course;
use context_system;
use core_table\dynamic;
use html_writer;
use moodle_url;
use table_sql;
use context;

require_once($CFG->libdir . '/tablelib.php');

class allcourse extends table_sql implements dynamic {

    const perpage = 30;

    const ACTION = [
            'enrol' => 1,
            'started' => 2,
            'complete' => 3,
    ];

    protected $tabcols = null;

    public function __construct($uniqueid) {
        global $DB;
        parent::__construct($uniqueid);

        $cols = [
          'coursename' => get_string('coursename', 'block_courseslist'),
          'enrolcount' => get_string('enroltotaluser', 'block_courseslist'),
          'startedcount' => get_string('startedcount', 'block_courseslist'),
          'completecount' => get_string('completioncount', 'block_courseslist'),
          'completionpercentage' => get_string('completionpercentage', 'block_courseslist'),
        ];

        $this->tabcols = array_keys($cols);

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));

        $fields = <<<SQL
c.id, c.fullname as coursename, enrol.ecount as enrolcount, completed.ccount as completecount, started.scount as startedcount
SQL;

        $from = <<<SQL
{course} c
LEFT JOIN (
    SELECT e.courseid, COUNT(DISTINCT ue.userid) as ecount
    FROM {user_enrolments} ue
    JOIN {enrol} e ON e.id = ue.enrolid
    GROUP BY e.courseid
) enrol ON enrol.courseid = c.id
LEFT JOIN (
    SELECT cc.course, COUNT(DISTINCT cc.userid) as ccount
    FROM {course_completions} cc
    JOIN {user_enrolments} ue2 ON ue2.userid = cc.userid
	JOIN {enrol} e2 ON e2.id = ue2.enrolid AND e2.courseid = cc.course
    WHERE timecompleted IS NOT NULL
    GROUP BY cc.course
) completed ON completed.course = c.id
LEFT JOIN (
    SELECT cm.course, COUNT(DISTINCT cmc.userid) as scount
    FROM {course_modules} cm
    JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id
    JOIN {user_enrolments} ue1 ON ue1.userid = cmc.userid
	JOIN {enrol} e1 ON e1.id = ue1.enrolid AND e1.courseid = cm.course
    LEFT JOIN {course_completions} cc ON cc.course = cm.course AND cc.userid = cmc.userid
    WHERE cc.timecompleted IS NULL AND cmc.completionstate != 0
    GROUP BY cm.course
) started ON started.course = c.id 
SQL;

        $where = <<<SQL
c.id > 1 AND c.visible = 1
SQL;

        $courseids = self::get_courseids();
        if (!empty($courseids) && !is_siteadmin()) {
            [$cin, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid', true);
            $where .= " AND c.id {$cin}";
        }

        $this->set_sql($fields, $from, $where, $params ?? []);
        $this->sortable(false);
        $this->collapsible(false);
    }

    public function guess_base_url(): void {
    }

    public function get_context() : context {
        return context_system::instance();
    }

    public function render($pagesize = self::perpage, $useinitialsbar = false, $downloadhelpbutton = '') {
        ob_start();
        $this->out($pagesize, $useinitialsbar, $downloadhelpbutton);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public function other_cols($column, $row) {
        switch ($column) {
            case $this->tabcols[0]:
                return !empty($row->$column) ? self::seturl($row, $column) : '-';
            case $this->tabcols[1]:
            case $this->tabcols[3]:
                return !empty($row->$column) ? self::seturl($row, $column) : 0;
            case $this->tabcols[2]:
                return !empty($row->$column) ? self::seturl($row, $column) : 0;
            case $this->tabcols[4]:
                $completename = $this->tabcols[3];
                $totalname = $this->tabcols[1];
                $total = '';
                if (!empty($row->$totalname) && !empty($row->$completename)) {
                    $total = self::calculat_percentage($row->$completename, $row->$totalname);
                }
                return $total;
        }
    }

    public static function get_courseids() {
        global $USER;
        $usercourses = enrol_get_users_courses($USER->id);
        $courseids = array_column($usercourses, 'id');
        foreach ($courseids as $coursekey => $courseid) {
            $coursecontext = context_course::instance($courseid);
            if (!has_capability('moodle/course:update', $coursecontext) ||
                    !has_capability('block/courseslist:view', $coursecontext)) {
                unset($courseids[$coursekey]);
            }
        }
        return $courseids;
    }

    public static function seturl($row, $columns): string {
        if (empty($row->$columns)) {
            return $row->$columns;
        }
        $coursecontext = context_course::instance($row->id);
        if (!has_capability('block/courseslist:view', $coursecontext)) {
            return $row->$columns;
        }
        $value = $row->$columns;

        if ($columns == 'coursename') {
            $action = self::ACTION['enrol'];
        } else {
            $action = self::ACTION[str_replace('count', '', $columns)];
        }
        $usersurl = new moodle_url('/blocks/courseslist/users.php', ['id' => $row->id, 'action' => $action]);
        return html_writer::tag('a', $value, ['href' => $usersurl->out(false), 'target' => '_blank', 'class' => 'text-decoration-none']);
    }

    public static function calculat_percentage($mark, $total) {
        $percentage = ($mark/$total)*100;
        return round($percentage, 2) . '%';
    }

}