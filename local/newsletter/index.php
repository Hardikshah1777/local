<?php

use local_newsletter\newsletter_table;

require_once ('../../config.php');
$id = optional_param('id', 0, PARAM_INT);
$url = new moodle_url('/local/newsletter/index.php');
$context = context_system::instance();
$addlink = new moodle_url('/local/newsletter/addnewsletter.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title', 'local_newsletter'));
$PAGE->set_heading(get_string('heading', 'local_newsletter'));
require_login();

if (!is_siteadmin()) {
    redirect(new moodle_url('/my'));
}
if (!empty($id)) {
    $DB->delete_records('local_newsletter', ['id' => $id]);
    redirect($url, get_string('deletesuccessfully', 'local_newsletter'));
}

$cols = [
        'name'  => get_string('name', 'local_newsletter'),
        'subject'  => get_string('subject', 'local_newsletter'),
        'feedbackname'  => get_string('nameofactivitys', 'local_newsletter'),
        'scheduledate'  => get_string('scheduledate', 'local_newsletter'),
        'remindermail'  => get_string('remindermail', 'local_newsletter'),
        'action' => get_string('action')
];

$newslettertable = new newsletter_table('newsletterid');
$newslettertable->define_baseurl($url);
$newslettertable->set_sql('ln.*, f.name as feedbackname',
        '{local_newsletter} ln 
        JOIN {course_modules} cm ON cm.id = ln.activityid
        JOIN {feedback} f ON f.id = cm.instance',
        'ln.id > 0');
$newslettertable->define_columns(array_keys($cols));
$newslettertable->define_headers(array_values($cols));
$newslettertable->sortable(false);
$newslettertable->collapsible(false);

echo $OUTPUT->header();
echo html_writer::link($addlink, get_string('addnewsletter', 'local_newsletter'), ['class' => 'btn btn-primary float-right mb-3']);
echo html_writer::start_div('w-100 ', ['style' => 'display: grid;']);
$newslettertable->out('20',false);
echo html_writer::end_div();
echo $OUTPUT->footer();