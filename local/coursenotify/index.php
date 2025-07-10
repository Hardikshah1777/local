<?php

require_once dirname(__FILE__).'/../../config.php';
use local_coursenotify\utility,local_coursenotify\notificationlist;

$courseid = required_param('courseid', PARAM_INT);
$title = get_string('indextitle',utility::$component);
$url = new moodle_url('/local/coursenotify/index.php', array('courseid' => $courseid));
$perpage = optional_param('perpage',utility::$perpage,PARAM_INT);
$PAGE->set_url($url);
require_login($courseid);

$PAGE->set_title($title);
$PAGE->set_heading($title);

require_capability('local/coursenotify:editnotification',$PAGE->context);

$table = new notificationlist($courseid);

$PAGE->set_button(html_writer::link(utility::get_editlink($courseid),
    get_string('add',utility::$component),array('class'=>'btn btn-primary')));

echo $OUTPUT->header();

$table->out($perpage,false);

echo $OUTPUT->footer();
