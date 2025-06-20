<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/tablelib.php');

$search = optional_param('search',null,PARAM_TEXT);
$userid = optional_param('userid',null,PARAM_INT);

$context = context_system::instance();
$url = new moodle_url('/admin/tool/mfa/ignoreauth.php');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('ignoreauthheading', 'tool_mfa'));
$PAGE->set_heading(get_string('ignoreauthtitle', 'tool_mfa'));
$PAGE->set_secondary_navigation(false);
require_admin();

class usersearch_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'search', get_string('searchuser', 'tool_mfa'));
        $mform->setType('search',PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search','tool_mfa'));
    }
}

$form = new usersearch_form();
class bypassuser_table extends table_sql {

    public function col_lastaccess($row) {
        if (!empty($row->lastaccess)) {
            $time = userdate($row->lastaccess);
        }else{
            $time = '-';
        }
        return $time;
    }

    public function col_timecreated($row) {
        if (!empty($row->timecreated)) {
            $time = userdate($row->timecreated);
        }else{
            $time = '-';
        }
        return $time;
    }
    public function col_action($row) {
        global $OUTPUT;
        $link = new moodle_url('/admin/tool/mfa/ignoreauth.php', ['userid' => $row->userid]);
        $confirm = new confirm_action(get_string('ignoreauthconfirm', 'tool_mfa'));
        $resendbutton = new action_link($link, get_string('bypassbtn', 'tool_mfa'), $confirm, ['class' => 'btn btn-primary']);
        return $OUTPUT->render($resendbutton);
    }
}

$col =  [
    'fullname' => get_string('firstname','tool_mfa'),
    'lastname' => get_string('lastname','tool_mfa'),
    'email' => get_string('email','tool_mfa'),
    'city' => get_string('city','tool_mfa'),
    'country' => get_string('country','tool_mfa'),
    'lastaccess' => get_string('lastaccess','tool_mfa'),
    'timecreated' => get_string('timecreated','tool_mfa'),
    'action' => get_string('action','tool_mfa'),
];

$table = new bypassuser_table('id');
$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->sortable(true);
$table->no_sorting('action');
$table->define_baseurl($url);
//$table->show_download_buttons_at(array(TABLE_P_BOTTOM));

$where = '';
$params[] = '';
$data[] = '';

if (!empty($search)) {
    $where .= ' AND ('.$DB->sql_like('firstname',':firstname',false).
        ' OR '. $DB->sql_like('lastname',':lastname',false).
        ' OR '. $DB->sql_like('email',':email',false).')';
    $params['firstname'] = $params['lastname'] = $params['email'] = '%' . trim($search) . '%';
}

$table->set_sql('*', '{user}', ' id > 2 '. $where, $params);

echo $OUTPUT->header();
$form->display();
$table->out(30, true);
echo $OUTPUT->footer();