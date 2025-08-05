<?php

require_once '../../config.php';
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/messagelib.php');

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

//if (!isset($data->userid) && isset($_FILES['pdf'])) {
//    echo json_encode(['success' => false, 'message' => 'No user ID provided']);
//    exit;
//} else if (!$touser && isset($_FILES['pdf'])) {
//    echo json_encode(['success' => false, 'message' => 'User not found']);
//    exit;
//} else {
//    $emailresult = 1;//email_to_user($touser,$fromuser,"Test js mail","<p>hii {$fullname}</p> <p>Test js mail from <b>/local/test1/testmail.php.</b> </p>");
//    echo json_encode(['success' => $emailresult, 'username' => fullname($fullname)]);
//    exit;
//}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    $userid = required_param('userid', PARAM_INT);
    $file = $_FILES['pdf'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $tempPath = $file['tmp_name'];
        $filename = $file['name'];
        $filecontents = file_get_contents($tempPath);

        $touser = core_user::get_user($userid);
        $fromuser = core_user::get_support_user();

        $subject = "User Information PDF";
        $body = "Attached is the PDF containing the user data.";

        $emailresult = email_to_user($touser, $fromuser, $subject, $body, $body, $filecontents, $filename, mime_content_type($tempPath),'','','',true,true);
        echo json_encode(['success' => $emailresult, 'username' => fullname($touser)]);
    } else {
        echo "File upload error.";
    }
} else {
    echo "Invalid request.";
}

//echo $OUTPUT->header();
//echo $OUTPUT->footer();