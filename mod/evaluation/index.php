<?php

require_once ('../../config.php');

$url = new moodle_url('/mod/evaluation/index.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname','mod_evaluation'));
$PAGE->set_heading(get_string('pluginname','mod_evaluation'));

echo $OUTPUT->header();
echo $OUTPUT->footer();