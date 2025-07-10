<?php

use mod_evaluation\output\evaluationform;
use mod_evaluation\output\level;
use mod_evaluation\output\section;
use mod_evaluation\output\skill;
use mod_evaluation\output\userfield;

define('DEFAULTVAL',90);
define('PASSGRADE',80);
define('TRAINIGREQUIRED',1);
define('MAXIMUM',100);

require_once('../../config.php');
require_once ($CFG->dirroot.'/mod/evaluation/locallib.php');


$id = optional_param('id','0',PARAM_INT);
$evaluationuser = $DB->get_record('evaluation_user',['id' => $id]);
$userid = $evaluationuser->userid;
$evaluationid = $evaluationuser->evaluationid;

$attempt = optional_param('attempt',0,PARAM_INT);
$moduleid = optional_param('moduleid',0,PARAM_INT);
$agree = optional_param('agree',0,PARAM_INT);

$url = new moodle_url('/mod/evaluation/userview.php',['id'=>$id]);
$redirecturl = new moodle_url('/mod/evaluation/view.php',['id'=>$moduleid]);

if($moduleid) {
    if (!$cm = get_coursemodule_from_id('evaluation', $moduleid)) {
        print_error('invalidcoursemodule');
    }
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    require_course_login($course, true, $cm);
    $context = context_module::instance($cm->id);
}
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname','mod_evaluation'));
$PAGE->set_heading(get_string('pluginname','mod_evaluation'));
require_login();
echo $OUTPUT->header();

$userdata = core_user::get_user($userid);

$evaluation = $DB->get_record('evaluation',['id'=>$evaluationid]);
$evaluationform = new evaluationform();
$evaluationuser = $DB->get_record('evaluation_user',['id' => $id]);
$evaluationuserinfo = $DB->get_records('evaluation_userinfo',['evaluationid'=>$evaluationid]);

foreach ($evaluationuserinfo as $userinfo){
    $userfielddata = new userfield($userinfo->infofiled,$userinfo->infovalue,$userid);
    $evaluationform->add_userfield($userfielddata);
    $userfielddata->id = $userinfo->id;
    $userfielddata->evaluationuserid = $evaluationuser->id;
    $userfielddata->attemptuser = $attempt;
}

if(!has_capability('mod/evaluation:evaluations',$context)){
    $evaluationform->student = 1;
}

$evaluationlevels = get_evaluation_level($evaluationid);
foreach ($evaluationlevels as $evaluationlevel){
    $level = new level($evaluationlevel->id,$evaluationlevel->name,$evaluationlevel->status,$evaluationlevel->grade);
    $evaluationform->add_level($level);
}

$evaluationdata = $DB->get_records_menu('evaluation_user_skill_level', ['evaluationuserid'=>$evaluationuser->id], '', 'skillid,levelid');
$evaluationform->set_evaluationdata($evaluationdata);
$evaluationform->username = $userdata->firstname.' '.$userdata->lastname;
$evaluationform->userid = $userdata->id;
$evaluationform->city = $userdata->city;
$evaluationform->id = $id;
$evaluationform->comments = $evaluationuser->comments;
$evaluationform->moduleid = $USER->moduleid;
$evaluationform->evaluationname = $evaluation->name;
$evaluationform->evaluationid = $evaluation->id;
$evaluationform->evaluationurl = new moodle_url('/mod/evaluation/evaluation_form.php',['userid'=>$userid,'evaluationid'=>$evaluationid]);
$evaluationform->attempt = $attempt;
$evaluationform->agree = $evaluationuser->agree;
$evaluationform->result = $evaluationuser->pass;
$evaluationform->grade = $evaluationuser->grade;
$evaluationform->additionaltraining = $evaluationuser->additionaltraining;
if($evaluationuser->grade < PASSGRADE && $evaluationuser->grade != 0){
    $evaluationform->reson = get_string('requirepassgrade','mod_evaluation');
}elseif ($evaluationuser->additionaltraining == 1){
    $evaluationform->reson = get_string('urgsection','mod_evaluation');
}
$evaluationsection = get_evaluation_sections($evaluationid);

foreach ($evaluationsection as $evaluationsec){
    $deletecheck = delete_check_section($evaluationsec->id,$id);
    if(!empty($deletecheck)) {
        $section = new section($evaluationsec->id, $evaluationsec->name);
        $section->saferskill = $evaluationsec->saferskill;
        $evaluationform->add_section($section);
        $skills = get_evaluation_sections_skill($evaluationsec->id);
        foreach ($skills as $skill) {
            $deleteuser = $DB->get_field('evaluation_user_skill_level', 'id', ['skillid' => $skill->id, 'evaluationuserid' => $id]);
            if (!empty($deleteuser)) {
                $skillnew = new skill($skill->id, $skill->name);
                $section->add_skill($skillnew);
                $skillnew->evaluationuserid = $id;
                $evaluationlevels = get_evaluation_level($evaluationid);
            }
        }
    }
}


if(!empty($agree)){
    $agreedata = new stdClass();
    $agreedata->agree = $agree;
    $agreedata->id = $id;
    $updateagree = $DB->update_record('evaluation_user',$agreedata);
    $successurl =  new moodle_url('/mod/evaluation/view.php',['id'=>$moduleid]);
    redirect($successurl);
}

echo $OUTPUT->render($evaluationform);
echo $OUTPUT->footer();