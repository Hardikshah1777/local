<?php

use core_table\local\filter\filter;
use report_kln\form\userfilter;
use report_kln\table\userslist;
use report_kln\table\userslist_filterset;
use report_kln\util;

require_once(__DIR__.'/../../config.php');

$starttime = optional_param('starttime', null, PARAM_TEXT);
$endtime = optional_param('endtime', null, PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$syscontext = context_system::instance();
$pageurl = new moodle_url('/report/kln/index.php');

$PAGE->set_context($syscontext);
$PAGE->set_url($pageurl);
$PAGE->set_heading(get_string('indexheader', util::COMPONENT));
$PAGE->set_title(get_string('indexheader', util::COMPONENT));

require_admin();

$filterform = new userfilter(null, null, 'post', '', null, true, null, true);

$table = new userslist(uniqid('userlist-'));
$filterset = (new userslist_filterset())
    ->add_filter_from_params('starttime', filter::JOINTYPE_DEFAULT, (array) $starttime)
    ->add_filter_from_params('endtime', filter::JOINTYPE_DEFAULT, (array) $endtime)
    ->add_filter_from_params('userid', filter::JOINTYPE_DEFAULT, (array) $userid)
    ->add_filter_from_params('courseid', filter::JOINTYPE_DEFAULT, (array) $courseid);
$table->set_filterset($filterset);
$PAGE->requires->js_call_amd('report_kln/index', 'registerDynamicform');

echo $OUTPUT->header();

echo html_writer::start_div('', ['data-region' => 'dynamicform', 'data-form-class' => get_class($filterform), 'data-tableuniqueid' => $table->uniqueid]);
$filterform->display();
echo html_writer::end_div();

$table->out(30, false);

echo $OUTPUT->footer();
