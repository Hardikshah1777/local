<?php

use core\output\notification;

require_once('../../config.php');
require_once('classes/form.php');

$myurl = new moodle_url('/local/registration/manage.php');
$context = context_system::instance();
$pageurl = new moodle_url('/local/registration/add.php');

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('addcoupon', 'local_registration'));
require_login();

$myform = new registration_form();
if ($myform->is_cancelled()) {
    redirect($myurl);
} elseif ($data = $myform->get_data()) {
    $record = new stdClass;
    $record->couponcode = $data->couponcode;
    $record->visible = $data->couponenable;
    $record->courseid = $data->course;
    $record->duration = $data->duration;
    $DB->insert_record('local_registration', $record);
    redirect($myurl, get_string('added', 'local_registration'), null, notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
$myform->display();
echo $OUTPUT->footer();