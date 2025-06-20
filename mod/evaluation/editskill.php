<?php

require_once('../../config.php');

use mod_evaluation\skill_form;

$id = optional_param('id',0,PARAM_INT);
$skillid = optional_param('skillid',0,PARAM_INT);

$url = new moodle_url('/mod/evaluation/editskill.php',['id'=>$id,'skillid'=>$skillid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title(get_string('editskill','mod_evaluation'));
$PAGE->set_heading(get_string('editskill','mod_evaluation'));
require_login();
$cancelurl = new moodle_url('/mod/evaluation/manage.php',['id'=>$id]);

$skill = new skill_form($url);

if(!empty($skillid)){
    $data = $DB->get_record('evaluation_skill',['id' => $skillid]);
    $setdata = ['skillname' => $data->name,'visiblestatus'=>$data->visible,'validaitonstatus'=>$data->validation];
    $skill->set_data($setdata);
}
//$getdata = $skill->get_data();

if($skill->is_cancelled()){
    redirect($cancelurl);
}elseif ($skill->is_submitted()){
    $getdata = $skill->get_data();
    $udata = new stdClass();
    $udata->id = $skillid;
    $udata->name = $getdata->skillname;
    $udata->visible = $getdata->visiblestatus;
    $udata->validation = $getdata->validaitonstatus;
    $update = $DB->update_record('evaluation_skill',$udata);
    if($update){
        redirect($cancelurl);
    }
}

echo $OUTPUT->header();
$skill->display();
echo $OUTPUT->footer();