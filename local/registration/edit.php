<?php

use core\output\notification;

require_once('../../config.php');
require_once('classes/form.php');

$id = optional_param('id', 0, PARAM_INT);
$url = new moodle_url('/local/registration/edit.php', ['id' => $id]);
$myurl = new moodle_url('/local/registration/manage.php');

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
if (empty($id)){
    $PAGE->set_title(get_string('addcoupon', 'local_registration'));
    $PAGE->set_heading(get_string('addcoupon', 'local_registration'));
}else{
    $PAGE->set_title(get_string('editcoupon', 'local_registration'));
    $PAGE->set_heading(get_string('editcoupon', 'local_registration'));
}

require_admin();

$myform = new registration_form($url,$id);

if (!empty($id)) {
    $records = $DB->get_record('local_registration', ['id' => $id], '*');
    $record = new stdClass;
    $records->couponenable = $records->visible;
    $records->course = $records->courseid;
    $records->group = $records->groupid;
    $records->duration = $records->duration;
    $records->couponuse = $records->type;
    $myform->set_data($records);
}
if ($myform->is_cancelled()) {
    redirect($myurl);
}
if ($data = $myform->get_data()) {
    $record = new stdClass;
    $record->couponcode = $data->couponcode;
    $record->visible = $data->couponenable;
    $record->courseid = $data->course;
    $record->duration = $data->duration;
    $record->type = $data->couponuse;
    $record->groupid = $data->group;
    if (!empty($id)) {
        $record->id = $id;
        $record->checkupdate = $id;
        $DB->update_record('local_registration', $record);
        redirect($myurl, get_string('update', 'local_registration'), null, notification::NOTIFY_SUCCESS);
    } else {
        $id=$DB->insert_record('local_registration', $record);
        redirect($myurl, get_string('added','local_registration'), null, notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
$myform->display();
echo $OUTPUT->footer();