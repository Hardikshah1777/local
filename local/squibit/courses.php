<?php

use local_squibit\output\course_filterform;
use local_squibit\table\courses;
use local_squibit\table\courses_filterset;
use local_squibit\utility;
use core_table\local\filter\filter;

require_once('../../config.php');

$context = context_system::instance();
$url = new moodle_url('/local/squibit/courses.php');
$perpage = optional_param('perpage', 30, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$fullname = optional_param('fullname', null, PARAM_TEXT);
$status = optional_param('status', null, PARAM_INT);
$courseteacher = optional_param('courseteacher', null, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url($url);

require_login();
require_capability(utility::CAPS['manage'], $context);

if (!utility::is_enabled()) {
    throw new moodle_exception('syncdisabled', 'local_squibit',
        new moodle_url('/admin/settings.php', ['section' => 'local_squibit']));
}

$PAGE->set_title(get_string('courselisttitle', 'local_squibit'));

$filterset = (new courses_filterset)
        ->add_filter_from_params('courseid', filter::JOINTYPE_DEFAULT, (array) $courseid)
        ->add_filter_from_params('fullname', filter::JOINTYPE_DEFAULT,(array) $fullname)
        ->add_filter_from_params('status', filter::JOINTYPE_DEFAULT,(array) $status)
        ->add_filter_from_params('courseteacher', filter::JOINTYPE_DEFAULT,(array) $courseteacher);
$table = new courses('courses');
$table->set_filterset($filterset);

$PAGE->requires->js_call_amd('local_squibit/table', 'tableRegister', [$table->uniqueid]);

echo $OUTPUT->header();

echo html_writer::div(html_writer::tag('a',
    get_string('search'),
    [
            'href' => '#coursefilter',
            'class' => 'btn btn-primary mb-2 mr-2',
            'data-action' => 'toggle',
            'data-toggle' => 'collapse',
    ]). html_writer::tag('a',
    get_string('syncallcourses', 'local_squibit'),
    [
        'data-action' => 'syncallcourse', 'data-type' => 'courses',
        'class' => 'btn btn-secondary actionbutton mb-2',
    ]).html_writer::tag('a',
    get_string('back'),
    [
        'href' => $CFG->wwwroot."/admin/settings.php?section=local_squibit",
        'class' => 'btn btn-primary mb-2 ml-2',
    ]), 'text-right');

echo $OUTPUT->render(new course_filterform($table));

echo $table->out($perpage, true);

echo $OUTPUT->footer();
