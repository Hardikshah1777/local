<?php

use local_squibit\table\users;
use local_squibit\table\users_filterset;
use local_squibit\utility;
use core_table\local\filter\filter;
use local_squibit\output\user_filterform;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$context = context_system::instance();
$url = new moodle_url('/local/squibit/users.php');
$perpage = optional_param('perpage', 30, PARAM_INT);
$userid = optional_param('userid', null, PARAM_INT);
$firstname = optional_param('firstname', null, PARAM_TEXT);
$lastname = optional_param('lastname', null, PARAM_TEXT);
$status = optional_param('status', null, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url($url);

require_login();
require_capability(utility::CAPS['manage'], $context);

//if (!utility::is_enabled()) {
//    throw new moodle_exception('syncdisabled', 'local_squibit',
//        new moodle_url('/admin/settings.php', ['section' => 'local_squibit']));
//}

$PAGE->set_title(get_string('userlisttitle', 'local_squibit'));

$filterset = (new users_filterset)
            ->add_filter_from_params('userid', filter::JOINTYPE_DEFAULT, (array)$userid)
            ->add_filter_from_params('firstname', filter::JOINTYPE_DEFAULT,(array) $firstname)
            ->add_filter_from_params('lastname', filter::JOINTYPE_DEFAULT,(array) $lastname)
            ->add_filter_from_params('status', filter::JOINTYPE_DEFAULT,(array) $status);
$table = new users('users');
$table->set_filterset($filterset);

$PAGE->requires->js_call_amd('local_squibit/table', 'tableRegister', [$table->uniqueid]);

echo $OUTPUT->header();

echo html_writer::div(html_writer::tag('a',
    get_string('search'),
    [
            'href' => '#userfilter',
            'class' => 'btn btn-primary mb-2 mr-2',
            'data-action' => 'toggle',
            'data-toggle' => 'collapse',
    ]).html_writer::tag('a',
    get_string('syncallusers', 'local_squibit'),
    [
        'data-action' => 'syncalluser', 'data-type' => 'users',
        'class' => 'btn btn-secondary actionbutton mb-2',
    ]).html_writer::tag('a',
    get_string('back'),
    [
        'href' => $CFG->wwwroot."/admin/settings.php?section=local_squibit",
        'class' => 'btn btn-primary mb-2 ml-2',
    ]), 'text-right');

echo $OUTPUT->render(new user_filterform($table));

echo $table->out($perpage, true);

echo $OUTPUT->footer();
