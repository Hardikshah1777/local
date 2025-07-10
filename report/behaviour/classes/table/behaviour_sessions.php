<?php

namespace report_behaviour\table;

use coding_exception;
use context_system;
use core_table\dynamic;
use core_table\local\filter\filterset;
use html_writer;
use moodle_url;
use report_behaviour\util;
use table_sql;
use context;

require_once ($CFG->libdir . '/tablelib.php');

class behaviour_sessions extends table_sql implements dynamic {

    protected $courseid;

    public $sessioncolumns = [];

    public $statuscolumns;

    public $lastcol = 'totalsession';

    protected $cmid;

    public $isweekend;

    protected $prefix = [
            'table',
            'col',
            'tablecol',
            'status'
    ];

    public $defaultcolumns;

    public $timeformat;

    public $totalcol;

    protected $lastmidnight;

    public function set_filterset(filterset $filterset): void {
        global $DB;
        parent::set_filterset($filterset);
        $filter = $this->get_filters();
        $this->courseid = $filter['courseid'];
        $this->timeformat = '%d %B %Y';

        $column =  [
                'firstname' => get_string('firstname',util::COMPONENT),
                'lastname' => get_string('lastname',util::COMPONENT),
        ];

        $this->defaultcolumns = $column;

        $modulewhere = '';
        $this->cmid = !empty($filter['cmid']) ? $filter['cmid'] : '';
        if ($filter['cmid']) {
            $modid = $DB->get_field('course_modules', 'instance', ['id' => $this->cmid]);
            $modulewhere .= <<<SQL
AND a.id = {$modid}
SQL;
        }

        if (!empty($filter['isweekend'])) {
            $timestart = strtotime('midnight');
            $timeend = (strtotime('tomorrow') +  (7 * DAYSECS)) - 1;
        } else {
            $timestart = strtotime('midnight');
            $timeend = strtotime('tomorrow') - 1;
        }
        $this->isweekend = !empty($filter['isweekend']);

        if (!empty($filter['timeselected'])) {
            $days = DAYSECS;
            if ($this->isweekend) {
                $days = (7 * DAYSECS);
            }
            $timestart = strtotime($filter['timeselected']);
            $timeend = (strtotime($filter['timeselected']) +  $days) - 1;
        }

        // For behaviour sessions colums
        $colsql = <<<SQL
SELECT bss.id as sessionid, b.course, bss.sessdate FROM {behaviour_sessions} bss
JOIN {behaviour} b ON b.id = bss.behaviourid
WHERE b.course =:courseid AND bss.sessdate >= :timestart AND bss.sessdate <= :timeend {$modulewhere}
SQL;
        $colparams['courseid'] = $this->courseid;
        $colparams['timestart'] = $timestart;
        $colparams['timeend'] = $timeend;
        $behaviourcols = $DB->get_records_sql($colsql, $colparams);

        // For behaviour statuses colums
        $statesql = <<<SQL
SELECT bstatus.id, bstatus.acronym, bstatus.description
FROM {behaviour_statuses} bstatus
JOIN {behaviour} b ON b.id = bstatus.behaviourid
WHERE b.course = :cid AND bstatus.visible = 1 {$modulewhere}
SQL;
        $behaviourstatusescols = $DB->get_records_sql($statesql, ['cid' => $this->courseid]);

        $tableprefix = self::get_prefix($this->prefix[0]);
        $colprefix = self::get_prefix($this->prefix[1]);
        $tablecolprefix = self::get_prefix($this->prefix[2]);

        if ($this->isweekend) {
            $tempcols = $behaviourcols;
            $behaviourcols = [];
            $midnights = [];
            for ($i = 0;$i < 7; $i++) {
                $midnights[] = $timestart+ ($i*DAYSECS);
            }
            foreach ($midnights as $midnight) {
                foreach ($tempcols as $tempkey => $tempvalue) {
                    if ($tempvalue->sessdate >= $midnight && $tempvalue->sessdate <= ($midnight + (DAYSECS - 1))) {
                        $behaviourcols[] = $tempvalue;
                        unset($tempcols[$tempkey]);
                    }
                }
            }
        }

        $sessfield = $customsessfield = $sessfrom = ' ';
        $indexcount = 1;

        foreach ($behaviourcols as $behaviourcol) {
            $sessionid = $behaviourcol->sessionid;
            $colid = $colprefix . $sessionid;

            $sessionmidnight = strtotime('midnight', $behaviourcol->sessdate);

            if ($this->lastmidnight != $sessionmidnight) {
                $indexcount = 1;
                $tabcolumnid = $tablecolprefix . $indexcount;
            } else {
                $tabcolumnid = $tablecolprefix . $indexcount;
            }

            $column[$colid] = $tabcolumnid;
            if (array_key_exists($sessionmidnight, $this->sessioncolumns)) {
                $this->sessioncolumns[$sessionmidnight][$colid] = $tabcolumnid;
            } else {
                $this->sessioncolumns[$sessionmidnight][$colid] = $tabcolumnid;
            }
            $this->lastmidnight = $sessionmidnight;

            $sessfield .= <<<SQL
, {$tableprefix}.{$colid}, {$tableprefix}.log{$sessionid}
SQL;

            $customsessfield .= <<<SQL
, MAX(if(bsession.id = {$sessionid}, bst.acronym, null)) as c{$sessionid},
MAX(if(bsession.id = {$sessionid}, bl.statusid, null)) as log{$sessionid}
SQL;

            $indexcount++;
        }

        if (!empty($behaviourcols)) {
            $sesscol = array_column($behaviourcols, 'sessionid');
            [$in, $inparam] = $DB->get_in_or_equal($sesscol, SQL_PARAMS_NAMED, 'sessid', true);
            $params = $inparam;
        }

        $sessfrom .= <<<SQL
LEFT JOIN (
    SELECT CONCAT(bl.studentid, "#", b.course) as uniqueid, bl.studentid as userid, b.course {$customsessfield}
    FROM {behaviour_log} bl
    JOIN {behaviour_sessions} bsession ON bsession.id = bl.sessionid
    JOIN {behaviour} b ON b.id = bsession.behaviourid
    JOIN {behaviour_statuses} bst ON bst.id = bl.statusid
    WHERE bsession.id {$in} {$modulewhere}
    GROUP BY uniqueid, bl.studentid, b.course
) as {$tableprefix} ON ({$tableprefix}.userid = u.id AND {$tableprefix}.course = c.id)
SQL;


        $field = <<<SQL
ue.id, u.id as userid,u.firstname, u.lastname, u.email {$sessfield}
SQL;

        $from = <<<SQL
{user_enrolments} ue
JOIN {enrol} e ON e.id = ue.enrolid
JOIN {user} u ON u.id = ue.userid
JOIN {course} c ON c.id = e.courseid
{$sessfrom}
SQL;

        $where = <<<SQL
u.id > 2  AND c.id = :courseid1
SQL;
        $params['courseid'] = $params['courseid1'] = $this->courseid;

        if (!empty($this->sessioncolumns)) {
            $statusprefix = self::get_prefix($this->prefix[3]);
            foreach ($behaviourstatusescols as $statusescol) {
                $column[$statusprefix.$statusescol->id] = $statusescol->acronym;
                $this->statuscolumns[$statusprefix.$statusescol->id] = $statusescol->description;
            }
        }
        $column[$this->lastcol] = get_string('totalsession',util::COMPONENT);

        $this->set_sql($field, $from, $where, $params);
        $this->define_columns(array_keys($column));
        $this->define_headers(array_values($column));
        $this->sortable(false);
        $this->collapsible(false);
    }

    public static function get_prefix($name) {
        $prefix = [
          'table' => 'tab',
          'col' => 'c',
          'tablecol' => 'P',
          'status' => 'status:'
        ];
        return $prefix[$name];
    }

    public function get_context(): context {
        return context_system::instance();
    }

    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/report/behaviour/index.php', ['id' => $this->courseid]);
    }

    public function print_headers() {
        global $CFG, $OUTPUT;

        echo html_writer::start_tag('thead');
        if (!empty($this->sessioncolumns)) {
            echo html_writer::start_tag('tr');
            if (!empty($this->sessioncolumns)) {
                echo html_writer::tag('th', get_string('students', util::COMPONENT), ['class' => "header firstheader", 'scope' => 'col', 'colspan' => count($this->defaultcolumns) ?? 2]);
                foreach ($this->sessioncolumns as $sessioncolkey => $sessioncolvalue) {
                        $date = userdate($sessioncolkey, $this->timeformat);
                        $content = html_writer::tag('div', $date);
                        $colspan = count($sessioncolvalue);
                        $otherclass = 'text-center';
                        echo html_writer::tag('th', $content, ['class' => "header {$otherclass}", 'scope' => 'col', 'colspan' => $colspan]);
                }

                foreach ($this->statuscolumns as $statuscolkey => $statuscolvalue) {
                    echo html_writer::tag('th', $statuscolvalue, ['class' => "header {$otherclass}", 'scope' => 'col', 'rowspan' => 2]);
                }
                echo html_writer::tag('th', $this->headers[array_key_last($this->headers)], ['class' => "header {$otherclass}", 'scope' => 'col', 'rowspan' => 2,'text-align'=>'center']);
            } else {
                echo html_writer::tag('th', $this->headers[array_key_last($this->headers)], ['class' => "header ", 'scope' => 'col']);
            }

            echo html_writer::end_tag('tr');
        }

        echo html_writer::start_tag('tr');
        foreach ($this->columns as $column => $index) {
            if (array_key_exists($column, $this->statuscolumns ?? [])) {
                break;
            }
            $content = html_writer::tag('div', $this->headers[$index]);
            echo html_writer::tag('th', $content, ['class' => "header c{$index}", 'scope' => 'col']);
        }

        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
    }

    public function other_cols($column, $row) {
        global $DB;
        $todaymidnight = strtotime('midnight', $this->lastmidnight);
        if ($column == $this->lastcol) {
            if ($this->isweekend) {
                $count = 0;
                foreach ($this->sessioncolumns as $colkey => $colvalue) {
                    $count += count($colvalue);
                }
                $row->$column = $count;
            } else {
                $row->$column = count($this->sessioncolumns[$todaymidnight] ?? []);
            }
        }

        if (strpos($column, self::get_prefix($this->prefix[3])) !== false) {
            $row->$column = '';
            $count = 0;
            if ($this->isweekend) {
                $midnights = array_keys($this->sessioncolumns);
                foreach ($row as $colname => $colvalue) {
                    foreach ($midnights as $midnightvalue) {
                        if (!empty($this->sessioncolumns) &&
                            !empty($midnightvalue) &&
                            !is_null($this->sessioncolumns[$midnightvalue]) &&
                            in_array($colname, array_keys($this->sessioncolumns[$midnightvalue]))) {
                            $logid = str_replace('c', '', $colname);
                            $statusidcount = in_array(str_replace('status:', '', $column),explode(',', $row->{'log'.$logid}));
                            if (!empty($statusidcount)) {
                                $count++;
                            }
                        }
                    }
                }
            } else {
                foreach ($row as $colname => $colvalue) {
                    if (!empty($this->sessioncolumns) &&
                        !empty($todaymidnight) &&
                        !is_null($this->sessioncolumns[$todaymidnight]) &&
                        in_array($colname, array_keys($this->sessioncolumns[$todaymidnight]))) {
                        $logid = str_replace('c', '', $colname);
                        $statusidcount = in_array(str_replace('status:', '', $column),explode(',',$row->{'log'.$logid}));
                        if (!empty($statusidcount)) {
                            $count++;
                        }
                    }
                }
            }

            $row->$column = $count;
        }

        if (strpos($column, self::get_prefix($this->prefix[1])) !== false) {
            $statusids = str_replace('c','',$column);
            $statusidarr = explode(',', $row->{'log'.$statusids});
            [$in, $status] = $DB->get_in_or_equal($statusidarr, SQL_PARAMS_NAMED, 'sessid', true);
            if (!empty($status)) {
                foreach ($status as $stsid) {
                    $status1 = $DB->get_record_sql("SELECT *  FROM {behaviour_statuses} WHERE id IN ('" . $stsid . "')");
                    $data[] = $status1->acronym;
                }
                $statusvalue = implode(',', $data);
                $row->$column = !empty($row->$column) ? $statusvalue : '-';
            }
        }

        return parent::other_cols($column, $row);
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

    public function get_coursemodules() {
        global $DB;

        $sql = <<<SQL
SELECT cm.id, cm.instance, b.name
FROM {behaviour} b
JOIN {course_modules} cm ON cm.instance = b.id
JOIN {modules} m ON m.id = cm.module
WHERE m.name LIKE "behaviour" AND cm.course = :courseid AND cm.visible = 1
SQL;
        $param['courseid'] = $this->courseid;
        $modules = $DB->get_records_sql($sql, $param);
        return $modules;
    }

}