<?php

use mod_meltassessment\skill_form;

require_once('../../config.php');
$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$skillid = optional_param('skillid',0,PARAM_INT);

if($id){
    if (!$cm = get_coursemodule_from_id('meltassessment', $id)) {
        print_error('invalidcoursemodule');
    }

    $meltassessment = $DB->get_record('meltassessment', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/meltassessment/editskill.php',['id'=>$id,'skillid'=>$skillid]);
$PAGE->set_url($url);
$PAGE->set_title($course->shortname.': '.$meltassessment->name);
$PAGE->set_heading(get_string('pluginname','mod_meltassessment'));
$cancelurl = new moodle_url('/mod/meltassessment/manage.php',['id'=>$id]);
$skill = new skill_form($url);

if(!empty($skillid)){
    $data = $DB->get_record('meltassessment_skill',['id' => $skillid]);
    $setdata = ['skillname' => $data->name,'visiblestatus'=>$data->visible,'validaitonstatus'=>$data->validation];
    $skill->set_data($setdata);
}

if($skill->is_cancelled()){
    redirect($cancelurl);
}elseif ($skill->is_submitted()){
    $getdata = $skill->get_data();
    $udata = new stdClass();
    $udata->id = $skillid;
    $udata->name = $getdata->skillname;
    $udata->visible = $getdata->visiblestatus;
    $udata->validation = $getdata->validaitonstatus;
    $update = $DB->update_record('meltassessment_skill',$udata);
    if($update){
        redirect($cancelurl);
    }
}

echo $OUTPUT->header();
$skill->display();
echo $OUTPUT->footer();