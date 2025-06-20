<?php

namespace report_temco_completion\table;

use table_dataformat_export_format;
use table_sql;

require_once($CFG->libdir . '/tablelib.php');

class temco_complete extends table_sql {

    public $validtime;

    protected $timeformat = '%d-%b-%y';

    public function __construct($uniqueid, $customdata = null) {
        parent::__construct($uniqueid);

        $col = [
            'idnumber' => get_string('idnumber', 'report_temco_completion'),
            'cohortname' => get_string('cohortname', 'report_temco_completion'),
            'cohortmanager' => get_string('cohortmanager', 'report_temco_completion'),
            'cshortname' => get_string('cshortname', 'report_temco_completion'),
            'coursetimecompleted' => get_string('coursetimecompleted', 'report_temco_completion'),
            'result' => get_string('result', 'report_temco_completion'),
            'recduration' => get_string('duration', 'report_temco_completion'),
        ];

        $field = <<<SQL
CONCAT(u.id, '#', cc.course) as uniqueid, u.id as userid, u.idnumber, c.shortname as cshortname, cc.timecompleted as coursetimecompleted, cohort.id as cohortid, cohort.name as cohortname, c.recduration 
SQL;
        $from = <<<SQL
{user} u
JOIN {course_completions} cc on cc.userid = u.id
JOIN {course} c ON c.id = cc.course
LEFT JOIN {cohort_members} cm ON cm.userid = u.id
LEFT JOIN {cohort} cohort ON cohort.id = cm.cohortid
SQL;

        $where = <<<SQL
u.id > 3 AND u.deleted = 0 AND u.suspended = 0 AND (cc.timecompleted > 0 OR cc.timecompleted IS NOT NULL AND cc.timecompleted != 0)
SQL;
        $param = [];
        if (!empty($customdata)) {
            $where .= <<<SQL
AND cc.timecompleted > :timestart AND cc.timecompleted < :timeend
SQL;
            $param = array_merge($param, $customdata);
        }
        $where .= ' ORDER BY timecompleted ASC';
        $this->set_sql($field, $from, $where, $param);
        $this->define_headers(array_values($col));
        $this->define_columns(array_keys($col));
        $this->sortable(false);
        $this->collapsible(false);

    }

    public function other_cols($column, $row) {
        global $DB;
        if ($column == 'idnumber') {
            $row->$column = $row->idnumber ?? '-';
        } else if ($column == 'coursetimecompleted') {
            $this->validtime = $row->$column;
            $time = (!empty($row->$column) ? userdate($row->$column, $this->timeformat) : '');
            $row->$column = $time;
        } else if ($column == 'result') {
            $row->$column = (!empty($row->coursetimecompleted) ? get_string('pass', 'report_temco_completion') : '');
        } else if ($column == 'recduration') {
            $recduration = (!empty($row->coursetimecompleted) && !empty($row->recduration)) ? userdate($this->validtime + $row->recduration, $this->timeformat) : '';
            $row->$column = $recduration;
        } else if ($column == 'cohortmanager') {
            if (!empty($row->cohortid)) {
                $managers = $DB->get_records('cohort_members', ['cohortid' => $row->cohortid]);
                $users = [];
                foreach ($managers as $manager) {
                    $user = \core_user::get_user($manager->userid);
                    if (has_capability('report/temco_completion:view', \context_system::instance(), $user)) {
                        $users[] = fullname($user);
                    }
                }
                $row->$column = implode(',', $users);
            }
        }

        return parent::other_cols($column,$row);
    }

    public function render($pagesize, $useinitialsbar = true) {

        $this->out($pagesize, $useinitialsbar);
    }

    public function is_downloading($download = null, $filename='', $sheettitle='') {
        if ($download!==null) {
            $this->sheettitle = $sheettitle;
            $this->is_downloadable(true);
            $this->download = $download;
            $this->filename = clean_filename($filename);
            $this->export_class_instance();
        }
        return $this->download;
    }

    public function export_class_instance($exportclass = null) {
        if (!is_null($exportclass)) {
            $this->started_output = true;
            $this->exportclass = $exportclass;
            $this->exportclass->table = $this;
        } else if (is_null($this->exportclass) && !empty($this->download)) {
            $tempdir = make_temp_directory('temco_completion');
            $filepath = $tempdir . DIRECTORY_SEPARATOR . $this->filename;
            $this->exportclass = new table_dataformat_export_format($this, $this->download);
            if (!$this->exportclass->document_started()) {
                $this->exportclass->start_document($filepath, $this->sheettitle);
            }
        }
        return $this->exportclass;
    }

}