<?php

use local_elite\users;

require_once(dirname(__FILE__) . '/../../config.php');
require_once "$CFG->libdir/tablelib.php";

$context = context_system::instance();
$url = new moodle_url('/local/elite/index.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title','local_elite'));
$PAGE->set_heading(get_string('heading','local_elite'));
require_login();

$utable = new users('uid');

echo $OUTPUT->header();
echo $utable->showdata();
echo $OUTPUT->footer();