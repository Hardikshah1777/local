<?php
use core_user;
use core\output\notification;
use stdclass;
require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once $CFG->dirroot . '/lib/enrollib.php';
require_once($CFG->dirroot.'/group/lib.php');
require_once( 'classes/signupform.php');
require_once($CFG->libdir.'/moodlelib.php');
$myurl = new moodle_url('/login/index.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/registration/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('adduser','local_registration'));

if (isloggedin() and !isguestuser()) {
    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url('/login/logout.php',
        array('sesskey' => sesskey(), 'loginpage' => 1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('cannotsignup', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}
$myform = new users_form();
if ($myform->is_cancelled()) {
    redirect($myurl);
} elseif ($data = $myform->get_data()) {
    global $DB;

    $user = new stdClass();
    $user->username = $data->email;
    $user->firstname = $data->firstname;
    $user->lastname = $data->lastname;
    $user->email = $data->email;
    $user->phone1 = $data->phone1;
    $user->couponcode = $data->couponcode;
    $user->auth = 'email';
    $user->mnethostid = 1;
    $user->confirmed = 1;
    $users = user_create_user($user);

    $from = core_user::get_support_user();
    $subject = get_string('subject', 'local_registration');
    $users = core_user::get_user_by_email($user->email);

    $coupon = $DB->get_record('local_registration', ['couponcode' => $user->couponcode], '*');

    $enrol = enrol_get_plugin('manual');
    $instance = $DB->get_record('enrol', ['courseid' => $coupon->courseid, 'enrol' => 'manual'], '*');
    $enrol->enrol_user($instance, $users->id, 5, time(), $timeend = time() + $coupon->duration);

    if (!empty($coupon)) {
        $insert = new stdClass();
        $insert->couponid =  $coupon->id;
        $insert->userid = $users->id;
        $insert->timeused = time();
        $DB->insert_record('local_registration_users', $insert);
    }
    if(!empty($coupon->groupid && $DB->record_exists('groups',['id'=>$coupon->groupid] ))){
        groups_add_member($coupon->groupid,$users);
    }
    $user->password = generate_password(10);
    email_to_user($users, $from, $subject, get_string('message', 'local_registration', [
        'siteurl' => $CFG->wwwroot,
        'firstname' => $user->firstname,
        'username' => $user->username,
        'password' => $user->password]));
    $users->password = MD5($user->password);

    $DB->update_record('user', $users);
    set_user_preference('auth_forcepasswordchange', 1, $users->id);
    redirect($myurl, get_string('usercreated', 'local_registration'));
}

echo $OUTPUT->header();
$myform->display();
echo $OUTPUT->footer();