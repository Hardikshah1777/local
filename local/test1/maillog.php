<?php

require_once '../../config.php';

use local_test1\form\logfilter;
use local_test1\table\maillog;

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

if (!empty($endtime) || is_array($endtime)) {
    $timeend = is_array( $endtime ) ? make_timestamp( $endtime['year'], $endtime['month'], $endtime['day'], 23, 59, 59 ) : $endtime;
}else {
    if (array_key_exists('page', $_GET) || array_key_exists('tsort', $_GET)) {
        $timeend = $timeend;
    }
}

$where = '';
if (!empty($type)) {
    $where .= " AND (" . $DB->sql_like('ml.type', ':type', false). " ) ";
    $params['type'] .= $type;
    $url->param('type', $type);
}

if (!empty($timestart)) {
    $where .= ' AND sendtime >= :timestart';
    $params['timestart'] .= $timestart;
    $url->param('starttime', $timestart);
}

if (!empty($timeend)) {
    $where .= ' AND sendtime <= :timeend';
    $params['timeend'] .= $timeend;
    $url->param('endtime', $timeend);
}

$user = core_user::get_user($userid);
$fullname = fullname($user);
$params['userid'] = $userid;
$table = new maillog('custommaillog');
$filterform = new logfilter($url->out(false), ['userid' => $userid]);
$filterform->set_data(['type' => $type, 'starttime' => $starttime, 'endtime' => $endtime]);

$table->set_sql('ml.id,u.firstname, u.lastname, u.email, ml.userid as userid, ml.mailer, ml.type, ml.subject, ml.body, ml.sendtime, ml.resendtime',
                '{local_test1_mail_log} ml
                       JOIN {user} u ON u.id = ml.userid',
                'ml.userid = :userid '. $where, $params);

$col = [
  'name' => get_string('name','local_test1'),
  'email' => get_string('email1','local_test1'),
  'mailer' => get_string('mailer','local_test1'),
  'type' => get_string('type','local_test1'),
  'sendtime' => get_string('sendtime','local_test1'),
  'resendtime' => get_string('resendtime','local_test1'),
  'action' => get_string('action','local_test1'),
];

$table->define_baseurl($url);
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->sortable(true,'sendtime', SORT_DESC);
$table->no_sorting('name');
$table->no_sorting('email');
$table->no_sorting('action');
$table->showdownloadbuttonsat = [TABLE_P_BOTTOM];
$table->collapsible(false);
$table->is_downloadable(false);
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