<?php

use local_elite\enrolments;

require_once(dirname(__FILE__) . '/../../config.php');
require_once "$CFG->libdir/tablelib.php";
$download = optional_param('download', '', PARAM_ALPHA);

$context = context_system::instance();
$url = new moodle_url('/local/elite/enrolments.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title','local_elite'));
$PAGE->set_heading(get_string('enrolment','local_elite'));
require_login();

$etable = new enrolments('eid');
$etable->download = $download;

echo $OUTPUT->header();
echo $etable->showdata();
echo $OUTPUT->footer();