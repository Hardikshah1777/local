<?php

use local_generalnotes_comment as comment;

define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');

$contextid = optional_param('contextid', SYSCONTEXTID, PARAM_INT);
$action    = optional_param('action', '', PARAM_ALPHA);

if (empty($CFG->usecomments)) {
    throw new comment_exception('commentsnotenabled', 'moodle');
}

list($context, $course, $cm) = get_context_info_array($contextid);

if ( $contextid == SYSCONTEXTID ) {
    $course = $SITE;
}

$PAGE->set_url('/local/generalnotes/ajax.php');

// Allow anonymous user to view comments providing forcelogin now enabled
require_course_login($course, true, $cm);
$PAGE->set_context($context);
if (!empty($cm)) {
    $PAGE->set_cm($cm, $course);
} else if (!empty($course)) {
    $PAGE->set_course($course);
}

if (!confirm_sesskey()) {
    $error = array('error'=>get_string('invalidsesskey', 'error'));
    die(json_encode($error));
}

$client_id = required_param('client_id', PARAM_ALPHANUM);
$commentid = optional_param('commentid', -1, PARAM_INT);
$content   = optional_param('content',   '', PARAM_RAW);
$itemid    = optional_param('itemid',    '', PARAM_INT);
$page      = optional_param('page',      0,  PARAM_INT);

// initilising comment object
$args = new stdClass;
$args->context   = $context;
$args->client_id = $client_id;
$manager = new comment($args);

echo $OUTPUT->header(); // send headers

// process ajax request
switch ($action) {
    case 'add':
        if ($manager->can_post()) {
            $result = $manager->add($content);
            if (!empty($result) && is_object($result)) {
                $result->count = $manager->count();
                $result->client_id = $client_id;
                echo json_encode($result);
                die();
            }
        }
        break;
    case 'delete':
        $comment = $DB->get_record('comments', ['id' => $commentid]);
        if ($manager->can_delete($comment)) {
            if ($manager->delete($commentid)) {
                $result = array(
                    'client_id' => $client_id,
                    'commentid' => $commentid
                );
                echo json_encode($result);
                die();
            }
        }
        break;
    case 'get':
    default:
        if ($manager->can_view()) {
            $comments = $manager->get_comments($page);
            $result = array(
                'list'       => $comments,
                'count'      => $manager->count(),
                'pagination' => $manager->get_pagination($page),
                'client_id'  => $client_id
            );
            echo json_encode($result);
            die();
        }
        break;
}

if (!isloggedin()) {
    // tell user to log in to view comments
    echo json_encode(array('error'=>'require_login'));
}
// ignore request
die;
