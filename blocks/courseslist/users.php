<?php

use block_courseslist\table\allcourse;
use block_courseslist\table\courseusers;
use block_courseslist\table\courseusers_filterset;

require_once(__DIR__.'/../../config.php');

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_INT);

if (empty($id)) {
    throw new moodle_exception('invalidid', 'block_courseslist');
}

if (empty($action) || !in_array($action, allcourse::ACTION)) {
    throw new moodle_exception('invalidaction', 'block_courseslist');
}

$coursecontext = context_course::instance($id);
require_login();
require_capability('block/courseslist:view', $coursecontext);

$coursename = $DB->get_field('course', 'fullname', ['id' => $id]);

$actionname = array_flip(allcourse::ACTION)[$action];
$context = context_system::instance();
$pageurl = new moodle_url('/blocks/courseslist/users.php');
$title = get_string("{$actionname}:users",'block_courseslist', ['coursename' => $coursename]);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_heading($title);
$PAGE->set_title($title);

$filterset = new courseusers_filterset();
$filterset->add_filter_from_params('id', $filterset::JOINTYPE_DEFAULT, [$id]);
$filterset->add_filter_from_params('action', $filterset::JOINTYPE_DEFAULT, [$action]);

$table = new courseusers('cusers');
$table->define_baseurl($pageurl);
$table->set_filterset($filterset);

echo $OUTPUT->header();

echo $table->render($table::perpage, false);

echo $OUTPUT->footer();
