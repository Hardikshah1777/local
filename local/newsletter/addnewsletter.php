<?php

use local_newsletter\newsletter_form;

require_once ('../../config.php');
$courseid = 26;
$id = optional_param('id', 0, PARAM_INT);
$url = new moodle_url('/local/newsletter/addnewsletter.php', ['id' => $id, 'courseid' => $courseid]);
$returnurl = new moodle_url('/local/newsletter/index.php');
$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('add:title', 'local_newsletter'));
$PAGE->set_heading(get_string('add:heading', 'local_newsletter'));
require_login();

if (!is_siteadmin()) {
    redirect(new moodle_url('/my'));
}
global $CFG, $DB;
$sql = "SELECT cm.id,f.id as feedbackid,f.name FROM {course_modules} cm 
        JOIN {feedback} f ON f.id = cm.instance WHERE cm.course = :courseid AND cm.visible = 1 AND 
        cm.module IN (SELECT m.id FROM {modules} m WHERE m.name = :modulename AND m.visible = 1)";

$modules = $DB->get_records_sql($sql, ['courseid' => $courseid, 'modulename' => 'feedback']);

$cutomdata = [];
$feedbacks = [];
foreach ($modules as $module) {
    $feedbacks[$module->id] = $module->name;
}

$cutomdata['feedback'] = $feedbacks;
$cutomdata['sitelogo'] = $CFG->wwwroot.'/local/newsletter/pix/EU-logo.jpg';;
$newsletterform = new newsletter_form($url, $cutomdata);

if ($id) {
    $feedbackrec = $DB->get_record('local_newsletter', ['id' => $id]);
    $feedbackrec->activitylist = $feedbackrec->activityid;
    $feedbackrec->message = ['text' => $feedbackrec->message];
    $newsletterform->set_data($feedbackrec);
}


if ($newsletterform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $newsletterform->get_data()) {
    $insertdata = new stdClass();
    $insertdata->name = $data->name;
    $insertdata->subject = $data->subject;
    $insertdata->message = $data->message['text'];
    $insertdata->scheduledate = $data->scheduledate;
    $insertdata->enddate = $data->enddate;
    $insertdata->activityid = $data->activitylist;
    $insertdata->remindermail = $data->remindermail;
    $insertdata->timecreated = time();
    if (isset($id) && $id > 0) {
        $insertdata->id = $id;
        $DB->update_record('local_newsletter', $insertdata);
        redirect($returnurl, get_string('editsuccessfully', 'local_newsletter'));
    }else {
        $DB->insert_record('local_newsletter', $insertdata);
        redirect($returnurl, get_string('addsuccessfully', 'local_newsletter'));
    }
}


echo $OUTPUT->header();
$newsletterform->display();
echo $OUTPUT->footer();