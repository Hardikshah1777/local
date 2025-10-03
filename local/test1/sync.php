<?php

require_once '../../config.php';

$context = context_system::instance();
$url = new moodle_url('/local/test1/sync.php');
$userid = optional_param('userid','',PARAM_INT);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Test');
require_login();

$token = 'd44a345b7a2d0349f7d0d8a634eec1d3';
$domainname = 'http://localhost/iomad4';
$functionname = 'core_user_create_users';

$serverurl = $domainname.'/webservice/rest/server.php'.'?wstoken='.$token.'&wsfunction='.$functionname.'&moodlewsrestformat=json';

// User to create
$coreuser = core_user::get_user($userid);
$user = [
    'username'       => $coreuser->username,
    'password'       => 'Test@123',
    'firstname'      => $coreuser->firstname,
    'lastname'       => $coreuser->lastname,
    'email'          => $coreuser->email,
    'auth'           => $coreuser->auth,
    'idnumber'       => $coreuser->idnumber,
    'lang'           => $coreuser->lang,
    'theme'          => $coreuser->theme,
    'phone1'         => $coreuser->phone1,
    'phone2'         => $coreuser->phone2,
    'institution'    => $coreuser->institution,
    'department'     => $coreuser->department,
    'city'           => $coreuser->city,
    'country'        => $coreuser->country,
    'timezone'       => $coreuser->timezone,
    'mailformat'     => 1,
];

// Flattened POST data using custom function
function buildParams($params, $prefix = '') {
    $result = [];
    foreach ($params as $key => $value) {
        $name = $prefix === '' ? $key : $prefix . "[$key]";
        if (is_array($value)) {
            $result += buildParams($value, $name);
        } else {
            $result[$name] = $value;
        }
    }
    return $result;
}

$params = ['users' => [ $user ]];
$postFields = buildParams($params);

// cURL setup
$ch = curl_init($serverurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

$response = curl_exec($ch);
curl_close($ch);

echo $OUTPUT->header();

// Show result
echo "<br>------ Hello ------<br>";

$backurl = new moodle_url('/local/test1/index.php');
$fromuser = core_user::get_support_user();

if ($response && !preg_match('/Username already exists:\s*(\w+)/', json_decode($response)->debuginfo, $matches)) {
    $coreuser->type = 'Signup with moodle4 account';
    $loginurl = 'http://localhost/iomad4/login/index.php';
    $body = '<p>Signup with moodle4 account</p>
            <p>You can login with same credentials</p>
            <p>Password : Test@123</p>
            <p><a href='.$loginurl.'>Click here to : login</a></p>';

    email_to_user($coreuser, $fromuser,'Signup with moodle4 account', $body, $body);
    //print_object(json_decode($response));
    $msg = \core\notification::success('User created in iomad4');
    redirect($backurl, $msg);
} else {
    \core\notification::warning('something went wrong');
    echo html_writer::link($backurl, 'Back' , ['class' =>'btn btn-primary float-right']);
    $msg = '';
    foreach (json_decode($response) as $responsemsg) {
        $msg .= "<p>".$responsemsg."</p>";
    }
    email_to_user($USER, $fromuser,'Failed to Signup ', $msg, $msg);
    print_object(json_decode($response)->debuginfo);
}

//$userId = 3289;
//$secret = "d44a345b7a2d0349f7d0d8a634eec1d3";
//$data = $userId . "|" . time();
//$hash = hash_hmac('sha256', $data, $secret);
//$token = base64_encode($data . "|" . $hash);
//header("Location: http://localhost/iomad4/test.php?token=" . urlencode($token));
//exit;
echo $OUTPUT->footer();
