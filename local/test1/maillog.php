<?php

require_once '../../config.php';

use local_test1\form\logfilter;
use local_test1\table\maillog;

$id = optional_param('id', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHANUM);
$type = optional_param('type', '', PARAM_TEXT);
$starttime = optional_param('starttime', 0, PARAM_INT);
$endtime = optional_param('endtime', 0, PARAM_INT);

$url = new moodle_url( '/local/test1/maillog.php', ['id' => $id, 'type' => $type]);
$context = context_system::instance();

if (!$DB->record_exists('user', ['id' =>$id ])){
    throw new exception('Invalid userid');
}

$user = core_user::get_user($id);
$fullname = fullname($user);
$PAGE->set_title('Mails Detail');
$PAGE->set_url($url);
$PAGE->set_context($context);
require_login();

$params['userid'] = $id;
$table = new maillog('maillog');
$filterform = new logfilter($url->out(false), ['userid' => $id]);
$filterform->set_data(['type' => $type, 'starttime' => $starttime, 'endtime' => $endtime]);
$where = '';

if (!empty($type)) {
    $where .= " AND (" . $DB->sql_like('ml.type', ':type', false). " ) ";
    $params['type'] .= $type;
}

if (!empty($starttime)) {
    $timestart = strtotime($starttime['day'].'-'.$starttime['month'].'-'.$starttime['year']);
    $where .= ' AND sendtime >= :timestart';
    $params['timestart'] .= $timestart;
}

if (!empty($endtime)) {
    $timeend = make_timestamp( $endtime['year'], $endtime['month'], $endtime['day'], 23, 59, 59 );
    $where .= ' AND sendtime <= :timeend';
    $params['timeend'] .= $timeend;
}

$table->set_sql('ml.id, ml.userid, ml.mailer, ml.type, ml.sendtime, u.firstname, u.lastname, u.email',
                '{local_test1_mail_log} ml
                       JOIN {user} u ON u.id = ml.userid',
                'ml.userid = :userid '. $where, $params);

$col = [
  'name' => get_string('name','local_test1'),
  'email' => get_string('email1','local_test1'),
  'mailer' => get_string('mailer','local_test1'),
  'type' => get_string('type','local_test1'),
  'sendtime' => get_string('sendtime','local_test1'),
];

$table->define_baseurl($url);
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->sortable(false);
$table->showdownloadbuttonsat = [TABLE_P_BOTTOM];
$table->collapsible(false);
$table->is_downloadable(false);
if ($table->is_downloading($download, $fullname.' Mails', ' Mails logs')) {
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
$table->out(3, false );
echo $OUTPUT->footer();