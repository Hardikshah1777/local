<?php

use mod_progressreport\skill_form;

require_once('../../config.php');
$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$sectionid = optional_param('sectionid',0,PARAM_INT);

if($id){
    if (!$cm = get_coursemodule_from_id('progressreport', $id)) {
        print_error('invalidcoursemodule');
    }

    $progressreport = $DB->get_record('progressreport', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/progressreport/skill.php',['id'=>$id,'sectionid'=>$sectionid]);
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$progressreport->name);
$PAGE->set_heading(get_string('pluginname','mod_progressreport'));
$skill = new skill_form($url);
$redircturl = new moodle_url('/mod/progressreport/manage.php',['id'=>$id]);
if ($skill->is_cancelled()){
    redirect($redircturl);
} else if($skill->is_submitted()){
    $data = $skill->get_data();
    $skilldata = new stdClass();
    $skilldata->sectionid = $sectionid;
    $skilldata->name = $data->skillname;
    $skilldata->visible = $data->visiblestatus;
    $skilldata->validation = $data->validaitonstatus;
    $skilldata->timemodified = time();
    $insert = $DB->insert_record('progressreport_skill',$skilldata);
    if($insert){
        redirect($redircturl);
    }
}
echo $OUTPUT->header();
$skill->display();
echo $OUTPUT->footer();