<?php

function local_timetracker_extend_navigation(global_navigation $nav) {
    global $PAGE,$USER;
    $pagetype = $PAGE->pagetype;
    $inheader = $PAGE->state <= $PAGE::STATE_BEFORE_HEADER;
    $inheader && $PAGE->requires->jquery();
    $PAGE->requires->js('/local/timetracker/js/lock.js', $inheader);
    if (!is_siteadmin($USER->id) && is_students() &&
            (in_array($pagetype, ['mod-*', 'course-*']) || preg_match('~^course-view-~', $pagetype) ||
                    preg_match('~^mod-~', $pagetype))) {
        $PAGE->requires->js('/local/timetracker/js/custom.js', $inheader);
        $PAGE->requires->js('/local/timetracker/js/bootstrap.min.js');

    }
}

function local_timetracker_standard_after_main_region_html() {
    global $PAGE, $USER;
    echo '<script src = https://code.jquery.com/jquery-3.6.0.min.js></script>';
    if ($PAGE->pagelayout == 'embedded') {
        $PAGE->requires->js('/local/timetracker/js/lock.js');
        if (!is_siteadmin($USER->id) && is_students()) {
            $PAGE->requires->js('/local/timetracker/js/custom.js');
		$PAGE->requires->js('/local/timetracker/js/bootstrap.min.js');
        }
    }
}

function is_students() {
        global $DB, $USER;
		$user = $DB->get_records('role_assignments',array('roleid'=>5,'userid'=>$USER->id));

        if ($user) {
            return true;
        } else {
            return false;
        }
    }

function unlock_notification($id){
	global $DB,$USER;

	$url = new moodle_url('/local/timetracker/unlock.php',array('id'=>$id));
	$lock = $DB->get_record('timetracker_lock',array('id' => $id));
	$userto = get_admin();
	$userfrom = $DB->get_record('user',array('id' => $USER->id));

	$a =new stdclass();
	$a->name = fullname($userfrom);

	$message = new \core\message\message();
	$message->courseid = SITEID;
	$message->component = 'local_timetracker';
	$message->name = 'course_unlock';
	$message->notification = 1;
	$message->userfrom = $userfrom;
	$message->userto = $userto;
	$message->subject = get_string('lockuser','local_timetracker',$a);
	$message->fullmessage = text_to_html(get_string('unlockmessage','local_timetracker',$a->name));
	$message->fullmessageformat = FORMAT_HTML;
	$message->fullmessagehtml = get_string('unlockmessage','local_timetracker',$a->name);
	$message->smallmessage = '';
	$message->contexturl = $url->out(false);
	$message->contexturlname = get_string('unlockhere', 'local_timetracker');
	$message->customdata = [
		'id' => $id,
		'action' => 'unlock',
	];

	message_send($message);
	return true;
}

function local_timetracker_isalreadypassed($contextid) {
    global $DB, $USER;
    $context = context::instance_by_id($contextid);
    $courseid = $context->get_course_context()->instanceid;
    $timethreshold = max(time() - 30 * MINSECS, $USER->currentlogin);
    $alreadypassed = $DB->record_exists_select('timetracker_securitycheck',
            'answerstatus = 0 AND userstatus = 1 AND userid= :userid AND timecreated > :timethreshold',
            ['userid' => $USER->id, 'timethreshold' => $timethreshold]);
    return $alreadypassed;
}
