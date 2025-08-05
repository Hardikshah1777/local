<?php

require_once '../../config.php';

$userid   = optional_param('uid', '', PARAM_INT);
$search   = optional_param('search', '', PARAM_TEXT);
$url = new moodle_url( '/local/test1/testmail.php', ['search'=> $search]);
$context = context_system::instance();

$PAGE->set_title(get_string('title', 'local_test1'));
$PAGE->set_heading(get_string('heading', 'local_test1'));
$PAGE->set_url($url);
$PAGE->set_context($context);
require_admin();

header('Content-Type: application/json');
$raw = file_get_contents("php://input");
$data = json_decode($raw);
$userid = intval($data->userid);
$fromuser = core_user::get_support_user();
$touser = core_user::get_user($userid);
//$mail_subject = get_string('mailsubject','local_test1');
//$mail_body = get_string('mailbody','local_test1', $touser);

if (!isset($data->userid)) {
    echo json_encode(['success' => false, 'message' => 'No user ID provided']);
    exit;
}
if (!$touser) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$fullname = fullname($touser);
$emailresult = email_to_user($touser,$fromuser,"Test js mail","<p>hii {$fullname}</p> <p>Test js mail from <b>/local/test1/testmail.php.</b> </p>");
echo json_encode(['success' => $emailresult, 'username' => $fullname]);

//email_to_user($touser, $fromuser, 'mail_subject', 'mail_body','','','',
//            '','','','',true,true);
//redirect(new moodle_url('/local/test1/index.php', ['search'=> $search]), 'Mail sent to '. fullname($touser),0,\core\output\notification::NOTIFY_SUCCESS);

//echo $OUTPUT->header();
//    if (email_to_user($touser, $fromuser, mail_subject, $mail_body)) {
//    }
//echo $OUTPUT->footer();