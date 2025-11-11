<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/message/lib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:sendmessage', $context);

$username = required_param('username', PARAM_USERNAME);
$message  = required_param('message', PARAM_TEXT);
$message = urldecode($message);
$url = new moodle_url('/local/notify/notify.php', ['username' => $username, '' => $message]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Send Notification');
$PAGE->set_heading('Send Notification');
$PAGE->requires->js_call_amd('core/notification_desktop', 'requestPermission');

try {
    $recipient = core_user::get_user_by_username($username);
} catch (dml_missing_record_exception $e) {
    echo $OUTPUT->header();
    \core\notification::error('Recipient not found!');
    echo $OUTPUT->footer();
    exit;
}

$eventdata = new \core\message\message();
$eventdata->component         = 'local_notify';
$eventdata->name              = 'custom_notification';
$eventdata->userfrom          = $USER;
$eventdata->userto            = $recipient;
$eventdata->subject           = 'Custom Notification';
$eventdata->fullmessage       = $message;
$eventdata->fullmessageformat = FORMAT_PLAIN;
$eventdata->fullmessagehtml   = $message;
$eventdata->smallmessage      = shorten_text($message, 50);
$eventdata->notification      = 1;
$eventdata->contexturl        = new moodle_url('/local/notify/index.php');
$eventdata->contexturlname    = 'Open Moodle';
$messageid = message_send($eventdata);

echo $OUTPUT->header();
$msg = !empty($messageid) ? 'Notification sent!' : 'Failed to send notification.';
redirect(new moodle_url('/local/notify/index.php'), $msg);
echo $OUTPUT->footer();
