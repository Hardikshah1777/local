<?php

namespace report_temco_completion\table;

use table_dataformat_export_format;
use table_sql;

require_once($CFG->libdir . '/tablelib.php');

class temcoscorm_complete extends table_sql {

    public $validtime;

    protected $timeformat = '%d-%b-%y';

    public function __construct($uniqueid, $customdata = null) {
        global $DB;

        parent::__construct($uniqueid);

        $col = [
            'userfullname' => get_string('idnumber', 'report_temco_completion'),
            'cohortname' => get_string('cohortname', 'report_temco_completion'),
            'cohortmanager' => get_string('cohortmanager', 'report_temco_completion'),
            //'coursename' => get_string('coursename', 'report_temco_completion'),
            'activityid' => get_string('cshortname', 'report_temco_completion'),
            'coursetimecompleted' => get_string('scormtimecompleted', 'report_temco_completion'),
            'result' => get_string('result', 'report_temco_completion'),
            'retakeduration' => get_string('duration', 'report_temco_completion'),
        ];

        $scormid = $DB->get_field('modules', 'id', ['name' => 'scorm']);
        $fileid = $DB->get_field('modules', 'id', ['name' => 'resource']);
        $params = [];
        $params['scormid'] = $scormid;
        $params['fileid'] = $fileid;
        $field = <<<SQL
CONCAT(u.id, '#', COALESCE(s.id,r.id)) as uniqueid, u.id as userid,u.firstname, u.lastname, u.username, s.idnumber as activityid, cmc.timemodified as coursetimecompleted, ch.id as cohortid, 
ch.name as cohortname, cmc.completionstate, s.retakeduration, c.shortname as coursename, r.activityid as factivityid, r.retakeduration as fretakeduration
SQL;
        $from = <<<SQL
{course_modules_completion} cmc
JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
JOIN {user} u ON u.id = cmc.userid
LEFT JOIN {scorm} s ON s.id = cm.instance
LEFT JOIN {resource} r ON r.id = cm.instance
JOIN {course} c ON c.id = cm.course
LEFT JOIN {cohort_members} chm ON chm.userid = u.id
LEFT JOIN {cohort} ch ON ch.id = chm.cohortid
SQL;
        $where = <<<SQL
u.id > 3 AND u.deleted = 0 AND u.suspended = 0 AND cm.module IN (:scormid, :fileid) AND  cmc.completionstate = 1 AND (cmc.timemodified > 0 OR cmc.timemodified IS NOT NULL AND cmc.timemodified != 0)
SQL;
        if (!empty($customdata)) {
            $where .= <<<SQL
 AND cmc.timemodified > :timestart AND cmc.timemodified < :timeend
SQL;
            $params['timestart'] = $customdata['timestart'];
            $params['timeend'] = $customdata['timeend'];
        }

        $where .= 'ORDER BY cmc.timemodified DESC';
        $this->set_sql($field, $from, $where, $params);
        $this->define_headers(array_values($col));
        $this->define_columns(array_keys($col));
        $this->sortable(false);
        $this->collapsible(false);

    }

    public function other_cols($column, $row) {
        global $DB;
        if ($column == 'userfullname') {
            $row->$column = $row->username;
        } else if ($column == 'coursetimecompleted') {
            $this->validtime = $row->$column;
            $time = (!empty($row->$column) ? userdate($row->$column, $this->timeformat) : '');
            $row->$column = $time;
        } else if ($column == 'activityid' || $column == 'factivityid') {
            $activityid = !empty($row->activityid) ? $row->activityid : '';
            $factivityid = !empty($row->factivityid) ? $row->factivityid : '';
            $row->$column = $activityid.$factivityid;
        } else if ($column == 'result') {
            $row->$column = (!empty($row->coursetimecompleted) ? get_string('pass', 'report_temco_completion') : '-');
        } else if ($column == 'retakeduration' || $column == 'fretakeduration') {
            $recduration = (!empty($row->coursetimecompleted) && !empty($row->retakeduration)) ? userdate($this->validtime + $row->retakeduration, $this->timeformat) : '';
            $frecduration = (!empty($row->coursetimecompleted) && !empty($row->fretakeduration)) ? userdate($this->validtime + $row->fretakeduration, $this->timeformat) : '';
            $row->$column = $recduration.$frecduration;
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
                $row->$column = !empty($users) ? implode(',', $users) : '-';
            }else{
                $row->$column = '-';
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