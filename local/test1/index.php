<?php

require_once '../../config.php';
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');

$search = optional_param( 'search', '', PARAM_TEXT );
$download = optional_param( 'download', '', PARAM_ALPHANUM );
$delete = optional_param( 'delete', '', PARAM_INT );
$exportinzip = optional_param( 'exportinzip', '', PARAM_ALPHA );

$url = new moodle_url( '/local/test1/index.php', ['search' => $search] );
$context = context_system::instance();

$PAGE->set_title( get_string( 'title', 'local_test1' ) );
$PAGE->set_heading( get_string( 'heading', 'local_test1' ) );
$PAGE->set_url( $url );
$PAGE->set_context( $context );
require_admin();
$users = $DB->get_records_sql('SELECT * FROM `mdl_user` WHERE timecreated > 1755511758 order by id desc LIMIT 5');
foreach ($users as $user) {
    if(email_to_user($user, $USER, 'student', 'student')) {
        \core\notification::success( 'Mail send to : ' . fullname( $user ) );
    }
}
//die('------------------------ Here ------------------------');
class searchform extends moodleform {
    public function definition()
    {
        $mform = $this->_form;
        $mform->addElement( 'text', 'search', get_string( 'search', 'local_test1' ) );
        $mform->setType( 'search', PARAM_TEXT );
        $this->add_action_buttons( false, get_string( 'search', 'local_test1' ) );
    }
}

class userlist extends table_sql {
    public $showpaginationsat = [TABLE_P_BOTTOM];

    public $search;

    public function col_profile($row) {
        global $OUTPUT;
        return $OUTPUT->user_picture( $row, ['size' => 40, 'link' => true, 'alttext' => false] );
    }

    public function col_timecreated($row) {
        return userdate( $row->timecreated, get_string( 'strftimedatetime', 'core_langconfig' ) );
    }

    public function col_action($row) {
        global $OUTPUT;
        $edituser1 = new moodle_url( '/user/editadvanced.php', ['id' => $row->id, 'localtest1' => 'localtest1'] );
        $edituser = $OUTPUT->action_link( $edituser1, new pix_icon( 't/edit', get_string( 'edit' ) ) );

        //$mail = new moodle_url('/local/test1/testmail.php', ['data-uid' => $row->id, 'search'=> $this->search]);
        $email = $OUTPUT->action_link( '#', new pix_icon( 't/email', get_string( 'email', 'local_test1' ) ), null, ['data-uid' => $row->id, 'class' => 'maillink'] );

        $downloadpdf = $OUTPUT->action_link( '#', new pix_icon( 'f/pdf-128', get_string( 'pdf', 'local_test1' ) ), null, ['data-user' => json_encode( $row ), 'class' => 'downloadpdf'] );

        $downloadcsv = $OUTPUT->action_link( '#', new pix_icon( 'f/calc-128', get_string( 'csv', 'local_test1' ) ), null, ['data-user' => json_encode( $row ), 'class' => 'downloadcsv'] );

        $edituser2 = new moodle_url( '/local/test1/index.php', ['delete' => $row->id, 'localtest1' => 'localtest1'] );
        $confirm = new confirm_action( get_string( 'confirmuserdelete', 'local_test1', $row ) );
        $deleteuser = $OUTPUT->action_link( $edituser2, new pix_icon( 't/delete', get_string( 'delete' ) ), $confirm );

        return ($edituser . $email . $downloadpdf . $downloadcsv . $deleteuser);
    }

    function start_html() {
        global $OUTPUT;

        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        // Do we need to print initial bars?
        $this->print_initials_bar();

        // Paging bar
        if ($this->use_pages && in_array( TABLE_P_TOP, $this->showpaginationsat )) {
            $pagingbar = new paging_bar( $this->totalrows, $this->currpage, $this->pagesize, $this->baseurl );
            $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
            echo $OUTPUT->render( $pagingbar );
        }

        if (in_array( TABLE_P_TOP, $this->showdownloadbuttonsat )) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table

        echo html_writer::start_tag( 'div', array('class' => 'no-overflow') );
        echo html_writer::start_tag( 'table', $this->attributes );
    }
}

if (!empty( $delete )) {
    $deluser = $DB->get_record( 'user', ['id' => $delete] );
    delete_user( $deluser );
    redirect( $url );
}

$searchform = new searchform( $url );
$userlisttable = new userlist( 'userlist' );
$where = '';
$params = [];

if (!empty( $search )) {
    $search = trim( $search );
    $where = " AND (" . $DB->sql_like( 'firstname', ':firstname', false ) .
        " OR " . $DB->sql_like( 'lastname', ':lastname', false ) .
        " OR " . $DB->sql_like( 'username', ':username', false ) .
        " OR " . $DB->sql_like( 'email', ':email', false ) . ")";
    $params['firstname'] = $params['lastname'] = $params['username'] = $params['email'] = '%' . $search . '%';
}

$userlisttable->set_sql('id, username, firstname, lastname, email, city, timecreated',
    '{user}',
    'id > 2 AND deleted = 0 AND suspended = 0 ' . $where, $params);
$searchcount = $DB->count_records_sql( 'SELECT COUNT(1) FROM {user} WHERE deleted = 0 AND suspended = 0 AND id > 2 ' . $where, $params );
$totalcount = $DB->count_records_sql( 'SELECT COUNT(1) FROM {user} WHERE deleted = 0 AND suspended = 0 AND id > 2' );
$col = [
    'profile' => '#',
    'fullname' => get_string( 'fullname' ),
    'email' => get_string( 'email' ),
    'city' => get_string( 'city' ),
    'timecreated' => get_string( 'date' ),
    'action' => get_string( 'action' ),
];

$userlisttable->search = $search;
$userlisttable->define_baseurl( $url );
$userlisttable->define_headers( array_values( $col ) );
$userlisttable->define_columns( array_keys( $col ) );
$userlisttable->sortable( true );
$userlisttable->sortable(true,'timecreated',SORT_DESC);
$userlisttable->no_sorting( 'profile' );
$userlisttable->no_sorting( 'action');
$userlisttable->collapsible( false );
$userlisttable->show_download_buttons_at( [TABLE_P_BOTTOM] );
$userlisttable->set_attribute( 'id', 'userlist' );
$userlisttable->is_downloadable( false );

if ($userlisttable->is_downloading( $download, 'Users', 'Users' )) {
    unset( $userlisttable->headers[0] );
    unset( $userlisttable->headers[5] );
    unset( $userlisttable->columns['profile'] );
    unset( $userlisttable->columns['action'] );
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
//$userlisttable->out( 50, false );
$btndownloadzip = $OUTPUT->single_button( new moodle_url( "/local/test1/index.php", ['exportinzip' => 'exportinzip', 'search' => $search] ), "Users zip");
echo html_writer::tag( 'div', $btndownloadzip, ['id' => 'exportinzip']);
echo $OUTPUT->footer();