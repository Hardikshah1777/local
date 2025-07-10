<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once "$CFG->libdir/tablelib.php";

$orgid = optional_param('orgid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', 0, PARAM_RAW);
$delid = optional_param('delid', 0, PARAM_RAW);
$download = optional_param('download', '', PARAM_ALPHA);
$perpage = 15;

$context = context_system::instance();
$url = new moodle_url('/blocks/vxg_orgs/viewadmin.php', ['orgid' => $orgid]);
$dashurl = new moodle_url('/my');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title('View Admins');
$PAGE->set_heading('View Admins');
require_login();

if (!is_siteadmin()) {
    redirect($dashurl);
}
if (!empty($delid)) {
    $deleteuser = $DB->get_record('user', ['id' => $delid]);
    delete_user($deleteuser);
}

class viewadmins extends table_sql
{
    public $orgid;

    public function col_id($data)
    {
        global $OUTPUT;
        return $OUTPUT->user_picture($data, ['size' => 35, '', 'includefullname' => false]);
    }

    public function col_action($data)
    {
        global $OUTPUT;

        $editurl = new moodle_url('/user/editadvanced.php', ['id' => $data->id]);
        $editbtn = $OUTPUT->action_link($editurl, '', null, null, new \pix_icon('t/edit', get_string('edituser', 'block_vxg_orgs')));
        $action = new \confirm_action(get_string('areyousureadmins', 'block_vxg_orgs'));
        $deleteurl = new moodle_url('/blocks/vxg_orgs/viewadmin.php', ['orgid' => $this->orgid, 'delid' => $data->id]);
        $actionlink = $OUTPUT->action_link($deleteurl, '', $action, null, new \pix_icon('t/delete', get_string('deleteuser', 'block_vxg_orgs')));
        return $editbtn . $actionlink;
    }
}

$params = ['objectid' => $orgid];
$table = new viewadmins('Org Users');
$table->set_sql('ou.id,u.* ,ou.objectid',
    '{block_vxg_orgs_right} ou JOIN {user} u ON u.id = ou.userid',
    'objectid =:objectid', $params);
$col = [
    'id' => 'Profile',
    'fullname' => get_string('firstname'),
    'email' => get_string('email'),
    'action' => get_string('action', 'block_vxg_orgs'),
];

$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->define_baseurl($url);
$table->sortable(true);
$table->no_sorting('id');
$table->no_sorting('email');
$table->no_sorting('action');
$table->orgid = $orgid;
$backbtn = new single_button($dashurl, get_string('back', 'block_vxg_orgs'),'post',true,['class' => 'px-lg-3']);

$table->is_downloadable(true);
if ($table->is_downloading($download, 'Org Users', 'Org Users')) {
    unset($table->headers[0], $table->columns['id'],$table->headers[3], $table->columns['action']);
    $table->out($perpage, false);
}

echo $OUTPUT->header();

echo html_writer::start_div('d-flex justify-content-end pb-2');
echo $OUTPUT->render($backbtn);
echo html_writer::end_div();

$table->out($perpage, false);

echo $OUTPUT->footer();