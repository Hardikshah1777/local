<?php

require('../../config.php');
require "$CFG->libdir/tablelib.php";
require_once $CFG->libdir . '/formslib.php';

$cohortid = optional_param('cohortid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$context = CONTEXT_SYSTEM::instance();

$url = new moodle_url("/report/cohortcompletion/index.php", ['cohortid' => $cohortid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'report_cohortcompletion'));
$PAGE->set_heading(get_string('pluginname', 'report_cohortcompletion'));
require_login();

class report_cohortcompletion_table extends table_sql
{
    const cols = ['Fullname', 'Course Start Date'];

    public function start_html()
    {
        $oldvalue = $this->use_pages;
        $this->use_pages = false;
        parent::start_html();
        $this->use_pages = $oldvalue;
    }

    public function out($pagesize, $useinitialsbar = false, $downloadhelpbutton = '')
    {
        global $OUTPUT;
        $cols = array_combine(self::cols, self::cols);
        foreach ($this->cohortcourses() as $course) {
            $cols['course-' . $course->id] = $course->fullname;
        }

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
        $this->sortable(false, 'u.fullname', SORT_ASC);
        $this->column_nosort = array_diff(array_keys($cols), self::cols);
        $this->collapsible(false);

        $fields = 'u.id, u.username, u.firstname, u.lastname';
        $from = '{cohort_members} cm
                  JOIN {user} u ON u.id = cm.userid';
        $where = 'u.id > 2 AND cm.cohortid = :cohortid AND u.id IN (SELECT ue.userid FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid)';

        if (!empty($course->id)) {
            $this->set_sql($fields, $from, $where, ['cohortid' => $course->cohortid]);
        } else {
            echo get_string('nodata', 'report_cohortcompletion');
            echo $OUTPUT->footer();
        }
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    public function cohortcourses(): array
    {
        global $DB, $cohortid;
        $courses = $DB->get_records_sql("SELECT c.id, c.fullname,ch.id as cohortid FROM {cohort} ch
                        JOIN {enrol} e on e.customint1 = ch.id  AND e.enrol = 'cohort'
                        JOIN {course} c on c.id = e.courseid WHERE ch.visible = 1 AND ch.id='" . $cohortid . "'ORDER BY id ASC");
        return $courses;
    }

    public function other_cols($column, $row)
    {
        global $DB;

        if ($column == self::cols[1]) {

            $startdata = $DB->get_record_sql('SELECT min(timestarted) as timecreated FROM {course_completions} WHERE timestarted > 0 AND userid = ' . $row->id);

            if ($startdata->timecreated > 0) {
                $startdate = userdate($startdata->timecreated, get_string('strftimedate', 'core_langconfig'));
            } else {
                $startdate = '-';
            }
            return $startdate;
        }

        if (preg_match('/course-(\d+)/', $column, $matches)) {
            $courseid = $matches[1];
            $data = $DB->get_record('course_completions', ['userid' => $row->id, 'course' => $courseid]);
            if (!empty($data->timecompleted)) {
                $coursestatus = userdate($data->timecompleted, get_string('strftimedate', 'core_langconfig'));
            } elseif (!empty($data->timestarted)) {
                $coursestatus = get_string('inprogress', 'report_cohortcompletion');
            } else {
                $coursestatus = get_string('notstarted', 'report_cohortcompletion');
            }
            return $coursestatus;
        }
    }
}

$cohorts = $DB->get_records_sql('SELECT * FROM {cohort} WHERE visible = 1');
foreach ($cohorts as $key => $cohort) {
    $cohortlist[$key] = $cohort->name;
}

$selecturl = new moodle_url("/report/cohortcompletion/index.php");
$selectcohort = new single_select($selecturl, 'cohortid', $cohortlist, $cohortid);
$selectcohort->set_label(get_string('selectcohort', 'report_cohortcompletion'));

$table = new report_cohortcompletion_table(get_string('cohortcompletiontbl', 'report_cohortcompletion'));
$table->define_baseurl($url);

$selected = $DB->get_record('cohort', ['id' => $cohortid], 'id,name');

$table->is_downloading($download, $selected->name . ' Completion Report', 'Cohort List');
if ($table->is_downloading()) {
    $table->out(3, false);
    exit();
}

echo $OUTPUT->header();

if (require_capability('report/cohortcompletion:view', $context) || is_siteadmin()) {
    echo $OUTPUT->box($OUTPUT->render($selectcohort));
    $table->out(30, true);
}

echo $OUTPUT->footer();