<?php
define('NO_DEBUG_DISPLAY', true);

require("../../config.php");
require($CFG->libdir.'/filelib.php');
ini_set('error_log',__DIR__.'/ipn.log');

error_log(var_export($_REQUEST,true),3,__DIR__.'/request.log');

function purchase_log($data,$file = 'purchase.csv'){
    $fp = fopen($file,'a+');
    if(!$csv = fgetcsv($fp)){
        fputcsv($fp,array_keys($data));
        fputcsv($fp,array_values($data));
        fclose($fp);
    }else {
        $csv = array_merge(array_flip($csv), $data);
        fputcsv($fp, $csv);
        fclose($fp);
    }
}

purchase_log($_REQUEST);

function ex_handler($ex) {
    $info = get_exception_info($ex);

    $logerrmsg = "block_coupon IPN exception handler: ".$info->message;
    if (debugging('', DEBUG_NORMAL)) {
        $logerrmsg .= ' Debug: '.$info->debuginfo."\n".format_backtrace($info->backtrace, true);
    }
    error_log($logerrmsg);

    if (http_response_code() == 200) {
        http_response_code(500);
    }

    exit(0);
};

function message_paypal_error_to_admin($subject, $data) {
    $admin = get_admin();
    $admin->email = 'rajuptl21@gmail.com';
    $site = get_site();

    $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

    foreach ($data as $key => $value) {
        $message .= "$key => $value\n";
    }


    email_to_user($admin,core_user::get_support_user(),"PAYPAL ERROR: ".$subject,$message);
    if($data->blockid) $redirecturl = new \moodle_url('/blocks/coupon/view/generate_coupon.php',array('id' => $data->blockid));
    else $redirecturl = new \moodle_url('/my');
    redirect($redirecturl,$subject);
}

set_exception_handler('ex_handler');

if (empty($_POST) or !empty($_GET)) {
    http_response_code(400);
    throw new moodle_exception('invalidrequest', 'core_error');
}

/// Read all the data from PayPal and get it ready for later;
/// we expect only valid UTF-8 encoding, it is the responsibility
/// of user to set it up properly in PayPal business account,
/// it is documented in docs wiki.

$req = 'cmd=_notify-validate';

$data = new stdClass();
foreach ($_POST as $key => $value) {
    if ($key !== clean_param($key, PARAM_ALPHANUMEXT)) {
        throw new moodle_exception('invalidrequest', 'core_error', '', null, $key);
    }
    if (is_array($value)) {
        throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Unexpected array param: '.$key);
    }
    $req .= "&$key=".urlencode($value);
    $data->$key = fix_utf8($value);
}
if (empty($data->custom)) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Missing request param: custom');
}

$custom = explode('-', $data->custom);
unset($data->custom);

if (empty($custom) || count($custom) < 5) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid value of the request param: custom');
}

$blockid = array_shift($custom);
$userid = array_shift($custom);
$groupid = array_shift($custom);
$courseid = array_shift($custom);
$quantity = array_shift($custom);

$data->userid           = (int)$userid;
$data->groupid          = (int)$groupid;
$data->courseid         = (int)$courseid;
$data->quantity         = (int)$quantity;
$data->baseval          = \block_coupon\helper::get_price($data->groupid);
$data->timemodified     = time();
$data->payment_gross    = $data->mc_gross;
$data->payment_currency = $data->mc_currency;
$data->blockid          = $blockid;

$user = $DB->get_record("user", array("id" => $data->userid), "*", MUST_EXIST);
$course = $DB->get_record("course", array("id" => $data->courseid), "*", MUST_EXIST);
$group = $DB->get_record("groups", array("id" => $data->groupid), "*", MUST_EXIST);
$context = context_block::instance($blockid, MUST_EXIST);

$PAGE->set_context($context);

/// Open a connection back to PayPal to validate the data
$use_sandbox = get_config('block_coupon','coupon_usepaypalsandbox');
$paypaladdr = empty($use_sandbox) ? 'ipnpb.paypal.com' : 'ipnpb.sandbox.paypal.com';
$c = new curl();
$options = array(
    'returntransfer' => true,
    'httpheader' => array('application/x-www-form-urlencoded', "Host: $paypaladdr"),
    'timeout' => 30,
    'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
);
$location = "https://$paypaladdr/cgi-bin/webscr";

if(!empty($CFG->bypasspaypal)) {
    $result = "VERIFIED";
    $dummy = array (
            'mc_gross' => $_POST['amount'],
            'protection_eligibility' => 'Eligible',
            'address_status' => 'confirmed',
            'payer_id' => 'LPLWNMTBWMFAY',
            'tax' => '0.00',
            'address_street' => '1 Main St',
            'payment_date' => '20:12:59 Jan 13, 2009 PST',
            'payment_status' => 'Completed',
            'charset' => 'windows-1252',
            'address_zip' => '95131',
            'first_name' => $_POST['first_name'],
            'mc_fee' => '0.88',
            'address_country_code' => 'US',
            'address_name' => 'Test User',
            'notify_version' => '2.6',
            'custom' => '',
            'payer_status' => 'verified',
            'address_country' => 'United States',
            'address_city' => 'San Jose',
            'verify_sign' => 'AtkOfCXbDm2hu0ZELryHFjY-Vb7PAUvS6nMXgysbElEn9v-1XcmSoGtf',
            'payer_email' => $USER->email,
            'txn_id' => '61E67681CH3238416',
            'payment_type' => 'instant',
            'last_name' => $_POST['last_name'],
            'address_state' => 'CA',
            'receiver_email' => get_config('block_coupon','coupon_paypalemail'),
            'payment_fee' => '0.88',
            'receiver_id' => 'S8XGHLYDW9T3S',
            'txn_type' => 'express_checkout',
            'item_name' => $_POST['item_name'],
            'mc_currency' => $_POST['currency_code'],
            'item_number' => $_POST['item_number'],
            'residence_country' => 'US',
            'test_ipn' => '1',
            'handling_amount' => '0.00',
            'transaction_subject' => '',
            'payment_gross' => $_POST['amount'],
            'shipping' => '0.00',
    );
    foreach ($dummy as $key=>$value) $data->$key = $value;
}else {
    $result = $c->post($location, $req, $options);

    if ($c->get_errno()) {
        throw new moodle_exception('errpaypalconnect', 'block_coupon', '', array('url' => $paypaladdr, 'result' => $result),
                json_encode($data));
    }
}
/// Connection is OK, so now we post the data to validate it

/// Now read the response and check if everything is OK.
//print_object($data);die;
if (strlen($result) > 0) {
    if (strcmp($result, "VERIFIED") == 0) {          // VALID PAYMENT!


        // check the payment_status and payment_reason

        // If status is not completed or pending then unenrol the student if already enrolled
        // and notify admin

        if ($data->payment_status != "Completed" and $data->payment_status != "Pending") {
            message_paypal_error_to_admin("Status not completed or pending.",
                                                              $data);
            die;
        }

        // If currency is incorrectly set then someone maybe trying to cheat the system

        if ($data->mc_currency != \block_coupon\helper::get_currency_code()) {
            message_paypal_error_to_admin(
                "Currency does not match course settings, received: ".$data->mc_currency,
                $data);
            die;
        }

        // If status is pending and reason is other than echeck then we are on hold until further notice
        // Email user to let them know. Email admin.

        if ($data->payment_status == "Pending" and $data->pending_reason != "echeck") {

            message_paypal_error_to_admin("Payment pending", $data);
            die;
        }

        // If our status is not completed or not pending on an echeck clearance then ignore and die
        // This check is redundant at present but may be useful if paypal extend the return codes in the future

        if (! ( $data->payment_status == "Completed" or
               ($data->payment_status == "Pending" and $data->pending_reason == "echeck") ) ) {
            die;
        }

        // At this point we only proceed with a status of completed or pending with a reason of echeck

        // Make sure this transaction doesn't exist already.
        /*if ($existing = $DB->get_record("enrol_paypal", array("txn_id" => $data->txn_id), "*", IGNORE_MULTIPLE)) {
            message_paypal_error_to_admin("Transaction $data->txn_id is being repeated!", $data);
            die;
        }*/

        // Check that the receiver email is the one we want it to be.
        if (isset($data->business)) {
            $recipient = $data->business;
        } else if (isset($data->receiver_email)) {
            $recipient = $data->receiver_email;
        } else {
            $recipient = 'empty';
        }

        if (core_text::strtolower($recipient) !== core_text::strtolower(get_config('block_coupon','coupon_paypalemail'))) {
            message_paypal_error_to_admin("Business email is {$recipient} (not ".
                    get_config('block_coupon','coupon_paypalemail').")", $data);
            die;
        }

        if (!$user) {   // Check that user exists
            message_paypal_error_to_admin("User $data->userid doesn't exist", $data);
            die;
        }

        if (!$course) { // Check that course exists
            message_paypal_error_to_admin("Course $data->courseid doesn't exist", $data);
            die;
        }

        $cost = $data->quantity * $data->baseval;

        if ($data->payment_gross < $cost) {
            message_paypal_error_to_admin("Amount paid is not enough ($data->payment_gross < $cost))", $data);
            die;

        }

        // ALL CLEAR !
        if($record = $DB->get_record('block_coupon_purchase_info',array('txn_id'=>$data->txn_id))){
            foreach (array('payer_email','payment_gross','payment_status','timemodified') as $key){
                $record->$key = $data->$key;
            }
            $DB->update_record('block_coupon_purchase_info',$record);
            $id = $record->id;
        }else {
            $id = $DB->insert_record("block_coupon_purchase_info", $data);
        }
        redirect(new \moodle_url('/blocks/coupon/view/generate_email.php',array('id' => $blockid,'pid'=> $id)),get_string('ipn:successmsg','block_coupon'));
    } else if (strcmp ($result, "INVALID") == 0) { // ERROR
        $DB->insert_record("block_coupon_purchase_info", $data, false);
        throw new moodle_exception('erripninvalid', 'block_coupon', '', null, json_encode($data));
    }
}
