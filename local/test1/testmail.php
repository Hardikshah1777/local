<?php

require_once '../../config.php';
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/messagelib.php');
require_once($CFG->libdir.'/filelib.php');

$search   = optional_param('search', '', PARAM_TEXT);
$url = new moodle_url( '/local/test1/testmail.php', ['search'=> $search]);
$contextsys = context_system::instance();

$PAGE->set_title(get_string('title', 'local_test1'));
$PAGE->set_heading(get_string('heading', 'local_test1'));
$PAGE->set_url($url);
$PAGE->set_context($contextsys);
require_admin();

header('Content-Type: application/json');
$raw = file_get_contents("php://input");
$data = json_decode($raw);
$uid = intval($data->uid);
$fromuser = $USER;
$touser = core_user::get_user($uid);
$mail_subject = get_string('mailsubject','local_test1');
$mail_body = get_string('mailbody','local_test1', $touser);
$fullname = fullname($touser);
$maillogs = new stdClass();
$maillogs->sendtime = time();

if (!$data->logid) {
    if (!isset( $uid ) && !isset( $_FILES['pdf'] )) {
        echo json_encode( ['success' => false, 'message' => 'No user ID provided'] );
        exit;
    } else if (!$uid && !isset( $_FILES['pdf'] )) {
        echo json_encode( ['success' => false, 'message' => 'User not found'] );
        exit;
    } else {
        if (!isset( $_FILES['pdf'] )) {
            $touser->type = 'Simple mail';
            $emailresult = email_to_user( $touser,
                $fromuser,
                "Test js mail",
                "<p>hii {$fullname}</p> <p>Test js mail from <b>/local/test1/testmail.php.</b> </p>" );
            echo json_encode( ['success' => $emailresult, 'username' => $fullname] );
            exit;
        }
    }
}
if (!$data->logid) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_FILES['pdf'] ) && !$data->logid) {
        $userid = optional_param( 'userid', '', PARAM_INT );
        $file = $_FILES['pdf'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $tempPath = $file['tmp_name'];
            $filename = clean_param( $file['name'], PARAM_FILE );
            $mimetype = mime_content_type( $tempPath );

            // Move to a safe temporary location
            $tempdir = make_temp_directory( 'userpdfs' );
            $finalpath = $tempdir . '/' . $filename;
            if (!move_uploaded_file( $tempPath, $finalpath )) {
                echo json_encode( ['success' => false, 'error' => 'Failed to move uploaded file.'] );
                exit;
            }

            $touser = core_user::get_user( $userid );
            $fromuser = $USER;

            $subject = "User Information PDF";
            $body = "<p>Attached is the PDF containing your user information.</p>";

            $touser->type = 'Attachment Email';
            $emailresult = email_to_user( $touser, $fromuser, $subject, $body, $body, $finalpath, $filename, $mimetype );

            @unlink( $finalpath );

            echo json_encode( ['success' => $emailresult, 'username' => fullname( $touser )] );
        } else {
            echo json_encode( ['success' => false, 'error' => 'File upload error.'] );
        }
    } else {
        echo json_encode( ['success' => false, 'error' => 'Invalid request.'] );
    }

}

//if ($resendid) {
if ($data->logid) {
    if ($resenddata = $DB->get_record('local_test1_mail_log', ['id' => $data->logid])) {

        $touser = core_user::get_user($resenddata->userid);
        $from = $USER;

        $touser->type = $resenddata->type;
        $touser->resend = $resenddata->resend;
        $touser->updateid = $resenddata->id;
        $subject = $resenddata->subject;
        $body = $resenddata->body;
        $emailresult = email_to_user($touser, $from, $subject, $body);
        $response = ['success' => $emailresult, 'fullname' => fullname($touser)];
    } else {
        throw new exception('Invalid resendid');
    }
    echo json_encode($response);
}
//}
//echo $OUTPUT->header();
//echo $OUTPUT->footer();