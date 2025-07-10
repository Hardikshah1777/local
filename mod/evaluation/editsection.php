<?php

require_once('../../config.php');

use mod_evaluation\section_form;

$id = optional_param('id',0,PARAM_INT);
$sectionid = optional_param('sectionid',0,PARAM_INT);

$url = new moodle_url('/mod/evaluation/editsection.php',['id'=>$id,'sectionid'=>$sectionid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title(get_string('editsection','mod_evaluation'));
$PAGE->set_heading(get_string('editsection','mod_evaluation'));
require_login();
$cancelurl = new moodle_url('/mod/evaluation/manage.php',['id'=>$id]);

$section = new section_form($url);

if(!empty($sectionid)){
    $data = $DB->get_record('evaluation_section',['id' => $sectionid]);
    $setdata = ['sectionname' => $data->name,'visiblestatus'=>$data->visible,'saferskill'=>$data->saferskill];
    $section->set_data($setdata);
}
$getdata = $section->get_data();

if($section->is_cancelled()){

    redirect($cancelurl);
}elseif ($section->is_submitted()){
    $getdata = $section->get_data();
    $udata = new stdClass();
    $udata->id = $sectionid;
    $udata->name = $getdata->sectionname;
    $udata->saferskill = $getdata->saferskill;
    $udata->visible = $getdata->visiblestatus;
    $update = $DB->update_record('evaluation_section',$udata);
    if($update){
        redirect($cancelurl);
    }
}

echo $OUTPUT->header();
echo $section->display();
echo $OUTPUT->footer();