<?php

require_once('../../config.php');

use mod_meltassessment\section_form;

$id = optional_param('id',0,PARAM_INT);
$sectionid = optional_param('sectionid',0,PARAM_INT);

$url = new moodle_url('/mod/meltassessment/editsection.php',['sectionid'=>$sectionid,'id'=>$id]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title(get_string('editsection','mod_meltassessment'));
$PAGE->set_heading(get_string('editsection','mod_meltassessment'));
require_login();

$section = new section_form($url);
if(!empty($sectionid)){
    $data = $DB->get_record('meltassessment_section',['id' => $sectionid]);
    $setdata = ['sectionname' => $data->name,'visiblestatus'=>$data->visible];
    $section->set_data($setdata);
}
$cancelurl = new moodle_url('/mod/meltassessment/manage.php',['id'=>$id]);
$getdata = $section->get_data();

if($section->is_cancelled()){
    redirect($cancelurl);
}elseif ($section->is_submitted()){
    $sectiondata = new stdClass();
    $sectiondata->id = $sectionid;
    $sectiondata->name = $getdata->sectionname;
    $sectiondata->visible = $getdata->visiblestatus;
    $update = $DB->update_record('meltassessment_section',$sectiondata);
    if($update){
        redirect($cancelurl);
    }
}

echo $OUTPUT->header();
$section->display();
echo $OUTPUT->footer();