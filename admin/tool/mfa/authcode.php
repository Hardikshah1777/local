<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/tablelib.php');

$search = optional_param('search',null,PARAM_TEXT);
$userid = optional_param('userid',null,PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/admin/tool/mfa/authcode.php');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('authcodeheading', 'tool_mfa'));
$PAGE->set_heading(get_string('authcodetitle', 'tool_mfa'));
$PAGE->set_secondary_navigation(false);
require_admin();

if (!empty($userid)){
    $params['userid'] = $userid;
    $codedata = $DB->get_record_sql('SELECT * FROM {tool_mfa} WHERE userid=:userid AND secret > 0 AND secret IS NOT NULL', $params);
    if (!empty($codedata)) {
        $touser = core_user::get_user($userid);
        $from = core_user::get_support_user();
        $subject = get_string('mfasubject', 'tool_mfa');
        $message = get_string('mfamessage', 'tool_mfa', $codedata);
        email_to_user($touser, $from, $subject, $message);
    }
}

class usersearch_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'search', get_string('searchuser', 'tool_mfa'));
        $mform->setType('search',PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search','tool_mfa'));
    }

}
$form = new usersearch_form();

class confirmationcodes_table extends table_sql {
    public function col_action($row) {
        global $OUTPUT;
        $link = new moodle_url('/admin/tool/mfa/authcode.php', ['userid' => $row->userid]);
        $confirm = new confirm_action(get_string('resendconfirm', 'tool_mfa'));
        $resendbutton = new action_link($link, get_string('resendbtn', 'tool_mfa'), $confirm, ['class' => 'btn btn-primary']);
        return $OUTPUT->render($resendbutton);
    }

    public function col_timecreated($row) {
        if (!empty($row->timecreated)) {
            $time = userdate($row->timecreated);
        }else{
            $time = '-';
        }
        return $time;
    }
}

$col =  [
        'firstname' => get_string('firstname','tool_mfa'),
        'lastname' => get_string('lastname','tool_mfa'),
        'email' => get_string('email','tool_mfa'),
        'secret' => get_string('secret','tool_mfa'),
        'timecreated' => get_string('timecreated','tool_mfa'),
        'action' => get_string('action','tool_mfa'),
];

$table = new confirmationcodes_table('codes');
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->sortable(false);
$table->define_baseurl($url);

$where = '';
$params[] = '';
$data[] = '';

if (!empty($search)) {
    $where .= ' AND ('.$DB->sql_like('firstname',':firstname',false).
              ' OR '. $DB->sql_like('lastname',':lastname',false).
              ' OR '. $DB->sql_like('email',':email',false).')';
    $params['firstname'] = $params['lastname'] = $params['email'] = '%' . trim($search) . '%';
}

$table->set_sql('tm.id,u.firstname,u.lastname,u.email,tm.userid,tm.secret,tm.timecreated',
        '{tool_mfa} tm JOIN {user} u ON u.id = tm.userid',
        'tm.secret IS NOT NULL AND tm.secret > 0 '. $where, $params);

echo $OUTPUT->header();
$form->display();
$table->out(30, false);
echo $OUTPUT->footer();