<?php

use local_test1\form\searchform;
use local_test1\table\userlist;

require_once '../../config.php';
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');

$search = optional_param('search', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHANUM);
$delete = optional_param('delete', 0, PARAM_INT);
$exportinzip = optional_param('exportinzip', '', PARAM_ALPHA);

$url = new moodle_url('/local/test1/index.php', ['search' => $search]);
$context = context_system::instance();

$PAGE->set_title(get_string('title', 'local_test1'));
$PAGE->set_heading(get_string('heading', 'local_test1'));
$PAGE->set_url($url);
$PAGE->set_context($context);
require_admin();

if (!empty($delete)) {
    if ($deluser = $DB->get_record( 'user', ['id' => $delete])) {
        delete_user($deluser);
    }
    redirect($url);
}

$searchform = new searchform($url->out(false));
$searchform->set_data(['search' => $search]);

$userlisttable = new userlist('userlist');

$where = '';
$params = [];

if (!empty($search)) {
    $search = trim($search);
    $where = " AND (" . $DB->sql_like('firstname', ':firstname', false) .
        " OR " . $DB->sql_like('lastname', ':lastname', false) .
        " OR " . $DB->sql_like('username', ':username', false) .
        " OR " . $DB->sql_like('email', ':email', false) . ")";
    $params['firstname'] = $params['lastname'] = $params['username'] = $params['email'] = '%' . $search . '%';
}

$userlisttable->set_sql('*',
                        '{user}',
                        'id > 2 AND deleted = 0 AND suspended = 0 ' . $where, $params);

$searchcount = $DB->count_records_sql('SELECT COUNT(1) FROM {user} WHERE deleted = 0 AND suspended = 0 AND id > 2 ' . $where, $params );
$totalcount = $DB->count_records_sql('SELECT COUNT(1) FROM {user} WHERE deleted = 0 AND suspended = 0 AND id > 2' );

$col = [
    'profile' => '',
    'fullname' => get_string('fullname'),
    'email' => get_string('email'),
    'city' => get_string('city'),
    'timecreated' => get_string('date'),
    'action' => get_string('action'),
];

$userlisttable->define_headers(array_values($col));
$userlisttable->define_columns(array_keys($col));
$userlisttable->sortable(true);
$userlisttable->sortable(true,'id',SORT_ASC);
$userlisttable->no_sorting('profile');
$userlisttable->no_sorting('action');
$userlisttable->collapsible(false);
$userlisttable->search = $search;
$userlisttable->define_baseurl($url);
$userlisttable->show_download_buttons_at([TABLE_P_BOTTOM]);
$userlisttable->set_attribute('id', 'userlist');
$userlisttable->is_downloadable(false);
$userlisttable->attributes = ['style' => 'background-color: #ededf7de'];
if ($userlisttable->is_downloading($download, 'Users', 'Users')) {
    unset($userlisttable->headers[0], $userlisttable->headers[5],
          $userlisttable->columns['profile'], $userlisttable->columns['action']);
    $userlisttable->out( 50, false );
}

if ($exportinzip == "exportinzip") {
    $zipper = get_file_packer( 'application/zip' );
    $temppath = make_request_directory() . '/zip';
    $zipfiles = [];
    $users = $DB->get_records_sql( 'SELECT ' . $userlisttable->sql->fields . ' FROM ' . $userlisttable->sql->from . ' WHERE ' . $userlisttable->sql->where, $userlisttable->sql->params );

    foreach ($users as $user) {
        $zipfiles[fullname( $user )] = ["\nFullname: " . fullname( $user ) .
            "\nEmail: " . $user->email .
            "\ncity: " . $user->city .
            "\nCreated Date: " . userdate( $user->timecreated, get_string( 'strftimedatetimeshortaccurate', 'core_langconfig' ) ) ?? '-'];
    }
    if (!empty( $zipfiles )) {
        $zipper->archive_to_pathname($zipfiles, $temppath);
        $filename = 'Users_' . date('Ymd_His') . '.zip';
        send_file($temppath, $filename);
        exit;
    } else {
        \core\notification::add( get_string( 'nodatatoexport', 'local_test1' ), \core\notification::WARNING );
    }
}

echo $OUTPUT->header();

$PAGE->requires->js_call_amd('local_test1/test1', 'init');
echo html_writer::tag( 'div', '', ['id' => 'toast', 'class' => 'toast'] );
echo html_writer::tag( 'link', '', ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css'] );
echo html_writer::tag( 'link', '', ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'https://cdn.datatables.net/2.3.2/js/dataTables.min.js'] );
echo html_writer::tag( 'link', '', ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css'] );
echo html_writer::tag( 'link', '', ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css'] );
echo html_writer::tag( 'script', '', ['type' => 'text/javascript', 'src' => 'https://cdn.datatables.net/2.3.2/js/dataTables.js'] );
echo html_writer::tag( 'script', '', ['type' => 'text/javascript', 'src' => 'https://code.jquery.com/jquery-3.6.0.min.js'] );
echo html_writer::tag( 'script', '', ['type' => 'text/javascript', 'src' => 'https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js'] );
echo html_writer::tag( 'script', '', ['type' => 'text/javascript', 'src' => 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'] );
echo html_writer::tag( 'script', '', ['type' => 'text/javascript', 'src' => 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js'] );

$searchform->display();

echo html_writer::tag( 'span', 'User count = ' . $searchcount . '/' . $totalcount, ['class' => 'd-flex justify-content-end']);
$userlisttable->out( 50, false );
$btndownloadzip = $OUTPUT->single_button( new moodle_url( "/local/test1/index.php", ['exportinzip' => 'exportinzip', 'search' => $search] ), "Users zip");
echo html_writer::tag( 'div', $btndownloadzip, ['id' => 'exportinzip']);

echo $OUTPUT->footer();