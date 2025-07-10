<?php
require_once(dirname(__FILE__).'/../../config.php');

global $DB,$USER,$CFG;

require_login($SITE);
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

if ($action == 'unlock') {
	$DB->update_record('timetracker_lock ',array('id'=>$id,'userstatus'=>0));
}

$title = get_string('pluginname', 'local_timetracker');

$data = $DB->get_record('timetracker_lock',array('id'=>$id));
$user = $DB->get_record('user',array('id' => $data->userid));
$heading = get_string('unlockmessage','local_timetracker',fullname($user));

$url = '/local/timetracker/unlock.php';
$baseurl = new moodle_url($url,array('id' => $id));

$PAGE->set_url($baseurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);

// Create the breadcrumb.
$basetext = get_string('administrationsite');
$baseurl = new moodle_url('/admin/search.php');
$PAGE->navbar->add($basetext, $baseurl);

$basetext = get_string('plugin');
$baseurl = new moodle_url('/admin/category.php', array(
	'category' => 'module'
));
$PAGE->navbar->add($basetext, $baseurl);

$basetext = get_string('localplugins');
$baseurl = new moodle_url('/admin/category.php', array(
	'category' => 'localplugins'
));
$PAGE->navbar->add($basetext, $baseurl);

$baseurl = new moodle_url('/admin/category.php', array(
	'category' => 'local_timetracker'
));
$PAGE->navbar->add($title, $baseurl);

$PAGE->navbar->add($heading, new moodle_url($PAGE->url));

echo $OUTPUT->header();

if($data->userstatus == 1){
	$unlocklink = new moodle_url($url,array('id' => $id,'action' => 'unlock'));

	echo html_writer::start_tag('a', array('href' => $unlocklink));

	echo  get_string('unlockhere', 'local_timetracker');
	echo html_writer::end_tag('a');	
}
else{
	echo $OUTPUT->box( get_string('userunlocked', 'local_timetracker',fullname($user)), 'alert alert-success alert-block fade in ');
}
	
echo $OUTPUT->footer();
