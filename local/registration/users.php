<?php 
require_once('../../config.php');
require_once $CFG->libdir . '/tablelib.php';
$id =optional_param('id',0,PARAM_INT);
$data =optional_param('data','',PARAM_TEXT);
$url=new moodle_url('/local/registration/users.php',['id'=>$id,'data'=>$data]);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('userslist','local_registration',['data'=>$data]));
require_admin();
class user extends table_sql
{
        public function col_fullname($row){
        $profile = new moodle_url('/user/view.php', ['id' => $row->userid]);
        $fullname = html_writer::link($profile, $row->firstname . ' ' . $row->lastname);
        
        return $fullname;
    }
}
$table = new user('id');
$table->set_sql('r.id, u.id AS userid,u.firstname,u.lastname, u.phone1,u.email,ru.couponcode',
        "{local_registration_users} r 
        LEFT JOIN {user} u ON u.id = r.userid
        LEFT JOIN {local_registration} ru ON ru.id =r.couponid",
        'couponid='.$id);

$col = [
    'fullname' => get_string('firstname/lastname', 'local_registration'),
    'phone1' => get_string('telephone', 'local_registration'),
    'email' => get_string('email', 'local_registration'),
];

$table->define_columns(array_keys($col));
$table->define_headers(array_values($col));
$table->sortable(false);
$table->collapsible(false);
$table->define_baseurl($url);

echo $OUTPUT->header();
echo '<h3>'.get_string('userslist','local_registration',['data'=>$data]).'</h3>';
$table->out(10, false);
echo $OUTPUT->footer();