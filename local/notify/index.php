<?php

use local_notify\form\send_form;

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:sendmessage', $context);

$url = new moodle_url('/local/notify/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Send Notification');
$PAGE->set_heading('Send Notification');
$users = $DB->get_records_sql('SELECT * FROM {user} WHERE deleted = 0 AND suspended  = 0 AND  id > 2');

$mform = new send_form($url, $users);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/notify/index.php'));
} else if ($data = $mform->get_data()) {
    redirect(new moodle_url('/local/notify/notify.php', [
        'username' => $data->username,
        'message' => urlencode($data->message)
    ]));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
