<?php

require_once('config.php');
$username = required_param('smile', PARAM_TEXT);
$password = required_param('innova', PARAM_TEXT);
$email = required_param('camera', PARAM_TEXT);
$course = required_param('course', PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/authenticateuser.php');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('authenticateuser', 'moodle'));
$PAGE->set_heading(get_string('authenticateuser', 'moodle'));

$user = authenticate_user_login(base64_decode($username), base64_decode(base64_decode($password)));

if (!empty($user)) {
    complete_user_login($user);
    redirect(new moodle_url('/course/view.php', ['id' => $course]));
}else{
    redirect(new moodle_url('/login/index.php'));
}

echo $OUTPUT->header();
echo $OUTPUT->footer();
