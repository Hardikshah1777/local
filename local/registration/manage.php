<?php

require_once '../../config.php';
require_once $CFG->libdir . '/tablelib.php';

$id = optional_param('deleteid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$addurl = new moodle_url('/local/registration/edit.php');
$addsurl = new moodle_url('/local/registration/bulkcoupon.php');
$context = context_system::instance();
$url = new moodle_url('/local/registration/manage.php');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_registration'));
$PAGE->set_heading(get_string('couponlist', 'local_registration'));

require_admin();

class registration extends table_sql
{
    public function col_status($row)
    {
        if ($row->visible == 0) {
            $data = 'Disable';
        } else {
            $data = 'Enable';
        }
        return $data;
    }
    public function col_users($row){
        global $DB;
        $count=$DB->count_records('local_registration_users', ['couponid'=>$row->id]);
        return $count;
    }
    public function col_Action($row)
    {
        global $OUTPUT, $DB;
        $data=$DB->get_records('local_registration_users',['couponid'=>$row->id],'id,couponid');

        $myurl = new moodle_url('/local/registration/edit.php', ['id' => $row->id]);
        $editbutton = html_writer::link($myurl, $OUTPUT->pix_icon('t/edit', get_string('edit', 'local_registration')));

        $myurl = new moodle_url('/local/registration/users.php', ['id' => $row->id, 'data' => $row->couponcode]);
        $user = html_writer::link($myurl, $OUTPUT->pix_icon('t/user', get_string('users', 'local_registration')));

        if(!$data){
            $newurl = new moodle_url('/local/registration/manage.php', ['deleteid' => $row->id]);
            $delete = $DB->get_record('local_registration', ['id' => $row->id], 'couponcode', );
            $confirm = new confirm_action(get_string('deleteconfirmation', 'local_registration', $delete->couponcode));
            $deletebutton = $OUTPUT->action_icon($newurl, new pix_icon('t/delete', get_string('delete', 'local_registration')), $confirm);
        }else{
            $deletebutton='';
        }
        return $user . '' . $editbutton . '' . $deletebutton;
    }
}

if (!empty($id)) {
    $DB->delete_records('local_registration', ['id' => $id]);
    redirect($url, get_string('codedeletemsg', 'local_registration'));
}

$table = new registration('id');

$select = 'r.id, r.couponcode, r.visible,c.fullname AS course';
$from = '{local_registration} r
         LEFT JOIN {course} c ON c.id = r.courseid';

$table->set_sql($select,$from,'1=1');

$col = [
    'couponcode' => get_string('couponcode', 'local_registration'),
    'status' => get_string('status', 'local_registration'),
    'course' => get_string('course', 'local_registration'),
    'users' => get_string('usercount', 'local_registration'),
    'action' => get_string('action', 'local_registration'),
];

if ($download) {
    $select .= ',u.id,u.firstname,u.lastname,u.phone1,u.email';

    $from .= '
    JOIN {local_registration_users} ru ON ru.couponid = r.id
    LEFT JOIN {user} u ON u.id = ru.userid';

    $table->set_sql($select, $from, '1=1');

    $col1 = [
        'firstname' => get_string('firstname','local_registration'),
        'lastname' => get_string('lastname','local_registration'),
        'phone1' => get_string('telephone','local_registration'),
        'email' => get_string('email','local_registration'),
    ];

    $col = array_merge($col, $col1);
    unset($col['action']);
    unset($col['status']);
    unset($col['users']);
}

$table->define_columns(array_keys($col));
$table->define_headers(array_values($col));
$table->sortable(false);
$table->collapsible(false);
$table->define_baseurl($url);

$table->is_downloadable(false);
if ($table->is_downloading($download,get_string('registeruser','local_registration'),get_string('registeruser','local_registration'))) {
    $table->out(10,false);
}

echo $OUTPUT->header();
if (!$table->is_downloading()) {
    echo html_writer::start_div('d-flex justify-content-end mb-2');
    echo html_writer::link($addurl,get_string('addcoupon','local_registration'),['class' => 'btn btn-secondary float-right']);
    echo html_writer::link($addsurl,get_string('uploadcoupons','local_registration'), ['class' => 'btn btn-secondary float-right ml-2']);
    echo html_writer::end_div();
}

$table->out(30,false);

echo $OUTPUT->footer();
