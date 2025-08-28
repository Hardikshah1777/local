<?php

require_once '../../config.php';
require_once($CFG->libdir . '/formslib.php');

use local_test1\form\logfilter;
use local_test1\table\maillog;

$id = optional_param('id', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHANUM);
$type = optional_param('type', '', PARAM_TEXT);

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
require_admin();

$params['userid'] = $id;
$table = new maillog('maillog');
$filterform = new logfilter($PAGE->url, ['userid' => $id]);
$where = '';
if (!empty($type)) {
    $where = " AND (" . $DB->sql_like( 'ml.type', ':type', false). " ) ";
    $params['type'] = $type;
}

$table->set_sql('ml.id, ml.userid, ml.mailer, ml.type, ml.sendtime, u.firstname, u.lastname',
                '{local_test1_mail_log} ml
                       JOIN {user} u ON u.id = ml.userid',
                'ml.userid = :userid '. $where, $params);

$col = [
  'name' => 'Fullname',
  'mailer' => 'Mailer',
  'type' => 'Mail type',
  'sendtime' => 'Send time',
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
$table->out(50, false );
echo $OUTPUT->footer();