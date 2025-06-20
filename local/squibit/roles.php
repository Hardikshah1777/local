<?php

use local_squibit\utility;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

admin_externalpage_setup('local_squibit_rolesync');

$url = new moodle_url('/local/squibit/roles.php');
$action = optional_param('action', null, PARAM_ALPHA);

if (!get_config('local_squibit', 'rolejson')) {
    syncroles(false);
}
if (!empty($action) && confirm_sesskey()) {
    if ($action === 'syncremote') {
        syncroles();
    } else if ($action == 'savechanges') {
        $data = data_submitted();
        $roles = [];
        foreach ($data->roles as $rolename => $roleid) {
            $roles[$rolename] = clean_param($roleid, PARAM_INT);
        }
        set_config('rolemapping', json_encode(array_filter($roles)), 'local_squibit');
        redirect($url, get_string('mappingupdated', 'local_squibit'));
    }
}

$profilefield = utility::getprofilefield();
if (empty($profilefield)) {
    //TODO: pending to create profile
    return;
}

$allroles = json_decode(get_config('local_squibit', 'rolejson'), true);
$rolemappings = utility::get_rolemapping();

$table = new html_table;

$table->head = [get_string('userprofilefields','local_squibit'), get_string('rolesinsquibit','local_squibit')];

foreach ($profilefield->options ?? [] as $key => $name) {
    $key = strtolower($key);
    $value_skip = strtolower($name);
    if($key == 'none' || $value_skip == 'choose...'){
        continue;
    }
    $table->data[] = new html_table_row([
        new html_table_cell($name),
        html_writer::select($allroles, 'roles[' . $key . ']', $rolemappings[$key] ?? null)
    ]);
}

$savebuttoncell = new html_table_cell(
    html_writer::tag(
        'button',
        get_string('savechanges'),
        [
            'type' => 'submit',
            'name' => 'action',
            'value' => 'savechanges',
            'class' => 'btn btn-primary'
        ]
));
$table->data[] = new html_table_row([
    new html_table_cell(
        html_writer::empty_tag(
            'input',
            ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]
        )
    ),
    $savebuttoncell]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('rolesync', 'local_squibit'));
echo '<div class="pull-right"><a class="btn btn-primary" href="'.$CFG->wwwroot.'/admin/settings.php?section=local_squibit">Back</a></div><div style="clear:both;" class="mb-1"></div>';
echo html_writer::start_div('card');
echo html_writer::start_div('card-header');
echo html_writer::tag('b', get_string('availableroles', 'local_squibit'));
echo html_writer::end_div();
echo html_writer::start_div('card-body');
echo html_writer::link(
    new moodle_url($url, ['action' => 'syncremote', 'sesskey' => sesskey()]),
    get_string('getroles', 'local_squibit'), ['class' => 'btn btn-primary pull-right']
);
echo empty($allroles) ? get_string('noroles', 'local_squibit') :
    html_writer::alist($allroles, ['class' => 'list-unstyled']);
echo html_writer::end_div();
echo html_writer::end_div();

//Start form
echo html_writer::tag('h3', get_string('rolemapping', 'local_squibit'), ['class' => 'my-5']);
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::table($table);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();

function syncroles(bool $redirect = true) {
    $url = new moodle_url('/local/squibit/roles.php');
    $rolesdata = local_squibit\squibitapi::get_roles();
    if (!empty($rolesdata) && !empty($rolesdata['success'])) {
        $syncstatus = set_config('rolejson', json_encode(array_column($rolesdata['data']['roles']['data'], 'name', 'id')), 'local_squibit');
    }
    $message = get_string(empty($syncstatus) ? 'syncfail' : 'syncsuccessful', 'local_squibit');
    $messagetype = empty($syncstatus) ? core\notification::ERROR : core\notification::SUCCESS;
    if (!$redirect) {
        core\notification::add($message, $messagetype);
    } else {
        redirect($url, $message, null, $messagetype);
    }
}
