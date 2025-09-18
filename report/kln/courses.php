<?php

use core_table\local\filter\filter;
use report_kln\form\coursefilter;
use report_kln\table\courselist;
use report_kln\table\courselist_filterset;
use report_kln\util;

require_once(__DIR__.'/../../config.php');

$starttime = optional_param('starttime', null, PARAM_TEXT);
$endtime = optional_param('endtime', null, PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$syscontext = context_system::instance();
$pageurl = new moodle_url('/report/kln/courses.php');

$PAGE->set_context($syscontext);
$PAGE->set_url($pageurl);
$PAGE->set_heading(get_string('courseheader', util::COMPONENT));
$PAGE->set_title(get_string('courseheader', util::COMPONENT));

require_admin();

$filterform = new coursefilter(null, null, 'post', '', null, true, null, true);

$table = new courselist(uniqid('courselist-'));
$filterset = (new courselist_filterset())
    ->add_filter_from_params('starttime', filter::JOINTYPE_DEFAULT, (array) $starttime)
    ->add_filter_from_params('endtime', filter::JOINTYPE_DEFAULT, (array) $endtime)
    ->add_filter_from_params('courseid', filter::JOINTYPE_DEFAULT, (array) $courseid);
$table->set_filterset($filterset);
$PAGE->requires->js_call_amd('report_kln/index', 'registerDynamicform');

echo $OUTPUT->header();

echo html_writer::start_div('', ['data-region' => 'dynamicform', 'data-form-class' => get_class($filterform), 'data-tableuniqueid' => $table->uniqueid]);
$filterform->display();
echo html_writer::end_div();

$table->out(30, false);

echo $OUTPUT->footer();