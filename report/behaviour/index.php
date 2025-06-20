<?php

use core_table\local\filter\filter;
use report_behaviour\output\behaviour_sessions_filterform;
use report_behaviour\table\behaviour_sessions;
use report_behaviour\table\behaviour_sessions_filterset;
use report_behaviour\util;

require_once(__DIR__.'/../../config.php');
require_once ($CFG->libdir . '/tablelib.php');

$courseid = required_param('id',PARAM_INT);
$download = optional_param('download', false, PARAM_BOOL);
$cmid = optional_param('cmid', 0, PARAM_INT);
$timeselected = optional_param('timeselected', null, PARAM_TEXT);
$isweekend = optional_param('isweekend', 0, PARAM_INT);

$url = new moodle_url('/report/behaviour/index.php', ['id' => $courseid]);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_heading(get_string('indexheader', util::COMPONENT));
$PAGE->set_title(get_string('indextitle', util::COMPONENT));

require_login($course);
require_capability('report/behaviour:view', $context);

$table = new behaviour_sessions('behaviour_session');

$filterset = (new behaviour_sessions_filterset())
        ->add_filter_from_params('courseid', filter::JOINTYPE_DEFAULT, (array) $courseid)
        ->add_filter_from_params('cmid', filter::JOINTYPE_DEFAULT, (array) $cmid)
        ->add_filter_from_params('timeselected', filter::JOINTYPE_DEFAULT, (array) $timeselected)
        ->add_filter_from_params('isweekend', filter::JOINTYPE_DEFAULT, (array) $isweekend);

$table->set_filterset($filterset);
$filters = $table->get_filters();

if ($download) {
    ob_start();
    $table->out(util::PERPAGE, false);
    ob_clean();
    util::export_excel($table, 'Daily report');
}

$downloadurl = $PAGE->url;
$downloadurl->param('download', true);
$downloadurl->param('cmid', $filters['cmid']);
$downloadurl->param('timeselected', $filters['timeselected']);

echo $OUTPUT->header();

echo $OUTPUT->render(new behaviour_sessions_filterform($table));

echo html_writer::start_div('d-flex flex-column-reverse');

$table->out(util::PERPAGE, false);
if ($table->totalrows > 0) {
    echo html_writer::start_div('d-flex justify-content-end pb-2');
    echo html_writer::tag('a', get_string('export', util::COMPONENT), ['href' => $downloadurl, 'class' => 'btn btn-primary', 'id' => 'exportbtn']);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo $OUTPUT->footer();

