<?php

require_once '../../config.php';
require_once($CFG->libdir . '/tablelib.php');

use local_userlist\form\search_user;
use local_userlist\table\userlist;

$download = optional_param('download', '', PARAM_ALPHA);
$userid   = optional_param('userid', '', PARAM_INT);
$userid1   = optional_param('userid1', '', PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/local/userlist/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('title','local_userlist'));
$PAGE->set_heading(get_string('heading','local_userlist'));
require_login();

if ($userid || $userid1) {
    $fromuser = core_user::get_support_user();
    $touser = core_user::get_user($userid ? $userid : $userid1);
    $fullname = fullname($touser);
    if (email_to_user($touser, $fromuser, 'Check mail', fullname( $touser ) . ' check mail', 'check mail',
        '', 'test', $touser->email, 'abc@demo.com', 'ABC', '', true, true)) {
        \core\notification::success('Sent mail to ' . $fullname);
    }
    if ($userid1) {
        redirect(new moodle_url('/admin/user.php'));
    }else{
        redirect($PAGE->url);
    }
}
$table = new userlist('userlist');
$form = new search_user($PAGE->url,null,'post','', ['class' => 'd-flex']);
$table->downloadable = false;
$table->showdownloadbuttonsat = [TABLE_P_BOTTOM];

if ($table->is_downloading($download,'users','userlist')) {
    $table->init(30, false);
}

echo $OUTPUT->header();
$form->display();
$PAGE->requires->js_call_amd('local_userlist/mail', 'handletoast');
$table->init(30, false);
echo html_writer::tag('h5','Users = '.$table->totalrows, []);
echo $OUTPUT->footer();