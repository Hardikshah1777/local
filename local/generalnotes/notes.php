<?php

use local_generalnotes_comment as comment;

require_once('../../config.php');

require_login(null, false);

$userid = required_param('id',PARAM_INT);

// Check that the user is a valid user.
$user = core_user::get_user($userid);
if (!$user || !core_user::is_real_user($userid)) {
    throw new moodle_exception('invaliduser', 'error');
}

$url = new moodle_url('/local/generalnotes/notes.php',['id' => $userid,]);
$usercontext = context_user::instance($userid);
$title = get_string('tabname',comment::TABLE);

$args = new stdClass;
$args->context   = $usercontext;
$args->linktext  = get_string('showcomments');
$commentbox = new comment($args);
if(!$commentbox->can_add()){
    throw new moodle_exception('cannotaddnote', comment::TABLE);
}

$PAGE->set_url($url);
$PAGE->set_context($usercontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading(fullname($user));

$PAGE->navigation->extend_for_user($user);
$navbar = $PAGE->navbar->add($title, $url);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $commentbox->output(true);
echo $OUTPUT->footer();
