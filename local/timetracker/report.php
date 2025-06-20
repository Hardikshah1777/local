<?php

require(dirname(__FILE__).'/../../config.php');

require_login(null, false);

// PERMISSION.
require_capability('local/timetracker:viewreport', context_system::instance(), $USER->id);

$title = get_string('pluginname', 'local_timetracker');
$heading = get_string('report', 'local_timetracker');
$url = '/local/timetracker/report.php';  
$baseurl = new moodle_url($url);

$PAGE->set_url($url);
$PAGE->set_pagelayout('course');
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
	 $sql = "SELECT tl.id,u.firstname,u.lastname,c.fullname,sum(tl.timemodified-tl.timecreated) as total FROM {timetracker_log} tl
				JOIN {user} u on u.id=tl.userid
				JOIN {course} c on c.id=tl.courseid
				GROUP BY tl.userid,tl.courseid ORDER BY u.firstname ASC,c.fullname ASC";
				
   $local_timetrackers = $DB->get_records_sql($sql);
  // print_r($local_timetrackers);
  // $local_timetrackers = $DB->get_records('timetracker_log');

    if(!empty($local_timetrackers)){
        $table = new html_table();
        $table->tablealign="left";
        $table->head  = array(get_string('user'),get_string('course'),'Timespent');
        $table->align = array('centre');
        $table->width = '50%';
        $table->attributes['class'] = 'generaltable';
        $table->data = array();

    foreach ($local_timetrackers as $log) {
		  // print_r($log);
			$init = $log->total;
			$hours = floor($init / 3600);
			$minutes = floor(($init / 60) % 60);
			$seconds = $init % 60;
			$duration = $hours.':'. $minutes.':'. $seconds;
        $table->data[] = array($log->firstname,$log->fullname,$duration);
    }
    echo html_writer::table($table);        
    }
   
echo $OUTPUT->footer();

