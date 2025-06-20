<?php

require_once('../../config.php');
require_once $CFG->libdir . '/tablelib.php';

use mod_evaluation\output\section;
use mod_evaluation\output\sectionlist;
use mod_evaluation\output\skill;
use mod_evaluation\section_form;

$id = required_param('id',PARAM_INT);
$skillid = optional_param('skillid',0,PARAM_INT);
$sectionid = optional_param('sectionid',0,PARAM_INT);

$cm = get_coursemodule_from_id('evaluation', $id);


$url = new moodle_url('/mod/evaluation/manage.php',['id'=>$id]);
$returnurl = new moodle_url('/course/view.php',['id'=>$cm->course]);


$PAGE->set_url($url);
$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_title(get_string('pluginname','mod_evaluation'));
$PAGE->set_heading(get_string('pluginname','mod_evaluation'));
require_course_login($cm->course,true,$cm);
$section = new section_form($url);
$data = $section->get_data();

if(!empty($skillid)){
    $updatedata = new stdClass();
    $updatedata->id = $skillid;
    $updatedata->deleted = 1;
    $update = $DB->update_record('evaluation_skill',$updatedata);
    redirect($url);
}


if(!empty($sectionid)){
    $deletedata = new stdClass();
    $deletedata->id = $sectionid;
    $deletedata->deleted = 1;
    $delete = $DB->update_record('evaluation_section',$deletedata);
    if($delete){
        $skills = $DB->get_records('evaluation_skill',['sectionid' => $sectionid]);
        foreach ($skills as $skill){
            $deleteskilldata = new stdClass();
            $deleteskilldata->sectionid = $sectionid;
            $deleteskilldata->id = $skill->id;
            $deleteskilldata->deleted = 1;
            $update = $DB->update_record('evaluation_skill',$deleteskilldata);
        }
        redirect($url);
    }
}


if($section->is_cancelled()){
    redirect($returnurl);
}elseif ($section->is_submitted()){
    $data->timemodified = time();
    $sectiondata = new stdClass();
    $sectiondata->evaluationid  = $cm->instance;
    $sectiondata->name = $data->sectionname;
    $sectiondata->visible = $data->visiblestatus;
    $sectiondata->saferskill = $data->saferskill;
    $sectiondata->timemodified = $data->timemodified;
    $insert = $DB->insert_record('evaluation_section',$sectiondata);
}
echo $OUTPUT->header();

$section->display();



if(!empty($id)){

    $skillid = ['id'=>$id];
    $skillurl = new moodle_url('/mod/evaluation/skill.php',$skillid);
    $addskil = get_string('addskill','mod_evaluation');

    $condition = ['evaluationid' => $cm->instance,'deleted' => 0];
    $sectionrecs = $DB->get_records('evaluation_section',$condition, 'saferskill,sortorder');

    $sectionlist = new sectionlist();

    foreach ($sectionrecs as $sectionrec){

        $section = new section($sectionrec->id,$sectionrec->name);
        $skills = $DB->get_records('evaluation_skill',['sectionid' => $sectionrec->id,'deleted' => 0]);
        $section->moduleid = $id;
        $section->visiblestatus = $sectionrec->visible;
        $section->saferskill = $sectionrec->saferskill;
        foreach ($skills as $skillrec){
            $skill = new skill($skillrec->id,$skillrec->name);
            $skill->moduleid = $id;
            $section->add_skill($skill);
        }
        $sectionlist->add_section($section);
    }
    echo $OUTPUT->render($sectionlist);
}

$PAGE->requires->js_call_amd('mod_evaluation/move', 'init',['skillsectionwrapper', 'move_updown_field.class']);
echo $OUTPUT->footer();