<?php

namespace block_courseslist\table;

use context;
use context_system;
use core_table\dynamic;
use core_table\local\filter\filterset;
use table_sql;

require_once($CFG->libdir . '/tablelib.php');

class courseusers extends table_sql implements dynamic {

    const perpage = 30;

    public function set_filterset(filterset $filterset): void {
        parent::set_filterset($filterset);

        $filters = (object) $this->get_filters();

        $courseid = $filters->id;
        $action = $filters->action;

        $cols = [
                'firstname' => get_string('tab:firstname', 'block_courseslist'),
                'lastname' => get_string('tab:lastname', 'block_courseslist'),
                'email' => get_string('tab:email', 'block_courseslist'),
                'timecompleted' => get_string('tab:timecompleted', 'block_courseslist'),
                'cpercentage' => get_string('tab:completionpercentage', 'block_courseslist'),
        ];

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));

        $fields = <<<SQL
ue.id, u.firstname, u.lastname, u.email, cc.timecompleted, cmtable.*, completecmtab.*
SQL;

        $from = <<<SQL
{user_enrolments} ue
JOIN {enrol} e ON e.id = ue.enrolid
JOIN {user} u ON u.id = ue.userid 
LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = u.id
LEFT JOIN (
    SELECT cm.course, COUNT(cm.id) as cmcount
    FROM {course_modules} cm
    WHERE cm.course = :courseid1 AND cm.completion != 0
    GROUP BY cm.course
) as cmtable ON cmtable.course = e.courseid
LEFT JOIN (
    SELECT cmc.userid, COUNT(cmc.userid) as cmcompletecount
    FROM {course_modules} cm
    JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id
    WHERE cm.course = :courseid2 AND cmc.completionstate != 0
    GROUP BY cmc.userid
) as completecmtab ON completecmtab.userid = u.id 
SQL;

        $where = <<<SQL
e.courseid = :courseid
SQL;
        $params['courseid'] = $params['courseid1'] = $params['courseid2'] = $courseid;

        if ($action == allcourse::ACTION['started']) {
            $fields .= <<<SQL
, started.*
SQL;

            $from .= <<<SQL
LEFT JOIN (
    SELECT cm1.course, cmc1.userid, COUNT(DISTINCT cmc1.userid) as scount
    FROM {course_modules_completion} cmc1
    JOIN {course_modules} cm1 ON cm1.id = cmc1.coursemoduleid
    LEFT JOIN {course_completions} cc1 ON cc1.course = cm1.course AND cc1.userid = cmc1.userid
    WHERE cc1.timecompleted IS NULL AND cmc1.completionstate != 0
    GROUP BY cm1.course, cmc1.userid
) as started ON started.course = e.courseid AND started.userid = u.id  
SQL;

            $where .= <<<SQL
AND started.scount >= 1
SQL;

        }

        if ($action == allcourse::ACTION['complete']) {
            $where .= <<<SQL
AND cc.timecompleted IS NOT NULL
SQL;

        }

        $this->set_sql($fields, $from, $where, $params);
        $this->sortable(false);
        $this->collapsible(false);
    }

    public function guess_base_url(): void {
    }

    public function get_context() : context {
        return context_system::instance();
    }

    public function get_filters(bool $nonemptyonly = true): array {
        $filters = [];
        foreach ($this->filterset->get_filters() as $filter) {
            $filtername = $filter->get_name();
            $filters[$filtername] = !isset($filters[$filtername]) ? $filter->current(): $filter->get_filter_values();
        }
        if ($nonemptyonly) {
            $filters = array_filter($filters, fn($filtername) => $this->filterset->has_filter($filtername), ARRAY_FILTER_USE_KEY);
        }
        return $filters;
    }

    public function render($pagesize = self::perpage, $useinitialsbar = false, $downloadhelpbutton = '') {
        ob_start();
        $this->out($pagesize, $useinitialsbar, $downloadhelpbutton);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public function col_timecompleted($row) {
        return !empty($row->timecompleted) ? userdate($row->timecompleted, '%d-%m-%y') : '-';
    }

    public function col_cpercentage($row) {
        if (!empty($row->cmcompletecount) && !empty($row->cmcount)) {
            return allcourse::calculat_percentage($row->cmcompletecount, $row->cmcount);
        } else {
            return '-';
        }
    }

}