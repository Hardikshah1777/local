<?php

require_once '../../config.php';

use core_table\local\filter\filter;
use local_test1\form\logfilter;
use local_test1\table\maillog;
use local_test1\table\maillog_filterset;

$userid = optional_param('userid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHANUM);
$type = optional_param('type', '', PARAM_TEXT);
$starttime = optional_param('starttime', 0, PARAM_INT);
$endtime = optional_param('endtime', 0, PARAM_INT);

$url = new moodle_url( '/local/test1/maillog.php', ['userid' => $userid]);
$context = context_system::instance();

$PAGE->set_title('Mails Detail');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->requires->js_call_amd('local_test1/test1', 'init');
require_login();

if (!$DB->record_exists('user', ['id' => $userid ])) {
    throw new exception('Invalid userid');
}
if (!empty($starttime) && is_array($starttime)) {
    $timestart = strtotime($starttime['day'] . '-' . $starttime['month'] . '-' . $starttime['year']);
} else {
    if (array_key_exists('page', $_GET) || array_key_exists('tsort', $_GET)) {
        $timestart = $starttime;
    }
}

if (!empty($endtime) && is_array($endtime)) {
    $timeend = is_array( $endtime ) ? make_timestamp( $endtime['year'], $endtime['month'], $endtime['day'], 23, 59, 59 ) : $endtime;
}else {
    if (array_key_exists('page', $_GET) || array_key_exists('tsort', $_GET)) {
        $timeend = $endtime;
    }
}

if (!empty($type)) {
    $url->param('type', $type);
}

if (!empty($timestart)) {
    $url->param('starttime', $timestart);
}

if (!empty($timeend)) {
    $url->param('endtime', $timeend);
}

$user = core_user::get_user($userid);
$fullname = fullname($user);

$filterset = (new maillog_filterset())
->add_filter_from_params('userid', filter::JOINTYPE_DEFAULT, (array) $userid)
->add_filter_from_params('type', filter::JOINTYPE_DEFAULT, (array) $type)
->add_filter_from_params('timestart', filter::JOINTYPE_DEFAULT, (array) $timestart)
->add_filter_from_params('timeend', filter::JOINTYPE_DEFAULT, (array) $timeend);

$table = new maillog(uniqid('custommaillog-'));
$table->set_filterset($filterset);

$filterform = new logfilter($url->out(false), ['userid' => $userid]);
$filterform->set_data(['type' => $type, 'starttime' => $starttime, 'endtime' => $endtime]);

if ($table->is_downloading($download, $fullname.' Mails', ' Mails logs')) {
    unset($table->headers[6]);
    unset($table->columns['action']);
    $table->out(50, false);
}
echo $OUTPUT->header();
$backurl = new moodle_url( '/local/test1/index.php');
$backbtn = html_writer::link($backurl, 'Back', ['class' => 'btn btn-primary mt-1']);
echo '<div class="d-flex justify-content-between mb-2">
    <div><h2>'. $fullname .'</h2> </div>
    <div>' . $backbtn . '</div>
</div>';

$filterform->display();
$table->out(50, false );
echo $OUTPUT->footer();