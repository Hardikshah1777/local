<?php

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$enable_block = false;
if (file_exists('../../blocks/autoattend/locallib.php')) {
    require_once('../../blocks/autoattend/locallib.php');
    $enable_block = true;
}

$id = required_param('id', PARAM_INT);

$url = new moodle_url('/mod/autoattendmod/index.php', array('id'=>$id));
$PAGE->set_url($url);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    jbxl_print_error('invalidcourseid');
}
$context = context_course::instance($course->id);

require_login($course);
$PAGE->set_pagelayout('incourse');

$urlparams['id'] = $id;
$urlparams['course'] = $course->id;
//add_to_log($course->id, 'autoattendmod', 'view all', $url->out(false), $course->id);
$event = apply_get_event($context, 'view all', $urlparams);
jbxl_add_to_log($event);


/// Print the page header
$strautoattendmods = get_string('modulenameplural', 'autoattendmod');
$strautoattendmod  = get_string('modulename', 'autoattendmod');

$PAGE->navbar->add($strautoattendmods);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('modulename', 'autoattendmod').'&nbsp;'.get_string('activities'));
echo $OUTPUT->header();

/// Get all the appropriate data
if (! $autoattendmods = get_all_instances_in_course('autoattendmod', $course)) {
    $url = new moodle_url('/course/view.php', array('id'=>$course->id));
    notice(get_string('thereareno', 'moodle', $strautoattendmods), $url);
    die;
}
$usesections = course_format_uses_sections($course->format);

/// Print the list of instances (your module will probably extend this)
$timenow = time();
$strname  = get_string('name');
$strsectionname = get_string('sectionname', 'format_'.$course->format);
$strsessionnum  = get_string('session_num', 'autoattendmod');

$table = new html_table();

if ($usesections) {
    $table->head  = array($strsectionname, $strname, $strsessionnum);
    $table->align = array('center', 'left', 'center');
} 
else {
    $table->head  = array($strname, $strsessionnum);
    $table->align = array('left', 'center');
}


//
foreach ($autoattendmods as $autoattendmod) {
    $viewurl = new moodle_url('/mod/autoattendmod/view.php', array('id'=>$autoattendmod->coursemodule));

    $dimmedclass = $autoattendmod->visible ? '' : 'class="dimmed"';
    $link = '<a '.$dimmedclass.' href="'.$viewurl->out().'">'.$autoattendmod->name.'</a>';

    if ($usesections) {
        $tabledata = array(get_section_name($course, $autoattendmod->section), $link);
    }
    else {
        $tabledata = array($link);
    }

    if ($enable_block) {
        $tabledata[] = intval(autoattend_count_sessions($course->id, 0));
    }
    else {
        $tabledata[] = ' - ';
    }

    $table->data[] = $tabledata;
}
echo '<br />';

echo html_writer::table($table);

/// Finish the page
echo $OUTPUT->footer();

