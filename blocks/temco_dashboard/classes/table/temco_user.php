<?php

namespace block_temco_dashboard\table;

use context;
use context_system;
use core_table\dynamic;

require_once($CFG->libdir . '/tablelib.php');

class temco_user extends \table_sql implements dynamic {

    const perpage = 30;

    private $defaultperpage = self::perpage;

    protected $cache = [];

    /**
     * @var array
     */
    protected $cohorts;

    public static function get_course_progress($course)
    {
        return \core_completion\progress::get_course_progress_percentage($course);
    }

    public function out($pagesize = self::perpage, $useinitialsbar = false, $downloadhelpbutton = '') {
        global $USER, $DB;

        [
                'cohortid' => $cohortid,
                'idnumber' => $idnumber,
                'fullname' => $fullname,
        ] = $this->get_filters();

        $cols = [
                'idnumber' => get_string('userid', 'block_temco_dashboard'),
                'uname' => get_string('fullname', 'block_temco_dashboard'),
                'cohortname' => get_string('cohortname', 'block_temco_dashboard'),
                'coursename' => get_string('coursename', 'block_temco_dashboard'),
                'duedate' => get_string('duedate', 'block_temco_dashboard'),
                'completiondate' => get_string('completiondate', 'block_temco_dashboard'),
        ];
        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
        $params['suspended'] = 0;
        $params['deleted'] = 0;

        $fields = ' CONCAT(u.id,"#", c.id) as uniqueid,
                    tab.userid, 
                    tab.courseid, 
                    CONCAT(u.firstname," ",u.lastname) as uname, 
                    c.fullname as coursename, 
                    cc.timecompleted as completiondate,
                    tab.duedate,
                    u.idnumber,
                    c.duration,';

        $from = '( SELECT ue.userid, e.courseid, MAX(ue.timestart) as duedate FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid GROUP BY ue.userid, e.courseid ) tab
                    JOIN {user} u ON u.id = tab.userid
                    JOIN {course} c ON c.id = tab.courseid
                    LEFT JOIN {course_completions} cc ON cc.userid = tab.userid AND cc.course = tab.courseid';

        $where = 'u.suspended = :suspended AND u.deleted = :deleted';

        $managerfield = '';
        $systemcontext = context_system::instance();
        if (has_capability('block/temco_dashboard:view', $systemcontext, null, false)) {
            $cohortfield = $cohortwhere = '';
            if (!empty($cohortid)) {
                $cohortwhere = ' AND cmember.cohortid = :managercohort1';
                $params['managercohort1'] = $cohortid;
            }

            $managerfield = 'AND co1.id IN (SELECT cmember.cohortid FROM {cohort_members} cmember
            WHERE cmember.userid = :managerid '. $cohortfield .')';

            $filteridnumber = !empty($idnumber) ? ' AND u.idnumber LIKE "%'.$idnumber.'%"' : '';
            $filterfullname = !empty($fullname) ?  ' AND CONCAT(u.firstname," ",u.lastname) LIKE "%'.$fullname.'%"' : '';
            $where .= ' AND tab.userid IN (SELECT umember.userid FROM {cohort_members} cmember
                JOIN {cohort_members} umember ON umember.cohortid = cmember.cohortid
                WHERE cmember.userid = :manager '. $cohortwhere .') '.$filteridnumber . $filterfullname;
            $params['manager'] = $USER->id;
            $params['managerid'] = $USER->id;
        }

        $fields .= ' (SELECT GROUP_CONCAT(co1.name) FROM {cohort_members} cohortmembers JOIN {cohort} co1 ON co1.id = cohortmembers.cohortid
        where cohortmembers.userid = tab.userid '. $managerfield .') as cohortname';

        if (!has_capability('block/temco_dashboard:view', $systemcontext, null, false)) {
            if (!empty($cohortid)) {
                $from .= ' JOIN {cohort_members} cm ON cm.userid = u.id';
                $where .= ' AND cm.cohortid = :cohortid';
                $params['cohortid'] = $cohortid;
            }
            $where .= !empty($idnumber) ? ' AND u.idnumber LIKE "%'.$idnumber.'%"' : '';
            $where .= !empty($fullname) ? ' AND CONCAT(u.firstname," ",u.lastname) LIKE "%'.$fullname.'%"' : '';
        }

        $where .= ' ORDER BY u.id';

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql('SELECT COUNT(1) FROM '. $from.' WHERE ' . $where, $params);
        $this->sortable(false);
        $this->collapsible(false);
        $this->attributes['border'] = 1;
        parent::out($pagesize, false, $downloadhelpbutton);
    }

    public function start_html() {
        $oldvalue = $this->use_pages;
        $this->use_pages = false;
        parent::start_html();
        $this->use_pages = $oldvalue;
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
        if (in_array($column, array_keys($this->cache))) {
            if (!in_array($row->userid, $this->cache[$column])) {
                $this->cache[$column][] = $row->userid;
                return $row->$column;
            } else {
                return '';
            }
        }
        return null;
    }

    public function col_duedate($row) {
        if (!empty($row->duration) && !empty($row->duedate)) {
            $row->duedate += $row->duration;
        }
        $class = ' text-center ';
        if (!empty($row->duedate)) {
            $duedate = userdate($row->duedate, '%d/%m/%Y');
            if (!empty($row->completiondate)) {
                if ($row->completiondate < $row->duedate) {
                    $class .= 'bg-success';
                } else {
                    $class .= 'bg-danger';
                }
            } else if ($row->duedate > time()) {
                $class .= 'bg-warning';
            } else {
                $class .= 'bg-danger';
            }
        } else {
            $duedate = '-';
        }
        $this->column_class['duedate'] = $class;


        return $duedate;
    }

    public function col_completiondate($row) {
        $completiondate = '';
        if (!empty($row->completiondate)) {
            $completiondate = userdate($row->completiondate, '%d/%m/%Y');
        }
        $this->column_class['completiondate'] = ' text-center';

        return $completiondate;
    }

    /**
     * Set the default per page.
     *
     * @param int $defaultperpage
     */
    public function set_default_per_page(int $defaultperpage): void {
        $this->defaultperpage = $defaultperpage;
    }

    public function get_default_per_page(): int {
        return $this->defaultperpage;
    }

    public function get_filters() {
        $filters = [];

        foreach ($this->filterset->get_filters() as $filter) {
            $filters[$filter->get_name()] = !isset($filters[$filter->get_name()]) ?
                    $filter->current() :  $filter->get_filter_values();
        }
        return $filters;
    }

    public function getcohorts(): array {
        global $DB, $USER;
        if (has_capability('block/temco_dashboard:view', context_system::instance(), null, false)) {
            $cohorts = $DB->get_records_sql('SELECT cmember.cohortid FROM {cohort_members} cmember WHERE cmember.userid = :managerid', ['managerid' => $USER->id]);
            $managercohort = implode(',', array_keys($cohorts));
            if (empty($managercohort)) {
                return [];
            }
            $sql = "SELECT DISTINCT c.id,c.name FROM {cohort} c JOIN {cohort_members} cm ON cm.cohortid = c.id WHERE c.id IN (". $managercohort .") AND c.visible = :visible";
            $params['visible'] = 1;
        }else {
            $sql = "SELECT id,name FROM {cohort} WHERE visible = :visible";
            $params['visible'] = 1;
        }
        $this->cohorts = $DB->get_records_sql_menu($sql, $params);
        return $this->cohorts;
    }

}