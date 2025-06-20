<?php
define('AJAX_SCRIPT', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

if (!confirm_sesskey()) {
	throw new moodle_exception('invalidsesskey', 'error');
}

ob_start();
header('Expires: Sun, 28 Dec 1997 09:32:45 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');

$courseid = required_param('course', PARAM_INT);

$PAGE->set_context(get_context_instance(CONTEXT_COURSE, $courseid));


$htmlarray = array();
$courses = array($courseid=>$DB->get_record('course', array('id' => $courseid)));

if (isset($USER->lastcourseaccess[$courseid])) {
	$courses[$courseid]->lastaccess = $USER->lastcourseaccess[$courseid];
} else {
	$courses[$courseid]->lastaccess = 0;
}

if ($modules = $DB->get_records('modules')) {
	foreach ($modules as $mod) {
		if (file_exists($CFG->dirroot.'/mod/'.$mod->name.'/lib.php')) {
			include_once($CFG->dirroot.'/mod/'.$mod->name.'/lib.php');
			$fname = $mod->name.'_print_overview';
			if (function_exists($fname)) {
				$fname($courses,$htmlarray);
			}
		}
	}
}

if (array_key_exists($courseid,$htmlarray)) {
	foreach ($htmlarray[$courseid] as $modname => $html) {
    	echo $html;
	}
}

header('Content-Length: ' . ob_get_length() );
ob_end_flush();