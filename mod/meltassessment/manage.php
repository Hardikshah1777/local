<?php

use mod_meltassessment\output\skill;
use mod_meltassessment\output\section;
use mod_meltassessment\output\sectionlist;
use mod_meltassessment\section_form;

require_once('../../config.php');

$id = optional_param('id', 0, PARAM_INT);//course module id
$sectionid = optional_param('sectionid',0,PARAM_INT);
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
$url = new moodle_url('/mod/meltassessment/manage.php',['id'=>$id]);
$returnurl = new moodle_url('/course/view.php',['id'=>$cm->course]);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname','mod_meltassessment'));
$PAGE->set_heading(get_string('pluginname','mod_meltassessment'));

$section = new section_form($url);
$data = $section->get_data();

if($section->is_cancelled()){
    redirect($returnurl);
}elseif ($section->is_submitted()){
    $sectiondata = new stdClass();
    $sectiondata->meltassessmentid  = $cm->instance;
    $sectiondata->name = $data->sectionname;
    $sectiondata->visible = $data->visiblestatus;
    $sectiondata->timemodified = time();
    $insert = $DB->insert_record('meltassessment_section',$sectiondata);
}

if(!empty($skillid)){
    $updatedata = new stdClass();
    $updatedata->id = $skillid;
    $updatedata->deleted = 1;
    $update = $DB->update_record('meltassessment_skill',$updatedata);
    redirect($url);
}

if(!empty($sectionid)){
    $deletedata = new stdClass();
    $deletedata->id = $sectionid;
    $deletedata->deleted = 1;
    $delete = $DB->update_record('meltassessment_section',$deletedata);
    if($delete){
        $skills = $DB->get_records('meltassessment_skill',['sectionid' => $sectionid]);
        foreach ($skills as $skill){
            $deleteskilldata = new stdClass();
            $deleteskilldata->sectionid = $sectionid;
            $deleteskilldata->id = $skill->id;
            $deleteskilldata->deleted = 1;
            $update = $DB->update_record('meltassessment_skill',$deleteskilldata);
        }
    }
    redirect($url);
}

echo $OUTPUT->header();
$section->display();

if(!empty($id)){

    $condition = ['meltassessmentid' => $cm->instance,'deleted' => 0];
    $sectionrecs = $DB->get_records('meltassessment_section',$condition, 'sortorder');

    $sectionlist = new sectionlist();

    foreach ($sectionrecs as $sectionrec){
        $section = new section($sectionrec->id,$sectionrec->name);
        $section->visiblestatus = $sectionrec->visible;
        $section->moduleid = $id;
        $sectionlist->add_section($section);
        $skills = $DB->get_records('meltassessment_skill',['sectionid' => $sectionrec->id,'deleted' => 0]);
        foreach ($skills as $skillrec){
            $skill = new skill($skillrec->id,$skillrec->name);
            $skill->moduleid = $id;
            $section->add_skill($skill);
        }
    }
    echo $OUTPUT->render($sectionlist);
}
$PAGE->requires->js_call_amd('mod_meltassessment/sectionmove', 'init',['skillsectionwrapper', 'move_updown_field.class']);
echo $OUTPUT->footer();