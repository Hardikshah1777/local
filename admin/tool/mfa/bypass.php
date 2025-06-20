<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);
$confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
$sort         = optional_param('sort', 'name', PARAM_ALPHANUMEXT);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);
$acl          = optional_param('acl', '0', PARAM_INT);
$suspend      = optional_param('suspend', 0, PARAM_INT);
$unsuspend    = optional_param('unsuspend', 0, PARAM_INT);
$unlock       = optional_param('unlock', 0, PARAM_INT);
$resendemail  = optional_param('resendemail', 0, PARAM_INT);
$userstatus   = optional_param('userstatus', 0, PARAM_INT);
$skipuser     = optional_param('skipuser', 0, PARAM_INT);

admin_externalpage_setup('editusers');

$sitecontext = context_system::instance();
$site = get_site();

if (!has_capability('moodle/user:update', $sitecontext) and !has_capability('moodle/user:delete', $sitecontext)) {
    print_error('nopermissions', 'error', '', 'edit/delete users');
}

$strconfirm = get_string('confirm');
$strresendemail = get_string('resendemail');

$returnurl = new moodle_url('/admin/tool/mfa/skipusers.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));

$PAGE->set_secondary_navigation(false);

if (!empty($skipuser)) {
    $skipuserdata = new stdClass();
    $skipuserdata->userid = $skipuser;
    $skipuserdata->timemodified = time();
    $skipuserdata->status = $userstatus;
    if (!$existuserstatus = $DB->get_record('tool_mfa_skipusers', ['userid' => $skipuser])){
        $DB->insert_record('tool_mfa_skipusers', $skipuserdata);
    } else {
        $skipuserdata->id = $existuserstatus->id;
        $DB->update_record('tool_mfa_skipusers', $skipuserdata);
    }
    redirect($returnurl);
}
// The $user variable is also used outside of these if statements.
$user = null;
if ($confirmuser and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);
    if (!$user = $DB->get_record('user', array('id'=>$confirmuser, 'mnethostid'=>$CFG->mnet_localhost_id))) {
        print_error('nousers');
    }

    $auth = get_auth_plugin($user->auth);

    $result = $auth->user_confirm($user->username, $user->secret);

    if ($result == AUTH_CONFIRM_OK or $result == AUTH_CONFIRM_ALREADY) {
        redirect($returnurl);
    } else {
        echo $OUTPUT->header();
        redirect($returnurl, get_string('usernotconfirmed', '', fullname($user, true)));
    }

} else if ($resendemail && confirm_sesskey()) {
    if (!$user = $DB->get_record('user', ['id' => $resendemail, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0])) {
        print_error('nousers');
    }

    // Prevent spamming users who are already confirmed.
    if ($user->confirmed) {
        print_error('alreadyconfirmed');
    }

    $returnmsg = get_string('emailconfirmsentsuccess');
    $messagetype = \core\output\notification::NOTIFY_SUCCESS;
    if (!send_confirmation_email($user)) {
        $returnmsg = get_string('emailconfirmsentfailure');
        $messagetype = \core\output\notification::NOTIFY_ERROR;
    }

    redirect($returnurl, $returnmsg, null, $messagetype);
} else if ($delete and confirm_sesskey()) {              // Delete a selected user, after confirmation
    require_capability('moodle/user:delete', $sitecontext);

    $user = $DB->get_record('user', array('id'=>$delete, 'mnethostid'=>$CFG->mnet_localhost_id), '*', MUST_EXIST);

    if ($user->deleted) {
        print_error('usernotdeleteddeleted', 'error');
    }
    if (is_siteadmin($user->id)) {
        print_error('useradminodelete', 'error');
    }

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        $fullname = fullname($user, true);
        echo $OUTPUT->heading(get_string('deleteuser', 'admin'));

        $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
        $deleteurl = new moodle_url($returnurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');

        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$fullname'"), $deletebutton, $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        if (/*delete_user*/($user)) {
            \core\session\manager::gc(); // Remove stale sessions.
            redirect($returnurl);
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->header();
            echo $OUTPUT->notification($returnurl, get_string('deletednot', '', fullname($user, true)));
        }
    }
} else if ($acl and confirm_sesskey()) {
    if (!has_capability('moodle/user:update', $sitecontext)) {
        print_error('nopermissions', 'error', '', 'modify the NMET access control list');
    }
    if (!$user = $DB->get_record('user', array('id'=>$acl))) {
        print_error('nousers', 'error');
    }
    if (!is_mnet_remote_user($user)) {
        print_error('usermustbemnet', 'error');
    }
    $accessctrl = strtolower(required_param('accessctrl', PARAM_ALPHA));
    if ($accessctrl != 'allow' and $accessctrl != 'deny') {
        print_error('invalidaccessparameter', 'error');
    }
    $aclrecord = $DB->get_record('mnet_sso_access_control', array('username'=>$user->username, 'mnet_host_id'=>$user->mnethostid));
    if (empty($aclrecord)) {
        $aclrecord = new stdClass();
        $aclrecord->mnet_host_id = $user->mnethostid;
        $aclrecord->username = $user->username;
        $aclrecord->accessctrl = $accessctrl;
        $DB->insert_record('mnet_sso_access_control', $aclrecord);
    } else {
        $aclrecord->accessctrl = $accessctrl;
        $DB->update_record('mnet_sso_access_control', $aclrecord);
    }
    $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    redirect($returnurl);

} else if ($suspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);

    if ($user = $DB->get_record('user', array('id'=>$suspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if (!is_siteadmin($user) and $USER->id != $user->id and $user->suspended != 1) {
            $user->suspended = 1;
            // Force logout.
            \core\session\manager::kill_user_sessions($user->id);
            user_update_user($user, false);
        }
    }
    redirect($returnurl);

} else if ($unsuspend and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);

    if ($user = $DB->get_record('user', array('id'=>$unsuspend, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        if ($user->suspended != 0) {
            $user->suspended = 0;
            user_update_user($user, false);
        }
    }
    redirect($returnurl);

} else if ($unlock and confirm_sesskey()) {
    require_capability('moodle/user:update', $sitecontext);

    if ($user = $DB->get_record('user', array('id'=>$unlock, 'mnethostid'=>$CFG->mnet_localhost_id, 'deleted'=>0))) {
        login_unlock_account($user);
    }
    redirect($returnurl);
}

// create the user filter form
$ufiltering = new user_filtering();
echo $OUTPUT->header();

// Carry on with the user listing
$context = context_system::instance();
// These columns are always shown in the users list.
$requiredcolumns = array('city', 'country', 'lastaccess');
// Extra columns containing the extra user fields, excluding the required columns (city and country, to be specific).
$userfields = \core_user\fields::for_identity($context, true)->excluding(...$requiredcolumns);
$extracolumns = $userfields->get_required_fields();
// Get all user name fields as an array, but with firstname and lastname first.
$allusernamefields = \core_user\fields::get_name_fields(true);
$columns = array_merge($allusernamefields, $extracolumns, $requiredcolumns);

foreach ($columns as $column) {
    $string[$column] = \core_user\fields::get_display_name($column);
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "lastaccess") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        if ($column == "lastaccess") {
            $columnicon = ($dir == "ASC") ? "sort_desc" : "sort_asc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
            ['class' => 'iconsort']);

    }
    $$column = "<a href=\"skipusers.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
}

// We need to check that alternativefullnameformat is not set to '' or language.
// We don't need to check the fullnamedisplay setting here as the fullname function call further down has
// the override parameter set to true.
$fullnamesetting = $CFG->alternativefullnameformat;
// If we are using language or it is empty, then retrieve the default user names of just 'firstname' and 'lastname'.
if ($fullnamesetting == 'language' || empty($fullnamesetting)) {
    // Set $a variables to return 'firstname' and 'lastname'.
    $a = new stdClass();
    $a->firstname = 'firstname';
    $a->lastname = 'lastname';
    // Getting the fullname display will ensure that the order in the language file is maintained.
    $fullnamesetting = get_string('fullnamedisplay', null, $a);
}

// Order in string will ensure that the name columns are in the correct order.
$usernames = order_in_string($allusernamefields, $fullnamesetting);
$fullnamedisplay = array();
foreach ($usernames as $name) {
    // Use the link from $$column for sorting on the user's name.
    $fullnamedisplay[] = ${$name};
}
// All of the names are in one column. Put them into a string and separate them with a /.
$fullnamedisplay = implode(' / ', $fullnamedisplay);
// If $sort = name then it is the default for the setting and we should use the first name to sort by.
if ($sort == "name") {
    // Use the first item in the array.
    $sort = reset($usernames);
}

list($extrasql, $params) = $ufiltering->get_sql_filter();
$users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '',
    $extrasql, $params, $context);
$usercount = get_users(false);
$usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params);

if ($extrasql !== '') {
    echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading("$usercount ".get_string('users'));
}

$strall = get_string('all');
$baseurl = new moodle_url('/admin/tool/mfa/skipusers.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));

flush();

if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));
    $table = NULL;
} else {
    $countries = get_string_manager()->get_list_of_countries(true);
    if (empty($mnethosts)) {
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    }

    foreach ($users as $key => $user) {
        if (isset($countries[$user->country])) {
            $users[$key]->country = $countries[$user->country];
        }
    }
    if ($sort == "country") {
        foreach ($users as $user) {
            $susers[$user->id] = $user->country;
        }
        if ($dir === 'DESC') {
            arsort($susers);
        } else {
            asort($susers);
        }
        foreach ($susers as $key => $value) {
            $nusers[] = $users[$key];
        }
        $users = $nusers;
    }

    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = $fullnamedisplay;
    $table->attributes['class'] = 'admintable generaltable table-sm';
    foreach ($extracolumns as $field) {
        $table->head[] = ${$field};
    }
    $table->head[] = $city;
    $table->head[] = $country;
    $table->head[] = $lastaccess;
    $table->head[] = get_string('edit');
    $table->colclasses[] = 'centeralign';
    $table->head[] = "";
    $table->colclasses[] = 'centeralign';

    $table->id = "users";
    foreach ($users as $user) {
        $buttons = array();
        $lastcolumn = '';
        $btndata = $DB->record_exists('tool_mfa_skipusers', ['userid' => $user->id, 'status' => 1]);
        if (!empty($btndata)) {
            $statusurl = ['userstatus' => 0, 'skipuser' => $user->id];
            $btntext = get_string('enableauth','tool_mfa');
        }else{
            $statusurl = ['userstatus' => 1, 'skipuser' => $user->id];
            $btntext = get_string('disableauth','tool_mfa');
        }

        $authstatus = new moodle_url('/admin/tool/mfa/skipusers.php', $statusurl);
        $buttons[] = $OUTPUT->action_link($authstatus, $btntext, null, ['class' => 'btn btn-primary']);

        // the last column - confirm or mnet info
        if (is_mnet_remote_user($user)) {
            if (isset($mnethosts[$user->mnethostid])) {
                $lastcolumn = get_string($accessctrl, 'mnet').': '.$mnethosts[$user->mnethostid]->name;
            } else {
                $lastcolumn = get_string($accessctrl, 'mnet');
            }

        } else if ($user->confirmed == 0) {
            if (has_capability('moodle/user:update', $sitecontext)) {
                $lastcolumn = html_writer::link(new moodle_url($returnurl, array('confirmuser'=>$user->id, 'sesskey'=>sesskey())), $strconfirm);
            } else {
                $lastcolumn = "<span class=\"dimmed_text\">".get_string('confirm')."</span>";
            }
            $lastcolumn .= ' | ' . html_writer::link(new moodle_url($returnurl,
                    [
                        'resendemail' => $user->id,
                        'sesskey' => sesskey()
                    ]
                ), $strresendemail);
        }

        if ($user->lastaccess) {
            $strlastaccess = format_time(time() - $user->lastaccess);
        } else {
            $strlastaccess = get_string('never');
        }
        $fullname = fullname($user, true);

        $row = array ();
        $row[] = "<a href=\"../../../user/profile.php?id=$user->id\">$fullname</a>";
        foreach ($extracolumns as $field) {
            $row[] = s($user->{$field});
        }
        $row[] = $user->city;
        $row[] = $user->country;
        $row[] = $strlastaccess;
        if ($user->suspended) {
            foreach ($row as $k=>$v) {
                $row[$k] = html_writer::tag('span', $v, array('class'=>'usersuspended'));
            }
        }
        $row[] = implode(' ', $buttons);
        $row[] = $lastcolumn;
        $table->data[] = $row;
    }
}

// add filters
$ufiltering->display_add();
$ufiltering->display_active();

if (!empty($table)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
