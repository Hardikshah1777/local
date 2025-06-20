<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$userid = optional_param('userid', 0, PARAM_INT);
$context = context_system::instance();
$url = new moodle_url('/admin/tool/mfa/invalidtry.php');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('invalidtry', 'tool_mfa'));
$PAGE->set_heading(get_string('heading', 'tool_mfa'));
$PAGE->set_secondary_navigation(false);
require_login();

if (!empty($userid)) {
    $params = ['userid' => $userid];
    $DB->set_field('tool_mfa', 'revoked', 0, $params);
    redirect($url, get_string('unlockmsg', 'tool_mfa'));
}

class invalidtry_table extends table_sql
{
    public function query_db($pagesize, $useinitialsbar = true)
    {
        global $DB;
        $this->rawdata = $DB->get_records_sql('SELECT  a.userid, MAX(a.revoked) AS revoked, u.firstname, u.lastname, u.email 
                                                    FROM {tool_mfa} a 
                                                    JOIN {user} u ON u.id = a.userid 
                                                    WHERE a.userid > 2 AND revoked > 0 GROUP BY a.userid');
    }

    public function col_revoked($data)
    {
        if (!empty($data->revoked)) {
            global $OUTPUT;
            $action = new confirm_action(get_string('cnfunlock', 'tool_mfa'));
            return $OUTPUT->action_link(new moodle_url('/admin/tool/mfa/invalidtry.php', ['userid' => $data->userid]), get_string('unlockbtn', 'tool_mfa'),  $action,['class' => 'btn btn-secondary']);
        }
    }
}

$table = new invalidtry_table(get_string('invalidtry', 'tool_mfa'));
$col = [
    'firstname' => get_string('firstname'),
    'lastname' => get_string('lastname', 'tool_mfa'),
    'email' => get_string('email'),
    'revoked' => get_string('action'),
];

$table->define_headers(array_values($col));
$table->define_columns(array_keys($col));
$table->collapsible(false);
$table->define_baseurl($url);
$table->sortable(false);

echo $OUTPUT->header();
$table->out(30, true);
echo $OUTPUT->footer();