<?php

require_once('../../config.php');
use mod_evaluation\skill_form;
$id = optional_param('id',0,PARAM_INT);
$sectionid = optional_param('sectionid',0,PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$url = new moodle_url('/mod/evaluation/skill.php',['id' => $id,'sectionid'=>$sectionid]);
$PAGE->set_title(get_string('addskill','mod_evaluation'));
$PAGE->set_heading(get_string('addskill','mod_evaluation'));
$PAGE->set_url($url);

$skill = new skill_form($url);
$redircturl = new moodle_url('/mod/evaluation/manage.php',['id'=>$id]);
if ($skill->is_cancelled()){
    redirect($redircturl);
} else if($skill->is_submitted()){
    $data = $skill->get_data();
    $skilldata = new stdClass();
    $skilldata->timemodified = time();
    $skilldata->name = $data->skillname;
    $skilldata->sectionid = $sectionid;
    $skilldata->visible = $data->visiblestatus;
    $skilldata->validation = $data->validaitonstatus;
    $insert = $DB->insert_record('evaluation_skill',$skilldata);
    if($insert){
        redirect($redircturl);
    }
}

echo $OUTPUT->header();
echo $skill->display();
echo $OUTPUT->footer();