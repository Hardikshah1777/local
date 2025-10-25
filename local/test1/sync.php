<?php
require_once('../../config.php');
require_login();

$context = context_system::instance();
$url = new moodle_url('/local/test1/sync.php');
$userid = optional_param('userid','',PARAM_INT);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('Test 1');

$token = 'd44a345b7a2d0349f7d0d8a634eec1d3';
$domainname = 'http://localhost/iomad4';
$functionname = 'core_user_create_users';

$serverurl = $domainname.'/webservice/rest/server.php'.'?wstoken='.$token.'&wsfunction='.$functionname.'&moodlewsrestformat=json';

$coreuser = core_user::get_user($userid);
// Build user array to send
$newuser = [
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

// Recursive function to flatten array for POST
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

$params = ['users' => [ $newuser ]];
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
    $body = '
        <p>You have been signed up on Iomad4.</p>
        <p>Use the same credentials to login:</p>
        <p><strong>Password:</strong> Test@123</p>
        <p><a href="' . $loginurl . '">Click here to login</a></p>';
    // Send email to the new user
    email_to_user($coreuser, $fromuser,'Account created on Iomad4', $body, $body);
    $msg = \core\notification::success('User created and email sent');
    redirect($backurl, 'User successfully synced to Iomad4');
} else {
    \core\notification::warning('something went wrong');
    echo html_writer::link($backurl, 'Back' , ['class' =>'btn btn-primary float-right']);
    $msg = '';
    foreach (json_decode($response) as $responsemsg) {
        $msg .= "<p>".$responsemsg."</p>";
    }
    email_to_user($USER, $fromuser,'Failed to Signup', $msg, $msg);
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
