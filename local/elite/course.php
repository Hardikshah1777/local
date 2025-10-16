<?php

use local_elite\courses;

require_once(dirname(__FILE__) . '/../../config.php');
require_once "$CFG->libdir/tablelib.php";
$download = optional_param('download', '', PARAM_ALPHA);

$context = context_system::instance();
$url = new moodle_url('/local/elite/course.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title','local_elite'));
$PAGE->set_heading(get_string('courses','local_elite'));
require_login();

$ctable = new courses('cid');
$ctable->is_downloadable(true);
if ($ctable->is_downloading($download, 'course', 'course')) {
    $ctable->showdata();
}

echo $OUTPUT->header();
echo $ctable->showdata();
echo $OUTPUT->footer();