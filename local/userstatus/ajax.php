<?php

define('AJAX_SCRIPT', true);

use local_userstatus\status;

require_once(__DIR__ . '/../../config.php');

// Must have the sesskey
$id      = required_param('id', PARAM_INT); // course id
$action  = required_param('action', PARAM_ALPHANUMEXT);
$userids = required_param_array('userids',PARAM_INT);

$PAGE->set_url(new moodle_url('/local/userstatus/ajax.php', array('id'=>$id, 'action'=>$action)));

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

if ($course->id == SITEID) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);
require_capability('moodle/course:viewparticipants', $context);
require_sesskey();

echo $OUTPUT->header(); // send headers

$outcome = new stdClass();
$outcome->success = false;
$outcome->users = [];
$outcome->error = '';

function isStundent($context,$userid){
    return is_enrolled($context,$userid);
}

switch ($action) {
    case 'put':
        require_capability('moodle/course:bulkmessaging', $context);
        $statusid = required_param('status', PARAM_INT);
        $support = \core_user::get_support_user();
        $status = status::get_options()[$statusid];
        $mailsubject = \get_string('mailsubject',status::component,[
                'coursename' => $course->fullname,
                'status' => $status,
        ]);
        foreach ($userids as $userid){
            if(isStundent($context,$userid)){
                status::set_status($userid,$course->id,$statusid);
                $outcome->users[$userid] = status::get_statushtml_by_id($statusid);
                $langid = null;
                switch ($statusid){
                    case status::assessment1:
                        $langid = 'mailassessment1';
                        break;
                    case status::remediation1:
                        $langid = 'mailremediation1';
                        break;
                    case status::remediation2:
                        $langid = 'mailremediation2';
                        break;
                    case status::feedbackletter:
                        $langid = 'mailfeedbackletter';
                        break;
                    case status::internalmoderation:
                        $langid = 'mailinternalmoderation';
                        break;
                    case status::externalmoderation:
                        $langid = 'mailexternalmoderation';
                        break;
                    case status::externalmoderationfollowup:
                        $langid = 'mailexternalmoderationfollowup';
                        break;
                    case status::certification:
                        $langid = 'mailcertification';
                        break;
                    default:
                        break;
                }
                if(!$langid) continue;
                $user = \core_user::get_user($userid);
                $mailBody = \get_string($langid,status::component,[
                    'username' => $user->firstname,
                    'coursename' => $course->fullname,
                    'status' => $status,
                ]);
                \email_to_user(
                    $user,
                    $support,
                    $mailsubject,
                    html_to_text($mailBody),
                    $mailBody
                );
            }
        }
        $outcome->success = true;
        break;
    case 'get':
        foreach ($userids as $userid){
            if(isStundent($context,$userid)){
                $outcome->users[$userid] = status::get_statushtml($userid,$course->id);
            }
        }
        $outcome->success = true;
        break;
}

echo $OUTPUT->header();
echo json_encode($outcome);

die();
