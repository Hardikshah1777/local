<?php

use mod_progressreport\constants;
use mod_progressreport\output\field;
use mod_progressreport\output\market;
use mod_progressreport\output\progressreport_form;
use mod_progressreport\output\section;
use mod_progressreport\output\skill;

define('SAVE', 1);
define('REQUIRED',1);
include_once('../../config.php');
require_once($CFG->dirroot . '/mod/progressreport/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$progressreportuserid = optional_param('progressreportuserid', 0, PARAM_INT);

$progressreportuser = $DB->get_record('progressreport_user',['id' => $progressreportuserid]);
$userid = $progressreportuser->userid;
$progressreportid = $progressreportuser->progressreportid;
$attempt = optional_param('attempt',0,PARAM_INT);

$redirecturl = new moodle_url('/mod/progressreport/view.php', ['id' => $id]);

if ($id) {
    if (!$cm = get_coursemodule_from_id('progressreport', $id)) {
        print_error('invalidcoursemodule');
    }

    $progressreport = $DB->get_record('progressreport', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/progressreport/editprogressreport_form.php', array('id' => $cm->id));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname . ': ' . $progressreport->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($progressreport);

$userdata = core_user::get_user($userid);

$progressreportform = new progressreport_form();
$progressreportform->progressreportuserid = $progressreportuserid;
$progressreportform->notes = $progressreportuser->notes;
$progressreportform->save = $progressreportuser->save;
$progressreport = $DB->get_record('progressreport', ['id' => $progressreportid]);
$progressreportnuserinfo = $DB->get_records('progressreport_field', ['progressreportid' => $progressreportid]);

$progressreportform->userid = $userdata->id;
$progressreportform->moduleid = $id;
$progressreportform->progressreportid = $progressreport->id;
$progressreportform->progressreportname = $progressreport->name;
$progressreportform->progressreporturl = new moodle_url('/mod/progressreport/editprogressreport_form.php',
        ['userid' => $userid, 'progressreportid' => $progressreportid, 'id' => $id]);
$progressreportform->lesson = $progressreportuser->nolesson;
$progressreportform->attempt = $attempt;

foreach ($progressreportnuserinfo as $userinfo) {
    $userfielddata = new field($userinfo->field, $userinfo->fieldvalue, $userid);
    $userfielddata->id = $userinfo->id;
    $userfielddata->progressreportid = $progressreportuserid;
    $userfielddata->attemptuser = $progressreportuser->attempt;
    $progressreportform->add_field($userfielddata);
}

$progressreportmarkets = get_progressreport_market($progressreportid);
$i = 0;
foreach ($progressreportmarkets as $progressreportmarket) {
    $i++;
    $market = new market($progressreportmarket->id, $progressreportmarket->name);
    $progressreportform->add_market($market);
    $market->number = $i;
}

$averages = $DB->get_records('progressreport_user_lesson',['progressreportuserid' => $progressreportuserid]);
$progressreportform->average = $averages;


$progressreportsections = get_progressreport_section($progressreportid);
foreach ($progressreportsections as $progressreportsec) {
    $section = new section($progressreportsec->id, $progressreportsec->name);
    $progressreportform->add_section($section);
    $skills = get_progressreport_sections_skill($progressreportsec->id);
    foreach ($skills as $skill) {
        $skillnew = new skill($skill->id, $skill->name);
        $section->add_skill($skillnew);
        $skillnew->nolesson = $progressreportuser->nolesson;
        $skillnew->progressreportuserid = $progressreportuser->id;
        $skillnew->validation($skill->validation);
    }
}

$progressreportdatas = $DB->get_records('progressreport_user_skill', ['progressreportuserid'=>$progressreportuser->id],'','*');
$progressreportform->set_progressreportdata($progressreportdatas);

echo $OUTPUT->header();
echo $OUTPUT->render($progressreportform);
echo $OUTPUT->footer();


